<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitud para restablecer tu contraseña</title>
</head>
<body style="margin:0;background:#F7FAFC;color:#0B294D;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7FAFC;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:570px;background:#FFFFFF;border:1px solid #DDE7ED;border-radius:12px;">
                    <tr>
                        <td style="padding:32px 36px 12px;text-align:center;">
                            <div style="font-size:25px;font-weight:700;letter-spacing:-0.04em;color:#0B294D;">Soty<span style="color:#0F766E;">Tech</span></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 36px 36px;">
                            <h1 style="margin:0 0 20px;font-size:24px;line-height:1.25;color:#0B294D;">Solicitud para restablecer tu contraseña</h1>
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.6;color:#526B83;">Recibimos una solicitud para cambiar la contraseña de tu cuenta.</p>
                            <p style="margin:0 0 28px;text-align:center;">
                                <a href="{{ $url }}" style="display:inline-block;padding:14px 24px;border-radius:8px;background:#0F766E;color:#FFFFFF;font-size:16px;font-weight:700;text-decoration:none;">Restablecer contraseña</a>
                            </p>
                            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#71849C;">Este enlace es temporal y expirará en {{ $expires }} minutos.</p>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#71849C;">Si tú no solicitaste este cambio, puedes ignorar este correo.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:18px 0 0;font-size:12px;color:#8AA0B3;">Equipo SotyTech</p>
            </td>
        </tr>
    </table>
</body>
</html>
