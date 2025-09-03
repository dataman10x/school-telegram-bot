<?php
namespace App\Http\Controllers\API\Telegram;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Models\DialogMessages;
use App\Models\DirectMessages;
use App\Models\Products;

class SearchXController
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
        $text = "I could not find a match your entered text. You may consider sending us a <b>Direct Message /dm</b> instead";
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
        if($this->parser->isTelegramMatch($this->messageCommandText, config('telegram.commands_button.search.name'), true)) {
            $this->intro();
        }

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function intro()
    {
        $text = "Enter a search Term. You may view the Search Tips section in /help";

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

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function name()
    {
        $title = "<b>Search Businesses by Name</b>\n\n No Result was found.";

        $strippedKeyword = str_replace('name', '', $this->messageCommandText);
        $strippedKeyword = trim($strippedKeyword);

        $keyboardBuilder = $this->onetimeKeyboard->aboutOnetimeKeyboard();

        $biz = Products::where('name', 'like', "%$strippedKeyword%")->get();

        $textArr = [];

        try {
            foreach ($biz as $value) {
                $id = $value->id;
                $name = $value->name;
                $contacts = $value->contacts;
                $sub = "\n🏬$name (ID: $id)\n $contacts\n";
                array_push($textArr, $sub);
            }
        } catch (\Throwable $th) {
            $this->parser->log($th);
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>Businesses Name Search Result: $countArr</b>\nTo View more details, insert the ID in the input field.";
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

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function info()
    {
        $title = "<b>Search Businesses by Description</b>\n\n No Result was found.";

        $strippedKeyword = str_replace('info', '', $this->messageCommandText);
        $strippedKeyword = trim($strippedKeyword);

        $keyboardBuilder = $this->onetimeKeyboard->aboutOnetimeKeyboard();

        $biz = Products::where('detail', 'like', "%$strippedKeyword%")->get();

        $textArr = [];

        try {
            foreach ($biz as $value) {
                $id = $value->id;
                $name = $value->name;
                $contacts = $value->contacts;
                $sub = "\n🏬$name (ID: $id)\n $contacts\n";
                array_push($textArr, $sub);
            }
        } catch (\Throwable $th) {
            $this->parser->log($th);
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>Businesses Description Search Result: $countArr</b>\nTo View more details, insert the ID in the input field.";
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

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function question()
    {
        $title = "<b>Search Frequently Asked Question by keyword</b>\n\n No Result was found.";

        $strippedKeyword = str_replace('question', '', $this->messageCommandText);
        $strippedKeyword = trim($strippedKeyword);

        $keyboardBuilder = $this->onetimeKeyboard->aboutOnetimeKeyboard();

        $pluck = DialogMessages::select('id', 'keywords', 'detail', 'views')->get();

        $textArr = [];

        try {
            foreach ($pluck as $key => $value) {
                $id = $value->id;
                $keyword = $value->keywords;
                $detail = $value->detail;
                $views = $value->views;
                $vInK = $this->parser->numberFormatter($views);
                if(array_search($strippedKeyword, $keyword) !== false) {
                    $sub = "\n👁️‍🗨️$vInK\n $detail\n";
                    array_push($textArr, $sub);
                    $this->faqCounter($id);
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

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function dm()
    {

        $keyboardBuilder = $this->onetimeKeyboard->aboutOnetimeKeyboard();

        $strippedKeyword = str_replace('dm', '', $this->messageCommandText);
        $strippedKeyword = trim($strippedKeyword);

        $title = "<b>Search $strippedKeyword Direct Messages</b>";

        $hashId = $this->parser->encoder($this->userId);

        $messages = [];

        $textArr = [];

        if($strippedKeyword == 'reply') {
            $messages = DirectMessages::where('user_id', $hashId)->whereNotNull('reply')->get();
        }
        else if($strippedKeyword == 'unreply') {
            $messages = DirectMessages::where('user_id', $hashId)->whereNull('reply')->get();
        }
        else {
            $messages = DirectMessages::where('user_id', $hashId)->get();
        }


        try {
            foreach ($messages as $msg) {
                $sentMsg = $msg->message;
                $reply = $msg->reply;
                $createdAt = $msg->created_at;
                $formatDate = $this->parser->formatDate($createdAt, $this->parser->format1(), $this->parser->format6c());
                $diffDate = $this->parser->diffHumans($createdAt);
                $getReply = !is_null($reply)? "<blockquote>$reply</blockquote>": '';
                $getMessage = "✉️ $sentMsg";
                $msgStr = "\n** sent on $formatDate ($diffDate)\n$getMessage $getReply\n";

                array_push($textArr, $msgStr);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>Direct Messages: $countArr</b>";
        }
        else {
            $body = "No message was found";
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

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    public function faq()
    {
        $title = "Hello there, this is the <b>Help Centre of BizPlugBot</b> for listing Businesses for FREE
\n <b>Get /help</b>
<b>Vote in /polls</b>
<b>Join updates /waitlist</b>
<b>Add your business as /owner</b>\n
Not a Business Owner, please search /businesses of interest\n
If these resources didn't help, kindly ask your question again through our direct message /dm";

        $strippedKeyword = $this->messageCommandText;
        $strippedKeyword = trim($strippedKeyword);

        $keyboardBuilder = $this->onetimeKeyboard->aboutOnetimeKeyboard();

        $faq = DialogMessages::where('detail', 'like', "%$strippedKeyword%")->get();

        $textArr = [];

        try {
            foreach ($faq as $value) {
                $id = $value->id;
                $detail = $value->detail;
                $views = $value->views;
                $vInK = $this->parser->numberFormatter($views);
                $sub = "\n👁️‍🗨️$vInK\n $detail\n";
                array_push($textArr, $sub);
            }
        } catch (\Throwable $th) {
            $this->parser->log($th);
        }

        $body = implode('', $textArr);
        $countArr = count($textArr);
        if($countArr > 0) {
            $title = "<b>Ask a Question Search Result: $countArr</b>\n";
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

        $result = app('telegram_bot')->sendMessage( $this->content);
        return response()->json($result, 200);
    }

    private function faqCounter($id)
    {
        $faq = DialogMessages::find($id);

        try {
            $getViews = $faq->views + 1;
            $data = [
                'views' => $getViews
            ];
            $faq->update($data);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}

