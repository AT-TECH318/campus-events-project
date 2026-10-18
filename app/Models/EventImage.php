<?php

namespace App\Models;

use Database\Factories\EventImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventImage extends Model
{
    /** @use HasFactory<EventImageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'is_banner',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'is_banner' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (EventImage $image): void {
            if (! $image->is_banner || $image->event_id === null) {
                return;
            }

            static::query()
                ->where('event_id', $image->event_id)
                ->where('is_banner', true)
                ->when($image->exists, function ($query) use ($image) {
                    $query->whereKeyNot($image->getKey());
                })
                ->update(['is_banner' => false]);
        });
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
