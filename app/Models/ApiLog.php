<?php

namespace App\Models;

use Database\Factories\ApiLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    /** @use HasFactory<ApiLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * Request and response bodies are intentionally absent so passwords and tokens are not stored here.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'method',
        'path',
        'status_code',
        'ip_address',
        'user_agent',
        'duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
