<?php
namespace App\Http\Controllers\API;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Classes\Ability;
use App\Classes\Parser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\SmartException;
use App\Exceptions\SmartResponse;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Models\BotAdmins;
use App\Models\BotCacheInputs;
use App\Models\BotCacheSliders;
use App\Models\BotCallbacks;
use App\Models\BotUsers;
use App\Models\BotVisitCounters;
use App\Models\BotMediaCounters;
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

class UserAccountController extends Controller
{
    private $inProduction;
    private $parser;
    private $inlineKeyboard;
    private $onetimeKeyboards;
    private $commandFullname;
    private $addFullnameSteps;
    private $activationPrefix;
    private $data;
    private $chatAction;
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
    private $BotCallbacks;
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

    public function __construct()
    {
        $this->inProduction = app()->isProduction();
        $this->parser = new Parser;
        $this->inlineKeyboard = new InlineKeyboards;
        $this->onetimeKeyboards = new OnetimeKeyboards;
        $this->commandFullname = config('telegram.commands_button.add_fullname.name');
        $this->activationPrefix = config('constants.cache_prefix.activation');
        $this->addFullnameSteps = [
            'activation' => ['name'=>'activation', 'label'=>'Enter Admin Activation key'],
            'activated' => ['name'=>'activation', 'label'=>'Bot is now activated. Kindly complete the following steps to register as the Super Admin'],
            'phonenumber' => ['name'=>'phonenumber', 'label'=>'Enter your Phone Number & send please\nFor example, if your mobile is: 08033344455\nType: 2348033344455\nDo not enter this number. Follow the format and enter yours please.'],
            'firstname' => ['name'=>'firstname', 'label'=>'Enter your First Name & send please'],
            'lastname' => ['name'=>'lastname', 'label'=>'Enter your Last Name & send please'],
            'completed' => ['name'=>'completed', 'label'=>'Thank you for supplying your firstname & lastname. You may proceed to perform available actions by clicking:  /start']
        ];
    }

    public function index()
    {

    }

    public function createSuperAdmin(string $id, string $chatId, string $activationKey = null)
    {
        $res = true;
        $countUsers = BotUsers::count();
        if($countUsers == 0) {
            $text = $this->addFullnameSteps['activation']['label'];
    
            $data = [
                'chat_id' => $chatId,
                'parse_mode' => 'HTML'
            ];

            $getSuperAdminKey = env('ADMIN_ACTIVATION_KEY');
            $prefix = $this->addFullnameSteps['activation']['name'];
            $key = $prefix . "_" . $id;

            $getCacheActivation = $this->parser->cacheGet($key);

            if ( str_starts_with($activationKey, '/')) {
                $activationKey = null;
            }

            if($getSuperAdminKey == $activationKey && !is_null($activationKey)) {
                $key = $this->activationPrefix . "_" . $id;
                $text = $this->addFullnameSteps['activated']['label'];
                $this->parser->cachePut($key, $id);

                $res = true;
            }
            else if(!is_null($activationKey)) {
                $attempts = $getCacheActivation??1;
                if(!is_null($getCacheActivation)) {
                    $attempts = $getCacheActivation + 1;
                }
                $this->parser->cachePut($key, $attempts);
                $text = "Your failed attempts: $attempts \n $text";
            }

            $userData = [
                'text' => $text
            ];

            $content = [
                ...$data,
                ...$userData
            ];

            $result = app('telegram_bot')->sendMessage( $content);
            return response()->json($result, 200);
        }

        return $res;
    }

    public function createNewUser(string $id, string $firstname, mixed $username = null)
    {
        $response = false;
        $key = $this->activationPrefix . "_" . $id;
        $getCacheActivation = $this->parser->cacheGet($key);
        $baseRole = config('constants.user_roles.user');
        $superAdminRole = config('constants.user_roles.superadmin');
        $usersCount = BotUsers::count();

        // $this->parser->log("CACHE: $getCacheActivation");

        if(!is_null($getCacheActivation) || $usersCount > 0) {
        
            $hashId = $this->parser->encoder($id);
    
            // register if user does not exist
            $user = BotUsers::find($hashId);
            if(is_null($user)) {
                $getUsername = !is_null($username)? $username: null;
                $getEmail = $hashId . '@'. env('SESSION_DOMAIN') . '.com';
                if($this->inProduction) {
                    $getEmail = $hashId . '@'. env('SESSION_DOMAIN');
                }
    
                $role = !is_null($getCacheActivation)? $superAdminRole: $baseRole;
    
                try {
                    $save = new BotUsers;
                    $save->id = $hashId;
                    $save->name = $firstname;
                    $save->username = $getUsername;
                    $save->role = $role;
                    $save->email = $getEmail;
    
                    if($save->save()) {
                        try {
                            // add superAdmin to admin table
                            if(!is_null($getCacheActivation)) {
                                $saveAdmin = new BotAdmins;
                                $saveAdmin->id = $save->id;
                                $saveAdmin->approved_by = $save->id;
                                $saveAdmin->approved_at = now();
                                $saveAdmin->save();
                            }
                        } catch (\Throwable $th) {
                            //throw $th;
                        }

                        $saveV = new BotVisitCounters;
                        $saveV->id = $hashId;
                        $saveV->one_time = 1;
                        $saveV->daily = 1;
                        $saveV->monthly = 1;
                        $saveV->yearly = 1;
                        $saveV->last_date = $this->parser->dateNow();
    
                        if($saveV->save()) {
                            if(!is_null($getCacheActivation)) {
                                // clear cache
                                $this->parser->cacheClearAll();
                            }
                            $response = true;
                        }
                    }
                } catch (\Throwable $th) {
                    // Log::error($th);
                }
            }
        }

        return $response;
    }

    public function requestPhoneNumber(string $id, string $chatId)
    {
        $text = config('messages.register_phone');

        $data = [
            'text' => $text,
            'chat_id' => $chatId,
            'parse_mode' => 'HTML'
        ];

        // check if user phone is registered
        $user = $this->info($id);
        $isRegistered = false;

        try {
            if(!is_null($user->phone)) {
                $isRegistered = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        if(!$isRegistered) {
            $keyboardBuilder = $this->onetimeKeyboards->requestContactkeyboard();
            $userData = [
                'reply_markup' => $keyboardBuilder
            ];
        }
        else {
            $text = config('messages.already_registered');
            $userData = [
                'text' => $text,
            ];
        }

        $content = [
            ...$data,
            ...$userData
        ];

        $result = app('telegram_bot')->sendMessage( $content);
        return response()->json($result, 200);
    }

    public function registerPhoneNumber(string $id, string $chatId, mixed $phone = null)
    {
        $text = config('messages.already_registered');
        $hashId = $this->parser->encoder($id);

        $data = [
            'chat_id' => $chatId,
            'parse_mode' => 'HTML'
        ];
        $userData = [
            'text' => $text,
        ];

        // check if user phone is registered
        $user = $this->info($id);
        $isPhoneRegistered = false;

        try {
            if(!is_null($user->phone)) {
                $isPhoneRegistered = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        if(!$isPhoneRegistered) {
            $command = $this->addFullnameSteps['phonenumber']['name'];
            if(is_null($phone)) {
                // request manual entry of phonenumber
                $text = "Auto phone number request was not successful.\n";
                $addText = $this->addFullnameSteps['phonenumber']['label'];
                $text = "$text \n\n $addText";
    
                $userData = [
                    'text' => $text,
                ];
            }
            else {
                $dbdata = [
                    'phone' => $phone
                ];
                $user->update($dbdata);
                
                $command = $this->addFullnameSteps['firstname']['name'];
                $text = config('messages.registered_success');
                $addText = $this->addFullnameSteps['firstname']['label'];
                $text = "$text \n\n $addText";
                $keyboardBuilder = $this->onetimeKeyboards->removeOnetimekeyboard();            
                $userData = [
                    'text' => $text,
                    'reply_markup' => $keyboardBuilder,
                ];
            }
            
            $this->setInput($id, $this->commandFullname, $this->addFullnameSteps, $command);
        }
        else {
            $userData = [
                'text' => $text,
            ];
        }

        // initiate saving fullname
        // $this->parser->log("TEXT1: $this->commandFullname");
        // if(is_null($user->firstname) || is_null($user->lastname)) {
        //     $addText = $this->addFullnameSteps['firstname']['label'];
        //     $text = "$text \n\n $addText";
        //     $this->setInput($id, $this->commandFullname, $this->addFullnameSteps, $this->addFullnameSteps['firstname']['name']);
            
        //     $keyboardBuilder = $this->onetimeKeyboards->removeOnetimekeyboard();
        //     $userData = [
        //         'text' => $text,
        //         'reply_markup' => $keyboardBuilder,
        //     ];
        //     $this->parser->log("TEXT2: $text");
        // }

        $content = [
            ...$data,
            ...$userData
        ];

        $result = app('telegram_bot')->sendMessage( $content);
        return response()->json($result, 200);
    }

    public function addFullName(string $id, string $chatId, string $message, $userInput)
    {
        $text = 'Your detail is required.';

        $data = [
            'chat_id' => $chatId,
            'parse_mode' => 'HTML'
        ];

        $userData = [
            'text' => $text,
        ];

        // check if user phone is registered
        $user = $this->info($id);
        $isRegistered = true;
        $steps = null;
        $activeStep = null;

        try {
            if(is_null($user->phone) || is_null($user->firstname) || is_null($user->lastname)) {
                $isRegistered = false;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $steps = $userInput->steps;
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            $activeStep = $userInput->active_step;
        } catch (\Throwable $th) {
            //throw $th;
        }          
        
        if(is_null($activeStep) && is_null($user->phone)) {
            // initiate saving fullname
            $addText = $this->addFullnameSteps['phonenumber']['label'];
            $text = "$text \n\n$addText";
            $this->setInput($id, $this->commandFullname, $steps, $this->addFullnameSteps['phonenumber']['name']);

            $userData = [
                'text' => $text,
            ];
        }
        else if(!empty($message)) {
            // save firstname & lastname
            $validateName = $this->parser->validateSingleName($message);
            $text = is_null($validateName)? "$message is valid": $validateName;

            if($message == '') {
                $text = config('messages.empty_input');
            }
            else if($activeStep == $this->addFullnameSteps['phonenumber']['name'] && is_null($user->phone)) {
                if(!is_null($message)) {     
                    // validate if number 
                    if($this->parser->validateMobileNumber($message)) {
                        $dbdata = [
                            'phone' => $message
                        ];
                        $user->update($dbdata);
                        $this->setInput($id, $this->commandFullname, $steps, $this->addFullnameSteps['firstname']['name']);
                        $text = $this->addFullnameSteps['firstname']['label'];
                    }
                    else {
                        $text = config('messages.reenter_input') . "\nThe response you sent was not a mobile number in format: 234XXXXXXXXXX (13 Digits).\n\nE.g, if your mobile is: 08033344455\nType: 2348033344455";
                    }
                }
                else {
                    $text = config('messages.reenter_input') . "\nYour Phone number please\nE.g if your mobile is: 08033344455\nType: 2348033344455";
                }
            }
            else if($activeStep == $this->addFullnameSteps['firstname']['name'] && is_null($user->firstname)) {
                if(!is_null($message)) {
                    if(is_null($validateName)) {
                        $dbdata = [
                            'firstname' => $message
                        ];
                        $user->update($dbdata);
                        $this->setInput($id, $this->commandFullname, $steps, $this->addFullnameSteps['lastname']['name']);
                        $text = $this->addFullnameSteps['lastname']['label'];
                    }
                    else {
                        $text = config('messages.reenter_input') . "\n$validateName";
                    }
                }
                else {
                    $text = config('messages.reenter_input');
                }
            }
            else if($activeStep == $this->addFullnameSteps['lastname']['name'] && is_null($user->lastname)) {
                if(!is_null($message)) {
                    if(is_null($validateName)) {
                        $dbdata = [
                            'lastname' => $message
                        ];
                        $user->update($dbdata);
                        $this->setInput($id, $this->commandFullname, $steps, $this->addFullnameSteps['completed']['name']);
                        $text = $this->addFullnameSteps['completed']['label'];
                        // clear input
                        $this->clearAllCache($id);
                    }
                    else {
                        $text = config('messages.reenter_input') . "\n$validateName";
                    }
                }
                else {
                    $text = config('messages.reenter_input');
                }
            }
            else if($isRegistered){
                $text = config('messages.already_registered');
            }
            else {
                $text = config('messages.invalid_input');
            }
            
            $userData = [
                'text' => $text,
            ];
        }
        else {
            $userData = [
                'text' => $text,
            ];
        }

        $content = [
            ...$data,
            ...$userData
        ];

        $result = app('telegram_bot')->sendMessage( $content);
        return response()->json($result, 200);
    }

    public function info(mixed $id)
    {
        $user = null;
        try {
            $hashId = $this->parser->encoder($id);
            $user = BotUsers::find($hashId);
            // $user = BotUsers::with(['admin', 'parent',
            //     'visits', 'mediaCounter', 'inputs', 'sliders'])->find($hashId);
        } catch (\Throwable $th) {
            // throw $th;
        }
        return $user;
    }

    public function getInput($userId)
    {
        $input = null;

        try {
            $hashId = $this->parser->encoder($userId);
            $input = BotCacheInputs::find($hashId);
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $input;
    }

    public function setInput($userId, $command = null, $steps = null, $activeStep = null, $replyId = null)
    {
        $res = false;
        
        $dataUpdate = [
            'command' => $command??null,
            'steps' => $steps??null,
            'active_step' => $activeStep??null,
        ];

        try {
            $hashId = $this->parser->encoder($userId);

            // $dataKeys = ['id'];
            // $dataNew = [
            //     'id' => $hashId,
            //     'command' => $command,
            //     'steps' => $steps,
            //     'active_step' => $activeStep
            // ];

            $dataUpdate = [
                'command' => $command??null,
                'steps' => $steps??null,
                'active_step' => $activeStep??null,
            ];

            // BotCacheInputs::upsert($dataNew, $dataKeys, $dataUpdate);
            $inputs = BotCacheInputs::find($hashId);

            if(!is_null($inputs)) {
                $inputs->update($dataUpdate);
            }
            else {
                $inputs = new BotCacheInputs;
                $inputs->id = $hashId;
                $inputs->command = $command??null;
                $inputs->steps = $steps??null;
                $inputs->active_step = $activeStep??null;
                $inputs->save();
            }
        } catch (\Throwable $th) {
            // throw $th;
        }

        return $res;
    }

    public function clearInput($userId)
    {
        $res = false;
        $inputs = $this->getInput($userId);

        try {
            if(!is_null($inputs)) {
                $inputs->delete();
                $res = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function clearCallback($userId)
    {
        $res = false;
        $hashId = $this->parser->encoder($userId);

        try {
            $callback = BotCallbacks::find($hashId);
            if(!is_null($callback)) {
                $callback->delete();
                $res = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function getSlider($userId)
    {
        $input = null;

        try {
            $hashId = $this->parser->encoder($userId);
            $input = BotCacheSliders::find($hashId);
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $input;
    }

    public function setSlider($userId, $label = null, $command = null, $first = null, $previous = null, $active = null, $next = null, $last = null, $info = null)
    {
        $sliders = $this->getSlider($userId);
        $res = false;

        try {
            $hashId = $this->parser->encoder($userId);

            $dataKeys = ['id'];
            $dataNew = [
                'id' => $hashId,
                'label' => $label,
                'command' => $command,
                'first_step' => $first,
                'previous_step' => $previous,
                'next_step' => $next,
                'last_step' => $last,
                'active_step' => $active,
                'steps_info' => $info
            ];

            $dataUpdate = [
                'label' => $label??null,
                'command' => $command??null,
                'first_step' => $first??null,
                'previous_step' => $previous??null,
                'active_step' => $active??null,
                'next_step' => $next??null,
                'last_step' => $last??null,
                'steps_info' => $info??null
            ];

            BotCacheSliders::upsert($dataNew, $dataKeys, $dataUpdate);
        } catch (\Throwable $th) {
            // Log::error($th);
        }

        return $res;
    }

    public function clearSlider($userId)
    {
        $res = false;
        $sliders = $this->getSlider($userId);

        try {
            if(!is_null($sliders)) {
                $sliders->delete();
                $res = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function getCallback($userId)
    {
        $input = null;

        try {
            $hashId = $this->parser->encoder($userId);
            $input = BotCallbacks::find($hashId);
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $input; 
    }

    public function setCallback($userId, $replyId, $type = 'text')
    {
        $BotCallbacks = $this->getCallback($userId);
        $res = false;

        try {
            $hashId = $this->parser->encoder($userId);

            $dataKeys = ['id'];
            $dataNew = [
                'id' => $hashId,
                'reply_id' => $replyId,
                'type' => $type
            ];

            $dataUpdate = [
                'reply_id' => $replyId??null,
                'type' => $type??null
            ];

            BotCallbacks::upsert($dataNew, $dataKeys, $dataUpdate);

            if(!is_null($BotCallbacks->type)) {
                $res = true;
            }
        } catch (\Throwable $th) {
            // Log::error($th);
        }

        return $res;
    }

    public function clearAllCache($userId) {
        $resI = $this->clearInput($userId);
        $resS = $this->clearSlider($userId);
        $resC = $this->clearCallback($userId);
        $res = [
            'input' => $resI,
            'slider' => $resS,
            'callback' => $resC
        ];
    }

    public function updateTotalVisits($userId)
    {
        try {
            $hashId = $this->parser->encoder($userId);
            $visits = BotVisitCounters::find($hashId);

            if(!is_null($visits)) {
                $daily = $visits->daily;
                $monthly = $visits->monthly;
                $yearly = $visits->yearly;
                $lastDate = $visits->last_date;
                $today = $this->parser->dateNow();

                if($this->parser->diffHours($lastDate) > 24) {
                    $daily = $daily + 1;
                }

                if($this->parser->diffDays($lastDate) > 30) {
                    $monthly = $monthly + 1;
                }

                if($this->parser->diffDays($lastDate) > 365) {
                    $yearly = $yearly + 1;
                }


                $data = [
                    'daily' => $daily,
                    'monthly' => $monthly,
                    'yearly' => $yearly,
                    'last_date' => $today
                ];

                $visits->update($data);
            }
        } catch (\Throwable $th) {
            Log::error($th);
        }
    }

    public function updateMediaCounter($userId, $type = 'photo')
    {
        try {
            $hashId = $this->parser->encoder($userId);
            $counter = BotMediaCounters::find($hashId);

            if(!is_null($counter)) {
                $lastDate = $counter->last_date;
                $today = $this->parser->dateNow();

                $mediaVal = [
                    'text' => 0
                ];

                switch ($type) {
                    case 'text':
                        $media = $counter->text;
                        if($this->parser->diffHours($lastDate) > 24 || $media == 0) {
                            $media = $media + 1;
                        }
                        $mediaVal = [
                            'text' => $media
                        ];
                        break;
                    case 'audio':
                        $media = $counter->audio;
                        if($this->parser->diffHours($lastDate) > 24 || $media == 0) {
                            $media = $media + 1;
                        }
                        $mediaVal = [
                            'audio' => $media
                        ];
                        break;
                    case 'video':
                        $media = $counter->video;
                        if($this->parser->diffHours($lastDate) > 24 || $media == 0) {
                            $media = $media + 1;
                        }
                        $mediaVal = [
                            'video' => $media
                        ];
                        break;
                    case 'document':
                        $media = $counter->document;
                        if($this->parser->diffHours($lastDate) > 24 || $media == 0) {
                            $media = $media + 1;
                        }
                        $mediaVal = [
                            'document' => $media
                        ];
                        break;
                    
                    default:
                        $media = $counter->photo;
                        if($this->parser->diffHours($lastDate) > 24 || $media == 0) {
                            $media = $media + 1;
                        }
                        $mediaVal = [
                            'photo' => $media
                        ];
                        break;
                }

                $data = [
                    ...$mediaVal,
                    'last_date' => $today
                ];

                $counter->update($data);
            }
        } catch (\Throwable $th) {
            Log::error($th);
        }
    }
}
