-- Sync de datos legacy: bmh_last_tmp -> bmh_legacy
-- Generado: 2026-08-25 17:32:15
-- No altera estructura. No crea tablas. No toca las 3 tablas nuevas ni las normalizadas.
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- admins  | INCLUIDA (decisión final: traer users+admins del dump)
REPLACE INTO `bmh_legacy`.`admins` SELECT * FROM `bmh_last_tmp`.`admins`;

-- anuncios  | dump=1  legacy=1
REPLACE INTO `bmh_legacy`.`anuncios` (`id`, `contenido`, `mostrar`, `created_at`, `updated_at`) SELECT `id`, `contenido`, `mostrar`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`anuncios`;

-- bonificaciones  | dump=7  legacy=7
REPLACE INTO `bmh_legacy`.`bonificaciones` (`id`, `orden`, `desde`, `hasta`, `porcentaje`, `updated_at`) SELECT `id`, `orden`, `desde`, `hasta`, `porcentaje`, `updated_at` FROM `bmh_last_tmp`.`bonificaciones`;

-- caracteristicas  | dump=786  legacy=782
REPLACE INTO `bmh_legacy`.`caracteristicas` (`id`, `orden`, `nombre`, `created_at`, `deleted_at`, `updated_at`) SELECT `id`, `orden`, `nombre`, `created_at`, `deleted_at`, `updated_at` FROM `bmh_last_tmp`.`caracteristicas`;

-- carrito_informacion  | dump=1  legacy=1
REPLACE INTO `bmh_legacy`.`carrito_informacion` (`id`, `info`, `pedido`, `pedido_titulo`, `info_efectivo`, `info_mp`, `info_empresa`, `info_retiro`, `info_convenir`, `descuento_efectivo`, `created_at`, `updated_at`) SELECT `id`, `info`, `pedido`, `pedido_titulo`, `info_efectivo`, `info_mp`, `info_empresa`, `info_retiro`, `info_convenir`, `descuento_efectivo`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`carrito_informacion`;

-- categoria_caracteristica  | dump=1548  legacy=1541
REPLACE INTO `bmh_legacy`.`categoria_caracteristica` (`id`, `categoria_id`, `caracteristica_id`, `created_at`, `deleted_at`) SELECT `id`, `categoria_id`, `caracteristica_id`, `created_at`, `deleted_at` FROM `bmh_last_tmp`.`categoria_caracteristica`;

-- categorias  | dump=25  legacy=25
REPLACE INTO `bmh_legacy`.`categorias` (`id`, `nombre`, `alias`, `orden`, `portada`, `aumento`, `descuento`, `destacada`, `created_at`, `updated_at`, `columna_1`, `columna_2`, `columna_3`, `columna_4`, `columna_5`, `columna_6`, `columna_7`, `columna_8`, `columna_9`, `columna_10`, `columna_11`, `columna_12`, `columna_13`, `columna_14`, `columna_15`, `columna_16`, `columna_17`, `columna_18`, `columna_19`, `columna_20`, `columna_21`, `columna_22`, `columna_23`, `columna_24`, `columna_25`, `columna_26`, `columna_27`, `columna_28`, `columna_29`, `columna_30`, `columna_31`, `columna_32`, `columna_33`, `columna_34`, `columna_35`, `columna_36`, `columna_37`, `columna_38`, `Columna_39`, `columna_40`, `columna_41`, `columna_42`, `columna_43`, `columna_44`, `columna_45`, `columna_46`, `columna_47`, `columna_48`, `columna_49`, `columna_50`, `columna_51`, `columna_52`, `columna_53`, `columna_54`, `columna_55`, `columna_56`, `columna_57`, `columna_58`, `columna_59`, `columna_60`, `columna_61`, `columna_62`, `columna_63`, `columna_64`, `columna_65`, `columna_66`, `columna_67`, `columna_68`, `columna_69`, `columna_70`, `columna_71`, `columna_72`, `columna_73`, `columna_74`, `columna_75`, `columna_76`, `columna_77`, `columna_78`) SELECT `id`, `nombre`, `alias`, `orden`, `portada`, `aumento`, `descuento`, `destacada`, `created_at`, `updated_at`, `columna_1`, `columna_2`, `columna_3`, `columna_4`, `columna_5`, `columna_6`, `columna_7`, `columna_8`, `columna_9`, `columna_10`, `columna_11`, `columna_12`, `columna_13`, `columna_14`, `columna_15`, `columna_16`, `columna_17`, `columna_18`, `columna_19`, `columna_20`, `columna_21`, `columna_22`, `columna_23`, `columna_24`, `columna_25`, `columna_26`, `columna_27`, `columna_28`, `columna_29`, `columna_30`, `columna_31`, `columna_32`, `columna_33`, `columna_34`, `columna_35`, `columna_36`, `columna_37`, `columna_38`, `Columna_39`, `columna_40`, `columna_41`, `columna_42`, `columna_43`, `columna_44`, `columna_45`, `columna_46`, `columna_47`, `columna_48`, `columna_49`, `columna_50`, `columna_51`, `columna_52`, `columna_53`, `columna_54`, `columna_55`, `columna_56`, `columna_57`, `columna_58`, `columna_59`, `columna_60`, `columna_61`, `columna_62`, `columna_63`, `columna_64`, `columna_65`, `columna_66`, `columna_67`, `columna_68`, `columna_69`, `columna_70`, `columna_71`, `columna_72`, `columna_73`, `columna_74`, `columna_75`, `columna_76`, `columna_77`, `columna_78` FROM `bmh_last_tmp`.`categorias`;

-- contacto  | dump=1  legacy=1
REPLACE INTO `bmh_legacy`.`contacto` (`id`, `direccion`, `iframe`, `tel`, `mail`, `instagram`, `tiktok`, `whatsapp`, `facebook`, `created_at`, `updated_at`) SELECT `id`, `direccion`, `iframe`, `tel`, `mail`, `instagram`, `tiktok`, `whatsapp`, `facebook`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`contacto`;

-- descargas  | dump=4  legacy=4
REPLACE INTO `bmh_legacy`.`descargas` (`id`, `orden`, `nombre`, `archivo`, `sector`, `path`, `peso`, `formato`, `created_at`, `updated_at`) SELECT `id`, `orden`, `nombre`, `archivo`, `sector`, `path`, `peso`, `formato`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`descargas`;

-- dimension_pedido  | dump=0  legacy=0
REPLACE INTO `bmh_legacy`.`dimension_pedido` (`id`, `cantidad`, `precio_unitario`, `precio_descontado`, `descuento_cliente`, `descuento_categoria`, `descuento_producto`, `dimension_id`, `pedido_id`, `created_at`, `updated_at`) SELECT `id`, `cantidad`, `precio_unitario`, `precio_descontado`, `descuento_cliente`, `descuento_categoria`, `descuento_producto`, `dimension_id`, `pedido_id`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`dimension_pedido`;

-- dimensiones  | dump=0  legacy=0
REPLACE INTO `bmh_legacy`.`dimensiones` (`id`, `diametro`, `largo`, `altura_cuadrado`, `altura_cabeza`, `diametro_cabeza`, `precio`, `unidad`, `producto_id`, `created_at`, `updated_at`) SELECT `id`, `diametro`, `largo`, `altura_cuadrado`, `altura_cabeza`, `diametro_cabeza`, `precio`, `unidad`, `producto_id`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`dimensiones`;

-- SKIP (excluida): equivalencias

-- SKIP (excluida): failed_jobs

-- imagenes  | dump=5335  legacy=5224
REPLACE INTO `bmh_legacy`.`imagenes` (`id`, `path`, `baner_texto`, `baner_texto_2`, `sector`, `producto_id`, `tipo`, `orden`, `posicion`, `created_at`, `updated_at`) SELECT `id`, `path`, `baner_texto`, `baner_texto_2`, `sector`, `producto_id`, `tipo`, `orden`, `posicion`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`imagenes`;

-- impuestos  | dump=1  legacy=1
REPLACE INTO `bmh_legacy`.`impuestos` (`id`, `nombre`, `porcentaje`, `created_at`, `updated_at`) SELECT `id`, `nombre`, `porcentaje`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`impuestos`;

-- mails  | dump=1  legacy=1
REPLACE INTO `bmh_legacy`.`mails` (`id`, `registro_titulo`, `registro`, `habilitado_titulo`, `habilitado`, `created_at`, `updated_at`) SELECT `id`, `registro_titulo`, `registro`, `habilitado_titulo`, `habilitado`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`mails`;

-- medida_producto  | dump=0  legacy=0
REPLACE INTO `bmh_legacy`.`medida_producto` (`id`, `producto_id`, `medida_id`) SELECT `id`, `producto_id`, `medida_id` FROM `bmh_last_tmp`.`medida_producto`;

-- medidas  | dump=2  legacy=2
REPLACE INTO `bmh_legacy`.`medidas` (`id`, `codigo`, `descripcion`, `cantidad`, `updated_at`, `created_at`) SELECT `id`, `codigo`, `descripcion`, `cantidad`, `updated_at`, `created_at` FROM `bmh_last_tmp`.`medidas`;

-- metadatos  | dump=5  legacy=5
REPLACE INTO `bmh_legacy`.`metadatos` (`id`, `seccion`, `keyword`, `descripcion`, `created_at`, `updated_at`) SELECT `id`, `seccion`, `keyword`, `descripcion`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`metadatos`;

-- SKIP (excluida): migrations

-- newsletter  | dump=0  legacy=0
REPLACE INTO `bmh_legacy`.`newsletter` (`id`, `mail`, `created_at`, `updated_at`) SELECT `id`, `mail`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`newsletter`;

-- nosotros_contenido  | dump=1  legacy=1
REPLACE INTO `bmh_legacy`.`nosotros_contenido` (`id`, `info`, `mision`, `vision`, `valores`, `imagen_file`, `titulo_home`, `info_home`, `imagen_file_home`, `titulo_baner`, `texto_baner`, `imagen_file_kovea`, `titulo_kovea`, `texto_kovea`, `created_at`, `updated_at`) SELECT `id`, `info`, `mision`, `vision`, `valores`, `imagen_file`, `titulo_home`, `info_home`, `imagen_file_home`, `titulo_baner`, `texto_baner`, `imagen_file_kovea`, `titulo_kovea`, `texto_kovea`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`nosotros_contenido`;

-- novedades  | dump=3  legacy=3
REPLACE INTO `bmh_legacy`.`novedades` (`id`, `portada`, `etiqueta`, `titulo`, `epigrafe`, `texto`, `destacada`, `orden`, `created_at`, `updated_at`) SELECT `id`, `portada`, `etiqueta`, `titulo`, `epigrafe`, `texto`, `destacada`, `orden`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`novedades`;

-- SKIP (excluida): password_reset_tokens

-- pedido_producto  | dump=2952  legacy=2917
REPLACE INTO `bmh_legacy`.`pedido_producto` (`id`, `cantidad`, `precio_unitario`, `precio_descontado`, `descuento_producto`, `producto_id`, `pedido_id`, `created_at`, `updated_at`) SELECT `id`, `cantidad`, `precio_unitario`, `precio_descontado`, `descuento_producto`, `producto_id`, `pedido_id`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`pedido_producto`;

-- pedidos  | dump=308  legacy=304
REPLACE INTO `bmh_legacy`.`pedidos` (`id`, `fecha`, `nombre`, `dni`, `mail`, `provincia`, `localidad`, `direccion`, `celular`, `cp`, `direccion2`, `provincia2`, `localidad2`, `cp2`, `tipo_envio`, `tipo_pago`, `descuento_cliente`, `descuento_pago`, `bonificacion`, `total_pedido`, `archivo`, `notas`, `estado`, `estado_orden`, `vendedor`, `cliente_id`, `created_at`, `updated_at`) SELECT `id`, `fecha`, `nombre`, `dni`, `mail`, `provincia`, `localidad`, `direccion`, `celular`, `cp`, `direccion2`, `provincia2`, `localidad2`, `cp2`, `tipo_envio`, `tipo_pago`, `descuento_cliente`, `descuento_pago`, `bonificacion`, `total_pedido`, `archivo`, `notas`, `estado`, `estado_orden`, `vendedor`, `cliente_id`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`pedidos`;

-- SKIP (excluida): personal_access_tokens

-- producto_caracteristica  | dump=13292  legacy=13218
REPLACE INTO `bmh_legacy`.`producto_caracteristica` (`id`, `producto_id`, `caracteristica_id`, `valor`, `created_at`, `deleted_at`) SELECT `id`, `producto_id`, `caracteristica_id`, `valor`, `created_at`, `deleted_at` FROM `bmh_last_tmp`.`producto_caracteristica`;

-- producto_repuesto  | dump=0  legacy=0
REPLACE INTO `bmh_legacy`.`producto_repuesto` (`id`, `repuesto_id`, `producto_id`) SELECT `id`, `repuesto_id`, `producto_id` FROM `bmh_last_tmp`.`producto_repuesto`;

-- producto_uso  | dump=0  legacy=0
REPLACE INTO `bmh_legacy`.`producto_uso` (`id`, `producto_id`, `uso_id`, `created_at`, `updated_at`) SELECT `id`, `producto_id`, `uso_id`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`producto_uso`;

-- productos  | dump=5176  legacy=5055
REPLACE INTO `bmh_legacy`.`productos` (`id`, `orden`, `codigo`, `nombre`, `descripcion`, `caracteristicas`, `ficha`, `precio`, `precioN`, `iva`, `descuento`, `aumento`, `destacada`, `estado`, `stock`, `diametroInterno`, `diametroExterno`, `anchoBanda`, `tolerancia`, `blindaje`, `marca`, `modelo`, `categoria_id`, `created_at`, `updated_at`, `columna_1`, `columna_2`, `columna_3`, `columna_4`, `columna_5`, `columna_6`, `columna_7`, `columna_8`, `columna_9`, `columna_10`, `columna_11`, `columna_12`, `columna_13`, `columna_14`, `columna_15`, `columna_16`, `columna_17`, `columna_18`, `columna_19`, `columna_20`, `columna_21`, `columna_22`, `columna_23`, `columna_24`, `columna_25`, `columna_26`, `columna_27`, `columna_28`, `columna_29`, `columna_30`, `columna_31`, `columna_32`, `columna_33`, `columna_34`, `columna_35`, `columna_36`, `columna_37`, `columna_38`, `columna_39`, `columna_40`, `columna_41`, `columna_42`, `columna_43`, `columna_44`, `columna_45`, `columna_46`, `columna_47`, `columna_48`, `columna_49`, `columna_50`, `columna_51`, `columna_52`, `columna_53`, `columna_54`, `columna_55`, `columna_56`, `columna_57`, `columna_58`, `columna_59`, `columna_60`, `columna_61`, `columna_62`, `columna_63`, `columna_64`, `columna_65`, `columna_66`, `columna_67`, `columna_68`, `columna_69`, `columna_70`, `columna_71`, `columna_72`, `columna_73`, `columna_74`, `columna_75`, `columna_76`, `columna_77`, `columna_78`) SELECT `id`, `orden`, `codigo`, `nombre`, `descripcion`, `caracteristicas`, `ficha`, `precio`, `precioN`, `iva`, `descuento`, `aumento`, `destacada`, `estado`, `stock`, `diametroInterno`, `diametroExterno`, `anchoBanda`, `tolerancia`, `blindaje`, `marca`, `modelo`, `categoria_id`, `created_at`, `updated_at`, `columna_1`, `columna_2`, `columna_3`, `columna_4`, `columna_5`, `columna_6`, `columna_7`, `columna_8`, `columna_9`, `columna_10`, `columna_11`, `columna_12`, `columna_13`, `columna_14`, `columna_15`, `columna_16`, `columna_17`, `columna_18`, `columna_19`, `columna_20`, `columna_21`, `columna_22`, `columna_23`, `columna_24`, `columna_25`, `columna_26`, `columna_27`, `columna_28`, `columna_29`, `columna_30`, `columna_31`, `columna_32`, `columna_33`, `columna_34`, `columna_35`, `columna_36`, `columna_37`, `columna_38`, `columna_39`, `columna_40`, `columna_41`, `columna_42`, `columna_43`, `columna_44`, `columna_45`, `columna_46`, `columna_47`, `columna_48`, `columna_49`, `columna_50`, `columna_51`, `columna_52`, `columna_53`, `columna_54`, `columna_55`, `columna_56`, `columna_57`, `columna_58`, `columna_59`, `columna_60`, `columna_61`, `columna_62`, `columna_63`, `columna_64`, `columna_65`, `columna_66`, `columna_67`, `columna_68`, `columna_69`, `columna_70`, `columna_71`, `columna_72`, `columna_73`, `columna_74`, `columna_75`, `columna_76`, `columna_77`, `columna_78` FROM `bmh_last_tmp`.`productos`;

-- repuestos  | dump=1  legacy=1
REPLACE INTO `bmh_legacy`.`repuestos` (`id`, `codigo`, `descripcion`, `cantidad`, `updated_at`, `created_at`) SELECT `id`, `codigo`, `descripcion`, `cantidad`, `updated_at`, `created_at` FROM `bmh_last_tmp`.`repuestos`;

-- subcategorias  | dump=0  legacy=0
REPLACE INTO `bmh_legacy`.`subcategorias` (`id`, `nombre`, `categoria_id`, `created_at`, `updated_at`) SELECT `id`, `nombre`, `categoria_id`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`subcategorias`;

-- users  | INCLUIDA (decisión final: traer users+admins del dump)
REPLACE INTO `bmh_legacy`.`users` SELECT * FROM `bmh_last_tmp`.`users`;

-- usos  | dump=3  legacy=3
REPLACE INTO `bmh_legacy`.`usos` (`id`, `nombre`, `created_at`, `updated_at`) SELECT `id`, `nombre`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`usos`;

-- xml  | dump=0  legacy=0
REPLACE INTO `bmh_legacy`.`xml` (`id`, `path`, `pedido_id`, `created_at`, `updated_at`) SELECT `id`, `path`, `pedido_id`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`xml`;

-- zonas_postales  | dump=2  legacy=2
REPLACE INTO `bmh_legacy`.`zonas_postales` (`id`, `nombre`, `costo`, `created_at`, `updated_at`) SELECT `id`, `nombre`, `costo`, `created_at`, `updated_at` FROM `bmh_last_tmp`.`zonas_postales`;

SET FOREIGN_KEY_CHECKS=1;
