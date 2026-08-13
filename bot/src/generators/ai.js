const Anthropic = require('@anthropic-ai/sdk');
const OpenAI = require('openai');
const { GoogleGenAI } = require('@google/genai');
const { getSetting } = require('../db');

const LANGUAGE_INSTRUCTIONS = {
  uz_latin:    "Javob faqat o'zbek tilida (lotin harflarida) bo'lsin. Texnik terminlarni inglizcha qoldiring.",
  uz_cyrillic: "Жавоб фақат ўзбек тилида (кирилл ҳарфларида) бўлсин. Техник терминларни инглизча қолдиринг.",
  ru:          "Ответ должен быть только на русском языке. Технические термины можно оставить на английском.",
  en:          "The response must be in English only.",
};

const DEFAULT_CATEGORY = `Hikvision, HiLook, EZVIZ, HikCentral, Hik-Partner Pro, Hik-Connect,
HikCentral Professional, HikCentral Access Control, HikCentral FocSign,
Hikvision ColorVu, AcuSense, DarkFighter, TandemVu, PTZ, Thermal, ANPR,
Access Control, Face Recognition, Video Intercom, Alarm, Storage, NVR, DVR,
AI, Solar, WiFi, Cloud, HiLook mahsulotlari, EZVIZ Smart Home, EZVIZ ilovalari.

Mavzular: Yangi mahsulotlar, Firmware yangilanishlari, Dastur yangilanishlari,
Mobil ilova yangilanishlari, AI funksiyalari, Cyber Security, Texnik tavsiyalar,
O'rnatish usullari, Professional konfiguratsiya, Yangi texnologiyalar,
Case Study, Success Story, Integratsiyalar, Release Notes.

Manbalar: hikvision.com, hilook.com, ezviz.com, App Store, Google Play,
Hikvision Official Blog, Hikvision Security Bulletin.`;

function buildPrompt(channel) {
  const lang     = LANGUAGE_INSTRUCTIONS[channel?.language] || LANGUAGE_INSTRUCTIONS.uz_latin;
  const category = (channel?.category && channel.category.trim()) || DEFAULT_CATEGORY;

  return `Siz professional texnologik kontent muharririsiz.
Quyidagi kategoriya/mavzu bo'yicha internetdan eng yangi (oxirgi 7 kun) ma'lumot toping:

${category}

Agar oxirgi 7 kun ichida yangilik topilmasa, shu mavzuga oid eng foydali texnik maqola yoki funksiyani toping.

Har bir yangilik uchun quyidagi formatda yozing:
-------------------------------------
**Sarlavha**

**Nima vazifa bajaradi?**
(2-3 jumla)

**Nima o'zgardi / qaysi modellarda ishlaydi?**

**Amaliy foydasi**

**Halowatt Academy tavsiyasi**

**Manba:**
- (link)
-------------------------------------

${lang}
Marketing matni yozmang. Faqat aniq va tekshirilgan ma'lumotlardan foydalaning.
Har bir javob oxirida kamida 1 ta rasmiy manba keltiring.
Faqat kanalga joylashga tayyor yakuniy matnni qaytar, boshqa izoh yozma.`;
}

function buildTranslatePrompt(channel, rawText) {
  const lang = LANGUAGE_INSTRUCTIONS[channel?.language] || LANGUAGE_INSTRUCTIONS.uz_latin;

  return `Siz professional texnologik kontent muharririsiz.
Quyida bitta manba havolasidan skript orqali yuklab olingan xom matn berilgan.
Internetdan hech narsa qidirmang — faqat shu matndan foydalaning, faktlarni o'zgartirmang, faqat tarjima/qayta yozing:

"""
${rawText}
"""

${lang}
Telegram kanaliga joylashga tayyor, aniq va o'qilishi qulay yakuniy matn qaytaring
(sarlavha, asosiy mazmun, agar xom matnda havola bo'lsa — manba sifatida saqlab qoling).
Boshqa izoh yozmang, faqat yakuniy postni qaytaring.`;
}

const PROVIDER_LABELS = {
  claude:  'Claude (Anthropic)',
  chatgpt: 'ChatGPT (OpenAI)',
  grok:    'Grok (xAI)',
  gemini:  'Gemini (Google)',
};

const PROVIDER_DEFAULT_MODELS = {
  claude:  'claude-sonnet-5',
  chatgpt: 'gpt-5.1',
  grok:    'grok-4',
  gemini:  'gemini-2.5-flash',
};

// Eski .env orqali sozlangan Claude kaliti hamon ishlashi uchun — admin panelda
// hali kiritilmagan bo'lsa shu ishlatiladi.
const ENV_FALLBACKS = {
  claude: { apiKey: 'ANTHROPIC_API_KEY', model: 'ANTHROPIC_MODEL' },
};

async function getProviderCreds(provider) {
  const envFallback = ENV_FALLBACKS[provider] || {};
  const [dbApiKey, dbModel] = await Promise.all([
    getSetting(`ai_${provider}_api_key`, ''),
    getSetting(`ai_${provider}_model`, ''),
  ]);

  const apiKey = dbApiKey || (envFallback.apiKey && process.env[envFallback.apiKey]) || '';
  const model  = dbModel || (envFallback.model && process.env[envFallback.model]) || PROVIDER_DEFAULT_MODELS[provider];

  if (!apiKey) {
    const label = PROVIDER_LABELS[provider] || provider;
    throw new Error(`${label} API kaliti sozlanmagan — admin panelda "Sozlamalar → AI provayderlar"da kiriting.`);
  }

  return { apiKey, model };
}

// Claude — Anthropic Messages API + web_search vositasi (tarjima rejimida vosita o'chiriladi)
async function generateWithClaude(prompt, { search = true } = {}) {
  const { apiKey, model } = await getProviderCreds('claude');
  const client = new Anthropic({ apiKey });

  const resp = await client.messages.create({
    model,
    max_tokens: 3000,
    ...(search ? { tools: [{ type: 'web_search_20250305', name: 'web_search', max_uses: 8 }] } : {}),
    messages: [{ role: 'user', content: prompt }],
  });

  return resp.content
    .filter((b) => b.type === 'text')
    .map((b) => b.text)
    .join('\n')
    .trim();
}

// ChatGPT — OpenAI Responses API + web_search vositasi (tarjima rejimida vosita o'chiriladi)
async function generateWithChatGPT(prompt, { search = true } = {}) {
  const { apiKey, model } = await getProviderCreds('chatgpt');
  const client = new OpenAI({ apiKey });

  const resp = await client.responses.create({
    model,
    ...(search ? { tools: [{ type: 'web_search' }] } : {}),
    input: prompt,
  });

  return (resp.output_text || '').trim();
}

// Grok — xAI Responses API (OpenAI SDK, boshqa baseURL) + web_search vositasi (tarjima rejimida vosita o'chiriladi)
async function generateWithGrok(prompt, { search = true } = {}) {
  const { apiKey, model } = await getProviderCreds('grok');
  const client = new OpenAI({ apiKey, baseURL: 'https://api.x.ai/v1' });

  const resp = await client.responses.create({
    model,
    ...(search ? { tools: [{ type: 'web_search' }] } : {}),
    input: prompt,
  });

  return (resp.output_text || '').trim();
}

// Gemini — Google GenAI SDK + Google Search grounding (tarjima rejimida vosita o'chiriladi)
async function generateWithGemini(prompt, { search = true } = {}) {
  const { apiKey, model } = await getProviderCreds('gemini');
  const client = new GoogleGenAI({ apiKey });

  const resp = await client.models.generateContent({
    model,
    contents: prompt,
    config: search ? { tools: [{ googleSearch: {} }] } : {},
  });

  return (resp.text || '').trim();
}

const PROVIDER_GENERATORS = {
  claude:  generateWithClaude,
  chatgpt: generateWithChatGPT,
  grok:    generateWithGrok,
  gemini:  generateWithGemini,
};

/**
 * Claude / ChatGPT / Grok / Gemini'dan biri bilan (kanal tanlagan provayder,
 * standart — Claude) web'dan qidirib kanal uchun yangilik matnini tayyorlaydi.
 * @param {object|null} channel
 * @returns {Promise<string>}
 */
async function generateWithAI(channel = null) {
  const provider  = channel?.ai_provider || 'claude';
  const generate  = PROVIDER_GENERATORS[provider] || generateWithClaude;

  // Ustunlik: kanal ai_prompt → global settings ai_prompt → avtomatik qurilgan prompt
  const globalPrompt = await getSetting('ai_prompt', '');
  const prompt =
    (channel?.ai_prompt && channel.ai_prompt.trim()) ||
    (globalPrompt && globalPrompt.trim()) ||
    buildPrompt(channel);

  const text = await generate(prompt);
  if (!text) throw new Error('AI bo\'sh javob qaytardi');
  return text;
}

/**
 * Skript orqali yuklab olingan xom matnni AI'ga faqat tarjima/qayta yozish uchun beradi
 * — AI o'zi internetdan qidirmaydi (web_search vositasi o'chirilgan).
 * @param {object|null} channel
 * @param {string} rawText
 * @returns {Promise<string>}
 */
async function translateWithAI(channel, rawText) {
  const provider = channel?.ai_provider || 'claude';
  const generate = PROVIDER_GENERATORS[provider] || generateWithClaude;

  const prompt = buildTranslatePrompt(channel, rawText);
  const text = await generate(prompt, { search: false });
  if (!text) throw new Error('AI bo\'sh javob qaytardi');
  return text;
}

module.exports = { generateWithAI, translateWithAI, buildPrompt };
