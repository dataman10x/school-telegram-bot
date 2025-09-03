<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\UserAccountController;
use App\Models\BotAdmins;
use App\Models\BotCallbacks;
use App\Models\BotCandidates;
use App\Models\BotDialogMessages;
use App\Models\BotDirectMessages;
use App\Models\BotParents;
use App\Models\BotReviews;
use App\Models\BotUsers;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class SearchController
{
    private $parser;
    private $inlineKeyboard;
    private $data;
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
    private $searchCommand;
    private $viewCommand;
    private $reviewCommand;
    private $usersPerView;
    private $callbacks;
    private $callbackData;
    private $callbackType;

    public function __construct($data, $user)
    {
        $text = "I could not find a match for your entered text. You may consider sending us a <b>Direct Message /dm</b> instead";
        $this->data = $data;
        $this->user = $user;
        $this->parser = new Parser;
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

        $this->searchCommand = config('telegram.commands_button.search.name');
        $this->viewCommand = config('telegram.commands_button.view.name');
        $this->reviewCommand = config('telegram.commands_button.reviews.name');
        
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

    private function adminAuthorization()
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

    private function parentAuthorization()
    {
        $userAuth = false;
        
        try {
            $user = $this->user;
            $isSuperAdmin = $user->isSuperAdmin();
            $admin = $user->admin;
            $parent = $user->parent;

            if($isSuperAdmin || !is_null($admin)) { // allow super admin
                $userAuth = true;
            }

            if(!is_null($parent)) { // allow admin
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
        $searchTerm = str_replace($this->searchCommand . ' ', '', $this->messageCommandText);
        if($searchTerm == $this->messageCommandText) {  // try lowercase of command          
            $searchTerm = str_replace(strtolower($this->searchCommand) . ' ', '', $this->messageCommandText);
        }
        $searchTerm = trim($searchTerm);
        // $this->parser->log("$this->messageCommandText || $searchTerm");

        if($this->parser->isTelegramMatch($this->messageCommandText, '/' . strtolower($this->searchCommand), true) || 
            $this->parser->isTelegramMatch($this->messageCommandText, $this->searchCommand, true)) {
            $this->intro();
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, $this->searchCommand . ' keyword')) {
            $this->keyword($searchTerm);
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, $this->searchCommand . ' user')) {
            $this->users($searchTerm);
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, $this->searchCommand . ' admin')) {
            $this->admins($searchTerm);
        }
        else if($this->parser->isTelegramMatch($this->messageCommandText, $this->searchCommand . ' parent')) {
            $this->parents($searchTerm);
        }
        else {
            $this->notfound($searchTerm);
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
    
    public function intro()
    {
        $text = "Enter a search Term. You may view the Search Tips section in /help";

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

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

    public function notfound($searchTerm)
    {
        $text = "Your search term '$searchTerm' was not preceeded by category name: assessment, etc. You may view the Search Tips section in /help";

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

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

    public function keyword($searchTerm)
    {
        $title = "<b>Search Frequently Asked Question by keyword</b>\n\n No Result was found.";

        $strippedKeyword = str_replace('keyword ', '', $searchTerm);
        $strippedKeyword = trim($strippedKeyword);

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $pluck = BotDialogMessages::select('id', 'keywords', 'detail', 'views')->get();

        $textArr = [];

        try {
            $counter = 1;
            foreach ($pluck as $key => $value) {
                $id = $value->id;
                $keyword = $value->keywords;
                $detail = $value->detail;
                $views = $value->views;
                $vInK = $this->parser->numberFormatter($views);
                if(array_search($strippedKeyword, $keyword) !== false) {
                    $sub = "\n#$counter (👁️‍🗨️$vInK)\n $detail\n";
                    array_push($textArr, $sub);
                    $this->faqCounter($id);
                    $counter ++;
                }
            }
        } catch (\Throwable $th) {
            // $this->parser->log($th);
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>FAQ Search Result: $countArr</b>";
        }
        $text = "$title\n$body";

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

    public function faq($inputText)
    {
        $title = "Sorry, no match for your search '$inputText' was found. View /help for guide on performing better search.";

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $faq = BotDialogMessages::where('detail', 'like', "%$inputText%")->get();

        $textArr = [];

        try {
            $counter = 1;
            foreach ($faq as $value) {
                $id = $value->id;
                $detail = $value->detail;
                $views = $value->views;
                $vInK = $this->parser->numberFormatter($views);
                $sub = "\n#$counter (👁️‍🗨️$vInK)\n $detail\n";
                $this->faqCounter($id);
                array_push($textArr, $sub);
                $counter ++;
            }
        } catch (\Throwable $th) {
            $this->parser->log($th);
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>Search Result: $countArr</b>\n";
        }
        $text = "$title\n$body";

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

    private function faqCounter($id)
    {
        try {
            $faq = BotDialogMessages::whereId($id)->increment('views');
            // $getViews = $faq->views + 1;
            // $data = [
            //     'views' => $getViews
            // ];
            // $faq->update($data);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function dm()
    {
        $text = config('messages.send_email');

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

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

    public function users($searchTerm)
    {
        $this->adminAuthorization();

        $title = "<b>Users Search Result</b>\n\n No Result was found";

        $strippedKeyword = str_replace('user ', '', $searchTerm);
        $strippedKeyword = trim($strippedKeyword);

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $userData = BotUsers::whereId($strippedKeyword)->
            orWhere('firstname', 'like', "%$strippedKeyword%")->
            orWhere('lastname', 'like', "%$strippedKeyword%")->
            orWhere('email', 'like', "%$strippedKeyword%")->
            orWhere('phone', 'like', "%$strippedKeyword%") ->
            get();

        $textArr = [];

        try {
            foreach ($userData as $getUser) {
                $id = $getUser->id;
                $viewBtn = $this->viewCommand . '_' . $id;
                $firstname = $getUser->firstname;
                $lastname = $getUser->lastname;
                $email = $getUser->email;
                $phone = $getUser->phone;
                $sub = "\n💂‍♂️$firstname $lastname (ID: $id)\n📞$phone\n✉️$email\n/$viewBtn\n";
                array_push($textArr, $sub);
            }
        } catch (\Throwable $th) {
            $this->parser->log($th);
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>Users Search Result: $countArr</b>";
        }
        if($countArr > 0) {
            $title = "<b>Users Search Result: $countArr</b>";
            $text = "$title\n$body";
            
            $userAccount = new UserAccountController;
            $userAccount->setInput($this->userId, config('telegram.commands_button.users.name'));
        }
        else {
            $title = "<b>Users Search Result</b>";
            $text = "No Result was found on the search '$strippedKeyword'";
            $text = !empty($strippedKeyword) && $strippedKeyword !== 'user'? "$title\n\n$text": "$title\n\nSearch term was empty";
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

    public function admins($searchTerm)
    {
        $this->parentAuthorization();

        $title = "<b>BotAdmins Search Result</b>\n\n No Result was found";

        $strippedKeyword = str_replace('admin ', '', $searchTerm);
        $strippedKeyword = trim($strippedKeyword);

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $pluckIds = BotAdmins::pluck('id');
        $userData = BotUsers::whereIn('id', $pluckIds) ->
            where(function($query) use ($strippedKeyword) {
                $query->whereId($strippedKeyword)->
                orWhere('firstname', 'like', "%$strippedKeyword%")->
                orWhere('lastname', 'like', "%$strippedKeyword%")->
                orWhere('email', 'like', "%$strippedKeyword%")->
                orWhere('phone', 'like', "%$strippedKeyword%");
            }) ->
            get();

        $textArr = [];

        try {
            foreach ($userData as $getUser) {
                $id = $getUser->id;
                $viewBtn = $this->viewCommand . '_' . $id;
                $firstname = $getUser->firstname;
                $lastname = $getUser->lastname;
                $email = $getUser->email;
                $phone = $getUser->phone;
                $sub = "\n💂‍♂️$firstname $lastname (ID: $id)\n📞$phone\n✉️$email\n/$viewBtn\n";
                array_push($textArr, $sub);
            }
        } catch (\Throwable $th) {
            $this->parser->log($th);
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>BotAdmins Search Result: $countArr</b>";
        }
        if($countArr > 0) {
            $title = "<b>BotAdmins Search Result: $countArr</b>";
            $text = "$title\n$body";
            
            $userAccount = new UserAccountController;
            $userAccount->setInput($this->userId, config('telegram.admin_commands_button.admin.name'));
        }
        else {
            $title = "<b>BotAdmins Search Result</b>";
            $text = "No Result was found on the search '$strippedKeyword'";
            $text = !empty($strippedKeyword) && $strippedKeyword !== 'admin'? "$title\n\n$text": "$title\n\nSearch term was empty";
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

    public function parents($searchTerm)
    {
        $this->parentAuthorization();
        
        $title = "<b>BotParents Search Result</b>\n\n No Result was found.";

        $strippedKeyword = str_replace('parent ', '', $searchTerm);
        $strippedKeyword = trim($strippedKeyword);
        $this->parser->log($strippedKeyword);

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $pluckIds = BotParents::pluck('id');
        $userData = BotUsers::whereIn('id', $pluckIds) ->
            where(function($query) use ($strippedKeyword) {
                $query->whereId($strippedKeyword)->
                orWhere('firstname', 'like', "%$strippedKeyword%")->
                orWhere('lastname', 'like', "%$strippedKeyword%")->
                orWhere('email', 'like', "%$strippedKeyword%")->
                orWhere('phone', 'like', "%$strippedKeyword%");
            }) ->
            get();

        $textArr = [];

        try {
            foreach ($userData as $value) {
                $id = $value->id;
                $getUser = $value->user;
                $firstname = $getUser->firstname;
                $lastname = $getUser->lastname;
                $email = $getUser->email;
                $phone = $getUser->phone;
                $sub = "\n💂‍♂️$firstname $lastname (ID: $id)\n📞$phone\n✉️$email\n/view_$id\n";
                array_push($textArr, $sub);
            }
        } catch (\Throwable $th) {
            $this->parser->log($th);
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>BotParents Search Result: $countArr</b>";
            $text = "$title\n$body";
            
            $userAccount = new UserAccountController;
            $userAccount->setInput($this->userId, config('telegram.commands_button.parents.name'));
        }
        else {
            $title = "<b>BotParents Search Result</b>";
            $text = "No Result was found on the search '$strippedKeyword'";
            $text = !empty($strippedKeyword) && $strippedKeyword !== 'parent'? "$title\n\n$text": "$title\n\nSearch term was empty";
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

    public function assessmentById($id)
    {
        // $this->parentAuthorization();
        
        $title = "<b>Assessment Search Result</b>\n\n No Result was found.";

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $assessments = DB::table('assessments')->where('id', $id)->get()->toArray();

        $textArr = [];

        try {
            $assessmentArr = $this->parser->collectionToArray($assessments);
            if(count($assessmentArr??[]) > 0) {
                $assessmentData = $assessmentArr[0];
                $title = $assessmentData['title'];
                $description = $assessmentData['description'];
                $duration = $assessmentData['duration'];
                $lock = is_null($assessmentData['auth_code'])?'active': '--none--';
                $typeQuestion = $assessmentData['type'];
                $attempts = $assessmentData['attempts'];
                $startAt = $assessmentData['start_at'];
                $endAt = $assessmentData['end_at'];
                $schoolId = $assessmentData['school_id'];
                $classArr = $assessmentData['allowed_classes'];
                $school = '';
                $classList = [];
                $classStr = '';
                $startAtStr = $this->parser->formatDate($startAt, $this->parser->format1(), $this->parser->format7c());
                $endAtStr = $this->parser->formatDate($endAt, $this->parser->format1(), $this->parser->format7c());

                $textStart = "⚠️<b>Assessment Search Result (ID: $id)</b>\n";
                $getTitle = "<b>$title</b>";
                $getDesc = "<i>$description</i>";
                $getDuration = "<b>Duration:</b> $duration mins";
                $getLock = "<b>Lock code:</b> $lock";
                $getType = "<b>Question Type:</b> $typeQuestion";
                $getAttempts = "<b>Attempts:</b> $attempts";
                $getStartAt = "<b>Starts:</b> $startAtStr";
                $getEndAt = "<b>Ends:</b> $endAtStr";

                try {
                    $schoolObj = DB::table('schools')->where('id', $schoolId)->get()->toArray();
                    $schoolsArr = $this->parser->collectionToArray($schoolObj);
                    if(count($schoolsArr??[]) > 0) {
                        $school = $schoolsArr[0]['label'];
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }

                try {
                    $classes = json_decode($classArr);
                    foreach ($classes as $val) {
                        $classObj = DB::table('school_classes')->where('id', $val)->get()->toArray();
                        $classArr = $this->parser->collectionToArray($classObj);
                        if(count($classArr??[]) > 0) {
                            $className = $classArr[0]['label'];
                            array_push($classList, $className);
                        }
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }

                if(count($classList)) {
                    $classStr = implode('; ', $classList);
                }

                $getSchool = "<b>School:</b> $school";
                $getClasses = "<b>Allowed Classes:</b> $classStr";
                $link = env('ROOT_URL') . "/cbt/assessments/$id";

                if(!is_null($startAt)) {
                    $link = $this->parser->greaterThan($startAt)? "--NOT YET ACTIVE--": $link;
                }                

                if(!is_null($endAt)) {
                    $link = $this->parser->lessThan($endAt)? "--EXPIRED!--": $link;
                }

                $text = "$textStart\n$getTitle\n$getDesc\n\n$link\n\n$getDuration\n$getStartAt\n$getEndAt\n$getType\n$getAttempts\n$getLock\n$getSchool\n$getClasses";
                
                $buttonArray = [
                    'first' => [
                        'name' => config('telegram.commands_button.help.name'),
                        'label' => config('telegram.commands_button.help.label')
                    ],
                    'return' => [
                        'name' => config('telegram.commands_button.exit.name'),
                        'label' => config('telegram.commands_button.exit.label')
                    ],
                ];
                
                $keyboardBuilder = $this->inlineKeyboard->twoButtonsInlinekeyboard($buttonArray);
            }
            else {
                $text = "No Assessment with ID: $id was found";
            }
        } catch (\Throwable $th) {
            $this->parser->log($th);
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

    public function subscribe()
    {
        $text = "You do not have an active subscription to receive reports on assessments";
        $textArr = [];

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $parent = BotParents::whereId($this->userHashId)->first();
        $candidate = BotCandidates::whereId($this->userHashId)->first();


        try {
            if(!is_null($parent)) {
                $isReport = $parent->report_active;
                $res = "You are already have active subscription to receive assessment reports as Parent.";
                if(!$isReport) {
                    $rowData = [
                        'report_active' => true
                    ];
                    $parent->update($rowData);
                    $res = "You have been subscribed to receive assessment reports as Parent of a student.";
                }
                array_push($textArr, $res);
            }
        } catch (\Throwable $th) {
            // $this->parser->log($th);
        }
        
        try {
            if(!is_null($candidate)) {
                $isReport = $candidate->report_active;
                $res = "You are already have active subscription to receive assessment reports as Student.";
                if(!$isReport) {
                    $rowData = [
                        'report_active' => true
                    ];
                    $candidate->update($rowData);
                    $res = "You have been subscribed to receive assessment reports as a Student.";
                }
                array_push($textArr, $res);
            }
        } catch (\Throwable $th) {
            // $this->parser->log($th);
        }

        if($textArr > 0) {
            $text = implode("\n\n", $textArr);
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

    public function unsubscribe()
    {
        $text = "No active subscription to receive reports on assessments was found!";
        $textArr = [];

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $parent = BotParents::whereId($this->userHashId)->first();
        $candidate = BotCandidates::whereId($this->userHashId)->first();


        try {
            if(!is_null($parent)) {
                $isReport = $parent->report_active;
                $res = "You do not have an active subscription to receive assessment reports as Parent. You may subscribe now.";
                if($isReport) {
                    $rowData = [
                        'report_active' => false
                    ];
                    $parent->update($rowData);
                    $res = "You have been unsubscribed to receive assessment reports as Parent of a student.";
                }
                array_push($textArr, $res);
            }
        } catch (\Throwable $th) {
            // $this->parser->log($th);
        }
        
        try {
            if(!is_null($candidate)) {
                $isReport = $candidate->report_active;
                $res = "You do not have an active subscription to receive assessment reports as Student. you may subscribe now.";
                if($isReport) {
                    $rowData = [
                        'report_active' => false
                    ];
                    $candidate->update($rowData);
                    $res = "You have been unsubscribed to receive assessment reports as a Student.";
                }
                array_push($textArr, $res);
            }
        } catch (\Throwable $th) {
            // $this->parser->log($th);
        }

        if($textArr > 0) {
            $text = implode("\n\n", $textArr);
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

    public function review($message)
    {
        $text = "Your review could not be saved. Something went wrong";
        $textArr = [];

        $keyboardBuilder = $this->inlineKeyboard->appInlineKeyboard($this->searchCommand);

        $getMessage = $this->parser->stringReplaceFirst($this->reviewCommand, '', $message);
        
        if($this->parser->isTelegramMatch($message, $this->reviewCommand, true)) {
            $reviews = BotReviews::whereNotNull('approved_at')->orderBy('updated_at', 'desc')->limit(config('constants.review_per_view'))->get();

            if(!is_null($reviews) && count($reviews??[] > 0)) {
                try {
                    foreach ($reviews as $review) {
                        $getUser = $review->user;
                        $updatedAt = $review->updated_at;
                        $note = $review->note;
                        $fullname = "$getUser->firstname $getUser->lastname";
                        $getTime = $this->parser->formatDate($updatedAt, $this->parser->format1(), $this->parser->format7());
                        $timeDiff = $this->parser->diffHumans($updatedAt);
                        $newText = "<b>$fullname</b> on $getTime ($timeDiff) said:\n$note";
                        array_push($textArr, $newText);
                    }
                } catch (\Throwable $th) {
                    $newText = "Something went wrong while trying to retrieve Reviews";
                    array_push($textArr, $newText);
                }
            }
            else {
                $newText = "No Review was found at the moment. You may go ahead to write us a review.\n\nKindly preceed your review message with the word: Reviews";
                array_push($textArr, $newText);
            }
        }
        else if($getMessage == '') {
            $newText = "You need to enter your review message after the word: Reviews";
            array_push($textArr, $newText);
        }
        else if(str_starts_with($message, '/')) {
            $newText = "Kindly preceed your review message with the word: Reviews";
            array_push($textArr, $newText);
        }
        else {
            $review = BotReviews::whereId($this->userHashId)->first();

            try {
                if(!is_null($review)) {
                    $updatedAt = $review->updated_at;
                    $approvedAt = $review->approved_at;
                    $getTime = $this->parser->formatDate($updatedAt, $this->parser->format1(), $this->parser->format7c());
                    $timeDiff = $this->parser->diffHumans($updatedAt);

                    $newText = "You sent us a review on $getTime ($timeDiff), and was updated as requested😀";
                    array_push($textArr, $newText);
                       
                    $newReview = [
                        'note' => $getMessage,
                        'approved_at' => null
                    ];
                    $review->update($newReview);

                    if(!is_null($approvedAt)) { 
                        $newText = "This change needs another approval from an Admin to be listed publicly.";
                    }
                    else {
                        $newText = "Your Review is yet to be approved by an Admin.";
                    }
                    array_push($textArr, $newText);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

            try {
                if(is_null($review)) {
                    $saveReview = new BotReviews;
                    $saveReview->id = $this->userHashId;
                    $saveReview->note = $getMessage;
                    $saveReview->save();
                    $newText = "We appreciate your Review. Thank you very much😇.";
                    array_push($textArr, $newText);
                }
            } catch (\Throwable $th) {
                //throw $th;
            }

        }        

        if(count($textArr) > 0) {
            $text = implode("\n\n", $textArr);
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
}

