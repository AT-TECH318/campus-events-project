<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Event;

class ReplaceEventRequest extends StoreEventRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && ($this->user()?->can('update', $event) ?? false);
    }
}
