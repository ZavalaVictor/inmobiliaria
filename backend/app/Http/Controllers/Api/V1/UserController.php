<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\DeleteUserAction;
use App\Actions\Users\SyncUserRolesAction;
use App\Actions\Users\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserRolesRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Queries\Users\UserIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $query = (new UserIndexQuery($request->validated()))->apply(User::query());
        $query->with('roles:id,name,guard_name');
        $perPage = (int) ($request->validated('per_page') ?? 15);

        return UserResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function store(CreateUserRequest $request, CreateUserAction $action): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = $action->execute($request->validated(), $request->user());
        $user->load('roles:id,name,guard_name');

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);
        $user->load('roles:id,name,guard_name');

        return new UserResource($user);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        UpdateUserAction $action
    ): UserResource {
        Gate::authorize('update', $user);

        return new UserResource($action->execute($user, $request->validated(), $request->user()));
    }

    public function updateRoles(
        UpdateUserRolesRequest $request,
        User $user,
        SyncUserRolesAction $action
    ): UserResource {
        Gate::authorize('assignRoles', $user);

        return new UserResource($action->execute($user, $request->validated('roles'), $request->user()));
    }

    public function destroy(User $user, DeleteUserAction $action): Response
    {
        Gate::authorize('delete', $user);
        $action->execute($user, request()->user());

        return response()->noContent();
    }
}
