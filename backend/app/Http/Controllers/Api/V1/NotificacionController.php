<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notificacion\IndexNotificacionRequest;
use App\Http\Resources\NotificacionResource;
use App\Queries\Notificaciones\NotificacionIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificacionController extends Controller
{
    public function index(IndexNotificacionRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $notifications = (new NotificacionIndexQuery($request->user(), $filters))
            ->apply()
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();

        return NotificacionResource::collection($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notificacion): NotificacionResource
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificacion)
            ->firstOrFail();

        $notification->markAsRead();

        return new NotificacionResource($notification->fresh());
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => [
                'updated' => $updated,
            ],
        ]);
    }
}
