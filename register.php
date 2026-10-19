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
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>" placeholder="nombre@correo.com" required maxlength="120" autocomplete="email">
                            <span class="input-group-text bg-light" id="email-spinner" style="display:none;">
                                <span class="spinner-border spinner-border-sm text-secondary" role="status"></span>
                            </span>
                        </div>
                        <div id="email-feedback" class="mt-1 small"></div>
                        <div class="form-text small text-muted">Será tu usuario único para iniciar sesión en la plataforma.</div>
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

                    <!-- Mapa Interactivo para Comercios: Selector de Ubicación Exacta -->
                    <input type="hidden" name="latitud" id="inputLatitud" value="">
                    <input type="hidden" name="longitud" id="inputLongitud" value="">
                    <div id="mapPickerSection" class="mb-3" style="<?= $isComercio ? '' : 'display:none;' ?>">
                        <label class="form-label fw-semibold d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-pin-map-fill text-danger me-1"></i> Ubicación Exacta del Local en el Mapa</span>
                            <span class="badge bg-danger-subtle text-danger small fw-normal">Requerido para Comercios</span>
                        </label>
                        <p class="text-muted small mb-2">Haz clic en el mapa o usa el botón GPS para marcar la ubicación exacta de tu negocio. Los clientes te encontrarán en este punto.</p>
                        
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" id="btnGeolocate" class="btn btn-outline-success btn-sm rounded-pill px-3" onclick="autoDetectLocation()">
                                <i class="bi bi-crosshair me-1"></i> Usar mi GPS / Ubicación Actual
                            </button>
                            <span id="geoStatus" class="text-muted small align-self-center"></span>
                        </div>

                        <div class="input-group input-group-sm mb-2 shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-google text-danger"></i></span>
                            <input type="text" id="regGoogleMapsInput" class="form-control border-start-0 border-end-0" placeholder="O pega enlace de Google Maps o coordenadas (-25.3870, -57.3400)" oninput="parsearLinkGoogleMapsRegistro(this.value)">
                            <button type="button" class="btn btn-outline-primary fw-semibold" onclick="parsearLinkGoogleMapsRegistro(document.getElementById('regGoogleMapsInput').value)">
                                <i class="bi bi-pin-map-fill me-1"></i> Fijar Pin
                            </button>
                        </div>

                        <div id="regMap" style="height: 280px; border-radius: 12px; border: 2px solid #e2e8f0; z-index: 1;"></div>
                        <div id="mapCoordsFeedback" class="mt-1 small"></div>
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
                        <div id="password-match-feedback" class="mt-1 small"></div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-3 d-flex align-items-center">
                        <i class="bi bi-shield-check text-success fs-5 me-2 flex-shrink-0"></i>
                        <span><strong>Seguridad Nivel Cloud:</strong> Contraseña protegida con cifrado criptográfico BCRYPT, verificación anti-duplicados y tokens anti-CSRF.</span>
                    </div>

                    <button type="submit" id="btnSubmitRegistro" class="btn btn-fr-primary w-100 py-2 fs-6 mb-3 shadow-sm">
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
    const mapPickerSection = document.getElementById('mapPickerSection');

    // Elementos de validación en tiempo real
    const inputEmail = document.getElementById('email');
    const emailFeedback = document.getElementById('email-feedback');
    const emailSpinner = document.getElementById('email-spinner');
    const inputPassword = document.getElementById('password');
    const inputPasswordConfirm = document.getElementById('password_confirm');
    const passwordFeedback = document.getElementById('password-match-feedback');
    const btnSubmit = document.getElementById('btnSubmitRegistro');
    const formRegistro = document.getElementById('formRegistro');

    let emailValido = true;
    let emailTimer = null;

    // === Mapa Picker para Comercios ===
    let regMap = null;
    let regMarker = null;
    const inputLat = document.getElementById('inputLatitud');
    const inputLng = document.getElementById('inputLongitud');
    const mapFeedback = document.getElementById('mapCoordsFeedback');
    const geoStatus = document.getElementById('geoStatus');

    function initRegMap() {
        if (regMap) return; // ya inicializado
        if (typeof L === 'undefined') return;

        // Centrado en Itauguá, Central, Paraguay
        regMap = L.map('regMap').setView([-25.3870, -57.3400], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(regMap);

        // Pin personalizado verde FoodRescue
        const pinIcon = L.divIcon({
            className: 'custom-map-pin',
            html: `<div style="background:#dc2626; color:white; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(220,38,38,0.5); border:3px solid white; font-size:18px;"><i class="bi bi-shop"></i></div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        // Click en el mapa para colocar el pin
        regMap.on('click', function(e) {
            placeMarker(e.latlng.lat, e.latlng.lng, pinIcon);
        });

        // Si ya hay coords guardadas (ej. volver atrás), colocar el pin
        if (inputLat.value && inputLng.value) {
            placeMarker(parseFloat(inputLat.value), parseFloat(inputLng.value), pinIcon);
        }
    }

    function placeMarker(lat, lng, icon) {
        if (!regMap) return;
        if (regMarker) {
            regMarker.setLatLng([lat, lng]);
        } else {
            const pinIcon = icon || L.divIcon({
                className: 'custom-map-pin',
                html: `<div style="background:#dc2626; color:white; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(220,38,38,0.5); border:3px solid white; font-size:18px;"><i class="bi bi-shop"></i></div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            });
            regMarker = L.marker([lat, lng], { icon: pinIcon, draggable: true }).addTo(regMap);
            // Permitir arrastrar el pin para ajustar posición
            regMarker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                updateCoords(pos.lat, pos.lng);
            });
        }
        regMap.setView([lat, lng], 17);
        updateCoords(lat, lng);
    }

    function updateCoords(lat, lng) {
        inputLat.value = lat.toFixed(7);
        inputLng.value = lng.toFixed(7);
        if (mapFeedback) {
            mapFeedback.innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> Ubicación marcada: ${lat.toFixed(5)}, ${lng.toFixed(5)} — Los clientes verán tu local aquí en el mapa.</span>`;
        }
    }

    function autoDetectLocation() {
        if (!navigator.geolocation) {
            geoStatus.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i> Tu navegador no soporta geolocalización.</span>';
            return;
        }
        geoStatus.innerHTML = '<span class="text-primary"><i class="bi bi-hourglass-split me-1"></i> Detectando ubicación...</span>';
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                geoStatus.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> ¡GPS detectado!</span>';
                
                if (!regMap) initRegMap();
                setTimeout(() => {
                    if (regMap) regMap.invalidateSize();
                    placeMarker(lat, lng);
                }, 200);
            },
            function(error) {
                let msg = 'No se pudo obtener tu ubicación.';
                if (error.code === 1) msg = 'Permiso de ubicación denegado. Activa los permisos de GPS en tu navegador.';
                if (error.code === 2) msg = 'No se pudo determinar tu posición. Intenta en exteriores.';
                if (error.code === 3) msg = 'La solicitud de GPS tardó demasiado. Intenta de nuevo.';
                geoStatus.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i> ${msg}</span>`;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    }

    function parsearLinkGoogleMapsRegistro(text) {
        if (!text) return;
        text = text.trim();
        let lat = null;
        let lng = null;

        let m = text.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
        if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }
        if (!lat) {
            m = text.match(/(?:q|ll|destination)=(-?\d+\.\d+),(-?\d+\.\d+)/);
            if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }
        }
        if (!lat) {
            m = text.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
            if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }
        }

        if (lat !== null && lng !== null && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            if (!regMap) initRegMap();
            setTimeout(() => {
                placeMarker(lat, lng);
                if (mapFeedback) {
                    mapFeedback.innerHTML = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ¡Google Maps vinculado!: ${lat.toFixed(5)}, ${lng.toFixed(5)}</span>`;
                }
            }, 150);
        }
    }

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
            
            // Mostrar mapa picker
            mapPickerSection.style.display = '';
            setTimeout(() => {
                initRegMap();
                if (regMap) regMap.invalidateSize();
            }, 300);
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
            
            // Ocultar mapa picker
            mapPickerSection.style.display = 'none';
        }
    }

    radioCliente.addEventListener('change', updateRoleUI);
    radioComercio.addEventListener('change', updateRoleUI);

    // Inicializar mapa si ya viene como comercio
    if (radioComercio.checked) {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                initRegMap();
                if (regMap) regMap.invalidateSize();
            }, 500);
        });
    }

    // Verificación de disponibilidad de correo en tiempo real (anti-duplicados)
    function verificarEmailEnServidor(email) {
        if (!email || !email.includes('@') || email.length < 5) {
            emailFeedback.innerHTML = '';
            inputEmail.classList.remove('is-invalid', 'is-valid');
            emailValido = true;
            btnSubmit.disabled = false;
            return;
        }

        emailSpinner.style.display = 'block';

        fetch('api/check_email.php?email=' + encodeURIComponent(email))
            .then(res => res.json())
            .then(data => {
                emailSpinner.style.display = 'none';
                if (data.exists) {
                    emailValido = false;
                    inputEmail.classList.add('is-invalid');
                    inputEmail.classList.remove('is-valid');
                    emailFeedback.innerHTML = `
                        <div class="alert alert-warning py-2 px-3 mt-1 mb-0 rounded-3 d-flex align-items-center justify-content-between shadow-sm">
                            <div class="small">
                                <i class="bi bi-exclamation-triangle-fill text-warning-emphasis me-1"></i>
                                <strong>Este correo ya está registrado en FoodRescue.</strong>
                            </div>
                            <a href="login.php?email=${encodeURIComponent(email)}" class="btn btn-sm btn-dark text-nowrap ms-2 fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                            </a>
                        </div>
                    `;
                    btnSubmit.disabled = true;
                } else if (data.valid) {
                    emailValido = true;
                    inputEmail.classList.remove('is-invalid');
                    inputEmail.classList.add('is-valid');
                    emailFeedback.innerHTML = `
                        <div class="text-success small fw-semibold mt-1">
                            <i class="bi bi-check-circle-fill me-1"></i> Correo disponible para crear tu cuenta
                        </div>
                    `;
                    btnSubmit.disabled = false;
                }
            })
            .catch(() => {
                emailSpinner.style.display = 'none';
            });
    }

    inputEmail.addEventListener('input', () => {
        clearTimeout(emailTimer);
        emailTimer = setTimeout(() => {
            verificarEmailEnServidor(inputEmail.value.trim());
        }, 350);
    });

    inputEmail.addEventListener('blur', () => {
        verificarEmailEnServidor(inputEmail.value.trim());
    });

    // Verificación de coincidencia de contraseñas en vivo
    function verificarPasswords() {
        const p1 = inputPassword.value;
        const p2 = inputPasswordConfirm.value;

        if (!p2) {
            passwordFeedback.innerHTML = '';
            inputPasswordConfirm.classList.remove('is-invalid', 'is-valid');
            return true;
        }

        if (p1 === p2) {
            passwordFeedback.innerHTML = '<span class="text-success small fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> Las contraseñas coinciden</span>';
            inputPasswordConfirm.classList.remove('is-invalid');
            inputPasswordConfirm.classList.add('is-valid');
            return true;
        } else {
            passwordFeedback.innerHTML = '<span class="text-danger small fw-semibold"><i class="bi bi-x-circle-fill me-1"></i> Las contraseñas no coinciden</span>';
            inputPasswordConfirm.classList.add('is-invalid');
            inputPasswordConfirm.classList.remove('is-valid');
            return false;
        }
    }

    inputPassword.addEventListener('input', verificarPasswords);
    inputPasswordConfirm.addEventListener('input', verificarPasswords);

    formRegistro.addEventListener('submit', (e) => {
        if (!emailValido) {
            e.preventDefault();
            inputEmail.focus();
            return;
        }
        if (!verificarPasswords()) {
            e.preventDefault();
            inputPasswordConfirm.focus();
            return;
        }
        // Validar que comercio tenga ubicación marcada
        if (radioComercio.checked && (!inputLat.value || !inputLng.value)) {
            e.preventDefault();
            alert('Por favor marca la ubicación exacta de tu local en el mapa antes de registrarte. Puedes hacer clic en el mapa o usar el botón "Usar mi GPS".');
            document.getElementById('regMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
    });

    // Si viene email en la URL, verificarlo de inmediato
    if (inputEmail.value.trim() !== '') {
        verificarEmailEnServidor(inputEmail.value.trim());
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
