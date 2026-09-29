<?php

namespace Tests\Unit;

use App\Enums\EstadoCita;
use App\Enums\EstadoCliente;
use App\Enums\EstadoCorreo;
use App\Enums\EstadoDisponibilidadInmueble;
use App\Enums\EstadoInteresInmueble;
use App\Enums\EstadoLaboralAgente;
use App\Enums\EstadoOperacion;
use App\Enums\EstadoOportunidad;
use App\Enums\EstadoRegistroPropietario;
use App\Enums\EstadoRespaldo;
use App\Enums\EstadoRestauracionRespaldo;
use App\Enums\EstadoSolicitudInformacion;
use App\Enums\EstadoUsuario;
use App\Enums\EtapaOportunidad;
use App\Enums\FrecuenciaRespaldo;
use App\Enums\MedioSolicitudInformacion;
use App\Enums\MimeTypeDocumento;
use App\Enums\NivelInteres;
use App\Enums\OrigenVisualizacionInmueble;
use App\Enums\TipoCambioCita;
use App\Enums\TipoCorreo;
use App\Enums\TipoEventoOportunidad;
use App\Enums\TipoInteraccionCliente;
use App\Enums\TipoInteresCliente;
use App\Enums\TipoOperacion;
use App\Enums\TipoPersonaPropietario;
use App\Enums\TipoRespaldo;
use App\Models\Inmueble;
use App\Models\Operacion;
use App\Models\Oportunidad;
use App\Models\OportunidadHistorial;
use PHPUnit\Framework\TestCase;

final class DomainEnumsTest extends TestCase
{
    public function test_backed_values_match_the_approved_domains(): void
    {
        $domains = [
            EstadoUsuario::class => ['pendiente', 'activo', 'bloqueado', 'inactivo'],
            TipoInteresCliente::class => ['compra', 'renta', 'ambos'],
            EstadoCliente::class => ['prospecto', 'cliente', 'inactivo'],
            EstadoLaboralAgente::class => ['activo', 'inactivo'],
            TipoPersonaPropietario::class => ['fisica', 'moral'],
            EstadoRegistroPropietario::class => ['activo', 'inactivo'],
            TipoOperacion::class => ['venta', 'renta'],
            EstadoDisponibilidadInmueble::class => ['disponible', 'vendido', 'rentado', 'inactivo'],
            NivelInteres::class => ['bajo', 'medio', 'alto'],
            EstadoInteresInmueble::class => ['activo', 'descartado', 'convertido'],
            TipoInteraccionCliente::class => ['llamada', 'correo', 'whatsapp', 'reunion', 'nota', 'seguimiento'],
            MedioSolicitudInformacion::class => ['telefono', 'whatsapp', 'correo'],
            EstadoSolicitudInformacion::class => ['nueva', 'en_atencion', 'atendida', 'descartada'],
            EtapaOportunidad::class => ['contacto_inicial', 'cita', 'negociacion', 'documentacion', 'cierre'],
            EstadoOportunidad::class => ['activa', 'ganada', 'perdida', 'cancelada'],
            TipoEventoOportunidad::class => ['creacion', 'cambio_etapa', 'cambio_estado'],
            EstadoCita::class => ['programada', 'confirmada', 'completada', 'cancelada', 'no_asistio'],
            TipoCambioCita::class => ['reprogramacion', 'cambio_agente', 'reprogramacion_y_agente'],
            EstadoOperacion::class => ['registrada', 'anulada'],
            MimeTypeDocumento::class => ['application/pdf', 'image/png', 'image/jpeg'],
            OrigenVisualizacionInmueble::class => ['landing_publica', 'portal_cliente', 'interno'],
            TipoRespaldo::class => ['manual', 'automatico'],
            EstadoRespaldo::class => ['pendiente', 'en_proceso', 'completado', 'fallido'],
            EstadoRestauracionRespaldo::class => ['en_proceso', 'completada', 'fallida'],
            TipoCorreo::class => [
                'confirmacion_cita',
                'reprogramacion_cita',
                'cancelacion_cita',
                'recuperacion_password',
                'respaldo',
                'restauracion',
                'seguridad',
                'otro',
                'solicitud_recibida',
                'solicitud_asignada',
                'oportunidad_cambio_etapa',
                'oportunidad_cierre',
                'operacion_creada',
                'operacion_cierre',
                'documento_cargado',
            ],
            EstadoCorreo::class => ['pendiente', 'enviado', 'fallido'],
            FrecuenciaRespaldo::class => ['diario', 'semanal', 'mensual'],
        ];

        foreach ($domains as $enum => $expectedValues) {
            $this->assertSame($expectedValues, array_column($enum::cases(), 'value'), $enum);
        }
    }

    public function test_try_from_returns_enum_for_valid_value_and_null_for_invalid_value(): void
    {
        $this->assertSame(EstadoUsuario::Activo, EstadoUsuario::tryFrom('activo'));
        $this->assertNull(EstadoUsuario::tryFrom('no_existe'));
    }

    public function test_tipo_operacion_is_reused_by_inmuebles_and_operaciones(): void
    {
        $this->assertSame(TipoOperacion::class, (new Inmueble)->getCasts()['tipo_operacion']);
        $this->assertSame(TipoOperacion::class, (new Operacion)->getCasts()['tipo_operacion']);
    }

    public function test_etapa_oportunidad_is_reused_by_current_and_historical_fields(): void
    {
        $this->assertSame(EtapaOportunidad::class, (new Oportunidad)->getCasts()['etapa']);
        $historyCasts = (new OportunidadHistorial)->getCasts();
        $this->assertSame(EtapaOportunidad::class, $historyCasts['etapa_anterior']);
        $this->assertSame(EtapaOportunidad::class, $historyCasts['etapa_nueva']);
        $this->assertSame(['contacto_inicial', 'cita', 'negociacion', 'documentacion', 'cierre'], array_column(EtapaOportunidad::cases(), 'value'));
    }

    public function test_estado_oportunidad_is_reused_by_current_and_historical_fields(): void
    {
        $this->assertSame(EstadoOportunidad::class, (new Oportunidad)->getCasts()['estado']);
        $historyCasts = (new OportunidadHistorial)->getCasts();
        $this->assertSame(EstadoOportunidad::class, $historyCasts['estado_anterior']);
        $this->assertSame(EstadoOportunidad::class, $historyCasts['estado_nuevo']);
        $this->assertSame(['activa', 'ganada', 'perdida', 'cancelada'], array_column(EstadoOportunidad::cases(), 'value'));
    }
}
