# Halowatt Academy — Telegram bot (Node.js)

AI (Claude) va RSS manbalardan har kuni yangilik yig'ib, o'zbek tilida (lotin)
Telegram kanallariga tashlaydi. Hamma sozlama Laravel admin oynadan boshqariladi.

## O'rnatish

```bash
cd bot
npm install
cp .env.example .env
# .env ni to'ldiring (bot token, DB, ANTHROPIC_API_KEY, INTERNAL_API_SECRET)
npm start
```

Baza sxemasi loyiha ildizidagi `schema.sql` da. Uni bir marta yuklang:

```bash
psql -U halowatt -d halowatt -f ../schema.sql
```

## Ish oqimi

1. `node-cron` har daqiqada `settings.schedule_time` ni tekshiradi.
2. Vaqt kelganda `runJob` ishlaydi:
   - `source_mode` = `ai` → Claude web'dan qidiradi
   - `= rss` → RSS manbalardan yig'adi
   - `= both` → avval AI, ishlamasa RSS (zaxira)
3. `auto_send`:
   - `1` → to'g'ridan-to'g'ri barcha faol kanallarga yuboriladi
   - `0` → post `pending` bo'lib turadi, admin oynadan tasdiqlanadi
4. Bir post bir nechta kanalga ketadi (`channels` jadvali, `is_active`).

## Ichki API (Laravel admin chaqiradi)

`127.0.0.1:BOT_API_PORT`, har so'rovda `X-Internal-Secret` header:

| Metod | Yo'l | Vazifa |
|-------|------|--------|
| POST | /run-now | Hozir yaratib **yuborish** |
| POST | /generate-now | Hozir yaratib **navbatga** qo'yish (tasdiq uchun) |
| POST | /send-post `{post_id}` | Tasdiqlangan postni yuborish |
| POST | /edit-post `{post_id, content}` | Yuborilgan post matnini kanallarda ham yangilash |
| POST | /delete-post `{post_id}` | Yuborilgan postni kanallardan va bazadan o'chirish |
| GET  | /health | Holat |

Yuborilgan (`sent`) postni admin panelda tahrirlash yoki o'chirish — shu ikki
endpoint orqali Telegram kanallaridagi xabar(lar)ni ham avtomatik yangilaydi
yoki o'chiradi (`post_channel.message_ids` da saqlangan xabar id'lari orqali).

## Muhim
- Botni har bir kanalga **admin** qilib qo'shing (aks holda yubora olmaydi).
- `INTERNAL_API_SECRET` bot va Laravel `.env` da **bir xil** bo'lishi shart.
