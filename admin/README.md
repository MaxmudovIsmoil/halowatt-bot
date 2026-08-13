# Halowatt Academy — Admin panel (Laravel 13, PHP 8.3+)

Node.js botni boshqaradigan admin oyna. Bitta admin login qiladi va botni
to'liq nazorat qiladi: kanallar, manbalar, jadval, prompt, tasdiqlash.
Frontend Tailwind CSS'da, Blade shablonlar bilan qurilgan — qorong'i (dark)
va yorug' (light) rejimlarni admin xohlagan payt yuqori o'ng burchakdagi
tugma orqali almashtiradi (tanlov brauzerda `localStorage`da saqlanadi).

## O'rnatish

```bash
cd admin
composer install
npm install && npm run build      # Tailwind CSS + JS assetlarini yig'ish
cp .env.example .env
php artisan key:generate

# .env ni to'ldiring (DB bot bilan BIR XIL, BOT_API_SECRET bot bilan bir xil)
php artisan migrate --seed        # jadvallar + admin foydalanuvchi
php artisan serve                 # http://127.0.0.1:8000
```

Ishlab chiqish paytida (frontend'ga o'zgartirish kiritsangiz) alohida
terminalda `npm run dev` ishga tushiring — Vite o'zgarishlarni real vaqtda
qayta yuklaydi.

> Baza `schema.sql` bilan allaqachon yaratilgan bo'lsa, `migrate` o'rniga
> faqat `php artisan db:seed` ni ishlating (admin foydalanuvchi uchun).

## Kirish

- URL: `http://127.0.0.1:8000/login`
- Email: `admin@halowatt.uz`
- Parol: `halowatt123`  ← **birinchi kirishdan keyin o'zgartiring**

## Bo'limlar

- **Bosh sahifa** — statistika, "hozir yubor / tasdiqqa qo'y" tugmalari
- **Xabarlar** — yaratilgan postlar; tasdiqlash, tahrirlash, yuborish, tarix
- **Kanallar** — Telegram kanallarini qo'shish/o'chirish (botni admin qiling!)
- **Manbalar** — RSS manbalar (RSS/both rejimi uchun)
- **Sozlamalar** — manba rejimi, avto/tasdiq, jadval vaqti, AI prompt

## Bot bilan bog'lanish

Admin "yubor" tugmalarini bosganda Laravel → Node bot ichki API siga
(`BOT_API_URL`) `X-Internal-Secret` header bilan so'rov yuboradi.
Shu sabab **bot ishlab turishi** va ikki `.env` dagi secret **bir xil** bo'lishi shart.
