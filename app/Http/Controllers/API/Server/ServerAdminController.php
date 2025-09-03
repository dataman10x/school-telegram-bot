<?php
namespace App\Http\Controllers\API\Server;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Classes\Ability;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\SmartResponse;
use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServerAdminController extends Controller
{

    public function index(String $type = null)
    {
        $request = new Request();

        switch ($type) {
            case 'server-live':
                return $this->live($request);
                break;
            case 'server-up':
                // return $this->up($request);
                return response("Use the dedicated route with secret", config('constants.error_codes.not_found'))->header('Content-Type', 'text/json');
                break;
            case 'server-down':
                return $this->down($request);
                break;
            case 'server-clear':
                return $this->clear($request);
                break;
            case 'server-regenerate':
                return $this->regenerate($request);
                break;
            case 'server-delete-log':
                return $this->deleteLog($request);
                break;
            case 'backup-all':
                return $this->backupRunAll($request);
                break;
            case 'backup-db':
                return $this->backupRunDb($request);
                break;
            case 'backup-file':
                return $this->backupRunFiles($request);
                break;
            case 'backup-list':
                return $this->backupRunList($request);
                break;
            case 'backup-monitor':
                return $this->backupRunMonitor($request);
                break;

            default:
                return response("Requested resource was not found!", config('constants.error_codes.not_found'))->header('Content-Type', 'text/json');
                break;
        }
    }

    public function updateSchema(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;

        // verify if autorized
        $secret = MASTER_SECRET;
        $param = null;
        $clearPromoted = null;
        $param = htmlspecialchars($_POST["key"]);
        $res = [];

        if($secret !== $param) {
            $title = "You are not allowed to update schema";
            array_push($res, $title);
            return $feedback->json($res, false);
        }

        try {
            $clearPromoted = htmlspecialchars($_POST["clear-promoted"]);
        } catch (\Throwable $th) {
            //throw $th;
        }

        try {
            if(!is_null($clearPromoted)) { // step to enable promoting candidates
                DB::table('candidates')->update(array('reg_id' => null));

                Schema::table('candidates', function (Blueprint $table) {
                    $table->dropForeign(['reg_id']);
                });

                DB::table('school_regs')->truncate();

                Schema::table('candidates', function (Blueprint $table) {
                    $table->foreign('reg_id')->references('id')->on('school_regs')->onUpdate('cascade')->onDelete('SET NULL');
                });

                $message = 'All Candidates Registration have been cleared successfully.';
            }

            else if(env('ADMIN_UPDATE_ACTIVE', false)) {

                // update comments & blog
                // Schema::dropIfExists('comments');

                // Schema::table('sample_table', function (Blueprint $table) {
                //     $table->dropColumn('label');
                //     $table->string('ref');
                //     $table->string('answer')->change();
                //     $table->foreign('id')->references('id')->on('user')->onDelete('cascade');
                //     $table->dropForeign(['vote_id']);
                //     $table->index(['ref']);
                // });

                /**
                 * EDIT BELOW ONLY
                 */

                 $this->activeSchemaDrop();
                 
                //  $this->activeSchemaTables();
        


                $message = 'Schema was updated successfully.';
            } else {
                $message = 'Requested Schema update failed. Admin Update is inactive.';
            }
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, true);
            } else {
                $feedback->alertMessage($message,'success');
                return redirect()->back();
            }
        } catch (\Throwable $th) {
            $message = 'Something went wrong! Schema was not modified.';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
        }
    }

    private function activeSchemaDrop()
    {

    }

    private function activeSchemaTables()
    {
        

        Schema::create('bot_users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('role')->nullable()->default('user');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
        
        Schema::create('bot_dialog_messages', function (Blueprint $table) {
            $table->id();
            $table->json('keywords');
            $table->text('detail', 1000);
            $table->integer('views')->default(0);
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_visit_counters', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->integer('one_time')->default(0);
            $table->integer('daily')->default(0);
            $table->integer('monthly')->default(0);
            $table->integer('yearly')->default(0);
            $table->datetime('last_date')->nullable();
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_media_counters', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->integer('text')->default(0);
            $table->integer('photo')->default(0);
            $table->integer('audio')->default(0);
            $table->integer('video')->default(0);
            $table->integer('document')->default(0);
            $table->datetime('last_date')->nullable();
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_callbacks', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('reply_id')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_cache_inputs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('command')->nullable();
            $table->json('steps')->nullable();
            $table->string('active_step')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_cache_sliders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('label')->nullable();
            $table->string('command')->nullable();
            $table->string('first_step')->nullable();
            $table->string('previous_step')->nullable();
            $table->string('active_step')->nullable();
            $table->string('next_step')->nullable();
            $table->string('last_step')->nullable();
            $table->json('steps_info')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_reviews', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('note');
            $table->datetime('approved_at')->nullable();
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_admins', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('detail')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bot_media', function (Blueprint $table) {
            $table->id();
            $table->string('file_id');
            $table->string('unique_id')->nullable();
            $table->string('path')->nullable();
            $table->string('local_path')->nullable();
            $table->string('size')->nullable();
            $table->string('mime')->nullable();
            $table->string('name')->nullable();
            $table->string('disc')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_media_detail', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('bot_direct_messages', function (Blueprint $table) {
            $table->id();
            $table->text('message', 1000);
            $table->timestamps();
        });

        Schema::create('bot_dm_responses', function (Blueprint $table) {
            $table->id();
            $table->text('message', 1000);
            $table->timestamps();
        });

        Schema::create('bot_broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('label');
            $table->text('detail', 1000);
            $table->boolean('can_repeat');
            $table->datetime('start_at')->nullable();
            $table->datetime('end_at')->nullable();
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_broadcast_seen_users', function (Blueprint $table) {
            $table->id();
            $table->text('comment', 1000);
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_broadcast_locked_users', function (Blueprint $table) {
            $table->id();
            $table->text('comment', 1000);
            $table->timestamps();
            $table->index(['id']);
        });

        Schema::create('bot_parents', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('regs')->nullable();
            $table->boolean('report_active')->default(true);;
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bot_candidates', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('reg')->nullable();
            $table->boolean('report_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bot_teachers_media', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
        });

        Schema::create('bot_settings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('data');
            $table->datetime('start_at')->nullable();
            $table->datetime('end_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_snippets', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_app_info', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('label');
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_emoji_reactions', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id');
            $table->string('message_id');
            $table->string('type')->nullable();
            $table->string('emoji')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_settings_auths', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->json('auths')->nullable();
            $table->json('users')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_settings_switch', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->boolean('is_active');
            $table->timestamps();
        });

        
        Schema::table('bot_visit_counters', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('bot_media_counters', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('bot_callbacks', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_cache_inputs', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_cache_sliders', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_reviews', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignUuid('approved_by')->nullable()->constrained('bot_admins')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'approved_by']);
        });

        Schema::table('bot_admins', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignUuid('approved_by')->nullable()->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'approved_by']);
        });

        Schema::table('bot_media_detail', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'user_id']);
        });

        Schema::table('bot_media', function (Blueprint $table) {
            $table->foreignId('media_detail_id')->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'media_detail_id']);
        });

        Schema::table('bot_parents', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('bot_candidates', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::table('bot_teachers_media', function (Blueprint $table) {
            $table->foreign('id')->references('id')->on('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_direct_messages', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'user_id']);
        });

        Schema::table('bot_dm_responses', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_admins')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('message_id')->constrained('bot_direct_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'user_id', 'message_id']);
        });

        Schema::table('bot_broadcast_messages', function (Blueprint $table) {
            $table->foreignUuid('admin_id')->constrained('bot_admins')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'admin_id', 'media_id']);
        });

        Schema::table('bot_broadcast_seen_users', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('broadcast_id')->constrained('bot_broadcast_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'broadcast_id']);
        });

        Schema::table('bot_broadcast_locked_users', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('broadcast_id')->constrained('bot_broadcast_messages')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id', 'broadcast_id']);
        });

        Schema::table('bot_snippets', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_app_info', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->constrained('bot_media_detail')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('snippet_id')->nullable()->constrained('bot_snippets')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['id']);
        });

        Schema::table('bot_emoji_reactions', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('bot_users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['user_id']);
        });




        
    }

    public function migrateSchema(Request $request)
    {
        $secret = MASTER_SECRET;
        $param = null;

        try {
            $param = htmlspecialchars($_POST["key"]);
            $resType = 'error';

            if(!env('ADMIN_UPDATE_ACTIVE', false)) {
                $response = 'Requested Schema update failed. Admin Update is inactive.';
            }
            else if($secret === $param) {
                // create dynamic_config table if not exist
                if (!Schema::hasTable('dynamic_configs')) {
                    Schema::create('dynamic_configs', function (Blueprint $table) {
                        $table->id();
                        $table->string('key');
                        $table->json('values');
                    });
                }

                // then migrate
                Artisan::call('clear:init');
                Artisan::call('migrate:fresh', ['--force' => true]);
                $response = Artisan::output();
                $resType = 'success';
            } else {
                $response = 'You do not have the required authorization to perform this task';
            }
            // echo($response);
            return response()->json([$resType => $response], 200);
        } catch (\Throwable $th) {
            // echo('You need a key to perform this task');
            return response()->json(['error'=>'Something went wrong or the needed key is null',
                'detail' => json_encode($th)], 401);
        }
    }

    public function telegramWebhook(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;
        // $user = auth()->user();

        // verify if autorized
        $secret = MASTER_SECRET;
        $param = null;
        $res = [];
        $param = htmlspecialchars($_POST["key"]);
        // $url = htmlspecialchars($_POST["url"]);

        if($secret !== $param) {
            $title = "You are not allowed to update schema";
            array_push($res, $title);
            return $feedback->json($res, false);
        }

        try{
            // composer dump-autoload
            // php artisan botman:telegram:register
            $updates = json_encode([
                'update_id',
                'message',
                'callback_query',
                'poll',
                'poll_answer',
                'message_reaction',
                'message_reaction_count'
            ], true);
            $encodeUpdates = urlencode($updates);
            $params = "allowed_updates=$encodeUpdates";

            $webhookPath = config('constants.telegram_webhook_path');
            $configMessage = Http::post("$webhookPath&$params");

            // Artisan::call("bot:set-webhook $url");
            // $configMessage = Artisan::output();
            // $title = 'Telegram webhook was set';

            array_push($res, $configMessage);
            return $feedback->json($res, true);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
            Log::error($th);
         }
    }

    public function up(Request $request)
    {
        // Artisan::call('up');

        $files = Arr::where(Storage::disk('framework')->files(),
            function($filename) {
                return $filename === 'down' || $filename === 'maintenance.php';
            });

            $count = count($files);
            $success = new SmartResponse();
            $res = [];

            if(Storage::disk('framework')->delete($files)) {
                // $feedback = $this->info(sprintf('Deleted %s %s!', $count, Str::plural('file', $count)));
                $feedback = sprintf('Deleted %s %s!', $count, Str::plural('file', $count));

                $res = $success->add(
                    $res,
                    $count === 0?config('constants.success.maintenance_up_already'):config('constants.success.maintenance_up'),
                    $feedback);
                $success->init($res, 'You may broadcast the server is Live to notify users');
            } else {
                // $feedback = $this->error('Error in deleting log files!');
                $feedback = 'Error in deleting log files!';

                $res = $success->add([], '', '');
                return $success->res($res, $feedback, config('constants.error_codes.bad'));
            }
    }

    public function down(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;
        $user = auth()->user();
        $secret = env('MAINTENANCE_SECRET');

        try {
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $code = config('constants.success_codes.ok');

            $ability = new Ability();
            $superAdmin = $ability->isSuperAdmin();
           if($superAdmin['data']) {
                Artisan::call("down", ["--secret" => $secret]);

                $configMessage = config('constants.success.maintenance_down');
                $title = 'You need to add the secret to the url when bringing up the server';
            } else {
                $configMessage = config('constants.errors.unauthorized_cmd');
                $title = 'You do not have the required authorization to bring down the Server';
                $code = config('constants.error_codes.unauthorized');
            }

            $res = $success->add($res, $configMessage, $title);
            return $success->res($res, '', $code);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }

    public function clear(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;
        $user = auth()->user();
        try{
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $code = config('constants.success_codes.ok');

            // verify if autorized
            $secret = MASTER_SECRET;
            $param = null;
            $param = htmlspecialchars($_POST["key"]);

            if($secret == $param) {
                Artisan::call("clear:init");
                $configMessage = Artisan::output();

                // $configMessage = config('constants.success.server_clear_caches');
            } else {
                $configMessage = config('constants.errors.unauthorized_cmd');
            }

            array_push($res, $configMessage);
            return $feedback->json($res, true);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }

    public function regenerate(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;
        $user = auth()->user();
        try{
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $code = config('constants.success_codes.ok');

            // verify if autorized
            $secret = MASTER_SECRET;
            $param = null;
            $param = htmlspecialchars($_POST["key"]);

            if($secret == $param) {
            // composer dump-autoload
                    Artisan::call('dump-autoload');
                    $configMessage = 'App structure was regenerated';
            } else {
                $configMessage = 'Error occured!';
            }

            array_push($res, $configMessage);
            return $feedback->json($res, true);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }

    public function link(Request $request)
    {
        $secret = MASTER_SECRET;
        $param = null;

        try {
            $param = htmlspecialchars($_POST["key"]);
            $resType = 'error';

            if($secret === $param) {
                Artisan::call('storage:link');
                $response = Artisan::output();
                $resType = 'success';
            } else {
                $response = 'You do not have the required authorization to perform this task';
            }
            // echo($response);
            return response()->json([$resType => $response], 200);
        } catch (\Throwable $th) {
            // echo('You need a key to perform this task');
            return response()->json(['error'=>'You need a key to perform this task'], 401);
        }
    }

    public function deleteLog(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;
        $user = auth()->user();

        try {
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $code = config('constants.success_codes.ok');

            $ability = new Ability();
            $superAdmin = $ability->isSuperAdmin();
           if($superAdmin['data']) {
                Artisan::call("delete:log");

                $configMessage = config('constants.success.server_delete_log');
                $title = 'You may contact the Admin to perform more discrete operations like deleting of redundant files';
            } else {
                $configMessage = config('constants.errors.unauthorized_cmd');
                $title = 'You do not have the required authorization to clear caches on the Server';
                $code = config('constants.error_codes.unauthorized');
            }

            $res = $success->add($res, $configMessage, $title);
            return $success->res($res, '', $code);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }

    public function backupRunAll(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;
        $user = auth()->user();

        try {
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $code = config('constants.success_codes.ok');

            $ability = new Ability();
            $superAdmin = $ability->isSuperAdmin();
           if($superAdmin['data']) {
                Artisan::call("backup:run");

                $configMessage = config('constants.success.server_backup_all');
                $title = 'You may contact the Admin to perform more discrete operations like deleting of redundant files';
            } else {
                $configMessage = config('constants.errors.unauthorized_cmd');
                $title = 'You do not have the required authorization to backup everything on the Server';
                $code = config('constants.error_codes.unauthorized');
            }

            $res = $success->add($res, $configMessage, $title);
            return $success->res($res, '', $code);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }

    public function backupRunDb(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;

        $user = auth()->user();

        try {
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $code = config('constants.success_codes.ok');

            $ability = new Ability();
            $superAdmin = $ability->isSuperAdmin();
           if($superAdmin['data']) {
                Artisan::call("backup:run --only-db");

                $configMessage = config('constants.success.server_backup_db');
                $title = 'You may contact the Admin to perform more discrete operations like deleting of redundant files';
            } else {
                $configMessage = config('constants.errors.unauthorized_cmd');
                $title = 'You do not have the required authorization to backup database on the Server';
                $code = config('constants.error_codes.unauthorized');
            }

            $res = $success->add($res, $configMessage, $title);
            return $success->res($res, '', $code);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }

    public function backupRunFiles(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;

        $user = auth()->user();

        try {
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $code = config('constants.success_codes.ok');

            $ability = new Ability();
            $superAdmin = $ability->isSuperAdmin();
           if($superAdmin['data']) {
                Artisan::call("backup:run --only-files");

                $configMessage = config('constants.success.server_backup_files');
                $title = 'You may contact the Admin to perform more discrete operations like deleting of redundant files';
            } else {
                $configMessage = config('constants.errors.unauthorized_cmd');
                $title = 'You do not have the required authorization to backup files on the Server';
                $code = config('constants.error_codes.unauthorized');
            }

            $res = $success->add($res, $configMessage, $title);
            return $success->res($res, '', $code);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }

    public function backupRunList(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;

        $user = auth()->user();

        try {
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $head = '';
            $code = config('constants.success_codes.ok');

            $ability = new Ability();
            $superAdmin = $ability->isSuperAdmin();
           if($superAdmin['data']) {
                $head = Artisan::call("backup:list");

                $configMessage = config('constants.success.server_list');
                $title = 'You may contact the Admin to perform more discrete operations like deleting of redundant files';
            } else {
                $configMessage = config('constants.errors.unauthorized_cmd');
                $title = 'You do not have the required authorization to list backups on the Server';
            }

            $res = $success->add($res, $configMessage, $title);
            return $success->res($res, $head, $code);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }

    public function backupRunMonitor(Request $request)
    {
        $feedback = new SmartResponse();
        $responsetype = $_POST["type"];
        $responsetype = !is_null($responsetype)? $responsetype: null;

        $user = auth()->user();

        try {
            $success = new SmartResponse();
            $res = [];
            $configMessage = '';
            $title = '';
            $head = '';
            $code = config('constants.success_codes.ok');

            $ability = new Ability();
            $superAdmin = $ability->isSuperAdmin();
           if($superAdmin['data']) {
                $head = Artisan::call("backup:monitor");

                $configMessage = config('constants.success.server_monitor');
                $title = 'You may contact the Admin to perform more discrete operations like deleting of redundant files';
            } else {
                $configMessage = config('constants.errors.unauthorized_cmd');
                $title = 'You do not have the required authorization to check the health of backups on the Server';
                $code = config('constants.error_codes.unauthorized');
            }

            $res = $success->add($res, $configMessage, $title);
            return $success->res($res, $head, $code);
         } catch(\Exception $th){
            $message = 'You need to login to perform this request';
            $message = json_encode($th);
            if(!is_null($responsetype)) {
                return $feedback->exec('', $message, false);
            } else {
                $feedback->alertMessage($message,'error', true);
                return redirect()->back();
            }
         }
    }
}
