# BMH — Reglas de precios

**No se inventó ninguna fórmula.** Todo lo que sigue se reconstruyó leyendo el
código de producción de este mismo repositorio y verificando contra los datos.

---

## 1. La fórmula real

La referencia es `Producto::precio_unitario_descontado()`
([app/Models/Producto.php:128](../app/Models/Producto.php)), porque es el método
que `Carrito::subtotal_format()` usa para armar el subtotal del pedido. Es
decir: **es el precio que el cliente termina pagando.**

```php
$precio = $this->precio() * (1 - (floatval($this->descuento) / 100));

if (Auth::guard('web')->check()) {
    $precio = $precio * (1 - (Auth::guard('web')->user()->descuento / 100));
}
```

Traducido:

```
precio_neto = productos.precio
            × (1 − productos.descuento / 100)
            × (1 − users.descuento / 100)

precio_final = precio_neto × (1 + IVA / 100)
```

- `productos.precio` está cargado **sin IVA**.
- El IVA (21 %, tabla `impuestos`) se suma **al cerrar el carrito**
  (`Carrito::iva()`), no por línea.
- Los descuentos son **multiplicativos**, no aditivos. Un 10 % de producto más
  un 10 % de cliente da 19 %, no 20 %.

Implementado en [`PricingEngine`](../app/Domain/Pricing/PricingEngine.php) y
fijado por test en `PricingEngineTest::test_el_precio_coincide_con_la_formula_de_produccion`.

---

## 2. Lo que la base tiene cargado pero producción NO aplica

Esto es lo más importante del documento. Hay cuatro mecanismos de precio
presentes en el esquema que **el sistema actual no usa**:

| Mecanismo | Estado en la base | Estado en el código | Decisión |
|---|---|---|---|
| `categorias.aumento` / `categorias.descuento` | 1 categoría con descuento 8 % | **Comentado** en `Producto::precio()` líneas 90-106 | No se aplica |
| `productos.aumento` | **1.024 productos con 10** | **Comentado** en `Producto::precio()` | No se aplica |
| `bonificaciones` (escala por volumen) | 7 tramos, hasta 37,68 % | Se calcula (`Carrito::total_bonificacion()`) pero **`total_format()` no la resta** | Se informa, no se aplica |
| `productos.iva` (flag) | `1` en las 5.054 filas | La lógica que lo usaba (`precio_cliente()`) está comentada | Sin efecto |

**El asesor replica producción.** Si cotizara distinto del carrito, el cliente
vería dos precios diferentes para la misma pieza y perdería la confianza en los
dos. Se controla desde `config/bmh.php`:

```php
'apply_category_modifiers' => false,
'apply_product_aumento'    => false,
'apply_bonificacion'       => false,
```

Cambiar cualquiera a `true` es una **decisión de negocio de BMH**, no técnica.
Cuando la tomen, se cambia el flag y los tests de pricing lo cubren.

### 2.1 El caso `aumento = 10`

1.024 productos (20 % del catálogo) tienen `aumento = 10` cargado y sin aplicar.
Las lecturas posibles:

- se cargó para una actualización de lista que nunca se activó;
- se aplicó una vez sobre `precio` y quedó el rastro;
- se desactivó a propósito y nadie limpió el campo.

**Pendiente de confirmar con BMH.** Mientras tanto no se aplica, que es lo que
hace el sitio hoy.

---

## 3. Descuento del cliente

`users.descuento`, entero, porcentaje. Distribución real:

| % | Clientes |
|---:|---:|
| 0 | 221 |
| 6 | 146 |
| 9 | 37 |
| 14 | 15 |
| 16 | 7 |
| 18 | 6 |
| 10 | 2 |
| 12 / 28 / 50 | 1 c/u |

Se aplica **sólo** al cliente autenticado, resuelto desde la sesión del backend.
Nunca desde un `customer_id` que mande el frontend.

El asistente **no recibe el porcentaje**. Recibe el segmento
(`mayorista`, `preferencial`, …) y el precio ya calculado. Ver
[security.md](security.md).

---

## 4. Reventa

`users.reventa` es un markup sugerido sobre el precio neto, para que el taller
sepa a cuánto revender:

```
precio_reventa = precio_neto × (1 + users.reventa / 100)
```

86 clientes lo tienen configurado (21 % a 100 %). Se muestra **sólo al propio
cliente**.

---

## 5. Cuándo NO se da un precio

`PriceQuote::STATUS_REQUIRES_VALIDATION` cuando:

- `productos.precio` es `NULL` (24 productos), o
- `productos.precio <= 1` (2 productos) — ese `1.00` es el `DEFAULT` de la
  migración, no un precio.

En esos 26 casos el asistente tiene **prohibido dar un número**. Dice que lo
confirma un asesor y ofrece el handoff. El payload que recibe el modelo ni
siquiera incluye el campo `net_price`, así que no puede leerlo por accidente.

---

## 6. La IA no calcula

Regla estructural, no una instrucción en el prompt:

- `ProductView::forAiTool()` **no incluye precio**. El modelo no ve el precio de
  lista, así que no puede multiplicarlo por nada.
- El precio llega por una tool aparte (`get_customer_price`) que devuelve el
  número ya resuelto.
- En streaming, el precio se resuelve **antes** de emitir el primer token. No se
  transmite un valor provisional que después cambie.

Cubierto por `PricingEngineTest::test_el_producto_para_la_ia_no_incluye_precio`.

---

## 7. Validación contra el historial

`pedido_producto` conserva `precio_unitario` y `precio_descontado` de cada línea
histórica (2.913 filas). Es el registro de cómo se calculó el precio en su
momento y sirve para contrastar la fórmula contra la realidad.

⚠️ Los valores están guardados como **varchar con formato argentino**
(`"15.000,50"`). El repositorio los desarma antes de convertir; convertirlos
directo con `(float)` da `15.0`.

Los precios históricos **nunca** se presentan como precio actual: viajan con
`note: "precio_historico_no_vigente"`.

---

## 8. Pendientes para BMH

1. ¿`productos.aumento = 10` debe aplicarse? Afecta a 1.024 productos.
2. ¿La bonificación por volumen debe descontarse del total, o es sólo una
   escala informativa?
3. ¿El descuento de categoría (8 % en `ESTAÑO - DECAPANTE`) sigue vigente?
4. Los 26 productos sin precio real, ¿son discontinuados o falta cargarlos?
