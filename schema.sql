-- Halowatt Academy — umumiy PostgreSQL sxemasi
-- Node.js bot va Laravel admin shu bazani ulashadi.

-- Adminlar (Laravel `users` jadvali bilan mos)
CREATE TABLE IF NOT EXISTS users (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NULL UNIQUE,     -- login username orqali; email ixtiyoriy
    email_verified_at TIMESTAMP NULL,
    password        VARCHAR(255) NOT NULL,
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);

-- Eski bazalarda email hali NOT NULL bo'lishi mumkin — bo'shatamiz (login username bilan).
ALTER TABLE users ALTER COLUMN email DROP NOT NULL;

-- Login shu ustun orqali amalga oshadi (admin panel AuthController "username" so'raydi).
ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(255) NULL;
UPDATE users SET username = regexp_replace(lower(name), '[^a-z0-9]', '', 'g')
    WHERE username IS NULL;
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE table_name = 'users' AND constraint_name = 'users_username_unique'
    ) THEN
        ALTER TABLE users ALTER COLUMN username SET NOT NULL;
        ALTER TABLE users ADD CONSTRAINT users_username_unique UNIQUE (username);
    END IF;
END $$;

-- Telegram kanallari (admin qo'shadi/o'chiradi)
CREATE TABLE IF NOT EXISTS channels (
    id          BIGSERIAL PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,            -- ko'rsatiladigan nom
    chat_id     VARCHAR(255) NOT NULL UNIQUE,     -- @username yoki -100... raqami
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);

-- Har bir kanalning o'z manba/jadval/prompt sozlamalari (admin "Kanal sozlamalari"da boshqaradi)
ALTER TABLE channels ADD COLUMN IF NOT EXISTS source_mode       VARCHAR(20) NOT NULL DEFAULT 'both'; -- ai | rss | both
ALTER TABLE channels ADD COLUMN IF NOT EXISTS ai_provider       VARCHAR(20) NOT NULL DEFAULT 'claude'; -- claude | chatgpt | grok | gemini
ALTER TABLE channels ADD COLUMN IF NOT EXISTS auto_send         BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE channels ADD COLUMN IF NOT EXISTS schedule_enabled  BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE channels ADD COLUMN IF NOT EXISTS schedule_time     VARCHAR(255) NOT NULL DEFAULT '09:00'; -- "HH:MM" yoki vergul bilan bir nechta
ALTER TABLE channels ADD COLUMN IF NOT EXISTS ai_prompt         TEXT NULL;
ALTER TABLE channels ADD COLUMN IF NOT EXISTS category          TEXT NULL;
ALTER TABLE channels ADD COLUMN IF NOT EXISTS language          VARCHAR(20) NOT NULL DEFAULT 'uz_latin';

-- Bot sozlamalari (kalit-qiymat). Admin oynadan boshqariladi.
CREATE TABLE IF NOT EXISTS settings (
    id          BIGSERIAL PRIMARY KEY,
    key         VARCHAR(255) NOT NULL UNIQUE,
    value       TEXT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);

-- RSS / manba saytlar ro'yxati (har biri bitta kanalga tegishli — admin kanal sahifasida boshqaradi)
CREATE TABLE IF NOT EXISTS sources (
    id          BIGSERIAL PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    url         TEXT NOT NULL,                    -- RSS yoki sahifa URL
    type        VARCHAR(20) NOT NULL DEFAULT 'rss', -- 'rss'
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);

ALTER TABLE sources ADD COLUMN IF NOT EXISTS channel_id BIGINT NULL REFERENCES channels(id) ON DELETE SET NULL;

-- Tayyorlangan / yuborilgan xabarlar (log + tasdiqlash navbati)
CREATE TABLE IF NOT EXISTS posts (
    id           BIGSERIAL PRIMARY KEY,
    content      TEXT NOT NULL,                   -- yakuniy matn (o'zbek, lotin)
    source_type  VARCHAR(20) NOT NULL DEFAULT 'ai', -- 'ai' | 'rss'
    status       VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|approved|sent|rejected|failed
    error        TEXT NULL,
    scheduled_at TIMESTAMP NULL,
    sent_at      TIMESTAMP NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL
);

-- Post qaysi kanal uchun yaratilgani (bitta kanal — bir nechta kanalga jo'natish tarixi post_channel'da)
ALTER TABLE posts ADD COLUMN IF NOT EXISTS channel_id BIGINT NULL REFERENCES channels(id) ON DELETE SET NULL;

-- Har bir postning qaysi kanalga ketgani (yoki ketishi)
CREATE TABLE IF NOT EXISTS post_channel (
    id          BIGSERIAL PRIMARY KEY,
    post_id     BIGINT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    channel_id  BIGINT NOT NULL REFERENCES channels(id) ON DELETE CASCADE,
    status      VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|sent|failed
    error       TEXT NULL,
    message_id  BIGINT NULL,                     -- Telegram xabar id (oxirgi bo'lak)
    message_ids TEXT NULL,                        -- uzun post bo'lingan bo'lsa, vergul bilan barcha id'lar ("101,102")
    sent_at     TIMESTAMP NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);

-- Eski bazalarda ustun yo'q bo'lsa qo'shib qo'yamiz (qayta ishga tushirish xavfsiz)
ALTER TABLE post_channel ADD COLUMN IF NOT EXISTS message_ids TEXT NULL;

-- Bu ustun qo'shilishidan oldin yuborilgan postlar uchun eski message_id'dan to'ldiramiz
-- (aks holda ular tahrirlash/o'chirishda Telegram xabariga ega bo'lmay qoladi)
UPDATE post_channel SET message_ids = message_id::text WHERE message_ids IS NULL AND message_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
CREATE INDEX IF NOT EXISTS idx_posts_channel ON posts(channel_id);
CREATE INDEX IF NOT EXISTS idx_sources_channel ON sources(channel_id);
CREATE INDEX IF NOT EXISTS idx_post_channel_post ON post_channel(post_id);

-- Standart sozlamalar
INSERT INTO settings (key, value, created_at, updated_at) VALUES
    ('source_mode',      'both',   NOW(), NOW()),  -- ai | rss | both
    ('auto_send',        '0',      NOW(), NOW()),  -- 0 = admin tasdiqlaydi, 1 = avtomatik
    ('schedule_time',    '09:00',  NOW(), NOW()),  -- HH:MM (server vaqti)
    ('schedule_enabled', '1',      NOW(), NOW()),
    ('ai_prompt',        '',       NOW(), NOW()),  -- admin tahrirlaydigan prompt
    ('last_run_date',    '',       NOW(), NOW())
ON CONFLICT (key) DO NOTHING;
