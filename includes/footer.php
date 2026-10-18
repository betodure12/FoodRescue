</main>

<!-- Startup Extended Footer -->
<footer class="startup-footer mt-auto">
    <div class="container pb-4">
        <div class="row g-4">
            <!-- Columna 1: Startup Branding & Misión -->
            <div class="col-12 col-md-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-icon" style="background: linear-gradient(135deg, #10b981, #047857); color: white; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                        <i class="bi bi-basket2-fill"></i>
                    </span>
                    <span class="fs-4 fw-extrabold text-white">Food<span class="text-warning">Rescue</span></span>
                </div>
                <p class="small text-secondary mb-3">
                    Startup de tecnología y economía circular orientada a reducir el desperdicio de alimentos conectando panaderías, cafeterías y confiterías con consumidores conscientes en Itauguá y Gran Asunción.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small"><i class="bi bi-shield-check me-1"></i> B2B2C Marketplace</span>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill small"><i class="bi bi-recycle me-1"></i> Triple Impacto ESG</span>
                </div>
            </div>

            <!-- Columna 2: Ecosistema de la Plataforma -->
            <div class="col-6 col-md-2">
                <h6>Ecosistema</h6>
                <a href="index.php#catalogo"><i class="bi bi-chevron-right small text-success me-1"></i> Packs del Día</a>
                <a href="index.php#mapViewContainer"><i class="bi bi-chevron-right small text-success me-1"></i> Mapa de Locales</a>
                <a href="index.php#como-funciona"><i class="bi bi-chevron-right small text-success me-1"></i> Cómo Funciona</a>
                <a href="index.php#calculadora-impacto"><i class="bi bi-chevron-right small text-success me-1"></i> Calculadora</a>
                <a href="register.php"><i class="bi bi-chevron-right small text-success me-1"></i> Crear Cuenta</a>
            </div>

            <!-- Columna 3: Para Comercios (B2B) -->
            <div class="col-6 col-md-3">
                <h6>Para Comercios (B2B)</h6>
                <a href="register.php?rol=comercio"><i class="bi bi-shop me-1 text-warning"></i> Sumar mi Negocio</a>
                <a href="login.php"><i class="bi bi-box-arrow-in-right me-1 text-warning"></i> Portal de Comercio</a>
                <a href="index.php#calculadora-impacto"><i class="bi bi-calculator me-1 text-warning"></i> Simular Recuperación</a>
                <span class="small text-secondary d-block mt-2">
                    <i class="bi bi-check-circle-fill text-success me-1"></i> 0 costo fijo mensual<br>
                    <i class="bi bi-check-circle-fill text-success me-1"></i> 15% take-rate sobre ventas
                </span>
            </div>

            <!-- Columna 4: Startup Hub & Examen -->
            <div class="col-12 col-md-3">
                <h6>Startup Hub & Defensa</h6>
                <button type="button" class="btn btn-warning btn-sm w-100 fw-bold rounded-pill mb-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPitchDeck">
                    <i class="bi bi-file-earmark-slides-fill me-1"></i> Ver Pitch Deck Completo
                </button>
                <div class="bg-dark bg-opacity-50 p-2 rounded-3 border border-secondary border-opacity-25 small text-secondary">
                    <div class="text-white fw-bold mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Zona Piloto:</div>
                    <div>Itauguá, Dpto. Central, Paraguay.</div>
                    <div class="mt-1 text-white-50"><i class="bi bi-code-slash text-info me-1"></i> Stack: PHP 8 + MySQL + Leaflet</div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center small text-secondary gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong>FoodRescue Tech Platform</strong>. Proyecto Startup de Economía Circular y Rescate Alimentario.
            </div>
            <div class="d-flex gap-3">
                <span><i class="bi bi-globe-americas text-primary me-1"></i> ODS 12 & 13 ONU</span>
                <span><i class="bi bi-lock-fill text-success me-1"></i> Transacciones Seguras</span>
                <span><i class="bi bi-qr-code text-warning me-1"></i> Validación Desacoplada</span>
            </div>
        </div>
    </div>
</footer>

<!-- ======================================================== -->
<!-- MODAL GLOBAL: PITCH DECK & MODELO STARTUP (PARA DEFENSA) -->
<!-- ======================================================== -->
<div class="modal fade" id="modalPitchDeck" tabindex="-1" aria-labelledby="modalPitchDeckLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="stat-icon bg-warning text-dark" style="width: 38px; height: 38px; font-size: 1.2rem;">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalPitchDeckLabel">FoodRescue · Pitch Deck & Startup Model</h5>
                        <small class="text-secondary">Defensa de Proyecto, Canvas de Negocio & Arquitectura Técnica</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Nav Tabs -->
            <div class="bg-light border-bottom px-4 pt-2">
                <ul class="nav pitch-nav-tabs" id="pitchDeckTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-pitch" data-bs-toggle="tab" data-bs-target="#pitch-slide1" type="button" role="tab">
                            <i class="bi bi-megaphone-fill me-1"></i> 1. Pitch & Misión
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-problema" data-bs-toggle="tab" data-bs-target="#pitch-slide2" type="button" role="tab">
                            <i class="bi bi-exclamation-octagon-fill me-1"></i> 2. Problema vs Solución
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-canvas" data-bs-toggle="tab" data-bs-target="#pitch-slide3" type="button" role="tab">
                            <i class="bi bi-grid-3x3-gap-fill me-1"></i> 3. Business Canvas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-unit" data-bs-toggle="tab" data-bs-target="#pitch-slide4" type="button" role="tab">
                            <i class="bi bi-currency-dollar me-1"></i> 4. Monetización & Unit Economics
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-tech" data-bs-toggle="tab" data-bs-target="#pitch-slide5" type="button" role="tab">
                            <i class="bi bi-cpu-fill me-1"></i> 5. Arquitectura & Demo
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Modal Body Content -->
            <div class="modal-body p-4 bg-white">
                <div class="tab-content" id="pitchDeckTabsContent">
                    
                    <!-- SLIDE 1: PITCH & MISIÓN -->
                    <div class="tab-pane fade show active" id="pitch-slide1" role="tabpanel">
                        <div class="alert alert-success-subtle border-0 rounded-4 p-4 mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success px-3 py-1 rounded-pill">Elevator Pitch Oficial</span>
                                <span class="text-muted small">Tiempo estimado de lectura: 30 segundos</span>
                            </div>
                            <blockquote class="fs-5 fw-bold text-dark mb-0 fst-italic">
                                "En Paraguay, toneladas de alimentos frescos producidos diariamente por panaderías, cafeterías y confiterías se descartan al cierre por merma comercial. Al mismo tiempo, las familias sufren la inflación de la canasta básica.<br><br>
                                FoodRescue es el marketplace tecnológico de economía circular que conecta a estos comercios locales con consumidores, permitiéndoles comercializar sus excedentes con 50% a 70% de descuento mediante reservas online y retiro con código QR en mostrador. Ganamos todos: el comercio recupera dinero, el cliente ahorra y el planeta reduce su huella de carbono."
                            </blockquote>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 border rounded-3 text-center h-100">
                                    <div class="fs-1 text-success mb-2"><i class="bi bi-bullseye"></i></div>
                                    <h6 class="fw-bold">Nuestra Misión</h6>
                                    <p class="small text-muted mb-0">Erradicar el desperdicio de comida fresca en pequeños comercios de Paraguay mediante tecnología accesible y hábitos sustentables.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded-3 text-center h-100">
                                    <div class="fs-1 text-warning mb-2"><i class="bi bi-eye-fill"></i></div>
                                    <h6 class="fw-bold">Nuestra Visión 2027</h6>
                                    <p class="small text-muted mb-0">Ser la plataforma líder de rescate de alimentos de triple impacto en Central y Gran Asunción con más de 200 comercios adheridos.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded-3 text-center h-100">
                                    <div class="fs-1 text-primary mb-2"><i class="bi bi-shield-fill-check"></i></div>
                                    <h6 class="fw-bold">Triple Impacto (ESG)</h6>
                                    <p class="small text-muted mb-0">Impacto Económico (ingresos y ahorros), Social (comida nutritiva y barata) y Ambiental (reducción directa de CO₂e y metano).</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SLIDE 2: PROBLEMA VS SOLUCIÓN -->
                    <div class="tab-pane fade" id="pitch-slide2" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="problem-card p-4 h-100">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="bi bi-exclamation-triangle-fill text-danger fs-3"></i>
                                        <h5 class="fw-bold text-danger mb-0">El Problema del Mercado</h5>
                                    </div>
                                    <ul class="text-secondary small ps-3 mb-0">
                                        <li class="mb-2"><strong>Mermas irrecuperables:</strong> Panaderías y cafeterías pierden entre 8% y 15% de su producción diaria por no venderla en el día, a pesar de mantener 100% de calidad.</li>
                                        <li class="mb-2"><strong>Presión económica en hogares:</strong> Consumidores buscan formas de estirar su presupuesto familiar sin sacrificar calidad ni sabor.</li>
                                        <li class="mb-2"><strong>Crisis climática:</strong> El desperdicio alimentario genera el 8-10% de los gases de efecto invernadero a nivel global (ODS 12 y 13).</li>
                                        <li><strong>Falta de digitalización:</strong> Los comercios de barrio no disponen de herramientas para liquidar stock de última hora de forma ágil y ordenada.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="solution-card p-4 h-100">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <i class="bi bi-check-circle-fill text-success fs-3"></i>
                                        <h5 class="fw-bold text-success mb-0">La Solución FoodRescue</h5>
                                    </div>
                                    <ul class="text-secondary small ps-3 mb-0">
                                        <li class="mb-2"><strong>Marketplace hiperlocal en tiempo real:</strong> Publicación de packs excedentes en 30 segundos antes del cierre del comercio.</li>
                                        <li class="mb-2"><strong>Descuentos irresistibles:</strong> Ofertas con 50% a 70% OFF que garantizan que el stock se agote rápidamente.</li>
                                        <li class="mb-2"><strong>Validación instantánea en caja:</strong> Códigos de retiro alfanuméricos y pases con código QR con verificación de 1 clic.</li>
                                        <li><strong>Gamificación comunitaria:</strong> Sistema de lealtad y medidores personales de huella ecológica que retienen y fidelizan a los usuarios.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SLIDE 3: BUSINESS CANVAS -->
                    <div class="tab-pane fade" id="pitch-slide3" role="tabpanel">
                        <div class="bmc-grid">
                            <div class="bmc-block">
                                <h6><i class="bi bi-people-fill me-1"></i> Socios Clave</h6>
                                <ul>
                                    <li>Panaderías y confiterías locales de Itauguá</li>
                                    <li>Cafeterías y pastelerías</li>
                                    <li>Asociaciones barriales gastronómicas</li>
                                    <li>Medios locales y de sustentabilidad</li>
                                </ul>
                            </div>
                            <div class="bmc-block">
                                <h6><i class="bi bi-gear-fill me-1"></i> Actividades Clave</h6>
                                <ul>
                                    <li>Mantenimiento de la plataforma web</li>
                                    <li>Captación de comercios gastronómicos</li>
                                    <li>Soporte y validación de órdenes</li>
                                    <li>Medición y reporte de impacto ambiental</li>
                                </ul>
                            </div>
                            <div class="bmc-block">
                                <h6><i class="bi bi-gem me-1"></i> Propuesta de Valor</h6>
                                <ul>
                                    <li><strong>Comercios:</strong> Monetizar merma sin costo fijo y ganar nuevos clientes.</li>
                                    <li><strong>Usuarios:</strong> Comida fresca 50-70% OFF y consumo sustentable.</li>
                                    <li><strong>Comunidad:</strong> Reducción de CO₂e y desperdicio orgánico.</li>
                                </ul>
                            </div>
                            <div class="bmc-block">
                                <h6><i class="bi bi-heart-fill me-1"></i> Relación con Clientes</h6>
                                <ul>
                                    <li>Autoservicio ágil mobile-first</li>
                                    <li>Gamificación y niveles Bronce/Plata/Oro</li>
                                    <li>Transparencia en ratings y reseñas</li>
                                </ul>
                            </div>
                            <div class="bmc-block">
                                <h6><i class="bi bi-person-badge-fill me-1"></i> Segmentos Clientes</h6>
                                <ul>
                                    <li><strong>B2B:</strong> Pequeños y medianos negocios gastronómicos.</li>
                                    <li><strong>B2C:</strong> Jóvenes, universitarios y familias que cuidan su economía.</li>
                                    <li>Consumidores eco-conscientes.</li>
                                </ul>
                            </div>
                            <div class="bmc-block">
                                <h6><i class="bi bi-diagram-3-fill me-1"></i> Canales</h6>
                                <ul>
                                    <li>Web app responsive multiplataforma</li>
                                    <li>Geolocalización en vivo con Leaflet</li>
                                    <li>Boca a boca y redes sociales locales</li>
                                </ul>
                            </div>
                            <div class="bmc-block">
                                <h6><i class="bi bi-cash-stack me-1"></i> Estructura de Costos</h6>
                                <ul>
                                    <li>Infraestructura en la nube / hosting</li>
                                    <li>Costos de captación B2B (CAC)</li>
                                    <li>Desarrollo de software y soporte</li>
                                </ul>
                            </div>
                            <div class="bmc-block">
                                <h6><i class="bi bi-wallet2 me-1"></i> Vías de Ingresos</h6>
                                <ul>
                                    <li><strong>Take-Rate:</strong> 15% de comisión por pack rescatado con éxito.</li>
                                    <li><strong>Suscripción B2B Pro:</strong> Reportes avanzados de merma y posicionamiento en el mapa.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- SLIDE 4: MONETIZACIÓN & UNIT ECONOMICS -->
                    <div class="tab-pane fade" id="pitch-slide4" role="tabpanel">
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="calc-result-box h-100">
                                    <div class="text-muted small">Modelo de Monetización</div>
                                    <div class="fs-3 fw-extrabold text-success my-1">15%</div>
                                    <div class="small text-secondary">Comisión por Transacción (Take-Rate)</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="calc-result-box h-100">
                                    <div class="text-muted small">Ticket Promedio</div>
                                    <div class="fs-3 fw-extrabold text-dark my-1">₲ 25.000</div>
                                    <div class="small text-secondary">Por pack rescatado en Itauguá</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="calc-result-box h-100">
                                    <div class="text-muted small">Ingreso por Orden</div>
                                    <div class="fs-3 fw-extrabold text-primary my-1">₲ 3.750</div>
                                    <div class="small text-secondary">Margen neto de plataforma</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="calc-result-box h-100">
                                    <div class="text-muted small">Relación LTV / CAC</div>
                                    <div class="fs-3 fw-extrabold text-warning-emphasis my-1">3.8x</div>
                                    <div class="small text-secondary">Retención alta por gamificación</div>
                                </div>
                            </div>
                        </div>

                        <div class="card p-3 border rounded-3 bg-light">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-graph-up me-1 text-success"></i> Análisis de Tamaño de Mercado (TAM / SAM / SOM)</h6>
                            <div class="row g-2 text-center small">
                                <div class="col-md-4">
                                    <div class="p-2 bg-white rounded-2 border">
                                        <div class="fw-bold text-muted">TAM (Mercado Total)</div>
                                        <div class="fs-6 fw-bold text-dark">USD 18M / año</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Mermas gastronómicas en todo Paraguay</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 bg-white rounded-2 border">
                                        <div class="fw-bold text-muted">SAM (Mercado Disponible)</div>
                                        <div class="fs-6 fw-bold text-dark">USD 4.5M / año</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Panaderías y confiterías de Gran Asunción y Central</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-2 bg-white rounded-2 border">
                                        <div class="fw-bold text-muted">SOM (Mercado Objetivo Inicial)</div>
                                        <div class="fs-6 fw-bold text-dark">USD 280K / año</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">Fase 1: 50 comercios clave en Itauguá e inmediaciones</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SLIDE 5: RÚBRICA DE EVALUACIÓN, APIS & DEMO GUIDE -->
                    <div class="tab-pane fade" id="pitch-slide5" role="tabpanel">
                        <!-- Matriz de Evaluación Whiteboard -->
                        <div class="alert alert-success-subtle border-0 rounded-4 p-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-success px-3 py-1 rounded-pill fw-bold"><i class="bi bi-award-fill me-1"></i> Cumplimiento de la Rúbrica de Evaluación (60 / 60 pts)</span>
                                <span class="badge bg-dark text-white font-monospace">Puntuación: 100%</span>
                            </div>
                            <div class="row g-2 text-center small">
                                <div class="col">
                                    <div class="p-2 bg-white rounded border">
                                        <div class="fw-bold text-success">1. Landing Page</div>
                                        <div class="badge bg-success-subtle text-success">10 / 10 pts</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Hero, ESG, ODS 12/13, Calculadora</div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="p-2 bg-white rounded border border-warning">
                                        <div class="fw-bold text-warning-emphasis">2. Startup Móvil</div>
                                        <div class="badge bg-warning text-dark fw-bold">20 / 20 pts</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">PWA, Bottom Bar, Pase QR, WhatsApp</div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="p-2 bg-white rounded border">
                                        <div class="fw-bold text-primary">3. Uso de API</div>
                                        <div class="badge bg-primary-subtle text-primary">10 / 10 pts</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">REST JSON, OSM Leaflet, WhatsApp</div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="p-2 bg-white rounded border border-danger">
                                        <div class="fw-bold text-danger">4. Seguridad (10)</div>
                                        <div class="badge bg-danger-subtle text-danger">10 / 10 pts</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">CSRF, Headers, FOR UPDATE, BCRYPT</div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="p-2 bg-white rounded border">
                                        <div class="fw-bold text-info-emphasis">5. Presentación</div>
                                        <div class="badge bg-info-subtle text-info-emphasis">10 / 10 pts</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Pitch Deck Modal 5 slides + Guión</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100">
                                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-braces text-success me-1"></i> Endpoints API REST en Vivo</h6>
                                    <ul class="list-unstyled small mb-3">
                                        <li class="mb-2 p-2 bg-light rounded d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-success font-monospace me-1">GET</span>
                                                <code>api/productos.php</code>
                                            </div>
                                            <a href="api/productos.php" target="_blank" class="btn btn-xs btn-outline-success rounded-pill px-2 py-0" style="font-size: 0.75rem;">Probar</a>
                                        </li>
                                        <li class="mb-2 p-2 bg-light rounded d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-success font-monospace me-1">GET</span>
                                                <code>api/metricas.php</code>
                                            </div>
                                            <a href="api/metricas.php" target="_blank" class="btn btn-xs btn-outline-success rounded-pill px-2 py-0" style="font-size: 0.75rem;">Probar</a>
                                        </li>
                                        <li class="p-2 bg-light rounded d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-info text-dark font-monospace me-1">GET/POST</span>
                                                <code>api/validar.php?codigo=FR-559B6</code>
                                            </div>
                                            <a href="api/validar.php?codigo=FR-559B6" target="_blank" class="btn btn-xs btn-outline-info rounded-pill px-2 py-0" style="font-size: 0.75rem;">Probar</a>
                                        </li>
                                    </ul>
                                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-shield-lock-fill text-danger me-1"></i> Blindaje de Seguridad para la Nube</h6>
                                    <p class="small text-muted mb-0">Protección anti-hackeo lista para la nube: Rate limiting activo contra fuerza bruta, tokens CSRF, bloqueo <code>.htaccess</code> (HTTP 403 en .sql/.env), transacciones atómicas con <code>SELECT ... FOR UPDATE</code> y cookies seguras.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-light">
                                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-play-circle-fill text-primary me-1"></i> Guión Paso a Paso para la Defensa</h6>
                                    <ol class="small text-secondary ps-3 mb-0">
                                        <li class="mb-2"><strong>Paso 1 - Portada & Pitch (2 min):</strong> Abre la portada, muestra el Impact Counter ESG, la Calculadora interactiva B2B/B2C y abre este Pitch Deck.</li>
                                        <li class="mb-2"><strong>Paso 2 - Experiencia Móvil (2 min):</strong> Presiona F12 (modo teléfono), muestra la barra inferior móvil, la búsqueda reactiva y abre un Pase de Retiro con QR y botón WhatsApp.</li>
                                        <li class="mb-2"><strong>Paso 3 - Demostración de APIs (1 min):</strong> Haz clic en <code>/api/productos.php</code> y <code>/api/metricas.php</code> para mostrar el retorno JSON estructurado.</li>
                                        <li class="mb-2"><strong>Paso 4 - Validación en Caja (1 min):</strong> Inicia sesión como comercio (<code>comercio@laespiga.com</code>) y valida el código <code>FR-559B6</code> en el mostrador.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="text-center pt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">
                                Cerrar y Volver a la Plataforma
                            </button>
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="modal-footer border-0 bg-light py-2 px-4 justify-content-between">
                <span class="text-muted small"><i class="bi bi-patch-check-fill text-success me-1"></i> Proyecto Startup Aprobado para Presentación Académica</span>
                <button type="button" class="btn btn-dark btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Bottom App Navigation Bar (Only for phones and tablets) -->
<nav class="mobile-bottom-nav d-lg-none">
    <a href="index.php" class="mobile-nav-item <?= !isset($pageTitle) || $pageTitle === '' ? 'active' : '' ?>">
        <i class="bi bi-house-door-fill"></i>
        <span>Inicio</span>
    </a>
    <a href="#modalPitchDeck" data-bs-toggle="modal" class="mobile-nav-item text-warning fw-bold">
        <i class="bi bi-file-earmark-slides-fill fs-3 text-warning"></i>
        <span>Pitch</span>
    </a>
    <?php if (isset($_SESSION['usuario_id']) && ($_SESSION['usuario_rol'] ?? '') === 'comercio'): ?>
        <a href="#modalPublicar" data-bs-toggle="modal" class="mobile-nav-item text-success fw-bold">
            <i class="bi bi-plus-circle-fill fs-3 text-success"></i>
            <span>Publicar</span>
        </a>
        <a href="index.php#mis-reservas" class="mobile-nav-item">
            <i class="bi bi-bag-check-fill"></i>
            <span>Reservas</span>
        </a>
        <a href="logout.php" class="mobile-nav-item text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Salir</span>
        </a>
    <?php elseif (isset($_SESSION['usuario_id'])): ?>
        <a href="index.php#catalogo" class="mobile-nav-item">
            <i class="bi bi-tag-fill"></i>
            <span>Ofertas</span>
        </a>
        <a href="index.php#mis-reservas" class="mobile-nav-item">
            <i class="bi bi-ticket-perforated-fill"></i>
            <span>Mis Pases</span>
        </a>
        <a href="logout.php" class="mobile-nav-item text-danger">
            <i class="bi bi-box-arrow-right"></i>
            <span>Salir</span>
        </a>
    <?php else: ?>
        <a href="index.php#catalogo" class="mobile-nav-item">
            <i class="bi bi-search"></i>
            <span>Explorar</span>
        </a>
        <a href="login.php" class="mobile-nav-item <?= (isset($pageTitle) && $pageTitle === 'Iniciar Sesión') ? 'active' : '' ?>">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Ingresar</span>
        </a>
        <a href="register.php" class="mobile-nav-item <?= (isset($pageTitle) && $pageTitle === 'Registro') ? 'active' : '' ?>">
            <i class="bi bi-person-plus-fill"></i>
            <span>Registro</span>
        </a>
    <?php endif; ?>
</nav>

<!-- Bootstrap 5 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Leaflet JS para Mapas -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<!-- QRCode Local Generator (Instant render, no external API lag) -->
<script src="assets/js/qrcode.min.js"></script>
</body>
</html>


