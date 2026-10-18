<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Event */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['category', 'organizer', 'banner']);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'venue' => $this->venue,
            'event_date' => $this->event_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'maximum_capacity' => $this->maximum_capacity,
            'registration_deadline' => $this->registration_deadline,
            'status' => $this->status?->value,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ],
            'organizer' => [
                'id' => $this->organizer_id,
                'name' => $this->organizer?->name,
            ],
            'banner' => $this->bannerPayload(),
        ];
    }

    /**
     * @return array{id: int, path: string, url: string, original_name: string}|null
     */
    private function bannerPayload(): ?array
    {
        $banner = $this->banner;

        if ($banner === null || $banner->disk !== 'public') {
            return null;
        }

        if (preg_match('#^events/banners/[A-Za-z0-9][A-Za-z0-9._-]{0,180}$#', $banner->path) !== 1) {
            return null;
        }

        return [
            'id' => $banner->id,
            'path' => $banner->path,
            'url' => Storage::disk('public')->url($banner->path),
            'original_name' => $banner->original_name,
        ];
    }
}
