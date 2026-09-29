{{ $data['asunto'] }}

{{ $data['destinatario_nombre'] }}

{{ $data['mensaje'] }}
@if (!empty($data['url']))

Consultar en el portal: {{ $data['url'] }}
@endif
