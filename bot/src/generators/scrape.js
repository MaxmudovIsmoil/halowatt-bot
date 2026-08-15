const axios = require('axios');

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function stripHtml(html) {
  return html
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/\s+/g, ' ')
    .trim();
}

/**
 * Berilgan URL sahifasini yuklab, HTML teglaridan tozalangan matnni qaytaradi.
 * AI bu matnni faqat tarjima/qayta yozish uchun ishlatadi — o'zi internetdan qidirmaydi.
 * @param {string} url
 * @param {number} maxLength
 * @returns {Promise<string|null>}
 */
async function scrapeUrl(url, maxLength = 12000) {
  if (!url) return null;

  const resp = await axios.get(url, {
    timeout: 15000,
    maxRedirects: 5,
    responseType: 'text',
    headers: { 'User-Agent': 'Mozilla/5.0 (compatible; HalowattBot/1.0)' },
  });

  const text = stripHtml(String(resp.data || ''));
  if (!text) return null;

  return text.length > maxLength ? text.slice(0, maxLength) : text;
}

/**
 * Bir nechta URL'dan navbat bilan (orasida kechikish bilan, rate limitga tushmaslik uchun)
 * matn yuklab, bittalab birlashtiradi. Bittasi xato bersa (masalan sahifa ochilmasa)
 * o'sha manba o'tkazib yuboriladi.
 * @param {string[]} urls
 * @param {number} maxLengthTotal
 * @param {number} delayMs har bir so'rovdan keyingi kutish vaqti
 * @returns {Promise<string|null>}
 */
async function scrapeUrls(urls, maxLengthTotal = 12000, delayMs = 2000) {
  const list = (urls || []).filter(Boolean);
  if (!list.length) return null;

  const perUrlLimit = Math.ceil(maxLengthTotal / list.length);
  const parts = [];

  for (let i = 0; i < list.length; i++) {
    const url = list[i];
    try {
      const text = await scrapeUrl(url, perUrlLimit);
      if (text) parts.push(`Manba: ${url}\n${text}`);
    } catch (e) {
      console.error(`Scrape xato (${url}):`, e.message);
    }

    if (i < list.length - 1) await sleep(delayMs);
  }

  if (!parts.length) return null;

  const combined = parts.join('\n\n---\n\n');
  return combined.length > maxLengthTotal ? combined.slice(0, maxLengthTotal) : combined;
}

module.exports = { scrapeUrl, scrapeUrls };
