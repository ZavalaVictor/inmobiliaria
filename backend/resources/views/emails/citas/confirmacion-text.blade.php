Hola {{ $data['destinatario_nombre'] ?: 'usuario' }},

Tu cita fue registrada correctamente.
Fecha y hora: {{ $data['fecha_inicio'] }} - {{ $data['fecha_fin'] }}
@if (! empty($data['inmueble_titulo']))
Inmueble: {{ $data['inmueble_titulo'] }}
@endif
