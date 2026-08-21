# BMH — Design System

Identidad extraída del sitio y del código de producción de BMH, no inventada.
Los valores base salen de `public/css/*.css` y de las vistas Blade actuales,
donde el azul institucional aparece 49 veces y el verde de estado 9.

---

## 1. Marca

BMH Bobinajes. Reacondicionamiento del inducido del automotor.
Producto B2B, industrial, técnico. Los usuarios son talleres y revendedores que
trabajan rápido y con las manos sucias.

Eso manda tres decisiones:

1. **Contraste alto.** Se lee en un taller, no en una oficina con luz suave.
2. **Targets grandes.** Se usa desde el celular, muchas veces con una mano.
3. **Densidad de datos.** Un código y cuatro medidas valen más que una foto grande.

---

## 2. Color

### Azul institucional — el de BMH

Extraído de `#0098DA`, presente en producción en títulos, links y estados.

| Token | Hex | Uso |
|---|---|---|
| `brand-50`  | `#E6F6FD` | fondos de realce muy suaves |
| `brand-100` | `#CCEDFA` | chips, badges |
| `brand-200` | `#99DBF5` | bordes de foco suave |
| `brand-400` | `#33B4E4` | hover |
| `brand-500` | **`#0098DA`** | **primario. Botones, links, acentos.** |
| `brand-600` | `#007CB2` | hover de botón primario |
| `brand-700` | `#005E87` | active / pressed |
| `brand-900` | `#00405C` | texto sobre fondo claro cuando hace falta peso |

### Verde de estado — "Nuevo"

`#ABD430` es el verde con el que producción marca los productos NUEVO.
Se conserva su semántica: **estado positivo/confirmado**, nunca acción.

| Token | Hex | Uso |
|---|---|---|
| `signal-400` | `#C2E05F` | fondo de badge |
| `signal-500` | **`#ABD430`** | "Nuevo", coincidencia muy alta |
| `signal-700` | `#6E8A1C` | texto sobre fondo claro (contraste AA) |

> `#ABD430` sobre blanco da 1.9:1. **No se usa como color de texto.** Para texto
> verde se usa `signal-700`.

### Neutrales

Grises fríos, no puros: acompañan mejor al azul.

| Token | Hex |
|---|---|
| `surface-base` | `#F4F6F8` |
| `surface-raised` | `#FFFFFF` |
| `surface-sunken` | `#EAEEF2` |
| `surface-inverse` | `#0E1620` |
| `border-subtle` | `#E1E7ED` |
| `border-default` | `#CBD5DF` |
| `border-strong` | `#9AA9B8` |
| `ink-primary` | `#0E1620` |
| `ink-secondary` | `#4A5A6B` |
| `ink-tertiary` | `#748496` |
| `ink-inverse` | `#F4F6F8` |

### Semánticos

| Token | Hex | Uso |
|---|---|---|
| `state-success` | `#1F8A4C` | confirmado |
| `state-warning` | `#B26A00` | dato no verificado, precio a validar |
| `state-danger` | `#C0392B` | error |
| `state-info` | `#0098DA` | informativo (= marca) |

### Confianza — escala propia del asesor

La confianza **no** se muestra como número. Se muestra como color + texto.

| Banda | Token | Etiqueta |
|---|---|---|
| ≥ 0.90 | `signal-500` | Coincidencia muy alta |
| 0.75–0.89 | `brand-500` | Coincidencia alta |
| 0.50–0.74 | `state-warning` | Coincidencia parcial |
| < 0.50 | `ink-tertiary` | Coincidencia baja |

---

## 3. Tipografía

**Inter** para interfaz. **JetBrains Mono** para códigos de artículo.

El mono en los códigos no es estética: `RP381935` y `RP381940` se distinguen de
un vistazo en mono y se confunden en una sans proporcional. Con 11 códigos
duplicados en la base, esa diferencia importa.

| Token | Tamaño / interlineado | Peso | Uso |
|---|---|---|---|
| `display` | 30px / 36px | 700 | título de pantalla |
| `title` | 20px / 28px | 600 | encabezado de sección |
| `subtitle` | 16px / 24px | 600 | nombre de producto |
| `body` | 15px / 24px | 400 | texto de chat |
| `body-strong` | 15px / 24px | 600 | énfasis |
| `caption` | 13px / 18px | 500 | etiquetas de atributo |
| `micro` | 11px / 16px | 600 | badges, mayúsculas, tracking 0.04em |
| `code` | 14px / 20px | 500 | mono, códigos |

---

## 4. Espaciado

Escala de 4px. `--space-1: 4px` … `--space-12: 48px`.

Ritmo del chat: 12px entre burbujas del mismo emisor, 20px al cambiar de
emisor, 32px entre bloques de turno.

---

## 5. Superficies y elevación

Industrial: **el borde manda, la sombra acompaña**. Nada de glassmorphism ni
glow.

| Nivel | Uso | Estilo |
|---|---|---|
| 0 | fondo | `surface-base`, sin borde |
| 1 | card, burbuja | `surface-raised` + `1px border-subtle` + `0 1px 2px rgba(14,22,32,.06)` |
| 2 | card de producto en hover | `+ 0 4px 12px rgba(14,22,32,.08)`, borde `border-default` |
| 3 | modal, dropdown | `0 12px 32px rgba(14,22,32,.16)` |

Radios: `4px` inputs y badges · `8px` botones y cards · `12px` burbujas y
paneles · `999px` chips.

---

## 6. Componentes

### Botón

| Variante | Fondo | Texto | Borde |
|---|---|---|---|
| primary | `brand-500` → hover `brand-600` | blanco | — |
| secondary | `surface-raised` | `ink-primary` | `border-default` |
| ghost | transparente → hover `surface-sunken` | `ink-secondary` | — |
| danger | `state-danger` | blanco | — |

Alto: 40px (desktop), **44px mínimo en mobile**. Foco: `2px` outline
`brand-500` con `2px` de offset — visible siempre, nunca `outline: none`.

### Input / composer

Fondo `surface-raised`, borde `border-default`, radio 12px, padding 12/14.
Foco: borde `brand-500` + halo `0 0 0 3px brand-100`.

### Burbuja de chat

- **Cliente:** `brand-500`, texto blanco, radio 12px con esquina inferior derecha a 4px.
- **Asesor:** `surface-raised`, borde `border-subtle`, esquina inferior izquierda a 4px.

Ancho máximo 620px. Sin avatares: ocupan espacio y no aportan.

### Card de producto

```
┌──────────────────────────────────────┐
│ [96×96]  ROTORES              ● Alta │
│ img      ROTOR BOSCH CRAMACO         │
│          1163            [Nuevo]     │
│          ───────────────────────────  │
│          Voltaje    12v              │
│          Diámetro   88.8 mm          │
│          Amperes    55 A             │
│          ───────────────────────────  │
│          $ 39.321,50 + IVA           │
│          [Ver ficha]  [Es este]      │
└──────────────────────────────────────┘
```

El código va en mono. El badge de coincidencia arriba a la derecha, en color +
texto (nunca sólo color). Máximo 3 atributos en la card: los que **difieren**
entre candidatos, elegidos por `CandidateDisambiguationService`.

### Panel de contexto técnico (desktop ≥1280px)

Columna derecha de 320px, sticky. Secciones: Consulta actual · Rubro probable ·
Datos conocidos (con chip `confirmado`/`inferido`) · Datos que faltan · Tu
cuenta · Historial relacionado.

El chip confirmado/inferido es la traducción visual del data provenance del
backend. Es la pieza que hace visible que el sistema distingue un hecho de una
suposición.

---

## 7. Iconografía

**Lucide**, 20px, stroke 1.75. Nunca emojis como iconos.

`Camera` foto · `Paperclip` adjuntar · `Send` enviar · `Search` buscar ·
`Package` producto · `Tag` código · `Ruler` medida · `Zap` eléctrico ·
`History` historial · `UserRound` asesor humano · `CircleAlert` no verificado ·
`CircleCheck` confirmado.

---

## 8. Movimiento

150–250ms, `cubic-bezier(0.2, 0, 0, 1)`.

| Elemento | Animación |
|---|---|
| burbuja entrante | fade + 6px hacia arriba, 180ms |
| card de producto | fade + 8px, escalonado 60ms |
| estado del header | crossfade de texto, 150ms |
| skeleton | shimmer 1.2s, sólo con operación real en curso |

**Nada de fake loading.** El indicador aparece cuando hay una búsqueda o una
llamada al modelo de verdad, y el texto dice qué está pasando: "Analizando
imagen…", "Buscando en catálogo…", "Comparando 6 artículos…".

`prefers-reduced-motion: reduce` → todas las transiciones a 0ms, sin shimmer.

---

## 9. Responsive

| Breakpoint | Layout |
|---|---|
| `< 768px` | chat full screen. Composer fijo abajo con safe-area. Contexto en bottom sheet. |
| `768–1279px` | chat + contexto colapsable en drawer. |
| `≥ 1280px` | tres columnas: conversaciones 260px · chat fluido · contexto 320px. |

Mobile es prioritario: el cliente saca la foto de la pieza con el teléfono, en
el taller. El botón de cámara es acción de primer nivel en el composer, no un
submenú.

---

## 10. Accesibilidad

- Contraste AA en todo texto. El verde de marca **no** se usa para texto.
- Foco visible siempre; el orden de tabulación sigue el visual.
- Estados nunca sólo por color: coincidencia y condición llevan texto.
- `aria-live="polite"` en la lista de mensajes; el estado del header lo anuncia
  sin robar el foco.
- Cada imagen del catálogo con `alt` = nombre del producto + código.
- Errores descriptivos y accionables: "La foto es muy pesada, probá con una de
  menor tamaño", no "Error 422".
- Targets ≥ 44px en mobile.
