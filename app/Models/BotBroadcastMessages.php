<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotBroadcastMessages extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $table = 'bot_broadcast_messages';
    protected $fillable = [
        'type',
        'label',
        'detail',
        'can_repeat',
        'admin_id',
        'media_id',
        'start_at',
        'end_at'
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
        'can_repeat' => 'boolean',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(BotAdmins::class, "id", "admin_id");
    }

    public function lockedUsers(): HasMany
    {
        return $this->hasMany(BotBroadcastLockedUsers::class, "broadcast_id", "id");
    }

    public function seenByUsers(): HasMany
    {
        return $this->hasMany(BotBroadcastSeenUsers::class, "broadcast_id", "id");
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(BotMediaDetail::class, "id", "media_id");
    }
}
