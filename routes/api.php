<?php

use App\Classes\MediaHandler;
use App\Http\Controllers\API\Server\ServerAdminController;
use App\Http\Controllers\API\Server\ServerManualUpdatesController;
use App\Http\Controllers\API\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('/master-init', [ServerAdminController::class, 'migrateSchema']);

// Route::post('/master-init', function() {
//     $envVar = env('ADMIN_UPDATE_ACTIVE', false);
//     $envStr = $envVar? 'ACTIVE':'INACTIVE';
//     $response = "Admin update is $envStr";
//     return response()->json(['success' => $response], 200);
// });

Route::post('/master-clear', [ServerAdminController::class, 'clear']);

Route::post('/master-regenerate', [ServerAdminController::class, 'regenerate']);

Route::post('/master-link',  [ServerAdminController::class, 'link']);

Route::post("/update-schema", [ServerAdminController::class, 'updateSchema'])->name('update.schema');

Route::post("/update-database-xxx", [ServerManualUpdatesController::class, 'update'])->name('update.db.data');

Route::post("/telegram-webhook", [ServerAdminController::class, 'telegramWebhook'])->name('telegram.webhook');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Telegram Bot API
 */

 Route::prefix('/telegram/webhooks')->group(function() {
    Route::post("/inbound_6349011784", [TelegramController::class, 'inbound'])->name('telegram.inbound');
 });

 /**
  * Test for media paths
  */

 Route::prefix('/test')->group(function() {
    Route::match(['get', 'post'], "/images", function() {
        $mediaHandler = new MediaHandler;
        $path = $mediaHandler->publicPath('cbt pricing.png');
        $size = $mediaHandler->getSize('cbt pricing.png');
        $lastmodified = $mediaHandler->getLastModified('cbt pricing.png');
        // $getFiles = $mediaHandler->getFilesWithDir();
        $mime = $mediaHandler->getMime('cbt pricing.png');
        $media = [
            'path' => $path,
            'size' => $size,
            'lastmodified' => $lastmodified,
            'mime' => $mime,
        ];
        return $mediaHandler->download('cbt pricing.png');
        // dd($media);
        // $headers = [
        //     'Content-Type' => 'image/png',
        //     'Content-Disposition' => 'attachment; filename"new.png"'
        // ];
        // return Response::make($isDownloaded, 200, $headers);
    })->name('telegram.test');
 });
