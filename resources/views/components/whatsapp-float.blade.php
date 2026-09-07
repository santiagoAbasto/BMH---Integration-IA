{{--
    Botón flotante de WhatsApp — sólo Zona Pública.

    Reemplaza al anterior, que usaba un PNG dentro de un contenedor `fixed` con
    un hijo `absolute` en `right: -10px`: eso lo empujaba fuera del viewport y se
    veía cortado contra el borde de la pantalla.

    Este usa clases propias con prefijo `bmh-wa-` en vez de `.whatsapp-container`
    / `.whatsapp-btn`, para no heredar esas reglas rotas de styles2.css (que
    además tienen tres bloques comentados pisándose entre sí).

    El logo es SVG inline: escala sin pixelarse, pesa ~600 bytes y no depende de
    un archivo que pueda faltar en el deploy.
--}}
@php($bmhWaNumero = preg_replace('/[^0-9]/', '', $contacto->whatsapp ?? ''))

@if ($bmhWaNumero !== '')
  <a
    class="bmh-wa"
    href="https://wa.me/{{ $bmhWaNumero }}"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Escribinos por WhatsApp"
  >
    <span class="bmh-wa__pulso" aria-hidden="true"></span>

    <span class="bmh-wa__tooltip" aria-hidden="true">Escribinos por WhatsApp</span>

    <svg class="bmh-wa__icono" viewBox="0 0 24 24" width="30" height="30" fill="currentColor" aria-hidden="true" focusable="false">
      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413"/>
    </svg>
  </a>

  <style>
    .bmh-wa {
      /* Anclado al viewport por sí mismo: sin contenedor extra que lo recorte. */
      position: fixed;
      right: 24px;
      bottom: 24px;
      z-index: 9990;

      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 60px;
      height: 60px;
      border-radius: 999px;
      text-decoration: none;

      /* Verde oficial de WhatsApp. */
      background: #25D366;
      color: #FFFFFF;

      box-shadow: 0 6px 18px rgba(18, 140, 76, .32), 0 2px 6px rgba(0, 0, 0, .12);
      transition: transform .22s cubic-bezier(.2, 0, 0, 1),
                  box-shadow .22s cubic-bezier(.2, 0, 0, 1),
                  background-color .22s ease;
      -webkit-tap-highlight-color: transparent;
    }

    .bmh-wa:hover,
    .bmh-wa:focus-visible {
      background: #1FAE55;
      transform: translateY(-2px);
      box-shadow: 0 10px 26px rgba(18, 140, 76, .40), 0 3px 8px rgba(0, 0, 0, .14);
      color: #FFFFFF;
    }

    .bmh-wa:active { transform: translateY(0) scale(.96); }

    .bmh-wa:focus-visible {
      outline: 3px solid #FFFFFF;
      outline-offset: 3px;
    }

    .bmh-wa__icono {
      display: block;
      position: relative;
      z-index: 1;
    }

    /* Anillo que late. Una sola onda cada 2,6 s: presencia sin ser molesto. */
    .bmh-wa__pulso {
      position: absolute;
      inset: 0;
      border-radius: inherit;
      background: #25D366;
      opacity: .55;
      animation: bmhWaPulso 2.6s cubic-bezier(.24, .6, .35, 1) infinite;
    }

    @keyframes bmhWaPulso {
      0%        { transform: scale(1);    opacity: .55; }
      70%, 100% { transform: scale(1.75); opacity: 0; }
    }

    /* Etiqueta al pasar el mouse. En touch no aparece: no hay hover real. */
    .bmh-wa__tooltip {
      position: absolute;
      right: calc(100% + 12px);
      white-space: nowrap;
      padding: 8px 12px;
      border-radius: 8px;
      background: #0E1620;
      color: #FFFFFF;
      font-size: 13px;
      font-weight: 600;
      line-height: 1;
      opacity: 0;
      transform: translateX(6px);
      pointer-events: none;
      transition: opacity .18s ease, transform .18s cubic-bezier(.2, 0, 0, 1);
      box-shadow: 0 4px 14px rgba(14, 22, 32, .18);
    }

    .bmh-wa__tooltip::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 100%;
      margin-top: -4px;
      border: 4px solid transparent;
      border-left-color: #0E1620;
    }

    @media (hover: hover) {
      .bmh-wa:hover .bmh-wa__tooltip,
      .bmh-wa:focus-visible .bmh-wa__tooltip {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @media (max-width: 575.98px) {
      .bmh-wa { right: 16px; bottom: 16px; width: 56px; height: 56px; }
      .bmh-wa__tooltip { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
      .bmh-wa,
      .bmh-wa__tooltip { transition: none; }
      .bmh-wa__pulso { animation: none; opacity: 0; }
      .bmh-wa:hover { transform: none; }
    }
  </style>
@endif
