const Parser = require('rss-parser');
const { getChannelSources } = require('../db');

const parser = new Parser({ timeout: 15000 });

function isRecent(dateStr, days = 7) {
  if (!dateStr) return false;
  const d = new Date(dateStr);
  if (isNaN(d)) return false;
  return (Date.now() - d.getTime()) / (1000 * 60 * 60 * 24) <= days;
}

function clean(html, max = 300) {
  if (!html) return '';
  const t = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  return t.length > max ? t.slice(0, max) + '…' : t;
}

/**
 * Kanalga tegishli faol RSS manbalardan oxirgi 7 kun yangiliklarini yig'ib matn tayyorlaydi.
 * @param {number} channelId
 * @returns {Promise<string|null>}
 */
async function generateFromRSS(channelId) {
  const sources = await getChannelSources(channelId);
  if (!sources.length) return null;

  const items = [];
  for (const src of sources) {
    try {
      const feed = await parser.parseURL(src.url);
      for (const it of feed.items || []) {
        const date = it.isoDate || it.pubDate;
        if (isRecent(date, 7)) {
          items.push({
            source:  src.title,
            title:   it.title || 'Nomsiz',
            summary: clean(it.contentSnippet || it.content || it.summary),
            link:    it.link || src.url,
            date,
          });
        }
      }
    } catch (e) {
      console.error(`RSS xato (${src.url}):`, e.message);
    }
  }

  if (!items.length) return null;

  items.sort((a, b) => new Date(b.date) - new Date(a.date));
  const top = items.slice(0, 5);

  const blocks = top.map((it) =>
    [
      '-------------------------------------',
      it.title,
      it.summary,
      '',
      `Manba: ${it.source}`,
      it.link,
      '-------------------------------------',
    ].join('\n')
  );

  return blocks.join('\n\n');
}

module.exports = { generateFromRSS };
