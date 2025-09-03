<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;

class AboutController
{
    private $parser;
    private $inlineKeyboard;
    private $data;
    private $content;
    private $userId;
    private $userFirstname;
    private $userUsername;
    private $chatId;
    private $replyToMessageId;
    private $messageCommandText;
    private $messageTime;
    private $messageTimeFormatted;

    public function __construct($data)
    {
        $text = 'I could not find a match for your enquiry about us.';
        $this->data = $data;
        $this->parser = new Parser;
        $this->inlineKeyboard = new InlineKeyboards;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];
    }

    public function index()
    {
        if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.about_vision.name'), true)) {
            $this->vision();
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.about_mission.name'), true)) {
            $this->mission();
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.about_value.name'), true)) {
            $this->coreValue();
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.about_history.name'), true)) {
            $this->history();
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.about_school_song.name'), true)) {
            $this->schoolSong();
        }
        else {
            $this->intro();
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function intro()
    {
        $text = config('messages.about_us');

        $keyboardBuilder = $this->inlineKeyboard->aboutUsInlineKeyboard(config('telegram.commands_button.about.name'));

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }

    public function vision()
    {
        $text = config('messages.about_vision');

        $keyboardBuilder = $this->inlineKeyboard->aboutUsInlineKeyboard(config('telegram.commands_button.about_vision.name'));

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }

    public function mission()
    {
        $text = config('messages.about_mission');

        $keyboardBuilder = $this->inlineKeyboard->aboutUsInlineKeyboard(config('telegram.commands_button.about_mission.name'));

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }

    public function coreValue()
    {
        $text = config('messages.about_core_value');

        $keyboardBuilder = $this->inlineKeyboard->aboutUsInlineKeyboard(config('telegram.commands_button.about_value.name'));

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }

    public function history()
    {
        $text = config('messages.about_history');

        $keyboardBuilder = $this->inlineKeyboard->aboutUsInlineKeyboard(config('telegram.commands_button.about_history.name'));

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }

    public function schoolSong()
    {
        $text = config('messages.about_school_song');

        $keyboardBuilder = $this->inlineKeyboard->aboutUsInlineKeyboard(config('telegram.commands_button.about_school_song.name'));

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }
}

