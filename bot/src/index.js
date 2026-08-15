require('dotenv').config();
const http   = require('http');
const cron   = require('node-cron');
const { TelegramBot } = require('node-telegram-bot-api');
const { query, getSetting, setSetting, getActiveChannels, getChannelById } = require('./db');
const { runJobForChannel, runJobForAllChannels } = require('./job');
const { sendPost, deletePost, editPost } = require('./sender');

const token = process.env.TELEGRAM_BOT_TOKEN;
if (!token) {
  console.error('TELEGRAM_BOT_TOKEN yo\'q. .env ni tekshiring.');
  process.exit(1);
}

const bot = new TelegramBot(token, { polling: false });

// ---------- Har daqiqada — har kanal o'z jadvalini tekshiradi ----------
cron.schedule('* * * * *', async () => {
  try {
    const channels = await getActiveChannels();
    if (!channels.length) return;

    const now   = new Date();
    const hh    = String(now.getHours()).padStart(2, '0');
    const mm    = String(now.getMinutes()).padStart(2, '0');
    const today = now.toISOString().slice(0, 10);
    const current = `${hh}:${mm}`;

    for (const channel of channels) {
      if (!channel.schedule_enabled) continue;

      const times = (channel.schedule_time || '09:00')
        .split(',').map((t) => t.trim()).filter(Boolean);
      if (!times.includes(current)) continue;

      const slotKey  = `ch_${channel.id}_last_run_slot`;
      const lastSlot = await getSetting(slotKey, '');
      const slot     = `${today} ${current}`;
      if (lastSlot === slot) continue;

      await setSetting(slotKey, slot);
      console.log(`[cron] Kanal #${channel.id} (${channel.title}) — ${current}`);

      runJobForChannel(bot, channel, false)
        .then((r) => console.log(`[cron] Kanal #${channel.id}: post #${r.postId}, ${r.status}`))
        .catch((e) => console.error(`[cron] Kanal #${channel.id} xato:`, e.message));
    }
  } catch (e) {
    console.error('[cron] xato:', e.message);
  }
});

// ---------- Ichki API ----------
const PORT   = parseInt(process.env.BOT_API_PORT || '4000', 10);
const SECRET = process.env.INTERNAL_API_SECRET || '';

// Admin panelning "Tezkor amallar"idan kelgan bir martalik override'larni (ai_provider,
// source_mode, source_url) kanal ustiga vaqtincha qo'yadi — bazadagi kanal sozlamasi
// o'zgarmaydi, faqat shu bitta generatsiya uchun ishlatiladi.
function applyChannelOverrides(channel, body) {
  const overrides = {};

  if (body.ai_provider) overrides.ai_provider = body.ai_provider;
  if (body.source_mode) overrides.source_mode = body.source_mode;
  if (body.source_mode === 'scrape' && Array.isArray(body.source_url)) {
    overrides.source_url = body.source_url.map((u) => String(u).trim()).filter(Boolean).join('\n');
  }

  return Object.keys(overrides).length ? { ...channel, ...overrides } : channel;
}

function readBody(req) {
  return new Promise((resolve) => {
    let data = '';
    req.on('data', (c) => (data += c));
    req.on('end', () => {
      try { resolve(data ? JSON.parse(data) : {}); } catch { resolve({}); }
    });
  });
}

const server = http.createServer(async (req, res) => {
  res.setHeader('Content-Type', 'application/json');

  if (req.headers['x-internal-secret'] !== SECRET) {
    res.statusCode = 401;
    return res.end(JSON.stringify({ ok: false, error: 'unauthorized' }));
  }

  try {
    // Barcha kanallar uchun yaratib yuborish
    if (req.method === 'POST' && req.url === '/run-now') {
      const results = await runJobForAllChannels(bot, true);
      return res.end(JSON.stringify({ ok: true, results }));
    }

    // Barcha kanallar uchun yaratib navbatga qo'yish
    if (req.method === 'POST' && req.url === '/generate-now') {
      const results = await runJobForAllChannels(bot, false);
      return res.end(JSON.stringify({ ok: true, results }));
    }

    // Bitta kanal uchun yaratib yuborish
    if (req.method === 'POST' && req.url === '/run-channel') {
      const body = await readBody(req);
      const channelId = body.channel_id;
      if (!channelId) {
        res.statusCode = 400;
        return res.end(JSON.stringify({ ok: false, error: 'channel_id kerak' }));
      }
      const channel = await getChannelById(channelId);
      if (!channel) {
        res.statusCode = 404;
        return res.end(JSON.stringify({ ok: false, error: 'Kanal topilmadi' }));
      }
      const result = await runJobForChannel(bot, applyChannelOverrides(channel, body), true);
      return res.end(JSON.stringify({ ok: true, ...result }));
    }

    // Bitta kanal uchun yaratib navbatga qo'yish (tasdiq kutadi)
    if (req.method === 'POST' && req.url === '/generate-channel') {
      const body = await readBody(req);
      const channelId = body.channel_id;
      if (!channelId) {
        res.statusCode = 400;
        return res.end(JSON.stringify({ ok: false, error: 'channel_id kerak' }));
      }
      const channel = await getChannelById(channelId);
      if (!channel) {
        res.statusCode = 404;
        return res.end(JSON.stringify({ ok: false, error: 'Kanal topilmadi' }));
      }
      const result = await runJobForChannel(bot, applyChannelOverrides(channel, body), false);
      return res.end(JSON.stringify({ ok: true, ...result }));
    }

    // Tasdiqlangan postni yuborish
    if (req.method === 'POST' && req.url === '/send-post') {
      const body = await readBody(req);
      const postId = body.post_id;
      if (!postId) {
        res.statusCode = 400;
        return res.end(JSON.stringify({ ok: false, error: 'post_id kerak' }));
      }
      await query("UPDATE posts SET status='approved', updated_at=NOW() WHERE id=$1", [postId]);
      await sendPost(bot, postId);
      return res.end(JSON.stringify({ ok: true, post_id: postId }));
    }

    // Postni o'chirish
    if (req.method === 'POST' && req.url === '/delete-post') {
      const body = await readBody(req);
      const postId = body.post_id;
      if (!postId) {
        res.statusCode = 400;
        return res.end(JSON.stringify({ ok: false, error: 'post_id kerak' }));
      }
      const result = await deletePost(bot, postId);
      return res.end(JSON.stringify(result));
    }

    // Post matnini tahrirlash
    if (req.method === 'POST' && req.url === '/edit-post') {
      const body = await readBody(req);
      const { post_id: postId, content } = body;
      if (!postId || !content) {
        res.statusCode = 400;
        return res.end(JSON.stringify({ ok: false, error: 'post_id va content kerak' }));
      }
      const result = await editPost(bot, postId, content);
      return res.end(JSON.stringify(result));
    }

    if (req.method === 'GET' && req.url === '/health') {
      return res.end(JSON.stringify({ ok: true, uptime: process.uptime() }));
    }

    res.statusCode = 404;
    res.end(JSON.stringify({ ok: false, error: 'not found' }));
  } catch (e) {
    console.error('API xato:', e.message);
    res.statusCode = 500;
    res.end(JSON.stringify({ ok: false, error: e.message }));
  }
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`Halowatt bot ishga tushdi. Ichki API: http://127.0.0.1:${PORT}`);
  console.log('Cron faol — har kanal o\'z jadvali bo\'yicha ishlaydi.');
});
