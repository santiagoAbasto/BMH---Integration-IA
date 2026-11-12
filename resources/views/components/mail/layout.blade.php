@props(['title' => 'BMH Distribuidora', 'preheader' => null, 'logo' => null, 'eyebrow' => null])
{{-- Layout de los emails de BMH (como el de Conelec): tablas y estilos inline para que se vea igual en todos los
     clientes de correo. «logo»: App\Support\MailMarca::logo($message ?? null), embebido en el mail.
     Marca: tinta #0a0e14, azul #0098da (acentos) y #0076a8 (texto y botones), Montserrat + Inter con respaldo. --}}
@php
    $titulos = \App\Support\MailMarca::FUENTE_TITULOS;
    $texto = \App\Support\MailMarca::FUENTE_TEXTO;
@endphp
<!doctype html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title }}</title>
    {{-- Las fuentes del sitio donde el cliente las acepta (Apple Mail, iOS); en el resto, las de sistema. --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <style>
        @media (max-width: 620px) {
            .bmh-mail__pad { padding-left: 22px !important; padding-right: 22px !important; }
            .bmh-mail__h1 { font-size: 22px !important; line-height: 28px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f3f5f8;color:#1c2430;font-family:{{ $texto }};-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    @if ($preheader)
        <div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">{{ $preheader }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
    @endif
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f5f8;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e2e7ee;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td class="bmh-mail__pad" style="padding:22px 36px;background:{{ \App\Support\MailMarca::TINTA }};">
                            @if ($logo)
                                <img src="{{ $logo }}" alt="BMH Distribuidora" width="180" height="45" style="display:block;width:180px;max-width:180px;height:auto;border:0;outline:none;text-decoration:none;">
                            @else
                                <span style="color:#ffffff;font-family:{{ $titulos }};font-size:26px;font-weight:800;letter-spacing:1px;"><span style="color:{{ \App\Support\MailMarca::AZUL }};">///</span> BMH</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0;font-size:0;line-height:0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="96" style="height:4px;background:{{ \App\Support\MailMarca::AZUL }};font-size:0;line-height:4px;">&nbsp;</td>
                                    <td style="height:4px;background:#e2e7ee;font-size:0;line-height:4px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="bmh-mail__pad" style="padding:32px 36px 30px;font-family:{{ $texto }};">
                            @if ($eyebrow)
                                <p style="margin:0 0 8px;color:{{ \App\Support\MailMarca::AZUL_TEXTO }};font-family:{{ $titulos }};font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;">{{ $eyebrow }}</p>
                            @endif
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td class="bmh-mail__pad" style="padding:18px 36px;background:#f8fafc;border-top:1px solid #e2e7ee;color:#5b6575;font-family:{{ $texto }};font-size:12px;line-height:18px;">
                            {{ $footer ?? \App\Support\MailMarca::pie() }}
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0;color:#8792a2;font-family:{{ $texto }};font-size:11px;line-height:16px;">{{ \App\Support\MailMarca::NOMBRE }} · La marca del inducido</p>
            </td>
        </tr>
    </table>
</body>
</html>
