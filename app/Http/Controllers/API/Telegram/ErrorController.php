<?php

namespace App\Http\Controllers\API\Telegram;

use App\Http\Controllers\Controller;
use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Http\Controllers\API\UserAccountController;

class ErrorController extends Controller
{
    private $parser;
    private $cachePrefix;
    private $userAccount;
    private $onetimeKeyboard;
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
        $this->cachePrefix = config('constants.cache_prefix.chat');
        $text = 'I am so glad you are here.';

        $this->data = $data;
        $this->parser = new Parser;
        $this->userAccount = new UserAccountController;
        $this->onetimeKeyboard = new OnetimeKeyboards;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = '';

        try {
            $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);
        } catch (\Throwable $th) {
            //throw $th;
        }

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];
    }

    public function main()
    {
        $text = config('telegram.messages.error_main');

        $keyboardBuilder = $this->onetimeKeyboard->mainOnetimeKeyboard();

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function error($error)
    {
        $keyboardBuilder = app('telegram_bot')->buildKeyBoardHide();

        $data = [
            'text' => $error,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }
}

