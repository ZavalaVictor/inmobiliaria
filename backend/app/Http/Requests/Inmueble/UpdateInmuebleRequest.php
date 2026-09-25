<?php

namespace App\Http\Requests\Inmueble;

use App\Enums\EstadoDisponibilidadInmueble;
use App\Enums\TipoOperacion;
use App\Models\Inmueble;
use App\Models\User;
use App\Support\Authorization\ActorScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInmuebleRequest extends FormRequest
{
    private const MAX_PRECIO = '999999999999.99';

    private const MAX_SUPERFICIE = '99999999.99';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $inmueble = $this->route('inmueble');
        $codigo = Rule::unique('inmuebles', 'codigo');
        $slug = Rule::unique('inmuebles', 'slug');

        if ($inmueble instanceof Inmueble) {
            $codigo->ignore($inmueble->getKey());
            $slug->ignore($inmueble->getKey());
        }

        $rules = [
            'codigo' => ['sometimes', 'required', 'string', 'max:30', $codigo],
            'titulo' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => ['sometimes', 'required', 'string', 'max:180', $slug],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'tipo_operacion' => ['sometimes', 'required', Rule::enum(TipoOperacion::class)],
            'precio_venta' => $this->priceRules(),
            'renta_mensual' => $this->priceRules(),
            'superficie_terreno_m2' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:'.self::MAX_SUPERFICIE],
            'superficie_construccion_m2' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_SUPERFICIE],
            'habitaciones' => $this->unsignedTinyIntegerRules(),
            'banos_completos' => $this->unsignedTinyIntegerRules(),
            'medios_banos' => $this->unsignedTinyIntegerRules(),
            'estacionamientos' => $this->unsignedTinyIntegerRules(),
            'niveles' => $this->unsignedTinyIntegerRules(),
            'calle' => ['sometimes', 'required', 'string', 'max:150'],
            'numero_exterior' => ['sometimes', 'nullable', 'string', 'max:20'],
            'numero_interior' => ['sometimes', 'nullable', 'string', 'max:20'],
            'colonia' => ['sometimes', 'required', 'string', 'max:100'],
            'municipio' => ['sometimes', 'required', 'string', 'max:100'],
            'estado_ubicacion' => ['sometimes', 'required', 'string', 'max:100'],
            'codigo_postal' => ['sometimes', 'required', 'string', 'max:10'],
            'referencias' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitud' => ['sometimes', 'nullable', 'numeric', 'decimal:0,7', 'min:-90', 'max:90'],
            'longitud' => ['sometimes', 'nullable', 'numeric', 'decimal:0,7', 'min:-180', 'max:180'],
            'estado_disponibilidad' => ['sometimes', Rule::enum(EstadoDisponibilidadInmueble::class)],
            'publicado' => ['sometimes', 'boolean'],
            'fecha_publicacion' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            ...$this->prohibitedRules(),
        ];

        if ($this->user() instanceof User && ActorScope::isAgent($this->user())) {
            $rules['propietario_id'] = ['prohibited'];
            $rules['categoria_id'] = ['prohibited'];
        } else {
            $rules['propietario_id'] = [
                'sometimes',
                'required',
                'integer',
                Rule::exists('propietarios', 'id')->whereNull('deleted_at'),
            ];
            $rules['categoria_id'] = [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categorias', 'id')->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $inmueble = $this->route('inmueble');

            if (! $inmueble instanceof Inmueble) {
                return;
            }

            $operation = $this->effectiveValue('tipo_operacion', $inmueble->getRawOriginal('tipo_operacion'));
            $salePrice = $this->effectiveValue('precio_venta', $inmueble->getRawOriginal('precio_venta'));
            $rentPrice = $this->effectiveValue('renta_mensual', $inmueble->getRawOriginal('renta_mensual'));
            $this->validateOperationPrices($validator, $operation, [
                'precio_venta' => $salePrice,
                'renta_mensual' => $rentPrice,
            ]);

            $latitude = $this->effectiveValue('latitud', $inmueble->getRawOriginal('latitud'));
            $longitude = $this->effectiveValue('longitud', $inmueble->getRawOriginal('longitud'));
            $this->validateCoordinatePair($validator, $latitude, $longitude);
        });
    }

    private function effectiveValue(string $field, mixed $current): mixed
    {
        return array_key_exists($field, $this->all()) ? $this->input($field) : $current;
    }

    /**
     * @param  array{precio_venta:mixed,renta_mensual:mixed}  $prices
     */
    private function validateOperationPrices(Validator $validator, mixed $operation, array $prices): void
    {
        if ($operation === TipoOperacion::Venta->value) {
            if (! is_numeric($prices['precio_venta']) || (float) $prices['precio_venta'] <= 0) {
                $validator->errors()->add('precio_venta', 'El precio de venta debe ser mayor que cero.');
            }

            if ($prices['renta_mensual'] !== null) {
                $validator->errors()->add('renta_mensual', 'La renta mensual debe ser nula para una operación de venta.');
            }
        }

        if ($operation === TipoOperacion::Renta->value) {
            if (! is_numeric($prices['renta_mensual']) || (float) $prices['renta_mensual'] <= 0) {
                $validator->errors()->add('renta_mensual', 'La renta mensual debe ser mayor que cero.');
            }

            if ($prices['precio_venta'] !== null) {
                $validator->errors()->add('precio_venta', 'El precio de venta debe ser nulo para una operación de renta.');
            }
        }
    }

    private function validateCoordinatePair(Validator $validator, mixed $latitude, mixed $longitude): void
    {
        if (($latitude === null) !== ($longitude === null)) {
            $validator->errors()->add('latitud', 'La latitud y la longitud deben enviarse juntas.');
            $validator->errors()->add('longitud', 'La latitud y la longitud deben enviarse juntas.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function priceRules(): array
    {
        return ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_PRECIO];
    }

    /**
     * @return array<int, string>
     */
    private function unsignedTinyIntegerRules(): array
    {
        return ['sometimes', 'integer', 'min:0', 'max:255'];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prohibitedRules(): array
    {
        return [
            'agente_id' => ['prohibited'],
            'es_principal' => ['prohibited'],
            'asignaciones' => ['prohibited'],
            'agentes' => ['prohibited'],
            'imagenes' => ['prohibited'],
            'documentos' => ['prohibited'],
            'clientes' => ['prohibited'],
            'intereses' => ['prohibited'],
            'roles' => ['prohibited'],
            'permisos' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }
}
