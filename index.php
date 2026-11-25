<?php
require_once "config/conexion.php";
$baseUrl = ".";
$tituloPagina = "TecnoVentas | Inicio - Tienda de Tecnología";
require_once "includes/header.php";

// 1. Categorías activas
$stmtCat = $pdo->query("SELECT intid_Categoria, vchNombre FROM tblcategoria WHERE Estado = 1 ORDER BY intid_Categoria ASC");
$categorias = $stmtCat->fetchAll();

// 2. Productos en oferta (intDescuento > 0)
$stmtOfertas = $pdo->query("
    SELECT p.vchNo_Serie, p.vchNombre, p.floPrecioUnitario, p.intDescuento,
           p.intStock, p.vchImagen, p.decCalificacion, m.vchNombre AS marca
    FROM tblproductos p
    INNER JOIN tblmarcas m ON p.intid_Marca = m.intid_Marca
    WHERE p.Estado = 1 AND p.intDescuento > 0
    ORDER BY p.intDescuento DESC
    LIMIT 4
");
$ofertas = $stmtOfertas->fetchAll();

// 3. Productos destacados / lanzamientos
$stmtDest = $pdo->query("
    SELECT p.vchNo_Serie, p.vchNombre, p.floPrecioUnitario, p.intDescuento,
           p.intStock, p.vchImagen, p.decCalificacion, m.vchNombre AS marca, c.vchNombre AS categoria
    FROM tblproductos p
    INNER JOIN tblmarcas m ON p.intid_Marca = m.intid_Marca
    INNER JOIN tblcategoria c ON p.intid_Categoria = c.intid_Categoria
    WHERE p.Estado = 1
    ORDER BY p.decCalificacion DESC, p.vchNombre ASC
    LIMIT 8
");
$destacados = $stmtDest->fetchAll();

// Iconos Bootstrap para cada categoría
$iconosCat = [
    1 => 'bi-phone',
    2 => 'bi-laptop',
    3 => 'bi-tablet',
    4 => 'bi-headphones',
    5 => 'bi-smartwatch',
    6 => 'bi-motherboard',
    7 => 'bi-controller',
    8 => 'bi-usb-plug'
];
?>

<div class="container">
    <!-- Carrusel Principal -->
    <section aria-label="Promociones principales" class="mb-4">
        <div id="heroTecnoVentas" class="carousel slide rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroTecnoVentas" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#heroTecnoVentas" data-bs-slide-to="1"></button>
                <button type="button" data-bs-target="#heroTecnoVentas" data-bs-slide-to="2"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #0F172A 0%, #1D4ED8 100%);">
                        <div class="row align-items-center py-2">
                            <div class="col-lg-7">
                                <span class="badge bg-warning text-dark fw-bold mb-2">TEMPORADA TECH</span>
                                <h1 class="display-6 fw-bold mb-2">Semana Gaming y Cómputo Pro</h1>
                                <p class="lead fs-6 text-white-50 mb-4">Hasta 25% de descuento en laptops, audio Hi-Fi y periféricos seleccionados.</p>
                                <a href="publico/catalogo.php?ofertas=1" class="btn btn-light text-primary fw-bold rounded-pill px-4">
                                    Explorar ofertas <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="col-lg-5 text-center d-none d-lg-block">
                                <i class="bi bi-controller" style="font-size: 7rem; opacity: 0.85;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #1E293B 0%, #0F766E 100%);">
                        <div class="row align-items-center py-2">
                            <div class="col-lg-7">
                                <span class="badge bg-info text-dark fw-bold mb-2">AUDIO & HI-FI</span>
                                <h2 class="display-6 fw-bold mb-2">Sonido inmersivo sin cables</h2>
                                <p class="lead fs-6 text-white-50 mb-4">AirPods Pro 2, Sony WH-1000XM5 y bocinas portátiles con entrega inmediata.</p>
                                <a href="publico/catalogo.php?categoria=4" class="btn btn-light text-dark fw-bold rounded-pill px-4">
                                    Ver Audio & Hi-Fi <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="col-lg-5 text-center d-none d-lg-block">
                                <i class="bi bi-earbuds" style="font-size: 7rem; opacity: 0.85;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #111827 0%, #4338CA 100%);">
                        <div class="row align-items-center py-2">
                            <div class="col-lg-7">
                                <span class="badge bg-light text-primary fw-bold mb-2">SMARTPHONES</span>
                                <h2 class="display-6 fw-bold mb-2">Potencia en la palma de tu mano</h2>
                                <p class="lead fs-6 text-white-50 mb-4">iPhone 15 Pro, Galaxy S24 Ultra y Xiaomi 13 Ultra con garantía de 12 meses.</p>
                                <a href="publico/catalogo.php?categoria=1" class="btn btn-light text-primary fw-bold rounded-pill px-4">
                                    Ver Smartphones <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="col-lg-5 text-center d-none d-lg-block">
                                <i class="bi bi-phone" style="font-size: 7rem; opacity: 0.85;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Franja de beneficios rápidos estilo Steren -->
    <section aria-label="Beneficios de compra" class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="bg-white border rounded-4 p-3 d-flex align-items-center gap-3 h-100">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3 fs-4"><i class="bi bi-truck"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Envío gratis local</h6>
                    <small class="text-muted">Entrega en 24-48 hrs en Huejutla de Reyes</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white border rounded-4 p-3 d-flex align-items-center gap-3 h-100">
                <div class="bg-success bg-opacity-10 text-success rounded-3 p-3 fs-4"><i class="bi bi-shield-check"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Garantía oficial</h6>
                    <small class="text-muted">12 meses de respaldo directo en tienda</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white border rounded-4 p-3 d-flex align-items-center gap-3 h-100">
                <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3 fs-4"><i class="bi bi-credit-card"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Múltiples formas de pago</h6>
                    <small class="text-muted">Tarjeta, transferencia SPEI o pago en sucursal</small>
                </div>
            </div>
        </div>
    </section>

    <!-- Explora por Categoría -->
    <section aria-label="Categorías" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0">Compra por categoría</h2>
            <a href="publico/catalogo.php" class="text-decoration-none fw-semibold small">Ver todo el catálogo <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="row g-3">
            <?php foreach ($categorias as $cat): 
                $idCat = (int)$cat['intid_Categoria'];
                $icono = $iconosCat[$idCat] ?? 'bi-cpu';
            ?>
                <div class="col-6 col-md-3">
                    <a href="publico/catalogo.php?categoria=<?= $idCat ?>" class="text-decoration-none text-dark">
                        <div class="bg-white border rounded-4 p-3 text-center h-100 shadow-sm">
                            <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 52px; height: 52px;">
                                <i class="bi <?= $icono ?> fs-4"></i>
                            </div>
                            <h6 class="fw-semibold mb-0"><?= htmlspecialchars($cat['vchNombre']) ?></h6>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sección Ofertas Especiales -->
    <?php if (!empty($ofertas)): ?>
    <section aria-label="Productos con descuento" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 fw-bold mb-0">Productos con descuento</h2>
                <small class="text-muted">Precios especiales vigentes en tienda en línea</small>
            </div>
            <a href="publico/catalogo.php?ofertas=1" class="btn btn-outline-primary btn-sm rounded-pill px-3">Ver todas</a>
        </div>
        <div class="row g-3">
            <?php foreach ($ofertas as $prod): 
                $precioOrig = (float)$prod['floPrecioUnitario'];
                $desc = (int)$prod['intDescuento'];
                $precioFinal = round($precioOrig * (1 - ($desc / 100)), 2);
            ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="tv-product-card d-flex flex-column">
                        <a href="publico/producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="text-decoration-none">
                            <div class="tv-product-img-wrap">
                                <span class="tv-discount-badge">-<?= $desc ?>%</span>
                                <img src="<?= htmlspecialchars($prod['vchImagen']) ?>" alt="<?= htmlspecialchars($prod['vchNombre']) ?>">
                            </div>
                        </a>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                                <span><?= htmlspecialchars($prod['marca']) ?></span>
                                <span><i class="bi bi-star-fill text-warning"></i> <?= number_format((float)$prod['decCalificacion'], 1) ?></span>
                            </div>
                            <h3 class="h6 fw-bold mb-2">
                                <a href="publico/producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($prod['vchNombre']) ?>
                                </a>
                            </h3>
                            <div class="mt-auto">
                                <div class="mb-2">
                                    <span class="fw-bold text-primary fs-5">$<?= number_format($precioFinal, 2) ?></span>
                                    <small class="text-muted text-decoration-line-through ms-1">$<?= number_format($precioOrig, 2) ?></small>
                                </div>
                                <a href="publico/producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold">
                                    Ver detalle
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sección Productos Destacados -->
    <section aria-label="Productos destacados" class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 fw-bold mb-0">Lanzamientos y más vendidos</h2>
                <small class="text-muted">Disponibles para envío inmediato</small>
            </div>
            <a href="publico/catalogo.php" class="btn btn-primary btn-sm rounded-pill px-3">Ir al catálogo</a>
        </div>
        <div class="row g-3">
            <?php foreach ($destacados as $prod): 
                $precioOrig = (float)$prod['floPrecioUnitario'];
                $desc = (int)$prod['intDescuento'];
                $precioFinal = $desc > 0 ? round($precioOrig * (1 - ($desc / 100)), 2) : $precioOrig;
                $sinStock = ((int)$prod['intStock'] <= 0);
            ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="tv-product-card d-flex flex-column">
                        <a href="publico/producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="text-decoration-none">
                            <div class="tv-product-img-wrap">
                                <?php if ($desc > 0): ?>
                                    <span class="tv-discount-badge">-<?= $desc ?>%</span>
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars($prod['vchImagen']) ?>" alt="<?= htmlspecialchars($prod['vchNombre']) ?>">
                            </div>
                        </a>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                                <span><?= htmlspecialchars($prod['marca']) ?> · <?= htmlspecialchars($prod['categoria']) ?></span>
                                <span><i class="bi bi-star-fill text-warning"></i> <?= number_format((float)$prod['decCalificacion'], 1) ?></span>
                            </div>
                            <h3 class="h6 fw-bold mb-2">
                                <a href="publico/producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="text-dark text-decoration-none">
                                    <?= htmlspecialchars($prod['vchNombre']) ?>
                                </a>
                            </h3>
                            <div class="mt-auto">
                                <div class="mb-2">
                                    <span class="fw-bold text-primary fs-5">$<?= number_format($precioFinal, 2) ?></span>
                                    <?php if ($desc > 0): ?>
                                        <small class="text-muted text-decoration-line-through ms-1">$<?= number_format($precioOrig, 2) ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($sinStock): ?>
                                    <span class="badge bg-secondary-subtle text-secondary w-100 py-2 rounded-pill">Agotado temporalmente</span>
                                <?php else: ?>
                                    <a href="publico/producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="btn btn-primary btn-sm w-100 rounded-pill fw-semibold">
                                        Ver producto
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require_once "includes/footer.php"; ?>