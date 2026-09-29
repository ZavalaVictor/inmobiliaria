<?php

namespace App\Queries\Notificaciones;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

final class NotificacionIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly User $user,
        private readonly array $filters,
    ) {}

    /**
     * @return Builder<DatabaseNotification>
     */
    public function apply(): Builder
    {
        $query = $this->user->notifications()->getQuery();

        if (($this->filters['estado'] ?? null) === 'leida') {
            $query->whereNotNull('read_at');
        }

        if (($this->filters['estado'] ?? null) === 'no_leida') {
            $query->whereNull('read_at');
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
