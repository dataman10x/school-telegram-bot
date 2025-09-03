<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;

class MenuController
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
        $text = 'I may not understand your request about Menu options.';
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
        if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.menu.name'))) {
            $this->main();
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.stop.name'))) {
            $this->cancel();
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.exit.name'))) {
            $this->cancel();
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function main()
    {
        $text = config('messages.main_menu');
        $text = sprintf($text, $this->userFirstname);

        $keyboardBuilder = $this->inlineKeyboard->mainInlineKeyboard();

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

    public function cancel()
    {
        $text = config('messages.cancel_notice');
        $text = sprintf($text, $this->userFirstname);

        $keyboardBuilder = $this->inlineKeyboard->startInlinekeyboard();

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
