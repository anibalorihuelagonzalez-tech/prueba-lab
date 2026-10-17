<?php
/**
 * =========================================================================
 * app/controllers/metodos-pago-controller.php  —  MÉTODOS DE PAGO
 * =========================================================================
 * CRUD de los métodos con los que las clínicas pagan (efectivo, Zelle, pago móvil...).
 * Cada método apunta a la CUENTA CONTABLE (caja/banco) donde entra el dinero, así
 * cada cobro genera su asiento automáticamente en la cuenta correcta.
 *
 * Acciones: listar | cuentas_disponibles | guardar | alternar_estado | eliminar
 */
require_once __DIR__ . '/../helpers/api-guard.php'; // 🔒 portero: sesión + CSRF + roles
apiGuard(['ADMINISTRADOR']);
require_once __DIR__ . '/../helpers/finanzas.php';
header('Content-Type: application/json; charset=utf-8');

$in     = entrada();
$accion = (string)($in['action'] ?? 'listar');
$db     = conexion();

try {
    switch ($accion) {

        case 'listar': {
            $rows = $db->query("SELECT m.id, m.nombre, m.tipo, m.moneda_codigo, m.cuenta_contable_id, m.aplica_igtf,
                                       m.requiere_referencia, m.is_active, c.codigo AS cuenta_codigo, c.nombre AS cuenta_nombre,
                                       (SELECT COUNT(*) FROM ventas_pagos p WHERE p.metodo_pago_id = m.id) AS usos
                                FROM metodos_pago m INNER JOIN plan_cuentas c ON c.id = m.cuenta_contable_id
                                ORDER BY m.is_active DESC, m.moneda_codigo, m.nombre")->fetchAll(PDO::FETCH_ASSOC);
            responderJson(['status' => 'success', 'data' => $rows]);
        }

        // Cuentas de efectivo y bancos (1.1.01.xxx) donde puede entrar el dinero
        case 'cuentas_disponibles': {
            $rows = $db->query("SELECT id, codigo, nombre FROM plan_cuentas
                                WHERE tipo = 'ACTIVO' AND permite_movimiento = 1 AND is_active = 1 AND codigo LIKE '1.1.01.%'
                                ORDER BY codigo")->fetchAll(PDO::FETCH_ASSOC);
            responderJson(['status' => 'success', 'data' => $rows]);
        }

        case 'guardar': {
            $id     = (int)($in['id'] ?? 0);
            $nombre = trim((string)($in['nombre'] ?? ''));
            $tipo   = strtoupper(trim((string)($in['tipo'] ?? 'OTRO')));
            $moneda = strtoupper(trim((string)($in['moneda_codigo'] ?? 'VES')));
            $cuenta = (int)($in['cuenta_contable_id'] ?? 0);
            if ($nombre === '' || mb_strlen($nombre) > 80) {
                throw new NegocioException('El nombre es obligatorio (máximo 80 caracteres).');
            }
            if (!in_array($tipo, ['EFECTIVO', 'TRANSFERENCIA', 'PAGO_MOVIL', 'ZELLE', 'PUNTO_VENTA', 'CHEQUE', 'OTRO'], true)) {
                throw new NegocioException('Tipo de método no válido.');
            }
            if (!in_array($moneda, ['USD', 'VES'], true)) {
                throw new NegocioException('La moneda debe ser USD o VES.');
            }
            $st = $db->prepare("SELECT id FROM plan_cuentas WHERE id = ? AND permite_movimiento = 1 AND is_active = 1");
            $st->execute([$cuenta]);
            if (!$st->fetchColumn()) {
                throw new NegocioException('Selecciona una cuenta contable de caja/banco válida.');
            }
            $igtf = !empty($in['aplica_igtf']) && $moneda === 'USD' ? 1 : 0;  // el IGTF solo aplica a divisas
            $ref  = !empty($in['requiere_referencia']) ? 1 : 0;

            // Nombre único
            $st = $db->prepare("SELECT id FROM metodos_pago WHERE nombre = ? AND id <> ?");
            $st->execute([$nombre, $id]);
            if ($st->fetchColumn()) {
                throw new NegocioException('Ya existe un método de pago con ese nombre.');
            }
            if ($id > 0) {
                $db->prepare("UPDATE metodos_pago SET nombre=?, tipo=?, moneda_codigo=?, cuenta_contable_id=?, aplica_igtf=?, requiere_referencia=? WHERE id=?")
                   ->execute([$nombre, $tipo, $moneda, $cuenta, $igtf, $ref, $id]);
            } else {
                $db->prepare("INSERT INTO metodos_pago (nombre, tipo, moneda_codigo, cuenta_contable_id, aplica_igtf, requiere_referencia) VALUES (?,?,?,?,?,?)")
                   ->execute([$nombre, $tipo, $moneda, $cuenta, $igtf, $ref]);
            }
            responderJson(['status' => 'success', 'message' => 'Método de pago guardado.']);
        }

        case 'alternar_estado': {
            $db->prepare("UPDATE metodos_pago SET is_active = 1 - is_active WHERE id = ?")->execute([(int)($in['id'] ?? 0)]);
            responderJson(['status' => 'success', 'message' => 'Estado actualizado.']);
        }

        case 'eliminar': {
            $id = (int)($in['id'] ?? 0);
            $st = $db->prepare("SELECT COUNT(*) FROM ventas_pagos WHERE metodo_pago_id = ?");
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                throw new NegocioException('Este método ya tiene cobros registrados. Desactívalo en lugar de eliminarlo.');
            }
            $db->prepare("DELETE FROM metodos_pago WHERE id = ?")->execute([$id]);
            responderJson(['status' => 'success', 'message' => 'Método de pago eliminado.']);
        }

        default:
            responderJson(['status' => 'error', 'message' => 'Acción no permitida.'], 400);
    }
} catch (NegocioException $e) {
    responderJson(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    responderJson(['status' => 'error', 'message' => errorSeguro($e)], 500);
}
