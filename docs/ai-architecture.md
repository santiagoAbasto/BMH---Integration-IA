# BMH — Arquitectura de IA

> **La IA interpreta. La aplicación decide. La base de datos confirma.**

---

## 1. Qué hace y qué no hace el modelo

| Hace | No hace |
|---|---|
| Interpretar lenguaje libre | Elegir el producto |
| Extraer atributos de un mensaje | Calcular precios |
| Describir lo que ve en una foto | Identificar un artículo del catálogo |
| Redactar la respuesta | Ejecutar SQL |
| Decidir *qué preguntar* de una lista calculada | Afirmar disponibilidad |

Cuando el modelo redacta, **el producto y el precio ya están decididos** por el
orquestador. Ver [architecture.md §3](architecture.md).

Esto tiene una consecuencia práctica: la demo con `MockAiProvider` se siente
inteligente. La inteligencia está en la búsqueda, el ranking y la
desambiguación, no en el modelo.

---

## 2. AiProviderInterface

```php
interface AiProviderInterface
{
    public function name(): string;
    public function chat(array $messages, array $tools = [], array $options = []): AiResponse;
    public function analyzeImage(string $imagePath, string $context = ''): ImageAnalysis;
    public function structuredOutput(string $prompt, array $schema, array $options = []): array;
    public function embed(string $text): array;
    public function isAvailable(): bool;
}
```

| Implementación | Estado |
|---|---|
| `MockAiProvider` | Completo. Default sin API key. |
| `GeminiAiProvider` | Completo: chat, tool calling, visión con `responseSchema`, embeddings. |
| `OpenAiProvider` | Completo: chat, tool calling, visión con `json_schema`, embeddings. |

`AiProviderManager` elige. Sin `AI_API_KEY`, cae a `mock` y lo registra una vez.
La Zona de Clientes **nunca** queda inutilizable por falta de credenciales.

### AiModelRouter

No se usa el modelo caro para todo (`config/bmh.php → ai.routing`):

| Tarea | Modelo |
|---|---|
| Clasificación de intención | `fast` |
| Extracción estructurada | `fast` |
| Análisis de imagen | `vision` |
| Redacción final | `chat` |

### Fallback

`AI_FALLBACK_ENABLED=false` por defecto. Cambiar de proveedor cuesta plata: no
puede pasar solo.

---

## 3. MockAiProvider

No es un stub que devuelve texto de relleno. Interpreta con heurísticas reales:

- **Intención**: precio, recompra, equivalencia, humano, disponibilidad, identificación.
- **Rubro**: 35 palabras del oficio → rubro del catálogo ("burro" → MOTORES DE
  ARRANQUE, "ruleman" → RODAMIENTOS).
- **Atributos**: `12v`, `75a`, `28 mm`, `9 estrías`, `diámetro 88.8`, y pares
  `Etiqueta: valor` resueltos contra el diccionario de los 74 slots.
- **Marcas**: 18 marcas presentes en el catálogo.
- **Códigos**: alfanuméricos y numéricos.

En visión es **honesto**: sin modelo real no puede mirar la foto, así que
declara confianza baja (0.28–0.55, siempre por debajo del umbral de "alta") y no
inventa un tipo de pieza. Eso hace que el escenario "foto insuficiente" sea
demostrable de verdad.

---

## 4. Data provenance

Cada dato lleva de dónde salió:

```json
{ "value": "28 mm", "source": "producto_caracteristica", "source_id": 8291, "confidence": 1, "factual": true }
{ "value": "posible rotor", "source": "ai_vision", "confidence": 0.78, "factual": false }
```

`Provenance::isFactual()` distingue lo que se puede **afirmar** de lo que hay que
**matizar**. En la UI se traduce a los chips `CONFIRMADO` / `INFERIDO` del panel
de contexto: el cliente ve qué sabe el sistema y qué está suponiendo.

---

## 5. Memoria

Tres estados:

| Estado | Origen |
|---|---|
| `confirmed` | Lo dijo el cliente, o lo confirma la base |
| `inferred` | Lo dedujo visión o la interpretación de texto |
| `unknown` | Se preguntó y no hubo respuesta útil |

**Una inferencia nunca pisa un hecho confirmado.** Si el cliente dijo "es Bosch"
y una foto sugiere Valeo, `absorbImageAnalysis()` devuelve un **conflicto** en
vez de sobrescribir, y el asistente pregunta cuál corresponde.

Cubierto por `test_una_inferencia_no_pisa_un_dato_confirmado` y
`test_una_foto_que_contradice_lo_confirmado_expone_el_conflicto`.

La memoria se reconstruye en `SearchQuery` en cada turno: "es Bosch" dicho hace
tres mensajes sigue filtrando ahora.

---

## 6. Tool calling

10 tools (`ToolRegistry`). El modelo sólo puede **nombrar** una y pasarle
argumentos; Laravel valida, autoriza, consulta y devuelve JSON.

```
search_products · search_by_code · search_by_equivalence · get_product
compare_products · get_customer_price · get_customer_order_history
list_categories · check_availability · request_human_assistance
```

El `CustomerAccount` se inyecta al construir el registro, **no viaja como
argumento**: el modelo no puede pedir datos de otro cliente ni intentándolo.

`sanitizeAttributes()` descarta cualquier clave que no esté en el diccionario.

**En ningún escenario se ejecuta SQL producido por un modelo.**

---

## 7. Structured output

La extracción usa JSON Schema (`responseSchema` en Gemini, `json_schema` en
OpenAI) y **se valida igual** en `sanitizeInterpretation()`:

- `intent` tiene que ser un string del enum, si no → `product_identification`;
- `extracted_attributes` sólo acepta pares string/escalar;
- `confidence` se acota a 0..1.

Sin esto, un modelo desalineado que devuelve un array donde va un string tumba
el turno con "Array to string conversion". Pasó durante el desarrollo; ahora hay
un test (`test_un_json_malformado_del_modelo_no_rompe_el_turno`).

---

## 8. Confianza

Umbral único en `config/bmh.php`:

| Banda | Rango | Qué puede decir |
|---|---|---|
| `very_high` | ≥ 0.90 | "Encontré esta pieza." |
| `high` | 0.75–0.89 | "Encontré una coincidencia muy probable." |
| `ambiguous` | 0.50–0.74 | Pedir otro dato. |
| `low` | < 0.50 | "No tengo información suficiente." |

Techos deliberados en `Candidate::confidence()`:

- con **código exacto** → hasta 0.99;
- con código normalizado o equivalencia → hasta 0.89;
- **sin código** → tope 0.74.

Una coincidencia por atributos o por foto **nunca** llega a "muy alta". Sin un
código, el sistema no afirma una pieza.

Al cliente no se le muestra el número, sólo la etiqueta.

---

## 9. Visión

1. La imagen se optimiza (1280 px, calidad 82, EXIF removido).
2. Se pide **describir**, no identificar. El prompt lo dice explícitamente.
3. La salida entra como inferencia y alimenta filtros.
4. **La base confirma.**

Un código legible por OCR vale mucho más que un parecido de forma: entra con
confianza mínima 0.6 y dispara búsqueda por código, que tiene autoridad máxima
en el ranking.

### El embudo (`VisionMatchService`)

Tres etapas, en orden de autoridad decreciente:

1. **Códigos leídos** → exacto, normalizado, equivalencia, parcial. Si un código
   existe, gana y no se compara nada más.
2. **Rubro + atributos observados** → filtro estructurado contra la base.
3. **Comparación multimodal** → la foto del cliente contra las de los 4 primeros
   candidatos, en una sola llamada.

La similitud visual entra como una señal más del ranking (peso 3) contra 100 de
un código exacto: **una foto nunca le gana a un código**.

### Qué se descarta antes de filtrar

El modelo observa bien pero no sabe qué existe en el catálogo. Todo lo que
observa se verifica contra la base antes de convertirse en filtro, porque un
filtro que no matchea nada no devuelve “menos resultados”: devuelve **cero**, y
arrastra consigo a los filtros que sí servían.

| Observación | Se descarta si… | Caso real |
|---|---|---|
| Marca | no figura en `productos.marca` | leyó el “B.M.H” escrito en la mesada de trabajo, no en la pieza |
| Atributo | el **rubro** no define esa columna | `terminals` en MOTORES DE ARRANQUE, que no tiene esa columna |
| Atributo | el valor es prosa, no un valor | `"2 principales visibles"` → se reduce a `"2"`; `"terminal tipo ficha"` se tira |
| Rubro | ningún rubro real se le parece lo bastante | se elige el **mejor**, no todos los que comparten una palabra: “motores de arranque” resolvía a MOTORES + PORTAESCOBILLAS + ESCOBILLAS |

### La familia va adelante del código

Los códigos de BMH llevan la familia como prefijo: **REG**17705 es el regulador,
**POL**17705 la polea, **PLA**… la plaqueta. En la pieza, en cambio, suele venir
grabado sólo el número.

Sin conocer la convención, leer "17705" daba una coincidencia *parcial* contra
tres artículos de tres rubros distintos, todos con confianza baja: el cliente
veía una polea al lado del regulador que pidió y dejaba de confiar en el resto
del listado.

Sabiendo el rubro —lo dice la visión, o las palabras del cliente— el número se
completa y pasa a ser el código exacto. `CatalogFamilyResolver` concentra las dos
reglas y las usan los dos caminos, el de foto y el de texto:

| Lo que llega | Rubro | Resultado |
|---|---|---|
| foto con "17705" + regulador | REGULADOR DE VOLTAJE | REG17705, confianza muy alta, un solo candidato |
| "necesito el regulador 17705" | se deduce del texto | ídem |
| "17705" a secas | ninguno | los tres, confianza baja y se pregunta — es genuinamente ambiguo |

Los prefijos no están escritos a mano: salen de agrupar los códigos reales por
rubro (`codePrefixes()`), y se exige un mínimo de usos para no tomar un error de
carga por convención.

### Un código leído no es un hecho

El hecho es que ese código *existe* en el catálogo; que sea **el de esta pieza**
es otra cosa. Y un dígito mal leído no devuelve "nada": devuelve **otra pieza**, y
con confianza máxima por ser coincidencia exacta. Dos casos reales sobre la misma
foto de un regulador que dice `17705`:

| Se leyó | Devolvió | Cómo se detecta |
|---|---|---|
| `1175` | un inducido de arranque Dodge | otra familia: la foto mostraba un regulador |
| `1775` | REG40019, regulador Delco 12V **2 pines** | misma familia, pero la foto mostraba **5 pines** |

El segundo es el peligroso: el rubro coincide, así que mirar la familia no
alcanza. Por eso un acierto de código se contrasta contra **todo** lo demás que
la foto mostró, y si contradice —familia u observación— se descarta y se busca
por lo que sí se ve, avisándole al cliente qué pasó.

La regla es asimétrica, como en el resto de la búsqueda: si el producto tiene el
dato cargado y no coincide, contradice; si no lo tiene cargado, no dice nada.
Y sólo se duda del código cuando la foto es clara (`confidence ≥ 0.7`): si el
modelo no sabe qué pieza es, el código vuelve a ser lo mejor que hay.

### Contar es exacto; medir, no

La búsqueda toleraba ±1 en todo valor numérico. Para un diámetro está bien —la
base guarda `88.8` y `89` para la misma pieza— pero para un **conteo** es fatal:
buscando 5 pines devolvía también los de 4, y el de 4 llegaba a salir primero.
La tolerancia ahora depende del tipo: `count` exige igualdad exacta, `dimension`
mantiene ±1 mm, `electrical` ±0.01.

### Lo que la base no reconoce, no filtra — venga de donde venga

Un filtro que no matchea nada no devuelve "menos resultados": devuelve **cero**,
y entonces salta el respaldo que muestra el rubro entero, perdiendo también los
filtros que sí servían. Un dato malo contamina a los buenos.

Por eso cada valor observado se verifica contra el rubro antes de filtrar, y se
verifica el conjunto **entero** —no sólo lo que vino de la foto—. La memoria de
la conversación guarda lo observado tal cual se dijo, y por ahí volvía a entrar
lo ya descartado: `voltage: "28V"` es la tensión de regulación grabada en la
pieza, no un valor del catálogo, donde los reguladores son de 12V o 24V.

### El número más parecido del rubro

El OCR sobre dígitos estampados en bajorrelieve se come caracteres: en una pieza
que dice `17705` leyó `1775`. Dentro del rubro correcto esa diferencia de un
carácter no es ambigua, así que se busca por distancia de edición entre los
códigos del rubro y se ofrece como **pregunta**, no como certeza:

> Leí "1775" en la pieza, pero ese número no da con lo que muestra la foto.
> El más parecido que tenemos es REG17705. ¿Puede ser ese?

Las dos condiciones —rubro identificado y distancia ≤ 1— son lo que evita volver
al problema que esto viene a resolver: sin ellas aparecerían parecidos de
cualquier familia.

### Observar no es confirmar

Un dato mal contado es tan peligroso como un código mal leído, y peor en un
sentido: en vez de traer la pieza equivocada, **borra la correcta**. Con la ficha
vista de canto el modelo contó 3 pines donde hay 5, y como los conteos exigen
igualdad exacta, el regulador de 5 quedaba fuera del listado.

Por eso los atributos llevan procedencia:

| Origen | Qué puede hacer |
|---|---|
| el cliente lo dijo o lo confirmó | **descartar** productos que no coincidan |
| salió de mirar una foto | sólo **ordenar**: puntúa alto, pero no elimina a nadie |

Esto acota el daño, no lo elimina: con un conteo equivocado nada empuja a la
pieza correcta hacia arriba, así que puede quedar sepultada entre decenas de
piezas parecidas. El arreglo de fondo es que el modelo **no cuente lo que no
puede ver** —el prompt lo dice con todas las letras, con los casos típicos: ficha
de canto, conector en sombra, dientes superpuestos— porque no contar deja la
pieza en el puesto 6, y contar mal la deja afuera.

### Varias fotos, una sola lectura

El cliente manda tres fotos porque ninguna sola muestra todo. Se fusionan: cada
dato lo aporta la foto que lo vio mejor y, ante contradicción, gana la de mayor
confianza. La foto que se compara contra el catálogo es la más confiable, no la
primera.

Contar es lo que más rinde, porque es de lo poco que se puede sacar de una foto
sin inventar nada, y el catálogo lo tiene cargado:

| Qué contar | Dónde vive | Cuánto achica |
|---|---|---|
| dientes del piñón | MOTORES DE ARRANQUE, 246 de 260 | 260 → 84 (9 dientes) |
| pines de la ficha | REGULADOR DE VOLTAJE, 277 de 408 | 408 → 20 (5 pines) |

El prompt pide explícitamente contar ambos, y también nombrar la familia
constructiva (BOSCH, VALEO, MITSUBISHI…), que el catálogo guarda como TIPO.

Los conteos se comparan por número, no con `LIKE`: buscar 9 con `LIKE '%9%'`
traía también los de 19, y buscar 1 traía medio rubro.

### Capturas del catálogo

Una captura de la web de BMH no es una foto de pieza: el código es **texto impreso**.
El modelo marca `image_kind = catalog_screenshot` y `catalog_code` con el producto
principal de la ficha; los códigos de «productos relacionados» salen en el mismo
texto pero **no son el pedido**, así que `ImageAnalysis::searchCodes()` busca sólo
el principal. Antes se buscaban todos y llegaban artículos que el cliente no pidió.

La imagen se analiza a `AI_VISION_ANALYSIS_IMAGE_MAX_PX` (1600) con `detail: high`.
A 512 px el texto de una captura es ilegible y el modelo rellena con lo que supone.

### Índice visual del catálogo (foto sacada de la propia web)

`bmh:index-catalog-images` guarda una huella (promedio + diferencia, 8×8) de cada foto
de producto. La huella se calcula sobre el **contenido recortado** de la imagen
(`CatalogImageFingerprintIndex::contentSquare()`): se descarta el fondo claro y los
marcos grises (< rgb 200 cuenta como contenido), y la pieza se centra en un cuadrado.
Se aplica igual al indexar y al consultar. Sin eso, una captura con el borde de la
tarjeta web no se parecía a la foto original.

Medido con 200 fotos del catálogo, ¿el producto correcto queda en la lista devuelta?

| Captura simulada | Índice v1 (8/6/3) | v2 recortado (8/6/3) | v2 + umbral 12/9/8 |
|---|---|---|---|
| con marco de tarjeta web | 0 % | 87 % | **92 %** |
| reenviada (70 % + JPEG) | 80 % | 88 % | **94 %** |
| recortada 8 % por lado (prueba dura) | 14 % | 79 % | 84 % |

(umbral = distancia total / por huella / tamaño de la lista; `AI_CATALOG_IMAGE_*`.)
Las fotos de piezas que **no** son del catálogo —las de las pruebas reales de
producción— quedan a distancia 14 o más con 10 o más por huella, así que el tope no
las deja pasar. Las **reglas enseñadas** conservan el umbral estricto 8/6
(`ApprovedCorrectionService::RULE_MAX_DISTANCE`): una regla empuja un producto al primer
lugar, el índice sólo propone candidatos para confirmar.

Lo que queda son fichas de aspecto idéntico (pares macho/hembra, variantes con la misma
foto): la distancia no los separa, los resuelven la comparación visual y la
confirmación del cliente. Un resultado de índice **nunca** equivale a un código.
Cambiar la versión invalida el archivo anterior: hay que volver a correr el comando.

### Prueba a escala con la web (2026-11-16)

30 productos reales de 14 categorías, 4 imágenes cada uno (120): captura de escritorio,
captura de celular, **foto hecha a la pantalla** (perspectiva, desenfoque, reflejo, ruido)
y recorte de la foto del producto con el marco de la tarjeta. Flujo completo (visión +
índice + búsqueda) contra el código correcto:

| Imagen | 1.º correcto | En los 3 primeros |
|---|---|---|
| captura de escritorio | 97 % | 100 % |
| captura de celular | 100 % | 100 % |
| foto a la pantalla | 100 % | 100 % |
| recorte de la foto con marco | 63 % | 80 % |

(Medido con la API real, sin errores. Tras un último ajuste —el OCR que agrega una `I` de más, «POLI16066»— las capturas quedan en 30 de 30 por código.)

Cero casos de producto equivocado con confianza alta. Lo que cuenta como error en capturas es `RP033155`: dos
productos distintos con el mismo código y nombre (duplicado del catálogo). Tres defectos
que apareció y se corrigieron:

- **El índice corría antes de leer el código.** Una captura de celular (página alta y casi
  toda blanca) se parecía por casualidad a fotos sueltas, devolvía otro producto y se
  salteaba la lectura. Un código impreso que existe en la base manda sobre el parecido.
- **El OCR confunde I/L con 1 y O con 0**: `POLI6066` por `POL16066`. Si el código exacto
  no existe se prueban esas variantes (de a una y de a dos, hasta 8 caracteres ambiguos).
- **Variantes casi iguales** (una polea de otra, un color de otro) quedaban a distancia de
  hash parecida. `CatalogImageFingerprintIndex::fineSignature()` (miniatura RGB 16×16 del
  contenido) **reordena** los candidatos: el hash decide quién pasa, la firma fina quién va
  primero. Subió el primero correcto de 40 % a 63 %. Como filtro no sirve (el correcto y su
  variante quedan a distancias parecidas), y ampliar el pool del hash a 16/12 sube la lista a
  90 % pero multiplica por 5 los falsos positivos, así que no se hizo.

Para medir bien: la web hace un fade-in de la foto, y una captura tomada a mitad de la
animación sale desvanecida (el recorte pasa a ser casi blanco). Esperar a que cargue.

### Título impreso en la imagen

Una foto hecha a la pantalla (inclinada, con reflejo) o el visor ampliado de la foto de
un producto no dan código ni huella parecida, pero suelen mostrar el nombre. La visión
devuelve `product_text`; `VisionMatchService::buscarPorTitulo()` lo busca ignorando
números (cotas, voltajes, precios) y sólo vale si deja **3 productos o menos**: un título
genérico («ALTERNADOR BOSCH») no identifica. Suma la señal `catalog_title` (peso 55):
coincidencia parcial que el cliente confirma, nunca «muy alta». Después corre la
comparación visual. Caso real: «PASSAT, AMAROK POLEA CRICKET BOSCH» → `30032`.

### Un código de un turno anterior no le gana a una foto nueva

El `code` de la consulta sale de la memoria de la conversación. Si el cliente
escribió «2232» (un rotor) y después sube la foto de una ficha, ese código no se
busca como exacto: `VisionMatchService::soltarCodigoDeUnTurnoAnterior()` lo descarta
cuando la foto es clara, contradice al producto del código y el cliente no lo
repitió en ese mensaje. Caso real: contestaba «ROTOR BOSCH 2232», confianza muy alta,
a la foto de un conector.

### Peso de las imágenes

Las fotos del catálogo llegan a 4,5 MB. Se reducen a 512 px antes de mandarlas
(`ImagePayload`), que es lo que consume el modo `detail: low` del modelo: mandar
el original era pagar por píxeles que la API descarta. Sin esto, una comparación
subía ~24 MB y el turno superaba el `max_execution_time`.

---

## 10. Control de costos

Por conversación se acumulan tokens de entrada, de salida e imágenes analizadas
(`ai_conversations`). Cada turno queda en `ai_audit_logs` con proveedor, modelo,
versión de prompt y latencia.

Techos en `config/bmh.php → ai.limits`. Rate limit de 30 req/min por cliente.

Una foto de 12 MB nunca se manda tal cual.

---

## 11. Prompt versionado

`resources/prompts/bmh-sales-advisor/v1.md`. La versión se registra en cada
mensaje y en la auditoría (`prompt_version`), así se puede comparar rendimiento
entre versiones más adelante.

El prompt no está desperdigado en los Services.

---

## 12. Fallback sin IA

Si el proveedor falla o está apagado:

- la **búsqueda funciona igual** (no depende del modelo);
- el precio se calcula igual;
- la respuesta se arma con una plantilla determinística sobre los mismos datos;
- el cliente ve una respuesta útil, no un error.

Cubierto por `test_si_el_proveedor_falla_la_zona_de_clientes_sigue_respondiendo`:
con un proveedor que lanza excepción en todos sus métodos, la consulta por código
`1833` sigue devolviendo el producto correcto.

**La IA es un potenciador, no un single point of failure.**

---

## 13. Feedback y aprendizaje

> Actualizado 2026-11-16: hay aprendizaje con personas en el circuito (abajo). Sigue sin haber auto-training del proveedor.

Después de cada respuesta, 👍/👎 → `ai_feedback`.

Con el tiempo esto arma un dataset (foto + consulta + producto finalmente
vendido). **No hay auto-training.** Primero recolectar, limpiar, anonimizar,
revisar y versionar. Hoy sólo se almacena feedback estructurado.

### Reglas aprendidas (2026-11-16)

`ai_correction_rules` liga una **entrada** (foto/captura o texto) a un producto, y se
aplica con `ApprovedCorrectionService`. La foto se reconoce igual si llega reenviada o
recomprimida (huella perceptual con el umbral estricto del índice del catálogo); nunca
se adivina que dos fotos distintas son la misma pieza. Tres orígenes:

| Origen | Confianza | Cómo nace |
|---|---|---|
| `admin_teach` | máxima | *Aprendizaje IA → Enseñar*: foto + código correcto |
| `demo_account` | alta | 👍/👎 de una cuenta demo (se aprende al instante) |
| `feedback` | tras revisión | 👎 de un cliente con código esperado, aprobado por un admin |

Una regla suma 120 puntos al producto y el asesor sigue pidiendo confirmar antes de
agregar al carrito. Se desactiva o elimina desde el panel y deja de aplicarse al instante.
Cuentas demo: `ai_demo_accounts`, administradas en `/dashboard/ia/aprendizaje`.

Los recortes (63 % / 80 %) fallan casi siempre por variantes de la misma familia con fotos
casi idénticas (poleas `POL16066`↔`POL16005`, `ZEN1173`↔`ZEN1487`): el asesor las propone con
confianza 0,57–0,62 y pide confirmar, nunca las afirma. Sin texto en la imagen, la foto sola
no las separa; las separa una cota o el código.
