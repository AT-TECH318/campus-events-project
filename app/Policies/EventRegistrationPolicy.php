<?php

namespace App\Policies;

use App\Models\EventRegistration;
use App\Models\User;

class EventRegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EventRegistration $registration): bool
    {
        return (int) $registration->user_id === (int) $user->id;
    }

    public function delete(User $user, EventRegistration $registration): bool
    {
        return (int) $registration->user_id === (int) $user->id;
    }
}
