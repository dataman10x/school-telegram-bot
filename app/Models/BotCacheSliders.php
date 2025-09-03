<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotCacheSliders extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'bot_cache_sliders';
    protected $fillable = [
        'id',
        'label',
        'command',
        'first_step',
        'previous_step',
        'active_step',
        'next_step',
        'last_step',
        'steps_info'
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
        'steps_info' => 'json',
        // 'active_step' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(BotUsers::class, "id");
    }
}
