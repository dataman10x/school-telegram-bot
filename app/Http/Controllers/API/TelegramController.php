<?php

namespace App\Http\Controllers\API;

use App\Classes\Parser;
use App\Http\Controllers\API\Telegram\AboutController;
use App\Http\Controllers\API\Telegram\Admin\AdminController;
use App\Http\Controllers\API\Telegram\Admin\SuperAdminController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Telegram\ContactsController;
use App\Http\Controllers\API\Telegram\ErrorController;
use App\Http\Controllers\API\Telegram\HelpController;
use App\Http\Controllers\API\Telegram\InfoController;
use App\Http\Controllers\API\Telegram\IntroController;
use App\Http\Controllers\API\Telegram\MenuController;
use App\Http\Controllers\API\Telegram\MessageReaction;
use App\Http\Controllers\API\Telegram\ParentsController;
use App\Http\Controllers\API\Telegram\SearchController;
use App\Http\Controllers\API\Telegram\StatsController;
use App\Http\Controllers\API\Telegram\UpdateController;
use App\Http\Controllers\API\Telegram\UsersController;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    private $content;
    /**
     *
     * @return void
     */
    public function inbound(Request $request)
    {
        if(is_null($request)) {
            return response()->json($request, 200);
        }

        $botLabel = env('TELEGRAM_BOT_LABEL');

        $parser = new Parser;
        $userAccount = new UserAccountController;
        // $parser->log($request->all());

        $inboundArr = $parser->telegramInbound($request);
        // $parser->log($inboundArr);

        $errorC = new ErrorController($inboundArr);

        // maintence mode
        if(env('MAINTENANCE_MODE')) {
            $offlineText = config('messages.offline');
            return $errorC->error($offlineText);
        }

        try {
            $chatId = $inboundArr['chat-id'];
            $userId = $inboundArr['user-id'];
            $userPhone = $inboundArr['contact-phone'];
            $replyToMessageId = $inboundArr['message-id'];
            $messageCommandText = $inboundArr['message-command'];
            $messageText = $inboundArr['message-text'];
            $messageReaction = $inboundArr['message-reaction'];
            $pollOptionIds = $inboundArr['poll-option-ids'];
            $isBotCommand = $parser->isTelegramCommand($messageCommandText);

            // define defaults
            $text = "$botLabel 🤖 may not have understood your request";

            // detect bots
            if($inboundArr['is-bot'] && !$inboundArr['is-callback']) {
                $text = "Oops! Bots 🤖 are not recognized by me.";
                $content = [
                    'text' => $text,
                    'chat_id' => $userId,
                    'reply_parameters'  => [
                        'message_id'    => $replyToMessageId
                    ]
                ];
                $result = app('telegram_bot')->sendMessage($content);
                return response()->json($result, 200);
            }

            $this->content = [
                'text' => $text,
                'chat_id' => $userId,
                'reply_parameters'  => [
                    'message_id'    => $replyToMessageId
                ]
            ];

            // set if prompted input
            $user = $userAccount->info($userId);
            $dbUserPhone = null;
            $isRegistered = true;
            $inputCommand = null;
            $inputObj = null;
            $sliderCommand = null;
            $sliderObj = null;
            try {
                $dbUserPhone = $user->phone;
                $dbUserFirstname = $user->firstname;
                $dbUserLastname = $user->lastname;
                if(is_null($dbUserPhone) || is_null($dbUserFirstname) || is_null($dbUserLastname)) {
                    $isRegistered = false;
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
            try {
                $inputObj = $user->inputs;
                $inputCommand = $inputObj->command;
            } catch (\Throwable $th) {
                //throw $th;
            }
            try {
                $sliderObj = $user->sliders;
                $sliderCommand = $sliderObj->command;
            } catch (\Throwable $th) {
                //throw $th;
            }

            // update visits
            $userAccount->updateTotalVisits($userId);

            // prompt user
            $chatAction = config('telegram.chatactions.text');
            $this->content['action'] = $chatAction;
            // app('telegram_bot')->sendChatAction($this->content);

            // Clear cache            
            if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.reviews.name'))) {
                $userAccount->clearAllCache($userId);
            }

            // update user cache
            if(!is_null($messageReaction)) {
                // update message reaction
                $reactionC = new MessageReaction($inboundArr);
                $reactionC->init();
            }
            else if(is_null($user) && is_null($messageReaction)) {
                // activation as superadmin
                $isActivated = $userAccount->createSuperAdmin($userId, $chatId, $messageCommandText);
                
                // first time user
                $userFirstname = $inboundArr['user-firstname'];
                $userUsername = $inboundArr['user-username'];
                $isRegistered = $userAccount->createNewUser($userId, $userFirstname, $userUsername);
                if($isRegistered) {
                    $introC = new IntroController($inboundArr);
                    $introC->newUser();
                }
            }
            elseif(!is_null($userPhone) && !$isRegistered) {
                // save user phone
                $userAccount->registerPhoneNumber($userId, $chatId, $userPhone);
            }
            elseif(!$isRegistered && $messageCommandText != config('telegram.commands_button.register_phone.name')) {
                // initiate user fullname
                $userAccount->addFullName($userId, $chatId, $messageCommandText, $inputObj);
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.register_phone.name'))) {
                // request user's phone
                $userAccount->requestPhoneNumber($userId, $chatId);
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.start.name'))) {
                // intro
                    $userAccount->clearAllCache($userId);
                    $introC = new IntroController($inboundArr);
                    $introC->regularUser();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.exit.name'))) {
                // remove input & slider data
                $userAccount->clearAllCache($userId);

                // stop menu
                    $stopC = new MenuController($inboundArr);
                    $stopC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.menu.name'))) {
                // main menu
                    $menuC = new MenuController($inboundArr);
                    $menuC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.about.name'))) {
                // about
                    $aboutC = new AboutController($inboundArr);
                    $aboutC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.info.name'))) {
                // info
                    $aboutC = new InfoController($inboundArr);
                    $aboutC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.stats.name'), true)) {
                // stats
                    $statsC = new StatsController($inboundArr);
                    $statsC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.search.name'))) {
                // search
                    $searchC = new SearchController($inboundArr, $user);
                    $searchC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.help.name'))) {
                // help
                $helpC = new HelpController($inboundArr);
                $helpC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.emojis.name'))) {
                $emojisC = new MessageReaction($inboundArr);
                $emojisC->emojisList($chatId, $sliderCommand, $messageCommandText);
            }
            else if(!is_null($inputCommand) && !is_null($messageCommandText)){
                // handle all prompted user inputs, media, admin actions received

                if($parser->isTelegramMatch($inputCommand, config('telegram.commands_button.add_fullname.name'))) {
                    $userAccount->addFullName($userId, $chatId, $messageCommandText, $inputObj);
                }

                else if($parser->isTelegramMatch($inputCommand, config('telegram.commands_button.dm.name'))) {
                    $contactC = new ContactsController($inboundArr);
                    $contactC->inputHandler($inputCommand);
                }

                else if($parser->isTelegramMatch($inputCommand, config('telegram.superadmin_commands_button.superadmin.name'))) {
                    $adminC = new SuperAdminController($inboundArr, $inputObj, $sliderObj);
                    $adminC->inputHandler($inputCommand, $inputObj);
                }

                else if($parser->isTelegramMatch($inputCommand, config('telegram.admin_commands_button.admin.name'))) {
                    $adminC = new AdminController($inboundArr, $user, $inputObj, $sliderObj);
                    $adminC->index();
                }
                else if($parser->isTelegramMatch($inputCommand, config('telegram.commands_button.users.name'))) {
                    $userC = new UsersController($inboundArr, $user, $inputObj, $sliderObj);
                    $userC->index();
                }
                else if($parser->isTelegramMatch($inputCommand, config('telegram.commands_button.parents.name'))) {
                    $userC = new ParentsController($inboundArr, $user, $inputObj, $sliderObj);
                    $userC->index();
                }
                else if($parser->isTelegramMatch($inputCommand, config('telegram.commands_button.update.name'))) {
                    // User stepwise actions
                    $updateC = new UpdateController($inboundArr, $user, $inputObj, $sliderObj);
                    $updateC->index();
                }
                else {
                    // clear cache most probably user did not exit slider or input steps
                    $userAccount->clearAllCache($userId);
                }
            }
            else if(!is_null($sliderCommand)){
                // perform slider actions
                if($parser->isTelegramMatch($sliderCommand, config('telegram.superadmin_commands_button.superadmin.name'))) {
                    $adminC = new SuperAdminController($inboundArr, $inputObj, $sliderObj);
                    $adminC->index();
                }

                else if($parser->isTelegramMatch($sliderCommand, config('telegram.admin_commands_button.admin.name'))) {
                    $adminC = new AdminController($inboundArr, $user, $inputObj, $sliderObj);
                    $adminC->index();
                }
                else if($parser->isTelegramMatch($sliderCommand, config('telegram.commands_button.users.name'))) {
                    $userC = new UsersController($inboundArr, $user, $inputObj, $sliderObj);
                    $userC->index();
                }
                else if($parser->isTelegramMatch($sliderCommand, config('telegram.commands_button.parents.name'))) {
                    $userC = new ParentsController($inboundArr, $user, $inputObj, $sliderObj);
                    $userC->index();
                }
                else {
                    // clear cache most probably user did not exit slider or input steps
                    $userAccount->clearAllCache($userId);
                }
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.contacts.name'))) {
                // Contacts stepwise actions
                    $contactC = new ContactsController($inboundArr);
                    $contactC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.superadmin_commands_button.superadmin.name'))) {
                // SuperAdmin stepwise actions
                    $adminC = new SuperAdminController($inboundArr, $user, $inputObj, $sliderObj);
                    $adminC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.admin_commands_button.admin.name'))) {
                // Admin stepwise actions
                    $adminC = new AdminController($inboundArr, $user, $inputObj, $sliderObj);
                    $adminC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.parents.name'))) {
                // Parents stepwise actions
                $userC = new ParentsController($inboundArr, $user, $inputObj, $sliderObj);
                $userC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.users.name'))) {
                // User stepwise actions
                $userC = new UsersController($inboundArr, $user, $inputObj, $sliderObj);
                $userC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.update.name'))) {
                // User stepwise actions
                $updateC = new UpdateController($inboundArr, $user, $inputObj, $sliderObj);
                $updateC->index();
            }
            else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.reviews.name'))) {
                $searchC = new SearchController($inboundArr, $user);
                $searchC->review($messageCommandText);
            }
            else {
                if($isBotCommand) {
                    $cmdArr = $parser->telegramBotCommandArray();
                    $errorText = config('messages.unknown_error');

                    if(array_search($messageCommandText, $cmdArr) !== false) {
                        // not assigned bot commands
                        $errorText = config('messages.unassigned_bot_command');
                        $errorText = sprintf($errorText, $messageCommandText);
                    } else {
                        // bot commands not recognized
                        $errorText = config('messages.unrecognized_bot_command');
                        $errorText = sprintf($errorText, $messageCommandText);
                    }
                    // $parser->log($errorText);
                    $errorC->error($errorText);
                }
                else if(!is_null($inputCommand)){
                    // handle all prompted user inputs, media, admin actions received
                    if($parser->isTelegramMatch($inputCommand, config('telegram.commands_button.update.name'))) {
                        // User stepwise actions
                        $updateC = new UpdateController($inboundArr, $user, $inputObj, $sliderObj);
                        $updateC->index();
                    }
                }
                else {
                    // otherwords, treat as search terms
                    $stripIntType = preg_replace('/[^0-9]/', '', $messageCommandText);
                    $stripMessageInt = intval($stripIntType);

                    $searchC = new SearchController($inboundArr, $user);

                    if(is_numeric($messageCommandText)) {
                        // search event by number
                        $searchC->assessmentById($messageCommandText);
                    }
                    else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.dm.name'))) {
                        $searchC->dm();
                    }
                    else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.subscribe.name'))) {
                        $searchC->subscribe();
                    }
                    else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.unsubscribe.name'))) {
                        $searchC->unsubscribe();
                    }
                    else if($parser->isTelegramMatch($messageCommandText, config('telegram.commands_button.reviews.name'))) {
                        $searchC->review($messageCommandText);
                    }
                    else {
                        $searchC->faq($messageCommandText);
                        // $errorText = config('messages.unrecognized_input');
                        // $errorText = sprintf($errorText, $messageCommandText);
                        // $parser->log($errorText);
                        // $errorC->error($errorText);
                    }
                }
            }

        } catch (\Throwable $th) {
            $parser->log("Error: $th");
            $errorC->main();
        }

        // execute Domi functions
        if(env('DOMI_IS_ACTIVE')) {
            $domi = new DomiController;
            $domi->init();
        }
    }
}
