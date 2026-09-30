<?php

namespace App\Services\PortalCliente;

use App\Enums\EstadoCorreo;
use App\Enums\TipoCorreo;
use App\Mail\Clientes\ClientePortalActivationMail;
use App\Models\Cliente;
use App\Models\HistorialCorreo;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Throwable;

final class ClientePortalActivationSender
{
    public function send(Cliente $cliente, User $user, User $actor): HistorialCorreo
    {
        $history = HistorialCorreo::create([
            'destinatario_user_id' => $user->getKey(),
            'cliente_id' => $cliente->getKey(),
            'relacionado_type' => $cliente->getMorphClass(),
            'relacionado_id' => $cliente->getKey(),
            'enviado_por_user_id' => $actor->getKey(),
            'destinatario_email' => mb_strtolower(trim((string) $user->email)),
            'destinatario_nombre' => $this->name($user),
            'tipo' => TipoCorreo::PortalClienteActivacion->value,
            'asunto' => 'Configura tu acceso al portal',
            'plantilla' => 'emails.clientes.portal-activacion',
            'estado' => EstadoCorreo::Pendiente->value,
        ]);

        try {
            $token = Password::broker()->createToken($user);
            $url = rtrim((string) config('app.frontend_url'), '/').'/nueva-contrasena?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], '', '&', PHP_QUERY_RFC3986);

            Mail::to($user->email)->send(new ClientePortalActivationMail(
                $this->name($user),
                $url,
                (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ));

            $history->update([
                'estado' => EstadoCorreo::Enviado->value,
                'fecha_envio' => now(),
                'mensaje_error' => null,
            ]);
        } catch (Throwable) {
            $history->update([
                'estado' => EstadoCorreo::Fallido->value,
                'fecha_envio' => null,
                'mensaje_error' => 'No fue posible enviar el correo de activación.',
            ]);
        }

        return $history->fresh();
    }

    private function name(User $user): string
    {
        return trim(implode(' ', array_filter([
            $user->nombres,
            $user->apellido_paterno,
            $user->apellido_materno,
        ])));
    }
}
