<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();
sendSecurityHeaders();

if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
$pageTitle = "Iniciar Sesión";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-2 py-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5">
            <div class="card fr-card border-0 shadow-sm p-3 p-sm-4">
                <div class="text-center mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle p-2 mb-2" style="width: 52px; height: 52px;">
                        <i class="bi bi-box-arrow-in-right fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark fs-4 mb-1">Bienvenido de vuelta</h3>
                    <p class="text-muted small mb-0">Ingresa tus credenciales para acceder a FoodRescue</p>
                </div>

                <?php if (isset($_SESSION['flash_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center small py-2 px-3 mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                        <div class="text-break"><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center small py-2 px-3 mb-3" role="alert">
                        <i class="bi bi-check-circle-fill flex-shrink-0 me-2"></i>
                        <div class="text-break"><?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="login_process.php" method="POST">
                    <?= csrfField() ?>
                    <?php $prefillEmail = htmlspecialchars(trim($_GET['email'] ?? '')); ?>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $prefillEmail ?>" placeholder="nombre@correo.com" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-fr-primary w-100 py-2 fs-6 mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                    </button>

                    <div class="text-center">
                        <span class="text-muted small">¿No tienes cuenta?</span>
                        <a href="register.php" class="text-decoration-none fw-semibold text-success small ms-1">Regístrate aquí</a>
                    </div>
                </form>

                <!-- Cuentas Demo con Autocompletado de 1 Clic -->
                <div class="mt-3 pt-3 border-top small" style="font-size: 0.82rem;">
                    <p class="mb-2 fw-bold text-dark text-center"><i class="bi bi-info-circle me-1 text-primary"></i> Cuentas de Demostración (Haz clic para usar):</p>
                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3 shadow-sm" onclick="llenarCredenciales('comercio@laespiga.com', 'password123')" title="Autocompletar cuenta de comercio">
                            <div>
                                <span class="badge bg-warning text-dark me-1"><i class="bi bi-shop me-1"></i>Comercio</span>
                                <span class="fw-semibold">comercio@laespiga.com</span>
                            </div>
                            <span class="badge bg-light text-muted border">password123 <i class="bi bi-arrow-right-short ms-1 text-warning"></i></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success text-dark text-start d-flex justify-content-between align-items-center py-2 px-3 rounded-3 shadow-sm" onclick="llenarCredenciales('cliente@foodrescue.com', 'password123')" title="Autocompletar cuenta de cliente">
                            <div>
                                <span class="badge bg-success text-white me-1"><i class="bi bi-bag-check-fill me-1"></i>Cliente</span>
                                <span class="fw-semibold">cliente@foodrescue.com</span>
                            </div>
                            <span class="badge bg-light text-muted border">password123 <i class="bi bi-arrow-right-short ms-1 text-success"></i></span>
                        </button>
                    </div>
                </div>

                <script>
                function llenarCredenciales(email, pass) {
                    const inEmail = document.getElementById('email');
                    const inPass = document.getElementById('password');
                    if (inEmail && inPass) {
                        inEmail.value = email;
                        inPass.value = pass;
                        inPass.focus();
                    }
                }
                </script>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
