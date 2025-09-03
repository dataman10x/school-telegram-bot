<?php

namespace App\Http\Controllers\API\Telegram\Keyboards;

class SuperAdminInlineKeyboards
{

    public function mainMenuInlinekeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.superadmin_commands_button.superadmin_user_panel_list.label'),
                    '',
                    config('telegram.superadmin_commands_button.superadmin_user_panel_list.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.superadmin_commands_button.superadmin_user_panel_banned.label'),
                    '',
                    config('telegram.superadmin_commands_button.superadmin_user_panel_banned.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.superadmin_commands_button.superadmin_upgrade.label'),
                    '',
                    config('telegram.superadmin_commands_button.superadmin_upgrade.name')
                ),
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin.label'),
                    '',
                    config('telegram.admin_commands_button.admin.name')
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
        $adminName = config('telegram.superadmin_commands_button.superadmin.name');
        $getExitName = $adminName .  '.exit';
        $getLoadName = $adminName .  '.' . $loadName;
        $getBackName = $adminName .  '.' . $backName;

        $sub = [];

        $homeBtn = app('telegram_bot')->buildInlineKeyboardButton(
            config('telegram.superadmin_commands_button.superadmin.label'),
            '',
            config('telegram.superadmin_commands_button.superadmin.name'),
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
