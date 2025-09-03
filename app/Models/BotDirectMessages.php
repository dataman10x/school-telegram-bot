<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotDirectMessages extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $table = 'bot_direct_messages';
    protected $fillable = [
        'message',
        'user_id',
        'media_id'
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
    //     'is_read' => 'boolean',
    // ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(BotUsers::class, "id", "user_id");
    }

    public function messages(): HasMany
    {
        return $this->hasMany(BotDmResponses::class, "message_id");
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(BotMediaDetail::class, "id", "media_id");
    }
}
