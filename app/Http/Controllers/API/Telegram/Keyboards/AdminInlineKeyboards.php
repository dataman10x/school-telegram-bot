<?php

namespace App\Http\Controllers\API\Telegram\Keyboards;

class AdminInlineKeyboards
{

    public function mainMenuInlinekeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_user_panel_list.label'),
                    '',
                    config('telegram.admin_commands_button.admin_user_panel_list.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_manage.label'),
                    '',
                    config('telegram.admin_commands_button.admin_manage.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_dm_panel.label'),
                    '',
                    config('telegram.admin_commands_button.admin_dm_panel.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_stats_panel.label'),
                    '',
                    config('telegram.admin_commands_button.admin_stats_panel.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_reviews_panel.label'),
                    '',
                    config('telegram.admin_commands_button.admin_reviews_panel.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_broadcasts_panel.label'),
                    '',
                    config('telegram.admin_commands_button.admin_broadcasts_panel.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.exit.label'),
                    '',
                    config('telegram.commands_button.exit.name')
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function flexibleInlinekeyboard($isHome = true, $loadName = null, $loadLabel = null, $backName = null, $backLabel = 'Back')
    {
        $adminName = config('telegram.admin_commands_button.admin.name');
        $getExitName = $adminName .  '.exit';
        $getLoadName = $adminName .  '.' . $loadName;
        $getBackName = $adminName .  '.' . $backName;

        $sub = [];

        $homeBtn = app('telegram_bot')->buildInlineKeyboardButton(
            config('telegram.admin_commands_button.admin.label'),
            '',
            config('telegram.admin_commands_button.admin.name'),
        );

        $backBtn = app('telegram_bot')->buildInlineKeyboardButton(
            $backLabel,
            '',
            $getBackName
        );

        $loadBtn = app('telegram_bot')->buildInlineKeyboardButton(
            $loadLabel,
            '',
            $getLoadName
        );

        $exitBtn = app('telegram_bot')->buildInlineKeyboardButton(
            'Exit',
            '',
            $getExitName
        );

        if($isHome) {
            array_push($sub, $homeBtn);
        }

        if(!is_null($backName) && !is_null($backLabel)) {
            array_push($sub, $backBtn);
        }

        if(!is_null($loadName) && !is_null($loadLabel)) {
            array_push($sub, $loadBtn);
        }

        array_push($sub, $exitBtn);

        $buttonArr = [
            $sub
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }
}
