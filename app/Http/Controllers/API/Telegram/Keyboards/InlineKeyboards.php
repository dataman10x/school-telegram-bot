<?php

namespace App\Http\Controllers\API\Telegram\Keyboards;

class InlineKeyboards
{

    public function startInlinekeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.start.label'),
                    '',
                    config('telegram.commands_button.start.name')
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function appInlineKeyboard($hideItem = null)
    {
        $subs = [];

        if($hideItem !== config('telegram.commands_button.about.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.about.label'),
                '',
                config('telegram.commands_button.about.name')
            );
            array_push($subs, $item);
        }

        if($hideItem !== config('telegram.commands_button.info.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.info.label'),
                '',
                config('telegram.commands_button.info.name')
            );
            array_push($subs, $item);
        }

        if($hideItem !== config('telegram.commands_button.help.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.help.label'),
                '',
                config('telegram.commands_button.help.name')
            );
            array_push($subs, $item);
        }

        if($hideItem !== config('telegram.commands_button.search.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.search.label'),
                '',
                config('telegram.commands_button.search.name')
            );
            array_push($subs, $item);
        }

        $buttonArr = [
            $subs
            ,
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.start.label'),
                    '',
                    config('telegram.commands_button.start.name')
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

    public function aboutUsInlineKeyboard($hideItem = null)
    {
        $subs = [];
        $subsB = [];

        if($hideItem !== config('telegram.commands_button.about.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.about.label'),
                '',
                config('telegram.commands_button.about.name')
            );
            array_push($subs, $item);
        }

        if($hideItem !== config('telegram.commands_button.about_vision.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.about_vision.label'),
                '',
                config('telegram.commands_button.about_vision.name')
            );
            array_push($subs, $item);
        }

        if($hideItem !== config('telegram.commands_button.about_mission.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.about_mission.label'),
                '',
                config('telegram.commands_button.about_mission.name')
            );
            array_push($subs, $item);
        }

        if($hideItem !== config('telegram.commands_button.about_value.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.about_value.label'),
                '',
                config('telegram.commands_button.about_value.name')
            );
            array_push($subsB, $item);
        }

        if($hideItem !== config('telegram.commands_button.about_history.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.about_history.label'),
                '',
                config('telegram.commands_button.about_history.name')
            );
            array_push($subsB, $item);
        }

        if($hideItem !== config('telegram.commands_button.about_school_song.name')) {
            $item = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.about_school_song.label'),
                '',
                config('telegram.commands_button.about_school_song.name')
            );
            array_push($subsB, $item);
        }

        $buttonArr = [
            $subs,
            $subsB,
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.start.label'),
                    '',
                    config('telegram.commands_button.start.name')
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

    public function prevNextInlinekeyboard($next = null, $prev = null, bool $isCancel = false)
    {
        $rowArr = [];

        if(!is_null($prev)) {
            $button = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.prev.label'),
                '',
                $prev
            );
            array_push($rowArr, $button);
        }

        if($isCancel) {
            $button = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.stop.label'),
                '',
                config('telegram.commands_button.stop.name')
            );
            array_push($rowArr, $button);
        }

        if(!is_null($next)) {
            $button = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.next.label'),
                '',
                $next
            );
            array_push($rowArr, $button);
        }
        $buttonArr = [
            $rowArr
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function paginationInlinekeyboard($next = null, $prev = null, $first = null, $last = null, mixed $isExit = true)
    {
        $rowArr = [];

        if(!is_null($first)) {
            $button = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.first.label'),
                '',
                $first
            );
            array_push($rowArr, $button);
        }

        if(!is_null($prev)) {
            $button = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.prev.label'),
                '',
                $prev
            );
            array_push($rowArr, $button);
        }

        if(!is_null($isExit)) {
            if(is_string($isExit)) {
                $button = app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.return.label'),
                    '',
                    $isExit
                );
            }
            else {
                $button = app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.exit.label'),
                    '',
                    config('telegram.commands_button.exit.name')
                );
            }
            array_push($rowArr, $button);
        }

        if(!is_null($next)) {
            $button = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.next.label'),
                '',
                $next
            );
            array_push($rowArr, $button);
        }

        if(!is_null($last)) {
            $button = app('telegram_bot')->buildInlineKeyboardButton(
                config('telegram.commands_button.last.label'),
                '',
                $last
            );
            array_push($rowArr, $button);
        }

        $buttonArr = [
            $rowArr
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function mainInlineKeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.update.label'),
                    '',
                    config('telegram.commands_button.update.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.reviews.label'),
                    '',
                    config('telegram.commands_button.reviews.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.search.label'),
                    '',
                    config('telegram.commands_button.search.name')
                ),
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.help.label'),
                    '',
                    config('telegram.commands_button.help.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.about.label'),
                    '',
                    config('telegram.commands_button.about.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.contacts.label'),
                    '',
                    config('telegram.commands_button.contacts.name')
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function updateInlineKeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.update_own.label'),
                    '',
                    config('telegram.commands_button.update_own.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.update_child.label'),
                    '',
                    config('telegram.commands_button.update_child.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.update_other.label'),
                    '',
                    config('telegram.commands_button.update_other.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.update_teacher_photo.label'),
                    '',
                    config('telegram.commands_button.update_teacher_photo.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.start.label'),
                    '',
                    config('telegram.commands_button.start.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.dm.label'),
                    '',
                    config('telegram.commands_button.dm.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.contacts.label'),
                    '',
                    config('telegram.commands_button.contacts.name')
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function usersInlineKeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    "All Users",
                    '',
                    config('telegram.commands_button.users_list.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.candidates.label'),
                    '',
                    config('telegram.commands_button.candidates.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.parents.label'),
                    '',
                    config('telegram.commands_button.parents.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.admins.label'),
                    '',
                    config('telegram.commands_button.admins.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.start.label'),
                    '',
                    config('telegram.commands_button.return.name')
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function adminSettingsInlineKeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_manage_candidate.label'),
                    '',
                    config('telegram.admin_commands_button.admin_manage_candidate.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_manage_parent.label'),
                    '',
                    config('telegram.admin_commands_button.admin_manage_parent.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_manage_auth.label'),
                    '',
                    config('telegram.admin_commands_button.admin_manage_auth.name')
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.admin_commands_button.admin_manage_switch.label'),
                    '',
                    config('telegram.admin_commands_button.admin_manage_switch.name')
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.return.label'),
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

    public function requestPhoneNmberInlinekeyboard()
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    config('telegram.commands_button.request_phone.label'),
                    '',
                    config('telegram.commands_button.request_phone.name')
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function multiButtonsInlinekeyboard(array $buttons, array $returnButton)
    {
        $rowArr = [];

        try {
            $counter = 0;
            $pArr = [];
            foreach ($buttons as $button) {
                $mkRow = app('telegram_bot')->buildInlineKeyboardButton(
                            $button['label'],
                            '',
                            $button['name']
                        );
                array_push($pArr, $mkRow);
                $counter++;

                if($counter == 3) {
                    array_push($rowArr, $pArr);
                    $counter = 0;
                    $pArr = [];
                }
            }

            if($counter !== 3 && count($pArr) > 0) {
                array_push($rowArr, $pArr);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        $buttonArr = [
            ...$rowArr,
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    $returnButton['label'],
                    '',
                    $returnButton['name']
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

    public function oneButtonInlinekeyboard($name, $label)
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    $label,
                    '',
                    $name
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function twoButtonsInlinekeyboard(array $buttons)
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['first']['label'],
                    '',
                    $buttons['first']['name']
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['return']['label'],
                    '',
                    $buttons['return']['name']
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function threeButtonsInlinekeyboard(array $buttons)
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['first']['label'],
                    '',
                    $buttons['first']['name']
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['second']['label'],
                    '',
                    $buttons['second']['name']
                ),                 
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['return']['label'],
                    '',
                    $buttons['return']['name']
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function fourButtonsInlinekeyboard(array $buttons)
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['first']['label'],
                    '',
                    $buttons['first']['name']
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['second']['label'],
                    '',
                    $buttons['second']['name']
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['third']['label'],
                    '',
                    $buttons['third']['name']
                )
            ],
            [                    
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['return']['label'],
                    '',
                    $buttons['return']['name']
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }

    public function fiveButtonsInlinekeyboard(array $buttons)
    {
        $buttonArr = [
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['first']['label'],
                    '',
                    $buttons['first']['name']
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['second']['label'],
                    '',
                    $buttons['second']['name']
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['third']['label'],
                    '',
                    $buttons['third']['name']
                )
            ],
            [
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['fourth']['label'],
                    '',
                    $buttons['fourth']['name']
                ),
                app('telegram_bot')->buildInlineKeyboardButton(
                    $buttons['return']['label'],
                    '',
                    $buttons['return']['name']
                )
            ]
        ];

        $builder = app('telegram_bot')->buildInlineKeyBoard($buttonArr);

        return $builder;
    }
}
