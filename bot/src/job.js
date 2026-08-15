const { query, getActiveChannels } = require('./db');
const { generateWithAI, translateWithAI } = require('./generators/ai');
const { generateFromRSS } = require('./generators/rss');
const { scrapeUrls } = require('./generators/scrape');
const { sendPost } = require('./sender');

/**
 * Bitta kanal uchun kontent tayyorlaydi (uning source_mode ga qarab).
 */
async function buildContentForChannel(channel) {
  const mode = channel.source_mode || 'ai';

  if (mode === 'scrape') {
    const urls = (channel.source_url || '').split(/\r?\n/).map((u) => u.trim()).filter(Boolean);
    const raw = await scrapeUrls(urls);
    if (!raw || !raw.trim()) throw new Error('Manba havoladan matn topilmadi');
    const content = await translateWithAI(channel, raw);
    if (!content || !content.trim()) throw new Error('AI tarjima qila olmadi');
    return { content, sourceType: 'scrape' };
  }

  if (mode === 'ai' || mode === 'both') {
    try {
      const content = await generateWithAI(channel);
      if (content && content.trim()) return { content, sourceType: 'ai' };
    } catch (e) {
      console.error(`[ch#${channel.id}] AI xatosi:`, e.message);
      if (mode === 'ai') throw e;
    }
  }

  if (mode === 'rss' || mode === 'both') {
    const content = await generateFromRSS(channel.id);
    if (content && content.trim()) return { content, sourceType: 'rss' };
  }

  throw new Error('Hech qaysi manbadan content topilmadi');
}

/**
 * Bitta kanal uchun to'liq sikl: kontent → baza → yuborish/navbat.
 * @param {object} bot
 * @param {object} channel  — channels jadvalidagi qator
 * @param {boolean} forceSend
 */
async function runJobForChannel(bot, channel, forceSend = false) {
  const { content, sourceType } = await buildContentForChannel(channel);

  const { rows } = await query(
    `INSERT INTO posts (content, source_type, status, channel_id, created_at, updated_at)
     VALUES ($1, $2, 'pending', $3, NOW(), NOW()) RETURNING id`,
    [content, sourceType, channel.id]
  );
  const postId = rows[0].id;

  if (channel.auto_send || forceSend) {
    await sendPost(bot, postId);
    return { postId, status: 'sent', channelId: channel.id };
  }

  return { postId, status: 'pending', channelId: channel.id };
}

/**
 * Barcha aktiv kanallar uchun parallel ravishda job ishga tushiradi.
 * @param {object} bot
 * @param {boolean} forceSend
 */
async function runJobForAllChannels(bot, forceSend = false) {
  const channels = await getActiveChannels();
  if (!channels.length) throw new Error('Faol kanal yo\'q');

  const results = await Promise.allSettled(
    channels.map((ch) => runJobForChannel(bot, ch, forceSend))
  );

  return results.map((r, i) =>
    r.status === 'fulfilled'
      ? r.value
      : { channelId: channels[i].id, error: r.reason?.message }
  );
}

module.exports = { runJobForChannel, runJobForAllChannels };
