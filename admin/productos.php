<?php
require_once "../config/conexion.php";
require_once "../includes/verificar_acceso.php";
requireRol(['admin', 'empleado']);

$esAdmin = (($_SESSION['tipo_nav'] ?? '') === 'admin');
$baseUrl = "..";
$mensaje = "";
$tipoAlerta = "success";

// Carpeta física para subida de imágenes (upload)
$carpetaUpload = __DIR__ . "/../uploads/productos/";
if (!is_dir($carpetaUpload)) {
    mkdir($carpetaUpload, 0755, true);
}

// Función auxiliar para procesar subida de imagen
function procesarImagenUpload(string $campoFile, string $carpetaDestino): ?string {
    if (!isset($_FILES[$campoFile]) || $_FILES[$campoFile]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
    $tipoMime = mime_content_type($_FILES[$campoFile]['tmp_name']);
    if (!in_array($tipoMime, $permitidos, true)) {
        throw new Exception("Formato de imagen no válido. Solo se permite JPG, PNG o WEBP.");
    }
    $ext = pathinfo($_FILES[$campoFile]['name'], PATHINFO_EXTENSION);
    $nombreArchivo = "prod_" . uniqid() . "." . strtolower($ext);
    if (!move_uploaded_file($_FILES[$campoFile]['tmp_name'], $carpetaDestino . $nombreArchivo)) {
        throw new Exception("No se pudo guardar la imagen en el servidor.");
    }
    return "../uploads/productos/" . $nombreArchivo;
}

// Procesar acciones POST (solo Administrador)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esAdmin) {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear' || $accion === 'editar') {
            $noSerie     = strtoupper(trim($_POST['vchNo_Serie'] ?? ''));
            $nombre      = trim($_POST['vchNombre'] ?? '');
            $idMarca     = (int)($_POST['intid_Marca'] ?? 0);
            $idCategoria = (int)($_POST['intid_Categoria'] ?? 0);
            $descripcion = trim($_POST['vchDescripcion'] ?? '');
            $precioVenta = (float)($_POST['floPrecioUnitario'] ?? 0);
            $precioComp  = (float)($_POST['floPrecioCompra'] ?? 0);
            $stock       = (int)($_POST['intStock'] ?? 0);
            $descuento   = max(0, min(100, (int)($_POST['intDescuento'] ?? 0)));
            $acabado     = trim($_POST['vchAcabado'] ?? 'Estándar');
            $urlManual   = trim($_POST['url_imagen'] ?? '');
            $audioUrl    = trim($_POST['vchAudio_Url'] ?? '');
            $videoUrl    = trim($_POST['vchVideo_Url'] ?? '');

            if ($noSerie === '' || $nombre === '' || $idMarca <= 0 || $idCategoria <= 0 || $precioVenta <= 0) {
                throw new Exception("Completa todos los campos obligatorios (*).");
            }

            // Resolver imagen: prioridad archivo subido -> URL escrita -> imagen actual (si edita)
            $rutaSubida = procesarImagenUpload('archivo_imagen', $carpetaUpload);
            $rutaSubida2 = procesarImagenUpload('archivo_imagen2', $carpetaUpload);

            if ($accion === 'crear') {
                $imgFinal = $rutaSubida ?? ($urlManual !== '' ? $urlManual : 'https://via.placeholder.com/400x400?text=TecnoVentas');

                $stmtCheck = $pdo->prepare("SELECT vchNo_Serie FROM tblproductos WHERE vchNo_Serie = ? LIMIT 1");
                $stmtCheck->execute([$noSerie]);
                if ($stmtCheck->fetch()) {
                    throw new Exception("Ya existe un producto con el número de serie $noSerie.");
                }

                $stmtIns = $pdo->prepare("
                    INSERT INTO tblproductos (
                        vchNo_Serie, intid_Marca, intid_Categoria, vchNombre, vchDescripcion,
                        Estado, floPrecioUnitario, intStock, floPrecioCompra,
                        vchImagen, vchImagen2, vchAudio_Url, vchVideo_Url,
                        intDescuento, vchAcabado
                    ) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtIns->execute([
                    $noSerie, $idMarca, $idCategoria, $nombre, $descripcion,
                    $precioVenta, $stock, $precioComp,
                    $imgFinal, $rutaSubida2,
                    $audioUrl !== '' ? $audioUrl : null,
                    $videoUrl !== '' ? $videoUrl : null,
                    $descuento, $acabado !== '' ? $acabado : 'Estándar'
                ]);
                $mensaje = "Producto $nombre registrado correctamente.";
            } else {
                $imgActual = trim($_POST['imagen_actual'] ?? '');
                $imgFinal = $rutaSubida ?? ($urlManual !== '' ? $urlManual : $imgActual);

                $sqlUpd = "
                    UPDATE tblproductos
                    SET intid_Marca = ?, intid_Categoria = ?, vchNombre = ?, vchDescripcion = ?,
                        floPrecioUnitario = ?, intStock = ?, floPrecioCompra = ?,
                        vchImagen = ?, vchAudio_Url = ?, vchVideo_Url = ?,
                        intDescuento = ?, vchAcabado = ?
                ";
                $paramsUpd = [
                    $idMarca, $idCategoria, $nombre, $descripcion,
                    $precioVenta, $stock, $precioComp,
                    $imgFinal,
                    $audioUrl !== '' ? $audioUrl : null,
                    $videoUrl !== '' ? $videoUrl : null,
                    $descuento, $acabado !== '' ? $acabado : 'Estándar'
                ];

                if ($rutaSubida2 !== null) {
                    $sqlUpd .= ", vchImagen2 = ?";
                    $paramsUpd[] = $rutaSubida2;
                }

                $sqlUpd .= " WHERE vchNo_Serie = ?";
                $paramsUpd[] = $noSerie;

                $pdo->prepare($sqlUpd)->execute($paramsUpd);
                $mensaje = "Producto $noSerie actualizado correctamente.";
            }
        } elseif ($accion === 'cambiar_estado') {
            $noSerie = trim($_POST['vchNo_Serie'] ?? '');
            $nuevoEstado = (int)($_POST['nuevo_estado'] ?? 1);
            $pdo->prepare("UPDATE tblproductos SET Estado = ? WHERE vchNo_Serie = ?")->execute([$nuevoEstado, $noSerie]);
            $mensaje = $nuevoEstado === 1 ? "Producto reactivado en catálogo." : "Producto dado de baja del catálogo.";
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoAlerta = "danger";
    }
}

// Si viene ?editar=SERIE, cargar datos en el formulario
$prodEditar = null;
if ($esAdmin && isset($_GET['editar'])) {
    $stmtEd = $pdo->prepare("SELECT * FROM tblproductos WHERE vchNo_Serie = ? LIMIT 1");
    $stmtEd->execute([trim($_GET['editar'])]);
    $prodEditar = $stmtEd->fetch();
}

// Catálogos y listado general
$marcas = $pdo->query("SELECT intid_Marca, vchNombre FROM tblmarcas WHERE Estado = 1 ORDER BY vchNombre ASC")->fetchAll();
$categorias = $pdo->query("SELECT intid_Categoria, vchNombre FROM tblcategoria WHERE Estado = 1 ORDER BY vchNombre ASC")->fetchAll();

$filtro = trim($_GET['q'] ?? '');
$sqlLista = "
    SELECT p.*, m.vchNombre AS marca, c.vchNombre AS categoria
    FROM tblproductos p
    INNER JOIN tblmarcas m ON p.intid_Marca = m.intid_Marca
    INNER JOIN tblcategoria c ON p.intid_Categoria = c.intid_Categoria
";
$paramsLista = [];
if ($filtro !== '') {
    $sqlLista .= " WHERE p.vchNombre LIKE ? OR p.vchNo_Serie LIKE ? OR m.vchNombre LIKE ?";
    $like = "%$filtro%";
    $paramsLista = [$like, $like, $like];
}
$sqlLista .= " ORDER BY p.Estado DESC, p.vchNombre ASC";
$stmtLista = $pdo->prepare($sqlLista);
$stmtLista->execute($paramsLista);
$listaProductos = $stmtLista->fetchAll();

$tituloPagina = "Gestión de Productos | TecnoVentas";
require_once "../includes/header.php";
?>

<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 fw-bold mb-0"><?= $esAdmin ? 'CRUD de Productos e Inventario' : 'Consulta de Inventario' ?></h1>
            <small class="text-muted">Los cambios se reflejan automáticamente en la tienda pública.</small>
        </div>
        <?php if ($esAdmin): ?>
            <button class="btn btn-primary rounded-pill px-4 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#panelFormularioProducto">
                <i class="bi bi-plus-lg me-1"></i> <?= $prodEditar ? 'Editando: ' . htmlspecialchars($prodEditar['vchNo_Serie']) : 'Nuevo Producto' ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show rounded-3 small">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario de Alta / Edición con Upload (solo Admin) -->
    <?php if ($esAdmin): ?>
    <div class="collapse <?= $prodEditar ? 'show' : '' ?> mb-4" id="panelFormularioProducto">
        <section class="bg-white border rounded-4 p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0">
                    <?= $prodEditar ? 'Editar producto (' . htmlspecialchars($prodEditar['vchNo_Serie']) . ')' : 'Registrar nuevo producto' ?>
                </h2>
                <?php if ($prodEditar): ?>
                    <a href="productos.php" class="btn btn-outline-secondary btn-sm rounded-pill">Cancelar edición</a>
                <?php endif; ?>
            </div>

            <form method="POST" action="productos.php" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="<?= $prodEditar ? 'editar' : 'crear' ?>">
                <?php if ($prodEditar): ?>
                    <input type="hidden" name="vchNo_Serie" value="<?= htmlspecialchars($prodEditar['vchNo_Serie']) ?>">
                    <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($prodEditar['vchImagen']) ?>">
                <?php endif; ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">No. Serie (SKU) *</label>
                        <input type="text" name="vchNo_Serie" class="form-control" maxlength="20" placeholder="Ej. LAP-030"
                               value="<?= htmlspecialchars($prodEditar['vchNo_Serie'] ?? '') ?>" <?= $prodEditar ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Nombre del producto *</label>
                        <input type="text" name="vchNombre" class="form-control" value="<?= htmlspecialchars($prodEditar['vchNombre'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Categoría *</label>
                        <select name="intid_Categoria" class="form-select" required>
                            <option value="">Elegir...</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= (int)$c['intid_Categoria'] ?>" <?= ((int)($prodEditar['intid_Categoria'] ?? 0) === (int)$c['intid_Categoria']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['vchNombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Marca *</label>
                        <select name="intid_Marca" class="form-select" required>
                            <option value="">Elegir...</option>
                            <?php foreach ($marcas as $m): ?>
                                <option value="<?= (int)$m['intid_Marca'] ?>" <?= ((int)($prodEditar['intid_Marca'] ?? 0) === (int)$m['intid_Marca']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['vchNombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Precio venta (MXN) *</label>
                        <input type="number" step="0.01" min="1" name="floPrecioUnitario" class="form-control" value="<?= htmlspecialchars($prodEditar['floPrecioUnitario'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Precio compra (MXN) *</label>
                        <input type="number" step="0.01" min="0" name="floPrecioCompra" class="form-control" value="<?= htmlspecialchars($prodEditar['floPrecioCompra'] ?? '0') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Stock *</label>
                        <input type="number" min="0" name="intStock" class="form-control" value="<?= htmlspecialchars($prodEditar['intStock'] ?? '10') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Descuento (%)</label>
                        <input type="number" min="0" max="100" name="intDescuento" class="form-control" value="<?= htmlspecialchars($prodEditar['intDescuento'] ?? '0') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Acabado / Color</label>
                        <input type="text" name="vchAcabado" class="form-control" value="<?= htmlspecialchars($prodEditar['vchAcabado'] ?? 'Estándar') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Descripción técnica *</label>
                    <textarea name="vchDescripcion" class="form-control" rows="2" required><?= htmlspecialchars($prodEditar['vchDescripcion'] ?? '') ?></textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold"><i class="bi bi-upload me-1"></i> Subir imagen principal (JPG/PNG/WEBP)</label>
                        <input type="file" name="archivo_imagen" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">O pegar URL externa de imagen</label>
                        <input type="url" name="url_imagen" class="form-control form-control-sm" placeholder="https://..." value="<?= htmlspecialchars($prodEditar['vchImagen'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold"><i class="bi bi-images me-1"></i> Subir 2da vista (opcional)</label>
                        <input type="file" name="archivo_imagen2" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                    <i class="bi bi-save me-1"></i> <?= $prodEditar ? 'Guardar cambios' : 'Registrar producto' ?>
                </button>
            </form>
        </section>
    </div>
    <?php endif; ?>

    <!-- Tabla de Consulta -->
    <section class="bg-white border rounded-4 p-4 shadow-sm">
        <form method="GET" action="productos.php" class="row g-2 mb-3">
            <div class="col-md-6">
                <input type="search" name="q" class="form-control form-control-sm" placeholder="Filtrar por serie, nombre o marca..." value="<?= htmlspecialchars($filtro) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3">Buscar</button>
                <?php if ($filtro !== ''): ?>
                    <a href="productos.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Img</th>
                        <th>Serie</th>
                        <th>Producto</th>
                        <th>Categoría / Marca</th>
                        <th>Precio</th>
                        <th>Desc.</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <?php if ($esAdmin): ?><th class="text-end">Acciones</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listaProductos as $p): ?>
                        <tr class="<?= (int)$p['Estado'] === 0 ? 'table-secondary opacity-75' : '' ?>">
                            <td>
                                <img src="<?= htmlspecialchars($p['vchImagen']) ?>" alt="" style="width: 44px; height: 44px; object-fit: contain;" class="bg-light rounded p-1 border">
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($p['vchNo_Serie']) ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($p['vchNombre']) ?></div>
                                <a href="../publico/producto_detalle.php?no_serie=<?= urlencode($p['vchNo_Serie']) ?>" target="_blank" class="text-decoration-none" style="font-size: 0.75rem;">
                                    Ver vista detalle <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($p['categoria']) ?><br><span class="text-muted"><?= htmlspecialchars($p['marca']) ?></span></td>
                            <td class="fw-bold text-primary">$<?= number_format((float)$p['floPrecioUnitario'], 2) ?></td>
                            <td><?= (int)$p['intDescuento'] > 0 ? '<span class="badge bg-danger">-' . (int)$p['intDescuento'] . '%</span>' : '—' ?></td>
                            <td>
                                <span class="badge <?= (int)$p['intStock'] <= 5 ? 'bg-danger-subtle text-danger' : 'bg-light text-dark border' ?>">
                                    <?= (int)$p['intStock'] ?> uds
                                </span>
                            </td>
                            <td>
                                <?= (int)$p['Estado'] === 1
                                    ? '<span class="badge bg-success-subtle text-success">Activo</span>'
                                    : '<span class="badge bg-secondary">Inactivo</span>' ?>
                            </td>
                            <?php if ($esAdmin): ?>
                                <td class="text-end text-nowrap">
                                    <a href="productos.php?editar=<?= urlencode($p['vchNo_Serie']) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="productos.php" class="d-inline">
                                        <input type="hidden" name="accion" value="cambiar_estado">
                                        <input type="hidden" name="vchNo_Serie" value="<?= htmlspecialchars($p['vchNo_Serie']) ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?= (int)$p['Estado'] === 1 ? 0 : 1 ?>">
                                        <button type="submit" class="btn btn-sm rounded-pill px-2 <?= (int)$p['Estado'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                title="<?= (int)$p['Estado'] === 1 ? 'Dar de baja' : 'Reactivar' ?>">
                                            <i class="bi <?= (int)$p['Estado'] === 1 ? 'bi-trash' : 'bi-arrow-counterclockwise' ?>"></i>
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once "../includes/footer.php"; ?>