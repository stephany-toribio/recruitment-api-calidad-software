<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fuiste seleccionado para {{ $vacante }}</title>
</head>
{{-- Estilos en línea: la mayoría de clientes de correo ignoran <style> --}}
<body style="margin:0; padding:0; background-color:#F8FAFC; font-family:Arial, Helvetica, sans-serif; color:#111111;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F8FAFC; padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background-color:#FFFFFF; border:1px solid #E2E8F0; border-radius:12px; overflow:hidden;">
                    {{-- Franja superior: decisión positiva --}}
                    <tr>
                        <td style="background-color:#15803D; color:#FFFFFF; padding:20px 32px; font-size:14px; font-weight:bold; letter-spacing:1px;">
                            {{ config('app.name') }} · PROCESO DE SELECCIÓN
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 8px; font-size:22px; font-weight:bold; line-height:28px;">
                            ¡Felicitaciones, {{ $nombre }}!
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px; font-size:15px; line-height:23px;">
                            <strong>{{ $empresa }}</strong> terminó su proceso de selección y te eligió para el puesto
                            de <strong>{{ $vacante }}</strong>, al que postulaste a través de {{ config('app.name') }}.
                        </td>
                    </tr>
                    {{-- Resumen de la decisión --}}
                    <tr>
                        <td style="padding:0 32px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F0FDF4; border:1px solid #BBF7D0; border-radius:8px;">
                                <tr>
                                    <td style="padding:16px 20px 4px; font-size:13px; color:#475569;">Puesto</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 12px; font-size:15px; font-weight:bold;">{{ $vacante }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 4px; font-size:13px; color:#475569;">Empresa</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 12px; font-size:15px; font-weight:bold;">{{ $empresa }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 4px; font-size:13px; color:#475569;">Estado de tu postulación</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 16px;">
                                        <span style="display:inline-block; background-color:#15803D; color:#FFFFFF; font-size:13px; font-weight:bold; border-radius:999px; padding:4px 12px;">Contratado</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 8px; font-size:16px; font-weight:bold;">
                            Próximos pasos
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px; font-size:14px; line-height:22px; color:#334155;">
                            1. {{ $empresa }} se comunicará contigo al correo o al celular de tu perfil para coordinar tu incorporación.<br>
                            2. Si cambiaste de número o de correo, actualízalos en tu perfil para que puedan ubicarte.<br>
                            3. Revisa también la carpeta de spam o de correo no deseado durante los próximos días.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 24px; font-size:12px; line-height:18px; color:#64748B; border-top:1px solid #E2E8F0;">
                            Recibiste este correo porque postulaste a {{ $vacante }} en {{ config('app.name') }}.
                            Es un mensaje automático: por favor no lo respondas.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
