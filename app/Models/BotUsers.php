<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotUsers extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'bot_users';
    protected $fillable = [
        'id',
        'name',
        'username',
        'firstname',
        'lastname',
        'phone',
        'role',
        'email',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'role' => 'json',
        // 'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function inputs(): HasOne
    {
        return $this->hasOne(BotCacheInputs::class, "id");
    }

    public function sliders(): HasOne
    {
        return $this->hasOne(BotCacheSliders::class, "id");
    }

    public function visits(): HasOne
    {
        return $this->hasOne(BotVisitCounters::class, "id");
    }

    public function mediaCounter(): HasOne
    {
        return $this->hasOne(BotMediaCounters::class, "id");
    }

    public function isSuperAdmin(): bool
    {
        return $this->role == config('constants.user_roles.superadmin');
    }

    public function admin(): HasOne
    {
        return $this->hasOne(BotAdmins::class, "id");
    }

    public function parent(): HasOne
    {
        return $this->hasOne(BotParents::class, "id");
    }

    public function candidate(): HasOne
    {
        return $this->hasOne(BotCandidates::class, "id");
    }

    public function emojis(): HasMany
    {
        return $this->hasMany(BotEmojiReactions::class, "user_id", "id");
    }
}
