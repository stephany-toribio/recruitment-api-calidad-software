<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
{{-- Estilos en línea: la mayoría de clientes de correo ignoran <style> --}}
<body style="margin:0; padding:0; background-color:#F8FAFC; font-family:Arial, Helvetica, sans-serif; color:#111111;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#FFFFFF; border:1px solid #E2E8F0; border-radius:12px; padding:32px;">
                    <tr>
                        <td style="font-size:20px; font-weight:bold; padding-bottom:16px;">
                            {{ config('app.name') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:15px; line-height:22px; padding-bottom:16px;">
                            Hola, {{ $nombre }}:<br>
                            Recibimos una solicitud para restablecer la contraseña de tu cuenta.
                            Ingresa este código en la plataforma:
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:8px 0 24px;">
                            <span style="display:inline-block; font-size:32px; font-weight:bold; letter-spacing:8px; background-color:#FEF3C7; border-radius:8px; padding:12px 20px;">
                                {{ $codigo }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px; line-height:20px; color:#475569; padding-bottom:16px;">
                            El código vence en <strong>{{ $minutos }} minutos</strong> y solo puede usarse una vez.
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:13px; line-height:19px; color:#64748B; border-top:1px solid #E2E8F0; padding-top:16px;">
                            Si no solicitaste este cambio, ignora este correo: tu contraseña seguirá siendo la misma.
                            Nunca compartas este código con nadie.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
