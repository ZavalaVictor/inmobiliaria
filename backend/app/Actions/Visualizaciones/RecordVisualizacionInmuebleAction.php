<?php

namespace App\Actions\Visualizaciones;

use App\Enums\OrigenVisualizacionInmueble;
use App\Models\Inmueble;
use App\Models\VisualizacionInmueble;
use Illuminate\Http\Request;

final class RecordVisualizacionInmuebleAction
{
    public function execute(
        Inmueble $inmueble,
        Request $request,
        OrigenVisualizacionInmueble $origen,
    ): VisualizacionInmueble {
        return VisualizacionInmueble::create([
            'inmueble_id' => $inmueble->getKey(),
            'session_id' => $this->sessionId($request),
            'ip_hash' => $this->ipHash($request),
            'user_agent' => $this->truncate($request->userAgent(), 500),
            'referer' => $this->truncate($request->header('referer'), 500),
            'origen' => $origen->value,
        ]);
    }

    private function sessionId(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        $sessionId = $request->session()->getId();

        return $sessionId !== '' ? $sessionId : null;
    }

    private function ipHash(Request $request): ?string
    {
        $ip = $request->ip();

        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    private function truncate(?string $value, int $length): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, $length);
    }
}
