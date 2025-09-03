<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotVisitCounters extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'bot_visit_counters';
    protected $fillable = [
        'id',
        'one_time',
        'daily',
        'monthly',
        'yearly',
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

    public function uniqueTotal()
    {
        return BotVisitCounters::sum('one_time');
    }

    public function dailyTotal()
    {
        return BotVisitCounters::sum('daily');
    }

    public function monthlyTotal()
    {
        return BotVisitCounters::sum('monthly');
    }

    public function yearlyTotal()
    {
        return BotVisitCounters::sum('yearly');
    }

    public function demoTotal()
    {
        return BotVisitCounters::sum('demo');
    }

    public function total()
    {
        $total = $this->uniqueTotal() + $this->dailyTotal();
        return $total;
    }
}
