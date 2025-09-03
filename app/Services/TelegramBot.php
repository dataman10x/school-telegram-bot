<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBot
{
    protected $headers;
    protected $url;
    protected $urlFile;

    public function __construct()
    {
        $this->url = config('constants.telegram_bot_path');
        $this->urlFile = config('constants.telegram_file_path');
        $this->setHeaders();
    }

    protected function setHeaders()
    {
        $this->headers = [
            "Content-Type" => "application/json",
            "Accept"       => "application/json"
        ];
    }

    public function endpoint($api, array $content, $post = true, $baseUrl = null)
    {
        $getUrl = $this->url.'/'.$api;

        if(!is_null($baseUrl)) {
            $getUrl = $baseUrl.'/'.$api;
        }

        if ($post) {
            $reply = $this->sendAPIRequest($getUrl, $content);
        } else {
            $reply = $this->sendAPIRequest($getUrl, [], false);
        }

        return $reply;
    }

    protected function sendApiRequest($url, $params)
    {
        return Http::withHeaders($this->headers)->post($url, $params);
    }


    /**
     * Functions culled from
     * https://github.com/Eleirbag89/TelegramBotPHP/blob/master/Telegram.php
     */

    public function getMe()
    {
        return $this->endpoint('getMe', [], false);
    }

    public function sendMessage(array $content)
    {
        // default result array
        $result = [
            'success' => false,
            'body' => []
        ];

        // send the request
        try {
            $result = $this->endpoint('sendMessage', $content);
        } catch (\Throwable $th) {
            //throw $th;
            $result['error'] = $th->getMessage();
        }

        // Log::info("TelegramBot->sendMessage->result", ['result'=> $result]);
        return $result;
    }

    public function copyMessage(array $content)
    {
        return $this->endpoint('copyMessage', $content);
    }

    public function forwardMessage(array $content)
    {
        return $this->endpoint('forwardMessage', $content);
    }

    public function sendMediaGroup(array $content)
    {
        return $this->endpoint('sendMediaGroup', $content);
    }

    /**
     * build mediaGroup
     */
    public function mediaForMediaGroup(string $fileId, string $caption, string $type = 'photo', string $parseMode = 'HTML')
    {
        $replyMarkup = [
            'type'          => $type,
            'media' => $fileId,
            'caption'   => $caption,
            'parse_mode'         => $parseMode,
        ];
        $encodedMarkup = json_encode($replyMarkup, true);

        return $encodedMarkup;
    }

    public function serialize(array $content)
    {
        $encodedMarkup = json_encode($content, true);

        return $encodedMarkup;
    }

    public function sendPhoto(array $content)
    {
        return $this->endpoint('sendPhoto', $content);
    }

    public function sendAudio(array $content)
    {
        return $this->endpoint('sendAudio', $content);
    }

    public function sendDocument(array $content)
    {
        return $this->endpoint('sendDocument', $content);
    }

    public function sendAnimation(array $content)
    {
        return $this->endpoint('sendAnimation', $content);
    }

    public function sendSticker(array $content)
    {
        return $this->endpoint('sendSticker', $content);
    }

    public function sendVideo(array $content)
    {
        return $this->endpoint('sendVideo', $content);
    }

    public function sendVideoNote(array $content)
    {
        return $this->endpoint('sendVideoNote', $content);
    }

    public function sendVoice(array $content)
    {
        return $this->endpoint('sendVoice', $content);
    }

    public function sendLocation(array $content)
    {
        return $this->endpoint('sendLocation', $content);
    }

    public function sendVenue(array $content)
    {
        return $this->endpoint('sendVenue', $content);
    }

    public function sendContact(array $content)
    {
        return $this->endpoint('sendContact', $content);
    }

    public function sendPoll(array $content)
    {
        return $this->endpoint('sendPoll', $content);
    }

    public function stopPoll(array $content)
    {
        return $this->endpoint('stopPoll', $content);
    }

    public function sendDice(array $content)
    {
        return $this->endpoint('sendDice', $content);
    }


    public function deleteMessage(array $content)
    {
        return $this->endpoint('deleteMessage', $content);
    }

    public function sendChatAction(array $content)
    {
        return $this->endpoint('sendChatAction', $content);
    }

    public function getFile($file_id)
    {
        $content = ['file_id' => $file_id];

        return $this->endpoint('getFile', $content);
    }

    public function getChat(array $content)
    {
        return $this->endpoint('getChat', $content);
    }

    /// Get chat Administrators
    public function getChatAdministrators(array $content)
    {
        return $this->endpoint('getChatAdministrators', $content);
    }

    public function getChatMemberCount(array $content)
    {
        return $this->endpoint('getChatMemberCount', $content);
    }

    public function getChatMember(array $content)
    {
        return $this->endpoint('getChatMember', $content);
    }

    // need to enable this on BotFather
    public function answerInlineQuery(array $content)
    {
        return $this->endpoint('answerInlineQuery', $content);
    }

    public function setMyCommands(array $content)
    {
        return $this->endpoint('setMyCommands', $content);
    }

    public function deleteMyCommands(array $content)
    {
        return $this->endpoint('deleteMyCommands', $content);
    }

    public function getMyCommands(array $content)
    {
        return $this->endpoint('getMyCommands', $content);
    }

    public function setChatMenuButton(array $content)
    {
        return $this->endpoint('setChatMenuButton', $content);
    }

    public function getChatMenuButton(array $content)
    {
        return $this->endpoint('getChatMenuButton', $content);
    }

    /// Adminstrators rights
    public function setMyDefaultAdministratorRights(array $content)
    {
        return $this->endpoint('setMyDefaultAdministratorRights', $content);
    }

    public function getMyDefaultAdministratorRights(array $content)
    {
        return $this->endpoint('getMyDefaultAdministratorRights', $content);
    }

    public function editMessageText(array $content)
    {
        return $this->endpoint('editMessageText', $content);
    }

    public function editMessageCaption(array $content)
    {
        return $this->endpoint('editMessageCaption', $content);
    }

    public function editMessageMedia(array $content)
    {
        return $this->endpoint('editMessageMedia', $content);
    }

    public function editMessageReplyMarkup(array $content)
    {
        return $this->endpoint('editMessageReplyMarkup', $content);
    }

    public function downloadFile($telegram_file_path, $local_file_path)
    {
        $file_url = $this->urlFile.'/'.$telegram_file_path;
        $in = fopen($file_url, 'rb');
        $out = fopen($local_file_path, 'wb');

        while ($chunk = fread($in, 8192)) {
            fwrite($out, $chunk, 8192);
        }
        fclose($in);
        fclose($out);
    }

    public function checkFileExists($telegram_file_path) {
        $status = false;
        $url = "$this->urlFile/$telegram_file_path";

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($code == 200) {
                $status = true;
            } else {
                $status = false;
            }
            curl_close($ch);
        } catch (\Throwable $th) {
        }

        return $status;
    }

    public function checkFileExistsX($telegram_file_path) {
        $status = false;
        $url = "$this->urlFile/$telegram_file_path";

        try {
            $handle = @fopen($url, 'r');
            if (!$handle) {
                $status = false;
            }
            else {
                $status = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $status;
    }


    /// Set a custom keyboard

    /** This object represents a custom keyboard with reply options
     * \param $options Array of Array of String; Array of button rows, each represented by an Array of Strings
     * \param $onetime Boolean Requests clients to hide the keyboard as soon as it's been used. Defaults to false.
     * \param $resize Boolean Requests clients to resize the keyboard vertically for optimal fit (e.g., make the keyboard smaller if there are just two rows of buttons). Defaults to false, in which case the custom keyboard is always of the same height as the app's standard keyboard.
     * \param $selective Boolean Use this parameter if you want to show the keyboard to specific users only. Targets: 1) users that are @mentioned in the text of the Message object; 2) if the bot's message is a reply (has reply_to_message_id), sender of the original message.
     * \return the requested keyboard as Json.
     */
    public function buildKeyBoard(array $options, $onetime = false, $resize = true, $selective = true)
    {
        $replyMarkup = [
            'keyboard'          => $options,
            'one_time_keyboard' => $onetime,
            'resize_keyboard'   => $resize,
            'selective'         => $selective,
        ];
        $encodedMarkup = json_encode($replyMarkup, true);

        return $encodedMarkup;
    }

    /// Set an InlineKeyBoard

    /** This object represents an inline keyboard that appears right next to the message it belongs to.
     * \param $options Array of Array of InlineKeyboardButton; Array of button rows, each represented by an Array of InlineKeyboardButton
     * \return the requested keyboard as Json.
     */
    public function buildInlineKeyBoard(array $options)
    {
        $replyMarkup = [
            'inline_keyboard' => $options,
        ];
        $encodedMarkup = json_encode($replyMarkup, true);

        return $encodedMarkup;
    }

    /// Create an InlineKeyboardButton

    /** This object represents one button of an inline keyboard. You must use exactly one of the optional fields.
     * \param $text String; Array of button rows, each represented by an Array of Strings
     * \param $url String Optional. HTTP url to be opened when button is pressed
     * \param $callback_data String Optional. Data to be sent in a callback query to the bot when button is pressed
     * \param $switch_inline_query String Optional. If set, pressing the button will prompt the user to select one of their chats, open that chat and insert the bot‘s username and the specified inline query in the input field. Can be empty, in which case just the bot’s username will be inserted.
     * \param $switch_inline_query_current_chat String Optional. Optional. If set, pressing the button will insert the bot‘s username and the specified inline query in the current chat's input field. Can be empty, in which case only the bot’s username will be inserted.
     * \param $callback_game  String Optional. Description of the game that will be launched when the user presses the button.
     * \param $pay  Boolean Optional. Specify True, to send a <a href="https://core.telegram.org/bots/api#payments">Pay button</a>.
     * \return the requested button as Array.
     */
    public function buildInlineKeyboardButton(
        $text,
        $url = '',
        $callback_data = '',
        $switch_inline_query = null,
        $switch_inline_query_current_chat = null,
        $callback_game = '',
        $pay = ''
    ) {
        $replyMarkup = [
            'text' => $text,
        ];
        if ($url != '') {
            $replyMarkup['url'] = $url;
        } elseif ($callback_data != '') {
            $replyMarkup['callback_data'] = $callback_data;
        } elseif (!is_null($switch_inline_query)) {
            $replyMarkup['switch_inline_query'] = $switch_inline_query;
        } elseif (!is_null($switch_inline_query_current_chat)) {
            $replyMarkup['switch_inline_query_current_chat'] = $switch_inline_query_current_chat;
        } elseif ($callback_game != '') {
            $replyMarkup['callback_game'] = $callback_game;
        } elseif ($pay != '') {
            $replyMarkup['pay'] = $pay;
        }

        return $replyMarkup;
    }

    /// Create a KeyboardButton

    /** This object represents one button of an inline keyboard. You must use exactly one of the optional fields.
     * \param $text String; Array of button rows, each represented by an Array of Strings
     * \param $request_contact Boolean Optional. If True, the user's phone number will be sent as a contact when the button is pressed. Available in private chats only
     * \param $request_location Boolean Optional. If True, the user's current location will be sent when the button is pressed. Available in private chats only
     * \return the requested button as Array.
     */
    public function buildKeyboardButton($text, $request_contact = false, $request_location = false)
    {
        $replyMarkup = [
            'text'             => $text,
            'request_contact'  => $request_contact,
            'request_location' => $request_location,
        ];

        return $replyMarkup;
    }

    /// Hide a custom keyboard

    /** Upon receiving a message with this object, Telegram clients will hide the current custom keyboard and display the default letter-keyboard. By default, custom keyboards are displayed until a new keyboard is sent by a bot. An exception is made for one-time keyboards that are hidden immediately after the user presses a button.
     * \param $selective Boolean Use this parameter if you want to show the keyboard to specific users only. Targets: 1) users that are @mentioned in the text of the Message object; 2) if the bot's message is a reply (has reply_to_message_id), sender of the original message.
     * \return the requested keyboard hide as Array.
     */
    public function buildKeyBoardHide($selective = true)
    {
        $replyMarkup = [
            'remove_keyboard' => true,
            'selective'       => $selective,
        ];
        $encodedMarkup = json_encode($replyMarkup, true);

        return $encodedMarkup;
    }

    /// Display a reply interface to the user
    /* Upon receiving a message with this object, Telegram clients will display a reply interface to the user (act as if the user has selected the bot‘s message and tapped ’Reply'). This can be extremely useful if you want to create user-friendly step-by-step interfaces without having to sacrifice privacy mode.
     * \param $selective Boolean Use this parameter if you want to show the keyboard to specific users only. Targets: 1) users that are @mentioned in the text of the Message object; 2) if the bot's message is a reply (has reply_to_message_id), sender of the original message.
     * \return the requested force reply as Array
     */
    public function buildForceReply($selective = true)
    {
        $replyMarkup = [
            'force_reply' => true,
            'selective'   => $selective,
        ];
        $encodedMarkup = json_encode($replyMarkup, true);

        return $encodedMarkup;
    }

    // Payments
    /// Send an invoice

    /**
     * See <a href="https://core.telegram.org/bots/api#sendinvoice">sendInvoice</a> for the input values
     * \param $content the request parameters as array
     * \return the JSON Telegram's reply.
     */
    public function sendInvoice(array $content)
    {
        return $this->endpoint('sendInvoice', $content);
    }

    /// Answer a shipping query

    /**
     * See <a href="https://core.telegram.org/bots/api#answershippingquery">answerShippingQuery</a> for the input values
     * \param $content the request parameters as array
     * \return the JSON Telegram's reply.
     */
    public function answerShippingQuery(array $content)
    {
        return $this->endpoint('answerShippingQuery', $content);
    }

    /// Answer a PreCheckout query

    /**
     * See <a href="https://core.telegram.org/bots/api#answerprecheckoutquery">answerPreCheckoutQuery</a> for the input values
     * \param $content the request parameters as array
     * \return the JSON Telegram's reply.
     */
    public function answerPreCheckoutQuery(array $content)
    {
        return $this->endpoint('answerPreCheckoutQuery', $content);
    }

    /// Set Passport data errors

    /**
     * See <a href="https://core.telegram.org/bots/api#setpassportdataerrors">setPassportDataErrors</a> for the input values
     * \param $content the request parameters as array
     * \return the JSON Telegram's reply.
     */
    public function setPassportDataErrors(array $content)
    {
        return $this->endpoint('setPassportDataErrors', $content);
    }

}
