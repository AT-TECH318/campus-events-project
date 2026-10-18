<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventRegistrationResource;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventRegistrationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EventRegistration::class);

        $registrations = $request->user()
            ->registrations()
            ->with('event')
            ->latest('registered_at')
            ->paginate(15);

        return EventRegistrationResource::collection($registrations);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $this->authorize('register', $event);

        $registration = $event->registerAttendee($request->user());

        return (new EventRegistrationResource($registration))
            ->additional(['message' => 'Registration successful.'])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        $registration = $event->registrations()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($registration !== null) {
            $this->authorize('delete', $registration);
        }

        $event->cancelAttendee($request->user());

        return response()->json([
            'message' => 'Registration cancelled.',
        ]);
    }
}
