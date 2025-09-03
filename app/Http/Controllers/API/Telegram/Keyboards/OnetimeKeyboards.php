<?php

namespace App\Http\Controllers\API\Telegram\Keyboards;

class OnetimeKeyboards
{

    public function requestContactkeyboard($isActive = true)
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildKeyboardButton(
                    config('telegram.commands_button.register_phone.label'),
                    $isActive
                )
            ]
        ];

        $builder = app('telegram_bot')->buildKeyBoard($buttonArr);

        return $builder;
    }

    public function singlelButtonkeyboard($name)
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildKeyboardButton($name)
            ]
        ];

        $builder = app('telegram_bot')->buildKeyBoard($buttonArr);

        return $builder;
    }

    public function mainOnetimeKeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.menu.name')),
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.demo.name')),
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.products.name')),
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.contacts.name'))
            ]
        ];

        $builder = app('telegram_bot')->buildKeyBoard($buttonArr);

        return $builder;
    }

    public function cancelOnetimeKeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.stop.name'))
            ]
        ];

        $builder = app('telegram_bot')->buildKeyBoard($buttonArr);

        return $builder;
    }

    public function aboutOnetimeKeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.about.name')),
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.stats.name')),
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.search.name')),
                app('telegram_bot')->buildKeyboardButton(config('telegram.commands.help.name'))
            ]
        ];

        $builder = app('telegram_bot')->buildKeyBoard($buttonArr);

        return $builder;
    }

    public function removeOnetimekeyboard($selective = true)
    {
        $builder = app('telegram_bot')->buildKeyBoardHide($selective);

        return $builder;
    }

}
