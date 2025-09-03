<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\Reviews;

class DemoController
{
    private $parser;
    private $inlineKeyboard;
    private $userAccount;
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
    private $isCancelButton;
    private $isPrevButton;
    private $isNextButton;
    private $slideName;
    private $cachePrefix;
    private $cacheDuration;

    public function __construct($data)
    {
        $text = 'I see you are trying to interrupt the Demo. You may click Cancel button to exit Demo.';
        $this->data = $data;
        $this->parser = new Parser;
        $this->inlineKeyboard = new InlineKeyboards;
        $this->userAccount = new UserAccountController;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);

        $this->slideName = config('telegram.commands.demo.name');
        $this->cachePrefix = config('constants.cache_prefix.slide');
        $this->cacheDuration = 10;

        $this->isCancelButton = false;
        $this->isPrevButton = false;
        $this->isNextButton = true;

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];
    }

    public function index($type)
    {
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

        switch ($slideNumber) {
            case 0:
                $this->intro();
                break;
            case 1:
                $this->isCancelButton = true;
                $this->isPrevButton = true;
                $this->isNextButton = true;
                $this->slide1();
                break;
            case 2:
                $this->isCancelButton = true;
                $this->isPrevButton = true;
                $this->isNextButton = true;
                $this->slide2();
                break;
            case 3:
                $this->isCancelButton = true;
                $this->isPrevButton = true;
                $this->isNextButton = true;
                $this->slide3();
                break;
            case 4:
                $this->isCancelButton = true;
                $this->isPrevButton = true;
                $this->isNextButton = true;
                $this->slide4();
                break;
            case 5:
                $this->isCancelButton = true;
                $this->isPrevButton = true;
                $this->isNextButton = true;
                $this->slide5();
                break;
            case 6:
                $this->isCancelButton = true;
                $this->isPrevButton = true;
                $this->isNextButton = true;
                $this->slide6();
                break;
            case 7:
                $this->isCancelButton = true;
                $this->isPrevButton = true;
                $this->isNextButton = false;
                $this->last();
                break;

            default:
                # code...
                break;
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
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

        // update demo visits
        $this->userAccount->updateDemoVisits($this->userId);

        $text = "
Welcome to <b>Demo Simulation</b> of 7 Bullet Pointers of what I will enjoy when you opt in.
        ";

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

    public function slide1()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '1',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $text = "
Display Info about your business in form of <b>Text</b>, <b>Images</b>, <b>Videos</b>, and <b>Audio</b>.
Only an image was used as a sample in this case.
";

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

        $text = "
Accept data from your Bot users. Kindly enter your name in the input field, please.
        ";

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

        $text = "<b>Update Info</b> that does change over time, right from your Bot using Admin privileges.

You can send notification to your customers about latest updates you may have.";

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

    public function slide4()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '4',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $text = "List your <b>Products & Services</b> in the products section. Turn your Bot into store front.";

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

    public function slide5()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '5',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $text = "<b>Accept Payments</b> from your customers conveniently using secure and fast payment channel.";

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

    public function slide6()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '6',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $text = "<b>Accept Reviews</b> from satisfied customers and prompt them to join your update lists.

Receiving any info from your customers becomes a very stress free task. Nobody likes stress.";

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

    public function last()
    {
        // set cache
        $this->parser->cachePut(
            $this->userId,
            $this->slideName . '7',
            $this->cachePrefix,
            $this->parser->addMinutes($this->cacheDuration)
        );

        $text = "<b>You did it💃</b>

This is the end of the Demo. I hope you have been convinced to get a Bot like me for your business today.

If by any means you still got some doubts about your needing a Bot like me, kindly visit my /products section. I got jaw-dropping experience for you there.

Please, type in the input field to write us a review this Bot.
        ";

        $data = [
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        $this->content = [
            ...$this->content,
            ...$data
        ];
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

        $text = "<b>Recieved</b> with thanks.";

        if($type == $this->slideName . '2') {
            $text = "<b>$inputText</b>, you did great. We are glad you are getting a hang of this awesome Bot.

Please, click Next to return to the Demo.";
        }

        if($type == $this->slideName . '7') {
            $text = "Thank you for <b>writing us a review</b>. We will keep improving on serving you better.

Do join my waiting list to be among the first people to receive updates on releases of our Bots in development.";

        $keyboardBuilder = $this->inlineKeyboard->waitlistInlineKeyboard();

        // save review to DB
        $hashId = $this->parser->encoder($this->userId);
        $save = new Reviews;
        $save->id = $hashId;
        $save->note = $inputText;
        if($save->save()) {

        }
        else {
            $getReview = Reviews::where('user_id', $hashId)->first();
            if(!is_null($getReview)) {
                $isApproved = $getReview->is_approved;
                $sentDiff = $this->parser->diffHumans($getReview->created_at);
                $text1 = "I had received your review $sentDiff";
                $text2 = $isApproved? " , and it had been published.":'';
                $text3 = "\n<blockquote><b>Your Review:</b>\n$getReview->note</blockquote>\n";
                $text4 = "\nReviews could be sent only once to preserve integrity.";
                $text = "$text1 $text2 $text3 $text4";
            }
            else {
                $text = "Sorry, something went wrong with processing your review.";
            }
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

        // save state
        $this->userAccount->setUserInputTrue($this->userId, $this->slideName . '7');

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }
}

