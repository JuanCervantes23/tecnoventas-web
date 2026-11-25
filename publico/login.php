<?php
require_once "../config/conexion.php";
$baseUrl = "..";

// Si ya tiene sesión activa, redirigir al inicio
if (isset($_SESSION['tipo_nav']) && $_SESSION['tipo_nav'] !== 'publico') {
    header("Location: ../index.php");
    exit;
}

$error = "";
$correoIngresado = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correoIngresado = strtolower(trim($_POST['correo'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if ($correoIngresado === "" || $password === "") {
        $error = "Por favor, ingresa tu correo electrónico y contraseña.";
    } else {
        // 1. Buscar primero en personal interno (tblusuario -> Admin o Empleado)
        $stmtUsu = $pdo->prepare("
            SELECT id_usuario, vchnombre, vchapellido, vchcorreo, vchpassword, vchRol
            FROM tblusuario
            WHERE vchcorreo = ? AND Estado = 1
            LIMIT 1
        ");
        $stmtUsu->execute([$correoIngresado]);
        $usuario = $stmtUsu->fetch();

        if ($usuario && password_verify($password, $usuario['vchpassword'])) {
            unset($_SESSION['demo_nav'], $_SESSION['cliente_id']);
            $_SESSION['usuario_id'] = (int)$usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['vchnombre'] . " " . $usuario['vchapellido'];
            $_SESSION['rol_especifico'] = $usuario['vchRol'];

            // Asignar Nav Admin o Nav Empleado según el rol en BD
            if ($usuario['vchRol'] === 'Administrador') {
                $_SESSION['tipo_nav'] = 'admin';
            } else {
                $_SESSION['tipo_nav'] = 'empleado';
            }

            header("Location: ../index.php");
            exit;
        }

        // 2. Buscar en clientes registrados (tblcliente -> Nav Cliente)
        $stmtCli = $pdo->prepare("
            SELECT intid_Cliente, vchNombre, vchApellido_Paterno, vchCorreo, vchPassword
            FROM tblcliente
            WHERE vchCorreo = ? AND Estado = 1
            LIMIT 1
        ");
        $stmtCli->execute([$correoIngresado]);
        $cliente = $stmtCli->fetch();

        if ($cliente && password_verify($password, $cliente['vchPassword'])) {
            unset($_SESSION['demo_nav'], $_SESSION['usuario_id'], $_SESSION['rol_especifico']);
            $_SESSION['cliente_id'] = (int)$cliente['intid_Cliente'];
            $_SESSION['usuario_nombre'] = $cliente['vchNombre'];
            $_SESSION['tipo_nav'] = 'cliente';

            header("Location: ../index.php");
            exit;
        }

        $error = "Correo electrónico o contraseña incorrectos, o cuenta inactiva.";
    }
}

$tituloPagina = "Iniciar Sesión | TecnoVentas";
require_once "../includes/header.php";
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <section class="bg-white border rounded-4 p-4 p-md-5 shadow-sm">
                <div class="text-center mb-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-person-lock fs-3"></i>
                    </div>
                    <h1 class="h3 fw-bold mb-1">Bienvenido de nuevo</h1>
                    <p class="text-muted small mb-0">Ingresa a tu cuenta de TecnoVentas</p>
                </div>

                <?php if (isset($_GET['registrado'])): ?>
                    <div class="alert alert-success small rounded-3">
                        <i class="bi bi-check-circle-fill me-1"></i> Cuenta creada correctamente. Ya puedes iniciar sesión.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['recuperado'])): ?>
                    <div class="alert alert-success small rounded-3">
                        <i class="bi bi-shield-check me-1"></i> Contraseña actualizada. Inicia sesión con tu nueva clave.
                    </div>
                <?php endif; ?>

                <?php if ($error !== ""): ?>
                    <div class="alert alert-danger small rounded-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="txtCorreo" class="form-label small fw-semibold">Correo electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="email" id="txtCorreo" name="correo" class="form-control" placeholder="ejemplo@uthh.edu.mx" value="<?= htmlspecialchars($correoIngresado) ?>" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="txtPassword" class="form-label small fw-semibold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-key text-muted"></i></span>
                            <input type="password" id="txtPassword" name="password" class="form-control" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('txtPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="text-end mb-4">
                        <a href="recuperar_password.php" class="small text-decoration-none fw-semibold">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold mb-3">
                        Iniciar sesión
                    </button>

                    <p class="text-center small text-muted mb-0">
                        ¿Aún no tienes cuenta?
                        <a href="registro.php" class="fw-bold text-decoration-none">Regístrate aquí</a>
                    </p>
                </form>
            </section>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

<?php require_once "../includes/footer.php"; ?>