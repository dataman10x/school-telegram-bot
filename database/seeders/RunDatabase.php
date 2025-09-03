<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Admins;
use App\Models\Angels;
use App\Models\AngelsLevels;
use App\Models\Conveners;
use App\Models\Guardians;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RunDatabase extends Seeder
{
    private $superadminRoleName;
    private $adminRoleName;
    private $testerRoleName;
    private $convenerRoleName;
    private $guardianRoleName;
    private $parentRoleName;
    private $angelRoleName;
    private $superAdmin;
    private $adminIds;
    private $guardianIds;
    private $userIds;
    private $maxAdmins;
    private $maxConveners;
    private $maxGuardians;
    private $maxParents;

    public function __construct()
    {
        $this->superadminRoleName = config('constants.user_roles.superadmin');
        $this->testerRoleName = config('constants.user_roles.tester');
        $this->adminRoleName = config('constants.user_roles.admin');
        $this->convenerRoleName = config('constants.user_roles.convener');
        $this->guardianRoleName = config('constants.user_roles.guardian');
        $this->parentRoleName = config('constants.user_roles.parent');
        $this->angelRoleName = config('constants.user_roles.angel');
        
        $this->superAdmin = User::where('role', $this->superadminRoleName)->first();
        $this->userIds = [];
        $this->adminIds = [];
        $this->guardianIds = [];

        $this->maxAdmins = 5;
        $this->maxConveners = 5;
        $this->maxGuardians = 5;
        $this->maxParents = 5;
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $response = null;
        $minUsers = 50;

        try {
            $arrRes = [];
            
            $limitUsers = $this->command->ask('Enter max number of Users (role: Testers)');


            $limitAngels = $this->command->ask('Enter max number of Angels');

            $limitAngelsLevels = $this->command->ask('Enter max number of levels for Angels');

            if($limitUsers >= $minUsers) {
                $this->createUsers($limitUsers);
                $this->createAdmins();
                $this->createConveners();
                $this->createGuardians();
                $this->createParents();
                $this->createAngelsLevels($limitAngelsLevels);
                $this->createAngels($limitAngels);
            }
            else {
                Log::info("Additional operations could not be completed because $limitUsers Users is below required number");
            }
        } catch (\Throwable $th) {
            Log::error($th);
        }

    }

    private function createUsers($getLimit)
    {
        $limit = $getLimit;
        $this->call(UsersSeeder::class, false, compact('limit'));

        $this->userIds = User::where('role', $this->testerRoleName)->pluck('id');
    }

    private function createAdmins()
    {

        // create admins
        $superAdminId = $this->superAdmin->id;
        $selectedIds = [];
        $duplicates = [];

        $maxAllowed = count($this->userIds) < $this->maxAdmins? count($this->userIds): $this->maxAdmins;
        $counter = 0;

        foreach ($this->userIds as $key => $userId) {
            if($maxAllowed <= $counter) {
                break;
            }
            
            if(!is_null(Admins::find($userId)) || !is_null(Angels::find($userId))) {
                array_push($duplicates, $userId);
            }
            else {
                $counter ++;
                $input = json_encode([$superAdminId, $userId]);
                $this->call(AdminsSeeder::class, false, compact('input'));
            }
        }

        if(count($duplicates) > 0) {
            $getDuplicates = implode(', ', $duplicates);
            Log::warning("Admin Duplicates found: $getDuplicates");
        }

        $this->adminIds = Admins::pluck('id');
    }

    private function createConveners()
    {
        if(count($this->adminIds) == 0) {
            Log::warning("No Admin account was found to create Conveners");
            exit();
        }

        // create Conveners
        $adminId = $this->adminIds[0];
        $selectedIds = [];
        $duplicates = [];

        $maxAllowed = count($this->userIds) < $this->maxConveners? count($this->userIds): $this->maxConveners;
        $counter = 0;

        foreach ($this->userIds as $key => $userId) {
            if($maxAllowed <= $counter) {
                break;
            }
            
            if(!is_null(Conveners::find($userId)) || !is_null(Angels::find($userId))) {
                array_push($duplicates, $userId);
            }
            else {
                $counter ++;
                $input = json_encode([$adminId, $userId]);
                $this->call(ConvenersSeeder::class, false, compact('input'));
            }
        }

        if(count($duplicates)) {
            $getDuplicates = implode(', ', $duplicates);
            Log::warning("Conveners Duplicates found: $getDuplicates");
        }
    }

    private function createGuardians()
    {
        if(count($this->adminIds) == 0) {
            Log::warning("No Admin account was found to create Guardians");
            exit();
        }

        // create Guardians
        $adminId = $this->adminIds[0];
        $selectedIds = [];
        $duplicates = [];

        $maxAllowed = count($this->userIds) < $this->maxGuardians? count($this->userIds): $this->maxGuardians;
        $counter = 0;

        foreach ($this->userIds as $key => $userId) {
            if($maxAllowed <= $counter) {
                break;
            }
            
            if(!is_null(Guardians::find($userId)) || !is_null(Angels::find($userId))) {
                    array_push($duplicates, $userId);
            }
            else {
                $counter ++;
                $input = json_encode([$adminId, $userId]);
                $this->call(GuardiansSeeder::class, false, compact('input'));
            }
        }

        if(count($duplicates)) {
            $getDuplicates = implode(', ', $duplicates);
            Log::warning("Guardian Duplicates found: $getDuplicates");
        }

        $this->guardianIds = Guardians::pluck('id');
    }

    private function createParents()
    {
        if(count($this->adminIds) == 0) {
            Log::warning("No Admin account was found to create Guardians");
            exit();
        }

        if(count($this->guardianIds) == 0) {
            Log::warning("No Guardian account was found to create Parents");
            exit();
        }

        // create Guardians
        $guardianId = $this->guardianIds[0];
        $selectedIds = [];
        $duplicates = [];

        $maxAllowed = count($this->userIds) < $this->maxParents? count($this->userIds): $this->maxParents;
        $counter = 0;

        foreach ($this->userIds as $key => $userId) {
            if($maxAllowed <= $counter) {
                break;
            }
            
            if(!is_null(Parents::find($userId)) || !is_null(Angels::find($userId))) {
                    array_push($duplicates, $userId);
            }
            else {
                $counter ++;
                $input = json_encode([$guardianId, $userId]);
                $this->call(ParentsSeeder::class, false, compact('input'));
            }
        }

        if(count($duplicates)) {
            $getDuplicates = implode(', ', $duplicates);
            Log::warning("Parent Duplicates found: $getDuplicates");
        }
    }

    private function createAngelsLevels($limit)
    {
        $getLimit = $limit;
        $res = null;
        $this->call(AngelsLevelsSeeder::class, false, compact('getLimit'));

        return $res;
    }

    private function createAngels($getLimit = 10)
    {
        if(count($this->adminIds) == 0) {
            Log::warning("No Admin account was found to create Angels");
            exit();
        }

        // check if level exist
        $getLevel = AngelsLevels::first();
        if(is_null($getLevel)) {
            Log::warning("No Level for Angels was found to create Angels");
            exit();
        }

        // create Angels
        $levelId = $getLevel->id;

        $input = json_encode([$getLimit, $levelId]);
        $this->call(AngelsSeeder::class, false, compact('input'));
    }
}
