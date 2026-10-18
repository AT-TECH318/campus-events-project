<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Exceptions\EventRegistrationException;
use Carbon\Carbon;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * organizer_id is fillable for seeders and for a later controller that sets
     * it from the authenticated user. It must not be taken from an untrusted client.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'organizer_id',
        'title',
        'description',
        'venue',
        'event_date',
        'start_time',
        'end_time',
        'maximum_capacity',
        'registration_deadline',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'registration_deadline' => 'datetime',
            'maximum_capacity' => 'integer',
            'organizer_id' => 'integer',
            'category_id' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if ($event->maximum_capacity < 1) {
                throw new InvalidArgumentException('Event capacity must be at least 1.');
            }

            if ($event->start_time !== null && $event->end_time !== null && $event->end_time <= $event->start_time) {
                throw new InvalidArgumentException('Event end time must be after the start time on the same day.');
            }

            if ($event->event_date !== null && $event->start_time !== null && $event->registration_deadline !== null) {
                $startsAt = Carbon::parse($event->event_date->toDateString().' '.$event->start_time);

                if ($event->registration_deadline->gt($startsAt)) {
                    throw new InvalidArgumentException('The registration deadline must be on or before the event start.');
                }
            }
        });
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_registrations')
            ->withPivot('registered_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<EventImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class);
    }

    /**
     * @return HasOne<EventImage, $this>
     */
    public function banner(): HasOne
    {
        return $this->hasOne(EventImage::class)->where('is_banner', true);
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where('status', EventStatus::Published)
            ->whereDate('event_date', '>=', now()->toDateString());
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query
            ->where('status', EventStatus::Published)
            ->whereDate('event_date', '<', now()->toDateString());
    }

    /**
     * Draft events stay with their organizer and with administrators.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->hasRole(Role::SLUG_ADMINISTRATOR)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('status', '!=', EventStatus::Draft->value);

            if ($user?->hasRole(Role::SLUG_ORGANIZER)) {
                $query->orWhere('organizer_id', $user->id);
            }
        });
    }

    /**
     * @param  Builder<Event>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Event>
     */
    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        if (filled($filters['title'] ?? null)) {
            $this->whereLike($query, 'title', (string) $filters['title']);
        }

        if (filled($filters['organizer'] ?? null)) {
            $term = (string) $filters['organizer'];
            $query->whereHas('organizer', function (Builder $query) use ($term): void {
                $this->whereLike($query, 'name', $term);
            });
        }

        if (filled($filters['category_id'] ?? null)) {
            $query->where('category_id', $filters['category_id']);
        }

        if (filled($filters['category'] ?? null)) {
            $slug = (string) $filters['category'];
            $query->whereHas('category', function (Builder $query) use ($slug): void {
                $query->where('slug', $slug);
            });
        }

        if (filled($filters['date'] ?? null)) {
            $query->whereDate('event_date', $filters['date']);
        }

        if (($filters['timeframe'] ?? null) === 'upcoming') {
            $query->upcoming();
        } elseif (($filters['timeframe'] ?? null) === 'past') {
            $query->past();
        }

        return $query;
    }

    /**
     * @param  Builder<Event>  $query
     */
    private function whereLike(Builder $query, string $column, string $value): void
    {
        $pattern = '%'.addcslashes($value, '%_\\').'%';

        $query->whereRaw($column.' like ? escape ?', [$pattern, '\\']);
    }

    public function remainingCapacity(): int
    {
        return max(0, $this->maximum_capacity - $this->registrations()->count());
    }

    /**
     * Register one user while holding a row lock on this event.
     *
     * The lock makes the capacity count stable for concurrent requests.
     * The unique (event_id, user_id) index stops a duplicate if two requests pass the count together.
     */
    public function registerAttendee(User $user): EventRegistration
    {
        return DB::transaction(function () use ($user): EventRegistration {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== EventStatus::Published) {
                throw new EventRegistrationException(
                    EventRegistrationException::NOT_OPEN,
                    'This event is not open for registration.'
                );
            }

            if ($locked->registration_deadline->lt(now())) {
                throw new EventRegistrationException(
                    EventRegistrationException::DEADLINE_PASSED,
                    'The registration deadline has passed.'
                );
            }

            $alreadyRegistered = $locked->registrations()
                ->where('user_id', $user->getKey())
                ->exists();

            if ($alreadyRegistered) {
                throw new EventRegistrationException(
                    EventRegistrationException::ALREADY_REGISTERED,
                    'This user is already registered for the event.'
                );
            }

            if ($locked->registrations()->count() >= $locked->maximum_capacity) {
                throw new EventRegistrationException(
                    EventRegistrationException::AT_CAPACITY,
                    'This event has reached its maximum capacity.'
                );
            }

            try {
                return $locked->registrations()->create([
                    'user_id' => $user->getKey(),
                    'registered_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new EventRegistrationException(
                    EventRegistrationException::ALREADY_REGISTERED,
                    'This user is already registered for the event.'
                );
            }
        });
    }

    /**
     * Remove one user's registration while holding a row lock on this event.
     *
     * The lock keeps a cancellation and a new registration from counting the same seat.
     */
    public function cancelAttendee(User $user): void
    {
        DB::transaction(function () use ($user): void {
            /** @var self $locked */
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            $registration = $locked->registrations()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            if ($registration === null) {
                throw new EventRegistrationException(
                    EventRegistrationException::NOT_REGISTERED,
                    'You are not registered for this event.'
                );
            }

            $registration->delete();
        });
    }
}
