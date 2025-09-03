<?php
namespace App\Http\Controllers\API\Telegram;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Classes\Ability;
use App\Classes\Parser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\SmartException;
use App\Exceptions\SmartResponse;
use App\Http\Controllers\API\Telegram\AdminController;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\Admins;
use App\Models\Angels;
use App\Models\CacheInputs;
use App\Models\CacheSliders;
use App\Models\Callbacks;
use App\Models\Conveners;
use App\Models\EmojiReactions;
use App\Models\Guardians;
use App\Models\User;
use App\Models\VisitCounters;
use App\Models\MediaCounters;
use App\Models\Parents;
use BasementChat\Basement\Enums\MessageType;
use DateTime;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response as FacadesResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsersController extends Controller
{
    private $inProduction;
    private $parser;
    private $inlineKeyboard;
    private $onetimeKeyboards;
    private $data;
    private $chatAction;
    private $content;
    private $user;
    private $userId;
    private $userHashId;
    private $userFirstname;
    private $userUsername;
    private $chatId;
    private $replyToMessageId;
    private $messageCommandText;
    private $messageTime;
    private $messageTimeFormatted;
    private $panelName;
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
    private $editCommand;
    private $deleteCommand;
    private $emojisCommand;
    private $returnLabel;
    private $introCommand;
    private $usersCommand;
    private $usersListCommand;

    public function __construct($data, $user = null, $inputData = null, $sliderData = null)
    {
        $text = "The requested action may not exist in the User section yet.";
        $this->data = $data;
        $this->user = $user;
        $this->parser = new Parser;
        $this->inlineKeyboard = new InlineKeyboards;
        $this->onetimeKeyboards = new OnetimeKeyboards;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);
        $this->panelName = config('telegram.commands.admin.name');
        $this->usersPerView = config('constants.users_per_view');
        $this->chatAction = config('telegram.chatactions.text');

        $this->typeCommand = config('telegram.admin_commands_button.admin.name');
        $this->inputData = $inputData;
        $this->sliderData = $sliderData;

        $this->firstBtnCmd = config('telegram.commands_button.first.name');
        $this->lastBtnCmd = config('telegram.commands_button.last.name');
        $this->nextBtnCmd = config('telegram.commands_button.next.name');
        $this->previousBtnCmd = config('telegram.commands_button.prev.name');
        
        $this->viewCommand = config('telegram.commands_button.view.name');
        $this->editCommand = config('telegram.commands_button.edit.name');
        $this->deleteCommand = config('telegram.commands_button.delete.name');
        $this->emojisCommand = config('telegram.commands_button.emojis.name');
        $this->returnLabel = config('telegram.commands_button.return.label');

        $this->introCommand = config('telegram.commands_button.start.name');
        $this->usersCommand = config('telegram.commands_button.users.name');
        $this->usersListCommand = config('telegram.commands_button.users_list.name');
    
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
        $this->authorization();

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

    private function authorization()
    {
        $userAuth = false;
        
        try {
            $user = $this->user;
            $isSuperAdmin = $user->isSuperAdmin();
            $admin = $user->admin;

            if($isSuperAdmin || !is_null($admin)) { // allow super admin
                $userAuth = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
            $this->parser->log($th);
        }

        if(!$userAuth) {
            $this->unauthorized();
            exit();
        }
    }

    private function unauthorized($input = null)
    {
        $defaultText = config('messages.intruder_alert');
        $getText = sprintf($defaultText, $this->userFirstname);
        $text = !is_null($input)? $input: $getText;
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

    public function index()
    {
        $command = trim($this->messageCommandText);

        $this->moreHandler($command);

        if($this->parser->isTelegramMatch($command, $this->usersCommand, true)) {
            return $this->userPanel($command);
        }
        else if($this->parser->isTelegramMatch($command, $this->usersListCommand)) {
            return $this->userList($command);
        }
    }

    private function moreHandler($command)
    {
        if(!is_null($command)) {
            // actions: view, edit, delete, stat
            $commandText = str_replace('/', '', $command);

            if($this->parser->isTelegramMatch($commandText, $this->viewCommand)) {
                $getId = str_replace($this->viewCommand . '_', '', $commandText);
                return $this->viewUser($getId);
            }

            if($this->parser->isTelegramMatch($commandText, $this->emojisCommand)) {
                $emojis = new \App\Http\Controllers\API\Telegram\MessageReaction($this->data);
                return $emojis->emojisList($this->chatId, $this->usersListCommand, $commandText);
            }
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

    private function deleteLastMessage()
    {
        // remove last command
        $this->content['message_id'] = $this->replyToMessageId;
        app('telegram_bot')->deleteMessage($this->content);
    }

    private function userPanel()
    {
        $countUsers = User::count();
        $countAdmins = Admins::count();
        $countConveners = Conveners::count();
        $countGuardians = Guardians::count();
        $countParents = Parents::count();
        $countAngels = Angels::count();
        $countUnregistered = User::whereNull('phone')->count();

        $countUsersF = $this->parser->numberFormatter($countUsers);
        $countAdminsF = $this->parser->numberFormatter($countAdmins);
        $countConvenersF = $this->parser->numberFormatter($countConveners);
        $countGuardiansF = $this->parser->numberFormatter($countGuardians);
        $countParentsF = $this->parser->numberFormatter($countParents);
        $countAngelsF = $this->parser->numberFormatter($countAngels);
        $countUnregisteredF = $this->parser->numberFormatter($countUnregistered);
        $countOtherRegistered = $countUsers - ($countAdmins + $countConveners + $countGuardians + $countParents + $countAngels + $countUnregistered);
        $countOtherRegisteredF = $this->parser->numberFormatter($countOtherRegistered);
        $title = "There are $countUsersF Users in the following categories:";
        $body = "Angels: $countAngelsF\nParents: $countParentsF\nGuardians: $countGuardiansF\nConveners: $countConvenersF\nAdmins: $countAdminsF\nOther Registered: $countOtherRegisteredF\nOther Unregistered: $countUnregisteredF";
        $text = "$title\n\n$body";

        $keyboardBuilder = $this->inlineKeyboard->usersInlineKeyboard();

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

    private function userList($commandText = null)
    {
        $text = "USER PANEL: view";

        $this->typeCommand = $this->usersListCommand;
        $this->content['action'] = $this->chatAction;
        app('telegram_bot')->sendChatAction($this->content);

        $command = $this->typeCommand;
        $first = "$command.$this->firstBtnCmd";
        $prev = "$command.$this->previousBtnCmd";
        $next = "$command.$this->nextBtnCmd";
        $last = "$command.$this->lastBtnCmd";
        $isExit = config('telegram.commands_button.start.name');
        $infoArr = [];
        $usersArr = [];
        $totalusers = User::count();
        $totalusersFormatted = $this->parser->numberFormatter($totalusers);
        $limit = $this->usersPerView > $totalusers? $totalusers: $this->usersPerView;
        $totaluserslabel = $totalusers > 1? "$totalusersFormatted Users": "$totalusers User";
        $label = "$totaluserslabel (1 - $limit)";
        $activePresent = 0;
        $activeNext = $limit;

        // $sliderData = CacheSliders::find($this->userHashId);
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
            $this->parser->log($th);
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
            $activePresent = $totalusers - $limit;
            $activeNext = $totalusers;
        }

        if($totalusers <= $activeNext) {
            $activeNext = $totalusers;
        }

        $fromItem = $activePresent == 0? 1: ($activePresent + 1);
        $label = "$totaluserslabel ($fromItem - $activeNext)";

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

        $users = User::orderBy('created_at', 'ASC')->offset($activePresent)->limit($limit)->get();
        if($totalusers < $limit) {
            $users = User::orderBy('created_at', 'ASC')->offset($activePresent)->get();
        }
        $countUsers = count($users);

        if($totalusers == 0) {
            $text = "No user is registered yet";
        }

        try {
            foreach ($users as $user) {
                $id = $user->id;
                $name = $user->name;
                $username = $user->username;
                $firstname = $user->firstname;
                $lastname = $user->lastname;
                $roles = $user->role;
                $phone = $user->phone;
                $createdAt = $user->created_at;
                $joinedDate = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6c());
                $diffDate = $this->parser->diffHumans($createdAt);

                // add more commands
                $viewCmd = $this->viewCommand . "_$id";
                $moreCommands = "/$viewCmd";
                $sub = "\n<b>$name(ID: $id)</b> ($username)\nName: $firstname $lastname\nPhone: $phone\nRoles: $roles\nJoined: $joinedDate; $diffDate\n$moreCommands\n";

                array_push($usersArr, $sub);
            }
            $text = implode('', $usersArr);
            $cursorPresent = $countUsers + $activePresent;
            $text = "$cursorPresent of $label\n\n$text";
        } catch (\Throwable $th) {
            //throw $th;
                $text = "<b>Error!!!</b>\n\nAn Error occured.";
                $this->parser->log($th);
        }

        // set buttons visibility
        if($activeNext >= $totalusers) {
            $last = null;
            $next = null;
        }
        if($activeNext <= $countUsers) {
            $first = null;
            $prev = null;
        }

        $keyboardBuilder = $this->inlineKeyboard->paginationInlinekeyboard(
            $next, $prev, $first, $last, $isExit
        );

        
        try {
            $emojis = new \App\Http\Controllers\API\Telegram\MessageReaction($this->data);
            $getEmojis = $emojis->countDuplicates($this->chatId, $this->usersListCommand);
            $text = $emojis->displayEmojis($getEmojis, $text, config('constants.emojis_max'));
        } catch (\Throwable $th) {
            //throw $th;
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

        $result = $this->getResponseText($this->callbackData['reply_id']);
        return response()->json($result, 200);
    }

    private function viewUser($id)
    {
        $text = "Details of User (ID: $id)";

        $user = User::find($id);
        if(!is_null($user)){
            try {
                $body = "Body content here";
                $text = "$text\n\n$body";
            } catch (\Throwable $th) {
                $text = "$text\n\nError in retrieving user details.";
            }
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->usersListCommand,
            $this->returnLabel
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
        $this->deleteLastMessage();
        return response()->json($result, 200);
    }
}
