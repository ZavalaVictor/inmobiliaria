<?php

namespace App\Queries\HistorialCorreos;

use Illuminate\Database\Eloquent\Builder;

final class HistorialCorreoIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach ([
            'estado',
            'tipo',
            'destinatario_user_id',
            'cliente_id',
            'cita_id',
            'enviado_por_user_id',
            'destinatario_email',
        ] as $field) {
            if (array_key_exists($field, $this->filters)) {
                $query->where($field, $this->filters[$field]);
            }
        }

        foreach ([
            'fecha_envio_desde' => ['fecha_envio', '>='],
            'fecha_envio_hasta' => ['fecha_envio', '<='],
        ] as $filter => [$column, $operator]) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($column, $operator, $this->filters[$filter]);
            }
        }

        if (($this->filters['q'] ?? '') !== '') {
            $term = '%'.$this->filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search->where('destinatario_email', 'like', $term)
                    ->orWhere('destinatario_nombre', 'like', $term)
                    ->orWhere('asunto', 'like', $term)
                    ->orWhere('plantilla', 'like', $term);
            });
        }

        $allowedSorts = [
            'id',
            'destinatario_email',
            'tipo',
            'estado',
            'asunto',
            'fecha_envio',
            'created_at',
        ];
        $sort = in_array($this->filters['sort'] ?? null, $allowedSorts, true)
            ? $this->filters['sort']
            : 'created_at';
        $direction = ($this->filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction);
    }
}
