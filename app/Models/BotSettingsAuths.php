<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

class BotSettingsAuths extends Model
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $table = 'bot_settings_auths';
    protected $fillable = [
        'label',
        'auths',
        'users'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    // protected $hidden = [
    //     'password',
    // ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'auths' => 'json',
        'users' => 'json'
    ];

    public function findUser($userId)
    {
        $users = $this->users;
        if(!is_null($users)) {
            return in_array($users, $userId);
        }

        return false;
    }
}
