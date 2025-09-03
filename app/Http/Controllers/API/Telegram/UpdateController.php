<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\MediaHandler;
use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\BotCallbacks;
use App\Models\BotCandidates;
use App\Models\BotMedia;
use App\Models\BotMediaDetail;
use App\Models\BotSettingsAuths;
use App\Models\BotUsers;
use Illuminate\Support\Facades\DB;

class UpdateController
{
    private $userAccount;
    private $parser;
    private $mediaHandler;
    private $inlineKeyboard;
    private $onetimeKeyboard;
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
    private $messageText;
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
    private $introCommand;
    private $usersCommand;
    private $exitCommand;
    private $exitLabel;
    private $panelCommand;
    private $updateOwnCommand;
    private $updateChildCommand;
    private $updateOtherCommand;
    private $updateTeacherPhotoCommand;

    public function __construct($data, $user = null, $inputData = null, $sliderData = null)
    {
        $text = 'I am so glad you are here.';

        $this->data = $data;
        $this->parser = new Parser;
        $this->mediaHandler = new MediaHandler;
        $this->userAccount = new UserAccountController;
        $this->onetimeKeyboard = new OnetimeKeyboards;
        $this->inlineKeyboard = new InlineKeyboards;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageText = $this->data['message-text'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);
        $this->usersPerView = config('constants.users_per_view');
        $this->chatAction = config('telegram.chatactions.text');

        $this->typeCommand = config('telegram.commands_button.update.name');
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
        $this->exitCommand = config('telegram.commands_button.exit.name');
        $this->exitLabel = config('telegram.commands_button.exit.label');

        $this->panelCommand = config('telegram.commands_button.update.name');
        $this->updateOwnCommand = config('telegram.commands_button.update_own.name');
        $this->updateChildCommand = config('telegram.commands_button.update_child.name');
        $this->updateOtherCommand = config('telegram.commands_button.update_other.name');
        $this->updateTeacherPhotoCommand = config('telegram.commands_button.update_teacher_photo.name');

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

    private function teacherUploadAuthorization($mobile, $auth = null, $message = null, $forced = false)
    {
        $userAuth = false;
        $getKey = config('constants.bot_settings_auths.teacher_upload_auths');
        $settingsAuths = BotSettingsAuths::where('label', $getKey)->first();
        
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
        
        try {
            if(!is_null($settingsAuths)) {
                $getAuths = $settingsAuths->auths;
                $getUsers = $settingsAuths->users;
                if(is_null($auth)) {
                    // verify mobile only
                    if(in_array($mobile, $getUsers)) {
                        $userAuth = true;
                    }
                }
                else {
                    if(in_array($auth, $getAuths)) {
                        $userAuth = true;
                    }
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            // $this->parser->log($th);
        }

        if(!$userAuth && $forced) {
            $this->unauthorized($message);
            exit();
        }

        return $userAuth;
    }

    public function index()
    {
        $command = trim($this->messageCommandText);

        $getInput = $this->userAccount->getInput($this->userId);

        $this->moreHandler($command);

        if($this->parser->isTelegramMatch($command, $this->panelCommand, true)) {
            $this->userAccount->clearAllCache($this->userId);
            return $this->updatePanel($command);
        }
        else if(!is_null($command) && !is_null($getInput)) {
            try {
                $getActiveStep = $getInput->active_step;
                $getSteps = $getInput->steps;

                if(!is_null($getActiveStep)) {
                    return $this->saveDataPanel($command, $getActiveStep, $getSteps);
                }
                else {
                    return $this->updateProcess($command, $getSteps);
                }
            } catch (\Throwable $th) {
                return $this->updateProcess($command, null);
            }
        }
        else {
            $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard($this->panelCommand, $this->returnLabel);
    
            $data = [
                'text' => "You need to choose an action to perform before you enter any text",
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

    private function moreHandler($command)
    {
        if(!is_null($command)) {
            // actions: view, edit, delete, stat
            $commandText = str_replace('/', '', $command);

            if($this->parser->isTelegramMatch($commandText, $this->viewCommand)) {
                $getId = str_replace($this->viewCommand . '_', '', $commandText);
                // return $this->viewUser($getId);
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

    private function deleteLastMessage()
    {
        // remove last command
        $this->content['message_id'] = $this->replyToMessageId;
        app('telegram_bot')->deleteMessage($this->content);
    }

    public function newUser()
    {
        $isRegistered = $this->userAccount->createNewUser($this->userId, $this->userFirstname, $this->userUsername);

        $text = config('messages.intro_new_user');

        $keyboardBuilder = $this->inlineKeyboard->requestPhoneNmberInlinekeyboard();

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

    public function regularUser()
    {
        $text = config('messages.intro_regular_user');

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

        $result = $this->getResponseText();
        return response()->json($result, 200);
    }

    public function updatePanel()
    {
        $text = config('messages.update_intro');
        
        $this->userAccount->setInput($this->userId, $this->typeCommand);

        $keyboardBuilder = $this->inlineKeyboard->updateInlineKeyboard();

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

    public function updateProcess($command, $steps)
    {
        $text = "...processing";
        $sub = "Example: 'ADN/J/0000' or 'ADN/S/0000'";
        $activeCmd = null;
        $getSteps = $steps;

        if(is_null($getSteps)) {
            $getSteps = [
                'school' => null,
                'class' => null,
                'reg' => null
            ];
        }
        
        if($this->parser->isTelegramMatch($command, $this->updateOwnCommand, true)) {
            $text = "Kindly type your Admission Number using Own device and send";
            $activeCmd = $this->updateOwnCommand;
        }
        else if ($this->parser->isTelegramMatch($command, $this->updateChildCommand, true)) {
            $text = "Kindly type Admission Number using parent's device and send";
            $activeCmd = $this->updateChildCommand;
        }
        else if ($this->parser->isTelegramMatch($command, $this->updateOtherCommand, true)) {
            $text = "Kindly type your Admission Number using friend's device and send";
            $activeCmd = $this->updateOtherCommand;
        }
        else if ($this->parser->isTelegramMatch($command, $this->updateTeacherPhotoCommand, true)) {
            $text = "Kindly enter the authorization code to upload photo as a Teacher for school magazine";
            $sub = "If you do not have it, please contact the Admin";
            $getSteps = [
                'auth' => null,
                'first' => null,
                'last' => null,
                'photo' => null
            ];
            
            $user = BotUsers::find($this->userHashId);
            $getMobile = $user->phone;
            $auth = $this->teacherUploadAuthorization($getMobile);
            if($auth) {
                $getSteps['auth'] = $getMobile;
                $text = "Type your First name in the text field please";
                $sub = "[you are identified as authenticated User]";
            }
            $activeCmd = $this->updateTeacherPhotoCommand;
        }
        else {
            $text = "Something went wrong!";
        }

        $this->userAccount->setInput($this->userId, $this->typeCommand, $getSteps, $activeCmd);

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard($this->panelCommand, $this->returnLabel);

        $text = "$text\n\n$sub";
        
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

    public function saveDataPanel($command, $getActiveStep, $steps)
    {        
        if($this->parser->isTelegramMatch($getActiveStep, $this->updateOwnCommand, true)) {
            $msg = "Processing admin number on Own device";
            return $this->updateAdminNumber($command, $steps, $msg, $this->updateOwnCommand);
        }
        else if ($this->parser->isTelegramMatch($getActiveStep, $this->updateChildCommand, true)) {
            $msg = "Processing admin number on Parent's device";
            return $this->updateAdminNumber($command, $steps, $msg, $this->updateChildCommand);
        }
        else if ($this->parser->isTelegramMatch($getActiveStep, $this->updateOtherCommand, true)) {
            $msg = "Processing your Admin number on other device";
            return $this->updateAdminNumber($command, $steps, $msg, $this->updateOtherCommand);
        }
        else if ($this->parser->isTelegramMatch($getActiveStep, $this->updateTeacherPhotoCommand, true)) {
            $msg = "Processing photo upload for Teacher";
            return $this->updateTeacherPhoto($command, $steps);
        }
    }

    public function updateAdminNumber($command, $steps, $msg, $activeCmd)
    {
        $text = $msg;
        $isClear = false;
        $isSave = false;

        $buttonArray = [
            'first' => [
                'name' => $this->panelCommand,
                'label' => $this->returnLabel
            ],
            'return' => [
                'name' => $this->exitCommand,
                'label' => $this->exitLabel
            ],
        ];

        $returnButton =  [
            'name' => $this->panelCommand,
            'label' => $this->returnLabel
        ];

        $keyboardBuilder = $this->inlineKeyboard->twoButtonsInlinekeyboard($buttonArray);

        $getSchoolId = null;
        $getClassId = null;
        $getReg = null;

        try {
            $getSchoolId = $steps['school'];
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $getClassId = $steps['class'];
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $getReg = $steps['reg'];
        } catch (\Throwable $th) {
            //throw $th;
        }

        $getSteps = [
            'school' => $getSchoolId,
            'class' => $getClassId,
            'reg' => $getReg
        ];


        if(is_null($getReg) && is_null($getSchoolId) && is_null($getClassId)) {
            // verify if user is already with reg no
            $getUserReg = BotCandidates::find($this->userHashId);

            if(is_null($getUserReg)) {
                // verify if reg exists & save
                $regs = DB::table('school_regs')->where('reg_no', $command)->get()->toArray();
                $getRegs = $this->parser->collectionToArray($regs);
    
                // test the format
                $validFormat = false;
                if(str_starts_with($command, 'ADN/J/') || str_starts_with($command, 'ADN/S/') || str_starts_with($command, 'ADN/N/')){
                    $validFormat = true;
                }
    
                if(str_contains($command, '"') || str_contains($command, "'")){
                    $text = "$command contains forbidden character: apostrophe";
                }
                else if(!$validFormat){
                    $text = "$command did not follow the accepted admin number format: ADN/J/, or ADN/S/, or ADN/N/";
                }
                else if(count($getRegs??[]) == 0) {
                    $schools = DB::table('schools')->select('id', 'label')->get()->toArray();
                    $schoolArr = $this->parser->collectionToArray($schools);
    
                    if(count($schoolArr) > 0) {
                        $buttonArray = [];
                        foreach ($schoolArr as $sch) {
                            $getRow = [
                                'name' => $sch['id'],
                                'label' => $sch['label']
                            ];
                            array_push($buttonArray, $getRow);
                        }
                        
                        $getSteps['reg'] = $command;
                        $text = "Choose your school";
                        $isSave = true;
                        $keyboardBuilder = $this->inlineKeyboard->multiButtonsInlinekeyboard($buttonArray, $returnButton);
                    }
                    else {
                        $text = "No record of schools was found!";
                    }
                }
                else {
                    $text = "Admission number: $command, already exist";
                }
            }
            else {
                $text = "You may not add another admission number to yourself, since $getUserReg->reg is already registered for you.";
            }
        }
        else if(!is_null($getReg) && is_null($getSchoolId) && is_null($getClassId)) {  
            // save school, display class list
            $classes = DB::table('school_classes')->where('school_id', $command)->select('id','label')->get()->toArray();
            $classArr = $this->parser->collectionToArray($classes);

            if(count($classArr) > 0) {
                $buttonArray = [];
                foreach ($classArr as $clss) {
                    $getRow = [
                        'name' => $clss['id'],
                        'label' => $clss['label']
                    ];
                    array_push($buttonArray, $getRow);
                }

                $getSteps['school'] = $command;
                $text = "Choose your class";
                $isSave = true;
                $keyboardBuilder = $this->inlineKeyboard->multiButtonsInlinekeyboard($buttonArray, $returnButton);
            }
            else {
                $text = "No record of classes was found!";
            }
        }
        else if(!is_null($getReg) && !is_null($getSchoolId) && is_null($getClassId)) {  
            // create reg record
            $getSteps['class'] = $command;
            $text = "Admission number has been activated. You may now register on SAAC CBT site: https://creat.i.ng/cbt/signup/candidate";

            $dataReg = [
                'reg_no' => $getReg,
                'class_id' => $command,
                'created_at' => now(),
                'updated_at' => now()
            ];
            $created = DB::table('school_regs')->insertGetId($dataReg);
            if(!is_null($created)) {
                if($activeCmd == $this->updateOwnCommand) {
                    $err = $this->saveRegInfoToOwn($getReg, $created);
                    $text = !is_null($err)? $err: $text;
                }
                else if($activeCmd == $this->updateChildCommand) {
                    $this->saveRegInfoToParent($getReg);
                }
                $isClear = true;
            }
        }

        if($isClear) {
            $this->userAccount->clearInput($this->userId);
        }
        else {
            if($isSave) {
                $this->userAccount->setInput($this->userId, $this->typeCommand, $getSteps, $activeCmd);
            }
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

    private function saveRegInfoToOwn($reg, $id)
    {
        $err = null;

        try {
            $getData = DB::table('bot_candidates')->where('id', $this->userHashId)->get()->toArray();
            $regArr = $this->parser->collectionToArray($getData);

            if(count($regArr??[]) > 0) {
                $regSaved = $regArr[0]['reg'];
                $updatedAt = $regArr[0]['updated_at'];
                $dateStr = $this->parser->diffHumans($updatedAt);
                $err = "You have previously saved $regSaved, $dateStr";
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $dataReg = [
                'id' => $this->userHashId,
                'reg' => $reg,
                'report_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            $created = DB::table('bot_candidates')->insertGetId($dataReg);
        } catch (\Throwable $th) {
            //throw $th;
            $err = "$err. \nYou cannot add another admin number $reg.";
            
            // delete reg no
            $deleted = DB::table('school_regs')->where('id', $id)->delete();
        }
        return $err;
    }

    private function saveRegInfoToParent($reg)
    {
        $getRegs = [];
        $regs = DB::table('bot_parents')->where('id', $this->userHashId)->select('regs')->get()->toArray();
        try {
            $regArr = $this->parser->collectionToArray($regs);

            try {
                $regItem = $regArr[0]['regs'];
                $regDecode = json_decode($regItem);
                if(!in_array($reg, $regDecode)) {
                    $getRegs = [...$regDecode];
                    array_push($getRegs, $reg);
                }
            } catch (\Throwable $th) {
                array_push($getRegs, $reg);
            }
            
            $update = [
                'id' => $this->userHashId,
                'regs' => json_encode($getRegs),
                'report_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
            $ids = ['id'];
            $updatable = ['regs'];
            $created = DB::table('bot_parents')->upsert($update, $ids, $updatable);
        } catch (\Throwable $th) {
            //throw $th;
            // $this->parser->log($th);
        }
    }

    public function updateTeacherPhoto($command, $steps)
    {
        $text = "...processing";
        $activeCmd = $this->updateTeacherPhotoCommand;
        $getSteps = $steps;
        $isClear = false;

        $user = BotUsers::find($this->userHashId);
        $getMobile = $user->phone;

        if(is_null($getSteps)) {
            $getSteps = [
                'auth' => null,
                'first' => null,
                'last' => null,
                'photo' => null
            ];
        }
        $auth = $getSteps['auth'];
        $firstname = $getSteps['first'];
        $lastname = $getSteps['last'];
        $photo = $getSteps['photo'];

        if(is_null($auth) && is_null($firstname) && is_null($lastname) && is_null($photo)) {
            // confirm auth code
            $getCmd = trim($command);
            $auth = $this->teacherUploadAuthorization($getMobile, $getCmd);
            if($auth) {
                $text = "Type your First name in the text field please";
                $getSteps['auth'] = trim($command);
            }
            else {
                $text = "Authorization code: '$getCmd' is incorrect!\nType the correct code in the text field please.\n\nYou may contact the Admin, if you do not have it";
            }
        }
        else if((!is_null($auth) && is_null($firstname) && is_null($lastname) && is_null($photo)) || 
            ($getMobile == $auth && is_null($firstname) && is_null($lastname) && is_null($photo))) {
            $text = "Type your Last name in the text field please";
            $getSteps['first'] = trim($command);
            $getSteps['auth'] = trim($command);
        }
        else if(!is_null($auth) && !is_null($firstname) && is_null($lastname) && is_null($photo)) {
            $text = "Kindly attach your photo you wish to submit for school magazine";
            $getSteps['last'] = trim($command);
        }
        else if(!is_null($auth) && !is_null($firstname) && !is_null($lastname) && is_null($photo)) {
            $text = "Your photo was not saved due to some network errors. Try again please";
            $photoName = '';
            
            $chatAction = config('telegram.chatactions.photo');
            $this->content['action'] = $chatAction;
            app('telegram_bot')->sendChatAction($this->content);

            try {
                $photo = $this->data['photo'];
                // $caption = $this->data['caption'];
                $caption = "$firstname $lastname";
                $path = null;
                $fileContent = null;
                $disc = config('constants.discs.teachers');
                $telegramUrlFile = config('constants.telegram_file_path');

                if(!is_null($photo)) {
                    try {
                        $photoInfo = $this->parser->telegramPhotoInfo($photo);
                        $photoId = $photoInfo['file_id'];
                        $mime = $photoInfo['mime'];
                        // $localName = $this->parser->generateSpecificId('photo');
                        $getDate = $this->parser->formatDate(now(), $this->parser->format1(), $this->parser->format6d());
                        $localName = "$caption $getDate";
                        $extension = "jpg";
                        $photoName = "$localName.$extension";

                        // get image path
                        try {
                            $check = app('telegram_bot')->getFile($photoId);
                            $path = $this->parser->telegramPhotoPath($check);
                            $getArr = explode('.', $path);
                            $extension = array_pop($getArr);
                            $file_url = $telegramUrlFile.'/'.$path;
                            $fileContent = file_get_contents($file_url);
                        } catch (\Throwable $th) {
                            //throw $th;
                            // $this->parser->log($th);
                        }

                        $localPath = "$localName.$extension";

                        $this->userHashId = $this->parser->encoder($this->userId);
                        $media = new BotMedia;
                        $mediaDetail = new BotMediaDetail;
                        $mediaDetail->label = "upload";
                        $mediaDetail->user_id = $this->userHashId;

                        if($mediaDetail->save()) {
                            $media->file_id = $photoId;
                            $media->unique_id = $photoInfo['unique_id'];
                            $media->size = $photoInfo['file_size'];
                            $media->mime = $mime;
                            $media->name = $caption;
                            $media->path = $path;
                            $media->local_path = $localPath;
                            $media->disc = $disc;
                            $media->media_detail_id = $mediaDetail->id;
    
                            if($media->save()) {
                                if(!is_null($fileContent)) {
                                    $this->mediaHandler->saveMedia($localPath, $fileContent, $disc);
                                    
                                    $text = "Your photo was saved as $photoName. Thank you";
                                    $getSteps['photo'] = trim($command);
                                    $isClear = true;
                                }
                                else {
                                    // delete database records
                                    $mediaDetail->delete();
                                    $media->delete();
                                }
                            }
                        }
                    } catch (\Throwable $th) {
                        $text = "Something went wrong! This photo could not be uploaded.";
                    }

                } else {
                    $text = "Photo was required. You uploaded something else.";
                }
            } catch (\Throwable $th) {
                $text = "We could not find any matching Business in your name to update.";
            }
        }

        if($isClear) {
            $this->userAccount->clearInput($this->userId);
        }
        else {
            $this->userAccount->setInput($this->userId, $this->typeCommand, $getSteps, $activeCmd);
        }

        $keyboardBuilder = $this->inlineKeyboard->oneButtonInlinekeyboard($this->panelCommand, $this->returnLabel);
        
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

