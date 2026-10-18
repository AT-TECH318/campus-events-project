<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('role'));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()->with('role')->orderBy('name')->paginate(15);

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('role'));
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse|UserResource
    {
        $this->authorize('updateRole', $user);

        $role = Role::query()->where('slug', $request->validated('role'))->firstOrFail();

        if ($this->wouldRemoveLastAdministrator($user, $role)) {
            return response()->json([
                'message' => 'The last administrator cannot be given another role.',
            ], 422);
        }

        $user->role_id = $role->id;
        $user->save();

        return new UserResource($user->fresh()->load('role'));
    }

    private function wouldRemoveLastAdministrator(User $user, Role $role): bool
    {
        if (! $user->hasRole(Role::SLUG_ADMINISTRATOR) || $role->slug === Role::SLUG_ADMINISTRATOR) {
            return false;
        }

        return User::query()->where('role_id', $user->role_id)->count() <= 1;
    }
}
