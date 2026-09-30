<!doctype html>
<html lang="es">
<body style="font-family:Arial,sans-serif;color:#243447;line-height:1.5">
    <h1>Configura tu acceso al portal</h1>
    <p>{{ $recipientName }}, se habilitó tu acceso al Portal Cliente de SotyTech.</p>
    <p>Utiliza el siguiente enlace para establecer tu contraseña. El enlace expira en {{ $expires }} minutos.</p>
    <p><a href="{{ $url }}">Establecer contraseña</a></p>
    <p>Si no reconoces esta solicitud, puedes ignorar este correo.</p>
</body>
</html>
