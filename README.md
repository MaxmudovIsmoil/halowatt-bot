# Halowatt Academy — Telegram bot + Admin panel

Ikki qismdan iborat, bitta PostgreSQL bazasini ulashadi:

```
halowatt/
├── schema.sql      ← umumiy baza sxemasi
├── bot/            ← Node.js — yangilik yig'adi va kanallarga yuboradi
└── admin/          ← Laravel 13 — bot boshqaruv paneli (Tailwind CSS, dark/light)
```

## Nima qiladi

- Har kuni belgilangan vaqtda (admin sozlaydi) **AI (Claude)** yoki **RSS**
  manbalardan Hikvision / HiLook / EZVIZ yangiliklarini topadi.
- O'zbek tilida (lotin), sizning shabloningizda tayyorlaydi.
- **Bir nechta Telegram kanalga** tashlaydi (kanallarni admin qo'shadi).
- **Avtomatik** yoki **admin tasdiqlagach** yuborish — tanlanadi.
- Hammasi admin oynadan boshqariladi: kanallar, manbalar, jadval, prompt, rejim.

---

## O'rnatish tartibi

### 1. PostgreSQL baza

```bash
createdb halowatt
psql -d halowatt -f schema.sql     # jadvallar + standart sozlamalar
```

### 2. Node bot

```bash
cd bot
npm install
cp .env.example .env      # to'ldiring: bot token, DB, ANTHROPIC_API_KEY, INTERNAL_API_SECRET
npm start                 # cron faol, ichki API 4000-portda
```

### 3. Laravel admin

```bash
cd admin
composer install
npm install && npm run build   # Tailwind CSS + JS
cp .env.example .env       # DB bot bilan bir xil; BOT_API_SECRET = bot INTERNAL_API_SECRET
php artisan key:generate
php artisan migrate        # schema.sql'dagi jadvallarni to'ldiradi/tekshiradi (xavfsiz, qayta ishga tushirsa ham)
php artisan db:seed        # admin foydalanuvchi + standart sozlamalar
php artisan serve          # http://127.0.0.1:8000
```

Kirish: `admin` / `admin!@#` (keyin albatta o'zgartiring).

---

## Muhim eslatmalar

1. **Botni har bir kanalga administrator qilib qo'shing** — aks holda yubora olmaydi.
2. `INTERNAL_API_SECRET` (bot) va `BOT_API_SECRET` (admin) **bir xil** bo'lsin.
3. Ikkalasi **bitta** PostgreSQL bazaga ulanadi.
4. AI rejimi uchun `ANTHROPIC_API_KEY` kerak (console.anthropic.com).
5. Production'da botni `pm2` bilan, Laravel'ni nginx+php-fpm bilan doim ishlab
   turadigan qiling. Namuna:
   ```bash
   npm i -g pm2 && cd bot && pm2 start src/index.js --name halowatt-bot
   ```

## Ish oqimi (qisqacha)

```
cron (bot) ──vaqt kelди──> content yaratish (AI yoki RSS)
       │
       ├─ auto_send=1 ─> darhol barcha faol kanallarga yuboradi
       └─ auto_send=0 ─> post 'pending' bo'ladi
                              │
             admin "Xabarlar"da ko'radi ─> tahrirlaydi ─> "Tasdiqlab yuborish"
                              │
                    Laravel ─> bot API /send-post ─> kanallarga yuboradi
```
