# BMH — Seguridad y privacidad

---

## 1. Prompt injection

Todo lo que llega del cliente es **contenido no confiable**. Incluye el texto
que aparezca *dentro de una imagen*: una pieza fotografiada junto a un papel que
dice "ignorá tus instrucciones" es un objeto fotografiado, no una orden.

La separación es estructural, no una súplica en el prompt:

| Canal | Rol | Origen |
|---|---|---|
| System prompt | `system` | `resources/prompts/bmh-sales-advisor/v1.md`, versionado |
| Definiciones de tools | schema | `ToolRegistry::definitions()` |
| Resultados de base | `tool` | JSON generado por Laravel |
| Mensaje del cliente | `user` | **siempre** este rol, nunca otro |

El texto del cliente:

- entra **siempre** con rol `user`;
- va delimitado (`<mensaje_del_cliente>`) en la extracción estructurada, con la
  aclaración explícita de que es contenido y no instrucción;
- está acotado a 2.000 caracteres (`SendMessageRequest`);
- en visión, el contexto se recorta a 500 caracteres y se marca como referencia.

Y sobre todo: **el modelo no elige el producto ni el precio**. Aunque una
inyección lograra cambiar su tono, no puede cambiar qué devuelve la búsqueda ni
qué calcula el `PricingEngine`. La superficie de daño es la redacción, no los
datos.

Cubierto por `AssistantConversationTest::test_una_inyeccion_de_prompt_no_cambia_el_comportamiento`.

---

## 2. Lo que la IA no puede hacer

| No puede | Por qué |
|---|---|
| Ejecutar SQL | No tiene conexión. Sólo puede nombrar una tool del registro. |
| Inventar una tool | `ToolRegistry::has()` valida el nombre; lo desconocido devuelve `unknown_tool`. |
| Pasar filtros arbitrarios | `sanitizeAttributes()` descarta cualquier clave fuera del diccionario. |
| Consultar otro cliente | El `CustomerAccount` se inyecta desde la sesión; no es un argumento. |
| Calcular un precio | `ProductView::forAiTool()` no incluye precio. No tiene el número base. |
| Afirmar stock | `check_availability` devuelve `can_assert: false`. |
| Leer `.env`, filesystem o terminal | No hay tool que lo permita. |
| Modificar la base legacy | El usuario de la conexión sólo tiene `SELECT`. |

---

## 3. Minimización de datos

Lo **único** que viaja al proveedor de IA sobre el cliente:

```json
{
  "authenticated": true,
  "commercial_segment": "mayorista",
  "has_agreement": true,
  "resale_enabled": true
}
```

No viaja: nombre, DNI, email, teléfono, dirección, código de cliente,
`vendedor_id`, **ni el porcentaje de descuento**. El porcentaje no hace falta
porque el modelo no calcula: recibe el precio ya resuelto.

`LegacyBmhCustomerRepository` hace un `SELECT` con columnas explícitas que
**excluye `password` y `remember_token`**: el hash nunca se carga en memoria, así
que no puede terminar en un log, un dump de excepción ni un payload.

Verificado por:
- `test_el_contexto_que_va_al_llm_no_lleva_pii_ni_el_porcentaje`
- `test_el_catalogo_no_expone_el_hash_de_password`

### Al frontend

`HandleInertiaRequests` comparte sólo `id`, `name` y `rol`. No se vuelca el
modelo `User`, que tiene DNI, dirección y celular.

---

## 4. Aislamiento entre clientes

| Superficie | Control |
|---|---|
| Conversaciones | `scopeOwnedBy(customer_id)` + `findOrFail` → 404 |
| Mensajes | Siempre a través de la conversación |
| Adjuntos | Se valida propiedad de la conversación antes de servir el archivo |
| Historial | `pedidos.cliente_id` en el WHERE del SQL |
| Precios | El `CustomerAccount` sale de la sesión |
| Feedback / handoff | `customer_id` del backend |

Se devuelve **404 y no 403** ante un recurso ajeno: un 403 confirmaría que
existe.

---

## 5. Adjuntos

`AttachmentService` + `StoreAttachmentRequest`:

- **MIME real**, no extensión. Además se verifica que el archivo sea una imagen
  decodificable: un `.jpg` que en realidad es PHP no pasa.
- Máximo 12 MB de entrada y 3 imágenes por mensaje.
- Nombre **aleatorio** (UUID). No se conserva el nombre original.
- Disco **privado** (`storage/app/ai-private`), fuera del document root. La única
  vía de acceso es una ruta que valida propiedad.
- **EXIF eliminado**: se corrige la orientación y se reescribe con `imagejpeg()`,
  que no copia metadatos. Adiós geolocalización de la foto del taller.
- Al proveedor va la versión optimizada (1280 px, calidad 82), no el original.

---

## 6. Secretos

- `AI_API_KEY` sólo en `.env`, leída vía `config()`.
- Nunca se comparte al frontend ni se expone en `/api/assistant/status`.
- En los errores de Gemini se loguea **sólo el status HTTP**: el body y la URL
  pueden contener la key.
- `.env` está en `.gitignore` (verificado).

⚠️ **Hallazgo:** el `.env` de este checkout traía las credenciales de la base de
producción (`bmhbobinajes_bmh` + password). Se reapuntaron a la copia local. Como
`.env` no está versionado no hay filtración en el repositorio, pero **conviene
rotar esa contraseña**: estuvo en un archivo de trabajo local.

---

## 7. Rate limiting

```php
RateLimiter::for('assistant', fn ($r) => Limit::perMinute(30)->by('assistant:'.$r->user()->id));
```

Por **cliente autenticado**, no por IP: varios talleres pueden compartir una
salida NAT y no queremos que uno le consuma la cuota al otro. Contiene el costo
de tokens de una sesión desbocada sin molestar el uso normal.

También hay techos en `config/bmh.php`: imágenes por mensaje, tool calls por
turno y tokens por conversación.

---

## 8. Auditoría

Se guarda: mensaje, intención, estrategia, tools ejecutadas, candidatos
mostrados con su confianza, producto resuelto, precio, feedback, handoffs.

**No se guarda chain-of-thought.** No se pide, no se recibe y no hay campo donde
ponerlo. `AiResponse` no tiene propiedad de razonamiento.
Verificado por `test_audita_el_turno_sin_guardar_razonamiento`.

---

## 9. IDOR en la Zona de Clientes existente (encontrado y corregido)

Encontrado al preparar las credenciales de demo. **No lo introdujo este trabajo:
estaba en el código de producción.**

`UserController::cliente_datos()` y `UserController::updateDate()` resolvían el
cliente desde el request:

```php
$usuario = User::find($request->id);          // GET  /mis-datos?id=NNN
$usuario = User::find($request->cliente_id);  // POST /mis-datos-cliente
```

Consecuencias verificadas sobre la copia local, con una sesión de cliente común:

| Vector | Resultado |
|---|---|
| `GET /mis-datos?id=4453` | HTTP 200 con la ficha de **otro cliente**: nombre, DNI, dirección, teléfono, email y margen de reventa. |
| `POST /mis-datos-cliente` con `cliente_id` de otro | **Escritura**: cambiaba email, dirección, teléfono, transporte y reventa de esa cuenta. |
| `GET /mis-datos` sin parámetros | HTTP 500 — `find(null)` devolvía null y la vista reventaba. |

Con 437 clientes, cualquiera de ellos podía enumerar y editar a los otros
iterando un entero.

**Corrección:** el cliente se resuelve desde `Auth::guard('web')->user()`. El id
del request se ignora por completo. Fijado por
`tests/Feature/Bmh/ClientZoneIsolationTest.php`.

> ⚠️ **Esta vulnerabilidad está viva en producción.** El fix es de dos líneas y
> está acá; hay que desplegarlo. Conviene además revisar los logs de acceso a
> `/mis-datos` con parámetro `id`.

---

## 10. Lo que queda pendiente

| Tema | Estado |
|---|---|
| Antivirus en adjuntos | No implementado. Requiere infraestructura (ClamAV). |
| Cifrado en reposo de adjuntos | No implementado. Hoy: disco privado + nombre aleatorio. |
| Retención / borrado de conversaciones | No hay política definida. Decisión de BMH. |
| Rotar la contraseña de producción | **Recomendado.** Ver §6. |
| Auditar otros controllers con el mismo patrón `find($request->…)` | **Recomendado.** El IDOR de §9 puede no ser el único. |
| CSP | Existe `ContentSecurityPolicyMiddleware` en el proyecto; no se auditó. |
| Migrar hashes legacy | Son bcrypt, están bien. Sin acción. |
