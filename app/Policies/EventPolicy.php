<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;

class EventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Event $event): bool
    {
        if ($event->status !== EventStatus::Draft) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->hasRole(Role::SLUG_ADMINISTRATOR)) {
            return true;
        }

        return $user->hasRole(Role::SLUG_ORGANIZER)
            && (int) $event->organizer_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::SLUG_ORGANIZER) || $user->hasRole(Role::SLUG_ADMINISTRATOR);
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->hasRole(Role::SLUG_ADMINISTRATOR)) {
            return true;
        }

        return $user->hasRole(Role::SLUG_ORGANIZER)
            && (int) $event->organizer_id === (int) $user->id;
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }

    public function register(User $user, Event $event): bool
    {
        return true;
    }
}
