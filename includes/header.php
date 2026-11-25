<?php
// Rutas base relativas según desde qué carpeta se incluya
$baseUrl = $baseUrl ?? ".";

// Modo prueba visual de los 4 Nav (solo si no hay sesión real iniciada)
if (isset($_GET['demo_nav']) && in_array($_GET['demo_nav'], ['publico', 'cliente', 'empleado', 'admin'], true)) {
    $_SESSION['demo_nav'] = $_GET['demo_nav'];
}

$tipoNav = $_SESSION['tipo_nav'] ?? ($_SESSION['demo_nav'] ?? 'publico');
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Invitado';

// Consultar categorías activas para el menú de tienda
$categoriasNav = [];
if (isset($pdo)) {
    $stmtCat = $pdo->query("SELECT intid_Categoria, vchNombre FROM tblcategoria WHERE Estado = 1 ORDER BY intid_Categoria ASC");
    $categoriasNav = $stmtCat->fetchAll();
}

// Contador de carrito si es cliente
$itemsBolsa = 0;
if ($tipoNav === 'cliente' && isset($_SESSION['cliente_id'], $pdo)) {
    $stmtCart = $pdo->prepare("SELECT COALESCE(SUM(intCantidad), 0) FROM tblcarrito WHERE id_Cliente = ?");
    $stmtCart->execute([(int)$_SESSION['cliente_id']]);
    $itemsBolsa = (int)$stmtCart->fetchColumn();
}

$navClass = '';
if ($tipoNav === 'admin') $navClass = 'nav-admin';
if ($tipoNav === 'empleado') $navClass = 'nav-empleado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'TecnoVentas | Tienda Tech') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= $baseUrl ?>/assets/css/tecnoventas.css" rel="stylesheet">
</head>
<body>

<!-- Barra superior informativa + selector rápido para revisar los 4 Nav -->
<div class="tv-topbar">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-truck me-1"></i> Envío gratis en Huejutla de Reyes · Garantía oficial de 12 meses</span>
        <div class="d-flex align-items-center gap-2">
            <span class="text-secondary">Vista Nav actual: <strong class="text-white text-uppercase"><?= htmlspecialchars($tipoNav) ?></strong></span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Selector de Nav">
                <a href="?demo_nav=publico" class="btn btn-outline-light py-0 px-2 <?= $tipoNav==='publico'?'active':'' ?>">1. Público</a>
                <a href="?demo_nav=cliente" class="btn btn-outline-light py-0 px-2 <?= $tipoNav==='cliente'?'active':'' ?>">2. Cliente</a>
                <a href="?demo_nav=empleado" class="btn btn-outline-light py-0 px-2 <?= $tipoNav==='empleado'?'active':'' ?>">3. Empleado</a>
                <a href="?demo_nav=admin" class="btn btn-outline-light py-0 px-2 <?= $tipoNav==='admin'?'active':'' ?>">4. Admin</a>
            </div>
        </div>
    </div>
</div>

<!-- Encabezado principal estilo Steren -->
<header class="tv-header py-3">
    <div class="container">
        <div class="row align-items-center g-3">
            <!-- Logo -->
            <div class="col-6 col-lg-3">
                <a href="<?= $baseUrl ?>/index.php" class="tv-brand">
                    <span class="tv-brand-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                    <span>TECNOVENTAS</span>
                </a>
            </div>

            <!-- Buscador central -->
            <div class="col-12 col-lg-5 order-3 order-lg-2">
                <form action="<?= $baseUrl ?>/publico/catalogo.php" method="GET">
                    <div class="input-group tv-search-group">
                        <input type="search" name="buscar" class="form-control" placeholder="Buscar smartphones, laptops, audio, accesorios..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                        <button class="btn" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>

            <!-- Acciones de usuario según el tipo de sesión -->
            <div class="col-6 col-lg-4 order-2 order-lg-3 d-flex justify-content-end align-items-center gap-2">
                <?php if ($tipoNav === 'publico'): ?>
                    <a href="<?= $baseUrl ?>/publico/login.php" class="btn btn-outline-primary rounded-pill px-3 btn-sm fw-semibold">
                        <i class="bi bi-person me-1"></i> Iniciar sesión
                    </a>
                    <a href="<?= $baseUrl ?>/publico/registro.php" class="btn btn-primary rounded-pill px-3 btn-sm fw-semibold">
                        Crear cuenta
                    </a>

                <?php elseif ($tipoNav === 'cliente'): ?>
                    <a href="<?= $baseUrl ?>/publico/perfil.php" class="btn btn-light rounded-pill px-3 btn-sm fw-semibold border">
                        <i class="bi bi-person-circle text-primary me-1"></i> <?= htmlspecialchars($nombreUsuario) ?>
                    </a>
                    <a href="<?= $baseUrl ?>/publico/carrito.php" class="btn btn-primary rounded-pill px-3 btn-sm fw-semibold position-relative">
                        <i class="bi bi-bag me-1"></i> Bolsa
                        <span class="badge bg-danger rounded-pill ms-1"><?= $itemsBolsa ?></span>
                    </a>
                    <a href="<?= $baseUrl ?>/publico/logout.php" class="btn btn-outline-danger rounded-pill px-2 btn-sm" title="Cerrar sesión">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>

                <?php else: /* empleado o admin */ ?>
                    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                        <i class="bi bi-shield-lock-fill text-primary me-1"></i>
                        <?= htmlspecialchars($nombreUsuario) ?> (<?= strtoupper($tipoNav) ?>)
                    </span>
                    <a href="<?= $baseUrl ?>/publico/logout.php" class="btn btn-outline-danger rounded-pill px-3 btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i> Salir
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Barra de navegación dinámica (4 vistas de Nav) -->
<nav class="navbar navbar-expand-lg tv-navbar <?= $navClass ?> py-1">
    <div class="container">
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <i class="bi bi-list fs-4"></i> Menú
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <?php if ($tipoNav === 'publico' || $tipoNav === 'cliente'): ?>
                <!-- NAV 1 (PÚBLICO) Y NAV 2 (CLIENTE) -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $baseUrl ?>/index.php"><i class="bi bi-house-door me-1"></i> Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-grid me-1"></i> Categorías
                        </a>
                        <ul class="dropdown-menu shadow border-0 rounded-3">
                            <li><a class="dropdown-item fw-semibold" href="<?= $baseUrl ?>/publico/catalogo.php">Todo el catálogo</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($categoriasNav as $cat): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= $baseUrl ?>/publico/catalogo.php?categoria=<?= (int)$cat['intid_Categoria'] ?>">
                                        <?= htmlspecialchars($cat['vchNombre']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $baseUrl ?>/publico/catalogo.php?ofertas=1"><i class="bi bi-tag me-1"></i> Ofertas</a>
                    </li>
                    <?php if ($tipoNav === 'cliente'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?>/publico/mis_pedidos.php"><i class="bi bi-box-seam me-1"></i> Mis pedidos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $baseUrl ?>/publico/favoritos.php"><i class="bi bi-heart me-1"></i> Favoritos</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $baseUrl ?>/publico/nosotros.php">Quiénes somos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $baseUrl ?>/publico/sucursales.php">Sucursales y FAQ</a>
                    </li>
                </ul>

            <?php elseif ($tipoNav === 'empleado'): ?>
                <!-- NAV 3: TRABAJADOR / EMPLEADO -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/pedidos.php"><i class="bi bi-truck me-1"></i> Pedidos Online</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/ventas.php"><i class="bi bi-cash-coin me-1"></i> Ventas Sucursal</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/productos.php"><i class="bi bi-box me-1"></i> Consultar Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/clientes.php"><i class="bi bi-people me-1"></i> Clientes</a></li>
                </ul>

            <?php elseif ($tipoNav === 'admin'): ?>
                <!-- NAV 4: ADMINISTRADOR -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Panel</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/productos.php"><i class="bi bi-cpu me-1"></i> Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/categorias_marcas.php"><i class="bi bi-tags me-1"></i> Categorías y Marcas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/pedidos.php"><i class="bi bi-bag-check me-1"></i> Pedidos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/ventas.php"><i class="bi bi-receipt me-1"></i> Ventas y Ganancias</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/compras.php"><i class="bi bi-truck me-1"></i> Proveedores y Compras</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/usuarios.php"><i class="bi bi-person-badge me-1"></i> Usuarios y Roles</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $baseUrl ?>/admin/contenido.php"><i class="bi bi-file-earmark-text me-1"></i> Info / FAQ / Sucursales</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="py-4">