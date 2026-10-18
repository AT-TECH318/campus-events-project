<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing('role');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => [
                'id' => $this->role?->id,
                'name' => $this->role?->name,
                'slug' => $this->role?->slug,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
