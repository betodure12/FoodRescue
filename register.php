<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();
sendSecurityHeaders();

if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
$pageTitle = "Registro de Usuario";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-8">
            <div class="card fr-card border-0 shadow-sm p-3 p-md-4">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle p-3 mb-2" style="width: 60px; height: 60px;">
                        <i class="bi bi-person-plus-fill fs-3"></i>
                    </div>
                    <h3 class="fw-bold text-dark">Únete a FoodRescue</h3>
                    <p class="text-muted small">Crea tu cuenta para salvar comida o publicar los excedentes de tu comercio.</p>
                </div>

                <?php if (isset($_SESSION['flash_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                        <div><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php 
                $selectedRol = trim($_GET['rol'] ?? 'cliente');
                $isComercio = ($selectedRol === 'comercio');
                ?>
                <form action="register_process.php" method="POST" id="formRegistro" novalidate>
                    <?= csrfField() ?>
                    <!-- Selector de Rol Interactivo -->
                    <div class="mb-4">
                        <label class="form-label fw-bold fs-6 mb-1 text-dark d-flex align-items-center justify-content-between">
                            <span>¿Cómo vas a usar FoodRescue?</span>
                            <span class="badge bg-secondary-subtle text-secondary small fw-normal">Paso 1: Tu Perfil</span>
                        </label>
                        <p class="text-muted small mb-3">Elige si deseas comprar y rescatar comida con descuento o si eres dueño de un negocio gastronómico.</p>
                        
                        <div class="row g-3">
                            <!-- Opción A: Cliente -->
                            <div class="col-12 col-sm-6">
                                <input type="radio" class="btn-check" name="rol" id="rol_cliente" value="cliente" <?= !$isComercio ? 'checked' : '' ?> autocomplete="off">
                                <label class="card role-card h-100 p-3 rounded-3 text-start position-relative d-block shadow-sm" for="rol_cliente" id="card_rol_cliente">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle" style="width: 44px; height: 44px;">
                                            <i class="bi bi-person-heart fs-4"></i>
                                        </div>
                                        <span class="badge bg-success text-white small px-2 py-1 check-badge" id="badge_cliente" style="<?= $isComercio ? 'display:none;' : '' ?>">
                                            <i class="bi bi-check-lg me-1"></i> Seleccionado
                                        </span>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">👤 Soy un Cliente</h5>
                                    <p class="text-muted small mb-2">Quiero comprar y rescatar comida de comercios locales con hasta <strong>50% a 70% de descuento</strong>.</p>
                                    <div class="pt-2 border-top border-light-subtle small text-success fw-semibold">
                                        <i class="bi bi-bag-check-fill me-1"></i> Rescatista de alimentos
                                    </div>
                                </label>
                            </div>

                            <!-- Opción B: Comercio / Negocio -->
                            <div class="col-12 col-sm-6">
                                <input type="radio" class="btn-check" name="rol" id="rol_comercio" value="comercio" <?= $isComercio ? 'checked' : '' ?> autocomplete="off">
                                <label class="card role-card h-100 p-3 rounded-3 text-start position-relative d-block shadow-sm" for="rol_comercio" id="card_rol_comercio">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning-emphasis rounded-circle" style="width: 44px; height: 44px;">
                                            <i class="bi bi-shop fs-4"></i>
                                        </div>
                                        <span class="badge bg-warning text-dark small px-2 py-1 check-badge" id="badge_comercio" style="<?= !$isComercio ? 'display:none;' : '' ?>">
                                            <i class="bi bi-check-lg me-1"></i> Seleccionado
                                        </span>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">🏪 Tengo un Negocio</h5>
                                    <p class="text-muted small mb-2">Soy dueño/encargado de panadería, confitería o restaurante y quiero <strong>monetizar mis mermas</strong>.</p>
                                    <div class="pt-2 border-top border-light-subtle small text-warning-emphasis fw-semibold">
                                        <i class="bi bi-qr-code-scan me-1"></i> Comercio gastronómico
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Banner explicativo dinámico -->
                        <div id="rol-explanation" class="alert mt-3 mb-0 py-2 px-3 d-flex align-items-center rounded-3 <?= $isComercio ? 'alert-warning border-warning-subtle' : 'alert-success border-success-subtle' ?>">
                            <i class="bi <?= $isComercio ? 'bi-shop text-warning-emphasis' : 'bi-bag-check-fill text-success' ?> fs-5 me-2 flex-shrink-0" id="rol-icon"></i>
                            <div class="small" id="rol-text">
                                <?= $isComercio 
                                    ? '<strong>Perfil Negocio:</strong> Tendrás acceso a tu panel para publicar packs sorpresa con stock y horarios de retiro, y una caja para validar los códigos de los clientes.'
                                    : '<strong>Perfil Cliente:</strong> Podrás explorar los comercios de Itauguá, reservar packs sorpresa con descuento y retirarlos mostrando tu código secreto.' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Registro -->
                    <div class="border-top border-light-subtle pt-3 mb-3">
                        <label class="form-label fw-bold text-dark small mb-2 text-uppercase text-muted" style="letter-spacing: 0.5px;">Paso 2: Datos de la Cuenta</label>
                    </div>

                    <!-- Nombre / Comercio -->
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-semibold" id="lblNombre"><?= $isComercio ? 'Nombre del Comercio' : 'Nombre Completo' ?></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="<?= $isComercio ? 'Ej. Panadería & Confitería La Espiga' : 'Ej. Juan Pérez' ?>" required maxlength="100">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="nombre@correo.com" required maxlength="120">
                        </div>
                        <div class="form-text small">Será tu usuario para iniciar sesión en la plataforma.</div>
                    </div>

                    <div class="row g-2">
                        <!-- Teléfono -->
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label fw-semibold">Teléfono / WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="+595 981 123456" maxlength="20">
                            </div>
                        </div>
                        <!-- Dirección -->
                        <div class="col-md-6 mb-3">
                            <label for="direccion" class="form-label fw-semibold" id="lblDireccion"><?= $isComercio ? 'Dirección del Local Comercial' : 'Dirección / Barrio (Opcional)' ?></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" class="form-control" id="direccion" name="direccion" placeholder="<?= $isComercio ? 'Ej. Defensores del Chaco c/ Mcal. López, Itauguá' : 'Ej. Barrio San Antonio, Itauguá' ?>" maxlength="200">
                            </div>
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 6 caracteres" required minlength="6" maxlength="72">
                        </div>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label fw-semibold">Confirmar Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite tu contraseña" required minlength="6" maxlength="72">
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-3 d-flex align-items-center">
                        <i class="bi bi-shield-check text-success fs-5 me-2 flex-shrink-0"></i>
                        <span><strong>Seguridad Nivel Cloud:</strong> Contraseña protegida con cifrado criptográfico BCRYPT, tokens anti-CSRF y protección activa contra ataques de fuerza bruta.</span>
                    </div>

                    <button type="submit" class="btn btn-fr-primary w-100 py-2 fs-6 mb-3 shadow-sm">
                        <i class="bi bi-check-circle-fill me-1"></i> Registrar Cuenta en FoodRescue
                    </button>

                    <div class="text-center">
                        <span class="text-muted small">¿Ya tienes una cuenta registrada?</span>
                        <a href="login.php" class="text-decoration-none fw-semibold text-success small ms-1">Inicia sesión</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const radioCliente = document.getElementById('rol_cliente');
    const radioComercio = document.getElementById('rol_comercio');
    const badgeCliente = document.getElementById('badge_cliente');
    const badgeComercio = document.getElementById('badge_comercio');
    const lblNombre = document.getElementById('lblNombre');
    const inputNombre = document.getElementById('nombre');
    const lblDireccion = document.getElementById('lblDireccion');
    const inputDireccion = document.getElementById('direccion');
    const rolExplanation = document.getElementById('rol-explanation');
    const rolIcon = document.getElementById('rol-icon');
    const rolText = document.getElementById('rol-text');

    function updateRoleUI() {
        if (radioComercio.checked) {
            badgeCliente.style.display = 'none';
            badgeComercio.style.display = 'inline-block';
            lblNombre.textContent = 'Nombre Comercial del Negocio';
            inputNombre.placeholder = 'Ej. Panadería & Confitería La Espiga';
            lblDireccion.textContent = 'Dirección del Local Comercial (Itauguá)';
            inputDireccion.placeholder = 'Ej. Defensores del Chaco c/ Mcal. López, Itauguá';
            rolExplanation.className = 'alert mt-3 mb-0 py-2 px-3 d-flex align-items-center rounded-3 alert-warning border-warning-subtle';
            rolIcon.className = 'bi bi-shop text-warning-emphasis fs-5 me-2 flex-shrink-0';
            rolText.innerHTML = '<strong>Perfil Negocio:</strong> Tendrás acceso a tu panel para publicar packs, gestionar stock y validar compras con tu caja de validación.';
        } else {
            badgeCliente.style.display = 'inline-block';
            badgeComercio.style.display = 'none';
            lblNombre.textContent = 'Nombre y Apellido';
            inputNombre.placeholder = 'Ej. Juan Pérez';
            lblDireccion.textContent = 'Tu Dirección / Barrio (Opcional)';
            inputDireccion.placeholder = 'Ej. Barrio San Antonio, Itauguá';
            rolExplanation.className = 'alert mt-3 mb-0 py-2 px-3 d-flex align-items-center rounded-3 alert-success border-success-subtle';
            rolIcon.className = 'bi bi-bag-check-fill text-success fs-5 me-2 flex-shrink-0';
            rolText.innerHTML = '<strong>Perfil Cliente:</strong> Podrás explorar packs sorpresa en Itauguá, reservarlos al 50% de descuento y retirarlos en el local con tu código de canje.';
        }
    }

    radioCliente.addEventListener('change', updateRoleUI);
    radioComercio.addEventListener('change', updateRoleUI);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
