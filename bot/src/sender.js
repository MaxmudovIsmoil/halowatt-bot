const { query } = require('./db');

// Telegram ba'zi xatolarni qaytaradi, lekin natija baribir bizga kerakli holatda
// (masalan matn allaqachon shunday, yoki xabar allaqachon o'chirilgan) — bularni xato hisoblamaymiz.
function isBenignTelegramError(message) {
  const m = (message || '').toLowerCase();
  return m.includes('message is not modified') || m.includes('message to delete not found');
}

// **bold** → <b>bold</b>, _italic_ → <i>italic</i>, `code` → <code>code</code>,
// [text](url) → <a href="url">text</a>. & < > escaped before tag injection.
function mdToHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\*\*(.+?)\*\*/gs, '<b>$1</b>')
    .replace(/(?<!\w)_([^_\n]+?)_(?!\w)/g, '<i>$1</i>')
    .replace(/`([^`\n]+)`/g, '<code>$1</code>')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
}

// Telegram bitta xabar uzunligi 4096 belgidan oshmasligi kerak
function splitMessage(text, max = 4000) {
  if (text.length <= max) return [text];
  const parts = [];
  let rest = text;
  while (rest.length > max) {
    let cut = rest.lastIndexOf('\n', max);
    if (cut < max * 0.5) cut = max; // qulay bo'linish topilmasa qattiq kesamiz
    parts.push(rest.slice(0, cut));
    rest = rest.slice(cut);
  }
  if (rest.trim()) parts.push(rest);
  return parts;
}

/**
 * Berilgan postni barcha faol kanallarga yuboradi.
 * @param {object} bot node-telegram-bot-api instance
 * @param {number} postId
 */
async function sendPost(bot, postId) {
  const { rows: postRows } = await query('SELECT * FROM posts WHERE id = $1', [postId]);
  if (!postRows.length) throw new Error('Post topilmadi');
  const post = postRows[0];

  // Post o'ziga tegishli kanalga yuboriladi; channel_id yo'q bo'lsa barcha faol kanallarga
  let channelsQuery;
  if (post.channel_id) {
    channelsQuery = await query('SELECT * FROM channels WHERE id = $1 AND is_active = TRUE', [post.channel_id]);
  } else {
    channelsQuery = await query('SELECT * FROM channels WHERE is_active = TRUE');
  }
  const channels = channelsQuery.rows;

  if (!channels.length) {
    await query(
      "UPDATE posts SET status='failed', error='Faol kanal yo''q', updated_at=NOW() WHERE id=$1",
      [postId]
    );
    throw new Error('Faol kanal yo\'q');
  }

  const chunks = splitMessage(post.content);
  let successCount = 0;
  let lastError = null;

  for (const ch of channels) {
    try {
      const msgIds = [];
      for (const chunk of chunks) {
        const sent = await bot.sendMessage(ch.chat_id, mdToHtml(chunk), { parse_mode: 'HTML' });
        msgIds.push(sent.message_id);
      }
      await query(
        `INSERT INTO post_channel (post_id, channel_id, status, message_id, message_ids, sent_at, created_at, updated_at)
         VALUES ($1, $2, 'sent', $3, $4, NOW(), NOW(), NOW())`,
        [postId, ch.id, msgIds[msgIds.length - 1], msgIds.join(',')]
      );
      successCount++;
    } catch (e) {
      lastError = e.message;
      await query(
        `INSERT INTO post_channel (post_id, channel_id, status, error, created_at, updated_at)
         VALUES ($1, $2, 'failed', $3, NOW(), NOW())`,
        [postId, ch.id, e.message]
      );
    }
  }

  // Hech bo'lmasa bitta kanalga ketgan bo'lsa 'sent' (qisman muvaffaqiyat ham shu holat,
  // batafsil ma'lumot post_channel da); birortasiga ham ketmagan bo'lsa 'failed' — admin
  // "Xabarlar" bo'limida qayta ko'rib, tahrirlab qayta yuborishi mumkin bo'lsin.
  const status = successCount > 0 ? 'sent' : 'failed';
  await query(
    `UPDATE posts SET status=$2, error=$3, sent_at=NOW(), updated_at=NOW() WHERE id=$1`,
    [postId, status, successCount > 0 ? null : lastError]
  );
}

/**
 * Yuborilgan postni barcha kanallardan (Telegram'dan) o'chiradi, so'ng bazadan ham o'chiradi.
 * @param {object} bot
 * @param {number} postId
 * @returns {Promise<{ok: boolean, errors: string[]}>}
 */
async function deletePost(bot, postId) {
  const { rows } = await query(
    `SELECT pc.message_ids, c.chat_id, c.title
     FROM post_channel pc JOIN channels c ON c.id = pc.channel_id
     WHERE pc.post_id = $1 AND pc.status = 'sent' AND pc.message_ids IS NOT NULL`,
    [postId]
  );

  const errors = [];
  for (const row of rows) {
    const ids = row.message_ids.split(',').map(Number);
    for (const id of ids) {
      try {
        await bot.deleteMessage(row.chat_id, id);
      } catch (e) {
        if (!isBenignTelegramError(e.message)) {
          errors.push(`${row.title}: ${e.message}`);
        }
      }
    }
  }

  // post_channel qatorlari FK ON DELETE CASCADE orqali o'zi bilan ketadi.
  await query('DELETE FROM posts WHERE id = $1', [postId]);

  return { ok: errors.length === 0, errors };
}

/**
 * Yuborilgan postning matnini bazada va Telegram kanallaridagi xabarlarda yangilaydi.
 * Eslatma: agar yangi matn eski xabar bo'laklari soniga to'g'ri kelmasa (uzun post
 * qayta bo'linib ketsa), o'sha kanal tahrirlanmaydi va xato sifatida qaytariladi —
 * bunday holda "O'chirish" qilib qayta yuborish tavsiya etiladi.
 * @param {object} bot
 * @param {number} postId
 * @param {string} content
 * @returns {Promise<{ok: boolean, errors: string[]}>}
 */
async function editPost(bot, postId, content) {
  const { rows } = await query(
    `SELECT pc.message_ids, c.chat_id, c.title
     FROM post_channel pc JOIN channels c ON c.id = pc.channel_id
     WHERE pc.post_id = $1 AND pc.status = 'sent' AND pc.message_ids IS NOT NULL`,
    [postId]
  );

  const newChunks = splitMessage(content);
  const errors = [];

  for (const row of rows) {
    const ids = row.message_ids.split(',').map(Number);
    if (ids.length !== newChunks.length) {
      errors.push(`${row.title}: xabar bo'laklari soni mos emas (${ids.length} → ${newChunks.length}), tahrirlab bo'lmadi. O'chirib qayta yuboring.`);
      continue;
    }
    try {
      for (let i = 0; i < ids.length; i++) {
        await bot.editMessageText(mdToHtml(newChunks[i]), { chat_id: row.chat_id, message_id: ids[i], parse_mode: 'HTML' });
      }
    } catch (e) {
      if (!isBenignTelegramError(e.message)) {
        errors.push(`${row.title}: ${e.message}`);
      }
    }
  }

  await query(
    `UPDATE posts SET content = $2, error = $3, updated_at = NOW() WHERE id = $1`,
    [postId, content, errors.length ? errors.join('; ') : null]
  );

  return { ok: errors.length === 0, errors };
}

module.exports = { sendPost, deletePost, editPost, splitMessage };
