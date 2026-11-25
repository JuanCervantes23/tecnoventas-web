<?php
require_once "../config/conexion.php";
require_once "../includes/verificar_acceso.php";
requireRol(['admin']);

$baseUrl = "..";
$tituloPagina = "Panel Administrativo | TecnoVentas";
require_once "../includes/header.php";

// Indicadores rápidos desde la BD
$totalProductos = (int)$pdo->query("SELECT COUNT(*) FROM tblproductos WHERE Estado = 1")->fetchColumn();
$bajoStock      = (int)$pdo->query("SELECT COUNT(*) FROM tblproductos WHERE Estado = 1 AND intStock <= 5")->fetchColumn();
$totalClientes  = (int)$pdo->query("SELECT COUNT(*) FROM tblcliente WHERE Estado = 1")->fetchColumn();
$pedidosPend    = (int)$pdo->query("SELECT COUNT(*) FROM tblpedido WHERE vchEstado IN ('Pendiente', 'Pagado', 'En camino')")->fetchColumn();
?>

<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <span class="badge bg-dark text-white rounded-pill px-3 py-2 mb-1">Panel de Control</span>
            <h1 class="h3 fw-bold mb-0">Administración TecnoVentas</h1>
        </div>
        <a href="productos.php" class="btn btn-primary rounded-pill px-4 fw-semibold">
            <i class="bi bi-plus-circle me-1"></i> Gestionar Productos
        </a>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="bg-white border rounded-4 p-3 shadow-sm">
                <small class="text-muted d-block">Productos activos</small>
                <span class="h3 fw-bold text-primary"><?= $totalProductos ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="bg-white border rounded-4 p-3 shadow-sm">
                <small class="text-muted d-block">Stock bajo (≤ 5)</small>
                <span class="h3 fw-bold text-danger"><?= $bajoStock ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="bg-white border rounded-4 p-3 shadow-sm">
                <small class="text-muted d-block">Clientes activos</small>
                <span class="h3 fw-bold text-success"><?= $totalClientes ?></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="bg-white border rounded-4 p-3 shadow-sm">
                <small class="text-muted d-block">Pedidos en curso</small>
                <span class="h3 fw-bold text-warning"><?= $pedidosPend ?></span>
            </div>
        </div>
    </div>

    <!-- Accesos directos a los módulos privados del equipo -->
    <div class="row g-3">
        <div class="col-md-4">
            <div class="bg-white border rounded-4 p-4 h-100 shadow-sm">
                <h2 class="h6 fw-bold text-primary"><i class="bi bi-cpu me-1"></i> Catálogo e Inventario</h2>
                <p class="small text-muted">Altas, bajas, edición de precios, descuentos y carga de imágenes.</p>
                <a href="productos.php" class="btn btn-outline-primary btn-sm rounded-pill">Abrir CRUD Productos</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white border rounded-4 p-4 h-100 shadow-sm">
                <h2 class="h6 fw-bold text-success"><i class="bi bi-bag-check me-1"></i> Pedidos y Ventas</h2>
                <p class="small text-muted">Seguimiento de pedidos en línea, cambios de estatus y ventas en tienda.</p>
                <a href="pedidos.php" class="btn btn-outline-success btn-sm rounded-pill">Gestionar Pedidos</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white border rounded-4 p-4 h-100 shadow-sm">
                <h2 class="h6 fw-bold text-dark"><i class="bi bi-people me-1"></i> Usuarios y Contenido</h2>
                <p class="small text-muted">Control de roles del personal, clientes, sucursales y preguntas frecuentes.</p>
                <a href="usuarios.php" class="btn btn-outline-dark btn-sm rounded-pill">Gestionar Usuarios</a>
            </div>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>