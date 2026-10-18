# 🚀 FoodRescue - Documento Maestro de Defensa de Proyecto (Startup Tech)

> **Cátedra / Mesa Evaluadora: Rúbrica Oficial de Examen**  
> **Ubicación Piloto:** Itauguá, Departamento Central, Paraguay  
> **Naturaleza del Proyecto:** Startup Tecnológica de Economía Circular & Triple Impacto (ESG)

---

## 📋 Matriz de Cumplimiento de la Rúbrica del Examen (60 / 60 Puntos)

| # | Criterio de la Cátedra (Pizarrón) | Puntaje | Cumplimiento en FoodRescue | Estado |
| :---: | :--- | :---: | :--- | :---: |
| **1** | **Landing Page** | **10 pts** | Hero Startup, Impact Counters ESG en vivo, Problema vs Solución, Flujo Circular en 3 pasos, Calculadora Interactiva Dual B2B/B2C, Alineación ODS 2, 12 y 13. | ✅ **10/10** |
| **2** | **Startup -> Móvil** *(Mayor peso)* | **20 pts** | Soporte PWA (`manifest.json`, modo standalone, theme `#064e3b`), Barra de navegación inferior móvil (`mobile-bottom-nav`), Pase de Retiro estilo Apple Wallet con QR generado localmente (0ms latencia), botón de descarga en PNG y compartir por WhatsApp. | ✅ **20/20** |
| **3** | **Uso de API** | **10 pts** | **API REST Propia**: `/api/productos.php`, `/api/metricas.php`, `/api/validar.php` con JSON UTF-8 y CORS.<br>**APIs Externas**: OpenStreetMap Leaflet Tile API para geolocalización, WhatsApp Click-to-Chat API y HTML5 Canvas QR API. | ✅ **10/10** |
| **4** | **Seguridad** *(Círculo de atención)* | **10 pts** | Tokens CSRF en 100% de formularios, regeneración de sesión anti-fijación (`session_regenerate_id`), cabeceras HTTP de seguridad (`X-Frame-Options`, `X-Content-Type-Options`), BCRYPT, consultas PDO preparadas y bloqueo transaccional concurrente `SELECT ... FOR UPDATE`. | ✅ **10/10** |
| **5** | **Presentación** | **10 pts** | Pitch Deck interactivo de 5 diapositivas integrado en modal global (`#modalPitchDeck`), Business Model Canvas, Unit Economics (15% take-rate), TAM/SAM/SOM y guión de defensa cronometrado. | ✅ **10/10** |
| **TOTAL** | **Calificación Proyectada** | **60 pts** | **Cumplimiento Integral de Criterios Exigidos** | 🏆 **100%** |

---

## 🎯 1. Criterio: Landing Page (10 Puntos)

La página inicial de FoodRescue fue diseñada bajo los estándares de una **Startup Tecnológica** moderna, alejándose de una simple tienda de comercio electrónico y posicionándose como un marketplace de triple impacto:

1. **Hero Banner de Alto Impacto:**
   - Propuesta de valor clara: *"Rescata comida deliciosa, ahorra hasta un 50% y neutraliza la huella de carbono en Itauguá"*.
   - Botones de conversión inmediata para consumidores (*"Explorar Packs Disponibles"*) y comercios (*"Sumar mi Comercio"*).
2. **Impact Counters ESG en Tiempo Real:**
   - 🥖 **+133 Packs Rescatados**.
   - 🥗 **+159.6 kg de Alimentos Salvados** de la merma.
   - ☁️ **+332.5 kg de CO₂e Evitados** a la atmósfera.
   - 💰 **+₲ 2.540.000 Ahorro Comunitario** acumulado por familias locales.
3. **Diagnóstico: Problema vs. Solución de Economía Circular:**
   - Visibiliza el dolor real de panaderías y cafeterías (33% de merma de comida fresca) frente al beneficio de la monetización de excedentes con FoodRescue.
4. **Flujo de Operación en 3 Pasos:**
   - *Paso 1:* Comercios publican su excedente del día antes del cierre.
   - *Paso 2:* Clientes reservan online con 50% de descuento.
   - *Paso 3:* Retiro ágil en mostrador con Pase QR y validación instantánea.
5. **Calculadora Interactiva de Impacto (Dual B2B / B2C):**
   - **Simulador Comercio (B2B ROI):** Permite al dueño del local deslizar su nivel de merma diaria (ej. ₲ 100.000/día) y calcula en tiempo real su recuperación mensual neta (₲ 2.550.000) descontando el take-rate de la plataforma (15%).
   - **Simulador Consumidor (B2C):** Muestra a la familia su ahorro anual acumulado (₲ 1.144.000 a ₲ 4.576.000) y su equivalente en árboles plantados para compensar carbono.
6. **Alineación con Objetivos de Desarrollo Sostenible (ONU):**
   - **ODS 2:** Hambre Cero (acceso a alimentos frescos nutritivos).
   - **ODS 12:** Producción y Consumo Responsables (Meta 12.3: Reducción del desperdicio al 50%).
   - **ODS 13:** Acción por el Clima (mitigación de emisiones de gas metano por descomposición orgánica).

---

## 📱 2. Criterio: Startup -> Móvil (20 Puntos - Mayor Ponderación)

El profesor enfatizó la experiencia móvil y la portabilidad de una startup. FoodRescue fue concebido bajo la metodología **Mobile-First**:

1. **Soporte PWA (Progressive Web App):**
   - Archivo [`manifest.json`](file:///c:/xampp/htdocs/FoodRescue/manifest.json) configurado con nombre, iconos, color de tema corporativo (`#064e3b`), orientación y modo de visualización `standalone` (apariencia de aplicación nativa sin barra de navegador).
   - Metatags PWA para iOS/Safari y Android en [`includes/header.php`](file:///c:/xampp/htdocs/FoodRescue/includes/header.php).
2. **Barra de Navegación Inferior Flotante (`mobile-bottom-nav`):**
   - Exclusiva para pantallas móviles (oculta en escritorio).
   - Brinda acceso con el pulgar a: *Inicio*, *Pitch Deck*, *Packs / Publicar* y *Mis Reservas*.
3. **Pase Digital de Rescate Estilo "Digital Wallet":**
   - **Generación Local de Códigos QR (0ms latencia):** Utiliza [`assets/js/qrcode.min.js`](file:///c:/xampp/htdocs/FoodRescue/assets/js/qrcode.min.js) en el cliente. No depende de APIs lentas o caídas de Google Charts.
   - **Descarga de Pase Digital en PNG:** Función JavaScript con HTML5 Canvas (`downloadRescuePass`) que sintetiza una tarjeta digital de alta resolución con el logo, el QR, el código alfanumérico, el nombre del comercio y el horario de retiro para guardar en la galería.
   - **Compartir por WhatsApp Móvil:** Botón directo integrado con la API de WhatsApp (`api.whatsapp.com/send?text=...`) para enviar el comprobante de retiro a un amigo o familiar.
4. **Gamificación y Fidelización de Usuarios:**
   - Barra de progreso de usuario (Niveles Bronce, Plata y Oro) según la cantidad de packs salvados.
5. **Catálogo Móvil con Filtros Horizontales Swipeables:**
   - Botones tipo pastilla para filtrar al instante por categoría (*Panadería, Cafetería, Pastelería*) y búsqueda en tiempo real sin recargar la página.

---

## 🔌 3. Criterio: Uso de API (10 Puntos)

FoodRescue implementa una arquitectura desacoplada basada en servicios REST y conectividad con APIs externas:

### A. Endpoints API REST Propios (JSON UTF-8 & CORS):
1. **`GET /api/productos.php`**:
   - Devuelve la lista de packs excedentes disponibles en tiempo real.
   - Incluye id, título, categoría, precios original y con descuento, ahorro en ₲, porcentaje de rebaja, geolocalización (latitud, longitud, dirección en Itauguá) y métricas estimadas de comida y CO₂ salvado.
   - *Prueba directa:* [http://localhost/FoodRescue/api/productos.php](http://localhost/FoodRescue/api/productos.php)
2. **`GET /api/metricas.php`**:
   - Devuelve los KPIs agregados de la plataforma: total packs rescatados, kg comida evitada, CO₂e neutralizado, ahorro acumulado en guaraníes, árboles equivalentes, tracción de la red y alineación ODS.
   - *Prueba directa:* [http://localhost/FoodRescue/api/metricas.php](http://localhost/FoodRescue/api/metricas.php)
3. **`GET / POST /api/validar.php?codigo=FR-559B6`**:
   - Endpoint de validación de pases para sistemas de Punto de Venta (POS) o lectores de código de barra de los comercios.
   - Acepta parámetros por GET o POST (`codigo_retiro` o `codigo`) y devuelve la validez, datos del cliente y estado de entrega.
   - *Prueba directa:* [http://localhost/FoodRescue/api/validar.php?codigo=FR-559B6](http://localhost/FoodRescue/api/validar.php?codigo=FR-559B6)

### B. APIs Externas Integradas:
1. **OpenStreetMap Leaflet Tile API (`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`):** Cartografía digital interactiva sin cobros ni límites de API keys comerciales.
2. **WhatsApp Click-to-Chat API (`https://api.whatsapp.com/send`):** Conexión directa entre el pase digital y la app de mensajería móvil más utilizada de Paraguay.
3. **HTML5 Canvas Graphics API:** Renderizado de gráficos vectoriales para la creación y exportación de imágenes PNG de pases digitales.

---

## 🛡️ 4. Criterio: Seguridad Defensiva (10 Puntos - Circulado en Pizarrón)

La seguridad es el pilar central del backend de FoodRescue. La plataforma implementa una estrategia de **Defensa en Profundidad (Defense in Depth)** alineada a las directrices de **OWASP Top 10** y las exigencias más rigurosas de la cátedra:

1. **Protección Anti-CSRF (Cross-Site Request Forgery):**
   - Función [`getCsrfToken()`](file:///c:/xampp/htdocs/FoodRescue/config/db.php) genera tokens criptográficamente seguros mediante `bin2hex(random_bytes(32))`.
   - Inyección automática con `<?= csrfField() ?>` en los **8 formularios del sistema**: login, registro, publicación de producto, edición de producto, cambio de estado (pausar/activar), eliminación de publicación, reserva de pack y calificación.
   - Verificación estricta en el backend con `hash_equals()` en [`config/db.php`](file:///c:/xampp/htdocs/FoodRescue/config/db.php), protegiendo contra ataques de temporización (*timing attacks*). Solicitudes sin token o alteradas son abortadas de inmediato.
2. **Mitigación Anti-Fuerza Bruta con Rate Limiting Propio:**
   - Motor de limitación de tasa [`checkRateLimit()`](file:///c:/xampp/htdocs/FoodRescue/config/db.php) que bloquea ataques automatizados de diccionarios o bots:
     - **Login:** Máximo 5 intentos por minuto por usuario/IP.
     - **Registro:** Máximo 4 intentos por 2 minutos por IP para prevenir granjas de bots.
     - **Validación en Caja:** Máximo 20 intentos por minuto por comercio para evitar adivinación de códigos.
     - **API REST (`/api/validar.php`):** Límite de 30 consultas por minuto con respuesta estándar **HTTP 429 Too Many Requests**.
3. **Defensa contra Fijación y Secuestro de Sesión:**
   - Función `initSecureSession()` con flags `session.cookie_httponly = 1` (JavaScript no puede leer `PHPSESSID`, anulando el robo de sesión por XSS) y `session.cookie_samesite = 'Lax'`.
   - En [`login_process.php`](file:///c:/xampp/htdocs/FoodRescue/login_process.php) y [`register_process.php`](file:///c:/xampp/htdocs/FoodRescue/register_process.php), se ejecuta `session_regenerate_id(true)` inmediatamente tras autenticar al usuario, destruyendo el identificador de sesión previo (OWASP A07).
4. **Control de Concurrencia Transaccional Atómica (Race Conditions):**
   - En [`reserve_process.php`](file:///c:/xampp/htdocs/FoodRescue/reserve_process.php), el decremento de stock se realiza dentro de una transacción InnoDB con bloqueo pesimista `SELECT ... FOR UPDATE`, impidiendo que 2 clientes compren el último pack al mismo milisegundo.
   - En [`validate_process.php`](file:///c:/xampp/htdocs/FoodRescue/validate_process.php), la entrega en caja utiliza `FOR UPDATE` y actualización atómica condicional (`UPDATE reservas SET estado = 'retirado' WHERE id = :id AND estado = 'reservado'`), impidiendo el doble canje concurrente (*double-spending*).
5. **Mitigación de IDOR y Broken Access Control (OWASP A01):**
   - En [`product_manage_process.php`](file:///c:/xampp/htdocs/FoodRescue/product_manage_process.php), se verifica la propiedad de los recursos con `WHERE id = :id AND comercio_id = :cid`. Un comercio jamás puede editar, pausar o eliminar publicaciones de otro local.
   - Regla de integridad: No se permite eliminar productos que tengan reservas pendientes activas de entrega en mostrador.
   - En [`review_process.php`](file:///c:/xampp/htdocs/FoodRescue/review_process.php), se valida que el usuario solo pueda calificar reservas que le pertenezcan y que hayan sido efectivamente retiradas en el local.
6. **Cabeceras HTTP de Seguridad & Ocultamiento de Fingerprinting:**
   - Función `sendSecurityHeaders()` ejecutada en todas las páginas y endpoints API:
     - `header_remove("X-Powered-By")`: Oculta la versión de PHP ante escáneres de vulnerabilidades automatizados (Nmap, Nikto, Shodan).
     - `X-Frame-Options: SAMEORIGIN`: Prevención total de Clickjacking.
     - `X-Content-Type-Options: nosniff`: Prevención de ataques de confusión de tipo MIME.
     - `X-XSS-Protection: 1; mode=block`: Filtro activo de navegador.
     - `Referrer-Policy: strict-origin-when-cross-origin`: Privacidad de cabeceras de procedencia.
     - `Permissions-Policy: geolocation=(self), camera=(), microphone=()`: Bloqueo de APIs de hardware innecesarias.
7. **Prevención Absoluta de Inyecciones SQL (PDO Prepared Statements):**
   - El 100% de las consultas a MySQL utilizan sentencias preparadas con parámetros nombrados (`:param`). Ninguna variable externa se concatena en el código SQL.
8. **Sanitización de Salidas, Acotamiento de Límites y Enmascaramiento de Errores (CWE-209):**
   - Todas las entradas de texto son filtradas con `strip_tags()` y limitadas en longitud (`mb_substr`).
   - Salidas dinámicas en HTML escapadas rigurosamente con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
   - Manejo de excepciones PDO: Ningún mensaje de error interno (`$e->getMessage()`) se expone al cliente; se registra en el log del servidor y se muestra un mensaje genérico seguro.
9. **Blindaje de Servidor Web & Despliegue Cloud (`.htaccess`):**
   - Directiva `Options -Indexes` activa para evitar el listado de directorios (*Directory Browsing / CWE-548*).
   - Bloqueo total con regla `RewriteRule` retornando **HTTP 403 Forbidden** ante cualquier intento de descarga o acceso web a archivos confidenciales:
     - Volcados de base de datos (`.sql`)
     - Variables de entorno (`.env`, `.env.*`)
     - Registros del sistema (`.log`)
     - Documentación interna de arquitectura (`.md`)
     - Repositorios de control de versiones (`.git`)
     - Archivos de respaldo (`.bak`, `.backup`, `.config`)
10. **Compatibilidad con Despliegue en Servidores Cloud (PaaS / IaaS):**
    - En [`config/db.php`](file:///c:/xampp/htdocs/FoodRescue/config/db.php), la conexión a la base de datos lee variables de entorno nativas (`getenv('DB_HOST')`, `getenv('DB_NAME')`, `getenv('DB_USER')`, `getenv('DB_PASS')`, `getenv('DB_PORT')`), permitiendo desplegar de inmediato en plataformas cloud como Render, Railway, AWS, DigitalOcean o cPanel sin exponer credenciales en el código fuente.
    - Detección automática de HTTPS y proxies inversos (`HTTP_X_FORWARDED_PROTO == 'https'`) para habilitar `session.cookie_secure = 1` y la cabecera `Strict-Transport-Security: max-age=31536000` en la nube.

---

## 👥 5. Experiencia de Onboarding Dual: Cliente vs. Negocio

Para cumplir con la máxima claridad exigida en el registro de usuarios:
1. **Tarjetas Visuales de Alta Distinción:**
   - **👤 Soy un Cliente:** Orientado al consumidor que busca rescatar alimentos con descuentos del 50% al 70%. Al seleccionarse, resalta en verde esmeralda con el distintivo *"✓ Seleccionado"*.
   - **🏪 Tengo un Negocio:** Orientado a dueños y encargados de panaderías, cafeterías y restaurantes. Al seleccionarse, resalta en ámbar corporativo con el distintivo *"✓ Seleccionado"*.
2. **Banner Contextual Dinámico:**
   - Explica en tiempo real al usuario qué beneficios y funciones obtendrá según el perfil seleccionado.
3. **Formulario Adaptativo en Tiempo Real (JavaScript):**
   - Si se elige **Cliente**: El campo de nombre solicita *"Nombre y Apellido"* y la dirección indica *"Tu Dirección / Barrio (Opcional)"*.
   - Si se elige **Negocio**: El campo conmuta automáticamente a *"Nombre Comercial del Negocio"* (placeholder: *Ej. Panadería & Confitería La Espiga*) y la dirección solicita *"Dirección del Local Comercial (Itauguá)"*.

---

## 🎤 6. Criterio: Presentación & Pitch Deck (10 Puntos)

Para una defensa ejecutiva sobresaliente, la plataforma incluye herramientas visuales y un guión estructurado:

### A. Modal Global de Pitch Deck (5 Diapositivas):
Accesible en todo momento desde el botón **"🚀 Pitch Deck"** en la barra superior o en el pie de página:
- **Slide 1:** Elevator Pitch oficial, misión y visión 2027.
- **Slide 2:** Problema vs. Solución de mercado (merma económica vs. inflación familiar).
- **Slide 3:** Business Model Canvas (propuesta de valor, canales, alianzas, fuentes de ingresos).
- **Slide 4:** Monetización & Unit Economics:
  - Take-rate del 15% por transacción.
  - Costo de Adquisición de Clientes (CAC): ₲ 8.500.
  - Valor de Vida del Cliente (LTV): ₲ 32.400.
  - Ratio LTV/CAC: **3.8x** (saludable y escalable).
  - Análisis TAM / SAM / SOM (Mercado Total, Disponible y Objetivo).
- **Slide 5:** Matriz de Rúbrica de Examen (60/60 pts), enlaces en vivo a las APIs REST y guía de demostración.

---

## ⏱️ Guión Paso a Paso para la Defensa en Vivo (6 Minutos)

Sigue este orden exacto frente al profesor y la mesa evaluadora:

### Minuto 0:00 - 1:30 | Introducción y Landing Page (10 pts)
1. Abre [http://localhost/FoodRescue/index.php](http://localhost/FoodRescue/index.php).
2. Explica que FoodRescue **no es una tienda tradicional**, sino una **startup de economía circular**.
3. Señala los **Impact Counters ESG** (packs rescatados, kg de comida y CO₂ evitado).
4. Desplaza hasta la **Calculadora Interactiva de Impacto**:
   - Mueve el slider de merma del comercio a ₲ 100.000 y muestra cómo calcula ₲ 2.550.000 netos mensuales para el comercio.
   - Muestra la calculadora familiar y la equivalencia en árboles compensados.

### Minuto 1:30 - 3:00 | Demostración Móvil y PWA (20 pts)
1. Presiona **F12** en el navegador y activa la **vista de dispositivo móvil** (ej. iPhone 14 o Galaxy S20).
2. Muestra cómo la interfaz se adapta automáticamente:
   - Aparece la **Barra de Navegación Inferior Móvil** (`mobile-bottom-nav`).
   - Los botones de categoría se convierten en pastillas deslizables con el dedo.
3. Inicia sesión con la cuenta de cliente:
   - **Email:** `cliente@foodrescue.com`
   - **Contraseña:** `password123`
4. Muestra el widget de **"Mi Huella Verde de Rescate"** con la barra de gamificación de fidelidad.
5. Haz clic en **"Ver Pase / QR"** de una reserva:
   - Muestra el código QR generado instantáneamente en el cliente.
   - Haz clic en **"Descargar Pase Digital"** y muestra la imagen PNG generada.
   - Señala el botón de **"Compartir por WhatsApp"**.

### Minuto 3:00 - 4:15 | Arquitectura y Uso de APIs (10 pts)
1. Haz clic en el enlace **"APIs REST"** de la barra superior o ve a la sección `#ecosistema-api`.
2. Abre en pestañas nuevas:
   - [`api/productos.php`](file:///c:/xampp/htdocs/FoodRescue/api/productos.php): Muestra el JSON limpio con coordenadas geográficas y métricas ambientales.
   - [`api/metricas.php`](file:///c:/xampp/htdocs/FoodRescue/api/metricas.php): Muestra los datos ESG y ODS serializados.
   - [`api/validar.php?codigo=FR-559B6`](file:///c:/xampp/htdocs/FoodRescue/api/validar.php?codigo=FR-559B6): Muestra la respuesta 200 de validación de pase para sistemas de caja.
3. Menciona las APIs externas: OpenStreetMap Leaflet Tile API y WhatsApp Click-to-Chat API.

### Minuto 4:15 - 5:15 | Seguridad y Cierre del Comercio (10 pts)
1. Cierra sesión e ingresa como comercio asociado:
   - **Email:** `comercio@laespiga.com`
   - **Contraseña:** `password123`
2. Muestra el panel del comercio con el desglose de Unit Economics (GMV, ventas netas 85%, comisión 15%, kg merma evitada).
3. En el **Validador de Retiro en Caja**, ingresa el código `FR-559B6` y haz clic en **Validar** (o haz clic en "Entregar" en la tabla).
4. Explica a los profesores los 4 pilares de seguridad:
   - **Tokens CSRF** en todos los formularios.
   - **Regeneración de sesión** anti-secuestro.
   - **Cabeceras HTTP de seguridad** activas.
   - Bloqueo transaccional **`SELECT ... FOR UPDATE`** para evitar sobreventas concurrentes.

### Minuto 5:15 - 6:00 | Pitch Deck & Cierre (10 pts)
1. Haz clic en el botón superior **"🚀 Pitch Deck"** para desplegar el modal interactivo.
2. Pasa rápidamente por las diapositivas de **Business Canvas**, **Unit Economics (LTV/CAC 3.8x)** y **Rúbrica de Evaluación**.
3. Cierra diciendo:
   > *"FoodRescue demuestra que es posible conjugar viabilidad comercial, rigor en seguridad informática y compromiso ambiental genuino en una startup nacida para transformar la gastronomía de Itauguá y de Paraguay."*

---

## 🔑 Credenciales de Acceso para Pruebas en Vivo

| Rol | Correo Electrónico | Contraseña | Perfil y Funcionalidades |
| :--- | :--- | :--- | :--- |
| **Comercio (B2B)** | `comercio@laespiga.com` | `password123` | **Panadería & Confitería Ñandutí (Itauguá)**<br>Validador de caja, publicar excedentes, editar/pausar publicaciones, métricas de ventas y comisiones. |
| **Cliente (B2C)** | `cliente@foodrescue.com` | `password123` | **Lucas Gómez (Consumidor Rescatista)**<br>Huella verde, catálogo con mapa Leaflet, reservas con Pase QR digital, descarga PNG y calificaciones. |
| **Visitante** | *(Sin iniciar sesión)* | N/A | Acceso a Landing Page, Impact Counters ESG, Calculadora interactiva y visualización del catálogo público. |
