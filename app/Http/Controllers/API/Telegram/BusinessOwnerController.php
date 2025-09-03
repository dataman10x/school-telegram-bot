<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\MediaHandler;
use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\ProductMedia;
use App\Models\Products;
use App\Models\Reviews;

class BusinessOwnerController
{
    private $parser;
    private $mediaHandler;
    private $inlineKeyboard;
    private $userAccount;
    private $businesses;
    private $data;
    private $content;
    private $userId;
    private $userHashId;
    private $userFirstname;
    private $userUsername;
    private $chatId;
    private $replyToMessageId;
    private $messageCommandText;
    private $messageTime;
    private $messageTimeFormatted;
    private $isCancelButton;
    private $isPrevButton;
    private $isNextButton;
    private $slideName;
    private $cachePrefix;
    private $cacheDuration;
    private $perView;
    private $maxBusinesses;
    private $maxUploads;
    private $lastSlide;

    public function __construct($data)
    {
        $text = 'Listed Businesses.';
        $this->data = $data;
        $this->parser = new Parser;
        $this->mediaHandler = new MediaHandler;
        $this->inlineKeyboard = new InlineKeyboards;
        $this->userAccount = new UserAccountController;
        $this->businesses = new Products;

        $this->userId = $this->data['user-id'];
        $this->userHashId = $this->parser->encoder($this->userId);
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);

        $this->slideName = config('telegram.commands.owner.name');
        $this->cachePrefix = config('constants.cache_prefix.slide');
        $this->cacheDuration = 10;
        $this->perView = config('constants.business_per_view');
        $this->maxBusinesses = config('constants.business_owner_products_max');
        $this->maxUploads = config('constants.business_owner_uploads_max');

        $this->isCancelButton = false;
        $this->isPrevButton = false;
        $this->isNextButton = true;

        $this->lastSlide = 10;

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];
    }

    public function index($type)
    {
        // first 50 listings
        $totalBiz = $this->businesses->count();
        if($totalBiz <= 50) {
            $this->lastSlide = 15;
            $this->maxUploads = 10;
        }

        $slideNumber = 0;

        if($this->messageCommandText == config('telegram.commands.prev.name') || $this->messageCommandText == config('telegram.commands.next.name')) {
            $stripType = preg_replace('/[^0-9]/', '', $type);
            $slideNumber = intval($stripType);
            if($this->messageCommandText == config('telegram.commands.prev.name')) {
                $slideNumber = $slideNumber - 1;
            }
            else if($this->messageCommandText == config('telegram.commands.next.name')) {
                $slideNumber = $slideNumber + 1;
            }
        }

        $validate = $slideNumber < 2? $this->validateAction(): null;

        if(!is_null($validate)) {
            $data = [
                'text' => $validate,
                'parse_mode' => 'HTML'
            ];

            $this->content = [
                ...$this->content,
                ...$data
            ];
            $result = app('telegram_bot')->sendMessage( $this->content);
            return response()->json($result, 200);
        }


        if($slideNumber == 0) {
            $this->intro();
        }
        else if($slideNumber == 1) {
            $this->slide1();
        }
        else if($slideNumber == 2) {
            $this->slide2();
        }
        else if($slideNumber == 3) {
            $this->slide3();
        }
        else if(
            $slideNumber >= 4 &&
            $slideNumber < $this->lastSlide
            ) {
            $this->slideUploads($slideNumber);
        }
        else if($slideNumber == $this->lastSlide) {
            $this->slideLast();
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    private function validateAction()
    {
        $response = null;

        // validate action
        $this->userHashId = $this->parser->encoder($this->userId);
        $userBusinesses = Products::where('user_id', $this->userHashId)->get();
        try {
            $countUploads = 0;
            foreach ($userBusinesses as $uMedia) {
                $mediaCount = count($uMedia->media);
                $countUploads = $countUploads + $mediaCount;
            }
            $errorMsg = [];

            $countBiz = count($userBusinesses);

            if($countBiz >= $this->maxBusinesses) {
                array_push($errorMsg, "Max. number of business you can create is $this->maxBusinesses");
            }

            if($countUploads >= ($this->maxUploads * $this->maxBusinesses)) {
                array_push($errorMsg, "Your business uploads cannot exceed $this->maxUploads");
            }

            if(count($errorMsg) > 0) {
                array_push($errorMsg, "\n\nYou may reset your saved Business info by typing <b>reset</b> in the input field.");
                $response = implode(' ', $errorMsg);

                $this->userAccount->setUserInputTrue($this->userId, $this->slideName . '_reset');
            }
        } catch (\Throwable $th) {
            // $this->parser->log($th);
        }

        return $response;
    }

    public function intro()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '0',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        // get business stats
        // $businessCount = Products::count();
        // $businessMediaCount = ProductMedia::count();
        // $firstname = $this->userFirstname;
        // $perView = $this->perView;

        // update demo visits
        $this->userAccount->updateDemoVisits($this->userId);



        $text = "<b>Business Set Up</b>

I will walk you through 4 steps to create your business. Simply click next till it is completed.

The 4 stages: <b>Business Name</b>, <b>Business detail</b>, <b>Business Contacts</b>, and <b>Image Uploads</b>.
Your first photo becomes your cover photo, if only it is sent as a single upload.

<b>Disclaimer!</b>
You are responsible for the info you update here. Do ensure that they are correct and not misleading in any way. Any report of falsehood will lead to immediate ban without warning.
        ";

        $keyboardBuilder = $this->inlineKeyboard->prevNextInlinekeyboard(
            $this->isNextButton,
            $this->isPrevButton,
            $this->isCancelButton
        );

        // save state
        $this->userAccount->setUserInputTrue($this->userId, $this->slideName);

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

    public function slide1()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '1',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        // save state
        $this->userAccount->setUserInputTrue($this->userId, $this->slideName . '1');

        $text = "<b>Business Name</b>

Please, type your Business Name in the input field.";

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }

    public function slide2()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '2',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        // save state
        $this->userAccount->setUserInputTrue($this->userId, $this->slideName . '2');

        $text = "<b>Business Detail</b>

Please, type your Business Detail in the input field.";

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }

    public function slide3()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '3',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        // save state
        $this->userAccount->setUserInputTrue($this->userId, $this->slideName . '3');

        $text = "<b>Business Contacts</b>

Please, type your Business Contacts in the input field. Contacts include: address, phone numbers(s), email(s), etc.";

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
    }

    public function slideUploads($getNum)
    {
        $num = $getNum > 4? $getNum: $getNum  + 1;
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . $num,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        // save state
        $this->userAccount->setUserInputTrue($this->userId, $this->slideName . $num);

        $sub = '';
        $sub2 = 'Click attach file OR Next button to skip to next upload.';
        $photoRef = $num - 4;

        if($photoRef == $this->maxUploads) {
            $sub2 = 'Click attach file OR Next button to skip to complete your business setup.';
        }

        if($num == 5) {
            $sub = "\nMaximum allowed photos is $this->maxUploads. Upload photo(s) that represent your business.";
            if($this->maxUploads == 10) {
                $sub = "\nYou are among the first 50 users to list your business with us. Your photo uploads were increased from 5 to $this->maxUploads\n";
            }
            $sub = $sub . "\nNo obscene photos are allowed please.\n";
        }

        $text = "<b>Business Photo $photoRef</b>
$sub
$sub2";


        $keyboardBuilder = $this->inlineKeyboard->prevNextInlinekeyboard(
            $this->isNextButton,
            $this->isPrevButton,
            $this->isCancelButton
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
    }

    public function slideLast()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . $this->lastSlide,
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        // save state
        $this->userAccount->setUserInputTrue($this->userId, $this->slideName . $this->lastSlide);

        $sub = '';
        if($this->maxUploads == 10) {
            $sub = "\n\nYour being among the first 50 gives you an edge in our future promotions.\n";
        }

        $text = "Thank you for listing your Business in our ChatBot. Spread the words to all your friends, so that they too will join $sub.

Stress is never a friend. Your business deserves better.

⚠ Your business photos stay visible on Telegram as long as you didn't delete them from the chat.
You need to keep your listing active. Telegram may delete data after 2 weeks of inactivity.";

        $keyboardBuilder = $this->inlineKeyboard->businessSuccessInlineKeyboard();

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];

        // reset input
        $this->userAccount->setUserInputFalse($this->userId);
    }

    public function inputHandler($type)
    {
        $this->isCancelButton = false;
        $this->isPrevButton = false;
        $this->isNextButton = true;

        $keyboardBuilder = $this->inlineKeyboard->prevNextInlinekeyboard(
            $this->isNextButton,
            $this->isPrevButton,
            $this->isCancelButton
        );

        $inputText = $this->messageCommandText;

        $text = "<b>Recieved</b> with thanks.";

        if($type == $this->slideName . '1') {
            $text = "Your Business Name: <b>$inputText</b>, was saved.

Please, click Next to add your Business Detail";

            // save to DB
            $this->userHashId = $this->parser->encoder($this->userId);
            $this->businesses->name = $inputText;
            $this->businesses->user_id = $this->userHashId;
            $this->businesses->save();
        }

        else if($type == $this->slideName . '2') {
            $text = "Your Business Detail was saved.

Please, click Next to add your Business Contacts";

            // save to DB
            $this->userHashId = $this->parser->encoder($this->userId);
            $ownerBusiness = $this->businesses->where('user_id', $this->userHashId)->latest()->first();
            try {
                $data = [
                    'detail' => $inputText
                ];
                $ownerBusiness->update($data);
            } catch (\Throwable $th) {
                $text = "We could not find any matching Business in your name to update.";
            }
        }

        else if($type == $this->slideName . '3') {
            $text = "Your Business Contacts were saved.

Please, click Next to upload Business Photos";

            // save to DB
            $this->userHashId = $this->parser->encoder($this->userId);
            $ownerBusiness = $this->businesses->where('user_id', $this->userHashId)->latest()->first();
            try {
                $data = [
                    'contacts' => $inputText
                ];
                $ownerBusiness->update($data);
            } catch (\Throwable $th) {
                $text = "We could not find any matching Business in your name to update.";
            }
        }

        else if($type == $this->slideName . '4' ||
            $type == $this->slideName . '5' ||
            $type == $this->slideName . '6' ||
            $type == $this->slideName . '7' ||
            $type == $this->slideName . '8' ||
            $type == $this->slideName . '9' ||
            $type == $this->slideName . '10' ||
            $type == $this->slideName . '11' ||
            $type == $this->slideName . '12' ||
            $type == $this->slideName . '13' ||
            $type == $this->slideName . '14') {

            // prompt user
            $chatAction = config('telegram.chatactions.photo');
            $this->content['action'] = $chatAction;
            app('telegram_bot')->sendChatAction($this->content);

            $stripType = preg_replace('/[^0-9]/', '', $type);
            $slideNumber = intval($stripType);
            $doneNumber = $slideNumber - 4;

            $sub = "Please, click Next to upload another Business Photo";

            if($doneNumber == $this->maxUploads) {
                $sub = "Please, click Next to complete your business setup.";
            }

            $text = "Your Business Photo $doneNumber was saved.

$sub";

            // save to DB
            $this->userHashId = $this->parser->encoder($this->userId);
            try {
                $photo = $this->data['photo'];
                $caption = $this->data['caption'];
                $path = null;
                $fileContent = null;
                $disc = config('constants.discs.products');
                $telegramUrlFile = config('constants.telegram_file_path');

                if(!is_null($photo)) {
                    try {
                        $photoInfo = $this->parser->telegramPhotoInfo($photo);
                        $photoId = $photoInfo['file_id'];
                        $mime = $photoInfo['mime'];
                        $localName = $this->parser->generateSpecificId('photo');
                        $extension = "jpg";

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
                            $this->parser->log($th);
                        }

                        $localPath = "$localName.$extension";

                        $this->userHashId = $this->parser->encoder($this->userId);
                        $ownerBusiness = $this->businesses->where('user_id', $this->userHashId)->latest()->first();
                        $media = new ProductMedia;
                        $media->file_id = $photoId;
                        $media->unique_id = $photoInfo['unique_id'];
                        $media->size = $photoInfo['file_size'];
                        $media->mime = $mime;
                        $media->name = $caption;
                        $media->path = $path;
                        $media->local_path = $localPath;
                        $media->disc = $disc;
                        $media->product_id = $ownerBusiness->id;

                        if($media->save()) {
                            if(!is_null($fileContent)) {
                                $this->mediaHandler->saveMedia($localPath, $fileContent, $disc);
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
        else if($type == $this->slideName . '_reset') {
            // reset business detail
            $bizIds = $this->businesses->where('user_id', $this->userHashId)->pluck('id');
            if(!is_null($bizIds)) {
                // get & delete stored media files
                $mediaFiles = [];

                $this->businesses->whereIn('id', $bizIds)->delete();
            }

            $text = "You Business info have been deleted together with all associated media files.
            You may create another business by clicking /owner.";

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

