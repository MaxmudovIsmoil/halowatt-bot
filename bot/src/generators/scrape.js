const axios = require('axios');

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

module.exports = { scrapeUrl };
