<!doctype html>
<html lang="es">
<body>
    <p>Hola {{ $data['destinatario_nombre'] ?: 'usuario' }},</p>
    <p>Tu cita fue reprogramada.</p>
    <p><strong>Horario anterior:</strong> {{ $data['fecha_inicio_anterior'] }} - {{ $data['fecha_fin_anterior'] }}</p>
    <p><strong>Horario nuevo:</strong> {{ $data['fecha_inicio_nueva'] }} - {{ $data['fecha_fin_nueva'] }}</p>
    @if (! empty($data['inmueble_titulo']))
        <p><strong>Inmueble:</strong> {{ $data['inmueble_titulo'] }}</p>
    @endif
</body>
</html>
