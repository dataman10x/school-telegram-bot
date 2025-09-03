<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Models\DialogMessages;
use App\Models\DirectMessages;
use App\Models\Products;
use App\Models\User;

class AdminController
{
    private $parser;
    private $inlineKeyboard;
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
    private $panelName;
    private $cachePrefix;
    private $cacheDuration;
    private $cacheData;
    private $defaultCacheData;
    private $cacheCallbackPrefix;

    public function __construct($data)
    {
        $text = "The requested action may not exist in the Admin section yet.";
        $this->data = $data;
        $this->parser = new Parser;
        $this->onetimeKeyboard = new OnetimeKeyboards;
        $this->inlineKeyboard = new InlineKeyboards;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);
        $this->panelName = config('telegram.commands.admin.name');
        $this->cachePrefix = config('constants.cache_prefix.admin');
        $this->cacheCallbackPrefix = config('constants.cache_prefix.callback');
        $this->cacheDuration = 10;

        $this->cacheData = $this->parser->cacheGet($this->userId, $this->cachePrefix);

        $this->defaultCacheData = [
            'type' => 'panel',
            'data' => null,
            'step' => 1,
            'back' => null,
            'cursor' => 0
        ];

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];

        if(is_null($this->cacheData)) { // set defailt data if null
            $this->parser->cachePut(
                $this->userId,
                $this->defaultCacheData,
                $this->cachePrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );
            $this->cacheData = $this->defaultCacheData;
        }

        // verify user
        $userHashId = $this->parser->encoder($this->userId);
        $adminId = env('TELEGRAM_BOT_ADMIN');
        if($userHashId !== $adminId) {
            return $this->unauthorized();
        }
    }

    public function index()
    {

        if($this->parser->isTelegramStepwise($this->messageCommandText, 'admin ')) {
            $command = str_replace('admin', '', $this->messageCommandText);
            $command = trim($command);
        }
        else if($this->parser->isTelegramStepwise($this->messageCommandText, $this->panelName . '.')){
            $command = str_replace($this->panelName . '.', '', $this->messageCommandText);
        }
        else {
            $command = $this->cacheData['type'];
        }


        if($command == 'panel') {
            return $this->panel();
        }
        else if($command == 'exit') {
            return $this->exitAdmin();
        }
        else if($this->parser->isTelegramStepwise($command, 'user.')) {
            return $this->userPanel($command);
        }
        else if($this->parser->isTelegramStepwise($command, 'biz.')) {
            return $this->bizPanel($command);
        }
        else if($this->parser->isTelegramStepwise($command, 'dm.')) {
            return $this->dmPanel($command);
        }
        else if($this->parser->isTelegramStepwise($command, 'stats.')) {
            return $this->statsPanel($command);
        }
        else if($this->parser->isTelegramStepwise($command, 'review.')) {
            return $this->reviewPanel($command);
        }
        else if($this->parser->isTelegramStepwise($command, 'broadcast.')) {
            return $this->broadcastPanel($command);
        }
        else if($this->parser->isTelegramStepwise($command, 'poll.')) {
            return $this->pollPanel($command);
        }
        else if($this->parser->isTelegramStepwise($command, 'waitlist.')) {
            return $this->waitlistPanel($command);
        }
    }

    private function getResponse()
    {
        $result = null;
        $getCallback = $this->parser->cacheGet(
            $this->userId,
            $this->cacheCallbackPrefix
        );

        if($getCallback == 0) {
            $this->parser->cachePut( // initiate callback
                $this->userId,
                $this->replyToMessageId,
                $this->cacheCallbackPrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );
            $getCallback = $this->replyToMessageId;
        }

        if(!is_null($getCallback) && $getCallback !== 0) {
            $this->content = [
                ...$this->content,
                'message_id' => $getCallback,
            ];
            $result = app('telegram_bot')->editMessageText( $this->content);
        }
        else {
            $result = app('telegram_bot')->sendMessage( $this->content);
        }

        return $result;
    }

    private function userPanel($command)
    {
        if($this->parser->isTelegramStepwise($command, config('constants.admin_commands.user_view'))) {
            $this->userView($command);
        }

        $result = $this->getResponse();
        return response()->json($result, 200);
    }

    private function bizPanel($command)
    {
        switch ($command) {
            case config('constants.admin_commands.biz_view'):
                $this->bizView();
                break;

            default:
                # code...
                break;
        }

        $result = $this->getResponse();
        return response()->json($result, 200);
    }

    private function dmPanel($command)
    {
        switch ($command) {
            case config('constants.admin_commands.dm_unread'):
                $this->dmUnRead();
                break;

            default:
                # code...
                break;
        }

        $result = $this->getResponse();
        return response()->json($result, 200);
    }

    private function statsPanel($command)
    {
        switch ($command) {
            case config('constants.admin_commands.stats_view'):
                $this->statsView();
                break;

            default:
                # code...
                break;
        }

        $result = $this->getResponse();
        return response()->json($result, 200);
    }

    private function reviewPanel($command)
    {
        switch ($command) {
            case config('constants.admin_commands.review_view'):
                $this->reviewView();
                break;

            default:
                # code...
                break;
        }

        $result = $this->getResponse();
        return response()->json($result, 200);
    }

    private function broadcastPanel($command)
    {
        switch ($command) {
            case config('constants.admin_commands.broadcast_view'):
                $this->broadcastView();
                break;

            default:
                # code...
                break;
        }

        $result = $this->getResponse();
        return response()->json($result, 200);
    }

    private function pollPanel($command)
    {
        switch ($command) {
            case config('constants.admin_commands.poll_view'):
                $this->pollView();
                break;

            default:
                # code...
                break;
        }

        $result = $this->getResponse();
        return response()->json($result, 200);
    }

    private function waitlistPanel($command)
    {
        switch ($command) {
            case config('constants.admin_commands.waitlist_view'):
                $this->waitlistView();
                break;

            default:
                # code...
                break;
        }

        $result = $this->getResponse();
        return response()->json($result, 200);
    }

    private function userView($command)
    {
        $text = "USER PANEL: view";
        $this->parser->log($text);

        $type = config('constants.admin_commands.user_view');
        $userPerView = config('constants.users_per_view');

        $loadLabel = 'Next';
        $loadName = $type . '.next';

        $activeName = $type . '.back';

        // set cache
        $back = $this->cacheData['back'];
        $getCursor = $this->cacheData['cursor'];

        if(str_contains($command, '.next')) {
            $getCursor = $getCursor + $userPerView;
        }

        if(str_contains($command, '.back')) {
            $getCursor = $getCursor - $userPerView;
        }

        if($command == 'panel') {
            $getCursor = 0;
        }

        // $this->parser->log("CURSOR: $getCursor");
        // $this->parser->log("COMMAND: $command");

        $this->cacheData = [
            'type' => null,
            'data' => null,
            'step' => 1,
            'back' => $activeName,
            'cursor' => $getCursor
        ];
        $this->parser->cachePut(
            $this->userId,
            $this->cacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $users = User::orderBy('created_at', 'DESC')->offset($getCursor)->limit($userPerView)->get();
        $totalusers = User::count();

        try {
            $countUsers = count($users);
            $usersArr = [];
            if($totalusers > 0) {
                if($countUsers > 0) {
                    foreach ($users as $user) {
                        $id = $user->id;
                        $name = $user->name;
                        $username = $user->username;
                        $roles = implode(', ', $user->roles);
                        $createdAt = $user->created_at;
                        $joinedDate = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6c());
                        $diffDate = $this->parser->diffHumans($createdAt);

                        $sub = "\n<b>$name</b> ($username)\nID: $id\nRoles: $roles\nJoined: $joinedDate; $diffDate\n";

                        array_push($usersArr, $sub);
                    }
                    $text = implode('', $usersArr);

                    if($getCursor == 0) {
                        $text = "<b>$totalusers Users found</b>\n\n$text";
                    }
                    else {
                        $sumUsers = $countUsers + $getCursor;
                        $sumUsers = $sumUsers > $totalusers? $totalusers: $sumUsers;
                        $suffix = $countUsers > 1?'Users':'User';
                        $text = "<b>$countUsers ($sumUsers / $totalusers) $suffix</b>\n\n$text";
                    }

                    if($countUsers < ($getCursor + $userPerView)) {
                        $loadName = null;
                    }
                }
                else {
                    $text = "No more User to view please.";
                }
            }
            else {
                $text = "No user is registered yet";
            }
        } catch (\Throwable $th) {
            //throw $th;
                $text = "<b>Error!!!</b>\n\nAn Error occured.";
                $this->parser->log($th);
        }

        $keyboardBuilder = $this->inlineKeyboard->adminInlinekeyboard(true, $loadName, $loadLabel, $back);

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

    private function bizView()
    {
        $text = "BIZ PANEL: view";
        $this->parser->log($text);

        $loadLabel = 'Unread DM';
        $loadName = config('constants.admin_commands.dm_unread');

        $activeName = config('constants.admin_commands.biz_view');

        // set cache
        $back = $this->cacheData['back'];
        $this->cacheData = [
            'type' => $loadName,
            'data' => null,
            'step' => 1,
            'back' => $activeName,
            'cursor' => 0
        ];
        $this->parser->cachePut(
            $this->userId,
            $this->cacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $keyboardBuilder = $this->inlineKeyboard->adminInlinekeyboard(true, $loadName, $loadLabel, $back);

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

    private function dmUnRead()
    {
        $text = "DM PANEL: unread";
        $this->parser->log($text);

        $loadLabel = 'Statistics';
        $loadName = config('constants.admin_commands.stats_view');

        $activeName = config('constants.admin_commands.dm_unread');

        // set cache
        $back = $this->cacheData['back'];
        $this->cacheData = [
            'type' => $loadName,
            'data' => null,
            'step' => 1,
            'back' => $activeName,
            'cursor' => 0
        ];
        $this->parser->cachePut(
            $this->userId,
            $this->cacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $keyboardBuilder = $this->inlineKeyboard->adminInlinekeyboard(true, $loadName, $loadLabel, $back);

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

    private function statsView()
    {
        $text = "STATS PANEL: view";
        $this->parser->log($text);

        $loadLabel = 'Reviews';
        $loadName = config('constants.admin_commands.review_view');

        $activeName = config('constants.admin_commands.stats_view');

        // set cache
        $back = $this->cacheData['back'];
        $this->cacheData = [
            'type' => $loadName,
            'data' => null,
            'step' => 1,
            'back' => $activeName,
            'cursor' => 0
        ];
        $this->parser->cachePut(
            $this->userId,
            $this->cacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $keyboardBuilder = $this->inlineKeyboard->adminInlinekeyboard(true, $loadName, $loadLabel, $back);

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

    private function reviewView()
    {
        $text = "REVIEW PANEL: view";
        $this->parser->log($text);

        $loadLabel = 'Polls';
        $loadName = config('constants.admin_commands.poll_view');

        $activeName = config('constants.admin_commands.review_view');

        // set cache
        $back = $this->cacheData['back'];
        $this->cacheData = [
            'type' => $loadName,
            'data' => null,
            'step' => 1,
            'back' => $activeName,
            'cursor' => 0
        ];
        $this->parser->cachePut(
            $this->userId,
            $this->cacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $keyboardBuilder = $this->inlineKeyboard->adminInlinekeyboard(true, $loadName, $loadLabel, $back);

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

    private function pollView()
    {
        $text = "POLL PANEL: view";
        $this->parser->log($text);

        $loadLabel = 'Broadcasts';
        $loadName = config('constants.admin_commands.broadcast_view');

        $activeName = config('constants.admin_commands.poll_view');

        // set cache
        $back = $this->cacheData['back'];
        $this->cacheData = [
            'type' => $loadName,
            'data' => null,
            'step' => 1,
            'back' => $activeName,
            'cursor' => 0
        ];
        $this->parser->cachePut(
            $this->userId,
            $this->cacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $keyboardBuilder = $this->inlineKeyboard->adminInlinekeyboard(true, $loadName, $loadLabel, $back);

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

    private function broadcastView()
    {
        $text = "BROADCAST PANEL: view";
        $this->parser->log($text);

        $loadLabel = 'Wait List';
        $loadName = config('constants.admin_commands.waitlist_view');

        $activeName = config('constants.admin_commands.broadcast_view');

        // set cache
        $back = $this->cacheData['back'];
        $this->cacheData = [
            'type' => $loadName,
            'data' => null,
            'step' => 1,
            'back' => $activeName,
            'cursor' => 0
        ];
        $this->parser->cachePut(
            $this->userId,
            $this->cacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $keyboardBuilder = $this->inlineKeyboard->adminInlinekeyboard(true, $loadName, $loadLabel, $back);

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

    private function waitlistView()
    {
        $text = "WAITLIST PANEL: view";
        $this->parser->log($text);

        $loadLabel = 'User View';
        $loadName = config('constants.admin_commands.user_view');

        $activeName = config('constants.admin_commands.waitlist_view');

        // set cache
        $back = $this->cacheData['back'];
        $this->cacheData = [
            'type' => $loadName,
            'data' => null,
            'step' => 1,
            'back' => $activeName,
            'cursor' => 0
        ];
        $this->parser->cachePut(
            $this->userId,
            $this->cacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $keyboardBuilder = $this->inlineKeyboard->adminInlinekeyboard(true, $loadName, $loadLabel, $back);

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

    private function unauthorized()
    {
        $text = "<b>⛔🚫INTRUDER ALERT❗</b>\n\nWhy don't you subscribe to own a Bot like me? See as you wish to break into a highly secure sector😁😆\n\n$this->userFirstname, please subscribe, the price is very affordable.";

        $keyboardBuilder = app('telegram_bot')->buildKeyBoardHide();

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

    private function exitAdmin()
    {
        $text = "<b>Admin Panel Closed</b>\n\n$this->userFirstname, I hope you were able to complete all you wish to do.\n\nSee you soon my amiable Oga😘";

        $keyboardBuilder = app('telegram_bot')->buildKeyBoardHide();

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];

        // reset cache
        $this->parser->cacheRemove(
            $this->userId,
            $this->cachePrefix,
        );

        $this->parser->log($this->cacheData);

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    private function panel()
    {
        $this->parser->cachePut( // initiate callback
            $this->userId,
            0,
            $this->cacheCallbackPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $this->parser->cachePut(
            $this->userId,
            $this->defaultCacheData,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $text = "<b>Admin Panel</b>\n\n$this->userFirstname, my Oga. I am here at your service. Kindly choose the action you wish I perform.";

        $keyboardBuilder = $this->inlineKeyboard->adminMainMenuInlinekeyboard();

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

    public function inputHandler($type)
    {
        switch ($type) {
            case config('constants.admin_commands.biz_delete'):
                return $this->bizDeleteProcessor();
                break;
            case config('constants.admin_commands.dm_delete'):
                return $this->dmDeleteProcessor();
                break;
            case config('constants.admin_commands.review_delete'):
                return $this->reviewDeleteProcessor();
                break;
            case config('constants.admin_commands.poll_new'):
                return $this->pollNewProcessor();
                break;
            case config('constants.admin_commands.poll_edit'):
                return $this->pollEditProcessor();
                break;
            case config('constants.admin_commands.poll_delete'):
                return $this->pollDeleteProcessor();
                break;
            case config('constants.admin_commands.broadcast_new'):
                return $this->broadcastNewProcessor();
                break;
            case config('constants.admin_commands.broadcast_edit'):
                return $this->broadcastEditProcessor();
                break;
            case config('constants.admin_commands.broadcast_delete'):
                return $this->broadcastDeleteProcessor();
                break;
            case config('constants.admin_commands.waitlist_delete'):
                return $this->waitlistDeleteProcessor();
                break;

            default:
                # code...
                break;
        }

    }

    private function bizDeleteProcessor()
    {
        $type = config('constants.admin_commands.biz_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function dmDeleteProcessor()
    {
        $type = config('constants.admin_commands.dm_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function reviewDeleteProcessor()
    {
        $type = config('constants.admin_commands.review_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function pollNewProcessor()
    {
        $type = config('constants.admin_commands.poll_new');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function pollEditProcessor()
    {
        $type = config('constants.admin_commands.poll_edit');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function pollDeleteProcessor()
    {
        $type = config('constants.admin_commands.poll_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function broadcastNewProcessor()
    {
        $type = config('constants.admin_commands.broadcast_new');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function broadcastEditProcessor()
    {
        $type = config('constants.admin_commands.broadcast_edit');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function broadcastDeleteProcessor()
    {
        $type = config('constants.admin_commands.broadcast_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function waitlistDeleteProcessor()
    {
        $type = config('constants.admin_commands.waitlist_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }
}

