<?php
namespace App\Http\Controllers\API;


use App\Classes\Parser;
use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Telegram\Keyboards\InlineKeyboards;
use App\Http\Controllers\API\Telegram\Keyboards\OnetimeKeyboards;
use App\Models\CacheInputs;
use App\Models\CacheSliders;
use App\Models\Callbacks;
use App\Models\User;
use App\Models\VisitCounters;
use App\Models\MediaCounters;
use DateTime;
use Illuminate\Support\Facades\Log;

class DomiController extends Controller
{
    private $inProduction;
    private $parser;
    private $totalUsers;
    private $inlineKeyboard;
    private $onetimeKeyboards;

    public function __construct()
    {
        $this->inProduction = app()->isProduction();
        $this->parser = new Parser;
        $this->inlineKeyboard = new InlineKeyboards;
        $this->onetimeKeyboards = new OnetimeKeyboards;
        $this->totalUsers = 99;
    }

    public function init()
    {
        $res = $this->creatDomiUsers();
    }

    private function creatDomiUsers()
    {
        $baseRole = config('constants.user_roles.user');

        try {
            $getCreated = User::factory()->count($this->totalUsers)->create();
            
            $response = [
                'Seeded Users' => count($getCreated),
                'Total Users' => User::count()
            ];

            $this->parser->log($response);
        } catch (\Throwable $th) {
            $this->parser->log($th);
        }
    }

    private function generateUniqueCode()
    {
        // $code = random_int(100000, 999999);
        $code = fake()->unique()->randomDigit;
        // $hashId = $this->parser->encoder(fake()->unixTime());
        $hashId = $this->parser->encoder($code);
        while(User::whereId($hashId)->first()) {
            $code = fake()->unique()->randomDigit;
            $hashId = $this->parser->encoder($code);
        }
  
        return strtolower($hashId);
    }

    private function creatDomiUsersX()
    {    
        $domiList = config('telegram.domi_users');
        $response = [
            'created' => 0,
            'total' => count($domiList),
            'skipped_ids' => []
        ];

        foreach ($domiList as $key => $item) {
            $id = $key + 1;
            $name = $item['name'];
            $username = strtolower($item['username']);
            $firstname = $item['firstname'];
            $lastname = $item['lastname'];
            $phone = $item['phone'];

            $hashId = $this->parser->encoder($id);
    
            // register if user does not exist
            $user = User::find($hashId);
            if(is_null($user)) {
                $getUsername = !is_null($username)? $username: null;
                $getEmail = $hashId . '@'. env('SESSION_DOMAIN') . '.com';
                if($this->inProduction) {
                    $getEmail = $hashId . '@'. env('SESSION_DOMAIN');
                }
    
                $baseRole = config('constants.user_roles.user');
    
                try {
                    $save = new User;
                    $save->id = $hashId;
                    $save->name = ucfirst($name);
                    $save->username = $getUsername;
                    $save->firstname = $firstname;
                    $save->lastname = $lastname;
                    $save->phone = $phone;
                    $save->role = $baseRole;
                    $save->email = $getEmail;
    
                    if($save->save()) {
                        $response['created'] ++;
                    }
                    else{
                        array_push($response['skipped_ids'], $id);
                    }
                } catch (\Throwable $th) {
                    Log::error($th);
                }
            }
        }

        return $response;
    }
}
