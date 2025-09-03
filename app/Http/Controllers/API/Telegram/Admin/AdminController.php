<?php
namespace App\Http\Controllers\API\Telegram\Admin;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\AdminInlineKeyboards;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\BotAdmins;
use App\Models\BotCacheSliders;
use App\Models\BotCallbacks;
use App\Models\BotCandidates;
use App\Models\BotDialogMessages;
use App\Models\BotDirectMessages;
use App\Models\BotDmResponses;
use App\Models\BotMediaCounters;
use App\Models\BotParents;
use App\Models\BotReviews;
use App\Models\BotSettingsAuths;
use App\Models\BotSettingsSwitch;
use App\Models\BotUsers;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class AdminController
{
    private $parser;
    private $inlineKeyboard;
    private $adminKeyboard;
    private $data;
    private $chatAction;
    private $content;
    private $user;
    private $userId;
    private $userHashId;
    private $userAccount;
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
    private $exitCommand;
    private $exitLabel;
    private $firstBtnCmd;
    private $lastBtnCmd;
    private $nextBtnCmd;
    private $previousBtnCmd;
    private $viewCommand;
    private $editCommand;
    private $deleteCommand;
    private $statCommand;
    private $returnLabel;
    private $acceptCommand;
    private $denyCommand;
    private $adminPanelCommand;
    private $adminUserPanelCommand;
    private $adminUserListCommand;
    private $adminManageCommand;
    private $adminManageAuthCommand;
    private $adminManageSwitchCommand;
    private $adminManageCandidateCommand;
    private $adminManageCandidateActiveCommand;
    private $adminManageCandidateInactiveCommand;
    private $adminManageParentCommand;
    private $adminManageParentActiveCommand;
    private $adminManageParentInactiveCommand;
    private $adminDmPanelCommand;
    private $adminDmUnreadCommand;
    private $adminDmReadCommand;
    private $adminStatsPanelCommand;
    private $adminReviewPanelCommand;
    private $adminReviewInactiveCommand;
    private $adminReviewActiveCommand;
    private $adminBroadcastPanelCommand;
    private $adminPollPanelCommand;

    public function __construct($data, $user = null, $inputData = null, $sliderData = null)
    {
        $text = "The requested action may not exist in the Admin section yet.";
        $this->data = $data;
        $this->user = $user;
        $this->parser = new Parser;
        $this->userAccount = new UserAccountController;
        $this->adminKeyboard = new AdminInlineKeyboards;
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
        $this->usersPerView = config('constants.users_per_view');
        $this->chatAction = config('telegram.chatactions.text');

        $this->typeCommand = config('telegram.admin_commands_button.admin.name');
        $this->inputData = $inputData;
        $this->sliderData = $sliderData;

        $this->firstBtnCmd = config('telegram.commands_button.first.name');
        $this->lastBtnCmd = config('telegram.commands_button.last.name');
        $this->nextBtnCmd = config('telegram.commands_button.next.name');
        $this->previousBtnCmd = config('telegram.commands_button.prev.name');
        
        $this->exitCommand = config('telegram.commands_button.exit.name');
        $this->exitLabel = config('telegram.commands_button.exit.label');
        $this->viewCommand = config('telegram.commands_button.view.name');
        $this->editCommand = config('telegram.commands_button.edit.name');
        $this->acceptCommand = config('telegram.commands_button.accept.name');
        $this->denyCommand = config('telegram.commands_button.deny.name');
        $this->deleteCommand = config('telegram.commands_button.delete.name');
        $this->statCommand = config('telegram.commands_button.stat.name');
        $this->returnLabel = config('telegram.commands_button.return.label');

        
        $this->adminPanelCommand = config('telegram.admin_commands_button.admin.name');
        $this->adminUserPanelCommand = config('telegram.admin_commands_button.admin_user_panel.name');
        $this->adminUserListCommand = config('telegram.admin_commands_button.admin_user_panel_list.name');
        $this->adminManageCommand = config('telegram.admin_commands_button.admin_manage.name');
        $this->adminManageAuthCommand = config('telegram.admin_commands_button.admin_manage_auth.name');
        $this->adminManageSwitchCommand = config('telegram.admin_commands_button.admin_manage_switch.name');
        $this->adminManageCandidateCommand = config('telegram.admin_commands_button.admin_manage_candidate.name');
        
        $this->adminManageCandidateActiveCommand = config('telegram.admin_commands_button.admin_manage_candidate_active.name');
        $this->adminManageCandidateInactiveCommand = config('telegram.admin_commands_button.admin_manage_candidate_inactive.name');
        $this->adminManageParentCommand = config('telegram.admin_commands_button.admin_manage_parent.name');
        $this->adminManageParentActiveCommand = config('telegram.admin_commands_button.admin_manage_parent_active.name');
        $this->adminManageParentInactiveCommand = config('telegram.admin_commands_button.admin_manage_parent_inactive.name');

        $this->adminDmPanelCommand = config('telegram.admin_commands_button.admin_dm_panel.name');
        $this->adminDmUnreadCommand = config('telegram.admin_commands_button.admin_dm_unread.name');
        $this->adminDmReadCommand = config('telegram.admin_commands_button.admin_dm_read.name');
        $this->adminStatsPanelCommand = config('telegram.admin_commands_button.admin_stats_panel.name');
        $this->adminReviewPanelCommand = config('telegram.admin_commands_button.admin_reviews_panel.name');
        $this->adminReviewInactiveCommand = config('telegram.admin_commands_button.admin_reviews_inactive.name');
        $this->adminReviewActiveCommand = config('telegram.admin_commands_button.admin_reviews_active.name');
        $this->adminBroadcastPanelCommand = config('telegram.admin_commands_button.admin_broadcasts_panel.name');

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

    private function failed($info = null)
    {
        $text = config('messages.failed');
        if(!is_null($info)) {
            $text = $info;
        }

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

        $getInput = $this->userAccount->getInput($this->userId);

        $this->moreHandler($command);

        $this->upgradeHandler($command);

        if($this->parser->isTelegramMatch($command, $this->adminManageCommand, true)) {
            return $this->managerPanel($command);
        }
        else if(!is_null($command) && !is_null($getInput)) {
            try {
                $getActiveStep = $getInput->active_step;
                $getSteps = $getInput->steps;

                if($this->parser->isTelegramMatch($getActiveStep, $this->adminManageAuthCommand, true)) {
                    return $this->settingsAuthsProcess($command, $getSteps);
                }
                else if($this->parser->isTelegramMatch($getActiveStep, $this->adminManageSwitchCommand, true)) {
                    return $this->settingsSwitchesProcess($command, $getSteps);
                }
                else {
                    return $this->managerPanel($command);
                }
            } catch (\Throwable $th) {
                return $this->managerPanel($command);
            }
        }
        else if($this->parser->isTelegramMatch($command, $this->adminPanelCommand, true)) {
            return $this->panel();
        }
        else if($this->parser->isTelegramMatch($command, $this->adminUserPanelCommand)) {
            return $this->userPanel($command);
        }
        else if($this->parser->isTelegramMatch($command, $this->adminManageCommand)) {
            return $this->manager($command);
        }
        else if($this->parser->isTelegramMatch($command, $this->adminDmPanelCommand)) {
            return $this->dmPanel($command);
        }
        else if($this->parser->isTelegramMatch($command, $this->adminStatsPanelCommand)) {
            return $this->statsPanel($command);
        }
        else if($this->parser->isTelegramMatch($command, $this->adminReviewPanelCommand)) {
            return $this->reviewMenu($command);
        }
        else if($this->parser->isTelegramMatch($command, $this->adminBroadcastPanelCommand)) {
            return $this->broadcastPanel($command);
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

            else if($this->parser->isTelegramMatch($commandText, $this->statCommand)) {
                $getId = str_replace($this->statCommand . '_', '', $commandText);
                return $this->userStat($getId);
            }
        }
    }

    private function upgradeHandler($command)
    {
        if(!is_null($command)) {
            // actions: accept, deny, delete
            $commandText = str_replace('/', '', $command);
            try {
                $type = $this->sliderData->command;
                
                if($this->parser->isTelegramMatch($type, $this->adminReviewPanelCommand, true)) {
                    // handle review approvals
                
                    if($this->parser->isTelegramMatch($commandText, $this->acceptCommand)) {
                        $getId = str_replace($this->acceptCommand . '_', '', $commandText);
                        return $this->acceptReview($getId);
                    }
                
                    if($this->parser->isTelegramMatch($commandText, $this->denyCommand)) {
                        $getId = str_replace($this->denyCommand . '_', '', $commandText);
                        return $this->denyReview($getId);
                    }
                
                    if($this->parser->isTelegramMatch($commandText, $this->deleteCommand)) {
                        $getId = str_replace($this->deleteCommand . '_', '', $commandText);
                        return $this->deleteReview($getId);
                    }
                }
            } catch (\Throwable $th) {
                //throw $th;
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

    private function userPanel($command)
    {
        if($this->parser->isTelegramMatch($command, $this->adminUserListCommand)) {
            return $this->userList($command);
        }
    }

    private function reviewMenu($command)
    {
        if($this->parser->isTelegramMatch($command, $this->adminReviewActiveCommand)) {
            return $this->manageActiveReviews();
        }
        else if($this->parser->isTelegramMatch($command, $this->adminReviewInactiveCommand)) {
            return $this->manageInactiveReviews();
        }
        else {
            return $this->reviewPanel();
        }
    }

    private function manager($command)
    {
        if($this->parser->isTelegramMatch($command, $this->adminManageAuthCommand, true)) {
            return $this->settingsAuthsPanel();
        }

        else if($this->parser->isTelegramMatch($command, $this->adminManageSwitchCommand, true)) {
            return $this->settingsSwitchesPanel();
        }

        else if($this->parser->isTelegramMatch($command, $this->adminManageCandidateActiveCommand, true)) {
            return $this->manageActiveCandidates();
        }

        else if($this->parser->isTelegramMatch($command, $this->adminManageCandidateInactiveCommand, true)) {
            return $this->manageInactiveCandidates();
        }

        else if($this->parser->isTelegramMatch($command, $this->adminManageParentActiveCommand, true)) {
            return $this->manageActiveParents();
        }

        else if($this->parser->isTelegramMatch($command, $this->adminManageParentInactiveCommand, true)) {
            return $this->manageInactiveParents($command, true);
        }

        else {
            return $this->managerPanel($command);
        }
    }

    private function userList($commandText = null, $isTrashed = false)
    {
        $text = "USER PANEL: view";

        $this->typeCommand = config('telegram.admin_commands_button.admin_user_panel_list.name');
        $this->content['action'] = $this->chatAction;
        app('telegram_bot')->sendChatAction($this->content);

        $command = $this->typeCommand;
        $first = "$command.$this->firstBtnCmd";
        $prev = "$command.$this->previousBtnCmd";
        $next = "$command.$this->nextBtnCmd";
        $last = "$command.$this->lastBtnCmd";
        $isExit = config('telegram.admin_commands_button.admin.name');
        $infoArr = [];
        $usersArr = [];
        $totalusers = BotUsers::count();
        $limit = $this->usersPerView > $totalusers? $totalusers: $this->usersPerView;
        $totaluserslabel = $totalusers > 1? "$totalusers Users": "$totalusers BotUsers";
        $label = "$totaluserslabel (1 - $limit)";
        $activePresent = 0;
        $activeNext = $limit;

        // $sliderData = BotCacheSliders::find($this->userHashId);
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

        $fromItem = $activePresent == 0? 1: ($activePresent > 1? ($activePresent + 1): $activePresent);
        if($totalusers < $activeNext) {
            $endItem = $totalusers;
        }
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

        if($activePresent <= 1) {
            $activePresent = 0;
        }

        $users = BotUsers::orderBy('created_at', 'ASC')->offset($activePresent)->limit($limit)->get();
        $countUsers = count($users);
        if($totalusers < $limit) {
            $users = BotUsers::orderBy('created_at', 'ASC')->offset($activePresent)->get();
        }

        if($totalusers == 0) {
            $text = "No user is registered yet";
            $this->failed($text);
            exit();
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
                $type = [];
                $typeStr = "User";

                if(!is_null($user->parent)) {
                    array_push($type, "Parent");
                }
                if(!is_null($user->candidate)) {
                    array_push($type, "Candidate");
                }
                if(count($type??[]) > 0) {
                    array_push($type, "User");
                    $typeStr = implode(', ', $type);
                }



                // add more commands
                $viewCmd = $this->viewCommand . "_$id";
                $statCmd = $this->statCommand . "_$id";
                $moreCommands = "/$viewCmd &#8202 /$statCmd";
                $sub = "\n<b>$name(ID: $id)</b> ($username)\nType: $typeStr\nName: $firstname $lastname\nPhone: $phone\nRoles: $roles\nJoined: $joinedDate; $diffDate\n$moreCommands\n";

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
        $text = "Details of BotUsers (ID: $id)";

        $user = BotUsers::find($id);
        if(!is_null($user)) {
            $admins = DB::table('admins')->select(['admins.detail'])
                ->where('admins.id', $id);
            $conveners = DB::table('conveners')->select(['conveners.detail'])
                ->where('conveners.id', $id)->whereNotNull('conveners.approved_by')->whereNotNull('conveners.approved_at');
            $guardians = DB::table('guardians')->select(['guardians.detail'])
                ->where('guardians.id', $id)->whereNotNull('guardians.approved_by')->whereNotNull('guardians.approved_at');
            $parents = DB::table('parents')->select(['parents.detail'])
                ->where('parents.id', $id)->whereNotNull('parents.approved_by')->whereNotNull('parents.approved_at');
            
            $userDetailsList = $admins ->
                union($conveners) ->
                union($guardians) ->
                union($parents)
                ->get();

            // extract detail from list
            $userDetails = [];
            try {
                foreach ($userDetailsList as $detail) {
                    $getItem = $detail->detail;
                    $item = "$getItem\n\n";
                    array_push($userDetails, $item);
                }
            } catch (\Throwable $th) {
                $getItem = $userDetailsList->detail;
                array_push($userDetails, $getItem);
            }
            
            $getDetails = implode('', $userDetails);
            $title = "$text";
            $text = !empty(trim($getDetails))? "$title\n\n$getDetails": "$title\n\nUser have no details yet.";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            config('telegram.admin_commands_button.admin_user_panel_list.name'),
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

    private function managerPanel()
    {
        $text = "Admin Panel\n\n<b>Manage Accounts & Bot Settings</b>";
        $this->userAccount->clearInput($this->userId);

        $candidates = BotCandidates::onlyTrashed()->count();
        $parents = BotParents::onlyTrashed()->count();
        $subs = '';
        $subArr = [];

        try {
            $auths = BotSettingsAuths::all();
            if(count($auths??[])) {
                foreach ($auths as $item) {
                    $label = $item->label;
                    $authArr = $item->auths;
                    $authUsers = $item->users;
                    $countAuths = count($authArr??[]);
                    $countUsers = count($authUsers??[]);
                    $mkStr = "$label (Auths: $countAuths, Users: $countUsers)";
                    array_push($subArr, $mkStr);
                }
            }
            else {
                $mkStr = "Settings Auths not setup yet";
                array_push($subArr, $mkStr);
            }
        } catch (\Throwable $th) {
            // throw $th;
        }

        try {
            $switches = BotSettingsSwitch::all();
            if(count($switches??[]) > 0) {
                foreach ($switches as $item) {
                    $label = $item->label;
                    $active = $item->is_active;
                    $toStr = $active?'Active': 'Inactive';
                    $mkStr = "$label is $toStr";
                    array_push($subArr, $mkStr);
                }
            }
            else {
                $mkStr = "Settings Switches not setup yet";
                array_push($subArr, $mkStr);
            }
        } catch (\Throwable $th) {
            // throw $th;
        }
        
        if(count($subArr) > 0) {
            $subs = implode("\n", $subArr);
        }
        $text = "$text\nCandidates: $candidates\nParents: $parents\n\n$subs";

        $keyboardBuilder = $this->inlineKeyboard->adminSettingsInlineKeyboard();

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

    private function settingsAuthsPanel()
    {
        $text = "Settings Auths Panel";
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminManageCommand,
            $this->returnLabel
        );

        $returnButton =  [
            'name' => $this->adminManageCommand,
            'label' => $this->returnLabel
        ];

        $getKeys = config('constants.bot_settings_auths');
        if(count($getKeys??[]) > 0) {
            $buttonArray = [];
            foreach ($getKeys as $item) {
                $label = str_replace('_', ' ', $item);
                $mkArr =  [
                    'name' => $item,
                    'label' => $label
                ];
                array_push($buttonArray, $mkArr);
            }
            $keyboardBuilder = $this->inlineKeyboard->multiButtonsInlinekeyboard($buttonArray, $returnButton);
        }
        
        $this->userAccount->setInput($this->userId, $this->adminManageCommand, null, $this->adminManageAuthCommand);

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

    private function settingsSwitchesPanel()
    {
        $text = "Settings Switches Panel";
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminManageCommand,
            $this->returnLabel
        );

        $returnButton =  [
            'name' => $this->adminManageCommand,
            'label' => $this->returnLabel
        ];

        $getKeys = config('constants.bot_settings_switch');
        if(count($getKeys??[]) > 0) {
            $buttonArray = [];
            foreach ($getKeys as $item) {
                $label = str_replace('_', ' ', $item);
                $mkArr =  [
                    'name' => $item,
                    'label' => $label
                ];
                array_push($buttonArray, $mkArr);
            }
            $keyboardBuilder = $this->inlineKeyboard->multiButtonsInlinekeyboard($buttonArray, $returnButton);
        }
        
        $this->userAccount->setInput($this->userId, $this->adminManageCommand, null, $this->adminManageSwitchCommand);

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

    private function settingsAuthsProcess($message, $steps)
    {
        $text = "Processing Settings Auths...";
        $isClear = false;
        $authAddCode = 'auths';
        $usersAddCode = 'users';
        $usersViewCode = 'display';
        $saveCode = 'save';
        $trashCode = 'trash';
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminManageCommand,
            $this->returnLabel
        );

        $buttonArray = [
            'first' => [
                'name' => $this->adminManageCommand,
                'label' => $this->returnLabel
            ],
            'return' => [
                'name' => $this->exitCommand,
                'label' => $this->exitLabel
            ],
        ];

        $keyboardBuilder = $this->inlineKeyboard->twoButtonsInlinekeyboard($buttonArray);

        $getKeys = array_keys(config('constants.bot_settings_auths'));

        if(is_null($steps)) {
            $steps = [
                'label' => null,
                'type' => null,
                'auths' => [],
                'users' => []
            ];
        }

        $getId = $steps['label'];
        $getLabel = str_replace('_', ' ', $getId);
        $getType = $steps['type'];
        $getAuths = $steps['auths'];
        $getUsers = $steps['users'];
        
        $getSettings = BotSettingsAuths::where('label', $getId)->first();

        if(in_array($message, $getKeys)) {
            $steps['label'] = $message;
            $text = "Kindly enter the keyword to initiate update in settings\n\n$authAddCode: Add Authorization codes\n$usersAddCode: Add allowed Users by mobile no\n$usersViewCode: View saved record\n$saveCode: To save changes\n$trashCode: Delete saved record";
        }
        else if(($message == $authAddCode) && in_array($getId, $getKeys)) {
            // initiate adding to auths            
            $steps['type'] = $authAddCode;
            $text = "Type the code you wish to add to Authorization list for $getLabel";
        }
        else if(($message == $usersAddCode) && in_array($getId, $getKeys)) {
            // initiate adding to users            
            $steps['type'] = $usersAddCode;
            $text = "Type the mobile number (234XXXXXXXXXX) of the User you wish to add to allowed list for $getLabel";
        }
        else if(($message == $usersViewCode) && in_array($getId, $getKeys)) {
            // initiate adding to users            
            $steps['type'] = $usersViewCode;
            $vUsers = '';
            $vAuths = '';
            try {
                $viewAuthsArr = $getSettings->auths;
                $viewUsersArr = $getSettings->users;
                if(count($viewUsersArr??[]) > 0) {
                    $vUsers = implode(", ", $viewUsersArr);
                }
                if(count($viewAuthsArr??[]) > 0) {
                    $vAuths = implode(", ", $viewAuthsArr);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
            $text = "<b>'$getLabel' Display</b>\n<b>Auths:</b> $vAuths\n\n<b>Users:</b> $vUsers";
        }
        else if(($message == $trashCode) && in_array($getId, $getKeys)) {
            // initiate adding to users            
            $steps['type'] = $trashCode;
            $vUsers = 0;
            $vAuths = 0;
            $dateStr = '';
            try {
                $viewAuthsArr = $getSettings->auths;
                $viewUsersArr = $getSettings->users;
                $updatedAt = $getSettings->updated_at;
                $dateStr = $this->parser->diffHumans($updatedAt);
                
                $vUsers = count($viewUsersArr??[]);
                $vAuths = count($viewAuthsArr??[]);
            } catch (\Throwable $th) {
                //throw $th;
            }
            $text = "<b>'$getLabel' Data</b> was deleted. It has\n<b>Auths:</b> $vAuths\n<b>Users:</b> $vUsers\nlast updated $dateStr";
            $getSettings->delete();
        }
        else if(($message == $saveCode) && in_array($getId, $getKeys)) {
            // save data
            $resArr = [];
            $countAuths = count($getAuths??[]);
            $countUsers = count($getUsers??[]);
            if(!is_null($getId) && $countAuths > 0) {
                if(!is_null($getSettings)) {
                    $newAuths = [...$getSettings->auths, ...$getAuths];
                    $newUsers = [...$getSettings->users, ...$getUsers];
                    $settingsData = [
                        'auths' => $newAuths,
                        'users' => $newUsers
                    ];
                    $getSettings->update($settingsData);
                    $text = "Settings for $getLabel was saved";
                }
                else {
                    $saveSettings = new BotSettingsAuths;
                    $saveSettings->label = $getId;
                    $saveSettings->auths = $getAuths;
                    $saveSettings->users = $getUsers;
                    
                    if($saveSettings->save()) {
                        $text = "New Settings for $getLabel was created";
                    }
                    else {
                        $text = "Error! Settings for $getLabel was not saved";
                    }
                }
            }
            else {
                $text = "You must add 1 authorization code at least to effect a change";
            }
            $isClear = true;
        }
        else if($getType == $authAddCode && in_array($getId, $getKeys)) {
            // add to auths
            $text = "Add more code to Authorization list for $getLabel";
            if($message !== $authAddCode && $message !== '') {
                if(!in_array($message, $getSettings->auths??[]) && !in_array($message, $getAuths)) {
                    array_push($steps['auths'], $message);
                }
                else if(in_array($message, $getAuths)) {
                    $text = "$message is already queued for update; add another code to Authorization list for $getLabel";
                }
                else {
                    $text = "$message is already saved; add another code to Authorization list for $getLabel";
                }
            }
        }
        else if($getType == $usersAddCode && in_array($getId, $getKeys)) {
            // add to users
            $text = "Add more User by mobile number (234XXXXXXXXXX) to the allowed list for $getLabel";
            if($message !== $usersAddCode && $message !== '') {
                array_push($steps['users'], $message);
            }
        }

        $this->userAccount->setInput($this->userId, $this->adminManageCommand, $steps, $this->adminManageAuthCommand);

        if($isClear) {
            $this->userAccount->clearInput($this->userId);
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

        $result = $this->getResponseText();
        return response()->json($result, 200);
    }

    private function settingsSwitchesProcess($message, $steps)
    {
        $text = "Processing Settings Switches...";
        $isClear = false;
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminManageCommand,
            $this->returnLabel
        );

        $buttonArray = [
            'first' => [
                'name' => $this->adminManageCommand,
                'label' => $this->returnLabel
            ],
            'return' => [
                'name' => $this->exitCommand,
                'label' => $this->exitLabel
            ],
        ];

        $keyboardBuilder = $this->inlineKeyboard->twoButtonsInlinekeyboard($buttonArray);

        

        if($isClear) {
            $this->userAccount->clearInput($this->userId);
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

        $result = $this->getResponseText();
        return response()->json($result, 200);
    }

    private function manageActiveCandidates()
    {
        $text = "Manage Active Candidates\n\n";

        $candidatesCount = BotCandidates::count();
        $text = "$text\nTotal found: $candidatesCount";
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminManageCandidateCommand,
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
        return response()->json($result, 200);
    }

    private function manageInactiveCandidates()
    {
        $text = "Manage Inactive Candidates\n\n";

        $candidatesCount = BotCandidates::onlyTrashed()->count();
        $text = "$text\nTotal found: $candidatesCount";
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminManageCandidateCommand,
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
        return response()->json($result, 200);
    }

    private function manageActiveParents()
    {
        $text = "Manage Active BotParents\n\n";

        $parentCount = BotParents::count();
        $text = "$text\nTotal found: $parentCount";
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminManageParentCommand,
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
        return response()->json($result, 200);
    }

    private function manageInactiveParents()
    {
        $text = "Manage Inactive BotParents\n";

        $parentCount = BotParents::onlyTrashed()->count();
        $text = "$text\nTotal found: $parentCount";
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminManageParentCommand,
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
        return response()->json($result, 200);
    }

    private function dmPanel()
    {
        $text = "Manage Direct Messages\n\n";

        $dm = BotDirectMessages::count();
        $responses = BotDmResponses::count();
        $text = "$text\nMessages: $dm\nResponses: $responses\n";

        $buttonArray = [
            'first' => [
                'name' => $this->adminDmUnreadCommand,
                'label' => config('telegram.admin_commands_button.admin_dm_unread.label')
            ],
            'second' => [
                'name' => $this->adminDmReadCommand,
                'label' => config('telegram.admin_commands_button.admin_dm_read.label')
            ],
            'return' => [
                'name' => $this->adminPanelCommand,
                'label' => $this->returnLabel
            ],
        ];
        
        $keyboardBuilder = $this->inlineKeyboard->threeButtonsInlinekeyboard($buttonArray);

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

    private function statsPanel()
    {
        $text = "<b>Bot Statistics</b>";
        $subs = [];
        
        $countUsers = BotUsers::count();
        $countAdmins = BotAdmins::count();
        $countCandidates = BotCandidates::count();
        $countParents = BotParents::count();
        $allUsers = "Users: $countUsers\nAdmins: $countAdmins\nCandidates: $countCandidates\nParents: $countParents";
        array_push($subs, $allUsers);

        $media = BotMediaCounters::all();
        $countText = BotMediaCounters::sum('text');
        $countPhotos = BotMediaCounters::sum('photo');
        $countAudios = BotMediaCounters::sum('audio');
        $countVideos = BotMediaCounters::sum('video');
        $countDocs = BotMediaCounters::sum('document');
        $allMedia = "\n\nText files: $countText\nImages: $countPhotos\nAudios: $countAudios\nVideos: $countVideos\nDocuments: $countDocs";
        array_push($subs, $allMedia);

        $countReviews = BotReviews::count();
        $countDm = BotDirectMessages::count();
        $countDmReplies = BotDmResponses::count();
        $countFaq = BotDialogMessages::count();
        $messages = "\n\nReviews: $countReviews\nDM: $countDm\nReplies: $countDmReplies\nFAQ: $countFaq\n";
        array_push($subs, $messages);

        $subsAll = implode('', $subs);
        $text = "$text\n\n$subsAll";

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminPanelCommand,
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
        return response()->json($result, 200);
    }

    private function userStat($id)
    {
        $text = "<b>BotUsers (ID: $id) Statistics</b>";
        $subs = [];

        $user = BotUsers::with(['admin', 'convener', 'guardian', 'parent',
            'visits', 'mediaCounter', 'inputs', 'sliders'])->find($id);

        try {
            if(!is_null($user)) {
                $superAdmin = $user->isSuperAdmin()? 'SuperAdmin .': '';
                $admin = !is_null($user->admin)? 'Admin .': '';
                $convener = !is_null($user->convener)? 'Convener .': '';
                $guardian = !is_null($user->guardian)? 'Guardian .': '';
                $parent = !is_null($user->parent)? 'Parent .': '';
                $roles = "Roles: $superAdmin $admin $convener $guardian $parent";
                array_push($subs, $roles);
    
                $countVisits = count($user->visits??[]);
                $countUploads = count($user->mediaCounter??[]);
                $countInputs = count($user->inputs??[]);
                $countSliders = count($user->sliders??[]);
                $counts = "\n\nVisits: $countVisits\nUploads: $countUploads\nActive Inputs: $countInputs\nActive Sliders: $countSliders";
                array_push($subs, $counts);
    
                $subToString = implode('', $subs);
                $text = "$text\n\n$subToString";
            }
            else {
                $text = config('messages.error_not_found');
            }
        } catch (\Throwable $th) {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminUserListCommand,
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

    private function reviewPanel()
    {
        $text = "Manage BotReviews\n\n";

        $inactive = BotReviews::whereNull('approved_by')->whereNull('approved_at')->count();
        $active = BotReviews::whereNotNull('approved_by')->whereNotNull('approved_at')->count();
        $text = "$text\nPending BotReviews: $inactive\nActive BotReviews: $active\n";

        $buttonArray = [
            'first' => [
                'name' => $this->adminReviewInactiveCommand,
                'label' => config('telegram.admin_commands_button.admin_reviews_inactive.label')
            ],
            'second' => [
                'name' => $this->adminReviewActiveCommand,
                'label' => config('telegram.admin_commands_button.admin_reviews_active.label')
            ],
            'return' => [
                'name' => $this->adminPanelCommand,
                'label' => $this->returnLabel
            ],
        ];
        
        $keyboardBuilder = $this->inlineKeyboard->threeButtonsInlinekeyboard($buttonArray);
        // $this->userAccount->setCallback($this->userId, $this->replyToMessageId);

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

    private function manageActiveReviews()
    {
        $reviewPerView = config('constants.review_per_view');
        $text = "Manage Aproved Bot Reviews ($reviewPerView latest)";
        $sub = [];

        $reviews = BotReviews::whereNotNull('approved_by')->whereNotNull('approved_at')->get();

        if(!is_null($reviews)) {
            foreach ($reviews as $review) {
                $id = $review->id;
                $note = $review->note;
                $reviewer = $review->user;
                $approver = $review->approvedBy;
                $approvedAt = $review->approved_at;
                $createdAt = $review->created_at;
                $dateStr = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6b());
                $dateDiff = $this->parser->diffHumans($createdAt);

                $reviewerName = "$reviewer->firstname $reviewer->lastname";
                
                $approvedStr = $this->parser->formatDate($approvedAt, $this->parser->format1(), $this->parser->format6b());
                $approvedDiff = $this->parser->diffHumans($approvedAt);
                $approverName = "$approver->firstname $approver->lastname ($approvedDiff)";

                $updateStr = "/$this->denyCommand" . "_$id";
                $deleteStr = "/$this->deleteCommand" . "_$id";
                $mksub = "<b>By $reviewerName</b>\nat $dateStr ($dateDiff)\nApproved by $approverName\n<blockquote>$note</blockquote>\n$updateStr  $deleteStr";
                array_push($sub, $mksub);
            }
        }

        $getSub = "No Review found!";
        $countReviews = count($sub??[]);
        if($countReviews > 0) {
            $getSub = implode("\n\n", $sub);
            $this->userAccount->setSlider($this->userId, "Reviews: $countReviews", $this->adminReviewPanelCommand);
        }
        $text = "$text\n\n$getSub";
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminReviewPanelCommand,
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
        return response()->json($result, 200);
    }

    private function manageInactiveReviews()
    {
        $reviewPerView = config('constants.review_per_view');
        $text = "Manage Pending Bot Reviews ($reviewPerView latest)";
        $sub = [];

        $reviews = BotReviews::whereNull('approved_by')->whereNull('approved_at')->get();

        if(!is_null($reviews)) {
            foreach ($reviews as $review) {
                $id = $review->id;
                $note = $review->note;
                $reviewer = $review->user;
                $createdAt = $review->created_at;
                $dateStr = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6b());
                $dateDiff = $this->parser->diffHumans($createdAt);

                $reviewerName = "$reviewer->firstname $reviewer->lastname";

                $updateStr = "/$this->acceptCommand" . "_$id";
                $deleteStr = "/$this->deleteCommand" . "_$id";
                $mksub = "<b>By $reviewerName</b>\nsent on $dateStr ($dateDiff)\n<blockquote>$note</blockquote>\n$updateStr  $deleteStr";
                array_push($sub, $mksub);
            }
        }

        $getSub = "No Review found!";
        $countReviews = count($sub??[]);
        if($countReviews > 0) {
            $getSub = implode("\n\n", $sub);
            $this->userAccount->setSlider($this->userId, "Reviews: $countReviews", $this->adminReviewPanelCommand);
        }
        $text = "$text\n\n$getSub";
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminReviewPanelCommand,
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
        return response()->json($result, 200);
    }

    private function acceptReview($id)
    {
        $text = "<b>Review Approval</b>";

        $review = BotReviews::find($id);

        try {
            if(!is_null($review)) {
                $id = $review->id;
                $note = $review->note;
                $reviewer = $review->user;
                $approver = $review->approved_by;
                $approvedAt = $review->approved_at;
                $createdAt = $review->created_at;
                $dateStr = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6b());
                $dateDiff = $this->parser->diffHumans($createdAt);
                $reviewerName = "$reviewer->firstname $reviewer->lastname";

                try {
                    $approveStr = $this->parser->formatDate($approvedAt, $this->parser->format1(), $this->parser->format6b());
                    $approveDiff = $this->parser->diffHumans($approvedAt);
                    $text = "Requested review has been approved on $approveStr ($approveDiff)\nCreated by $reviewerName on $dateStr ($dateDiff)<blockquote>$note</blockquote>";
                } catch (\Throwable $th) {
                    $reviewData = [
                        'approved_at' => now(),
                        'approved_by' => $this->userHashId
                    ];
                    $review->update($reviewData);
                    $text = "You approved \n<b>Review by $reviewerName</b>\ncreated on $dateStr ($dateDiff)<blockquote>$note</blockquote>";
                }
            }
            else {
                $text = config('messages.error_not_found');
            }
        } catch (\Throwable $th) {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminReviewPanelCommand,
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
        return response()->json($result, 200);
    }

    private function denyReview($id)
    {
        $text = "<b>Review Rejection</b>";

        $review = BotReviews::find($id);

        try {
            if(!is_null($review)) {
                $id = $review->id;
                $note = $review->note;
                $reviewer = $review->user;
                $approver = $review->approved_by;
                $approvedAt = $review->approved_at;
                $createdAt = $review->created_at;
                $dateStr = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6b());
                $dateDiff = $this->parser->diffHumans($createdAt);
                $reviewerName = "$reviewer->firstname $reviewer->lastname";

                if(!is_null($approvedAt)) {    
                    $reviewerName = "$reviewer->firstname $reviewer->lastname";
    
                    $reviewData = [
                        'approved_at' => null,
                        'approved_by' => null
                    ];
                    $review->update($reviewData);
                    $text = "You unapproved\n<b>Review by $reviewerName</b>\ncreated on $dateStr ($dateDiff)<blockquote>$note</blockquote>";
                }
                else {
                    $text = "Requested review has not been approved.\nCreated on $dateStr ($dateDiff)<blockquote>$note</blockquote>";
                }
            }
            else {
                $text = config('messages.error_not_found');
            }
        } catch (\Throwable $th) {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminReviewPanelCommand,
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
        return response()->json($result, 200);
    }

    private function deleteReview($id)
    {
        $text = "<b>Review Removal</b>";

        $review = BotReviews::find($id);
        if(!is_null($review)) {
            $id = $review->id;
            $note = $review->note;
            $reviewer = $review->user;
            $createdAt = $review->created_at;
            $dateStr = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6b());
            $dateDiff = $this->parser->diffHumans($createdAt);
            $reviewerName = "$reviewer->firstname $reviewer->lastname";
            $review->delete();
            $text = "<b>You deleted Review by $reviewerName\ncreated on $dateStr ($dateDiff)<blockquote>$note</blockquote></b>";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->adminReviewPanelCommand,
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
        return response()->json($result, 200);
    }

    private function broadcastPanel($command)
    {
        switch ($command) {
            case config('telegram.admin_commands_button.broadcast_view'):
                $this->broadcastView();
                break;

            default:
                # code...
                break;
        }

        $result = $this->getResponseText();
        return response()->json($result, 200);
    }

    private function dmUnRead()
    {
        $text = "DM PANEL: unread";
        $this->parser->log($text);

        $loadLabel = 'Statistics';
        $loadName = config('telegram.admin_commands_button.stats_view');

        $activeName = config('telegram.admin_commands_button.dm_unread');

        // set cache
        $keyboardBuilder = $this->adminKeyboard->flexibleInlinekeyboard(true, $loadName, $loadLabel);

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
        $loadName = config('telegram.admin_commands_button.waitlist_view');

        $activeName = config('telegram.admin_commands_button.broadcast_view');

        $keyboardBuilder = $this->adminKeyboard->flexibleInlinekeyboard(true, $loadName, $loadLabel);

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

    private function panel()
    {
        $adminText = config('messages.admin_panel');
        $text = sprintf($adminText, $this->userFirstname);

        $keyboardBuilder = $this->adminKeyboard->mainMenuInlinekeyboard();

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

    public function inputHandler($type, $inputObj)
    {
        switch ($type) {
            case config('telegram.admin_commands_button.stop.name'):
                // request exit from active input or slider
                $this->parser->log("ADMIN TYPE: STOP");
                break;
            case config('telegram.admin_commands_button.skip.name'):
                // skip active input or slider, move to next
                $this->parser->log("ADMIN TYPE: SKIP");
                break;
            case config('telegram.admin_commands_button.continue.name'):
                // continue with active input or slider
                $this->parser->log("ADMIN TYPE: CONTINUE");
                break;
            case config('telegram.admin_commands_button.dm_delete'):
                return $this->dmDeleteProcessor();
                break;
            case config('telegram.admin_commands_button.review_delete'):
                return $this->reviewDeleteProcessor();
                break;
            case config('telegram.admin_commands_button.broadcast_new'):
                return $this->broadcastNewProcessor();
                break;
            case config('telegram.admin_commands_button.broadcast_edit'):
                return $this->broadcastEditProcessor();
                break;
            case config('telegram.admin_commands_button.broadcast_delete'):
                return $this->broadcastDeleteProcessor();
                break;

            default:
                # code...
                break;
        }

    }

    private function dmDeleteProcessor()
    {
        $type = config('telegram.admin_commands_button.dm_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function reviewDeleteProcessor()
    {
        $type = config('telegram.admin_commands_button.review_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function broadcastNewProcessor()
    {
        $type = config('telegram.admin_commands_button.broadcast_new');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function broadcastEditProcessor()
    {
        $type = config('telegram.admin_commands_button.broadcast_edit');

        $this->parser->log("ADMIN TYPE: $type");
    }

    private function broadcastDeleteProcessor()
    {
        $type = config('telegram.admin_commands_button.broadcast_delete');

        $this->parser->log("ADMIN TYPE: $type");
    }
}

