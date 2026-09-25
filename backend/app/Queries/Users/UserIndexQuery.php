<?php

namespace App\Queries\Users;

use Illuminate\Database\Eloquent\Builder;

final class UserIndexQuery
{
    private const SORTABLE_COLUMNS = [
        'id',
        'nombres',
        'apellido_paterno',
        'email',
        'estado',
        'created_at',
        'updated_at',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        $this->applySearch($query);

        if (array_key_exists('estado', $this->filters)) {
            $query->where('estado', $this->filters['estado']);
        }

        if (array_key_exists('role', $this->filters)) {
            $query->whereHas('roles', function (Builder $roleQuery): void {
                $roleQuery
                    ->where('name', $this->filters['role'])
                    ->where('guard_name', 'web');
            });
        }

        $sort = $this->filters['sort'] ?? 'created_at';
        $direction = $this->filters['direction'] ?? 'desc';

        return $query
            ->orderBy(
                in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'created_at',
                $direction === 'asc' ? 'asc' : 'desc'
            );
    }

    private function applySearch(Builder $query): void
    {
        $search = $this->filters['q'] ?? null;

        if ($search === null || $search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search): void {
            $term = "%{$search}%";

            $builder->where('nombres', 'like', $term)
                ->orWhere('apellido_paterno', 'like', $term)
                ->orWhere('apellido_materno', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('telefono', 'like', $term);
        });
    }
}
