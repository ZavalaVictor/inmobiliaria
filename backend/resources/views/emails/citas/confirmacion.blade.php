<!doctype html>
<html lang="es">
<body>
    <p>Hola {{ $data['destinatario_nombre'] ?: 'usuario' }},</p>
    <p>Tu cita fue registrada correctamente.</p>
    <p><strong>Fecha y hora:</strong> {{ $data['fecha_inicio'] }} - {{ $data['fecha_fin'] }}</p>
    @if (! empty($data['inmueble_titulo']))
        <p><strong>Inmueble:</strong> {{ $data['inmueble_titulo'] }}</p>
    @endif
    <p>Consulta la cita desde tu aplicación.</p>
</body>
</html>
