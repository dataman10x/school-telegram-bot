<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotReviews extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'bot_reviews';
    protected $fillable = [
        'id',
        'note',
        'approved_by',
        'approved_at'
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
    //     'is_approved' => 'boolean',
    // ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(BotUsers::class, "id");
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(BotUsers::class, "approved_by", "id");
    }
}
