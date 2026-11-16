{{ $titulo }}

{{ trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", (string) $contenido)))) }}

{{ route('home') }}

---
{{ \App\Support\MailMarca::pie() }}
