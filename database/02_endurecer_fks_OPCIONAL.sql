-- =========================================================================
-- 02_endurecer_fks_OPCIONAL.sql
-- CORRECCIÓN DE INTEGRIDAD: claves foráneas con ON DELETE CASCADE peligrosas
-- =========================================================================
-- PROBLEMA: en el esquema original, borrar un registro "padre" borraba en cadena
-- información importante. Ejemplos reales:
--   * borrar un ROL        -> borraba TODOS los usuarios de ese rol
--   * borrar un USUARIO    -> borraba su historial de trazabilidad y de envíos
--   * borrar un SERVICIO o una CATEGORÍA -> borraba los casos (órdenes) asociados
--   * borrar un REPARTIDOR o una SEDE    -> borraba los envíos
-- SOLUCIÓN: se cambian a RESTRICT (la base de datos rechaza el borrado si hay datos
-- relacionados). El sistema ya muestra un mensaje claro en esos casos.
--
-- ⚠ Ejecutar UNA sola vez. Requiere MariaDB 10.4+/MySQL 8+ con los nombres de
--   restricción del archivo cnsb_lab.sql original. Si algún nombre no existe, el
--   motor avisará y puedes ignorar esa línea.
-- =========================================================================

ALTER TABLE `usuarios` DROP FOREIGN KEY `usuarios_ibfk_1`,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles_usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `servicios` DROP FOREIGN KEY `fk_servicio_categoria`,
  ADD CONSTRAINT `fk_servicio_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_servicios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `envios` DROP FOREIGN KEY `fk_envio_repartidor`, DROP FOREIGN KEY `fk_envio_sede`,
  ADD CONSTRAINT `fk_envio_repartidor` FOREIGN KEY (`repartidor_id`) REFERENCES `repartidores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_envio_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `historial_envios` DROP FOREIGN KEY `fk_historial_usuario`, DROP FOREIGN KEY `fk_historial_estatus`,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_estatus` FOREIGN KEY (`historial_envios_estatus_id`) REFERENCES `historial_envios_estatus` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `ordenes_trabajo` DROP FOREIGN KEY `fk_orden_clinica`, DROP FOREIGN KEY `fk_orden_paciente`, DROP FOREIGN KEY `fk_orden_servicio`,
  ADD CONSTRAINT `fk_orden_clinica`  FOREIGN KEY (`clinica_id`)  REFERENCES `clinicas`  (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orden_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orden_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `historial_trazabilidad` DROP FOREIGN KEY `fk_trazabilidad_usuario`,
  ADD CONSTRAINT `fk_trazabilidad_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
