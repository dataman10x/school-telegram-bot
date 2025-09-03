<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;

class InfoController
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
        if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.info.name'))) {
            $this->intro();
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function intro()
    {
        $text = config('messages.info');

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard(config('telegram.commands_button.info.name'));

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

