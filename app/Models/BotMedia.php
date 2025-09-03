<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BotMedia extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $table = 'bot_media';
    protected $fillable = [
        'id',
        'file_id',
        'unique_id',
        'path',
        'local_path',
        'size',
        'mime',
        'name',
        'disc',
        'media_detail_id',
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

    public function detail(): BelongsTo
    {
        return $this->belongsTo(BotMediaDetail::class, "id", "media_detail_id");
    }
}
