<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EventIndexRequest;
use App\Http\Requests\Api\V1\ReplaceEventRequest;
use App\Http\Requests\Api\V1\StoreEventRequest;
use App\Http\Requests\Api\V1\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Role;
use App\Services\EventBannerStore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(EventIndexRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Event::class);

        $perPage = (int) ($request->validated('per_page') ?? 15);

        $events = Event::query()
            ->with(['category', 'organizer', 'banner'])
            ->visibleTo($request->user())
            ->filtered($request->validated());

        if ($request->boolean('mine')) {
            abort_unless($request->user() !== null, 401, 'Unauthenticated.');
            $events->where('organizer_id', $request->user()->id);
        }

        $events = $events
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->paginate($perPage);

        return EventResource::collection($events);
    }

    public function show(Event $event): EventResource
    {
        $this->authorize('view', $event);

        return new EventResource($event);
    }

    public function store(StoreEventRequest $request, EventBannerStore $banners): JsonResponse
    {
        $this->authorize('create', Event::class);

        $attributes = $request->safe()->except(['banner', 'banner_path']);
        $attributes['organizer_id'] = $this->organizerId($request, $attributes);

        $event = DB::transaction(function () use ($request, $attributes, $banners): Event {
            $event = Event::query()->create($attributes);
            $banners->attach($event, $request->file('banner'), $request->validated('banner_path'));

            return $event;
        });

        return (new EventResource($event->load(['category', 'organizer', 'banner'])))
            ->response()
            ->setStatusCode(201);
    }

    public function replace(ReplaceEventRequest $request, Event $event, EventBannerStore $banners): EventResource
    {
        return $this->save($request, $event, $banners);
    }

    public function update(UpdateEventRequest $request, Event $event, EventBannerStore $banners): EventResource
    {
        return $this->save($request, $event, $banners);
    }

    public function destroy(Event $event): Response
    {
        $this->authorize('delete', $event);

        $event->delete();

        return response()->noContent();
    }

    private function save(FormRequest $request, Event $event, EventBannerStore $banners): EventResource
    {
        $this->authorize('update', $event);

        $attributes = $request->safe()->except(['banner', 'banner_path']);

        if (! $request->user()->hasRole(Role::SLUG_ADMINISTRATOR)) {
            unset($attributes['organizer_id']);
        }

        DB::transaction(function () use ($request, $event, $attributes, $banners): void {
            $event->update($attributes);
            $banners->attach($event, $request->file('banner'), $request->validated('banner_path'));
        });

        return new EventResource($event->fresh()->load(['category', 'organizer', 'banner']));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function organizerId(StoreEventRequest $request, array $attributes): int
    {
        if ($request->user()->hasRole(Role::SLUG_ADMINISTRATOR) && isset($attributes['organizer_id'])) {
            return (int) $attributes['organizer_id'];
        }

        return (int) $request->user()->id;
    }
}
