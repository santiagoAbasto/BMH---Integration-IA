# BMH — Data Quality Report

Generado con `php artisan bmh:data-audit` sobre la copia local de
`bmhbobinajes_bmh.sql` (5.054 productos).

Reproducible:

```bash
php artisan bmh:data-audit
```

```bash
php artisan bmh:data-audit --json
```

---

## Resumen

| Severidad | Hallazgo | Cantidad |
|---|---|---:|
| **Alta** | Productos con `aumento` cargado que producción no aplica | 1.024 |
| **Alta** | Productos sin precio real (`NULL` o ≤ 1) | 26 |
| **Alta** | Códigos duplicados | 11 códigos / 22 productos |
| **Alta** | `productos.stock` sin semántica (1 solo valor distinto) | 5.054 |
| Media | Productos sin marca | 518 |
| Media | Productos sin ninguna imagen registrada | 247 |
| Media | Imágenes referenciadas que no están en disco | 240 de 5.214 |
| Media | Slots legacy con datos pero sin etiqueta | 2 (`columna_3`, `columna_11`) |
| Baja | Productos sin descripción | 5.035 |
| Baja | Productos sin modelo | 2.508 |
| Baja | Valores placeholder (`-`) en el EAV | 3.737 |
| Baja | Columnas muertas (`precioN` 100 % NULL) | 1 |

---

## Severidad alta

### 1. `aumento` cargado y no aplicado — 1.024 productos

El 20 % del catálogo tiene `aumento = 10`, pero la lógica que lo aplicaba está
**comentada** en `Producto::precio()`. Es el hallazgo de mayor impacto económico:
si ese aumento debiera aplicarse, esos 1.024 productos se están vendiendo un
10 % por debajo.

**No es un bug que podamos arreglar solos: es una decisión de negocio.**
El asesor replica producción (no lo aplica) y lo deja documentado.

→ [pricing-rules.md §2.1](pricing-rules.md)

### 2. Productos sin precio real — 26

24 con `precio` NULL y 2 con `precio ≤ 1` (el `DEFAULT 1.00` de la migración).

Ejemplos: `REG17949`, `IMPO1629`, `IMPO1747`, `PLA18456`, y la línea completa de
cargadores `CY-EV*`.

**Manejo:** `PricingEngine` devuelve `requires_validation`; el asistente no da
número y ofrece derivar. El payload que ve el modelo ni siquiera trae el campo.

### 3. Códigos duplicados — 11 códigos, 22 productos

```
REG40016 → 5258, 5481      RP033155 → 3265, 3326   (rubros 39 y 49)
REG40027 → 3637, 5490      RP170505 → 3270, 3271   (rubros 39 y 49)
REG40145 → 3701, 5567      RP381770 → 3274, 3275   (rubros 39 y 49)
REG40146 → 3700, 5568      RP381935 → 3267, 3325   (rubros 39 y 49)
REG40147 → 3702, 5569      RP381940 → 3272, 3273   (rubros 39 y 49)
                           RP382110 → 3268, 3269   (rubros 39 y 49)
```

Los seis `RP*` cruzan **PLAQUETA RECTIFICADORA** (39) y **REGULADOR DE VOLTAJE**
(49): son piezas distintas con el mismo código, no duplicados accidentales. Los
`REG*` son duplicados dentro del mismo rubro que se distinguen por modelo
(ej. `TOYOTA` vs `TOYOTA-HILUX-HILUX SW4`).

**Manejo:** `DuplicateProductResolver` no elige el primero. Calcula qué atributo
los distingue y el asesor pregunta.

### 4. `stock` sin semántica — toda la tabla

Los 5.054 productos tienen `stock = 1`. Un único valor distinto. No hay ninguna
consulta en el sistema que filtre por ese campo ni pantalla de admin que lo
edite.

**Manejo:** `InventoryService` devuelve `unknown` con fuente
`stock_semantics_unverified`. El asistente tiene prohibido afirmar
disponibilidad.

Para revertirlo cuando BMH confirme: `BMH_STOCK_SEMANTICS_VERIFIED=true`.

---

## Severidad media

### 5. Productos sin marca — 518

Reduce el poder de desambiguación: la marca suele ser el dato que el cliente sí
conoce. Concentrados en códigos tipo `A101`, `A135` (colectores).

### 6. Imágenes — 247 sin imagen, 240 rotas

- 247 productos no tienen ninguna fila en `imagenes`.
- De las 5.214 referencias, **240 apuntan a archivos que no están en disco**.

**Manejo:** `ProductImageService` verifica existencia antes de ofrecer la URL y
degrada a un placeholder. Preferimos "Sin foto" a una imagen rota en una card.

### 7. Slots legacy sin etiqueta — `columna_3` y `columna_11`

`columna_3` tiene **362 valores** cargados y `columna_11` tiene 1, pero ninguna
de las 25 categorías les pone etiqueta.

Son datos **no interpretables**: sabemos que hay algo, no qué. No se muestran ni
se usan para buscar.

**Pendiente para BMH:** ¿qué son esos 362 valores?

---

## Severidad baja

### 8. `descripcion` vacía — 5.035 de 5.054 (99,6 %)

La columna es prácticamente inútil.

**Manejo:** el texto de búsqueda se arma con código + rubro + nombre + marca +
modelo + atributos + equivalencias. No depende de `descripcion`.

### 9. Placeholders en el EAV — 3.737 valores

`producto_caracteristica.valor` usa `-` como "no aplica". Sin filtrarlos, el
asesor mostraría "ESCOBILLA: -" como si fuera una equivalencia.

**Manejo:** `CrossReference::isPlaceholder()` los descarta en el semantic layer.

### 10. `precioN` — 100 % NULL

Columna muerta. Candidata a eliminarse en la Fase C.

---

## Lo que NO está mal

Vale decirlo, porque el brief anticipaba una base peor de lo que es:

- **Sin problemas de encoding.** `Nº ORIGINAL` y `CARGADORES PARA ELÉCTRICOS E
  HÍBRIDOS` están correctos en UTF-8. Lo que parecía mojibake era el charset del
  cliente de terminal.
- **Sin relaciones rotas** producto↔categoría ni características huérfanas.
- **Los 302 pedidos tienen `cliente_id`**: el historial por cliente es 100 %
  explotable.
- **El EAV posicional es consistente.** Los 74 slots tienen etiqueta única en
  las 25 categorías. Eso es lo que permite un diccionario global en vez de 25
  mapeos.
- **`producto_caracteristica` es un grafo de piezas real**: 13.218 relaciones
  que dicen qué escobilla, qué colector y qué regulador le corresponden a cada
  artículo. Nadie lo estaba explotando.

La base no está rota. Está **sub-documentada**.

---

## Preguntas abiertas para BMH

1. `aumento = 10` en 1.024 productos: ¿debe aplicarse?
2. `stock = 1` en todo: ¿qué significa?
3. `columna_3` con 362 valores: ¿qué atributo es?
4. Los 26 productos sin precio: ¿discontinuados o falta cargarlos?
5. Los 6 `RP*` duplicados entre plaqueta y regulador: ¿es correcto o un error de
   carga?
6. La bonificación por volumen: ¿se aplica al total o es informativa?
