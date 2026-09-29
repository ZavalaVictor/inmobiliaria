<!doctype html>
<html lang="es">
<body>
    <p>Hola {{ $data['destinatario_nombre'] ?: 'usuario' }},</p>
    <p>La cita programada fue cancelada.</p>
    <p><strong>Horario que tenía:</strong> {{ $data['fecha_inicio'] }} - {{ $data['fecha_fin'] }}</p>
    @if (! empty($data['inmueble_titulo']))
        <p><strong>Inmueble:</strong> {{ $data['inmueble_titulo'] }}</p>
    @endif
</body>
</html>
