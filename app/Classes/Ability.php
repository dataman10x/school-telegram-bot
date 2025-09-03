<?php
namespace App\Classes;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Ability
{
    private $options;
    private $parser;
    private $user;
    private $res;
    private $role;
    private $permission;
    private $team;
    private $required;
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke()
    {
        $this->parser = new Parser();
        $this->user = auth()->user();

        $this->role = config('usersauth.roles.basic_user');
        $this->permission = config('usersauth.permissions.use_basic');
        $this->team = config('usersauth.teams.members');
        $this->required = false;

        $this->options = [
            'validate_all' => $this->required,
            'return_type' => 'boolean'
        ];
        $this->res = [
            'data' => false,
            'text' => config('constants.errors.unauthorized_access')
        ];

        return $this;
    }

    public function configData($input, $config) {
        $this->__invoke();
        $res = null;
        $getInput = $this->parser->arrayForced($input);

        if(!is_null($getInput)) {
            $res = [];
            try {
                $inp = str_replace('-', '_', $input);
                $getValues = $config[$inp];
                array_push($res, $getValues);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        return $res;
    }

    public function can(array $role = null, array $permission = null, array $team = null, bool $validate_all = false)
    {
        $this->__invoke();

        $getRole = config('usersauth.roles.basic_user');
        $getPermission = config('usersauth.permissions.use_basic');
        $getTeam = config('usersauth.teams.members');

        try {
            if(is_bool($validate_all)) {
                $this->options['validate_all'] = $validate_all;
            }

            $getRole = is_null($role)? $getRole: $role;
            $getPermission = is_null($permission)? $getPermission: $permission;
            $getTeam = is_null($team)? $getTeam: $team;

            $this->res['data'] = $this->user->ability($getRole, $getPermission, $getTeam, $this->options);
            if($this->res['data']) {
                $this->res['text'] = 'User has rights';
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return $this->res;
    }

    public function hasTeam($id = null)
    {
        $this->__invoke();
        $data = $this->user->rolesTeams;
        if(!is_null($id)) {
            $getUser = User::find($id);
            if(!is_null($getUser)) {
                $data = $getUser->rolesTeams;
            }
        }
        $isNull = $this->parser->isNull($data);
        if(!is_null($isNull['data'])) {
            $this->res['data'] = $isNull['data'];
            $this->res['text'] = $data;
        } else {
            $this->res['data'] = false;
        }
        return $this->res;
    }

    public function hasRole($id = null)
    {
        $this->__invoke();
        $data = $this->user->roles;
        if(!is_null($id)) {
            $getUser = User::find($id);
            if(!is_null($getUser)) {
                $data = $getUser->roles;
            }
        }
        $isNull = $this->parser->isNull($data);
        if(!is_null($isNull['data'])) {
            $this->res['data'] = $isNull['data'];
            $this->res['text'] = $data;
        } else {
            $this->res['data'] = false;
        }
        return $this->res;
    }

    public function hasPermission($id = null)
    {
        $this->__invoke();
        $data = $this->user->permissions;
        if(!is_null($id)) {
            $getUser = User::find($id);
            if(!is_null($getUser)) {
                $data = $getUser->permissions;
            }
        }
        $isNull = $this->parser->isNull($data);
        if(!is_null($isNull['data'])) {
            $this->res['data'] = $isNull['data'];
            $this->res['text'] = $data;
        } else {
            $this->res['data'] = false;
        }
        return $this->res;
    }

    public function matchUserRole($input)
    {
        $res = null;
        try {
            $data = config('usersauth.roles');
            $res = $this->configData($input, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function matchUserPermission($input)
    {
        $this->__invoke();
        $res = null;
        try {
            $data = config('usersauth.permissions');
            $res = $this->configData($input, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function matchUserTeam($input)
    {
        $res = null;
        try {
            /**
             * Team is not an array
             * Since configData returns an array
             * take the first value of this function
             */
            $data = config('usersauth.teams');
            $res = $this->configData($input, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function matchMasterRole($input)
    {
        $res = null;
        try {
            $data = config('masterauth.roles');
            $res = $this->configData($input, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function matchMasterPermission($input)
    {
        $this->__invoke();
        $res = null;
        try {
            $data = config('masterauth.permissions');
            $res = $this->configData($input, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function matchMasterTeam($input)
    {
        $res = null;
        try {
            $data = config('masterauth.teams');
            $res = $this->configData($input, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function isMaster(bool $required = false)
    {
        $this->role = [config('masterauth.roles.creator_admin')];
        $this->permission = [config('masterauth.permissions.manage_all')];
        $this->team = [config('masterauth.teams.creators')];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isSuperAdmin(bool $required = false)
    {
        $this->role = [
            config('masterauth.roles.creator_admin'),
            config('usersauth.roles.super_admin')
        ];
        $this->permission = [
            config('masterauth.permissions.manage_all'),
            config('usersauth.permissions.manage_all')
        ];
        $this->team = [
            config('masterauth.teams.creators'),
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isAssistantAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isUserAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.user_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.edit_user')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isNewsletterAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.newsletter_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.edit_newsletter')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isMediaAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.media_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.manage_media')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isCommentAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.comment_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.manage_comments')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isBlogAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.blog_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.manage_posts')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isBookAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.book_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.manage_books')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isProductAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.product_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.manage_products')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isAnyAdmin(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.user_admin'),
            config('usersauth.roles.newsletter_admin'),
            config('usersauth.roles.media_admin'),
            config('usersauth.roles.comment_admin'),
            config('usersauth.roles.reaction_admin'),
            config('usersauth.roles.book_admin'),
            config('usersauth.roles.product_admin')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.edit_user'),
            config('usersauth.permissions.edit_newsletter'),
            config('usersauth.permissions.manage_media'),
            config('usersauth.permissions.manage_comments'),
            config('usersauth.permissions.manage_reactions'),
            config('usersauth.permissions.manage_books'),
            config('usersauth.permissions.manage_products')
        ];
        $this->team = [
            config('usersauth.teams.admins')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isBookAuthor(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.book_author')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.create_books')
        ];
        $this->team = [
            config('usersauth.teams.admins'),
            config('usersauth.teams.authors')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isProductAuthor(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.product_author')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.create_products')
        ];
        $this->team = [
            config('usersauth.teams.admins'),
            config('usersauth.teams.authors')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isPremiumUser(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.premium_user')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.use_premium')
        ];
        $this->team = [
            config('usersauth.teams.admins'),
            config('usersauth.teams.authors'),
            config('usersauth.teams.users')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isBasicUser(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.basic_user')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.use_basic')
        ];
        $this->team = [
            config('usersauth.teams.admins'),
            config('usersauth.teams.authors'),
            config('usersauth.teams.users')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function isDemoUser(bool $required = false)
    {
        $this->role = [
            config('usersauth.roles.super_admin'),
            config('usersauth.roles.assistant_admin'),
            config('usersauth.roles.demo_user')
        ];
        $this->permission = [
            config('usersauth.permissions.manage_all'),
            config('usersauth.permissions.edit_all'),
            config('usersauth.permissions.use_demo')
        ];
        $this->team = [
            config('usersauth.teams.admins'),
            config('usersauth.teams.demo')
        ];

        if(is_bool($required)) {
            $this->required = $required;
        }
        $userCan = $this->can($this->role, $this->permission, $this->team, $this->required);
        return $userCan;
    }

    public function addRoles(User $user, array $roles, array $teams)
    {
        $this->__invoke();
        $res = false;
        try {
            $teamData = null;
            $roleData = [];

            foreach ($roles as $role) {
                $getRole = Role::where('name', $role)->first();
                $isNull = $this->parser->isNull($getRole);
                if(!is_null($isNull['data'])) {
                    array_push($roleData, $getRole);
                }
            }

            $getTeam = Team::where('name', $teams[0])->first();
            $isNull = $this->parser->isNull($getTeam);
            if(!is_null($isNull['data'])) {
                $teamData = $getTeam;
            }

            if(!is_null($teamData) && count($roleData) > 0) {
                $user->addRoles($roleData, $teamData);
                $res = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function givePermissions(User $user, array $permissions, array $teams)
    {
        $this->__invoke();
        $res = false;
        try {
            $teamData = null;
            $permissionData = [];

            foreach ($permissions as $permission) {
                $getPermission = Permission::where('name', $permission)->first();
                $isNull = $this->parser->isNull($getPermission);
                if(!is_null($isNull['data'])) {
                    array_push($permissionData, $getPermission);
                }
            }

            $getTeam = Team::where('name', $teams[0])->first();
            $isNull = $this->parser->isNull($getTeam);
            if(!is_null($isNull['data'])) {
                $teamData = $getTeam;
            }

            if(!is_null($teamData) && count($permissionData) > 0) {
                $user->givePermissions($permissionData, $teamData);
                $res = true;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
        return $res;
    }

    public function isLogin()
    {
        return Auth::check();
    }

    public function findUserIdOnly(string $user_id = null)
    {
        $res = [
            'data' => null,
            'text' => null
        ];

        if(is_null($user_id)) {
            $res['text'] =  'User ID is required!' . $user_id;
            return $res;
        }

       if(!! User::whereId($user_id)->count()) {
            $res['data'] = $user_id;
            $res['text'] = 'Found a User';
       } else {
            $res['data'] = null;
            $res['text'] = 'User was not found';
       }

       return $res;
    }

    public function findUserIdOrMe(string $user_id = null)
    {
        $res = [
            'data' => null,
            'text' => null
        ];

        if(!! $user_id) {
            $res['data'] = Auth::id();
        } else {
            if(!! User::whereId($user_id)->count()) {
                 $res['data'] = $user_id;
            } else {
                 $res['data'] = Auth::id();
            }
        }

       return $res;
    }


}
