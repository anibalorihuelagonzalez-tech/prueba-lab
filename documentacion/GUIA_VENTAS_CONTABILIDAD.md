# CNSB LAB — Guía de los módulos de Ventas y Contabilidad

## 1. Cómo ponerlo a funcionar (3 pasos)

1. **Base de datos.** En phpMyAdmin selecciona tu base `cnsb_lab` → pestaña **Importar** →
   archivo `database/01_ventas_contabilidad.sql`.
   *Es seguro con datos existentes (no borra nada) y se puede ejecutar varias veces.*
   ⚠ **No importes `cnsb_lab.sql` completo sobre tu base actual**: ese archivo empieza con `DROP TABLE`
   y borraría tus datos. Solo sirve para instalaciones nuevas (ya incluye todo lo nuevo).
2. **(Recomendado) Integridad de datos.** Importa `database/02_endurecer_fks_OPCIONAL.sql` **una sola vez**
   (cambia borrados en cascada peligrosos por restricciones; ver sección 5).
3. **`.env`.** Se agregaron `APP_URL`, `APP_TIMEZONE` y `APP_DEBUG`. Cuando el sistema esté en un servidor con
   dominio, pon `APP_URL=https://tu-dominio.com` (así el enlace de la factura que viaja por WhatsApp
   funciona en el teléfono del cliente). En tu PC (`localhost`) el enlace solo abre en tu computadora.

Luego entra al sistema y revisa **Contabilidad → Configuración**: RIF, dirección, teléfono de la empresa,
IVA (16 %), IGTF (3 %) y cuentas automáticas. *Verifica las alícuotas vigentes con tu contador.*

## 2. Flujo de ventas

| Paso | Dónde | Qué pasa |
|------|-------|----------|
| 1 | Ventas → Prefacturas / Notas de entrega | Documentos previos, **sin efecto contable**. Pueden convertirse en factura. |
| 2 | Ventas → Facturas → *Nueva* | Eliges clínica, **casos del laboratorio sin facturar** (precio del servicio), servicios o líneas libres. Tasa del día automática. Contado o crédito. |
| 3 | **Emitir** | Asigna número `FAC-000001`, deja el saldo por cobrar y **genera el asiento**: Debe CxC / Haber Ingresos + IVA débito. Un caso no puede facturarse dos veces. |
| 4 | Botón **WhatsApp** | Abre un cuadro con teléfono de la clínica y mensaje listo (editable). Botones: *Abrir WhatsApp*, *WhatsApp Web*, copiar enlace/mensaje. **Sin API ni token**: tú pulsas enviar. |
| 5 | Ventas → Cobranzas → *Registrar cobro* | Método de pago, tasa, referencia. Se aplica a las facturas más antiguas (o a las que elijas). Genera el asiento con IGTF y diferencial cambiario. |
| 6 | Notas de crédito | Desde una factura vigente; no pueden superar su saldo. Reducen la deuda y reversan IVA. |

**Factura digital:** el enlace de WhatsApp abre `/factura-digital/<código>`: página limpia con los datos de la
empresa, ítems, totales en $ y Bs, pagos y saldo. Botón **Guardar como PDF / Imprimir**. El código son 32
caracteres aleatorios (no es un token de WhatsApp). Los borradores no son visibles por ese enlace.

## 3. Contabilidad

* **Plan de cuentas** jerárquico ya cargado (activo, pasivo, patrimonio, ingresos, costos, gastos).
* **Asientos** automáticos (ventas, notas de crédito, cobros, gastos) y **manuales** (ajustes/apertura).
  Partida doble obligatoria. Un asiento no se edita: se **anula** (genera asiento inverso).
* **Períodos**: al cerrar un mes no se aceptan movimientos con fecha en él. **Cierre de ejercicio** anual.
* **Libros**: Diario, Mayor, Balance de Comprobación y **Libro de Ventas** (base e IVA por factura).
* **Estados**: Estado de Resultados y Balance General (con verificación de que cuadra).
* **Gastos y compras**: contado o crédito (cuentas por pagar), con IVA crédito.
* Moneda contable: **bolívares**. Los documentos guardan la tasa del día; al cobrar con otra tasa la
  diferencia va a *Ganancia/Pérdida por diferencial cambiario*.

### Asientos que genera el sistema
```
Factura        Debe  CxC clientes (total Bs)
               Haber Ingresos por servicios (base Bs)    Haber IVA débito (IVA Bs)
Nota crédito   Debe  Devoluciones/descuentos (base)      Debe IVA débito
               Haber CxC clientes
Cobro          Debe  Caja/Banco del método (monto + IGTF en Bs)
               Haber CxC (a la tasa de la factura)       Haber IGTF por pagar
               Haber/Debe Diferencial cambiario (si cambió la tasa)
Gasto contado  Debe  Cuenta de gasto + IVA crédito       Haber Caja/Banco
Gasto crédito  Debe  Gasto + IVA crédito                 Haber Proveedores por pagar
```

## 4. Correcciones realizadas al proyecto original

**Seguridad**
* Todos los controladores ahora exigen sesión (`apiGuard`) y rol; antes ~20 eran públicos.
* Protección CSRF por verificación de origen; cookie de sesión HttpOnly/SameSite; nuevo ID de sesión al entrar.
* Las vistas ahora validan el rol (la matriz `Auth::$permisosVistas` existía pero no se usaba).
* **`.htaccess`**: ya no se pueden descargar `/.env`, `/cnsb_lab.sql`, `/app/...`, `/composer.json`.
* `/clave` (mostraba un hash público) eliminado → `php tools/generar-clave.php "clave"` por consola.
* Los errores técnicos de la base de datos ya no se muestran al usuario (se guardan en el log).
* Login: ahora **rechaza usuarios dados de baja**, limita 5 intentos fallidos / 10 min.
* `public/uploads` no ejecuta código PHP. `.gitignore` y `.env.example` corregidos.
* ⚠ Tu `.env` viaja dentro del zip: **cambia la contraseña de la base de datos** si ese archivo se compartió.

**Errores funcionales**
* `eliminar-clinica` usaba `$pdo` sin definirlo (error fatal) y borraba en cascada todo; ahora protege y valida.
* `eliminar-despacho` apuntaba a un archivo inexistente (`config/db.php`) → corregido y ruta arreglada.
* `eliminar-paciente-controller` no existía → creado (no borra pacientes con casos).
* Nombres de tablas en MAYÚSCULAS en `documentos-controller` (fallaban en Linux) → minúsculas.
* El "cobro rápido" del dashboard marcaba facturas como pagadas **sin dinero ni asiento** → ahora lleva a Cobranzas.
* Dashboard financiero reescrito sobre las nuevas tablas; archivos duplicados/huérfanos eliminados.
* Las facturas ya no se emiten desde "Administración → Documentos" (ese módulo queda para documentos no fiscales).

## 5. Pendiente / límites que debes conocer
* **Nada de esto se pudo ejecutar** en el entorno donde se escribió (no había PHP ni MySQL): se revisó la
  sintaxis y la lógica, pero **prueba primero en una copia** y reporta cualquier error.
* Los documentos son **administrativos**: la factura fiscal con número de control SENIAT debe emitirse con tu
  imprenta digital/máquina fiscal (hay un campo "N° de control" para registrarlo).
* No se manejan anticipos de clientes ni devoluciones de dinero (una nota de crédito no puede superar el saldo).
* Retenciones de IVA/ISLR y nómina no están incluidas (las cuentas existen en el plan).
* El archivo `02_endurecer_fks_OPCIONAL.sql` usa los nombres de restricción del `cnsb_lab.sql` original.
