<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotMediaCounters extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'bot_media_counters';
    protected $fillable = [
        'id',
        'text',
        'photo',
        'audio',
        'video',
        'document',
        'last_date'
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
    //     'last_date' => 'timestamp',
    // ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(BotUsers::class, "id");
    }

    public function textTotal()
    {
        return BotMediaCounters::sum('text');
    }

    public function photoTotal()
    {
        return BotMediaCounters::sum('photo');
    }

    public function audioTotal()
    {
        return BotMediaCounters::sum('audio');
    }

    public function videoTotal()
    {
        return BotMediaCounters::sum('video');
    }

    public function documentTotal()
    {
        return BotMediaCounters::sum('document');
    }

    public function total()
    {
        $total = $this->textTotal() + $this->photoTotal() + $this->audioTotal() + $this->videoTotal() + $this->documentTotal();
        return $total;
    }
}
