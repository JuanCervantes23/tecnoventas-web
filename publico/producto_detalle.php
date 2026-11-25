<?php
require_once "../config/conexion.php";
$baseUrl = "..";

$noSerie = isset($_GET['no_serie']) ? trim($_GET['no_serie']) : "";
if ($noSerie === "") {
    header("Location: catalogo.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, m.vchNombre AS marca, c.vchNombre AS categoria
    FROM tblproductos p
    INNER JOIN tblmarcas m ON p.intid_Marca = m.intid_Marca
    INNER JOIN tblcategoria c ON p.intid_Categoria = c.intid_Categoria
    WHERE p.vchNo_Serie = ? AND p.Estado = 1
    LIMIT 1
");
$stmt->execute([$noSerie]);
$producto = $stmt->fetch();

if (!$producto) {
    header("Location: catalogo.php");
    exit;
}

$tituloPagina = $producto['vchNombre'] . " | TecnoVentas";
require_once "../includes/header.php";

// Reunir galería multi-ángulo (hasta 4 imágenes)
$imagenes = [];
foreach (['vchImagen', 'vchImagen2', 'vchImagen3', 'vchImagen4'] as $col) {
    if (!empty($producto[$col])) {
        $imagenes[] = $producto[$col];
    }
}

$precioOrig  = (float)$producto['floPrecioUnitario'];
$desc        = (int)$producto['intDescuento'];
$precioFinal = $desc > 0 ? round($precioOrig * (1 - ($desc / 100)), 2) : $precioOrig;
$stock       = (int)$producto['intStock'];

// Productos relacionados de la misma categoría
$stmtRel = $pdo->prepare("
    SELECT p.vchNo_Serie, p.vchNombre, p.floPrecioUnitario, p.intDescuento, p.vchImagen, m.vchNombre AS marca
    FROM tblproductos p
    INNER JOIN tblmarcas m ON p.intid_Marca = m.intid_Marca
    WHERE p.Estado = 1 AND p.intid_Categoria = ? AND p.vchNo_Serie <> ?
    LIMIT 4
");
$stmtRel->execute([(int)$producto['intid_Categoria'], $noSerie]);
$relacionados = $stmtRel->fetchAll();
?>

<div class="container">
    <!-- Ruta de navegación (Breadcrumb) -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Inicio</a></li>
            <li class="breadcrumb-item"><a href="catalogo.php?categoria=<?= (int)$producto['intid_Categoria'] ?>" class="text-decoration-none"><?= htmlspecialchars($producto['categoria']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($producto['vchNombre']) ?></li>
        </ol>
    </nav>

    <!-- Tarjeta Principal de Detalle -->
    <section class="bg-white border rounded-4 p-4 p-lg-5 shadow-sm mb-5">
        <div class="row g-4 align-items-start">
            <!-- Columna Izquierda: Galería Multi-Ángulo -->
            <div class="col-lg-6">
                <div class="bg-light rounded-4 p-4 text-center mb-3 d-flex align-items-center justify-content-center" style="min-height: 360px;">
                    <img id="imgPrincipalProducto" src="<?= htmlspecialchars($imagenes[0]) ?>" alt="<?= htmlspecialchars($producto['vchNombre']) ?>" class="img-fluid" style="max-height: 310px; object-fit: contain;">
                </div>

                <?php if (count($imagenes) > 1): ?>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <?php foreach ($imagenes as $idx => $urlImg): ?>
                            <button type="button"
                                    class="btn bg-light border rounded-3 p-2 thumb-btn <?= $idx === 0 ? 'border-primary border-2' : '' ?>"
                                    onclick="cambiarImagenDetalle('<?= htmlspecialchars($urlImg, ENT_QUOTES) ?>', this)">
                                <img src="<?= htmlspecialchars($urlImg) ?>" alt="Vista <?= $idx + 1 ?>" style="width: 58px; height: 58px; object-fit: contain;">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Columna Derecha: Información, Precio y Compra -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill">
                        <?= htmlspecialchars($producto['marca']) ?> · <?= htmlspecialchars($producto['categoria']) ?>
                    </span>
                    <span class="small fw-semibold">
                        <i class="bi bi-star-fill text-warning"></i>
                        <?= number_format((float)$producto['decCalificacion'], 1) ?>
                        <span class="text-muted">(<?= (int)$producto['intResenas'] ?> reseñas)</span>
                    </span>
                </div>

                <h1 class="h2 fw-bold mb-2"><?= htmlspecialchars($producto['vchNombre']) ?></h1>
                <p class="small text-muted mb-3">Código de serie: <strong><?= htmlspecialchars($producto['vchNo_Serie']) ?></strong></p>

                <!-- Bloque de Precio -->
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="display-6 fw-bold text-primary mb-0">$<?= number_format($precioFinal, 2) ?> <small class="fs-6 fw-normal text-muted">MXN</small></span>
                    <?php if ($desc > 0): ?>
                        <span class="text-muted text-decoration-line-through fs-5">$<?= number_format($precioOrig, 2) ?></span>
                        <span class="badge bg-danger rounded-pill px-3 py-2">-<?= $desc ?>%</span>
                    <?php endif; ?>
                </div>

                <!-- Disponibilidad y Acabado -->
                <div class="mb-3">
                    <?php if ($stock > 0): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
                            <i class="bi bi-check-circle-fill me-1"></i> Disponible (<?= $stock ?> en stock) · Envío gratis
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2">
                            <i class="bi bi-x-circle-fill me-1"></i> Agotado temporalmente
                        </span>
                    <?php endif; ?>
                </div>

                <p class="small mb-2"><strong>Acabado:</strong> <?= htmlspecialchars($producto['vchAcabado']) ?></p>
                <p class="text-secondary mb-4" style="line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($producto['vchDescripcion'])) ?>
                </p>

                <!-- Chips de Garantía -->
                <div class="row g-2 mb-4 text-center small">
                    <div class="col-4">
                        <div class="border rounded-3 py-2 px-1 bg-light"><i class="bi bi-shield-check text-primary me-1"></i> 12m garantía</div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded-3 py-2 px-1 bg-light"><i class="bi bi-truck text-success me-1"></i> 24-48h envío</div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded-3 py-2 px-1 bg-light"><i class="bi bi-box-seam text-dark me-1"></i> <?= $stock ?> en stock</div>
                    </div>
                </div>

                <!-- Formulario Agregar a la Bolsa (conecta con el módulo de Tomasa) -->
                <?php if ($stock > 0): ?>
                    <?php if ($tipoNav === 'cliente'): ?>
                        <form action="carrito.php" method="POST" class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <input type="hidden" name="accion" value="agregar">
                            <input type="hidden" name="vchNo_Serie" value="<?= htmlspecialchars($producto['vchNo_Serie']) ?>">

                            <div class="input-group" style="width: 135px;">
                                <button class="btn btn-outline-secondary" type="button" onclick="ajustarCantidad(-1, <?= $stock ?>)">−</button>
                                <input type="number" id="inputCantidad" name="cantidad" class="form-control text-center fw-bold" value="1" min="1" max="<?= $stock ?>" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="ajustarCantidad(1, <?= $stock ?>)">+</button>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold flex-grow-1">
                                <i class="bi bi-bag-plus me-2"></i> Agregar a la bolsa
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold mb-2">
                            <i class="bi bi-person-lock me-2"></i> Inicia sesión como cliente para comprar
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Multimedia Demostrativa (Audio / Video si existen en BD) -->
                <?php if (!empty($producto['vchAudio_Url']) || !empty($producto['vchVideo_Url'])): ?>
                    <div class="border-top pt-3 mt-4">
                        <h6 class="fw-bold small text-uppercase text-muted mb-3">Demostración multimedia del producto</h6>
                        <?php if (!empty($producto['vchAudio_Url'])): ?>
                            <div class="mb-3">
                                <label class="small fw-semibold d-block mb-1"><i class="bi bi-volume-up text-primary me-1"></i> Reseña / prueba de audio</label>
                                <audio controls class="w-100">
                                    <source src="<?= htmlspecialchars($producto['vchAudio_Url']) ?>" type="audio/mpeg">
                                </audio>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($producto['vchVideo_Url'])): ?>
                            <div>
                                <label class="small fw-semibold d-block mb-1"><i class="bi bi-play-btn text-primary me-1"></i> Video demostrativo</label>
                                <video controls class="w-100 rounded-3 border" style="max-height: 240px;">
                                    <source src="<?= htmlspecialchars($producto['vchVideo_Url']) ?>" type="video/mp4">
                                </video>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Productos Relacionados -->
    <?php if (!empty($relacionados)): ?>
    <section aria-label="Productos relacionados">
        <h2 class="h5 fw-bold mb-3">También te puede interesar</h2>
        <div class="row g-3">
            <?php foreach ($relacionados as $rel): 
                $pOrig = (float)$rel['floPrecioUnitario'];
                $dRel  = (int)$rel['intDescuento'];
                $pFin  = $dRel > 0 ? round($pOrig * (1 - ($dRel / 100)), 2) : $pOrig;
            ?>
                <div class="col-6 col-md-3">
                    <article class="tv-product-card">
                        <a href="producto_detalle.php?no_serie=<?= urlencode($rel['vchNo_Serie']) ?>" class="text-decoration-none text-dark">
                            <div class="tv-product-img-wrap" style="height: 150px;">
                                <img src="<?= htmlspecialchars($rel['vchImagen']) ?>" alt="<?= htmlspecialchars($rel['vchNombre']) ?>" style="max-height: 120px;">
                            </div>
                            <div class="p-3">
                                <small class="text-muted d-block"><?= htmlspecialchars($rel['marca']) ?></small>
                                <h6 class="fw-bold text-truncate mb-1"><?= htmlspecialchars($rel['vchNombre']) ?></h6>
                                <span class="fw-bold text-primary">$<?= number_format($pFin, 2) ?></span>
                            </div>
                        </a>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
function cambiarImagenDetalle(url, btn) {
    document.getElementById('imgPrincipalProducto').src = url;
    document.querySelectorAll('.thumb-btn').forEach(el => {
        el.classList.remove('border-primary', 'border-2');
    });
    btn.classList.add('border-primary', 'border-2');
}

function ajustarCantidad(delta, maxStock) {
    const input = document.getElementById('inputCantidad');
    let actual = parseInt(input.value, 10) || 1;
    actual += delta;
    if (actual < 1) actual = 1;
    if (actual > maxStock) actual = maxStock;
    input.value = actual;
}
</script>

<?php require_once "../includes/footer.php"; ?>