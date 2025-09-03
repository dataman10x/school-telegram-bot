<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Models\BotCallbacks;

class HelpController
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
    private $userHashId;
    private $callbacks;
    private $callbackData;
    private $callbackType;

    public function __construct($data)
    {
        $text = "We could not find the exact help for you. You may consider sending us a <b>Direct Message</b> instead";
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
        
        $this->callbackType = config('constants.input_types.text');
        $this->callbackData = [
            'type' => config('constants.input_types.text'),
            'reply_id' => null
        ];

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];

        // verify user
        $this->userHashId = $this->parser->encoder($this->userId);

        $this->callbacks = BotCallbacks::find($this->userHashId);
        try {
            if(!is_null($this->callbacks)) {
                $this->callbackData = [
                    'type' => $this->callbacks->type,
                    'reply_id' => $this->callbacks->reply_id
                ];
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function index()
    {
        if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.help.name'))) {
            $this->intro();
        }
        else {
            $result = $this->getResponseText();
            return response()->json($result, 200);
        }    
    }

    private function getResponseText($overrideId = null, $overrideType = null)
    {
        $result = null;

        if(!is_null($overrideId)) {
            $this->callbackData['reply_id'] = $overrideId;
        }
        
        if(!is_null($overrideType)) {
            $this->callbackData['type'] = $overrideType;
        }

        if($this->callbackData['type'] == config('constants.input_types.text')) {
            if(!is_null($this->callbackData['reply_id'])) {
                $this->content = [
                    ...$this->content,
                    'message_id' => $this->callbackData['reply_id'],
                ];
                
                $result = app('telegram_bot')->editMessageText( $this->content);
            }
            else {
                $result = app('telegram_bot')->sendMessage( $this->content);
            }
        }
        else {
            $result = app('telegram_bot')->sendMessage( $this->content);
        }

        try {
            $toObj = json_decode($result);
            if(!$toObj->ok) {
                $this->parser->log($result);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $result;
    }

    public function intro($type = null)
    {
        $subs = [];
        array_push($subs, '/' . config('telegram.commands_button.about.name') . " - Get to know who we are.");
        array_push($subs, '/' . config('telegram.commands_button.help.name') . " - Tips & links to better navigate this Bot.");
        array_push($subs, '/' . config('telegram.commands_button.dm.name') . " - Direct message us.");
        array_push($subs, '/' . config('telegram.commands_button.contacts.name') . " - Call or send us email.");
        array_push($subs, '/' . config('telegram.commands_button.stats.name') . " - Summary of Bot statistics.");
        array_push($subs, '/' . config('telegram.commands_button.search.name') . " - Find anything by keyword.");
        array_push($subs, '/' . config('telegram.commands_button.info.name') . " - Other important information you need to know.");
        array_push($subs, '/' . config('telegram.commands_button.questions.name') . " - FAQ section for quick answers.");
        array_push($subs, '/' . config('telegram.commands_button.reviews.name') . " - Write us a review.");
        array_push($subs, '/' . config('telegram.commands_button.broadcasts.name') . " - Annoucements you need to know.");
        array_push($subs, '/' . config('telegram.commands_button.parents.name') . " - List of Moms & Dads.");

        $introText = config('messages.help');
        $subStr = implode("\n\n", $subs);
        $searchTips = config('messages.search_tips');
        $text = "$introText\n\n$subStr\n\n$searchTips";

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard(config('telegram.commands_button.help.name'));

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    
        $result = $this->getResponseText();
        return response()->json($result, 200);
    }
}

