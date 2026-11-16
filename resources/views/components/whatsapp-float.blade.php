{{--
    Botón flotante de WhatsApp (sólo Zona Pública; en la Zona de Clientes va el asesor): osole::whatsapp-button.v1,
    el mismo de Conelec. Al pasar el mouse sube, el ícono se sacude y se despliega el globo «¿Tenés una consulta? ·
    Escribinos ahora»; en reposo conserva el latido de BMH (un anillo cada 2,6 s, sitio/base.css). El número es el
    de Contacto › Contenido; sin número, no se muestra.
--}}
<x-osole::whatsapp-button.v1
    class="bmh-wa"
    :phone="$contacto->whatsapp ?? null"
    message="Hola, quiero hacer una consulta."
    callout-title="¿Tenés una consulta?"
    callout-text="Escribinos ahora"
/>
