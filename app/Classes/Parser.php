<?php
namespace App\Classes;

use App\Exceptions\SmartException;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use stdClass;

class Parser
{
    private $format1 = 'Y-m-d H:i:s'; // 2023-05-13 01:55:24
    private $format1b = 'd-m-Y'; // 13-05-2023
    private $format1c = 'Y-m-d h:i A'; // 2023-05-13 01:55 AM
    private $format1d = 'h:i A'; // 01:55 AM
    private $format1e = 'Y-m-d'; // 2023-05-13
    private $format2 = 'ymdHis'; // 230513010528; for id
    private $format3 = 'l jS \of F Y h:i:s A'; // Saturday 13th of May 2023 02:26:31 AM
    private $format3b = 'M d, Y h:i:s'; //  December 28, 2023 00:00:00
    private $format4 = 'l, jS F Y h:i a'; // Saturday, 13th May 2023 02:35 am
    private $format5 = 'l, jS F Y'; // Saturday, 13th May 2023
    private $format6 = 'D, d M Y'; // Sat, 13 May 2023
    private $format6b = 'D, d M Y h:i a'; // Sat, 13 May 2023 02:35 am
    private $format6c = 'd M Y h:i a'; // 13 May 2023 02:35 am
    private $format6d = 'd M Y h i a'; // 13 May 2023 02 35 am
    private $format7 = 'M d, Y'; // May 13, 2023
    private $format7b = 'M d, y'; // May 13, 23
    private $format7c = 'M d, Y h:i a'; // May 13, 2023 02:35 am
    private $format8 = 'a'; // am | pm

    public function generateToken(int $length = 16)
    {
        if(function_exists('random_bytes')){
            $bytes = random_bytes($length / 2);
        } else {
            $bytes = openssl_random_pseudo_bytes($length / 2);
        }

        return bin2hex($bytes);
    }

    public function generateId(int $prefix = 4)
    {
        $res = $this->generateDate($this->format2);

        try {
            if($prefix > 0) {
                $prefix = $this->generateToken($prefix);
                $res = "{$prefix}.{$res}";
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function generateSpecificId(string $prefix = '_')
    {
        $res = $this->generateDate($this->format2);
        $getPrefix = str_replace('_', '-', $prefix);
        $getPrefix = str_replace(' ', '-', $getPrefix);
        $extra = $this->generateToken(4);
        $res = "{$getPrefix}_{$extra}.{$res}";

        return strtolower($res);
    }

    public function encoder(mixed $data, bool $encode = true)
    {
        $res = $data;
        $isEncode = is_null($encode)? true: $encode;
        try {
            if(is_bool($isEncode)) {
                if($isEncode) {
                    $res = hashid()->encode($data);
                } else {
                    $res = hashid()->decode($data);
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function validateMobileNumber(string $mobile, $len = 13)
    {
        $pattern = '/^[0-9]{13}+$/';
        $getMobile = str_replace('+', '', $mobile);

        if(is_numeric($len)) {
            if($len <= 10) {
                $pattern = '/^[0-9]{10}+$/';
            }
            else if($len == 11) {
                $pattern = '/^[0-9]{11}+$/';
            }
            else if($len == 12) {
                $pattern = '/^[0-9]{12}+$/';
            }
        }
        return preg_match($pattern, $getMobile);
    }

    public function validateEmail(string $email)
    {
        return preg_match('/^[A-z0-9_\-]+[@][A-z0-9_\-]+([.][A-z0-9_\-]+)+[A-z.]{2,4}$/', $email);
    }

    public function validateSingleName(string $name)
    {
        $res = null;
        $err = [];

        if(ctype_space($name)) {
            array_push($err, 'cannot be only space');
        }
        else {
            $getName = trim($name);
            if(preg_match('/\s/', $getName)) {
                array_push($err, 'must be 1 word');
            }

            if(preg_match_all("/[A-Z]/", $getName) < 1) {
                array_push($err, 'must start with capital letter');
            }
    
            if(preg_match_all("/[A-Z]/", $getName) > 2) {
                array_push($err, 'can only contain max of 2 capital letters');
            }
        }
        
        if(count($err) > 0) {
            $res = implode("\n", $err);
        }

        return $res;
    }

    public function validateFullName(string $name)
    {
        $res = null;
        $err = [];

        if(ctype_space($name)) {
            array_push($err, 'cannot be only space');
        }
        else {
            $getName = trim($name);

            if(preg_match_all('/\b[A-Z][A-Za-z0-9]+\b/', $getName) == 0) {
                array_push($err, 'must start with capital letter');
            }
    
            if(!preg_match('/\s/', $getName)) {
                array_push($err, 'should include 2 names at least');
            }
        }
        
        if(count($err) > 0) {
            $res = implode("\n", $err);
        }

        return $res;
    }

    public function newlineToArray(string $data)
    {
        $res = [];
        try {
            $res = preg_split("/\\r\\n|\\r|\\n/", $data);
            // $res = preg_split('/$\R?^/m', $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function stringArrayToArray(string $data)
    {
        $res = [];
        try {
            if(str_contains($data, ',')) {
                $newData = str_replace('[', '', $data);
                $newData = str_replace(']', '', $newData);
                $res = explode(",", $newData);
            }
            // $res = preg_split('/$\R?^/m', $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function stringWordCount(mixed $data)
    {
        $res = [];
        try {
            $getWords = $data;
            if(is_array($data)) {
                $getWords = implode(" ", $data);
            }
            $getWords = strip_tags($getWords);
            $words = str_word_count($getWords);
            $res = $this->numberFormatter($words);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function stringReplaceFirst($search, $replace, $subject)
    {
        $res = $subject;

        try {
            $pos = strpos($subject, $search);
            if ($pos !== false) {
                $newStr = substr_replace($subject, $replace, $pos, strlen($search));
                $res = trim($newStr);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function numberFormatter(int $num)
    {
        if($num >= 1000) {
            $rounded = round($num);
            $x_num_format = number_format($rounded);
            $x_array = explode(',', $x_num_format);
            $x_parts = array('K', 'M', 'B', 'T');
            $x_count_parts = count($x_array) - 1;
            $x_display = $rounded;
            $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0]: '');
            $x_display .=  $x_parts[$x_count_parts - 1];
            return $x_display;
        }
        return $num;
    }

    public function route(Request $request, string $type = null, bool $isPattern = false)
    {
        $res = null;

        switch ($type) {
            case 'path':
                $res = $request->path();
                break;
            case 'name':
                $res = $request->route()->getName();
                break;
            case 'url':
                $res = $request->url();
                break;

            default:
                $res = $request->route()->getName();
                break;
        }

        if($isPattern) {
            $res = $request->is($type);
        }

        return $res;
    }

    public function isNull($data)
    {
        $res = [
            'data' => false,
            'text' => null
        ];

        try {
            if(is_null($data) || empty($data)) {
                $res = [
                    'data' => true,
                    'text' => 'Object'
                ];

                try {
                    if(is_string($data)){
                        $res['text'] = 'String';
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }

                try {
                    if(is_int($data)){
                        $res['text'] = 'Integer';
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            if($data->isEmpty()) {
                $res = [
                    'data' => true,
                    'text' => 'Collection'
                ];
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            if(is_bool($data)) {
                $res = [
                    'data' => $data,
                    'text' => 'Boolean'
                ];
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function arrayForced(mixed $data)
    {
        $res = [];
        try {
            if(is_array($data) && count($data) > 0) {
                $res = $data;
            }
        } catch(Exception $th) {
            //throw $th;
        }

        try {
            if(is_string($data) || is_numeric($data)) {
                if(str_contains($data, ',')) {
                    $res = explode(',', $data);
                } else {
                    array_push($res, $data);
                }
            }
        } catch(Exception $th) {
            //throw $th;
        }

        return $res;
    }

    public function truncateLetters(string $data, int $limit = 3, string $suffix)
    {
        $getData = $data;
        if($getData !== '') {
            $getData = Str::limit($getData, $limit, $suffix);
        }
        return $getData;
    }

    public function truncateWords(string $data, int $limit = 3, string $suffix = '...')
    {
        $getData = $data;
        if($getData !== '') {
            $getData = Str::words($getData, $limit, $suffix);
        }
        return $getData;
    }

    public function getSwitchData($data)
    {
        $res = false;
        try {
            if(is_bool($data) || $data == 'on' || $data == 'true') {
                $res = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function generateDate(string $format = 'Y-m-d H:i:s')
    {
        $date = Date::now();
        try {
            if(!empty($format)) {
                $date = $date->format($format);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $date;
    }

    public function getUploadFiles(Request $request, $field = 'file')
    {
        $uploads = null;
        try {
            $files = $_FILES[$field];
            if(!is_null($files)) {
                $newObj = new stdClass();
                if(is_array($files['name'])) {
                    $res = [];
                    foreach($files['name'] as $key => $val) {
                        $newObj->hashName = $files['name'][$key];
                        $newObj->getClientOriginalName = $files['name'][$key];
                        $newObj->getClientMimeType = $files['type'][$key];
                        $newObj->getClientOriginalExtension = $files['ext'][$key];
                        $newObj->getSize = $files['size'][$key];
                        $newObj->getRealPath = $files['tmp_name'][$key];
                        array_push($res, $newObj);
                    }
                    $uploads = $res;
                } else {
                    $newObj->hashName = $files['name'];
                    $newObj->getClientOriginalName = $files['name'];
                    $newObj->getClientMimeType = $files['type'];
                    $newObj->getClientOriginalExtension = $files['ext'];
                    $newObj->getSize = $files['size'];
                    $newObj->getRealPath = $files['tmp_name'];
                    $uploads = $newObj;
                }
            }
        } catch (\Throwable $th) {
            $files = $request->file($field);
            if(!is_null($files)) {
                $uploads = $files;
            }
        }
        return $uploads;
    }

    public function arrayRandomize(array $data)
    {
        $res = $data[0];
        try {
            $getRandArray = array_rand($data);
            $res = $data[$getRandArray];
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function arrayRestructure(array $data, array $res, $showBoth = false)
    {
        if(!is_null($data)) {
            foreach ($data as $key => $value) {
                array_push($res, [
                    'label' => $showBoth?"{$value} {$key}":"{$value}",
                    'value' => $value
                ]);
            }
        }
        return $res;
    }

    public function arrayRestructureB(array $data, array $res, $showBoth = false)
    {
        if(!is_null($data)) {
            foreach ($data as $key => $value) {
                array_push($res, [
                    'label' => $showBoth?"{$value} {$key}":"{$value}",
                    'value' => $key
                ]);
            }
        }
        return $res;
    }

    public function editorContent($request, $fieldName = null)
    {
        $getName = is_null($fieldName)? 'content': $fieldName;
        $getContent = $request->input($getName);
        $content = str_replace('../', env('ROUTE_FULL_PATH'), $getContent);
        return $content;
    }

    public function formatBytes($size, $precision = 2)
    {
        if($size > 0) {
            $size = (int) $size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');
            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        } else {
            return $size;
        }
    }

    public function bytesToHuman($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function collectionToArray($data)
    {
        /**
         * Use this when applying DB query builder
         */
        $res = null;
        try {
            $res = array_map(function ($value) {
                return (array)$value;
            }, $data); 
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }


    public function telegramInbound($request)
    {
        // $json = file_get_contents('php://input');
        // $request = json_decode($json);

        $isCallback = false;
        $callbackData = null;
        $requestRaw = $request;

        $data = [
            'update-id' => null,
            'user-id' => null,
            'user-firstname' => null,
            'user-username' => null,
            'user-displayname' => null,
            'contact-phone' => null,
            'message-command' => NULL,
            'message-text' => null,
            'message-date' => null,
            'message-id' => null,
            'language-code' => null,
            'chat-id' => null,
            'chat-obj' => null,
            'message-entities' => null,
            'caption' => null,
            'photo' => null,
            'video' => null,
            'audio' => null,
            'document' => null,
            'poll-id' => null,
            'poll-option-ids' => null,
            'is-callback' => false,
            'message-reaction' => null,
            'error' => null
        ];

        $getMessage = [];
        $updateId = null;
        try {
            $getMessage = $request->message;
            $updateId = $request->update_id;
        } catch (\Throwable $th) {
            //
        }

        try {
            $updateId = $request['update_id'];
            $request = $request['callback_query'];
            $getMessage = $request['message'];
            $callbackData = $request['data'];
            $isCallback = true;
        } catch (\Throwable $th) {
            // $this->log($th);
        }

        try {
            $userId = null;
            $isBot = false;
            $userDisplayname = null;
            $userFirstname = null;
            $userUsername = null;
            $contactPhone = null;
            $messageCommandText = null;
            $messageText = null;
            $messageDate = null;
            $messageId = null;
            $chatId = null;
            $chatObj = null;
            $langCode = null;
            $messageEntities = null;
            $caption = null;
            $photo = null;
            $video = null;
            $audio = null;
            $document = null;
            $messageReaction = null;

            try {
                $userId = $request['from']['id'];
                $chatId = $request['chat']['id'];
            } catch (\Throwable $th) {
                //throw $th;
            }

            try { // reaction is set
                $getReaction = $requestRaw['message_reaction'];
                $userId = $getReaction['user']['id'];
                $chatId = $getReaction['chat']['id'];
                $messageId = $getReaction['message_id'];
                $messageDate = $getReaction['date'];
                $isBot = $getReaction['user']['is_bot'];
                $userUsername = $getReaction['user']['username']??null;
                $userFirstname = $getReaction['user']['first_name'];
                $oldReaction = $getReaction['old_reaction'][0]??null;
                $newReaction = $getReaction['new_reaction'][0]??null;

                if(!$isBot) {
                    $messageReaction = [
                        'chat-id' => $chatId,
                        'message-id' => $messageId,
                        'date' => $messageDate,
                        'user-id' => $userId,
                        'firstname' => $userFirstname,
                        'username' => $userUsername,
                        'old-reaction' => $oldReaction,
                        'new-reaction' => $newReaction
                    ];
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

            try { // callback is set
                $userUsername = $getMessage['from']['username'];

                if($isCallback) {
                    $userFirstname = $request['from']['username'];
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

            try { // contact is set
                $contactObj = $getMessage['contact'];
                $contactPhone = $contactObj['phone_number'];
            } catch (\Throwable $th) {
                //throw $th;
            }

            try {
                $userId = is_null($userId)?$getMessage['from']['id']: $userId;
                $isBot = $getMessage['from']['is_bot'];
                $userFirstname = $getMessage['from']['first_name'];
                $messageDate = $getMessage['date'];
                $messageId = $getMessage['message_id'];
                $chatObj = $getMessage['chat'];
                $chatId = $getMessage['chat']['id'];

                if($isCallback) {
                    $userFirstname = $request['from']['first_name'];
                }

                if(!is_null($userUsername)) {
                    $userDisplayname = $userUsername;
                }
                elseif(!is_null($userFirstname)) {
                    $userDisplayname = "$userFirstname";
                }
                else {
                    $userDisplayname = $userId;
                }

                try {
                    $langCode = $getMessage['from']['language_code'];
                } catch (\Throwable $th) {
                    //throw $th;
                }

                try {
                    $messageEntities = $getMessage['entities'];
                } catch (\Throwable $th) {
                    //throw $th;
                }

                try {
                    $messageText = $callbackData;
                } catch (\Throwable $th) {
                    //
                }

                try {
                    $messageCommandText = $getMessage['text'];
                } catch (\Throwable $th) {
                    //
                }

                try {
                    $messageCommandText = !is_null($callbackData)? $callbackData: $getMessage['text'];
                } catch (\Throwable $th) {
                    //
                }

                try {
                    $caption = $getMessage['caption'];
                } catch (\Throwable $th) {
                    //throw $th;
                }

                try {
                    $photo = $getMessage['photo'];
                } catch (\Throwable $th) {
                    //throw $th;
                }

                try {
                    $video = $getMessage['video'];
                } catch (\Throwable $th) {
                    //throw $th;
                }

                try {
                    $audio = $getMessage['audio'];
                } catch (\Throwable $th) {
                    //throw $th;
                }

                try {
                    $document = $getMessage['document'];
                } catch (\Throwable $th) {
                    //throw $th;
                }
            } catch (\Throwable $th) {
                //throw $th;
                // $this->log($th);
            }

            $data = [
                'update-id' => $updateId,
                'user-id' => $userId,
                'is-bot' => $isBot,
                'user-firstname' => $userFirstname,
                'user-username' => $userUsername,
                'user-displayname' => $userDisplayname,
                'contact-phone' => $contactPhone,
                'message-command' => $messageCommandText,
                'message-text' => $messageText,
                'message-date' => $messageDate,
                'message-id' => $messageId,
                'language-code' => $langCode,
                'chat-id' => $chatId,
                'chat-obj' => $chatObj,
                'message-entities' => $messageEntities,
                'caption' => $caption,
                'photo' => $photo,
                'video' => $video,
                'audio' => $audio,
                'document' => $document,
                'poll-id' => null,
                'poll-option-ids' => null,
                'is-callback' => $isCallback,
                'message-reaction' => $messageReaction,
                'error' => null
            ];
        } catch (\Throwable $th) {
            //throw $th;
            // Log::error($th);
        }

        try {
            $request = $requestRaw->all();
            $poll = $request['poll_answer'];
            $user = $poll['user'];
            $newData = [
                'poll-id' => $poll['poll_id'],
                'poll-option-ids' => $poll['option_ids'],
                'user-id' => $user['id'],
                'user-firstname' => $user['first_name'],
                'user-username' => $user['username'],
                'language-code' => $user['language_code'],
            ];

            $data = [
                ...$data,
                ...$newData
            ];
        } catch (\Throwable $th) {
            // Log::error($th);
        }

        return $data;
    }

    public function telegramPhotoPath($data)
    {
        /**
         * {"ok":true,"result":{
         * "file_id":"AgVk4AQ",
         * "file_unique_id":"AQADj8kxGyQ5OFF9",
         * "file_size":32467,
         * "file_path":"photos/file_0.jpg"}}
         */
        $path = null;

        try {
            $getData = json_decode($data);
            $result = $getData->result;
            $path = $result->file_path;
        } catch (\Throwable $th) {
            // Log::error($th);
        }

        return $path;
    }

    public function telegramPhotoInfo(array $photo)
    {
        $data = [
            'file_id' => null,
            'unique_id' => null,
            'file_size' => null,
            'mime' => null
        ];

        try {
            $arrLen = count($photo);
            $bestPhoto = $photo[$arrLen - 1];
            $data = [
                'file_id' => $bestPhoto['file_id'],
                'unique_id' => $bestPhoto['file_unique_id'],
                'file_size' => $bestPhoto['file_size'],
                'mime' => 'image/jpeg'
            ];
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $data;
    }

    public function isTelegramCommand($messageText)
    {
        $isCommand = false;
        if ( !preg_match('/\s/', $messageText) && str_starts_with($messageText, '/')) {
            $isCommand = true;
        }

        return $isCommand;
    }

    public function isTelegramMatch($messageText, $input = 'step_', $forced = false)
    {
        $isMatched = false;
        $input = strtolower($input);
        $messageText = strtolower($messageText);

        if($forced) {
            if ( $messageText == $input) {
                $isMatched = true;
            }
        }
        else {
            if ( str_starts_with($messageText, '/')) {
                $messageText = str_replace('/', '', $messageText);
            }
    
            if ( str_starts_with($messageText, $input)) {
                $isMatched = true;
            }
        }

        return $isMatched;
    }

    public function telegramBotCommandArray()
    {
        $getCommands = config('telegram.commands_bot');
        $cmdArr = [];
        foreach ($getCommands as $val) {
            if($val !== '' && $val !== null) {
                array_push($cmdArr, $val);
            }
        }
        return $cmdArr;
    }

    public function cookieCheck(string $key)
    {
        $res = Cookie::has($key);

        return $res;
    }

    public function cookieSet(string $key, mixed $data, int $expiry = 60*24)
    {
        $res = false;
        try {
            // $dataHash = $this->encoder($data);
            Cookie::queue(Cookie::make($key, $data, $expiry));
            $res = true;
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function cookieGet(string $key)
    {
        $res = null;
        try {
            $res = Cookie::get($key);
            // $res = $this->encoder($dataHash, false);
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function cookieDelete(string $key)
    {
        $res = false;
        try {
            Cookie::queue(Cookie::forget($key));
            $res = true;
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function cacheHas(string $key, string $prefix = 'item_')
    {
        $res = false;
        try {
            $keyStr = $prefix . $key;
            $res = cache()->has($keyStr);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function cachePut(string $key, mixed $data, string $prefix = 'item_', $expiry = null)
    {
        $res = false;
        $keyStr = $prefix . $key;
        try {
            $getExpiry = $expiry;
            if(is_null($expiry)) {
                $getExpiry = now()->addMinute(1);
            }
            cache()->put($keyStr, $data, $getExpiry);
            $res = true;
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function cacheGet(string $key, string $prefix = 'item_')
    {
        $res = null;
        try {
            $keyStr = $prefix . $key;
            $res = cache()->get($keyStr);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function cacheRemove(string $key, string $prefix = 'item_')
    {
        $res = false;
        try {
            $keyStr = $prefix . $key;
            cache()->forget($keyStr);
            $res = true;
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function cacheClearAll()
    {
        $res = false;
        try {
            cache()->flush();
            $res = true;
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function log($data, string $type = 'info')
    {
        try {
            switch ($type) {
                case 'warning':
                    Log::warning($data);
                    break;
                case 'error':
                    Log::error($data);
                    break;
                case 'notice':
                    Log::notice($data);
                    break;

                default:
                    Log::info($data);
                    break;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function filterNumbers(string $data)
    {
        $res = (int)filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        return $res;
    }

    public function countCharacters(string $str)
    {
        $upper = 0;
        $lower = 0;
        $number = 0;
        $special = 0;

        try {
            for ($i = 0; $i < strlen($str); $i++)
            {
                if ($str[$i] >= 'A' &&
                    $str[$i] <= 'Z')
                    $upper++;
                else if ($str[$i] >= 'a' &&
                         $str[$i] <= 'z')
                    $lower++;
                else if ($str[$i]>= '0' &&
                         $str[$i]<= '9')
                    $number++;
                else
                    $special++;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        $res = [
            'upper' => $upper,
            'lower' => $lower,
            'number' => $number,
            'special' => $special,
            'total' => $upper + $lower + $number + $special
        ];

        return $res;
    }

    public function dateNow(string $format = null)
    {
        if(!is_null($format)) {
            return Carbon::now()->format($format);
        }
        return Carbon::now();
    }

    public function formatDate(string $dateString, string $fromFormat, string $toFormat)
    {
        $date = $dateString;
        try {
            if(!empty($dateString)) {
                $dateString = $dateString == 'now'? Carbon::now(): $dateString;
                $date = Carbon::createFromFormat($fromFormat, $dateString)->format($toFormat);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $date;
    }

    public function formatUnixTime(mixed $data, string $format = null)
    {
        $res = null;
        try {
            $getFormat = $this->format1;
            if(!is_null($format)) {
                $getFormat = $format;
            }
            $res = date($getFormat, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $res;
    }

    public function format1()
    {
        return $this->format1;
    }

    public function format1b()
    {
        return $this->format1b;
    }

    public function format1c()
    {
        return $this->format1c;
    }

    public function format1d()
    {
        return $this->format1d;
    }

    public function format1e()
    {
        return $this->format1e;
    }

    public function format2()
    {
        return $this->format2;
    }

    public function format3()
    {
        return $this->format3;
    }

    public function format3b()
    {
        return $this->format3b;
    }

    public function format4()
    {
        return $this->format4;
    }

    public function format5()
    {
        return $this->format5;
    }

    public function format6()
    {
        return $this->format6;
    }

    public function format6b()
    {
        return $this->format6b;
    }

    public function format6c()
    {
        return $this->format6c;
    }

    public function format6d()
    {
        return $this->format6d;
    }

    public function format7()
    {
        return $this->format7;
    }

    public function format7b()
    {
        return $this->format7b;
    }

    public function format7c()
    {
        return $this->format7c;
    }

    public function format8()
    {
        return $this->format8;
    }

    public function year()
    {
        $date = Carbon::now();
        return $date->year;
    }

    public function month()
    {
        $date = Carbon::now();
        return $date->month;
    }

    public function day()
    {
        $date = Carbon::now();
        return $date->day;
    }

    public function hour()
    {
        $date = Carbon::now();
        return $date->hour;
    }

    public function second()
    {
        $date = Carbon::now();
        return $date->second;
    }

    public function dayOfWeek()
    {
        $date = Carbon::now();
        return $date->dayOfWeek;
    }

    public function dayOfYear()
    {
        $date = Carbon::now();
        return $date->dayOfYear();
    }

    public function weekOfMonth()
    {
        $date = Carbon::now();
        return $date->weekOfMonth;
    }

    public function daysInMonth()
    {
        $date = Carbon::now();
        return $date->daysInMonth;
    }

    private function getDate($date = null, $tz = null)
    {
        $current = $date == null? Carbon::now(): Carbon::parse($date);
        if(!is_null($tz)) {
            $current = $date == null? Carbon::now()->setTimezone($tz): Carbon::parse($date)->setTimezone($tz);
        }
        return $current;
    }

    public function addDays(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->addDays($val);
    }

    public function addWeeks(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->addWeeks($val);
    }

    public function addMonths(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->addMonths($val);
    }

    public function addYears(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->addYears($val);
    }

    public function addHours(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->addHours($val);
    }

    public function addMinutes(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->addMinutes($val);
    }

    public function addSeconds(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->addSeconds($val);
    }

    public function subDays(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->subDays($val);
    }

    public function subWeeks(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->subWeeks($val);
    }

    public function subMonths(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->subMonths($val);
    }

    public function subYears(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->subYears($val);
    }

    public function subHours(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->subHours($val);
    }

    public function subMinutes(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->subMinutes($val);
    }

    public function subSeconds(int $val, $date = null, $tz = null)
    {
        $current = $this->getDate($date, $tz);
        return $current->subSeconds($val);
    }

    public function diffSeconds($targetDate, $baseDate = null)
    {
        $date = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        return $date->diffInSeconds($targetDate);
    }

    public function diffMinutes($targetDate, $baseDate = null)
    {
        $date = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        return $date->diffInMinutes($targetDate);
    }

    public function diffHours($targetDate, $baseDate = null)
    {
        $date = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        return $date->diffInHours($targetDate);
    }

    public function diffDays($targetDate, $baseDate = null)
    {
        $date = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        return $date->diffInDays($targetDate);
    }

    public function diffHumans($targetDate, $baseDate = null)
    {
        $date = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        $res = $date->diffForHumans($targetDate);
        $res = str_replace('after', 'ago', $res);
        return $res;
    }

    public function equals($targetDate, $baseDate = null)
    {
        $now = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        $date = Carbon::parse($targetDate);
        $res = $date->eq($now);
        return $res;
    }

    public function notEquals($targetDate, $baseDate = null)
    {
        $now = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        $date = Carbon::parse($targetDate);
        $res = $date->ne($now);
        return $res;
    }

    public function greaterThan($targetDate, $baseDate = null)
    {
        $now = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        $date = Carbon::parse($targetDate);
        $res = $date->gt($now);
        return $res;
    }

    public function lessThan($targetDate, $baseDate = null)
    {
        $now = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        $date = Carbon::parse($targetDate);
        $res = $date->lt($now);
        return $res;
    }

    public function greaterThanOrEquals($targetDate, $baseDate = null)
    {
        $now = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        $date = Carbon::parse($targetDate);
        $res = $date->gte($now);
        return $res;
    }

    public function lessThanOrEquals($targetDate, $baseDate = null)
    {
        $now = $baseDate == null? Carbon::now(): Carbon::parse($baseDate);
        $date = Carbon::parse($targetDate);
        $res = $date->lte($now);
        return $res;
    }
}
