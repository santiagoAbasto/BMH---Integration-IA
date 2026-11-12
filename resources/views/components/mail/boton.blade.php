@props(['href', 'secundario' => false])
{{-- Botón de los emails de BMH (tabla: los clientes de correo no respetan el padding de un <a> suelto). --}}
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="display:inline-table;margin:0 8px 8px 0;">
    <tr>
        <td style="border-radius:8px;background:{{ $secundario ? '#ffffff' : '#0076a8' }};border:1px solid #0076a8;">
            <a href="{{ $href }}" style="display:inline-block;padding:12px 22px;color:{{ $secundario ? '#0076a8' : '#ffffff' }};font-family:{{ \App\Support\MailMarca::FUENTE_TITULOS }};font-size:14px;font-weight:700;line-height:20px;text-decoration:none;">{{ $slot }}</a>
        </td>
    </tr>
</table>
