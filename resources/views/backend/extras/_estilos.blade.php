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
  .ext-encabezado p{margin:6px 0 0;color:var(--ext-suave);max-width:64ch;font-size:.92rem}
  .ext-grid{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:24px;align-items:start}
  .ext-preview{position:sticky;top:16px}
  @media (max-width:1199px){ .ext-grid{grid-template-columns:1fr} .ext-preview{position:static} }

  .ext-card{background:#fff;border:1px solid var(--ext-borde);border-radius:10px;padding:20px 22px;margin-bottom:20px}
  /* El layout del admin pisa h2 y h3 con !important; acá se acota a Extras. */
  .ext .ext-card h2{font-size:1.05rem!important;font-weight:600!important;margin:0 0 4px}
  .ext .mk-hero h3{font-size:46px!important;font-weight:600!important}
  .ext .mkm-hero h3{font-size:34px!important;font-weight:600!important}
  .ext-ayuda{font-size:.84rem;color:var(--ext-suave);margin:0 0 16px;max-width:60ch}
  .ext-subtitulo{font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--ext-suave);font-weight:600;margin:20px 0 10px}
  .ext-fila{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
  .ext-fila > span{font-size:.85rem;font-weight:500}

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

  /* ---------- Selector segmentado (qué logo usar) ---------- */
  .ext-segmentos{display:inline-flex;border:1px solid var(--ext-borde);border-radius:8px;padding:3px;background:var(--ext-fondo);gap:3px}
  .ext-segmentos input{position:absolute;opacity:0;pointer-events:none}
  .ext-segmentos label{padding:6px 13px;border-radius:6px;font-size:.84rem;cursor:pointer;color:var(--ext-suave);margin:0;user-select:none}
  .ext-segmentos input:checked + label{background:#fff;color:var(--ext-tinta);font-weight:500;box-shadow:0 1px 2px rgba(16,24,40,.1)}
  .ext-segmentos input:focus-visible + label{outline:2px solid var(--ext-azul);outline-offset:1px}
  .ext-segmentos button{border:0;background:none;padding:6px 13px;border-radius:6px;font-size:.84rem;color:var(--ext-suave);cursor:pointer}
  .ext-segmentos button[aria-selected="true"]{background:#fff;color:var(--ext-tinta);font-weight:500;box-shadow:0 1px 2px rgba(16,24,40,.1)}
  .ext-segmentos button:focus-visible{outline:2px solid var(--ext-azul);outline-offset:1px}
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
  .ext-logo__cuerpo{padding:12px 14px 14px;display:flex;flex-direction:column;gap:5px}
  .ext-logo__titulo{font-weight:600;font-size:.9rem;margin:0}
  .ext-logo__uso{font-size:.77rem;color:var(--ext-suave);margin:0}
  .ext-logo__acciones{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px}
  .ext-archivo{font-size:.75rem;color:var(--ext-suave);overflow-wrap:anywhere}
  .ext-archivo.is-nuevo{color:var(--ext-ok);font-weight:500}
  .ext-nota{font-size:.74rem;color:var(--ext-suave);background:var(--ext-fondo);border-radius:5px;padding:4px 8px;margin:4px 0 0}
  .ext-visually-hidden{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}

  /* ---------- Panel de vista previa ---------- */
  .ext-preview .ext-card{padding:16px 18px 18px}
  .ext-preview__barra{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
  .ext-preview__titulo{font-weight:600;margin:0;font-size:1.05rem}
  .ext-estado{font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;color:var(--ext-suave);font-weight:600;margin:16px 0 7px;display:flex;align-items:center;gap:8px}
  .ext-estado::after{content:"";flex:1;height:1px;background:var(--ext-borde)}
  .ext-marco{border:1px solid var(--ext-borde);border-radius:9px;overflow:hidden;background:#fff}
  .ext-marco__chrome{height:24px;background:#EEF1F5;display:flex;align-items:center;gap:5px;padding:0 10px;border-bottom:1px solid var(--ext-borde)}
  .ext-marco__chrome i{width:8px;height:8px;border-radius:50%;background:#CBD2DB;display:block}
  .ext-marco__url{margin-left:10px;font-size:.68rem;color:var(--ext-suave);background:#fff;border-radius:5px;padding:1px 10px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
  .ext-marco__ventana{overflow:hidden}
  /* La maqueta se arma a tamaño real (1280px) y se escala con zoom desde JS. */
  .ext-lienzo{width:1280px;zoom:var(--ext-zoom,.5)}
  .ext-pie{font-size:.78rem;color:var(--ext-suave);margin:10px 0 0}

  /* Resaltado: el campo enfocado marca qué parte del sitio cambia. */
  .is-resaltado{outline:3px solid var(--ext-resalte)!important;outline-offset:3px;border-radius:6px;animation:ext-pulso 1.3s ease-in-out infinite}
  .is-resaltado.mk-header,.is-resaltado.mkm-header,.is-resaltado.mkm-menu,.is-resaltado.mkf{outline-offset:-3px;border-radius:0}
  @keyframes ext-pulso{50%{outline-color:rgba(247,144,9,.3)}}

  /* ---------- Maqueta del sitio: desktop ---------- */
  .mk{font-family:Montserrat,system-ui,sans-serif;position:relative;overflow:hidden;background:#fff}
  .mk-info{height:31px;background:#0098DA;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 72px;font-size:14px;font-weight:500}
  .mk-info span{display:inline-flex;gap:56px}
  .mk-header{height:117px;display:flex;align-items:center;justify-content:space-between;padding:0 72px;position:relative;z-index:2}
  .mk-header img{height:68px;max-width:260px;object-fit:contain}
  .mk-nav{display:flex;align-items:center;gap:45px}
  .mk-nav a{font-size:16px;font-weight:500;color:inherit;text-decoration:none;padding:4px 0}
  .mk-btn{height:40px;padding:0 31px;border-radius:10px;border:1px solid;font:600 16px Montserrat,sans-serif;display:inline-flex;align-items:center;background-color:transparent;background-repeat:no-repeat;background-size:0 100%;transition:background-size .45s ease,color .3s,border-color .3s;cursor:pointer}
  .mk-btn:hover,.mk-btn.is-hover{background-size:100% 100%}

  .mk--home .mk-header{position:absolute;top:31px;left:0;right:0;background:transparent;color:#fff}
  .mk--home .mk-nav a:hover{color:#0098DA}
  .mk--home .mk-btn{color:#fff;border-color:#fff;background-image:linear-gradient(#0098DA,#0098DA)}
  .mk--blanco .mk-header{background:#fff;color:#000}
  .mk--blanco .mk-nav a:hover{color:#0098DA}
  .mk--blanco .mk-nav .is-activo{font-weight:600}
  .mk--blanco .mk-btn{color:#0098DA;border-color:#0098DA;background-image:linear-gradient(#0098DA,#0098DA)}
  .mk--blanco .mk-btn:hover{color:#fff}

  .mk--scroll .mk-header{position:absolute;top:0;left:0;right:0;background:var(--ap-hs-fondo);color:var(--ap-hs-links);box-shadow:0 2px 10px rgba(0,0,0,.08)}
  .mk--scroll .mk-nav a:hover{color:color-mix(in srgb,var(--ap-hs-links) 70%,transparent)}
  .mk--scroll .mk-nav .is-activo{box-shadow:0 2px 0 color-mix(in srgb,var(--ap-hs-links) 70%,transparent)}
  .mk--scroll .mk-btn{color:var(--ap-hs-btn-texto);border-color:var(--ap-hs-btn-borde);background-image:linear-gradient(var(--ap-hs-btn-hover-fondo),var(--ap-hs-btn-hover-fondo))}
  .mk--scroll .mk-btn:hover,.mk--scroll .mk-btn.is-hover{color:var(--ap-hs-btn-hover-texto);border-color:var(--ap-hs-btn-hover-fondo)}

  .mk-hero{height:380px;background:#1d3b52 center/cover no-repeat;position:relative}
  .mk-hero h3{position:absolute;left:72px;bottom:64px;margin:0;color:#fff;font-size:46px;font-weight:600;line-height:1.2;max-width:660px}
  .mk--scroll .mk-hero{height:250px;background-position:center 70%}
  .mk-pagina{display:grid;grid-template-columns:1fr 1fr;height:250px;margin:44px 72px 0}
  .mk-pagina__foto{background:#3d4652 linear-gradient(135deg,#59616c,#2c333c)}
  .mk-pagina__texto{background:#0098DA;color:#fff;padding:36px 28px}
  .mk-pagina__texto b{display:block;font-size:24px;margin-bottom:22px}
  .mk-pagina__texto i{display:block;height:11px;border-radius:6px;background:rgba(255,255,255,.55);margin-bottom:12px}
  .mk--scroll .mk-pagina{margin-top:0;height:230px}

  /* ---------- Maqueta del sitio: celular ---------- */
  .ext-telefonos{display:flex;gap:18px;justify-content:center;flex-wrap:wrap}
  .ext-telefono{display:flex;flex-direction:column;align-items:center;gap:8px}
  .ext-telefono__cuerpo{width:236px;height:452px;border:9px solid #1F2A37;border-radius:30px;overflow:hidden;background:#fff;box-shadow:0 8px 24px rgba(16,24,40,.14)}
  .ext-telefono__cuerpo .ext-lienzo{width:375px;zoom:calc(218 / 375)}
  .ext-telefono__rotulo{font-size:.74rem;color:var(--ext-suave)}
  .mkm{font-family:Montserrat,system-ui,sans-serif;height:780px;position:relative;overflow:hidden;background:#fff}
  .mkm-header{height:72px;display:flex;align-items:center;justify-content:space-between;padding:0 14px;background:var(--ap-hm-fondo)}
  .mkm-header img{max-width:184px;max-height:62px;object-fit:contain}
  .mkm-toggler{width:30px;height:30px;background-color:var(--ap-hm-links);
    -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M2 3.5A.5.5 0 0 1 2.5 3h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zM2 7a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11A.5.5 0 0 1 2 7zm0 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z'/%3E%3C/svg%3E") center/contain no-repeat;
            mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M2 3.5A.5.5 0 0 1 2.5 3h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5zM2 7a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11A.5.5 0 0 1 2 7zm0 3.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5z'/%3E%3C/svg%3E") center/contain no-repeat}
  .mkm-menu{background:var(--ap-hm-fondo);padding:4px 14px 20px;border-top:1px solid rgba(127,127,127,.15)}
  .mkm-menu a{display:block;color:var(--ap-hm-links);font-size:16px;font-weight:500;padding:11px 0;text-decoration:none}
  .mkm-btn{margin-top:10px;color:var(--ap-hm-btn-texto);border-color:var(--ap-hm-btn-borde);background-image:linear-gradient(var(--ap-hm-btn-hover-fondo),var(--ap-hm-btn-hover-fondo))}
  .mkm-btn:hover,.mkm-btn.is-hover{color:var(--ap-hm-btn-hover-texto);border-color:var(--ap-hm-btn-hover-fondo)}
  .mkm-hero{height:708px;background:#1d3b52 center/cover no-repeat;position:relative}
  .mkm-hero h3{position:absolute;left:14px;right:14px;bottom:90px;margin:0;color:#fff;font-size:34px;font-weight:600;line-height:1.25}
  .ext-muestra{display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:14px;padding:12px;border-radius:8px;background:var(--ap-hm-fondo);border:1px solid var(--ext-borde)}
  .ext-muestra small{color:var(--ext-suave);font-size:.74rem;background:#fff;border-radius:4px;padding:2px 6px}
  .ext-muestra .mk-btn{zoom:.85}

  /* ---------- Maqueta del sitio: footer ---------- */
  .mkf{font-family:Montserrat,system-ui,sans-serif;background:var(--ap-f-fondo);color:var(--ap-f-texto);padding:60px 0 0}
  .mkf-fila{display:grid;grid-template-columns:3fr 4fr 5fr;gap:24px;padding:0 72px 36px}
  .mkf-logo img{max-width:100%;max-height:120px;object-fit:contain}
  .mkf h4{font-size:16px;font-weight:600;text-transform:uppercase;margin:20px 0 24px;color:var(--ap-f-texto)}
  .mkf ul{list-style:none;padding:0;margin:0}
  .mkf li{font-size:16px;font-weight:500;margin-bottom:20px}
  .mkf-secciones{display:grid;grid-template-columns:1fr 1fr}
  .mkf a{color:var(--ap-f-texto);text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:color .2s}
  .mkf a:hover,.mkf a.is-hover{color:var(--ap-f-texto-hover)}
  .mkf svg{flex:0 0 auto}
  .mkf svg [fill="white"],.mkf svg [fill="#fff"]{fill:var(--ap-f-texto)}
  .mkf svg [stroke="white"]{stroke:var(--ap-f-texto)}
  .mkf svg [stroke="#0098DA"]{stroke:var(--ap-f-fondo)}
  .mkf-derechos{height:81px;background:#241F21;color:rgba(255,255,255,.8);display:flex;align-items:center;justify-content:space-between;padding:0 72px;font-size:14px}

  /* ---------- Barra de guardado ---------- */
  .ext-guardar{position:sticky;bottom:0;z-index:30;display:flex;justify-content:flex-end;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 16px;margin:4px -16px 0;background:rgba(255,255,255,.96);border-top:1px solid var(--ext-borde);backdrop-filter:blur(4px)}
  .ext-guardar__estado{margin-right:auto;font-size:.85rem;color:var(--ext-suave);display:flex;align-items:center;gap:8px}
  .ext-guardar__estado::before{content:"";width:8px;height:8px;border-radius:50%;background:#CBD2DB}
  .ext-guardar.is-sucio .ext-guardar__estado{color:var(--ext-tinta);font-weight:500}
  .ext-guardar.is-sucio .ext-guardar__estado::before{background:var(--ext-resalte)}

  @media (prefers-reduced-motion:reduce){
    .is-resaltado{animation:none}
    .mk-btn,.mkf a{transition:none}
  }
</style>
