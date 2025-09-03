<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotBroadcastSeenUsers extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $table = 'bot_broadcast_seen_users';
    protected $fillable = [
        'broadcast_id',
        'user_id',
        'comment'
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
    // protected $casts = [
    //     'can_repeat' => 'boolean',
    // ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(BotUsers::class, "id", "user_id");
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(BotBroadcastMessages::class, "id", "broadcast_id");
    }
}
