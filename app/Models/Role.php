<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const SLUG_STUDENT = 'student';

    public const SLUG_ORGANIZER = 'organizer';

    public const SLUG_ADMINISTRATOR = 'administrator';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Fixed roles for this application. Public registration may only receive the student row.
     *
     * @return array<string, array{name: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            self::SLUG_STUDENT => [
                'name' => 'Student',
                'description' => 'Browses events and registers for them. This is the only role assigned by public registration.',
            ],
            self::SLUG_ORGANIZER => [
                'name' => 'Event Organizer',
                'description' => 'Creates and manages events that they organize.',
            ],
            self::SLUG_ADMINISTRATOR => [
                'name' => 'Administrator',
                'description' => 'Manages categories, events, and user roles.',
            ],
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
