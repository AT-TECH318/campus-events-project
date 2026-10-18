<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EventStatus;
use App\Http\Requests\Api\V1\Concerns\ValidatesEventSchedule;
use App\Models\Event;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    use ValidatesEventSchedule;

    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event instanceof Event && ($this->user()?->can('update', $event) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'venue' => ['sometimes', 'required', 'string', 'max:255'],
            'event_date' => ['sometimes', 'required', 'date'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i:s'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i:s'],
            'maximum_capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'registration_deadline' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', Rule::enum(EventStatus::class)],
            'organizer_id' => $this->organizerRules(),
            'banner' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'banner_path' => ['nullable', 'string', 'max:200', 'regex:/^events\/banners\/[A-Za-z0-9][A-Za-z0-9._-]{0,180}$/', 'prohibits:banner'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Event|null $event */
            $event = $this->route('event');
            $start = $this->input('start_time', $event?->start_time);
            $end = $this->input('end_time', $event?->end_time);

            if ($start !== null && $end !== null && $end <= $start) {
                $validator->errors()->add('end_time', 'The end time must be after the start time.');
            }

            $this->assertDeadlineIsBeforeStart($validator, $event);
        });
    }

    /**
     * @return list<mixed>
     */
    private function organizerRules(): array
    {
        if (! $this->user()?->hasRole(Role::SLUG_ADMINISTRATOR)) {
            return ['prohibited'];
        }

        $roleIds = Role::query()
            ->whereIn('slug', [Role::SLUG_ORGANIZER, Role::SLUG_ADMINISTRATOR])
            ->pluck('id');

        return [
            'sometimes',
            'integer',
            Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role_id', $roleIds)),
        ];
    }
}
