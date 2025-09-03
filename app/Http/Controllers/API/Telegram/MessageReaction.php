<?php

namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Http\Controllers\Controller;
use App\Models\CacheSliders;
use App\Models\Callbacks;
use App\Models\EmojiReactions;
use Illuminate\Http\Request;

class MessageReaction extends Controller
{
    private $parser;
    private $content;
    private $data;
    private $inlineKeyboard;
    private $messageReaction;
    private $chatAction;
    private $userId;
    private $userHashId;
    private $userFirstname;
    private $userUsername;
    private $chatId;
    private $replyToMessageId;
    private $messageCommandText;
    private $oldReaction;
    private $newReaction;
    private $messageTime;
    private $messageTimeFormatted;
    private $usersPerView;
    private $callbacks;
    private $callbackData;
    private $callbackType;
    private $callbackId;
    private $typeCommand;
    private $inputData;
    private $sliderData;
    private $firstBtnCmd;
    private $lastBtnCmd;
    private $nextBtnCmd;
    private $previousBtnCmd;
    private $viewCommand;
    private $emojisCommand;
    private $returnLabel;

    public function __construct($data)
    {
        $text = "This message may have reaction";
        $this->data = $data;
        $this->messageCommandText = $data['message-command'];
        $this->messageReaction = $data['message-reaction'];
        $this->parser = new Parser;
        $this->inlineKeyboard = new InlineKeyboards;
        
        $this->usersPerView = config('constants.users_per_view');
        $this->chatAction = config('telegram.chatactions.text');

        try {
            $this->userId = $this->data['user-id'];
            $this->userFirstname = $this->data['user-firstname'];
            $this->userUsername = $this->data['user-username'];
            $this->chatId = $this->data['chat-id'];
            $this->replyToMessageId = $this->data['message-id'];
            $this->messageCommandText = $this->data['message-command'];
            $this->messageTime = $this->data['message-date'];
            $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $this->userId = $this->messageReaction['user-id'];
            $this->userFirstname = $this->messageReaction['firstname'];
            $this->userUsername = $this->messageReaction['username'];
            $this->chatId = $this->messageReaction['chat-id'];
            $this->replyToMessageId = $this->messageReaction['message-id'];
            $this->oldReaction = $this->messageReaction['old-reaction'];
            $this->newReaction = $this->messageReaction['new-reaction'];
            $this->messageTime = $this->messageReaction['date'];
            $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);
        } catch (\Throwable $th) {
            //throw $th;
        }

        $this->firstBtnCmd = config('telegram.commands_button.first.name');
        $this->lastBtnCmd = config('telegram.commands_button.last.name');
        $this->nextBtnCmd = config('telegram.commands_button.next.name');
        $this->previousBtnCmd = config('telegram.commands_button.prev.name');
        
        $this->emojisCommand = config('telegram.commands_button.emojis.name');
        $this->returnLabel = config('telegram.commands_button.return.label');
        
        $this->viewCommand = config('telegram.commands_button.view.name');
        
        $this->callbackType = config('constants.input_types.text');
        $this->callbackData = [
            'type' => config('constants.input_types.text'),
            'reply_id' => null
        ];

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];

        $this->userHashId = $this->parser->encoder($this->userId);
        $this->sliderData = CacheSliders::find($this->userHashId);
        $this->callbacks = Callbacks::find($this->userHashId);
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

    public function init()
    {
        if(is_null($this->messageCommandText)) {
            $hashUserId = $this->parser->encoder($this->userId);
            $sliderData = CacheSliders::find($hashUserId);
            $this->messageCommandText = $sliderData->command;
        }

        $this->deleteEmoji();
        $this->createEmoji();
        $this->countDuplicates();
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

    private function deleteLastMessage($replyId = null)
    {
        $getId = !is_null($replyId)? $replyId: $this->replyToMessageId;
        // remove last command
        $this->content['message_id'] = $getId;
        app('telegram_bot')->deleteMessage($this->content);
    }

    private function createEmoji()
    {
        try {
            $hashUserId = $this->parser->encoder($this->userId);
            $getType = $this->newReaction['type'];
            $getEmoji = $this->newReaction['emoji'];
            $getMessage = EmojiReactions::where("message_id", $this->messageCommandText)->where("user_id", $hashUserId)->pluck('id');
            if(!is_null($getMessage)) { // delete emoji if exist
                EmojiReactions::whereIn('id', $getMessage)->delete();
            }

            if(!is_null($getEmoji)) {
                $message = new EmojiReactions;
                $message->chat_id = $this->chatId;
                $message->message_id = $this->messageCommandText;
                $message->type = $getType;
                $message->emoji = $getEmoji;
                $message->user_id = $hashUserId;
                $message->save();
            }
        } catch (\Throwable $th) {
            // throw $th;
        }
    }

    private function deleteEmoji()
    {
        try {
            $hashUserId = $this->parser->encoder($this->userId);
            $getEmoji = $this->oldReaction['emoji'];
            if(!is_null($getEmoji)) { // remove all previously saved emoji by user
                $getMessage = EmojiReactions::where("message_id", $this->messageCommandText)->where("user_id", $hashUserId)->pluck('id');
                if(!is_null($getMessage)) { // delete emoji if exist
                    EmojiReactions::whereIn('id', $getMessage)->delete();
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function countDuplicates($chatId = null, $messageId = null)
    {
        $res = null;
        $getChatId = !is_null($chatId)? $chatId: $this->chatId;
        $getMessageId = !is_null($messageId)? $messageId: $this->replyToMessageId;

        try {
            if(!is_null($getChatId) && !is_null($getMessageId)) {
                $getDupliactes = EmojiReactions::where('chat_id', $getChatId)->where('message_id', $getMessageId)->get()->toArray();
            }
            else {
                $getDupliactes = EmojiReactions::all()->toArray();
            }
            $getCollection = collect($getDupliactes);
    
            $res = $getCollection->groupBy('emoji')
                ->mapWithKeys(function ($item) {
                    return [$item->first()['emoji'] => $item->count()];
                })->sortDesc();
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function displayEmojis($getEmojis, $text, $limit = 3) {   
        $res = $text;
        if(!is_null($getEmojis)) {
            $newText = [];
            $counter = 0;
            $total = 0;
            foreach ($getEmojis as $key=> $item) {
                if($counter < $limit) {
                    $label = $key . "[$item]";
                    array_push($newText, $label);
                    $counter++;
                }
                $total += intval($item);
            }
            
            $more = " /emojis";

            $getTotal = $this->parser->numberFormatter($total);
            $reactions = "👋🏻$getTotal: " . implode(' | ', $newText) . $more;
            $res = $text . "\n" . $reactions;
        }

        return $res;
    }
    
    public function emojisList($chatId = null, $messageId = null, $commandText = null)
    {
        $getChatId = !is_null($chatId)? $chatId: $this->chatId;
        $text = "EMOJIS: view";

        $this->typeCommand = $messageId;
        $this->content['action'] = $this->chatAction;
        app('telegram_bot')->sendChatAction($this->content);

        $command = $this->typeCommand;
        $sliderCmd = $this->emojisCommand . '_' . $command;
        $first = "$sliderCmd.$this->firstBtnCmd";
        $prev = "$sliderCmd.$this->previousBtnCmd";
        $next = "$sliderCmd.$this->nextBtnCmd";
        $last = "$sliderCmd.$this->lastBtnCmd";
        $isExit = config('telegram.commands_button.users_list.name');
        $infoArr = [];
        $emojisArr = [];
        $totalemojis = EmojiReactions::where('chat_id', $getChatId)->where('message_id', $command)->count();
        $totalemojisFormatted = $this->parser->numberFormatter($totalemojis);
        $limit = $this->usersPerView > $totalemojis? $totalemojis: $this->usersPerView;
        $totalemojislabel = $totalemojis > 1? "$totalemojisFormatted Emojis": "$totalemojis Emoji";
        $label = "$totalemojislabel (1 - $limit)";
        $activePresent = 0;
        $activeNext = $limit;

        $sliderData = $this->sliderData;
        $userAccount = new UserAccountController;

        try {
            if(!is_null($sliderData)) {
                $label = $sliderData->label;
                $active = $sliderData->active_step;
                $activePresent = intval($active);
            }

            if(!is_null($sliderData) && is_null($commandText)) {
                $this->typeCommand = $commandText;
                $commandText = $sliderData->command;
                $first = $sliderData->first_step;
                $prev = $sliderData->previous_step;
                $next = $sliderData->next_step;
                $last = $sliderData->last_step;
                $infoArr = $sliderData->steps_info;
            }
        } catch (\Throwable $th) {
            // $this->parser->log($th);
        }
        
        if($commandText == '/' . $this->emojisCommand) {
            $activePresent = 0;
        }


        // increment active pointer if next
        if($commandText == $next) {
            $activeNext = $activePresent + $limit;
        }
        else if($commandText == $prev) {
            $activeNext = $activePresent - $limit;
            $activePresent = $activePresent - ($limit * 2);
        }
        else if($commandText == $first) {
            $activeNext = $limit;
            $activePresent = 0;
        }
        else if($commandText == $last) {
            $activePresent = $totalemojis - $limit;
            $activeNext = $totalemojis;
        }

        if($totalemojis <= $activeNext) {
            $activeNext = $totalemojis;
        }

        $fromItem = $activePresent == 0? 1: ($activePresent + 1);
        $label = "$totalemojislabel ($fromItem - $activeNext)";


        // create callback only if no slider exist
        if(is_null($this->callbackData['reply_id'])) {
            $this->callbackData['reply_id'] = $this->replyToMessageId;
            
            $userAccount->setCallback(
                $this->userId, $this->replyToMessageId, $this->callbackType
            );
        }
            
        // create slider & callback
        $userAccount->setSlider(
            $this->userId, 
            $label, 
            $command, 
            $first, 
            $prev,
            $activeNext, 
            $next, 
            $last
        );

        if($activePresent < 0) {
            $activePresent = 0;
        }

        $emojis = null;
        $countEmojis = 0;

        try {
            $emojis = EmojiReactions::where('chat_id', $getChatId)->where('message_id', $command)->orderBy('created_at', 'ASC')
                ->offset($activePresent)->limit($limit)->get();
            if($totalemojis <= $limit) {
                $emojis = EmojiReactions::where('chat_id', $getChatId)->where('message_id', $command)
                    ->orderBy('created_at', 'ASC')->offset($activePresent)->get();
            }
            $countEmojis = count($emojis);
        } catch (\Throwable $th) {
            //throw $th;
        }

        if($totalemojis == 0) {
            $text = "No Emoji was saved yet";
        }

        try {
            foreach ($emojis as $emoji) {
                $user = $emoji->user;
                $id = $user->id;
                $name = $user->name;
                $getEmojiSym = $emoji->emoji;
                $createdAt = $emoji->created_at;
                $joinedDate = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6c());
                $diffDate = $this->parser->diffHumans($createdAt);

                // add more commands
                $viewCmd = $this->viewCommand . "_$id";
                $moreCommands = "/$viewCmd";
                $sub = "\n$getEmojiSym <b>$name</b> $diffDate\n$moreCommands\n";

                array_push($emojisArr, $sub);
            }
            $text = implode('', $emojisArr);
            $cursorPresent = $countEmojis + $activePresent;
            $text = "$cursorPresent of $label\n\n$text";
        } catch (\Throwable $th) {
            //throw $th;
            $this->parser->log($th);
                $text = "<b>Error!!!</b>\n\nAn Error occured.";
        }

        // set buttons visibility
        if($activeNext >= $totalemojis) {
            $last = null;
            $next = null;
        }
        if($activeNext <= $countEmojis) {
            $first = null;
            $prev = null;
        }

        $keyboardBuilder = $this->inlineKeyboard->paginationInlinekeyboard(
            $next, $prev, $first, $last, $isExit
        );


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
        if($commandText == $this->emojisCommand || $commandText == "/$this->emojisCommand") {
            $this->deleteLastMessage($this->replyToMessageId);
        }
        return response()->json($result, 200);
    }
}
