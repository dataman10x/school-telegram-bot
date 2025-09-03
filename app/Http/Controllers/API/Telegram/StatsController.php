<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Models\BroadcastMessages;
use App\Models\DialogMessages;
use App\Models\OpinionPolls;
use App\Models\OpinionPollUsers;
use App\Models\ProductMedia;
use App\Models\Products;
use App\Models\Reviews;
use App\Models\User;
use App\Models\VisitCounters;
use App\Models\Waitlist;

class StatsController
{
    private $parser;
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

    public function __construct($data)
    {
        $text = 'I could not find a match for the statistical request.';
        $this->data = $data;
        $this->parser = new Parser;
        $this->onetimeKeyboard = new OnetimeKeyboards;

        $this->userId = $this->data['user-id'];
        $this->userFirstname = $this->data['user-firstname'];
        $this->userUsername = $this->data['user-username'];
        $this->chatId = $this->data['chat-id'];
        $this->replyToMessageId = $this->data['message-id'];
        $this->messageCommandText = $this->data['message-command'];
        $this->messageTime = $this->data['message-date'];
        $this->messageTimeFormatted = $this->parser->formatUnixTime($this->messageTime);

        $this->content = [
            'text' => $text,
            'chat_id' => $this->chatId
        ];
    }

    public function index()
    {
        if($this->messageCommandText == config('telegram.commands.stats.name') || $this->messageCommandText == config('telegram.commands.stats.botref')) {
            $this->intro();
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function intro()
    {
        $getStats = $this->getStats();
        $usersCount = $this->parser->numberFormatter($getStats['users']);
        $businessCount = $this->parser->numberFormatter($getStats['business']);
        $businessMediaCount = $this->parser->numberFormatter($getStats['business_media']);
        $waitlistCount = $this->parser->numberFormatter($getStats['waitlist_subscribers']);
        $polls = $this->parser->numberFormatter($getStats['polls']);
        $pollSubscribers = $this->parser->numberFormatter($getStats['poll_subscribers']);
        $broadcast = $this->parser->numberFormatter($getStats['broadcasts']);
        $reviews = $this->parser->numberFormatter($getStats['reviews']);
        $demoVisits = $this->parser->numberFormatter($getStats['demo_visits']);
        $uniqueVisits = $this->parser->numberFormatter($getStats['unique_visits']);
        $totalVisits = $this->parser->numberFormatter($getStats['total_visits']);
        $faq = $this->parser->numberFormatter($getStats['faq']);
        $search = $this->parser->numberFormatter($getStats['search']);

        $text = "<b>Statistics</b> collected as below
<blockquote><b>Users</b>: $usersCount
<b>Unique Visits</b>: $uniqueVisits
<b>Total Visits</b>: $totalVisits
<b>Demo Watched times</b>: $demoVisits
<b>Wait List Subscribers</b>: $waitlistCount
<b>Reviews</b>: $reviews
<b>Businesses</b>: $businessCount
<b>Business Media</b>: $businessMediaCount
<b>Created Polls</b>: $polls
<b>Poll Susbcribers</b>: $pollSubscribers
<b>Frequently Asked Question</b>: $faq
<b>Searches by Keyword</b>: $search
<b>Broadcast Messages</b>: $broadcast
</blockquote>
";

        $keyboardBuilder = $this->onetimeKeyboard->aboutOnetimeKeyboard();

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

    public function getStats()
    {
        $usersCount = User::count();
        $waitlistCount = Waitlist::count();
        $pollsCount = OpinionPolls::count();
        $pollUsersCount = OpinionPollUsers::count();
        $faq = DialogMessages::count();
        $broadcastCount = BroadcastMessages::count();
        $reviewsCount = Reviews::count();
        $businessCount = Products::count();
        $businessMediaCount = ProductMedia::count();
        $visits = new VisitCounters;
        $visitDemoCount = $visits->demoTotal();
        $visitUniqueCount = $visits->uniqueTotal();
        $visitTotalCount = $visits->dailyTotal();
        $faqCount = DialogMessages::sum('views');

        $stats = [
            'users' => $usersCount,
            'business' => $businessCount,
            'business_media' => $businessMediaCount,
            'waitlist_subscribers' => $waitlistCount,
            'polls' => $pollsCount,
            'poll_subscribers' => $pollUsersCount,
            'faq' => $faq,
            'search' => $faqCount,
            'broadcasts' => $broadcastCount,
            'reviews' => $reviewsCount,
            'demo_visits' => $visitDemoCount,
            'unique_visits' => $visitUniqueCount,
            'total_visits' => $visitTotalCount
        ];

        return $stats;
    }
}

