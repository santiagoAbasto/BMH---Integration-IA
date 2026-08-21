# Informe de ejecución local — 14 de agosto de 2026

## Resultado

La fase aditiva de normalización fue ejecutada sobre `bmh_legacy` sin eliminar
ni modificar columnas legacy.

| Control | Resultado |
|---|---:|
| Productos antes y después | 5.054 |
| Valores legacy no vacíos | 20.777 |
| Valores copiados a `producto_atributo` | 20.777 |
| Diferencias byte a byte | 0 |
| Relaciones huérfanas | 0 |
| Atributos del diccionario | 78 |

Los seis slots sin semántica conocida también fueron preservados, marcados como
pendientes. No se reinterpretaron datos, no se descartaron placeholders y no se
truncaron valores.

## Respaldo local

- Archivo: `storage/app/backups/bmh_legacy_pre_normalization_20260814_1055.sql`
- Tamaño: 8.243.131 bytes
- SHA-256: `9b3c7803b4bb2287ea2c0d2e87b0aab19459a334715cc173adf4b93f4a72cad5`
- El dump finaliza con la marca `Dump completed on 2026-08-14 11:02:04`.

El usuario MySQL local no posee `RELOAD/FLUSH_TABLES`, por lo que el dump se
generó sin bloqueo global. La aplicación local no estaba recibiendo escrituras
durante la operación. Para la base real es obligatorio usar credenciales de
backup, snapshot del proveedor o una réplica y comprobar una restauración.

## Artefactos entregados

- Migración `2026_08_14_120000_create_normalized_product_attributes.php`.
- Comando idempotente `bmh:normalize-catalog-attributes`.
- Runbook `docs/normalizacion-catalogo-runbook.md`.

## Alcance deliberadamente excluido

No se eliminaron `columna_1..78`, no se cambió todavía la lectura del sitio y no
se alteró `producto_caracteristica`. El corte de lectura y la eliminación de
columnas sólo pueden ocurrir después de escritura dual, observación en la base
real, backup restaurado y aceptación formal.
