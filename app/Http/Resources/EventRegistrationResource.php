<?php

namespace App\Http\Resources;

use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EventRegistration */
class EventRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing('event');

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'registered_at' => $this->registered_at,
            'event' => [
                'id' => $this->event?->id,
                'title' => $this->event?->title,
                'venue' => $this->event?->venue,
                'event_date' => $this->event?->event_date?->toDateString(),
                'status' => $this->event?->status?->value,
            ],
        ];
    }
}
