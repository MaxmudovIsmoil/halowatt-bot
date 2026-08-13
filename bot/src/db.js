const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
  host:     process.env.DB_HOST || '127.0.0.1',
  port:     parseInt(process.env.DB_PORT || '5432', 10),
  database: process.env.DB_NAME,
  user:     process.env.DB_USER,
  password: process.env.DB_PASSWORD,
});

async function query(text, params) {
  return pool.query(text, params);
}

async function getSetting(key, fallback = null) {
  const { rows } = await query('SELECT value FROM settings WHERE key = $1', [key]);
  return rows.length ? rows[0].value : fallback;
}

async function setSetting(key, value) {
  await query(
    `INSERT INTO settings (key, value, created_at, updated_at)
     VALUES ($1, $2, NOW(), NOW())
     ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = NOW()`,
    [key, value]
  );
}

async function getActiveChannels() {
  const { rows } = await query(
    'SELECT * FROM channels WHERE is_active = TRUE ORDER BY id'
  );
  return rows;
}

async function getChannelById(id) {
  const { rows } = await query('SELECT * FROM channels WHERE id = $1', [id]);
  return rows[0] || null;
}

async function getChannelSources(channelId) {
  const { rows } = await query(
    "SELECT title, url FROM sources WHERE channel_id = $1 AND is_active = TRUE AND type = 'rss'",
    [channelId]
  );
  return rows;
}

module.exports = { pool, query, getSetting, setSetting, getActiveChannels, getChannelById, getChannelSources };
