<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\EventStatus;
use App\Http\Requests\Api\V1\Concerns\ValidatesEventSchedule;
use App\Models\Event;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
{
    use ValidatesEventSchedule;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Event::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'venue' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'maximum_capacity' => ['required', 'integer', 'min:1'],
            'registration_deadline' => ['required', 'date'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'organizer_id' => $this->organizerRules(),
            'banner' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'banner_path' => ['nullable', 'string', 'max:200', 'regex:/^events\/banners\/[A-Za-z0-9][A-Za-z0-9._-]{0,180}$/', 'prohibits:banner'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->assertDeadlineIsBeforeStart($validator);
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
