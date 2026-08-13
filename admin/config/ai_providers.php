<?php

// Botda qo'llab-quvvatlanadigan AI provayderlar. Har birining API kaliti va modeli
// "Sozlamalar → AI provayderlar" sahifasida (global) kiritiladi; har bir kanal shulardan
// birini tanlaydi (channels.ai_provider).
return [
    'claude' => [
        'label'         => 'Claude (Anthropic)',
        'key_hint'      => 'console.anthropic.com dan olinadi',
        'model_default' => 'claude-sonnet-5',
        'model_hint'    => 'Masalan: claude-sonnet-5',
    ],
    'chatgpt' => [
        'label'         => 'ChatGPT (OpenAI)',
        'key_hint'      => 'platform.openai.com dan olinadi',
        'model_default' => 'gpt-5.1',
        'model_hint'    => 'Masalan: gpt-5.1',
    ],
    'grok' => [
        'label'         => 'Grok (xAI)',
        'key_hint'      => 'console.x.ai dan olinadi',
        'model_default' => 'grok-4',
        'model_hint'    => 'Masalan: grok-4',
    ],
    'gemini' => [
        'label'         => 'Gemini (Google)',
        'key_hint'      => 'aistudio.google.com dan olinadi',
        'model_default' => 'gemini-2.5-flash',
        'model_hint'    => 'Masalan: gemini-2.5-flash',
    ],
];
