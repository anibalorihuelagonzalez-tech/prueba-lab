-- =========================================================================
-- 01_ventas_contabilidad.sql
-- MIGRACIÓN: módulos de VENTAS y CONTABILIDAD para CNSB_LAB
-- =========================================================================
-- ✔ SEGURA PARA UNA BASE QUE YA TIENE DATOS: solo usa CREATE TABLE IF NOT EXISTS
--   e INSERT IGNORE. NO borra ni modifica ninguna tabla existente.
-- ✔ Se puede ejecutar más de una vez sin duplicar nada.
-- CÓMO USAR: phpMyAdmin -> selecciona la base "cnsb_lab" -> pestaña Importar
--            (o pestaña SQL, pegar todo) -> Continuar.
-- Motor: MariaDB 10.4+ / MySQL 8+.
-- =========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------------
-- 1. CONFIGURACIÓN GENERAL (clave/valor) — datos de la empresa, impuestos,
--    cuentas contables automáticas y plantillas de WhatsApp.
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` varchar(60) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 2. CORRELATIVOS — numeración de documentos (FAC-000001, REC-000001...).
--    Se incrementa con bloqueo de fila para que nunca se repita un número.
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `correlativos` (
  `clave` varchar(40) NOT NULL,
  `prefijo` varchar(10) NOT NULL,
  `ultimo` int(10) unsigned NOT NULL DEFAULT 0,
  `digitos` tinyint(3) unsigned NOT NULL DEFAULT 6,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 3. PLAN DE CUENTAS — catálogo contable jerárquico (1, 1.1, 1.1.01, 1.1.01.001)
--    permite_movimiento = 1 solo en las cuentas "hoja" donde se registran asientos.
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plan_cuentas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` enum('ACTIVO','PASIVO','PATRIMONIO','INGRESO','COSTO','GASTO') NOT NULL,
  `naturaleza` enum('DEUDORA','ACREEDORA') NOT NULL,
  `nivel` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `padre_id` int(10) unsigned DEFAULT NULL,
  `permite_movimiento` tinyint(1) NOT NULL DEFAULT 1,
  `es_sistema` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cuenta_codigo` (`codigo`),
  KEY `idx_cuenta_padre` (`padre_id`),
  KEY `idx_cuenta_tipo` (`tipo`),
  CONSTRAINT `fk_cuenta_padre` FOREIGN KEY (`padre_id`) REFERENCES `plan_cuentas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 4. PERÍODOS CONTABLES — un registro por mes. Un mes CERRADO no admite asientos.
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `periodos_contables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `anio` smallint(5) unsigned NOT NULL,
  `mes` tinyint(3) unsigned NOT NULL,
  `estatus` enum('ABIERTO','CERRADO') NOT NULL DEFAULT 'ABIERTO',
  `fecha_cierre` datetime DEFAULT NULL,
  `usuario_cierre_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_periodo` (`anio`,`mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 5. ASIENTOS CONTABLES (libro diario). Un asiento nunca se edita ni se borra:
--    se corrige anulándolo (se genera un asiento inverso).
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asientos_contables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL,
  `fecha` date NOT NULL,
  `periodo_id` int(10) unsigned NOT NULL,
  `tipo` enum('MANUAL','VENTA','NOTA_CREDITO','COBRO','GASTO','PAGO_GASTO','ANULACION','AJUSTE','APERTURA','CIERRE') NOT NULL DEFAULT 'MANUAL',
  `concepto` varchar(255) NOT NULL,
  `referencia_tabla` varchar(40) DEFAULT NULL,
  `referencia_id` int(10) unsigned DEFAULT NULL,
  `tasa_cambio` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `total_debe` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_haber` decimal(18,2) NOT NULL DEFAULT 0.00,
  `estatus` enum('ACTIVO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `asiento_original_id` int(10) unsigned DEFAULT NULL,
  `asiento_reverso_id` int(10) unsigned DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asiento_numero` (`numero`),
  KEY `idx_asiento_fecha` (`fecha`),
  KEY `idx_asiento_periodo` (`periodo_id`),
  KEY `idx_asiento_ref` (`referencia_tabla`,`referencia_id`),
  KEY `idx_asiento_usuario` (`usuario_id`),
  CONSTRAINT `fk_asiento_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos_contables` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_asiento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `asientos_contables_detalles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asiento_id` int(10) unsigned NOT NULL,
  `cuenta_id` int(10) unsigned NOT NULL,
  `debe` decimal(18,2) NOT NULL DEFAULT 0.00,
  `haber` decimal(18,2) NOT NULL DEFAULT 0.00,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_det_asiento` (`asiento_id`),
  KEY `idx_det_cuenta` (`cuenta_id`),
  CONSTRAINT `fk_det_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `asientos_contables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_det_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `plan_cuentas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 6. MÉTODOS DE PAGO — cada método apunta a la cuenta contable (caja/banco)
--    donde entra el dinero. aplica_igtf = 1 para pagos en divisas.
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `metodos_pago` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `tipo` enum('EFECTIVO','TRANSFERENCIA','PAGO_MOVIL','ZELLE','PUNTO_VENTA','CHEQUE','OTRO') NOT NULL DEFAULT 'OTRO',
  `moneda_codigo` char(3) NOT NULL DEFAULT 'VES',
  `cuenta_contable_id` int(10) unsigned NOT NULL,
  `aplica_igtf` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_referencia` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_metodo_nombre` (`nombre`),
  KEY `idx_metodo_cuenta` (`cuenta_contable_id`),
  CONSTRAINT `fk_metodo_cuenta` FOREIGN KEY (`cuenta_contable_id`) REFERENCES `plan_cuentas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 7. VENTAS: documentos (prefactura, factura, nota de crédito, nota de entrega)
--    Los precios se manejan en USD (como los servicios) y se convierten a Bs
--    con la tasa del día de emisión; saldo_usd es lo que la clínica aún debe.
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ventas_documentos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo` enum('PREFACTURA','FACTURA','NOTA_CREDITO','NOTA_ENTREGA') NOT NULL,
  `numero` varchar(30) DEFAULT NULL,
  `numero_control` varchar(30) DEFAULT NULL,
  `estatus` enum('BORRADOR','EMITIDO','PARCIAL','PAGADO','ANULADO','CONVERTIDO') NOT NULL DEFAULT 'BORRADOR',
  `clinica_id` int(10) unsigned NOT NULL,
  `sede_id` int(10) unsigned DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `documento_origen_id` int(10) unsigned DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `condicion_pago` enum('CONTADO','CREDITO') NOT NULL DEFAULT 'CONTADO',
  `dias_credito` smallint(5) unsigned NOT NULL DEFAULT 0,
  `tasa_cambio` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `subtotal_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  `base_exenta_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  `base_imponible_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  `iva_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `iva_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  `base_exenta_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `base_imponible_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `iva_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `saldo_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `codigo_publico` char(32) NOT NULL,
  `asiento_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vdoc_publico` (`codigo_publico`),
  UNIQUE KEY `uk_vdoc_numero` (`tipo`,`numero`),
  KEY `idx_vdoc_clinica` (`clinica_id`),
  KEY `idx_vdoc_estatus` (`estatus`),
  KEY `idx_vdoc_fecha` (`fecha_emision`),
  KEY `idx_vdoc_origen` (`documento_origen_id`),
  KEY `idx_vdoc_usuario` (`usuario_id`),
  CONSTRAINT `fk_vdoc_clinica` FOREIGN KEY (`clinica_id`) REFERENCES `clinicas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_vdoc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ventas_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `documento_id` int(10) unsigned NOT NULL,
  `orden_trabajo_id` int(10) unsigned DEFAULT NULL,
  `servicio_id` int(10) unsigned DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  `descuento_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `aplica_iva` tinyint(1) NOT NULL DEFAULT 1,
  `total_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_vitem_doc` (`documento_id`),
  KEY `idx_vitem_orden` (`orden_trabajo_id`),
  CONSTRAINT `fk_vitem_doc` FOREIGN KEY (`documento_id`) REFERENCES `ventas_documentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_vitem_orden` FOREIGN KEY (`orden_trabajo_id`) REFERENCES `ordenes_trabajo` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_vitem_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 8. COBROS (ingresos de dinero) y su aplicación a las facturas.
--    Un cobro puede pagar varias facturas (ventas_pagos_aplicaciones).
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ventas_pagos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(30) NOT NULL,
  `clinica_id` int(10) unsigned NOT NULL,
  `metodo_pago_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `fecha_pago` date NOT NULL,
  `moneda_codigo` char(3) NOT NULL,
  `monto` decimal(18,2) NOT NULL DEFAULT 0.00,
  `igtf` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tasa_cambio` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `monto_usd` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monto_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `igtf_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `referencia` varchar(100) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `estatus` enum('REGISTRADO','CONCILIADO','ANULADO') NOT NULL DEFAULT 'REGISTRADO',
  `motivo_anulacion` varchar(255) DEFAULT NULL,
  `asiento_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pago_numero` (`numero`),
  KEY `idx_pago_clinica` (`clinica_id`),
  KEY `idx_pago_metodo` (`metodo_pago_id`),
  KEY `idx_pago_fecha` (`fecha_pago`),
  KEY `idx_pago_usuario` (`usuario_id`),
  CONSTRAINT `fk_pago_clinica` FOREIGN KEY (`clinica_id`) REFERENCES `clinicas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_metodo` FOREIGN KEY (`metodo_pago_id`) REFERENCES `metodos_pago` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ventas_pagos_aplicaciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pago_id` int(10) unsigned NOT NULL,
  `documento_id` int(10) unsigned NOT NULL,
  `monto_usd` decimal(15,2) NOT NULL,
  `monto_bs_historico` decimal(18,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_apl_pago` (`pago_id`),
  KEY `idx_apl_doc` (`documento_id`),
  CONSTRAINT `fk_apl_pago` FOREIGN KEY (`pago_id`) REFERENCES `ventas_pagos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_apl_doc` FOREIGN KEY (`documento_id`) REFERENCES `ventas_documentos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 9. GASTOS / COMPRAS (egresos) — contado o a crédito (cuenta por pagar).
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gastos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(30) NOT NULL,
  `fecha` date NOT NULL,
  `proveedor` varchar(150) NOT NULL,
  `proveedor_rif` varchar(20) DEFAULT NULL,
  `nro_factura_proveedor` varchar(40) DEFAULT NULL,
  `concepto` varchar(255) NOT NULL,
  `cuenta_gasto_id` int(10) unsigned NOT NULL,
  `base_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `iva_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_bs` decimal(18,2) NOT NULL DEFAULT 0.00,
  `forma_pago` enum('CONTADO','CREDITO') NOT NULL DEFAULT 'CONTADO',
  `cuenta_pago_id` int(10) unsigned DEFAULT NULL,
  `estatus` enum('PAGADO','PENDIENTE','ANULADO') NOT NULL DEFAULT 'PAGADO',
  `fecha_pago` date DEFAULT NULL,
  `asiento_id` int(10) unsigned DEFAULT NULL,
  `asiento_pago_id` int(10) unsigned DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gasto_numero` (`numero`),
  KEY `idx_gasto_fecha` (`fecha`),
  KEY `idx_gasto_cuenta` (`cuenta_gasto_id`),
  KEY `idx_gasto_usuario` (`usuario_id`),
  CONSTRAINT `fk_gasto_cuenta` FOREIGN KEY (`cuenta_gasto_id`) REFERENCES `plan_cuentas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_gasto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- DATOS INICIALES
-- =========================================================================

-- ---- Correlativos ----
INSERT IGNORE INTO `correlativos` (`clave`,`prefijo`,`ultimo`,`digitos`) VALUES
  ('PREFACTURA','PRE',0,6), ('FACTURA','FAC',0,6), ('NOTA_CREDITO','NC',0,6),
  ('NOTA_ENTREGA','NE',0,6), ('RECIBO','REC',0,6), ('ASIENTO','AS',0,6), ('GASTO','GAS',0,6);

-- ---- Plan de cuentas (estructura sugerida para un laboratorio dental en Venezuela) ----
INSERT IGNORE INTO `plan_cuentas` (`codigo`,`nombre`,`tipo`,`naturaleza`,`permite_movimiento`) VALUES
  ('1','ACTIVO','ACTIVO','DEUDORA',0),
  ('1.1','ACTIVO CORRIENTE','ACTIVO','DEUDORA',0),
  ('1.1.01','EFECTIVO Y EQUIVALENTES','ACTIVO','DEUDORA',0),
  ('1.1.01.001','Caja Principal Bs','ACTIVO','DEUDORA',1),
  ('1.1.01.002','Caja Divisas (USD en efectivo)','ACTIVO','DEUDORA',1),
  ('1.1.01.003','Banco Nacional Bs','ACTIVO','DEUDORA',1),
  ('1.1.01.004','Banco Divisas / Zelle','ACTIVO','DEUDORA',1),
  ('1.1.01.005','Punto de Venta por Liquidar','ACTIVO','DEUDORA',1),
  ('1.1.02','CUENTAS POR COBRAR','ACTIVO','DEUDORA',0),
  ('1.1.02.001','Clientes - Clínicas y Odontólogos','ACTIVO','DEUDORA',1),
  ('1.1.02.002','Anticipos a Proveedores','ACTIVO','DEUDORA',1),
  ('1.1.02.003','Cuentas por Cobrar a Empleados','ACTIVO','DEUDORA',1),
  ('1.1.03','IMPUESTOS A FAVOR','ACTIVO','DEUDORA',0),
  ('1.1.03.001','IVA Crédito Fiscal','ACTIVO','DEUDORA',1),
  ('1.1.03.002','Retenciones de IVA / ISLR por Recuperar','ACTIVO','DEUDORA',1),
  ('1.1.04','INVENTARIOS','ACTIVO','DEUDORA',0),
  ('1.1.04.001','Materiales e Insumos Dentales','ACTIVO','DEUDORA',1),
  ('1.2','ACTIVO NO CORRIENTE','ACTIVO','DEUDORA',0),
  ('1.2.01','PROPIEDAD, PLANTA Y EQUIPO','ACTIVO','DEUDORA',0),
  ('1.2.01.001','Maquinaria y Equipos de Laboratorio','ACTIVO','DEUDORA',1),
  ('1.2.01.002','Mobiliario y Equipos de Oficina','ACTIVO','DEUDORA',1),
  ('1.2.01.003','Equipos de Computación','ACTIVO','DEUDORA',1),
  ('1.2.01.099','Depreciación Acumulada','ACTIVO','ACREEDORA',1),
  ('2','PASIVO','PASIVO','ACREEDORA',0),
  ('2.1','PASIVO CORRIENTE','PASIVO','ACREEDORA',0),
  ('2.1.01','CUENTAS POR PAGAR','PASIVO','ACREEDORA',0),
  ('2.1.01.001','Proveedores por Pagar','PASIVO','ACREEDORA',1),
  ('2.1.01.002','Otras Cuentas por Pagar','PASIVO','ACREEDORA',1),
  ('2.1.02','IMPUESTOS POR PAGAR','PASIVO','ACREEDORA',0),
  ('2.1.02.001','IVA Débito Fiscal','PASIVO','ACREEDORA',1),
  ('2.1.02.002','IGTF por Pagar','PASIVO','ACREEDORA',1),
  ('2.1.02.003','Retenciones por Pagar','PASIVO','ACREEDORA',1),
  ('2.1.03','OBLIGACIONES LABORALES','PASIVO','ACREEDORA',0),
  ('2.1.03.001','Sueldos y Salarios por Pagar','PASIVO','ACREEDORA',1),
  ('2.1.03.002','Prestaciones Sociales por Pagar','PASIVO','ACREEDORA',1),
  ('2.1.03.003','Aportes de Ley por Pagar (IVSS, FAOV, INCES)','PASIVO','ACREEDORA',1),
  ('2.1.04','ANTICIPOS RECIBIDOS','PASIVO','ACREEDORA',0),
  ('2.1.04.001','Anticipos de Clientes','PASIVO','ACREEDORA',1),
  ('3','PATRIMONIO','PATRIMONIO','ACREEDORA',0),
  ('3.1','CAPITAL Y RESULTADOS','PATRIMONIO','ACREEDORA',0),
  ('3.1.01','CAPITAL','PATRIMONIO','ACREEDORA',0),
  ('3.1.01.001','Capital Social','PATRIMONIO','ACREEDORA',1),
  ('3.1.02','RESULTADOS ACUMULADOS','PATRIMONIO','ACREEDORA',0),
  ('3.1.02.001','Resultados de Ejercicios Anteriores','PATRIMONIO','ACREEDORA',1),
  ('3.1.03','RESULTADO DEL EJERCICIO','PATRIMONIO','ACREEDORA',0),
  ('3.1.03.001','Resultado del Ejercicio','PATRIMONIO','ACREEDORA',1),
  ('4','INGRESOS','INGRESO','ACREEDORA',0),
  ('4.1','INGRESOS OPERATIVOS','INGRESO','ACREEDORA',0),
  ('4.1.01','VENTAS DE SERVICIOS','INGRESO','ACREEDORA',0),
  ('4.1.01.001','Ingresos por Servicios de Laboratorio Dental','INGRESO','ACREEDORA',1),
  ('4.1.01.002','Devoluciones y Descuentos en Ventas','INGRESO','DEUDORA',1),
  ('4.1.02','OTROS INGRESOS','INGRESO','ACREEDORA',0),
  ('4.1.02.001','Ganancia por Diferencial Cambiario','INGRESO','ACREEDORA',1),
  ('4.1.02.002','Otros Ingresos','INGRESO','ACREEDORA',1),
  ('5','COSTOS','COSTO','DEUDORA',0),
  ('5.1','COSTO DE PRODUCCIÓN','COSTO','DEUDORA',0),
  ('5.1.01','COSTOS DIRECTOS','COSTO','DEUDORA',0),
  ('5.1.01.001','Materiales Directos','COSTO','DEUDORA',1),
  ('5.1.01.002','Mano de Obra Directa','COSTO','DEUDORA',1),
  ('5.1.01.003','Costos Indirectos de Fabricación','COSTO','DEUDORA',1),
  ('6','GASTOS','GASTO','DEUDORA',0),
  ('6.1','GASTOS OPERATIVOS','GASTO','DEUDORA',0),
  ('6.1.01','GASTOS DE ADMINISTRACIÓN Y VENTAS','GASTO','DEUDORA',0),
  ('6.1.01.001','Sueldos y Salarios','GASTO','DEUDORA',1),
  ('6.1.01.002','Alquileres','GASTO','DEUDORA',1),
  ('6.1.01.003','Servicios Públicos (Luz, Agua, Internet, Teléfono)','GASTO','DEUDORA',1),
  ('6.1.01.004','Mantenimiento y Reparaciones','GASTO','DEUDORA',1),
  ('6.1.01.005','Fletes y Transporte (Despachos)','GASTO','DEUDORA',1),
  ('6.1.01.006','Papelería y Útiles de Oficina','GASTO','DEUDORA',1),
  ('6.1.01.007','Publicidad y Mercadeo','GASTO','DEUDORA',1),
  ('6.1.01.008','Depreciación del Ejercicio','GASTO','DEUDORA',1),
  ('6.1.01.009','Gastos Varios','GASTO','DEUDORA',1),
  ('6.1.02','GASTOS FINANCIEROS','GASTO','DEUDORA',0),
  ('6.1.02.001','Pérdida por Diferencial Cambiario','GASTO','DEUDORA',1),
  ('6.1.02.002','Comisiones y Gastos Bancarios','GASTO','DEUDORA',1),
  ('6.1.03','IMPUESTOS Y TASAS','GASTO','DEUDORA',0),
  ('6.1.03.001','Impuestos Municipales y Tasas','GASTO','DEUDORA',1),
  ('6.1.03.002','Impuesto Sobre la Renta (ISLR)','GASTO','DEUDORA',1);

-- Nivel = cantidad de puntos del código + 1  (1, 1.1, 1.1.01, 1.1.01.001)
UPDATE `plan_cuentas`
   SET `nivel` = CHAR_LENGTH(`codigo`) - CHAR_LENGTH(REPLACE(`codigo`,'.','')) + 1;

-- Padre = el mismo código sin el último segmento (1.1.01.001 -> 1.1.01)
UPDATE `plan_cuentas` h
  INNER JOIN `plan_cuentas` p
          ON p.`codigo` = SUBSTRING_INDEX(h.`codigo`,'.', CHAR_LENGTH(h.`codigo`) - CHAR_LENGTH(REPLACE(h.`codigo`,'.','')))
   SET h.`padre_id` = p.`id`
 WHERE h.`padre_id` IS NULL AND h.`codigo` LIKE '%.%';

-- Cuentas que el sistema usa automáticamente: no se pueden borrar
UPDATE `plan_cuentas` SET `es_sistema` = 1
 WHERE `codigo` IN ('1.1.02.001','1.1.03.001','2.1.01.001','2.1.02.001','2.1.02.002','2.1.04.001',
                    '3.1.02.001','3.1.03.001','4.1.01.001','4.1.01.002','4.1.02.001','6.1.02.001');

-- ---- Métodos de pago iniciales (cada uno apunta a su cuenta de caja/banco) ----
INSERT IGNORE INTO `metodos_pago` (`nombre`,`tipo`,`moneda_codigo`,`cuenta_contable_id`,`aplica_igtf`,`requiere_referencia`)
  SELECT 'Efectivo Bolívares','EFECTIVO','VES',`id`,0,0 FROM `plan_cuentas` WHERE `codigo`='1.1.01.001';
INSERT IGNORE INTO `metodos_pago` (`nombre`,`tipo`,`moneda_codigo`,`cuenta_contable_id`,`aplica_igtf`,`requiere_referencia`)
  SELECT 'Efectivo Dólares','EFECTIVO','USD',`id`,1,0 FROM `plan_cuentas` WHERE `codigo`='1.1.01.002';
INSERT IGNORE INTO `metodos_pago` (`nombre`,`tipo`,`moneda_codigo`,`cuenta_contable_id`,`aplica_igtf`,`requiere_referencia`)
  SELECT 'Zelle','ZELLE','USD',`id`,1,1 FROM `plan_cuentas` WHERE `codigo`='1.1.01.004';
INSERT IGNORE INTO `metodos_pago` (`nombre`,`tipo`,`moneda_codigo`,`cuenta_contable_id`,`aplica_igtf`,`requiere_referencia`)
  SELECT 'Transferencia Bolívares','TRANSFERENCIA','VES',`id`,0,1 FROM `plan_cuentas` WHERE `codigo`='1.1.01.003';
INSERT IGNORE INTO `metodos_pago` (`nombre`,`tipo`,`moneda_codigo`,`cuenta_contable_id`,`aplica_igtf`,`requiere_referencia`)
  SELECT 'Pago Móvil','PAGO_MOVIL','VES',`id`,0,1 FROM `plan_cuentas` WHERE `codigo`='1.1.01.003';
INSERT IGNORE INTO `metodos_pago` (`nombre`,`tipo`,`moneda_codigo`,`cuenta_contable_id`,`aplica_igtf`,`requiere_referencia`)
  SELECT 'Punto de Venta','PUNTO_VENTA','VES',`id`,0,1 FROM `plan_cuentas` WHERE `codigo`='1.1.01.005';

-- ---- Configuración inicial ----
INSERT IGNORE INTO `configuracion` (`clave`,`valor`,`descripcion`) VALUES
  ('empresa_razon_social','CNSB LAB','Nombre o razón social que aparece en facturas y mensajes'),
  ('empresa_rif','','RIF de la empresa (ej: J-12345678-9)'),
  ('empresa_direccion','','Dirección fiscal de la empresa'),
  ('empresa_telefono','','Teléfono de contacto'),
  ('empresa_email','','Correo de contacto'),
  ('iva_porcentaje','16','Alícuota general del IVA (%)'),
  ('igtf_porcentaje','3','Alícuota del IGTF (%) sobre pagos en divisas'),
  ('dias_credito_default','15','Días de crédito por defecto en ventas a crédito'),
  ('codigo_pais_telefono','58','Código de país para WhatsApp (58 = Venezuela)'),
  ('leyenda_documento','Documento administrativo generado por CNSB LAB.','Texto al pie de los documentos impresos'),
  ('cta_cxc','1.1.02.001','Cuenta contable: Cuentas por cobrar clientes'),
  ('cta_ingresos','4.1.01.001','Cuenta contable: Ingresos por servicios'),
  ('cta_devoluciones','4.1.01.002','Cuenta contable: Devoluciones y descuentos en ventas'),
  ('cta_iva_debito','2.1.02.001','Cuenta contable: IVA débito fiscal'),
  ('cta_iva_credito','1.1.03.001','Cuenta contable: IVA crédito fiscal'),
  ('cta_igtf','2.1.02.002','Cuenta contable: IGTF por pagar'),
  ('cta_dif_ganancia','4.1.02.001','Cuenta contable: Ganancia por diferencial cambiario'),
  ('cta_dif_perdida','6.1.02.001','Cuenta contable: Pérdida por diferencial cambiario'),
  ('cta_cxp','2.1.01.001','Cuenta contable: Proveedores por pagar'),
  ('cta_resultado_ejercicio','3.1.03.001','Cuenta contable: Resultado del ejercicio'),
  ('cta_resultados_acumulados','3.1.02.001','Cuenta contable: Resultados acumulados');

INSERT IGNORE INTO `configuracion` (`clave`,`valor`,`descripcion`) VALUES
  ('whatsapp_plantilla_documento',
   CONCAT('Hola *{cliente}*, le saludamos de *{empresa}*.', CHAR(10), CHAR(10),
          'Le enviamos su {tipo_documento} *{documento}* con fecha {fecha}.', CHAR(10),
          'Total: *{total_usd}* ({total_bs}).', CHAR(10), CHAR(10),
          'Puede verla, imprimirla o guardarla en PDF aquí:', CHAR(10), '{enlace}', CHAR(10), CHAR(10),
          'Gracias por preferirnos.'),
   'Mensaje de WhatsApp al enviar un documento. Variables: {cliente} {empresa} {tipo_documento} {documento} {fecha} {total_usd} {total_bs} {enlace}'),
  ('whatsapp_plantilla_cobranza',
   CONCAT('Estimado(a) *{cliente}*, le escribimos de la administración de *{empresa}* para recordarle que mantiene ',
          'un saldo pendiente de *{saldo_usd}* ({saldo_bs}).', CHAR(10), CHAR(10),
          'Detalle: {detalle}', CHAR(10), CHAR(10), 'Agradecemos su pronto pago. Gracias.'),
   'Mensaje de WhatsApp para recordatorio de cobranza. Variables: {cliente} {empresa} {saldo_usd} {saldo_bs} {detalle}');

-- Fin de la migración 01
