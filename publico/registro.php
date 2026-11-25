<?php
require_once "../config/conexion.php";
$baseUrl = "..";

$preguntas = $pdo->query("SELECT intid_Pregunta, vchPregunta FROM tblpreguntas_seguridad WHERE Estado = 1 ORDER BY intid_Pregunta ASC")->fetchAll();

$errores = [];
$datos = [
    'nombre' => '',
    'apellido_paterno' => '',
    'apellido_materno' => '',
    'correo' => '',
    'telefono' => '',
    'id_pregunta' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre']           = trim($_POST['nombre'] ?? '');
    $datos['apellido_paterno'] = trim($_POST['apellido_paterno'] ?? '');
    $datos['apellido_materno'] = trim($_POST['apellido_materno'] ?? '');
    $datos['correo']           = strtolower(trim($_POST['correo'] ?? ''));
    $datos['telefono']         = trim($_POST['telefono'] ?? '');
    $datos['id_pregunta']      = (int)($_POST['id_pregunta'] ?? 0);
    $respuesta                 = trim($_POST['respuesta_seguridad'] ?? '');
    $password                  = trim($_POST['password'] ?? '');
    $confirmar                 = trim($_POST['confirmar_password'] ?? '');

    if ($datos['nombre'] === '' || $datos['apellido_paterno'] === '') {
        $errores[] = "El nombre y apellido paterno son obligatorios.";
    }
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Ingresa un correo electrónico válido.";
    }
    if ($datos['telefono'] !== '' && !preg_match('/^[0-9]{10}$/', $datos['telefono'])) {
        $errores[] = "El teléfono debe contener exactamente 10 dígitos numéricos.";
    }
    if (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    if ($password !== $confirmar) {
        $errores[] = "Las contraseñas no coinciden.";
    }
    if ($datos['id_pregunta'] <= 0 || $respuesta === '') {
        $errores[] = "Selecciona una pregunta de seguridad y escribe su respuesta.";
    }

    if (empty($errores)) {
        $stmtDup = $pdo->prepare("SELECT intid_Cliente FROM tblcliente WHERE vchCorreo = ? LIMIT 1");
        $stmtDup->execute([$datos['correo']]);
        if ($stmtDup->fetch()) {
            $errores[] = "Ese correo electrónico ya está registrado.";
        }
    }

    if (empty($errores)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $respNormalizada = mb_strtolower($respuesta, 'UTF-8');

        $stmtIns = $pdo->prepare("
            INSERT INTO tblcliente (
                vchNombre, vchApellido_Paterno, vchApellido_Materno,
                vchCorreo, vchTelefono, vchPassword,
                intid_Pregunta, vchRespuestaSeguridad, Estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmtIns->execute([
            $datos['nombre'],
            $datos['apellido_paterno'],
            $datos['apellido_materno'],
            $datos['correo'],
            $datos['telefono'] !== '' ? $datos['telefono'] : null,
            $hash,
            $datos['id_pregunta'],
            $respNormalizada
        ]);

        header("Location: login.php?registrado=1");
        exit;
    }
}

$tituloPagina = "Crear Cuenta | TecnoVentas";
require_once "../includes/header.php";
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <section class="bg-white border rounded-4 p-4 p-md-5 shadow-sm">
                <div class="mb-4">
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2">Nuevo Cliente</span>
                    <h1 class="h3 fw-bold mb-1">Crea tu cuenta en TecnoVentas</h1>
                    <p class="text-muted small mb-0">Compra en línea, guarda favoritos y rastrea tus pedidos.</p>
                </div>

                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger small rounded-3">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errores as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="registro.php">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="txtNombre" class="form-label small fw-semibold">Nombre(s) *</label>
                            <input type="text" id="txtNombre" name="nombre" class="form-control" value="<?= htmlspecialchars($datos['nombre']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="txtApPaterno" class="form-label small fw-semibold">Apellido paterno *</label>
                            <input type="text" id="txtApPaterno" name="apellido_paterno" class="form-control" value="<?= htmlspecialchars($datos['apellido_paterno']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="txtApMaterno" class="form-label small fw-semibold">Apellido materno</label>
                            <input type="text" id="txtApMaterno" name="apellido_materno" class="form-control" value="<?= htmlspecialchars($datos['apellido_materno']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="txtCorreo" class="form-label small fw-semibold">Correo electrónico *</label>
                            <input type="email" id="txtCorreo" name="correo" class="form-control" placeholder="usuario@dominio.com" value="<?= htmlspecialchars($datos['correo']) ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label for="txtTelefono" class="form-label small fw-semibold">Teléfono (10 dígitos)</label>
                            <input type="tel" id="txtTelefono" name="telefono" class="form-control" maxlength="10" pattern="[0-9]{10}" placeholder="7711234567" value="<?= htmlspecialchars($datos['telefono']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="txtPassReg" class="form-label small fw-semibold">Contraseña (mín. 6 caracteres) *</label>
                            <input type="password" id="txtPassReg" name="password" class="form-control" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label for="txtPassConf" class="form-label small fw-semibold">Confirmar contraseña *</label>
                            <input type="password" id="txtPassConf" name="confirmar_password" class="form-control" minlength="6" required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h2 class="h6 fw-bold text-primary mb-3"><i class="bi bi-shield-lock me-1"></i> Configuración de seguridad</h2>

                    <div class="mb-3">
                        <label for="selPregunta" class="form-label small fw-semibold">Pregunta de seguridad *</label>
                        <select id="selPregunta" name="id_pregunta" class="form-select" required>
                            <option value="">Selecciona una pregunta...</option>
                            <?php foreach ($preguntas as $p): ?>
                                <option value="<?= (int)$p['intid_Pregunta'] ?>" <?= $datos['id_pregunta'] === (int)$p['intid_Pregunta'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['vchPregunta']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="txtRespuesta" class="form-label small fw-semibold">Tu respuesta secreta *</label>
                        <input type="text" id="txtRespuesta" name="respuesta_seguridad" class="form-control" placeholder="Escribe tu respuesta (no distingue mayúsculas)" required autocomplete="off">
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <a href="login.php" class="btn btn-outline-secondary rounded-pill px-4">Ya tengo cuenta</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Registrarme</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>