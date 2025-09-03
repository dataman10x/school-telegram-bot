<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\MediaHandler;
use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\ProductMedia;
use App\Models\Products;
use App\Models\Reviews;

class BusinessesController
{
    private $parser;
    private $mediaHandler;
    private $inlineKeyboard;
    private $onetimeKeyboard;
    private $userAccount;
    private $businesses;
    private $data;
    private $content;
    private $userId;
    private $userFirstname;
    private $userUsername;
    private $chatId;
    private $replyToMessageId;
    private $isCallback;
    private $messageCommandText;
    private $messageTime;
    private $messageTimeFormatted;
    private $isCancelButton;
    private $isPrevButton;
    private $isNextButton;
    private $slideName;
    private $galleryName;
    private $telegramGalleryPhotosMax;
    private $cachePaginatorPrefix;
    private $cacheViewPrefix;
    private $cacheBizIdPrefix;
    private $cacheGalleryIdPrefix;
    private $cacheCallbackPrefix;
    private $cacheDuration;
    private $perView;

    public function __construct($data)
    {
        $text = 'Listed Businesses.';
        $this->data = $data;
        $this->parser = new Parser;
        $this->mediaHandler = new MediaHandler;
        $this->inlineKeyboard = new InlineKeyboards;
        $this->onetimeKeyboard = new OnetimeKeyboards;
        $this->userAccount = new UserAccountController;
        $this->businesses = new Products;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->isCallback = $this->data['is-callback'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);

        $this->slideName = config('telegram.commands.business.name');
        $this->galleryName = config('telegram.commands.view.name');
        $this->telegramGalleryPhotosMax = config('constants.telegram_gallery_photos_max');
        $this->cachePaginatorPrefix = config('constants.cache_prefix.paginator');
        $this->cacheViewPrefix = config('constants.cache_prefix.view');
        $this->cacheGalleryIdPrefix = config('constants.cache_prefix.gallery');
        $this->cacheBizIdPrefix = config('constants.cache_prefix.biz');
        $this->cacheCallbackPrefix = config('constants.cache_prefix.callback');
        $this->cacheDuration = 10;
        $this->perView = config('constants.business_per_view');

        $this->isCancelButton = false;
        $this->isPrevButton = false;
        $this->isNextButton = true;

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];
    }

    public function index($type, $viewMode)
    {
        $slideNumber = 0;
        $totalData = Products::count();
        $bizId = $this->parser->cacheGet($this->userId??'', $this->cacheGalleryIdPrefix);

        if($viewMode == config('telegram.commands.single_business.name')) {
            $this->perView = 1;
        }

        if(($this->messageCommandText == config('telegram.commands.prev.name') || $this->messageCommandText == config('telegram.commands.next.name')) ||
            ($this->messageCommandText == config('telegram.commands.first.name') || $this->messageCommandText == config('telegram.commands.last.name')) ||
                !is_null($viewMode)) {

            $stripType = preg_replace('/[^0-9]/', '', $type);
            $slideNumber = intval($stripType);


            if($viewMode == config('telegram.commands.view.name')) {
                $totalData = ProductMedia::where('product_id', $bizId)->count();
                $this->perView = $this->telegramGalleryPhotosMax;

                if($this->messageCommandText == config('telegram.commands.prev.name')) {
                    $slideNumber = $slideNumber - $this->perView;
                }
                else if($this->messageCommandText == config('telegram.commands.single_business.name') ||
                    $this->messageCommandText == config('telegram.commands.list.name') ||
                    $this->messageCommandText == config('telegram.commands.view.name')) {
                        $slideNumber = 1;
                }
                else if($this->messageCommandText == config('telegram.commands.next.name')) {
                    $getNumber =  $slideNumber + $this->perView;
                    if($getNumber > $totalData) {
                        $getNumber = $slideNumber + ($totalData - $getNumber);
                    }
                    else if($slideNumber == $totalData) {
                        $getNumber = $totalData - 1;
                    }
                    $getNumber = $getNumber > $totalData? $totalData: $getNumber;
                    $slideNumber = $getNumber;
                }
                else if($this->messageCommandText == config('telegram.commands.first.name')) {
                    $slideNumber = 1;
                }
                else if($this->messageCommandText == config('telegram.commands.last.name')) {
                    $slideNumber = $totalData;
                }
            }
            else {
                if($this->messageCommandText == config('telegram.commands.prev.name')) {
                    $slideNumber = $slideNumber - $this->perView;
                }
                else if($this->messageCommandText == config('telegram.commands.single_business.name') ||
                    $this->messageCommandText == config('telegram.commands.list.name')) {
                        $slideNumber = 1;
                }
                else if($this->messageCommandText == config('telegram.commands.next.name')) {
                    $getNumber =  $slideNumber + $this->perView;
                    if($viewMode == config('telegram.commands.view.name')) {
                        $getNumber =  $slideNumber + $this->telegramGalleryPhotosMax;
                    }
                    if($getNumber > $totalData) {
                        $getNumber = $slideNumber + ($totalData - $getNumber);
                    }
                    else if($slideNumber == $totalData) {
                        $getNumber = $totalData - 1;
                    }
                    $getNumber = $getNumber > $totalData? $totalData: $getNumber;
                    $slideNumber = $getNumber;
                }
                else if($this->messageCommandText == config('telegram.commands.first.name')) {
                    $slideNumber = 1;
                }
                else if($this->messageCommandText == config('telegram.commands.last.name')) {
                    $slideNumber = $totalData;
                }
            }
        }


        if($type == $this->slideName) {
            $this->intro();
        }
        else if($viewMode == config('telegram.commands.view.name')) {
            return $this->galleryView($bizId, $slideNumber);
        }
        else if($slideNumber >= 0) {
            $this->isPrevButton = false;

            if($viewMode == config('telegram.commands.single_business.name')) {
                return $this->singleView($slideNumber);
            }
            else if($viewMode == config('telegram.commands.list.name')){
                return $this->listView($slideNumber);
            }
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function intro()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . 0,
            $this->cachePaginatorPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $this->parser->cachePut( // initiate callback
            $this->userId,
            0,
            $this->cacheCallbackPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        // get business stats
        $businessCount = Products::count();
        $businessMediaCount = ProductMedia::count();
        $firstname = $this->userFirstname;

        $text = "
$firstname enjoy the <b>$businessCount businesses</b> with over <b>$businessMediaCount uploads</b>. You too can join these wise business owners to list your business here (/owner).

Use the Next & Previous buttons to navigate through the available Businesses, $this->perView per view

<b>Disclaimer!</b>
Every info contained in the businesses is the sole responsibility of the owners to ensure there accuracy. We are not to be held responsible.
        ";

        $keyboardBuilder = $this->inlineKeyboard->businessViewInlinekeyboard();

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

    public function listView($slideNumber)
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . $slideNumber,
            $this->cachePaginatorPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $this->parser->cachePut(
            $this->userId,
            config('telegram.commands.list.name'),
            $this->cacheViewPrefix,
            $this->parser->addMinutes(5)
        );

        $getCallback = $this->parser->cacheGet(
            $this->userId,
            $this->cacheCallbackPrefix
        );

        if($getCallback == 0) {
            $this->parser->cachePut( // initiate callback
                $this->userId,
                1,
                $this->cacheCallbackPrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );
        }

        if($getCallback == 1) {
            $this->parser->cachePut( // initiate callback
                $this->userId,
                $this->replyToMessageId,
                $this->cacheCallbackPrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );
            $getCallback = $this->replyToMessageId;
        }

        $keyboardBuilder = $this->onetimeKeyboard->cancelOnetimeKeyboard();

        $text = "Business Gallery";

        $len = Products::count();
        $paginate = Products::with('media')->orderBy('created_at', 'DESC')->offset($slideNumber - 1)->limit($this->perView)->get();

        // $this->parser->log($paginate);

        if(!is_null($paginate)) {
            $textArr = [];

            try {
                foreach ($paginate as $key => $value) {
                    $mediaArr = $value->media;
                    $countMedia = count($mediaArr);
                    $businessName = "<b>#$value->id - 💼$value->name</b>";
                    $detail = $value->detail;
                    $contacts = $value->contacts;
                    $photosInfo = '';
                    if($countMedia > 0) {
                        $photosInfo = "\n\nUploaded Photos: $countMedia";
                    }
                    $item = "
$businessName

$detail

$contacts $photosInfo
                    ";
                    array_push($textArr, $item);
                }

                $text = implode("\n\n", $textArr);
            } catch (\Throwable $th) {
                //throw $th;
            }

            $keyboardBuilder = $this->inlineKeyboard->paginationInlinekeyboard($len, $slideNumber);

            if(!is_null($getCallback) && ($getCallback !== 0 && $getCallback !== 1)) {
                $this->content = [
                    'chat_id' => $this->chatId,
                    'text' => $text,
                    'message_id' => $getCallback,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboardBuilder
                ];

                $result = app('telegram_bot')->editMessageText( $this->content);
            }
            else{
                $this->content = [
                    'chat_id' => $this->chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboardBuilder
                ];

                $result = app('telegram_bot')->sendMessage( $this->content);
            }

        } else {
            $text = "<b>Business Gallery</b>\n
<blockquote>No Business listing is found at the moment.</blockquote>\n
You may add your business at /owner.";

        $this->content = [
            ...$this->content,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboardBuilder
        ];

        $result = app('telegram_bot')->sendMessage( $this->content);
        }

        return response()->json($result, 200);
    }

    public function singleView($slideNumber)
    {
        $getCallback = $this->parser->cacheGet(
            $this->userId,
            $this->cacheCallbackPrefix
        );

        if($getCallback == 0) {
            $this->parser->cachePut( // initiate callback
                $this->userId,
                1,
                $this->cacheCallbackPrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );
        }

        if($getCallback == 1) {
            $this->parser->cachePut( // initiate callback
                $this->userId,
                $this->replyToMessageId,
                $this->cacheCallbackPrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );
            $getCallback = $this->replyToMessageId;
        }

        $text = "Business Gallery";
        $countMedia = 0;
        $bizPhotoId = null;
        $filePath = null;
        $fileId = null;
        $localPath = null;

        $paginate = Products::with('media')->orderBy('created_at', 'DESC')->offset($slideNumber - 1)->limit(1)->get();
        $len = Products::count();

        $keyboardBuilder = $this->inlineKeyboard->paginationSingleViewInlinekeyboard($len, $slideNumber);

        if(!is_null($paginate)) {
            $textArr = [];
            $bizId = null;

            foreach ($paginate as $key => $value) {
                try {
                    $bizId = $value->id;
                    $mediaArr = $value->media;
                    $bizPhotoObj = $mediaArr[0];
                    $bizPhotoId = $bizPhotoObj->file_id;
                    $fileId = $bizPhotoObj->id;
                    $filePath = $bizPhotoObj->path;
                    $localDisc = $bizPhotoObj->disc;
                    $getLocalPathName = $bizPhotoObj->local_path;
                    $localPath = $this->mediaHandler->localPath($getLocalPathName, $localDisc);
                    $businessName = "<b>💼$value->name</b>";
                    $detail = $value->detail;
                    $contacts = $value->contacts;
                    $photosInfo = '';
                    $countMedia = count($mediaArr);
                    if($countMedia > 0) {
                        $photosInfo = "\n\nUploaded Photos: $countMedia";
                    }
                    $item = "
$businessName
ID: $bizId

$detail

$contacts $photosInfo
                    ";
                    array_push($textArr, $item);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }

            $text = implode("\n\n", $textArr);
        } else {
            $text = "<b>Business Gallery</b>\n
<blockquote>No Business listing is found at the moment.</blockquote>\n
You may add your business at /owner.";
        }

        $result = null;
        $path = null;

        if(!is_null($filePath)) {
            $fileExist = app('telegram_bot')->checkFileExists($filePath);
            if($fileExist) {
                $path = $bizPhotoId;
            }
            else {
                // set filepath to null
                try {
                    $getMedia = ProductMedia::find($fileId);
                    if(!is_null($getMedia)) {
                        $data = [
                            'path' => null
                        ];
                        $getMedia->update($data);
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        }
        else {
            // get file from local
            $path = $localPath;
        }

        if(!is_null($path)) {
            if(!is_null($getCallback) && ($getCallback !== 0 && $getCallback !== 1)) {
                $photo = [
                    'type' => 'photo',
                    'caption' => $text,
                    'media' => $path,
                    'parse_mode' => 'HTML'
                ];
                $media = app('telegram_bot')->serialize($photo);

                $this->content = [
                    'chat_id' => $this->chatId,
                    'message_id' => $getCallback,
                    'media' => $media,
                    'reply_markup' => $keyboardBuilder
                ];

                $result = app('telegram_bot')->editMessageMedia( $this->content);

            }
            else {
                $this->content = [
                    'chat_id' => $this->chatId,
                    'caption' => $text,
                    'photo' => $path,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboardBuilder
                ];
                $result = app('telegram_bot')->sendPhoto( $this->content);
            }
        }
        else {
            $data = [
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboardBuilder
            ];

            $this->content = [
                'chat_id' => $this->chatId,
                ...$data
            ];
            $result = app('telegram_bot')->sendMessage( $this->content);
        }

        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . $slideNumber,
            $this->cachePaginatorPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $this->parser->cachePut(
            $this->userId,
            config('telegram.commands.single_business.name'),
            $this->cacheViewPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $this->parser->cachePut(
            $this->userId,
            $bizId,
            $this->cacheGalleryIdPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        return response()->json($result, 200);
    }

    public function galleryView($businessId, $slideNumber)
    {
        $text = "Business Gallery";
        $bizName = '';
        $filePath = null;
        $mediaArray = [];

        $paginate = ProductMedia::where('product_id', $businessId)->orderBy('created_at', 'DESC')->offset($slideNumber - 1)->limit($this->telegramGalleryPhotosMax)->get();
        $len = ProductMedia::where('product_id', $businessId)->count();

        $keyboardBuilder = $this->inlineKeyboard->paginationSingleViewInlinekeyboard($len, $slideNumber, false, $this->telegramGalleryPhotosMax);

        if(!is_null($paginate)) {
            $bizId = null;
            $localPath = null;
            $bizPhotoId = null;
            $path = null;
            $type = 'photo';

            // prompt user
            $chatAction = config('telegram.chatactions.photo');
            $this->content['action'] = $chatAction;
            app('telegram_bot')->sendChatAction($this->content);

            foreach ($paginate as $key => $value) {
                try {
                    $biz = $value->product;
                    $bizId = $biz->id;
                    $bizName = $biz->name;
                    $fileId = $value->id;
                    $bizPhotoId = $value->file_id;
                    $filePath = $value->path;
                    $localDisc = $value->disc;
                    $getLocalPathName = $value->local_path;
                    $localPath = $this->mediaHandler->localPath($getLocalPathName, $localDisc);
                    $businessName = "<b>$bizName _photo $key</b>";
                    $setCaption = !is_null($value->name)?$value->name: $businessName;

                    if(!is_null($filePath)) {
                        $fileExist = app('telegram_bot')->checkFileExists($filePath);
                        if($fileExist) {
                            $path = $bizPhotoId;
                        }
                        else {
                            // set filepath to null
                            try {
                                $getMedia = ProductMedia::find($fileId);
                                if(!is_null($getMedia)) {
                                    $data = [
                                        'path' => null
                                    ];
                                    $getMedia->update($data);
                                }
                            } catch (\Throwable $th) {
                                //throw $th;
                            }
                        }
                    }
                    else {
                        // get file from local
                        $path = $localPath;
                    }

                    $item = [
                        'type' => $type,
                        'media' => $path,
                        'caption' => $setCaption,
                        'parse_mode' => 'HTML'
                    ];

                    $fileExist = app('telegram_bot')->checkFileExists($filePath);
                    if($fileExist) {
                        array_push($mediaArray, $item);
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                    $this->parser->log($th);
                }
            }

        } else {
            $text = "<b>Business Gallery</b>\n
<blockquote>No more Gallery was found at the moment.</blockquote>";
        }

        $result = null;
        $fileExist = false;

        if(count($mediaArray) > 0) {
            // set cache
            $this->parser->cachePut(
                $this->userId,
                $this->slideName . $slideNumber,
                $this->cachePaginatorPrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );

            $this->parser->cachePut(
                $this->userId,
                $this->galleryName,
                $this->cacheViewPrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );

            $this->parser->cachePut(
                $this->userId,
                $bizId,
                $this->cacheGalleryIdPrefix,
                $this->parser->addMinutes($this->cacheDuration)
            );

            $sumSlides = $this->telegramGalleryPhotosMax + $slideNumber - 1;
            $sumSlides = $sumSlides > $len? $len: $sumSlides;

            $data = [
                'chat_id' => $this->chatId,
                'text' => "<b>$bizName Gallery ($sumSlides/$len)</b>",
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboardBuilder
            ];

            $result = app('telegram_bot')->sendMessage( $data);

            $this->content = [
                'chat_id' => $this->chatId,
                'media' => $mediaArray,
                'protect_content' => false
            ];

            $result = app('telegram_bot')->sendMediaGroup( $this->content);

            return response()->json($result, 200);
        }
        else {
            $text = "<b>$bizName Gallery</b>\n
<blockquote>No photo was found.</blockquote>";
            $data = [
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboardBuilder
            ];

            $this->content = [
                'chat_id' => $this->chatId,
                ...$data
            ];
            $result = app('telegram_bot')->sendMessage( $this->content);
        }

        return response()->json($result, 200);
    }

    public function galleryViewX($businessId)
    {

        // set cache
        $this->parser->cachePut(
            $this->userId,
            config('telegram.commands.single_business.name'),
            $this->cacheViewPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        // $this->parser->cacheRemove(
        //     $this->userId,
        //     $this->cacheGalleryIdPrefix,
        // );

        $text = "Business Gallery";

        $biz = Products::with('media')->find($businessId);

        if(!is_null($biz)) {
            $mediaArray = [];
            $type = 'photo';

            try {
                $mediaArr = $biz->media;
                if(count($mediaArr) > 0) {
                    foreach ($mediaArr as $key => $value) {
                        $businessName = "<b>$biz->name _photo $key</b>";
                        $setCaption = !is_null($value->name)?$value->name: $businessName;
                        $item = [
                            'type' => $type,
                            'media' => $value->file_id,
                            'caption' => $setCaption,
                            'parse_mode' => 'HTML'
                        ];

                        $fileExist = app('telegram_bot')->checkFileExists($value->path);
                        if($fileExist) {
                            array_push($mediaArray, $item);
                        }
                    }

                    $countArr = count($mediaArray);

                    if($countArr > 0) {
                        $this->content = [
                            'chat_id' => $this->chatId,
                            'media' => $mediaArray,
                            'protect_content' => false
                        ];

                        $result = app('telegram_bot')->sendMediaGroup( $this->content);

                        return response()->json($result, 200);
                    }
                    else {
                        $text = "<b>$biz->name Gallery</b>\n
            <blockquote>No photos was found.</blockquote>";
                    }
                }
            } catch (\Throwable $th) {
                $this->parser->log($th);
                $text = "<b>$biz->name Gallery</b>\n
    <blockquote>Uploaded photos may no longer exist on Telegram</blockquote>";
            }
        } else {
            $text = "<b>Business Gallery</b>\n
<blockquote>No Business listing is found at the moment.</blockquote>\n
You may add your business at /owner.";
        }

        $keyboardBuilder = $this->onetimeKeyboard->cancelOnetimeKeyboard();

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

    public function searchById($businessId)
    {
        $text = "Business Gallery";
        $countMedia = 0;
        $bizPhotoId = null;
        $filePath = null;
        $bizId = null;

        $value = Products::with('media')->find($businessId);

        $keyboardBuilder = $this->inlineKeyboard->viewInlinekeyboard();

        if(!is_null($value)) {

            try {
                $bizId = $value->id;
                $mediaArr = $value->media;
                $bizPhotoObj = $mediaArr[0];
                $bizPhotoId = $bizPhotoObj->file_id;
                $filePath = $bizPhotoObj->path;
                $businessName = "<b>💼$value->name</b>";
                $detail = $value->detail;
                $contacts = $value->contacts;
                $photosInfo = '';
                $countMedia = count($mediaArr);
                if($countMedia > 0) {
                    $photosInfo = "\n\nUploaded Photos: $countMedia";
                }
                $text = "
$businessName
ID: $bizId

$detail

$contacts $photosInfo
                    ";
            } catch (\Throwable $th) {
                //throw $th;
            }
        } else {
            $text = "<b>Oops! Not Found</b>\n
<blockquote>The requested Business was not found.</blockquote>\n
You may search through the available /businesses listing to get the right ID.\n\n Use List view for faster search.";
        }

        $result = null;
        $fileExist = false;

        $fileExist = app('telegram_bot')->checkFileExists($filePath);

        if($fileExist) {
            $this->content = [
                'chat_id' => $this->chatId,
                'caption' => $text,
                'photo' => $bizPhotoId,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboardBuilder
            ];
            $result = app('telegram_bot')->sendPhoto( $this->content);
        }
        else {
            $data = [
                'text' => $text,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboardBuilder
            ];

            $this->content = [
                'chat_id' => $this->chatId,
                ...$data
            ];
            $result = app('telegram_bot')->sendMessage( $this->content);
        }

        // set cache
        $this->parser->cachePut(
            $this->userId,
            config('telegram.commands.single_business.name'),
            $this->cacheViewPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $this->parser->cachePut(
            $this->userId,
            $bizId,
            $this->cacheGalleryIdPrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        return response()->json($result, 200);
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

        // reset input
        $this->userAccount->setUserInputFalse($this->userId);

        $inputText = $this->messageCommandText;

        $text = "I may not understand your <b>Search Term</b>. Use /help to find quick tips.";

        if($type == $this->slideName . '2') {
            $text = "...";
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

        // save state
        $this->userAccount->setUserInputTrue($this->userId, $this->slideName . '7');

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }
}

