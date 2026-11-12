@props(['rows'])
{{-- Datos «etiqueta: valor» de un mail. $rows: [etiqueta => texto | ['text' => …, 'href' => …]]; los vacíos no se muestran. --}}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 22px;border-top:1px solid #e2e7ee;">
    @foreach ($rows as $label => $value)
        @continue(blank(is_array($value) ? $value['text'] : $value))
        <tr>
            <td width="130" style="width:130px;padding:10px 12px 10px 0;border-bottom:1px solid #e2e7ee;color:#5b6575;font-size:14px;line-height:20px;vertical-align:top;">{{ $label }}</td>
            <td style="padding:10px 0;border-bottom:1px solid #e2e7ee;color:#0a0e14;font-size:15px;line-height:20px;vertical-align:top;word-break:break-word;">
                @if (is_array($value))
                    <a href="{{ $value['href'] }}" style="color:#0076a8;text-decoration:none;font-weight:600;">{{ $value['text'] }}</a>
                @else
                    {{ $value }}
                @endif
            </td>
        </tr>
    @endforeach
</table>
