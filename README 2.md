# AI Telegram Kanal Menejeri — To'liq Loyiha Prompti (Spetsifikatsiya)

**Stack:** Laravel 11 (admin panel) + PostgreSQL 16 (baza) + Node.js 20 (bot & workerlar) + Redis (queue/cache)

---

## 0. MASTER PROMPT (AI coding agentga beriladigan qism)

> Quyidagi matnni Claude Code / Cursor / boshqa AI agentga to'g'ridan-to'g'ri berish mumkin.

```
Sen tajribali full-stack dasturchisan. Quyidagi tizimni noldan qurishing kerak.

MAQSAD:
Ko'p sonli Telegram kanallarini avtomatik boshqaradigan tizim. Har bir kanalning
o'z "prompti" (xarakteri, mavzusi, uslubi) bo'ladi. Tizim shu promptga qarab
AI orqali post generatsiya qiladi va belgilangan jadval bo'yicha kanalga tashlaydi.

KOMPONENTLAR:
1. Laravel 13 admin panel — kanallar, promptlar, jadval, postlarni moderatsiya qilish
2. PostgreSQL 16 — yagona umumiy baza
3. Node.js 20 bot — Telegraf + BullMQ, generatsiya va publikatsiya workerlari
4. Redis — queue va cache

TALABLAR:
- Laravel va Node bitta PostgreSQL bazasiga ulanadi. Migratsiyalar FAQAT Laravelda yoziladi.
- Node hech qachon schema o'zgartirmaydi, faqat o'qiydi/yozadi.
- Har bir kanal uchun alohida prompt versiyalanadi (prompt tarixi saqlanadi).
- Post statuslari: draft → pending_review → approved → scheduled → published / failed
- Avtomatik rejim (moderatsiyasiz) va qo'lda tasdiqlash rejimi bo'lsin.
- Telegram rate limit va 429 flood control to'g'ri handle qilinsin.
- Barcha AI so'rovlari loglanadi (token, narx, latency).
- Kod TypeScript'da (Node tomon), strict mode.

Quyidagi bo'limlarga qat'iy amal qil: [pastdagi 1–12 bo'limlar]
```

---

## 1. Tizim arxitekturasi

```
┌─────────────────────┐         ┌──────────────────────┐
│  Laravel Admin      │         │   Node.js Bot        │
│  (Filament/Blade)   │         │   (TypeScript)       │
│                     │         │                      │
│  - Kanallar CRUD    │         │  - Telegraf bot      │
│  - Prompt editor    │         │  - Scheduler worker  │
│  - Jadval sozlash   │         │  - Generator worker  │
│  - Moderatsiya      │         │  - Publisher worker  │
│  - Statistika       │         │  - Stats worker      │
└──────────┬──────────┘         └──────────┬───────────┘
           │                               │
           │      ┌─────────────────┐      │
           └─────▶│  PostgreSQL 16  │◀─────┘
                  └─────────────────┘
           ┌─────▶│     Redis       │◀─────┘
                  └─────────────────┘
                          │
                  ┌───────▼────────┐      ┌──────────────┐
                  │  Telegram API  │      │  AI Provider │
                  └────────────────┘      │ (Claude/GPT) │
                                          └──────────────┘
```

**Ma'lumot oqimi:**
1. Scheduler worker har daqiqada `schedules` jadvalini tekshiradi
2. Vaqti kelgan kanal uchun `generate` job'ini queue'ga qo'yadi
3. Generator worker kanal promptini oladi → AI'ga yuboradi → `posts` jadvaliga `draft` yozadi
4. Agar `auto_publish = true` → status `approved` bo'ladi, aks holda `pending_review`
5. Publisher worker `approved` + vaqti kelgan postlarni Telegramga yuboradi
6. Natija `posts` va `publish_logs` ga yoziladi

---

## 2. PostgreSQL baza sxemasi

> Barcha jadvallarda `created_at`, `updated_at` (timestamptz) bo'ladi. `id` — BIGSERIAL yoki UUID.

### 2.1 `users` (Laravel default)
```sql
id, name, email, password, role_id, is_active, last_login_at
```
Rollar: `super_admin`, `admin`, `editor`, `viewer`
(Spatie Laravel Permission ishlatilsin)

### 2.2 `bots`
Bir nechta Telegram bot tokenini qo'llab-quvvatlash uchun.
```sql
id                BIGSERIAL PK
name              VARCHAR(120)
username          VARCHAR(64)          -- @mybot
token             TEXT                 -- ENCRYPTED (Laravel Crypt)
webhook_url       TEXT NULL
is_active         BOOLEAN DEFAULT true
last_error        TEXT NULL
created_by        BIGINT FK users
```

### 2.3 `channels`
```sql
id                BIGSERIAL PK
bot_id            BIGINT FK bots
tg_chat_id        BIGINT UNIQUE        -- -1001234567890
username          VARCHAR(64) NULL     -- @mychannel
title             VARCHAR(255)
description       TEXT NULL
language          VARCHAR(8) DEFAULT 'uz'   -- uz, ru, en
timezone          VARCHAR(64) DEFAULT 'Asia/Tashkent'
auto_publish      BOOLEAN DEFAULT false     -- moderatsiyasiz chiqadimi
is_active         BOOLEAN DEFAULT true
subscribers_count INT DEFAULT 0
last_post_at      TIMESTAMPTZ NULL
settings          JSONB DEFAULT '{}'        -- qo'shimcha sozlamalar
```

### 2.4 `channel_prompts` ⭐ (tizimning yuragi)
```sql
id                  BIGSERIAL PK
channel_id          BIGINT FK channels ON DELETE CASCADE
version             INT NOT NULL              -- 1, 2, 3...
name                VARCHAR(160)
system_prompt       TEXT NOT NULL             -- asosiy rol/xarakter
topic              TEXT                       -- kanal mavzusi
audience            TEXT                      -- kimga mo'ljallangan
tone                VARCHAR(64)               -- rasmiy / do'stona / hazil / ekspert
style_guide         TEXT                      -- yozish qoidalari
structure_template  TEXT                      -- post tuzilishi shabloni
forbidden_topics    TEXT                      -- taqiqlangan mavzular
keywords            TEXT[]                    -- kalit so'zlar
hashtags            TEXT[]                    -- doimiy hashtaglar
cta_text            TEXT NULL                 -- call to action
emoji_level         SMALLINT DEFAULT 2        -- 0=yo'q,1=kam,2=o'rta,3=ko'p
min_length          INT DEFAULT 300           -- belgilarda
max_length          INT DEFAULT 900
use_markdown        BOOLEAN DEFAULT true
need_image          BOOLEAN DEFAULT false
examples            TEXT NULL                 -- few-shot namunalar
ai_model            VARCHAR(80)               -- claude-sonnet-4-6 / gpt-4o
temperature         NUMERIC(3,2) DEFAULT 0.8
is_active           BOOLEAN DEFAULT true      -- kanalda faqat 1 ta active
created_by          BIGINT FK users
```
**Qoida:** bitta `channel_id` uchun faqat bitta `is_active = true` yozuv bo'lishi kerak (partial unique index).
```sql
CREATE UNIQUE INDEX uniq_active_prompt
ON channel_prompts (channel_id) WHERE is_active = true;
```

### 2.5 `schedules`
```sql
id                BIGSERIAL PK
channel_id        BIGINT FK channels
mode              VARCHAR(16)      -- 'cron' | 'interval' | 'daily_times'
cron_expression   VARCHAR(64) NULL -- '0 9,13,18 * * *'
interval_minutes  INT NULL         -- 180
daily_times       TIME[] NULL      -- {'09:00','13:30','19:00'}
active_days       SMALLINT[]       -- {1,2,3,4,5} = Du-Ju
quiet_hours_from  TIME NULL        -- 23:00
quiet_hours_to    TIME NULL        -- 07:00
posts_per_day     INT DEFAULT 3
timezone          VARCHAR(64) DEFAULT 'Asia/Tashkent'
is_active         BOOLEAN DEFAULT true
next_run_at       TIMESTAMPTZ NULL
last_run_at       TIMESTAMPTZ NULL
```

### 2.6 `posts`
```sql
id                BIGSERIAL PK
channel_id        BIGINT FK channels
prompt_id         BIGINT FK channel_prompts   -- qaysi prompt versiyasi bilan
topic_id          BIGINT FK topics NULL
status            VARCHAR(24) NOT NULL
                  -- draft | pending_review | approved | scheduled
                  -- | publishing | published | failed | rejected | cancelled
content           TEXT NOT NULL
content_html      TEXT NULL
parse_mode        VARCHAR(16) DEFAULT 'HTML'  -- HTML | MarkdownV2 | null
has_media         BOOLEAN DEFAULT false
tg_message_id     BIGINT NULL
scheduled_at      TIMESTAMPTZ NULL
published_at      TIMESTAMPTZ NULL
error_message     TEXT NULL
retry_count       SMALLINT DEFAULT 0
ai_model          VARCHAR(80)
tokens_input      INT
tokens_output     INT
cost_usd          NUMERIC(10,6)
generation_ms     INT
content_hash      VARCHAR(64)   -- SHA256, dublikat tekshirish uchun
reviewed_by       BIGINT FK users NULL
reviewed_at       TIMESTAMPTZ NULL
```
Indexlar:
```sql
CREATE INDEX idx_posts_status_sched ON posts (status, scheduled_at);
CREATE INDEX idx_posts_channel_pub  ON posts (channel_id, published_at DESC);
CREATE UNIQUE INDEX idx_posts_hash  ON posts (channel_id, content_hash);
```

### 2.7 `post_media`
```sql
id, post_id FK, type ('photo'|'video'|'document'|'animation'),
file_path TEXT, file_url TEXT, tg_file_id TEXT NULL,
caption TEXT NULL, sort_order SMALLINT
```

### 2.8 `topics` (mavzular pooli — takrorlanmaslik uchun)
```sql
id, channel_id FK, title VARCHAR(255), notes TEXT NULL,
priority SMALLINT DEFAULT 0,
status VARCHAR(16),        -- pending | used | skipped
used_at TIMESTAMPTZ NULL,
source VARCHAR(24)         -- manual | ai_generated | rss
```

### 2.9 `content_sources` (ixtiyoriy — RSS/web'dan mavzu olish)
```sql
id, channel_id FK, type ('rss'|'url'|'none'), url TEXT,
last_fetched_at, is_active
```

### 2.10 `ai_providers`
```sql
id, name ('anthropic'|'openai'|'gemini'), base_url TEXT,
api_key TEXT (ENCRYPTED), default_model VARCHAR(80),
is_active BOOLEAN, priority SMALLINT   -- fallback tartibi
```

### 2.11 `generation_logs`
```sql
id, post_id FK NULL, channel_id FK, provider VARCHAR(32), model VARCHAR(80),
request_payload JSONB, response_text TEXT, tokens_input INT, tokens_output INT,
cost_usd NUMERIC(10,6), latency_ms INT, status VARCHAR(16), error TEXT NULL
```

### 2.12 `publish_logs`
```sql
id, post_id FK, channel_id FK, attempt SMALLINT,
status VARCHAR(16), tg_response JSONB, error TEXT NULL, duration_ms INT
```

### 2.13 `post_stats` (kunlik snapshot)
```sql
id, post_id FK, views INT, forwards INT, reactions INT, captured_at
```
> ⚠️ **Muhim:** Telegram Bot API post ko'rishlar sonini (views) bermaydi.
> Bu ma'lumot faqat MTProto (GramJS / Telethon) orqali olinadi.
> Agar views kerak bo'lsa — alohida GramJS userbot mikroservisi qo'shiladi.

### 2.14 `settings`
```sql
id, key VARCHAR(120) UNIQUE, value JSONB, description TEXT
```

### 2.15 `audit_logs`
```sql
id, user_id FK, action VARCHAR(64), model_type, model_id,
old_values JSONB, new_values JSONB, ip INET, user_agent TEXT
```

---

## 3. Kanal prompti shabloni ⭐

Bu — `channel_prompts.system_prompt` maydoniga yoziladigan shablon.
Admin panelda har bir kanal uchun shu forma to'ldiriladi.

```
Sen "{{channel_title}}" Telegram kanali uchun kontent yozadigan
professional SMM-copywriter sisan.

━━━ KANAL HAQIDA ━━━
Mavzu:        {{topic}}
Auditoriya:   {{audience}}
Til:          {{language}}
Ohang:        {{tone}}

━━━ YOZISH QOIDALARI ━━━
{{style_guide}}

- Uzunlik: {{min_length}}–{{max_length}} belgi
- Emoji darajasi: {{emoji_level}} (0=umuman yo'q, 3=ko'p)
- Formatlash: {{parse_mode}} (HTML: <b>, <i>, <code>, <a href="">)
- Har doim yangi va noyob kontent yoz, oldingi postlarni takrorlama

━━━ POST TUZILISHI ━━━
{{structure_template}}

Namuna tuzilish:
1. Diqqatni tortadigan sarlavha (1 qator, <b> ichida)
2. Bo'sh qator
3. Asosiy matn — 2-4 ta qisqa abzats
4. Amaliy maslahat yoki xulosa
5. {{cta_text}}
6. Hashtaglar: {{hashtags}}

━━━ TAQIQLAR ━━━
{{forbidden_topics}}

Qo'shimcha taqiqlar:
- Siyosat, din, milliy nizolarga oid mavzular
- Tibbiy va moliyaviy aniq maslahatlar (faqat umumiy ma'lumot)
- Boshqa kanallarga reklama
- Tasdiqlanmagan statistika yoki uydirma faktlar
- "Salom, aziz obunachilar" kabi shablon boshlanishlar

━━━ CHIQISH FORMATI ━━━
FAQAT post matnini qaytar. Hech qanday izoh, sarlavha,
markdown-kodblok yoki "Mana post:" degan preambula YOZMA.

━━━ SO'NGGI 10 POST (takrorlamaslik uchun) ━━━
{{recent_posts_summary}}

━━━ BUGUNGI MAVZU ━━━
{{topic_title}}
```

**Node tomonda bu shablon shunday to'ldiriladi:**
```ts
function buildSystemPrompt(prompt: ChannelPrompt, ctx: GenerationContext): string {
  return TEMPLATE
    .replaceAll('{{channel_title}}', ctx.channel.title)
    .replaceAll('{{topic}}', prompt.topic)
    .replaceAll('{{audience}}', prompt.audience)
    // ... qolganlari
    .replaceAll('{{recent_posts_summary}}', ctx.recentPosts.join('\n---\n'));
}
```

---

## 4. Laravel Admin Panel

### 4.1 Texnologiyalar
- Laravel 11 + PHP 8.3
- **Filament 3** (tez admin panel uchun) yoki Blade + Tailwind + Alpine.js
- Spatie Laravel Permission (rollar)
- Laravel Sanctum (Node bilan API uchun token)
- Laravel Horizon (agar Laravel queue ham ishlatilsa)

### 4.2 Modullar (menyu)

| Modul | Imkoniyatlar |
|---|---|
| **Dashboard** | Bugungi postlar, kutayotgan moderatsiya, xatolar, AI xarajat grafigi |
| **Botlar** | Token qo'shish, webhook o'rnatish, `getMe` orqali tekshirish |
| **Kanallar** | CRUD, botni kanalga admin qilish yo'riqnomasi, `tg_chat_id` avtomatik aniqlash |
| **Promptlar** | Prompt editor (form + live preview), versiyalash, "Test generate" tugmasi |
| **Jadval** | Cron builder (vizual), quiet hours, hafta kunlari |
| **Mavzular** | Mavzu pooli, CSV import, AI orqali 50 ta mavzu generatsiya qilish |
| **Postlar** | Filtrlar (status, kanal, sana), preview (Telegram ko'rinishida), tahrirlash |
| **Moderatsiya** | Navbat: Tasdiqlash / Rad etish / Tahrirlab tasdiqlash / Qayta generatsiya |
| **Statistika** | Kanal bo'yicha post soni, xarajat, muvaffaqiyat foizi |
| **AI Providerlar** | Kalitlar, model, fallback tartibi, limit |
| **Loglar** | generation_logs, publish_logs, audit_logs |
| **Sozlamalar** | Global limitlar, default qiymatlar |

### 4.3 Muhim funksiyalar

**Prompt editor sahifasi:**
- Chap tomonda forma (barcha `channel_prompts` maydonlari)
- O'ng tomonda "Preview" — real vaqtda yig'ilgan system prompt ko'rinadi
- Pastda **"🧪 Test generate"** tugmasi → Node API'ga so'rov → 1 ta post generatsiya qilib ko'rsatadi (kanalga yubormasdan)
- "💾 Yangi versiya sifatida saqlash" → eski versiya `is_active = false` bo'ladi

**Post preview komponenti:**
Telegram post ko'rinishini simulyatsiya qiladi (fon, shrift, HTML render, hashtag rangi).

### 4.4 Laravel → Node API (ichki)

Node.js Express'da kichik ichki API ochadi, Laravel unga murojaat qiladi:

```
POST   /internal/generate/test      { channel_id }         → generatsiya qilib qaytaradi
POST   /internal/posts/:id/publish  → darhol chop etish
POST   /internal/posts/:id/regenerate
POST   /internal/channels/:id/verify → bot kanalda adminmi tekshirish
GET    /internal/health
```
Autentifikatsiya: `X-Internal-Token` header (`.env` dagi shared secret).

---

## 5. Node.js Bot

### 5.1 Kutubxonalar
```json
{
  "telegraf": "^4.16",
  "bullmq": "^5",
  "ioredis": "^5",
  "pg": "^8",
  "kysely": "^0.27",
  "@anthropic-ai/sdk": "latest",
  "openai": "^4",
  "zod": "^3",
  "pino": "^9",
  "dayjs": "^1.11",
  "cron-parser": "^4",
  "express": "^4",
  "dotenv": "^16"
}
```

### 5.2 Papka tuzilishi
```
bot/
├── src/
│   ├── index.ts                 # entrypoint
│   ├── config/
│   │   ├── env.ts               # zod bilan validatsiya
│   │   └── db.ts                # pg pool + kysely
│   ├── bot/
│   │   ├── telegraf.ts          # bot instance(lar)
│   │   ├── commands/            # /start /status /channels /stats
│   │   └── middlewares/
│   ├── queues/
│   │   ├── index.ts             # BullMQ queue'lar
│   │   ├── generate.queue.ts
│   │   └── publish.queue.ts
│   ├── workers/
│   │   ├── scheduler.worker.ts  # har daqiqada schedules tekshiradi
│   │   ├── generate.worker.ts   # AI orqali kontent yaratadi
│   │   ├── publish.worker.ts    # Telegramga yuboradi
│   │   └── stats.worker.ts
│   ├── services/
│   │   ├── ai/
│   │   │   ├── provider.ts      # interface
│   │   │   ├── anthropic.ts
│   │   │   ├── openai.ts
│   │   │   └── index.ts         # fallback logikasi
│   │   ├── prompt.builder.ts    # shablonni to'ldiradi
│   │   ├── content.validator.ts # uzunlik, taqiq, dublikat
│   │   ├── telegram.sender.ts   # rate limit + retry
│   │   └── topic.picker.ts
│   ├── repositories/            # DB so'rovlari
│   ├── api/
│   │   └── internal.routes.ts
│   └── utils/
├── package.json
└── tsconfig.json
```

### 5.3 Workerlar mantiqiy ketma-ketligi

**scheduler.worker.ts** — har 60 soniyada:
```
1. SELECT * FROM schedules WHERE is_active AND next_run_at <= now()
2. Har biri uchun:
   a. Kanal is_active va bot is_active ekanini tekshir
   b. Quiet hours ichida emasmi tekshir
   c. Bugun posts_per_day limiti oshmaganmi tekshir
   d. generate queue'ga job qo'sh: { channel_id, scheduled_at }
   e. next_run_at ni cron-parser orqali yangila
```

**generate.worker.ts**:
```
1. Kanalning active promptini ol (channel_prompts WHERE is_active)
2. Mavzu tanla:
   - topics jadvalidan status='pending', priority DESC bo'yicha
   - agar bo'sh bo'lsa → AI'dan mavzu so'ra
3. Oxirgi 10 ta published postni ol (takrorlanmaslik uchun)
4. buildSystemPrompt() bilan promptni yig'
5. AI provider'ga yubor (fallback bilan: anthropic → openai)
6. Javobni tozala (```markdown olib tashla, trim)
7. Validatsiya:
   - uzunlik min_length..max_length oralig'idami
   - forbidden_topics so'zlari yo'qmi
   - content_hash dublikat emasmi
   - HTML teglar to'g'rimi (Telegram qo'llaydigan teglar)
   ❌ Validatsiya o'tmasa → 2 martagacha qayta generatsiya
8. posts jadvaliga INSERT:
   status = channel.auto_publish ? 'approved' : 'pending_review'
   scheduled_at = job.scheduled_at
9. generation_logs ga yoz
10. Agar approved → publish queue'ga delay bilan qo'sh
```

**publish.worker.ts**:
```
1. Postni ol, statusni 'publishing' ga o'zgartir (SELECT FOR UPDATE SKIP LOCKED)
2. Media bormi tekshir → sendPhoto / sendMediaGroup / sendMessage
3. Telegram rate limit:
   - Bir kanalga: 20 xabar/daqiqa
   - Global: 30 xabar/soniya
   - BullMQ limiter: { max: 20, duration: 60000 } har kanal uchun alohida
4. 429 xatosi → response.parameters.retry_after ni o'qi, shuncha kut, qayta urin
5. Muvaffaqiyat:
   status='published', tg_message_id, published_at
   channels.last_post_at yangila
   topics.status='used'
6. Xato:
   retry_count++, exponential backoff (1m, 5m, 15m)
   3 martadan keyin status='failed' + admin'ga xabar
7. publish_logs ga yoz
```

### 5.4 Telegram xatolarini handle qilish

| Xato | Harakat |
|---|---|
| `429 Too Many Requests` | `retry_after` soniya kut, qayta urin |
| `403 bot was blocked` | kanal `is_active = false`, adminga xabar |
| `400 chat not found` | kanal `is_active = false`, log |
| `400 not enough rights` | adminga "botni kanalga admin qiling" xabari |
| `400 message is too long` | postni bo'lib yubor yoki qayta generatsiya |
| `400 can't parse entities` | `parse_mode` ni olib tashlab qayta yubor |
| Network timeout | 3 marta retry, exponential backoff |

### 5.5 Bot buyruqlari (adminlar uchun)
```
/start          — botni ishga tushirish, admin tekshiruvi
/channels       — ulangan kanallar ro'yxati + status
/status         — tizim holati (queue, xatolar, oxirgi post)
/generate <id>  — kanalga darhol post generatsiya qilish
/pause <id>     — kanalni to'xtatish
/resume <id>    — kanalni davom ettirish
/stats <id>     — kanal statistikasi
/pending        — moderatsiya kutayotgan postlar (inline tugmalar bilan)
```

**Moderatsiya inline tugmalari:**
```
✅ Tasdiqlash   ✏️ Tahrirlash   🔄 Qayta   ❌ Rad etish
```

---

## 6. AI Provider qatlami

```ts
interface AIProvider {
  name: string;
  generate(params: {
    systemPrompt: string;
    userPrompt: string;
    model: string;
    temperature: number;
    maxTokens: number;
  }): Promise<{
    text: string;
    tokensInput: number;
    tokensOutput: number;
    costUsd: number;
    latencyMs: number;
  }>;
}
```

**Fallback strategiyasi:**
```
try anthropic (priority 1)
  → xato yoki timeout (30s)
    → try openai (priority 2)
      → xato
        → post status='failed', adminga xabar
```

**Xarajat nazorati:**
- `settings` jadvalida `monthly_budget_usd` limiti
- Har generatsiyadan oldin joriy oy xarajati tekshiriladi
- Limit oshsa → generatsiya to'xtaydi, adminga ogohlantirish

---

## 7. Kontent validatori

```ts
function validateContent(text: string, prompt: ChannelPrompt): ValidationResult {
  const errors: string[] = [];

  // 1. Uzunlik
  if (text.length < prompt.min_length) errors.push('juda qisqa');
  if (text.length > prompt.max_length) errors.push('juda uzun');
  if (text.length > 4096) errors.push('Telegram limitidan oshdi');

  // 2. Taqiqlangan so'zlar
  for (const word of prompt.forbidden_words) {
    if (text.toLowerCase().includes(word.toLowerCase()))
      errors.push(`taqiqlangan so'z: ${word}`);
  }

  // 3. Preambula tekshiruvi (AI "Mana post:" deb boshlab qo'ymasin)
  const badStarts = ['mana', 'here is', 'вот', 'post:', '```'];
  if (badStarts.some(s => text.toLowerCase().startsWith(s)))
    errors.push('preambula bor');

  // 4. HTML teglar validligi (Telegram qo'llaydiganlari)
  // ruxsat: b, i, u, s, code, pre, a, blockquote, tg-spoiler

  // 5. Dublikat: content_hash + oxirgi 50 post bilan similarity

  return { valid: errors.length === 0, errors };
}
```

---

## 8. Xavfsizlik

- Bot tokenlari va API kalitlari **shifrlangan** holda saqlanadi (Laravel `Crypt::encryptString`)
- Node tomonda dekript qilish uchun bir xil `APP_KEY` ishlatiladi (yoki Laravel API orqali olinadi)
- Admin panelga 2FA (Laravel Fortify)
- `internal API` faqat localhost / private network'dan ochiq
- Rate limiting: Laravel `throttle` middleware
- Barcha admin harakatlari `audit_logs` ga yoziladi
- SQL injection: Eloquent / Kysely (prepared statements)
- XSS: post preview'da `htmlspecialchars`

---

## 9. `.env` fayllari

### Laravel `.env`
```env
APP_NAME="TG Channel Manager"
APP_KEY=base64:...
APP_URL=https://admin.example.uz

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tg_manager
DB_USERNAME=tg_user
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

NODE_API_URL=http://127.0.0.1:3001
INTERNAL_API_TOKEN=long-random-secret
```

### Node `.env`
```env
NODE_ENV=production
PORT=3001

DATABASE_URL=postgresql://tg_user:secret@127.0.0.1:5432/tg_manager
REDIS_URL=redis://127.0.0.1:6379

INTERNAL_API_TOKEN=long-random-secret
LARAVEL_APP_KEY=base64:...          # tokenlarni dekript qilish uchun

ANTHROPIC_API_KEY=sk-ant-...
OPENAI_API_KEY=sk-...

DEFAULT_MODEL=claude-sonnet-4-6
GENERATION_TIMEOUT_MS=30000
MAX_RETRIES=3

ADMIN_TELEGRAM_IDS=123456789,987654321
LOG_LEVEL=info
```

---

## 10. Deployment

```
Ubuntu 22.04 server
├── Nginx          → admin.example.uz (Laravel)
├── PHP-FPM 8.3
├── PostgreSQL 16
├── Redis 7
├── Node.js 20 (PM2 bilan)
│   ├── pm2 start dist/index.js --name tg-bot
│   ├── pm2 start dist/workers/scheduler.js --name tg-scheduler
│   ├── pm2 start dist/workers/generate.js --name tg-generator -i 2
│   └── pm2 start dist/workers/publish.js --name tg-publisher
└── Supervisor / systemd
```

**Yoki Docker Compose:**
```yaml
services:
  postgres, redis, laravel-app, nginx, node-bot, node-workers
```

**Backup:**
- `pg_dump` har kuni 03:00 da, 30 kun saqlanadi
- Postlar arxivi alohida S3/MinIO'ga

**Monitoring:**
- Health check endpoint: `GET /internal/health`
- Agar 5 daqiqa ichida post chiqmasa → admin Telegramga alert
- Sentry (Laravel + Node)

---

## 11. Bosqichma-bosqich reja (Roadmap)

### 🟢 Faza 1 — Poydevor (1-hafta)
- [ ] Laravel loyiha + PostgreSQL ulanish
- [ ] Barcha migratsiyalar va modellar
- [ ] Auth + rollar (Spatie)
- [ ] Filament admin panel skeleti
- [ ] Node loyiha skeleti + DB ulanish + Redis

### 🟢 Faza 2 — Asosiy oqim (2-hafta)
- [ ] Botlar va kanallar CRUD
- [ ] Prompt editor + versiyalash
- [ ] AI provider qatlami (Anthropic + fallback)
- [ ] Generate worker → posts jadvaliga yozish
- [ ] Publisher worker → Telegramga yuborish
- [ ] Qo'lda "Test generate" tugmasi

### 🟡 Faza 3 — Avtomatlashtirish (3-hafta)
- [ ] Scheduler + cron builder UI
- [ ] Quiet hours, kunlik limitlar
- [ ] Moderatsiya navbati (panel + bot inline tugmalari)
- [ ] Kontent validatori + dublikat tekshiruvi
- [ ] Rate limit va retry logikasi

### 🟡 Faza 4 — Kengaytmalar (4-hafta)
- [ ] Mavzular pooli + AI orqali mavzu generatsiyasi
- [ ] Media (rasm) qo'shish — AI image yoki Unsplash
- [ ] RSS manbalardan mavzu olish
- [ ] Statistika dashboard + xarajat grafigi
- [ ] Telegram alertlar (xatolar, limitlar)

### 🔵 Faza 5 — Optimizatsiya
- [ ] Views statistikasi (GramJS userbot)
- [ ] A/B test: bir mavzuga 2 variant, yaxshirog'ini tanlash
- [ ] pgvector bilan semantik dublikat tekshiruvi
- [ ] Ko'p tilli kanallar (bir postni tarjima qilib boshqa kanalga)

---

## 12. Qabul mezonlari (Acceptance Criteria)

1. Admin panelda yangi kanal qo'shib, prompt yozib, jadval belgilaganda — belgilangan vaqtda kanalga avtomatik post chiqishi kerak
2. Prompt o'zgartirilganda eski versiya saqlanib qolishi va qaysi post qaysi versiya bilan yozilganini ko'rish mumkin bo'lishi kerak
3. `auto_publish = false` bo'lganda post kanalga chiqmasdan moderatsiya navbatida turishi kerak
4. Telegram 429 xatosi kelganda tizim yiqilmasligi, kutib qayta urinishi kerak
5. Bir xil kontent ikki marta chiqmasligi kerak (content_hash)
6. AI provider ishlamay qolsa, ikkinchisiga o'tishi kerak
7. Har bir generatsiya narxi va tokeni loglanishi kerak
8. 50+ kanalni bir vaqtda muammosiz boshqara olishi kerak

---

## Qo'shimcha eslatmalar

⚠️ **Telegram cheklovlari:**
- Bot kanalga **admin** qilinishi shart (`Post Messages` huquqi bilan)
- Kanal `chat_id` manfiy son bo'ladi: `-1001234567890`
- Xabar uzunligi: 4096 belgi (caption: 1024 belgi)
- Bot API post ko'rishlarini (views) bermaydi
- Bir kanalga daqiqada ~20 xabar limiti

⚠️ **AI cheklovlari:**
- Har generatsiya ~1500-3000 token = ~$0.01-0.03
- 50 kanal × 3 post/kun = 150 post/kun ≈ $2-5/kun
- Budjet nazorati albatta bo'lsin

⚠️ **Huquqiy:**
- AI yozgan kontentda faktlar noto'g'ri bo'lishi mumkin — moderatsiya tavsiya etiladi
- Kanal opisaniyasida AI ishlatilishini ko'rsatish yaxshi amaliyot