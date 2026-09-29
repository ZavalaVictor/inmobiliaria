<?php

namespace App\Http\Resources;

use App\Models\Cita;
use App\Models\Documento;
use App\Models\Operacion;
use App\Models\Oportunidad;
use App\Models\Respaldo;
use App\Models\SolicitudInformacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistorialCorreoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdministratorShow = $request->routeIs('historial-correos.show')
            && $request->user()?->hasRole('Administrador');

        return [
            'id' => $this->id,
            'destinatario_user_id' => $this->destinatario_user_id,
            'cliente_id' => $this->cliente_id,
            'cita_id' => $this->cita_id,
            'relacionado' => $this->when($this->relacionado_type !== null, [
                'tipo' => $this->relatedTypeName($this->relacionado_type),
                'id' => $this->relacionado_id,
            ]),
            'enviado_por_user_id' => $this->enviado_por_user_id,
            'destinatario_email' => $this->destinatario_email,
            'destinatario_nombre' => $this->destinatario_nombre,
            'tipo' => $this->tipo?->value ?? $this->tipo,
            'asunto' => $this->asunto,
            'plantilla' => $this->plantilla,
            'estado' => $this->estado?->value ?? $this->estado,
            'fecha_envio' => $this->fecha_envio?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'mensaje_error' => $this->when($isAdministratorShow, $this->mensaje_error),
            'destinatario' => $this->whenLoaded('destinatario', function (): ?array {
                return $this->destinatario === null ? null : [
                    'id' => $this->destinatario->id,
                    'nombres' => $this->destinatario->nombres,
                    'apellido_paterno' => $this->destinatario->apellido_paterno,
                    'apellido_materno' => $this->destinatario->apellido_materno,
                ];
            }),
            'cliente' => $this->whenLoaded('cliente', function (): ?array {
                return $this->cliente === null ? null : [
                    'id' => $this->cliente->id,
                    'nombres' => $this->cliente->nombres,
                    'apellido_paterno' => $this->cliente->apellido_paterno,
                    'apellido_materno' => $this->cliente->apellido_materno,
                ];
            }),
            'cita' => $this->whenLoaded('cita', function (): ?array {
                return $this->cita === null ? null : [
                    'id' => $this->cita->id,
                    'fecha_inicio' => $this->cita->fecha_inicio?->toISOString(),
                    'fecha_fin' => $this->cita->fecha_fin?->toISOString(),
                    'estado' => $this->cita->estado?->value ?? $this->cita->estado,
                ];
            }),
            'enviado_por' => $this->whenLoaded('enviadoPor', function (): ?array {
                return $this->enviadoPor === null ? null : [
                    'id' => $this->enviadoPor->id,
                    'nombres' => $this->enviadoPor->nombres,
                    'apellido_paterno' => $this->enviadoPor->apellido_paterno,
                    'apellido_materno' => $this->enviadoPor->apellido_materno,
                ];
            }),
        ];
    }

    private function relatedTypeName(?string $type): ?string
    {
        return match ($type) {
            SolicitudInformacion::class => 'solicitud',
            Oportunidad::class => 'oportunidad',
            Operacion::class => 'operacion',
            Documento::class => 'documento',
            Respaldo::class => 'respaldo',
            Cita::class => 'cita',
            default => null,
        };
    }
}
