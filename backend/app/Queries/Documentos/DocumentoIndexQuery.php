<?php

namespace App\Queries\Documentos;

use Illuminate\Database\Eloquent\Builder;

final class DocumentoIndexQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private readonly array $filters) {}

    public function apply(Builder $query): Builder
    {
        foreach ([
            'categoria_documento_id',
            'subido_por_user_id',
            'mime_type',
            'propietario_id',
            'cliente_id',
            'inmueble_id',
            'operacion_id',
        ] as $field) {
            if (array_key_exists($field, $this->filters)) {
                $query->where($field, $this->filters[$field]);
            }
        }

        if (isset($this->filters['destino'])) {
            $query->whereNotNull($this->destinationColumn($this->filters['destino']));
        }

        foreach ([
            ['fecha_documento_desde', 'fecha_documento', '>='],
            ['fecha_documento_hasta', 'fecha_documento', '<='],
            ['fecha_vencimiento_desde', 'fecha_vencimiento', '>='],
            ['fecha_vencimiento_hasta', 'fecha_vencimiento', '<='],
        ] as [$filter, $column, $operator]) {
            if (array_key_exists($filter, $this->filters)) {
                $query->where($column, $operator, $this->filters[$filter]);
            }
        }

        $search = $this->filters['q'] ?? null;

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('nombre_original', 'like', "%{$search}%")
                    ->orWhere('observaciones', 'like', "%{$search}%");
            });
        }

        $sort = $this->filters['sort'] ?? 'created_at';
        $direction = $this->filters['direction'] ?? 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction);
    }

    private function destinationColumn(string $destination): string
    {
        return match ($destination) {
            'propietario' => 'propietario_id',
            'cliente' => 'cliente_id',
            'inmueble' => 'inmueble_id',
            'operacion' => 'operacion_id',
        };
    }
}
