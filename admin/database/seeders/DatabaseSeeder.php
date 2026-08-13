<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name'     => 'Halowatt Admin',
                'password' => Hash::make('admin!@#'),
            ]
        );

        $defaultPrompt = <<<'PROMPT'
Siz professional texnologik kontent muharririsiz.
Quyidagi kategoriya/mavzu bo'yicha internetdan eng yangi (oxirgi 7 kun) ma'lumot toping:

Hikvision, HiLook, EZVIZ, HikCentral, Hik-Partner Pro, Hik-Connect,
HikCentral Professional, HikCentral Access Control, HikCentral FocSign,
Hikvision ColorVu, AcuSense, DarkFighter, TandemVu, PTZ, Thermal, ANPR,
Access Control, Face Recognition, Video Intercom, Alarm, Storage, NVR, DVR,
AI, Solar, WiFi, Cloud, HiLook mahsulotlari, EZVIZ Smart Home, EZVIZ ilovalari.

Mavzular: Yangi mahsulotlar, Firmware yangilanishlari, Dastur yangilanishlari,
Mobil ilova yangilanishlari, AI funksiyalari, Cyber Security, Texnik tavsiyalar,
O'rnatish usullari, Professional konfiguratsiya, Yangi texnologiyalar,
Case Study, Success Story, Integratsiyalar, Release Notes.

Manbalar: hikvision.com, hilook.com, ezviz.com, App Store, Google Play,
Hikvision Official Blog, Hikvision Security Bulletin.

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

Javob faqat o'zbek tilida (lotin harflarida) bo'lsin. Texnik terminlarni inglizcha qoldiring.
Marketing matni yozmang. Faqat aniq va tekshirilgan ma'lumotlardan foydalaning.
Har bir javob oxirida kamida 1 ta rasmiy manba keltiring.
Faqat kanalga joylashga tayyor yakuniy matnni qaytar, boshqa izoh yozma.
PROMPT;

        $defaults = [
            'source_mode'      => 'both',
            'auto_send'        => '1',
            'schedule_enabled' => '1',
            'schedule_time'    => '09:00',
            'ai_prompt'        => $defaultPrompt,
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $channelPrompt = <<<'PROMPT'
Siz professional texnologik kontent muharririsiz.
Hikvision, HiLook, EZVIZ, HikCentral va shu kabi video xavfsizlik mahsulotlari bo'yicha
internetdan eng so'nggi (oxirgi 7 kun ichidagi) yangilikni toping. Agar topilmasa, shu
mavzuga oid eng foydali texnik maqola yoki funksiyani tanlang.

Post quyidagi ko'rinishda bo'lsin:
- Boshida mavzuga mos 1 ta emoji va **qalin (bold) formatdagi chiroyli sarlavha**
- Matn mazmunli bo'lib, 50 tadan 500 tagacha so'zdan iborat bo'lsin
- Muhim so'z va iboralar **bold** qilib ajratilsin
- Matn ichida mos joylarda mavzuga mos emoji/ikonlar ishlatilsin (masalan yangilik uchun 🆕,
  xavfsizlik uchun 🔒, kamera uchun 📷, AI uchun 🤖, yangilanish uchun ⚙️ va h.k.) —
  ortiqcha emoji sepilmasin, faqat mazmunga mos joyda ishlatilsin
- Oxirida rasmiy manba (link) ko'rsatilsin

Javob faqat o'zbek tilida (lotin harflarida) bo'lsin. Texnik terminlarni inglizcha qoldiring.
Marketing matni yozmang, faqat aniq va tekshirilgan ma'lumotdan foydalaning.
Faqat kanalga joylashga tayyor yakuniy matnni qaytaring, boshqa izoh yozmang.
PROMPT;

        $hourlySchedule = implode(',', array_map(fn ($h) => sprintf('%02d:00', $h), range(0, 23)));

        Channel::updateOrCreate(
            ['chat_id' => '@halowatt_academy_channel'],
            [
                'title'            => 'Test',
                'is_active'        => true,
                'source_mode'      => 'ai',
                'auto_send'        => true,
                'schedule_enabled' => true,
                'schedule_time'    => $hourlySchedule,
                'language'         => 'uz_latin',
                'ai_prompt'        => $channelPrompt,
            ]
        );
    }
}
