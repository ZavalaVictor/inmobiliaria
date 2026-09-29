Hola {{ $data['destinatario_nombre'] ?: 'usuario' }},

La cita programada fue cancelada.
Horario que tenía: {{ $data['fecha_inicio'] }} - {{ $data['fecha_fin'] }}
@if (! empty($data['inmueble_titulo']))
Inmueble: {{ $data['inmueble_titulo'] }}
@endif
