<?php

namespace App\Support;

use App\Models\Setting;

class AiProviders
{
    // Nofaol qilingan provayderlarni ro'yxatdan chiqaradi; $mustInclude berilsa
    // (masalan hozir tanlangan provayder) nofaol bo'lsa ham saqlab qoladi.
    public static function active(?string $mustInclude = null): array
    {
        $all = config('ai_providers');
        $active = array_filter(
            $all,
            fn ($key) => Setting::get("ai_{$key}_active", '1') !== '0',
            ARRAY_FILTER_USE_KEY
        );

        if ($mustInclude && !isset($active[$mustInclude]) && isset($all[$mustInclude])) {
            $active[$mustInclude] = $all[$mustInclude];
        }

        return $active;
    }
}
