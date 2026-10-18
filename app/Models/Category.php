<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

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
     * Demonstration categories. The assignment brief was not in the repository,
     * so replace this catalogue if the brief names a different set.
     *
     * @return array<string, array{name: string, description: string}>
     */
    public static function catalogue(): array
    {
        return [
            'academic' => [
                'name' => 'Academic',
                'description' => 'Lectures, seminars, and faculty programmes.',
            ],
            'sports' => [
                'name' => 'Sports',
                'description' => 'Matches, training sessions, and sports clubs.',
            ],
            'cultural' => [
                'name' => 'Cultural',
                'description' => 'Music, drama, and cultural celebrations.',
            ],
            'career' => [
                'name' => 'Career',
                'description' => 'Career fairs, employer talks, and internship briefings.',
            ],
            'social' => [
                'name' => 'Social',
                'description' => 'Student social gatherings and community events.',
            ],
            'workshop' => [
                'name' => 'Workshop',
                'description' => 'Hands-on skills workshops and short courses.',
            ],
        ];
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
