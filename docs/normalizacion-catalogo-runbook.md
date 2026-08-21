# Runbook clínico — normalización de atributos del catálogo

## Objetivo

Copiar `productos.columna_1..columna_78` a un modelo relacionado, auditable y
sin truncamiento. Esta fase es **aditiva**: no elimina ni modifica las columnas
legacy y no toca el EAV existente de equivalencias (`producto_caracteristica`).

## Tablas nuevas

- `atributos_producto`: diccionario, slot de origen, tipo, unidad y estado semántico.
- `atributo_producto_categoria`: atributos aplicables por categoría.
- `producto_atributo`: valor por producto y atributo, `LONGTEXT`, hash SHA-256,
  foreign keys y `UNIQUE(producto_id, atributo_id)`.

Los slots 3, 11, 19, 20, 23 y 52 se copian con `semantica_confirmada = false`.
No se inventa una etiqueta y no se pierde su contenido.

## Procedimiento para la base real

1. Poner el administrador en mantenimiento o garantizar que no haya escrituras.
2. Crear un backup consistente con credenciales de backup (`RELOAD` o
   `LOCK TABLES`) y comprobar restauración en una base temporal.
3. Registrar conteos de `productos`, máximos IDs y checksum SHA-256 del dump.
4. Ejecutar primero:

   ```bash
   php artisan migrate --pretend
   php artisan migrate
   php artisan bmh:normalize-catalog-attributes --dry-run
   php artisan bmh:normalize-catalog-attributes
   php artisan bmh:normalize-catalog-attributes --verify-only
   ```

5. Guardar la salida de verificación. Debe cumplir simultáneamente:

   - origen no vacío = destino normalizado;
   - diferencias byte a byte = 0;
   - relaciones huérfanas = 0.

6. Repetir la verificación después de habilitar tráfico.

## Rollback

La aplicación actual continúa leyendo los campos legacy. Si aparece cualquier
diferencia, detener el despliegue y conservar las tablas nuevas para análisis.
No ejecutar `migrate:rollback` en producción sin revisar otras migraciones del
mismo batch. Las columnas originales siguen siendo la fuente recuperable.

## Fases posteriores (no ejecutar todavía)

1. Implementar escritura dual y comparación automática en cada alta/edición.
2. Operar varias semanas sin diferencias.
3. Cambiar lecturas al modelo normalizado bajo feature flag.
4. Confirmar con BMH la semántica de los seis slots desconocidos.
5. Sólo con backup restaurado y aceptación formal, retirar `columna_1..78` en
   otra migración independiente. Ese `DROP COLUMN` no forma parte de esta fase.

## Advertencias

- `producto_caracteristica.valor` mide sólo 50 caracteres y hay valores legacy
  de hasta 1.528; reutilizarla truncaría datos.
- No mezclar atributos técnicos con el grafo actual de equivalencias/piezas.
- No normalizar nombres, unidades ni placeholders durante la copia clínica: la
  transformación semántica debe ser una fase posterior y trazable.
