Hola {{ $data['destinatario_nombre'] ?: 'usuario' }},

Tu cita fue reprogramada.
Horario anterior: {{ $data['fecha_inicio_anterior'] }} - {{ $data['fecha_fin_anterior'] }}
Horario nuevo: {{ $data['fecha_inicio_nueva'] }} - {{ $data['fecha_fin_nueva'] }}
@if (! empty($data['inmueble_titulo']))
Inmueble: {{ $data['inmueble_titulo'] }}
@endif
