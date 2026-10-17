# Propuesta de Tablas Adicionales: Base de Datos CNSB_LAB

A continuación, te detallo las tablas nuevas recomendadas para complementar tu diseño actual y cubrir todos los requerimientos funcionales, listos para que los modeles en tu EER o los pases a migraciones de Laravel.

## 1. Módulo de Manufactura y Trazabilidad (Control de Calidad)

Tu tabla `HISTORIAL_TRAZABILIDAD` es un excelente punto de partida. Para el nivel de detalle y las animaciones que deseas, necesitamos estandarizar las etapas y manejar los archivos clínicos.

```sql
-- Catálogo de pasos de producción para estandarizar el proceso y facilitar la UI animada.
CREATE TABLE etapas_produccion (
    id INT PRIMARY KEY,
    nombre VARCHAR(100), -- Ej: 'Toma de impresión', 'Vaciado', 'Prueba de Rodete', 'Terminado'
    color_hex VARCHAR(7), -- Color para la barra de progreso en la interfaz
    icono VARCHAR(50), -- Nombre de icono (ej: 'fa-tooth')
    orden_sugerido INT
);

-- Define qué etapas requiere un servicio particular, porque una corona no requiere lo mismo que una prótesis total.
CREATE TABLE servicio_etapas (
    id INT PRIMARY KEY,
    servicio_id INT, -- FK a SERVICIOS
    etapa_id INT, -- FK a ETAPAS_PRODUCCION
    orden_ejecucion INT
);

-- Archivos Clínicos: Fundamental para los laboratorios modernos (escaneos 3D, fotos).
CREATE TABLE orden_archivos (
    id INT PRIMARY KEY,
    orden_trabajo_id INT, -- FK a ORDENES_TRABAJO
    usuario_subio_id INT, -- FK a USUARIOS
    tipo_archivo VARCHAR(50), -- Ej: 'STL', 'JPG', 'PDF'
    ruta_archivo VARCHAR(255),
    observaciones TEXT,
    fecha_subida DATETIME
);

-- Control de Calidad: Para rastrear devoluciones y problemas de manufactura.
CREATE TABLE incidencias (
    id INT PRIMARY KEY,
    orden_trabajo_id INT, -- FK a ORDENES_TRABAJO
    usuario_reporta_id INT, -- FK a USUARIOS
    motivo VARCHAR(150),
    descripcion TEXT,
    estatus VARCHAR(50), -- 'Abierta', 'En Revisión', 'Resuelta'
    fecha_reporte DATETIME,
    fecha_resolucion DATETIME NULL
);
```

## 2. Módulo de Envíos (Logística Tipo DHL)

Ya tienes `ENVIOS` (caja principal) y `ORDENES_TRABAJO` (caja pequeña por paciente), lo cual es arquitectónicamente muy inteligente. Para poder reprogramar y rastrear cada movimiento:

```sql
-- Agregar campos a tu tabla ENVIOS actual:
-- ALTER TABLE envios ADD COLUMN fecha_hora_programada DATETIME;
-- ALTER TABLE envios ADD COLUMN repartidor_id INT NULL;
-- ALTER TABLE envios ADD COLUMN notas_cancelacion TEXT NULL;

-- Rastreo paso a paso de la caja principal (El "Tracking" de DHL)
CREATE TABLE historial_envios (
    id INT PRIMARY KEY,
    envio_id INT, -- FK a ENVIOS
    usuario_registro_id INT, -- FK a USUARIOS
    estatus VARCHAR(50), -- 'Creado', 'Recolectado', 'En Tránsito', 'Entregado', 'Reprogramado', 'Cancelado'
    ubicacion_actual VARCHAR(255),
    notas TEXT,
    fecha_hora_registro DATETIME
);

-- Personal de mensajería (puede ser propio o externo)
CREATE TABLE repartidores (
    id INT PRIMARY KEY,
    nombres VARCHAR(100),
    apellidos VARCHAR(100),
    telefono VARCHAR(20),
    placa_vehiculo VARCHAR(20)
);
```

## 3. Módulo de Administración (Control Monetario)

Tienes bien estructurados los `DOCUMENTOS` (Pre-facturas, Notas de Entrega). Lo que falta es el registro del **dinero que ingresa** pagando esos documentos.

```sql
-- Catálogo de métodos por los que reciben dinero
CREATE TABLE metodos_pago (
    id INT PRIMARY KEY,
    nombre VARCHAR(50), -- Ej: 'Zelle', 'Pago Móvil', 'Efectivo USD', 'Transferencia Bs'
    moneda VARCHAR(5) -- 'USD', 'VES'
);

-- Registro de ingresos monetarios
CREATE TABLE pagos (
    id INT PRIMARY KEY,
    documento_id INT, -- FK a DOCUMENTOS (A qué factura abona este pago)
    metodo_pago_id INT, -- FK a METODOS_PAGO
    usuario_registro_id INT, -- FK a USUARIOS (Quién registró el pago)
    monto_pagado DECIMAL(15,2),
    tasa_cambio_aplicada DECIMAL(15,4),
    referencia VARCHAR(100), -- Referencia bancaria o de Zelle
    fecha_pago DATETIME,
    estatus VARCHAR(50) -- 'Por verificar', 'Conciliado', 'Rechazado'
);
```

## 4. Módulo de Recursos Humanos (Adaptado a Legislación Venezolana - LOTTT)

Separar empleados de usuarios es clave. Un mensajero o el personal de limpieza debe estar en nómina pero no necesita acceso al sistema web. Además, en Venezuela la nómina requiere mucha especificidad.

```sql
-- Catálogo de posiciones
CREATE TABLE cargos (
    id INT PRIMARY KEY,
    nombre VARCHAR(100),
    departamento VARCHAR(100) -- Ej: 'Cerámica', 'Yeso', 'Administración'
);

-- Empleados (Independiente de si tienen acceso al sistema o no)
CREATE TABLE empleados (
    id INT PRIMARY KEY,
    usuario_id INT NULL, -- Solo si el empleado tiene acceso a la App
    cargo_id INT, -- FK a CARGOS
    cedula VARCHAR(20) UNIQUE,
    nombres VARCHAR(100),
    apellidos VARCHAR(100),
    fecha_nacimiento DATE,
    fecha_ingreso DATE,
    salario_base_bs DECIMAL(15,2),
    bonificacion_divisas DECIMAL(15,2), -- Muy común en la realidad actual
    estatus VARCHAR(50) -- 'Activo', 'Reposo', 'Vacaciones', 'Liquidado'
);

-- Control de entrada y salida
CREATE TABLE asistencia_marcajes (
    id INT PRIMARY KEY,
    empleado_id INT, -- FK a EMPLEADOS
    fecha DATE,
    hora_entrada TIME,
    hora_salida TIME,
    horas_extras DECIMAL(5,2) DEFAULT 0
);

-- Catálogo de los elementos que suman o restan en la nómina venezolana
CREATE TABLE nomina_conceptos (
    id INT PRIMARY KEY,
    nombre VARCHAR(100), -- 'Cestaticket', 'IVSS', 'FAOV', 'INCES', 'Bono Guerra', 'Deducción Préstamo'
    tipo ENUM('Asignacion', 'Deduccion'),
    es_fijo BOOLEAN -- Indica si se cobra todas las quincenas automáticamente
);

-- El recibo de pago global de la quincena/mes
CREATE TABLE nominas (
    id INT PRIMARY KEY,
    empleado_id INT, -- FK a EMPLEADOS
    fecha_inicio DATE,
    fecha_fin DATE,
    total_asignaciones DECIMAL(15,2),
    total_deducciones DECIMAL(15,2),
    neto_pagar DECIMAL(15,2),
    estatus VARCHAR(50) -- 'Borrador', 'Procesada', 'Pagada'
);

-- El desglose de por qué el empleado cobró esa cantidad
CREATE TABLE nomina_detalles (
    id INT PRIMARY KEY,
    nomina_id INT, -- FK a NOMINAS
    concepto_id INT, -- FK a NOMINA_CONCEPTOS
    monto DECIMAL(15,2)
);
```
