# FoodRescue 🥗🥖

Plataforma web para la gestión de excedentes de comida en pequeños comercios locales (panaderías, cafeterías, pastelerías) para combatir el desperdicio de alimentos y fomentar el consumo sustentable.

---

## 🚀 Requisitos y Tecnologías
- **PHP 8.x** con extensión **PDO MySQL** activada.
- **MySQL / MariaDB** (vía XAMPP).
- **Bootstrap 5** & **Bootstrap Icons** (CDN).
- **HTML5 & CSS3** responsive (móvil y PC).

---

## 🗄️ 1. Estructura de la Base de Datos
El esquema y los datos iniciales de prueba están en [`database.sql`](file:///c:/xampp/htdocs/FoodRescue/database.sql).

### Tablas incluidas:
1. `usuarios`: Gestión de cuentas con roles (`cliente` o `comercio`) y passwords encriptados con `password_hash()`.
2. `productos`: Publicaciones de excedentes con stock, horario de retiro y descuentos.
3. `reservas`: Reservas de clientes con generación de código de retiro único.
4. `puntos_lealtad`: Gamificación y fidelización para clientes según sus compras de rescate.

### Importar la base de datos:
1. Abre **phpMyAdmin** en `http://localhost/phpmyadmin` o la terminal de MySQL en XAMPP.
2. Crea la base de datos `foodrescue_db` o ejecuta el contenido de [`database.sql`](file:///c:/xampp/htdocs/FoodRescue/database.sql).

---

## 🔑 Usuarios de Prueba (Seed Data)
| Rol | Correo Electrónico | Contraseña |
| :--- | :--- | :--- |
| **Comercio (Panadería)** | `comercio@laespiga.com` | `password123` |
| **Cliente** | `cliente@foodrescue.com` | `password123` |

---

## 📂 Estructura de Archivos
- [`config/db.php`](file:///c:/xampp/htdocs/FoodRescue/config/db.php): Conexión PDO con manejo de errores y UTF-8.
- [`register.php`](file:///c:/xampp/htdocs/FoodRescue/register.php) y [`register_process.php`](file:///c:/xampp/htdocs/FoodRescue/register_process.php): Registro con selección de rol e inicialización de puntos.
- [`login.php`](file:///c:/xampp/htdocs/FoodRescue/login.php) y [`login_process.php`](file:///c:/xampp/htdocs/FoodRescue/login_process.php): Autenticación con `password_verify()`.
- [`index.php`](file:///c:/xampp/htdocs/FoodRescue/index.php): Vista principal / Dashboard adaptable según el rol.
- [`publish_process.php`](file:///c:/xampp/htdocs/FoodRescue/publish_process.php): Publicación de excedentes por comercios.
- [`product_manage_process.php`](file:///c:/xampp/htdocs/FoodRescue/product_manage_process.php): Edición, cambio de estado (pausar/activar) y eliminación de productos.
- [`reserve_process.php`](file:///c:/xampp/htdocs/FoodRescue/reserve_process.php): Reserva con control de stock y puntos de lealtad.
- [`validate_process.php`](file:///c:/xampp/htdocs/FoodRescue/validate_process.php): Validación y canje en caja de códigos de retiro para comercios.
- [`review_process.php`](file:///c:/xampp/htdocs/FoodRescue/review_process.php): Calificaciones (1-5 estrellas) y reseñas para pedidos retirados.
- [`PROYECTO_STARTUP_DEFENSA.md`](file:///c:/xampp/htdocs/FoodRescue/PROYECTO_STARTUP_DEFENSA.md): Pitch Deck, Business Model Canvas y Guía de Demostración para el examen.
- [`assets/css/style.css`](file:///c:/xampp/htdocs/FoodRescue/assets/css/style.css): Estilos visuales personalizados y responsive.
