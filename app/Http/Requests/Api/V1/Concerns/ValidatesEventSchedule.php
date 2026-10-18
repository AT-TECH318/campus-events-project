<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

trait ValidatesEventSchedule
{
    protected function assertDeadlineIsBeforeStart(Validator $validator, ?Event $event = null): void
    {
        $date = $this->input('event_date', $event?->event_date?->toDateString());
        $start = $this->input('start_time', $event?->start_time);
        $deadline = $this->input('registration_deadline', $event?->registration_deadline);

        if ($date === null || $date === '' || $start === null || $start === '' || $deadline === null || $deadline === '') {
            return;
        }

        try {
            $startsAt = Carbon::parse($date.' '.Carbon::parse($start)->format('H:i:s'));
            $deadlineAt = Carbon::parse($deadline);
        } catch (\Exception) {
            return;
        }

        if ($deadlineAt->greaterThan($startsAt)) {
            $validator->errors()->add(
                'registration_deadline',
                'The registration deadline must be on or before the event start.'
            );
        }
    }
}
