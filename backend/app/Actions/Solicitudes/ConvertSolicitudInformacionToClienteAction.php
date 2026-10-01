<?php

namespace App\Actions\Solicitudes;

use App\Enums\EstadoCliente;
use App\Models\Cliente;
use App\Models\ClienteInmuebleInteres;
use App\Models\SolicitudInformacion;
use Illuminate\Support\Facades\DB;

final class ConvertSolicitudInformacionToClienteAction
{
    /**
     * @return array{cliente: Cliente, creado: bool}
     */
    public function execute(SolicitudInformacion $solicitud): array
    {
        return DB::transaction(function () use ($solicitud): array {
            $solicitud->loadMissing('inmueble');

            $cliente = Cliente::query()
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($solicitud->email))])
                ->first();
            $creado = false;

            if ($cliente === null) {
                [$nombres, $apellidoPaterno, $apellidoMaterno] = $this->splitName($solicitud->nombre);
                $cliente = Cliente::create([
                    'nombres' => $nombres,
                    'apellido_paterno' => $apellidoPaterno,
                    'apellido_materno' => $apellidoMaterno,
                    'email' => mb_strtolower(trim($solicitud->email)),
                    'telefono' => $solicitud->telefono,
                    'preferencias' => $solicitud->mensaje,
                    'estado_cliente' => EstadoCliente::Prospecto->value,
                ]);
                $creado = true;
            }

            $solicitud->update(['cliente_id' => $cliente->getKey()]);

            if ($solicitud->inmueble_id !== null) {
                $interest = ClienteInmuebleInteres::withTrashed()->firstOrNew([
                    'cliente_id' => $cliente->getKey(),
                    'inmueble_id' => $solicitud->inmueble_id,
                ]);
                $interest->notas = $solicitud->mensaje;
                $interest->save();
                if ($interest->trashed()) {
                    $interest->restore();
                }
            }

            return [
                'cliente' => $cliente->fresh(['user']),
                'creado' => $creado,
            ];
        });
    }

    /**
     * @return array{0: string, 1: string, 2: ?string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['Cliente'];
        $nombres = array_shift($parts) ?? 'Cliente';
        $apellidoPaterno = array_shift($parts) ?? 'Sin apellido';
        $apellidoMaterno = count($parts) > 0 ? implode(' ', $parts) : null;

        return [$nombres, $apellidoPaterno, $apellidoMaterno];
    }
}
