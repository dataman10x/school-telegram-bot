<?php

use App\Http\Controllers\API\BotManController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('pages.welcome');
});

// Route::match(['get', 'post'], '/botman', [BotManController::class, 'index']);

/**
 * widget URL
 */

//  $frameEndpoint = "http://localhost/testbot/botman/chat";
//  $chatServer = "http://localhost/testbot/botman";

//  $frameEndpoint = "https://creat.i.ng/testbot/botman/chat";
//  $chatServer = "https://api.telegram.org/$telegramToken/getme";
