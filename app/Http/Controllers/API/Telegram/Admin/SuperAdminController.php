<?php
namespace App\Http\Controllers\API\Telegram\Admin;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\SuperAdminInlineKeyboards;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\BotAdmins;
use App\Models\BotCacheSliders;
use App\Models\BotCallbacks;
use App\Models\BotDialogMessages;
use App\Models\BotDirectMessages;
use App\Models\BotParents;
use App\Models\BotUsers;

class SuperAdminController
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
    private $userFirstname;
    private $userUsername;
    private $chatId;
    private $replyToMessageId;
    private $messageCommandText;
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
    private $editCommand;
    private $deleteCommand;
    private $returnLabel;
    private $banCommand;
    private $unbanCommand;
    private $acceptCommand;
    private $denyCommand;
    private $superAdminPanel;
    private $superAdminUserPanel;
    private $superAdminUserPanelList;
    private $superAdminUserPanelBanned;
    private $superAdminUpgrade;
    private $superAdminUpgradeAdmin;
    private $superAdminUpgradeAdminActive;
    private $superAdminUpgradeAdminInactive;

    public function __construct($data, $user = null, $inputData = null, $sliderData = null)
    {
        $text = "The requested action may not exist in the SuperAdmin section yet.";
        $this->data = $data;
        $this->user = $user;
        $this->parser = new Parser;
        $this->adminKeyboard = new SuperAdminInlineKeyboards;
        $this->inlineKeyboard = new InlineKeyboards;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);
        $this->usersPerView = config('constants.users_per_view');
        $this->chatAction = config('telegram.chatactions.text');

        $this->typeCommand = config('telegram.superadmin_commands_button.superadmin.name');
        $this->inputData = $inputData;
        $this->sliderData = $sliderData;

        $this->firstBtnCmd = config('telegram.commands_button.first.name');
        $this->lastBtnCmd = config('telegram.commands_button.last.name');
        $this->nextBtnCmd = config('telegram.commands_button.next.name');
        $this->previousBtnCmd = config('telegram.commands_button.prev.name');
        
        $this->viewCommand = config('telegram.commands_button.view.name');
        $this->editCommand = config('telegram.commands_button.edit.name');
        $this->deleteCommand = config('telegram.commands_button.delete.name');
        $this->returnLabel = config('telegram.commands_button.return.label');
        $this->banCommand = config('telegram.commands_button.ban.name');
        $this->unbanCommand = config('telegram.commands_button.unban.name');
        $this->acceptCommand = config('telegram.commands_button.accept.name');
        $this->denyCommand = config('telegram.commands_button.deny.name');

        $this->superAdminPanel = config('telegram.superadmin_commands_button.superadmin.name');
        $this->superAdminUserPanel = config('telegram.superadmin_commands_button.superadmin_user_panel.name');
        $this->superAdminUserPanelList = config('telegram.superadmin_commands_button.superadmin_user_panel_list.name');
        $this->superAdminUserPanelBanned = config('telegram.superadmin_commands_button.superadmin_user_panel_banned.name');
        $this->superAdminUpgrade = config('telegram.superadmin_commands_button.superadmin_upgrade.name');
        $this->superAdminUpgradeAdmin = config('telegram.superadmin_commands_button.superadmin_upgrade_admin.name');
        $this->superAdminUpgradeAdminActive = config('telegram.superadmin_commands_button.superadmin_upgrade_admin_active.name');
        $this->superAdminUpgradeAdminInactive = config('telegram.superadmin_commands_button.superadmin_upgrade_admin_inactive.name');

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
            $user = BotUsers::find($this->userHashId);
            $isSuperAdmin = $user->isSuperAdmin();

            if($isSuperAdmin) { // allow super admin only
                $userAuth = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
            // $this->parser->log($th);
        }

        if(!$userAuth) {
            $this->unauthorized();
            exit();
        }
    }

    public function index()
    {
        $command = trim($this->messageCommandText);

        $this->moreHandler($command);

        $this->upgradeHandler($command);

        if($this->parser->isTelegramMatch($command, $this->superAdminPanel, true)) {
            return $this->panel();
        }
        else if($this->parser->isTelegramMatch($command, $this->superAdminUserPanel)) {
            return $this->userPanel($command);
        }
        else if($this->parser->isTelegramMatch($command, $this->superAdminUpgrade)) {
            return $this->upgrade($command);
        }
    }

    private function moreHandler($command)
    {
        if(!is_null($command)) {
            // actions: view, edit, delete
            $commandText = str_replace('/', '', $command);

            if($this->parser->isTelegramMatch($commandText, $this->viewCommand)) {
                $getId = str_replace($this->viewCommand . '_', '', $commandText);
                return $this->viewUser($getId);
            }

            if($this->parser->isTelegramMatch($commandText, $this->editCommand)) {
                $getId = str_replace($this->editCommand . '_', '', $commandText);
                return $this->editUser($getId);
            }

            if($this->parser->isTelegramMatch($commandText, $this->deleteCommand)) {
                $getId = str_replace($this->deleteCommand . '_', '', $commandText);
                return $this->deleteUser($getId);
            }
            
            if($this->parser->isTelegramMatch($commandText, $this->banCommand)) {
                $getId = str_replace($this->banCommand . '_', '', $commandText);
                return $this->banUser($getId);
            }
            
            if($this->parser->isTelegramMatch($commandText, $this->unbanCommand)) {
                $getId = str_replace($this->unbanCommand . '_', '', $commandText);
                return $this->unbanUser($getId);
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
                
                if($this->parser->isTelegramMatch($type, config('telegram.superadmin_commands_button.superadmin_upgrade'), true)) {
                    // handle user account upgrades
                
                    if($this->parser->isTelegramMatch($commandText, $this->acceptCommand)) {
                        $getId = str_replace($this->acceptCommand . '_', '', $commandText);
                        return $this->acceptAdmin($getId);
                    }
                
                    if($this->parser->isTelegramMatch($commandText, $this->denyCommand)) {
                        $getId = str_replace($this->denyCommand . '_', '', $commandText);
                        return $this->denyAdmin($getId);
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

    private function userPanel($command)
    {
        if($this->parser->isTelegramMatch($command, $this->superAdminUserPanelList)) {
            return $this->userList($command);
        }

        if($this->parser->isTelegramMatch($command, $this->superAdminUserPanelBanned)) {
            return $this->userList($command, true);
        }
    }

    private function upgrade($command)
    {
        if($this->parser->isTelegramMatch($command, $this->superAdminUpgradeAdmin, true)) {
            return $this->upgradeAdmins();
        }

        else if($this->parser->isTelegramMatch($command, $this->superAdminUpgradeAdminActive, true)) {
            return $this->manageActiveAdmins();
        }

        else if($this->parser->isTelegramMatch($command, $this->superAdminUpgradeAdminInactive, true)) {
            return $this->manageInactiveAdmins();
        }

        else {
            return $this->upgradePanel($command);
        }
    }

    private function userList($commandText = null, $isTrashed = false)
    {
        $text = "USER PANEL: view";

        $this->typeCommand = $this->superAdminUserPanelList;
        $this->content['action'] = $this->chatAction;
        app('telegram_bot')->sendChatAction($this->content);

        $command = $this->typeCommand;
        $first = "$command.$this->firstBtnCmd";
        $prev = "$command.$this->previousBtnCmd";
        $next = "$command.$this->nextBtnCmd";
        $last = "$command.$this->lastBtnCmd";
        $isExit = $this->superAdminPanel;
        $infoArr = [];
        $usersArr = [];
        $totalusers = $isTrashed? BotUsers::onlyTrashed()->count(): BotUsers::count();
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

            $userAccount = new UserAccountController;
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
        if($isTrashed) {
            $users = BotUsers::onlyTrashed()->orderBy('created_at', 'ASC')->offset($activePresent)->limit($limit)->get();
        }
        $countUsers = count($users);
        if($totalusers < $limit) {
            $users = BotUsers::orderBy('created_at', 'ASC')->offset($activePresent)->get();
        }

        if($totalusers == 0) {
            $text = $isTrashed?"Banned list is empty": "No user is registered yet";
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
                $editCmd = $this->editCommand . "_$id";
                $deleteCmd = $this->deleteCommand . "_$id";
                $banCmd = $isTrashed?$this->unbanCommand: $this->banCommand;
                $banCmd = $banCmd . "_$id";
                $moreCommands = $isTrashed?"/$viewCmd  /$banCmd  /$deleteCmd": "/$viewCmd  /$editCmd /$banCmd /$deleteCmd";
                $sub = "\n<b>$name(ID: $id)</b> ($username)\nName: $firstname $lastname\nPhone: $phone\nRoles: $roles\nJoined: $joinedDate; $diffDate\n$moreCommands\n";

                array_push($usersArr, $sub);
            }
            $text = count($usersArr) > 0?implode('', $usersArr): $text;
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
        $text = "BotUsers details";

        $user = BotUsers::find($id);
        if(!is_null($user)) {
            $text = "<b>Details of BotUsers (ID: $id)</b>";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUserPanelList,
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

    private function editUser($id)
    {
        $text = "Edit BotUsers";

        $user = BotUsers::find($id);
        if(!is_null($user)) {
            $text = "<b>Edit BotUsers (ID: $id)</b>";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUserPanelList,
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

    private function deleteUser($id)
    {
        $text = "Delete BotUsers";

        $user = BotUsers::find($id);
        if(!is_null($user)) {
            $text = "<b>Delete user (ID: $id)</b>";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUserPanelList,
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

    private function banUser($id)
    {
        $text = "Ban BotUsers";

        $user = BotUsers::find($id);
        if(!is_null($user)) {
            $text = "<b>Ban this user (ID: $id)</b>";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUserPanelList,
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

    private function unbanUser($id)
    {
        $text = "Unban BotUsers";

        $user = BotUsers::onlyTrashed()->whereId($id);
        if(!is_null($user)) {
            $text = "<b>Unban user (ID: $id)</b>";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUserPanelList,
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

    private function upgradePanel()
    {
        $text = "Manage Upgrade Requests\n\n<b>Stats of pending</b>";

        $admins = BotAdmins::onlyTrashed()->count();
        $text = "$text\nAdmins: $admins\n";

        $buttonArray = [
            'first' => [
                'name' => $this->superAdminUpgradeAdmin,
                'label' => config('telegram.superadmin_commands_button.superadmin_upgrade_admin.label')
            ],
            'return' => [
                'name' => $this->superAdminPanel,
                'label' => $this->returnLabel
            ],
        ];

        $keyboardBuilder = $this->inlineKeyboard->twoButtonsInlinekeyboard($buttonArray);

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

    private function upgradeAdmins()
    {
        $text = "Manage BotAdmins\n\n";

        $active = BotAdmins::count();
        $inactive = BotAdmins::onlyTrashed()->count();
        $text = "$text\nActive BotAdmins: $active\nInactive / pending BotAdmins: $inactive\n";

        $buttonArray = [
            'first' => [
                'name' => $this->superAdminUpgradeAdminActive,
                'label' => config('telegram.superadmin_commands_button.superadmin_upgrade_admin_active.label')
            ],
            'second' => [
                'name' => $this->superAdminUpgradeAdminInactive,
                'label' => config('telegram.superadmin_commands_button.superadmin_upgrade_admin_inactive.label')
            ],
            'return' => [
                'name' => $this->superAdminUpgrade,
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

    private function manageActiveAdmins()
    {
        $text = "Manage Active BotAdmins\n\n";

        $adminCount = BotAdmins::count();
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUpgradeAdmin,
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

    private function manageInactiveAdmins()
    {
        $text = "Manage Inactive BotAdmins\n\n";

        $adminCount = BotAdmins::onlyTrashed()->count();
        
        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUpgradeAdmin,
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

    private function acceptAdmin($id)
    {
        $text = "<b>Confirm Account Upgrade to Admin</b>";

        $user = BotUsers::find($id);
        if(!is_null($user)) {
            $text = "<b>Details of BotUsers (ID: $id)</b>";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUpgradeAdmin,
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

    private function denyAdmin($id)
    {
        $text = "<b>Deny Account Upgrade to Admin</b>";

        $user = BotUsers::find($id);
        if(!is_null($user)) {
            $text = "<b>Details of BotUsers (ID: $id)</b>";
        }
        else {
            $text = config('messages.error_not_found');
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard(
            $this->superAdminUpgradeAdmin,
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

    private function panel()
    {
        $adminText = config('messages.superadmin_panel');
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
            case config('telegram.superadmin_commands_button.stop.name'):
                // request exit from active input or slider
                $this->parser->log("ADMIN TYPE: STOP");
                break;
            case config('telegram.superadmin_commands_button.skip.name'):
                // skip active input or slider, move to next
                $this->parser->log("ADMIN TYPE: SKIP");
                break;
            case config('telegram.superadmin_commands_button.continue.name'):
                // continue with active input or slider
                $this->parser->log("ADMIN TYPE: CONTINUE");
                break;

            default:
                # code...
                break;
        }

    }
}

