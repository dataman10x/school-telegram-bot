<?php
$inProduction = env('APP_ENV') === 'production';
$telegramToken= env('TELEGRAM_BOT_TOKEN');
$telegramBotPath = "https://api.telegram.org/bot$telegramToken";
$telegramFilePath = "https://api.telegram.org/file/bot$telegramToken";
$webhookUrlBase = $inProduction?env('APP_URL'):env('NGROK_URL');
$endpoint = env('TELEGRAM_BOT_ENDPOINT');
$telegramWebhookPath = "$telegramBotPath/setWebhook?url=$webhookUrlBase/$endpoint";

return [
    'in_production' => $inProduction,
    'telegram_webhook_path' => $telegramWebhookPath,
    'telegram_bot_path' => $telegramBotPath,
    'telegram_file_path' => $telegramFilePath,
    'telegram_gallery_photos_max' => 10,
    'bot_wait_time' => 1,
    'bot_wait_time_long' => 2,
    'item_per_view' => 20,
    'users_per_view' => 20,
    'media_upload_max' => 10,
    'review_per_view' => 5,
    'emojis_max' => 4,
    'default_angel_levels' => [
        'kg' => 'kg',
        'tester' => 'tester'
    ],
    'user_roles' => [
        'superadmin' => 'superadmin',
        'admin' => 'admin',
        'convener' => 'convener',
        'guardian' => 'guardian',
        'parent' => 'parent',
        'user' => 'user',
        'tester' => 'tester'
    ],
    'discs' => [
        'main' => 'main',
        'site' => 'site',
        'teachers' => 'teachers'
    ],
    'input_types' => [
        'text' => 'text',
        'image' => 'image',
        'video' => 'video',
        'audio' => 'audio',
        'file' => 'file'
    ],
    'broadcast_types' => [
        'notice' => 'notice',
        'giveaway' => 'giveaway',
    ],
    'cache_prefix' => [
        'activation' => 'activation_'
    ],
    'user_settings' => [
        'phone' => true,
        'email' => true
    ],
    'app_settings' => [
        'maintenance' => 'maintenance',
    ],
    'app_info' => [
        'intro' => 'intro',
        'help' => 'help',
    ],
    'bot_settings_timer' => null,
    'bot_settings_auths' => [
        'teacher_upload_auths' => 'teacher_upload_auths'
    ],
    'bot_settings_switch' => [
        'can_activate_regno' => 'can_activate_regno',
        'can_upload_photo' => 'can_upload_photo'
    ]
];
