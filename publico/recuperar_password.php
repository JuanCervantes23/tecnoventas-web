<?php
require_once "../config/conexion.php";
$baseUrl = "..";

if (!isset($_SESSION['recovery_blocked'])) {
    $_SESSION['recovery_blocked'] = [];
}

$paso = 1;
$error = "";
$clienteEncontrado = null;
$correoIngresado = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $correoIngresado = strtolower(trim($_POST['correo'] ?? ''));

    if (!empty($_SESSION['recovery_blocked'][$correoIngresado])) {
        $error = "Has agotado tu único intento de recuperación para este correo. Contacta a soporte técnico.";
    } elseif ($accion === 'buscar_correo') {
        if ($correoIngresado === '' || !filter_var($correoIngresado, FILTER_VALIDATE_EMAIL)) {
            $error = "Ingresa un correo electrónico válido.";
        } else {
            $stmt = $pdo->prepare("
                SELECT c.intid_Cliente, c.vchNombre, c.vchCorreo, ps.vchPregunta
                FROM tblcliente c
                LEFT JOIN tblpreguntas_seguridad ps ON c.intid_Pregunta = ps.intid_Pregunta
                WHERE c.vchCorreo = ? AND c.Estado = 1
                LIMIT 1
            ");
            $stmt->execute([$correoIngresado]);
            $clienteEncontrado = $stmt->fetch();

            if (!$clienteEncontrado) {
                $error = "No existe una cuenta activa asociada a ese correo.";
            } elseif (empty($clienteEncontrado['vchPregunta'])) {
                $error = "Esta cuenta aún no tiene configurada una pregunta de seguridad.";
            } else {
                $paso = 2;
            }
        }
    } elseif ($accion === 'verificar_y_cambiar') {
        $respuesta = mb_strtolower(trim($_POST['respuesta'] ?? ''), 'UTF-8');
        $nuevaPass = trim($_POST['nueva_password'] ?? '');
        $confirmarPass = trim($_POST['confirmar_password'] ?? '');

        $stmt = $pdo->prepare("
            SELECT c.intid_Cliente, c.vchNombre, c.vchCorreo, c.vchRespuestaSeguridad, ps.vchPregunta
            FROM tblcliente c
            INNER JOIN tblpreguntas_seguridad ps ON c.intid_Pregunta = ps.intid_Pregunta
            WHERE c.vchCorreo = ? AND c.Estado = 1
            LIMIT 1
        ");
        $stmt->execute([$correoIngresado]);
        $clienteEncontrado = $stmt->fetch();

        if (!$clienteEncontrado) {
            $error = "Solicitud inválida.";
        } elseif ($respuesta === '' || $nuevaPass === '' || $confirmarPass === '') {
            $paso = 2;
            $error = "Todos los campos son obligatorios.";
        } elseif (strlen($nuevaPass) < 6) {
            $paso = 2;
            $error = "La nueva contraseña debe tener al menos 6 caracteres.";
        } elseif ($nuevaPass !== $confirmarPass) {
            $paso = 2;
            $error = "Las contraseñas no coinciden.";
        } else {
            $respuestaCorrecta = mb_strtolower(trim($clienteEncontrado['vchRespuestaSeguridad'] ?? ''), 'UTF-8');

            if ($respuesta !== $respuestaCorrecta) {
                $_SESSION['recovery_blocked'][$correoIngresado] = true;
                $paso = 1;
                $error = "Respuesta incorrecta. Has agotado tu único intento permitido por seguridad.";
            } else {
                $hash = password_hash($nuevaPass, PASSWORD_BCRYPT);
                $upd = $pdo->prepare("UPDATE tblcliente SET vchPassword = ? WHERE intid_Cliente = ?");
                $upd->execute([$hash, (int)$clienteEncontrado['intid_Cliente']]);

                header("Location: login.php?recuperado=1");
                exit;
            }
        }
    }
}

$tituloPagina = "Recuperar Contraseña | TecnoVentas";
require_once "../includes/header.php";
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <section class="bg-white border rounded-4 p-4 p-md-5 shadow-sm">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-2">Seguridad de Cuenta</span>
                <h1 class="h3 fw-bold mb-1">Recuperar contraseña</h1>
                <p class="text-muted small mb-4">Valida tu identidad mediante tu pregunta de seguridad.</p>

                <?php if ($error !== ""): ?>
                    <div class="alert alert-danger small rounded-3">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($paso === 1): ?>
                    <form method="POST" action="recuperar_password.php">
                        <input type="hidden" name="accion" value="buscar_correo">
                        <div class="mb-4">
                            <label for="txtCorreoRec" class="form-label small fw-semibold">Correo electrónico registrado</label>
                            <input type="email" id="txtCorreoRec" name="correo" class="form-control" placeholder="juan.prueba@uthh.edu.mx" value="<?= htmlspecialchars($correoIngresado) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold mb-3">
                            Continuar
                        </button>
                        <div class="text-center">
                            <a href="login.php" class="small text-decoration-none">Volver a iniciar sesión</a>
                        </div>
                    </form>

                <?php elseif ($paso === 2 && $clienteEncontrado): ?>
                    <div class="alert alert-warning small rounded-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Atención <strong><?= htmlspecialchars($clienteEncontrado['vchNombre']) ?></strong>: tienes <strong>1 único intento</strong> para responder correctamente.
                    </div>

                    <form method="POST" action="recuperar_password.php">
                        <input type="hidden" name="accion" value="verificar_y_cambiar">
                        <input type="hidden" name="correo" value="<?= htmlspecialchars($clienteEncontrado['vchCorreo']) ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pregunta de seguridad</label>
                            <div class="p-3 bg-light border rounded-3 fw-semibold small">
                                <?= htmlspecialchars($clienteEncontrado['vchPregunta']) ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="txtRespRec" class="form-label small fw-semibold">Tu respuesta</label>
                            <input type="text" id="txtRespRec" name="respuesta" class="form-control" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label for="txtNuevaPass" class="form-label small fw-semibold">Nueva contraseña (mín. 6 caracteres)</label>
                            <input type="password" id="txtNuevaPass" name="nueva_password" class="form-control" minlength="6" required>
                        </div>

                        <div class="mb-4">
                            <label for="txtConfPass" class="form-label small fw-semibold">Confirmar nueva contraseña</label>
                            <input type="password" id="txtConfPass" name="confirmar_password" class="form-control" minlength="6" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">
                            Restablecer contraseña
                        </button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>