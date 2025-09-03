<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\BotDirectMessages;

class ContactsController
{
    private $parser;
    private $inlineKeyboard;
    private $userAccount;
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
    private $contactsCommand;

    public function __construct($data)
    {
        $text = 'I may not understand your request about contacting us.';
        $this->data = $data;
        $this->parser = new Parser;
        $this->inlineKeyboard = new InlineKeyboards;
        $this->userAccount = new UserAccountController;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);
        
        $this->contactsCommand = config('telegram.commands_button.contacts.name');

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];
    }

    public function index()
    {
        if($this->parser->isTelegramMatch($this->messageCommandText, $this->contactsCommand)) {
            $this->intro();
        }
        else if($this->parser->isTelegramMatch(config('telegram.commands.socials.name'))) {
            $this->socials();
        }
        else if($this->parser->isTelegramMatch(config('telegram.commands.dm.name'))) {
            $this->dm();
        }
        else if($this->parser->isTelegramMatch(config('telegram.commands.call.name'))) {
            $this->call();
        }
        else if($this->parser->isTelegramMatch(config('telegram.commands.mail.name'))) {
            $this->mail();
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function intro()
    {
        $botName = env('TELEGRAM_BOT_LABEL');
        $getPhone = config('messages.call_me');
        $getEmail = config('messages.send_email');
        $getSocials = config('messages.socials');
        $text = "You may contact the Brain behind $botName, through the following:

        $getPhone
        $getEmail
        $getSocials
        ";

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->contactsCommand);

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

    public function socials()
    {
        $text = config('telegram.messages.social_links');

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->contactsCommand);

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

    public function dm()
    {
        $text = "please, type your <b>message</b> in the input field.";

        // save state
        $this->userAccount->setUserInputTrue($this->userId, config('telegram.commands.dm.name') . 'send');

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function call()
    {
        $text = config('telegram.messages.call_me');

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->contactsCommand);

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

    public function mail()
    {
        $text = config('telegram.messages.send_email');

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->contactsCommand);

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

    public function inputHandler($type)
    {
        // reset input
        $this->userAccount->setUserInputFalse($this->userId);

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->contactsCommand);

        $inputText = $this->messageCommandText;

        $text = "<b>Recieved</b> with thanks.";

        if($type == config('telegram.commands.dm.name') . 'send') {
            $text = "Your <b>Direct Message</b> was received. We will send you a reply as soon possible.";

            // save dm to DB
            $hashId = $this->parser->encoder($this->userId);
            $save = new BotDirectMessages;
            $save->message = $inputText;
            $save->user_id = $hashId;
            $save->save();
        }

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
}
