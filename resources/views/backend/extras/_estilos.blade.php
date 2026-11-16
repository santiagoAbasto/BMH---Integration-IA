{{-- Estilos compartidos por los editores de Header y Footer (admin → Extras). --}}
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  .ext{
    --ext-azul:#0098DA; --ext-tinta:#1F2A37; --ext-suave:#667085; --ext-borde:#E3E8EE;
    --ext-fondo:#F4F7FA; --ext-ok:#12805C; --ext-alerta:#B54708; --ext-alerta-bg:#FEF0C7;
    --ext-resalte:#F79009; color:var(--ext-tinta);
  }

  /* ---------- Estructura ---------- */
  .ext-encabezado{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:22px}
  .ext-encabezado h1{margin:0;font-weight:600}
  .ext-encabezado p{margin:6px 0 0;color:var(--ext-suave);max-width:78ch;font-size:.92rem}
  .ext-grid{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:24px;align-items:start}
  .ext-preview{position:sticky;top:16px}
  @media (max-width:1199px){ .ext-grid{grid-template-columns:1fr} .ext-preview{position:static} }

  .ext-card{background:#fff;border:1px solid var(--ext-borde);border-radius:10px;padding:20px 22px;margin-bottom:20px}
  /* El layout del admin pisa h2 y h3 con !important; acá se acota a Extras. */
  .ext .ext-card h2{font-size:1.05rem!important;font-weight:600!important;margin:0 0 4px}
  .ext-ayuda{font-size:.84rem;color:var(--ext-suave);margin:0 0 16px;max-width:66ch}
  .ext-subtitulo{font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--ext-suave);font-weight:600;margin:20px 0 10px}
  .ext-fila{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .ext-fila > span{font-size:.85rem;font-weight:500}

  /* ---------- Selector de modo: manda en los campos y en la vista previa ---------- */
  .ext-modos{background:#fff;border:1px solid var(--ext-borde);border-radius:10px;padding:16px 20px 18px;margin-bottom:20px}
  .ext-modos__rotulo{display:block;font-size:.88rem;font-weight:600;margin-bottom:10px}
  .ext-modos__ayuda{font-size:.8rem;color:var(--ext-suave);margin:12px 0 0;max-width:96ch;line-height:1.55}
  .ext-modos__ayuda b{color:var(--ext-tinta);font-weight:600}

  /* ---------- Campo de color ---------- */
  .ext-campos{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 16px}
  @media (max-width:560px){ .ext-campos{grid-template-columns:1fr} }
  .ext-color label{font-size:.84rem;font-weight:500;margin-bottom:6px;display:block}
  .ext-color__control{display:flex;align-items:center;gap:8px}
  .ext-color__swatch{width:40px;height:34px;padding:2px;border:1px solid var(--ext-borde);border-radius:7px;background:#fff;cursor:pointer;flex:0 0 auto}
  .ext-color__hex{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;text-transform:uppercase;max-width:108px;font-size:.85rem}
  .ext-color__reset{border:0;background:none;color:var(--ext-suave);font-size:.78rem;padding:4px 2px;cursor:pointer;white-space:nowrap}
  .ext-color__reset:hover,.ext-color__reset:focus-visible{color:var(--ext-azul)}
  .ext-color__aviso{font-size:.74rem;color:var(--ext-alerta);background:var(--ext-alerta-bg);border-radius:5px;padding:3px 8px;margin:7px 0 0;display:inline-block}
  .ext-color__error{font-size:.76rem;color:#B42318;margin:6px 0 0}
  .ext-color.is-invalido .ext-color__hex{border-color:#B42318}

  /* ---------- Selectores segmentados ---------- */
  .ext-segmentos{display:inline-flex;border:1px solid var(--ext-borde);border-radius:8px;padding:3px;background:var(--ext-fondo);gap:3px}
  .ext-segmentos input{position:absolute;opacity:0;pointer-events:none}
  .ext-segmentos label{padding:6px 13px;border-radius:6px;font-size:.84rem;cursor:pointer;color:var(--ext-suave);margin:0;user-select:none}
  .ext-segmentos input:checked + label{background:#fff;color:var(--ext-tinta);font-weight:500;box-shadow:0 1px 2px rgba(16,24,40,.1)}
  .ext-segmentos input:focus-visible + label{outline:2px solid var(--ext-azul);outline-offset:1px}
  .ext-segmentos--grande{flex-wrap:wrap}
  .ext-segmentos--grande label{padding:9px 20px;font-size:.9rem}
  .ext-segmentos--grande input:checked + label{color:var(--ext-azul);font-weight:600}
  .ext-logo input[type="file"]:focus-visible + label{outline:2px solid var(--ext-azul);outline-offset:2px}

  /* ---------- Tarjetas de logo ---------- */
  .ext-logos{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
  .ext-logos--uno{grid-template-columns:1fr}
  @media (max-width:560px){ .ext-logos{grid-template-columns:1fr} }
  .ext-logo{border:1px solid var(--ext-borde);border-radius:9px;overflow:hidden;display:flex;flex-direction:column;transition:outline-color .15s}
  .ext-logo.is-arrastrando{outline:2px dashed var(--ext-azul);outline-offset:-4px}
  .ext-logo__escenario{height:116px;display:flex;align-items:center;justify-content:center;padding:16px;background:#2B3440 center/cover no-repeat}
  .ext-logo__escenario--blanco{background:#fff;border-bottom:1px solid var(--ext-borde)}
  .ext-logo__escenario img{max-height:64px;max-width:100%;object-fit:contain}
  .ext-favicon-card{max-width:520px}
  .ext-favicon{max-width:300px}
  .ext-favicon__escenario{height:180px;background:#F4F7FA;background-image:linear-gradient(45deg,#E3E8EE 25%,transparent 25%),linear-gradient(-45deg,#E3E8EE 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#E3E8EE 75%),linear-gradient(-45deg,transparent 75%,#E3E8EE 75%);background-size:24px 24px;background-position:0 0,0 12px,12px -12px,-12px 0}
  .ext-favicon__escenario img{width:128px;height:128px;max-height:none;object-fit:contain}
  .ext-logo__cuerpo{padding:12px 14px 14px;display:flex;flex-direction:column;gap:5px}
  .ext-logo__titulo{font-weight:600;font-size:.9rem;margin:0}
  .ext-logo__uso{font-size:.77rem;color:var(--ext-suave);margin:0}
  .ext-logo__acciones{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px}
  .ext-archivo{font-size:.75rem;color:var(--ext-suave);overflow-wrap:anywhere}
  .ext-archivo.is-nuevo{color:var(--ext-ok);font-weight:500}
  .ext-nota{font-size:.74rem;color:var(--ext-suave);background:var(--ext-fondo);border-radius:5px;padding:6px 9px;margin:0 0 10px}
  .ext-visually-hidden{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}

  /* ---------- Panel de vista previa ---------- */
  .ext-preview .ext-card{padding:16px 18px 18px}
  .ext-preview__titulo{font-weight:600;margin:0;font-size:1.05rem}
  .ext-estado{font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;color:var(--ext-suave);font-weight:600;margin:16px 0 7px;display:flex;align-items:center;gap:8px}
  .ext-estado::after{content:"";flex:1;height:1px;background:var(--ext-borde)}
  .ext-marco{border:1px solid var(--ext-borde);border-radius:9px;overflow:hidden;background:#fff}
  .ext-marco__chrome{height:24px;background:#EEF1F5;display:flex;align-items:center;gap:5px;padding:0 10px;border-bottom:1px solid var(--ext-borde)}
  .ext-marco__chrome i{width:8px;height:8px;border-radius:50%;background:#CBD2DB;display:block}
  .ext-marco__url{margin-left:10px;font-size:.68rem;color:var(--ext-suave);background:#fff;border-radius:5px;padding:1px 10px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
  .ext-marco__ventana{overflow:hidden}
  .ext-pie{font-size:.78rem;color:var(--ext-suave);margin:14px 0 0}

  /* Resaltado: el campo enfocado marca qué parte del sitio cambia. */
  .is-resaltado{outline:3px solid var(--ext-resalte)!important;outline-offset:3px;border-radius:6px;animation:ext-pulso 1.3s ease-in-out infinite}
  @keyframes ext-pulso{50%{outline-color:rgba(247,144,9,.3)}}

  /* ----------------------------------------------------------------------
     Vista previa: el header y el footer reales del sitio en iframes
     (backend/extras/vista-previa). Cada uno se dibuja a su ancho de verdad
     (--vp-ancho) y el JS le da la escala (--vp-escala) para el lugar que hay.
     ---------------------------------------------------------------------- */
  .ext-preview__ayuda{font-size:.8rem;color:var(--ext-suave);margin:2px 0 0}
  .ext-iframe{position:relative;overflow:hidden;background:#0a0e14;block-size:calc(var(--vp-alto) * var(--vp-escala, .5) * 1px)}
  .ext-iframe iframe{position:absolute;inset:0 auto auto 0;display:block;border:0;transform-origin:0 0;transform:scale(var(--vp-escala, .5))}

  /* ---------- Celular ---------- */
  .ext-telefonos{display:flex;gap:18px;justify-content:center;flex-wrap:wrap}
  .ext-telefono{display:flex;flex-direction:column;align-items:center;gap:8px}
  .ext-telefono__cuerpo{width:236px;height:452px;border:9px solid #1F2A37;border-radius:30px;overflow:hidden;background:#0a0e14;box-shadow:0 8px 24px rgba(16,24,40,.14)}
  .ext-telefono__cuerpo .ext-iframe{block-size:100%}
  .ext-telefono__rotulo{font-size:.74rem;color:var(--ext-suave)}

  /* ---------- Lo que el diseño actual no usa ---------- */
  .ext-chip{display:inline-flex;align-items:center;padding:2px 8px;border-radius:999px;background:var(--ext-fondo);color:var(--ext-suave);font-size:.66rem;font-weight:600;letter-spacing:.04em;text-transform:none;white-space:nowrap;vertical-align:middle}
  .ext-chip--edita{background:#E5F5FC;color:#0369A1}
  .ext-chip--sin-uso{background:#F2F4F7;color:#475467;border:1px dashed #98A2B3}
  .ext-aviso-sin-uso{display:flex;gap:8px;align-items:flex-start;font-size:.78rem;color:#344054;background:#F2F4F7;border-radius:6px;padding:8px 10px;margin:0 0 14px}
  .ext-aviso-sin-uso i{margin-top:2px;color:#667085}
  .ext-card--sin-uso .ext-campos,.ext-card--sin-uso .ext-fila,.ext-sin-uso:not(.ext-logo){opacity:.6;transition:opacity .15s}
  .ext-card--sin-uso .ext-campos:is(:hover,:focus-within),.ext-card--sin-uso .ext-fila:is(:hover,:focus-within),.ext-sin-uso:not(.ext-logo):is(:hover,:focus-within){opacity:1}
  .ext-logo.ext-sin-uso .ext-logo__escenario{opacity:.6}
  .ext-card--sin-uso .ext-sin-uso{opacity:1}

  /* ---------- Barra de guardado ---------- */
  .ext-guardar{position:sticky;bottom:0;z-index:30;display:flex;justify-content:flex-end;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 16px;margin:4px -16px 0;background:rgba(255,255,255,.96);border-top:1px solid var(--ext-borde);backdrop-filter:blur(4px)}
  .ext-guardar__estado{margin-right:auto;font-size:.85rem;color:var(--ext-suave);display:flex;align-items:center;gap:8px}
  .ext-guardar__estado::before{content:"";width:8px;height:8px;border-radius:50%;background:#CBD2DB}
  .ext-guardar.is-sucio .ext-guardar__estado{color:var(--ext-tinta);font-weight:500}
  .ext-guardar.is-sucio .ext-guardar__estado::before{background:var(--ext-resalte)}

  @media (prefers-reduced-motion:reduce){
    .is-resaltado{animation:none}
  }
</style>
