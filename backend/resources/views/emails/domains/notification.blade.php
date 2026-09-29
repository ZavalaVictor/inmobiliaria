<!doctype html>
<html lang="es">
<body style="font-family:Arial,sans-serif;color:#243447;line-height:1.5">
    <h1>{{ $data['asunto'] }}</h1>
    <p>{{ $data['destinatario_nombre'] }}</p>
    <p>{{ $data['mensaje'] }}</p>
    @if (!empty($data['url']))
        <p><a href="{{ $data['url'] }}">Consultar en el portal</a></p>
    @endif
</body>
</html>
