<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * role_id is omitted on purpose. A public registration request cannot
     * set an organizer or administrator role through mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->role_id !== null) {
                return;
            }

            $definition = Role::definitions()[Role::SLUG_STUDENT];

            $user->role_id = Role::query()->firstOrCreate(
                ['slug' => Role::SLUG_STUDENT],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ],
            )->getKey();
        });
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function organizedEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function registeredEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_registrations')
            ->withPivot('registered_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<ApiLog, $this>
     */
    public function apiLogs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }
}
