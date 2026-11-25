<?php
require_once "../config/conexion.php";
$baseUrl = "..";
$tituloPagina = "Catálogo de Productos | TecnoVentas";
require_once "../includes/header.php";

$categoriaId = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$marcaId     = isset($_GET['marca']) ? (int)$_GET['marca'] : 0;
$buscar      = isset($_GET['buscar']) ? trim($_GET['buscar']) : "";
$soloOfertas = isset($_GET['ofertas']) ? (int)$_GET['ofertas'] : 0;
$orden       = $_GET['orden'] ?? "recientes";

// Catálogos para la barra lateral de filtros
$categorias = $pdo->query("SELECT intid_Categoria, vchNombre FROM tblcategoria WHERE Estado = 1 ORDER BY vchNombre ASC")->fetchAll();
$marcas     = $pdo->query("SELECT intid_Marca, vchNombre FROM tblmarcas WHERE Estado = 1 ORDER BY vchNombre ASC")->fetchAll();

// Construcción dinámica de consulta preparada
$sql = "
    SELECT p.vchNo_Serie, p.vchNombre, p.vchDescripcion, p.floPrecioUnitario,
           p.intDescuento, p.intStock, p.vchImagen, p.decCalificacion,
           m.vchNombre AS marca, c.vchNombre AS categoria
    FROM tblproductos p
    INNER JOIN tblmarcas m ON p.intid_Marca = m.intid_Marca
    INNER JOIN tblcategoria c ON p.intid_Categoria = c.intid_Categoria
    WHERE p.Estado = 1
";
$params = [];

if ($categoriaId > 0) {
    $sql .= " AND p.intid_Categoria = ?";
    $params[] = $categoriaId;
}
if ($marcaId > 0) {
    $sql .= " AND p.intid_Marca = ?";
    $params[] = $marcaId;
}
if ($soloOfertas === 1) {
    $sql .= " AND p.intDescuento > 0";
}
if ($buscar !== "") {
    $sql .= " AND (p.vchNombre LIKE ? OR p.vchNo_Serie LIKE ? OR m.vchNombre LIKE ? OR p.vchDescripcion LIKE ?)";
    $like = "%" . $buscar . "%";
    array_push($params, $like, $like, $like, $like);
}

switch ($orden) {
    case "precio_asc":
        $sql .= " ORDER BY (p.floPrecioUnitario * (1 - p.intDescuento / 100)) ASC";
        break;
    case "precio_desc":
        $sql .= " ORDER BY (p.floPrecioUnitario * (1 - p.intDescuento / 100)) DESC";
        break;
    case "descuento":
        $sql .= " ORDER BY p.intDescuento DESC, p.vchNombre ASC";
        break;
    default:
        $sql .= " ORDER BY p.vchNombre ASC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();
?>

<div class="container">
    <div class="row g-4">
        <!-- Sidebar de Filtros -->
        <aside class="col-lg-3">
            <form method="GET" action="catalogo.php" class="bg-white border rounded-4 p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-sliders me-1 text-primary"></i> Filtros</h2>
                    <a href="catalogo.php" class="small text-decoration-none">Limpiar</a>
                </div>

                <?php if ($buscar !== ""): ?>
                    <input type="hidden" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Categoría</label>
                    <select name="categoria" class="form-select form-select-sm rounded-3">
                        <option value="0">Todas las categorías</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= (int)$c['intid_Categoria'] ?>" <?= $categoriaId === (int)$c['intid_Categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['vchNombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Marca</label>
                    <select name="marca" class="form-select form-select-sm rounded-3">
                        <option value="0">Todas las marcas</option>
                        <?php foreach ($marcas as $m): ?>
                            <option value="<?= (int)$m['intid_Marca'] ?>" <?= $marcaId === (int)$m['intid_Marca'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['vchNombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Ordenar por</label>
                    <select name="orden" class="form-select form-select-sm rounded-3">
                        <option value="recientes" <?= $orden === 'recientes' ? 'selected' : '' ?>>Nombre (A - Z)</option>
                        <option value="precio_asc" <?= $orden === 'precio_asc' ? 'selected' : '' ?>>Menor precio</option>
                        <option value="precio_desc" <?= $orden === 'precio_desc' ? 'selected' : '' ?>>Mayor precio</option>
                        <option value="descuento" <?= $orden === 'descuento' ? 'selected' : '' ?>>Mayor descuento</option>
                    </select>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="ofertas" value="1" id="chkOfertas" <?= $soloOfertas === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-semibold" for="chkOfertas">
                        Solo productos en oferta
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill fw-semibold">
                    Aplicar filtros
                </button>
            </form>
        </aside>

        <!-- Cuadrícula de Resultados -->
        <section class="col-lg-9">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h1 class="h4 fw-bold mb-0">
                        <?= $buscar !== "" ? 'Resultados para "' . htmlspecialchars($buscar) . '"' : 'Catálogo de productos' ?>
                    </h1>
                    <small class="text-muted"><?= count($productos) ?> producto(s) encontrado(s)</small>
                </div>
            </div>

            <?php if (empty($productos)): ?>
                <div class="bg-white border rounded-4 p-5 text-center">
                    <i class="bi bi-search fs-1 text-muted mb-2 d-block"></i>
                    <h5 class="fw-bold">No encontramos productos con esos filtros</h5>
                    <p class="text-muted small mb-3">Prueba quitando algún filtro o buscando otro modelo.</p>
                    <a href="catalogo.php" class="btn btn-primary btn-sm rounded-pill px-4">Ver todo el catálogo</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($productos as $prod): 
                        $precioOrig = (float)$prod['floPrecioUnitario'];
                        $desc = (int)$prod['intDescuento'];
                        $precioFinal = $desc > 0 ? round($precioOrig * (1 - ($desc / 100)), 2) : $precioOrig;
                        $stock = (int)$prod['intStock'];
                    ?>
                        <div class="col-12 col-sm-6 col-xl-4">
                            <article class="tv-product-card d-flex flex-column">
                                <a href="producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="text-decoration-none">
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
                                    <h2 class="h6 fw-bold mb-1">
                                        <a href="producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="text-dark text-decoration-none">
                                            <?= htmlspecialchars($prod['vchNombre']) ?>
                                        </a>
                                    </h2>
                                    <p class="small text-muted text-truncate mb-3"><?= htmlspecialchars($prod['vchDescripcion']) ?></p>

                                    <div class="mt-auto">
                                        <div class="d-flex align-items-baseline gap-2 mb-2">
                                            <span class="fw-bold text-primary fs-5">$<?= number_format($precioFinal, 2) ?></span>
                                            <?php if ($desc > 0): ?>
                                                <small class="text-muted text-decoration-line-through">$<?= number_format($precioOrig, 2) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="<?= $stock > 0 ? 'text-success' : 'text-danger' ?> fw-semibold">
                                                <?= $stock > 0 ? "$stock disponibles" : "Sin stock" ?>
                                            </small>
                                            <small class="text-muted">SKU: <?= htmlspecialchars($prod['vchNo_Serie']) ?></small>
                                        </div>
                                        <a href="producto_detalle.php?no_serie=<?= urlencode($prod['vchNo_Serie']) ?>" class="btn btn-primary btn-sm w-100 rounded-pill fw-semibold">
                                            Ver detalle
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>