/# Sistema de Tickets de Ayuda - Documentación

## 📋 Descripción del Proyecto

Sistema web para gestión de tickets de ayuda desarrollado en **PHP 7+**, **MySQL** y **Bootstrap 5**. Permite a los usuarios reportar problemas y a los administradores dar seguimiento, responder y resolver solicitudes.

## ✨ Características

✅ **Autenticación segura** - Login/Registro con contraseñas hasheadas (bcrypt)
✅ **Módulo Usuario** - Crear tickets, ver estado, responder, historial
✅ **Módulo Admin** - Ver todos los tickets, responder, cambiar estado, asignar
✅ **Reportes** - Estadísticas con gráficas (Chart.js)
✅ **4 Estados de Tickets** - Nuevo, En proceso, Resuelto, Cerrado
✅ **2 Ubicaciones** - Finca El Jardín, San Ignacio
✅ **Interfaz Responsive** - Bootstrap 5
✅ **Seguridad** - Prepared statements, XSS protection, validación de roles

---

## 🚀 Instalación y Setup

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7+
- Apache/Nginx con soporte de reescritura de URLs
- Navegador web moderno

### Pasos de Instalación

#### 1. **Clonar/Descargar el proyecto**
```bash
cd proyecto_ayuda_app
```

#### 2. **Crear Base de Datos**

**Opción A: Usando phpMyAdmin**
- Acceder a phpMyAdmin (http://localhost/phpmyadmin)
- Crear nueva base de datos: `tickets_ayuda`
- Ir a SQL e importar el archivo: `setup_database.sql`

**Opción B: Línea de comando**
```bash
mysql -u root -p < setup_database.sql
```

#### 3. **Configurar conexión a BD**

Editar archivo `/config/database.php`:
```php
define('DB_HOST', 'localhost');    // Host del servidor MySQL
define('DB_USER', 'root');         // Usuario MySQL
define('DB_PASS', '');             // Contraseña MySQL (dejar vacío si no tiene)
define('DB_NAME', 'tickets_ayuda');
```

#### 4. **Generar contraseñas hasheadas (IMPORTANTE)**

El archivo `setup_database.sql` tiene placeholders. Genera los hashes:

```php
<?php
// Copiar en un archivo temp.php
echo 'Admin (admin123): ' . password_hash('admin123', PASSWORD_BCRYPT) . "\n";
echo 'Usuario (usuario123): ' . password_hash('usuario123', PASSWORD_BCRYPT) . "\n";
?>
```

Ejecuta en terminal:
```bash
php temp.php
```

Copia los hashes y actualiza en `setup_database.sql` antes de importar OR ejecuta estos queries en phpMyAdmin después:

```sql
UPDATE usuarios SET password = '$2y$10$YKVgKEKSTlq8oJfKDY7C2.YXvJV.6W4eWFCWr5Ux1hb/qQ3WfPSZu' WHERE email = 'admin@tickets.local';
UPDATE usuarios SET password = '$2y$10$D9LLd8b9sHZWVvGNnNJzXuRhQ5P1kI8Y7Z3wM2b9cK6pL4xJ5V0Q6' WHERE email = 'usuario@tickets.local';
```

#### 5. **Definir permisos** (Linux/Mac)
```bash
chmod -R 755 proyecto_ayuda_app/
chmod -R 777 proyecto_ayuda_app/includes/  # Si es necesario para escritura
```

#### 6. **Configurar Virtual Host** (Opcional pero recomendado)

**Apache (httpd-vhosts.conf):**
```apache
<VirtualHost *:80>
    ServerName tickets.local
    DocumentRoot "C:/xampp/htdocs/proyecto_ayuda_app"
    <Directory "C:/xampp/htdocs/proyecto_ayuda_app">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Agregar a hosts:**
- Windows: `C:\Windows\System32\drivers\etc\hosts`
- Linux/Mac: `/etc/hosts`

```
127.0.0.1 tickets.local
```

#### 7. **Crear archivo `.htaccess`** (Reescritura de URLs)

Crear en la raíz: `.htaccess`
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>
```

---

## � Configuración de Correo

El sistema ya está preparado para enviar correos al crear un ticket. La integración funciona de dos formas:

1. **mail() del servidor**: útil si tu entorno ya tiene un MTA configurado.
2. **SMTP**: la opción más fiable en XAMPP o localhost, especialmente con Gmail/Outlook.

### Configuración recomendada con SMTP

En el servidor o en la sesión de Apache, define estas variables de entorno:

```bash
MAIL_ENABLED=true
MAIL_DRIVER=smtp
MAIL_FROM_ADDRESS=tucuenta@ejemplo.com
MAIL_FROM_NAME="Sistema de Tickets"
MAIL_SMTP_HOST=smtp.tu-proveedor.com
MAIL_SMTP_PORT=587
MAIL_SMTP_USERNAME=tucuenta@ejemplo.com
MAIL_SMTP_PASSWORD=tu-password-o-app-password
MAIL_SMTP_ENCRYPTION=tls
MAIL_SMTP_AUTH=true
```

Si usas Gmail, normalmente necesitas una contraseña de aplicación en lugar de tu contraseña habitual.

### Probar la configuración

Desde la raíz del proyecto:

```bash
php tools/test_mail.php tucorreo@ejemplo.com
```

Si lo prefieres por navegador:

```text
http://localhost/proyecto_ayuda_app/tools/test_mail.php?to=tucorreo@ejemplo.com
```

Si el envío falla, revisa que el host SMTP, el puerto y las credenciales sean correctos.

---

## �🔐 Credenciales de Prueba

Una vez configurado, puedes iniciar sesión con:

### Administrador
- **Email:** `admin@tickets.local`
- **Contraseña:** `admin123`

### Usuario Regular
- **Email:** `usuario@tickets.local`
- **Contraseña:** `usuario123`

---

## 📁 Estructura de Carpetas

```
proyecto_ayuda_app/
│
├── config/
│   └── database.php          # Conexión PDO a MySQL
│
├── includes/
│   ├── header.php            # Navbar Bootstrap
│   ├── footer.php            # Footer
│   └── functions.php         # Funciones reutilizables
│
├── auth/
│   ├── login.php             # Página de login
│   └── register.php          # Página de registro
│
├── usuario/
│   ├── dashboard.php         # Dashboard usuario - lista tickets propios
│   ├── crear_ticket.php      # Crear nuevo ticket
│   └── ver_ticket.php        # Ver detalle y responder
│
├── admin/
│   ├── dashboard.php         # Panel admin - todos los tickets
│   ├── ver_ticket.php        # Ver detalle, cambiar estado, responder
│   └── reportes.php          # Estadísticas y gráficas
│
├── api/
│   (Para futuras extensiones JSON)
│
├── assets/
│   ├── css/
│   │   └── style.css         # Estilos personalizados Bootstrap
│   └── js/
│       └── main.js           # JavaScript principal
│
├── index.php                 # Punto de entrada
├── logout.php                # Cerrar sesión
├── setup_database.sql        # Script SQL para crear BD y tablas
└── README.md                 # Este archivo
```

---

## 🔄 Flujo de Uso

### Como Usuario Regular:

1. **Registrarse o Login**
   - Acceder a `/auth/login.php`
   - Si no tienes cuenta, ir a `/auth/register.php`

2. **Crear Ticket**
   - Ir a Dashboard (`/usuario/dashboard.php`)
   - Hacer clic en "Crear Nuevo Ticket"
   - Completar formulario (nombre, email, asunto, descripción, ubicación)
   - Enviar

3. **Ver Estado**
   - En Dashboard ves todos tus tickets
   - Hacer clic en "Ver" para más detalles
   - Ver respuestas del administrador
   - Agregar más comentarios si es necesario

### Como Administrador:

1. **Login como Admin**
   - Acceder con credenciales de admin

2. **Ver Todos los Tickets**
   - Ir al Panel Admin (`/admin/dashboard.php`)
   - Ver conteos y estadísticas
   - Filtrar por estado o ubicación

3. **Procesar Ticket**
   - Hacer clic en "Ver" en un ticket
   - Leer descripción y respuestas previas
   - Escribir respuesta
   - Cambiar estado (Nuevo → En proceso → Resuelto → Cerrado)
   - Asignar a sí mismo u otro admin
   - Guardar cambios

4. **Ver Reportes**
   - Ir a `/admin/reportes.php`
   - Ver gráficas de estado y ubicación
   - Análisis de tickets recientes

---

## 🔐 Seguridad

- ✅ **Contraseñas hasheadas** con bcrypt (PASSWORD_BCRYPT)
- ✅ **Prepared statements** en todas las queries (PDO)
- ✅ **XSS Protection** - htmlspecialchars() en outputs
- ✅ **CSRF Tokens** - Posible agregar en futuras versiones
- ✅ **Validación de roles** - Verificación en cada página
- ✅ **Permisos por usuario** - No ver tickets ajenos (excepto admins)

---

## 📊 Base de Datos

### Tabla: usuarios
```sql
- id (INT, PK, AI)
- nombre (VARCHAR 100)
- email (VARCHAR 100, UNIQUE)
- password (VARCHAR 255, hashed)
- rol (ENUM: 'usuario', 'admin')
- fecha_registro (TIMESTAMP)
- activo (TINYINT, 1/0)
```

### Tabla: tickets
```sql
- id (INT, PK, AI)
- usuario_id (INT, FK → usuarios.id)
- asunto (VARCHAR 255)
- descripcion (LONGTEXT)
- ubicacion (ENUM: 'Finca El Jardín', 'San Ignacio')
- estado (ENUM: 'Nuevo', 'En proceso', 'Resuelto', 'Cerrado')
- asignado_a (INT, FK → usuarios.id)
- fecha_creacion (TIMESTAMP)
- fecha_ultima_actualizacion (TIMESTAMP)
```

### Tabla: respuestas_ticket
```sql
- id (INT, PK, AI)
- ticket_id (INT, FK → tickets.id)
- usuario_id (INT, FK → usuarios.id)
- mensaje (LONGTEXT)
- fecha_creacion (TIMESTAMP)
```

---

## 🛠️ Funciones Principales (includes/functions.php)

### Autenticación
- `isLoggedIn()` - Verificar si está logueado
- `isAdmin()` - Verificar si es admin
- `requireLogin()` - Redirigir a login si no está autenticado
- `requireAdmin()` - Redirigir si no es admin

### Usuarios
- `createUser($nombre, $email, $password, $rol)` - Crear usuario
- `getUserById($id)` - Obtener usuario por ID
- `getUserByEmail($email)` - Obtener usuario por email
- `hashPassword($password)` - Hashear contraseña
- `verifyPassword($password, $hash)` - Verificar contraseña

### Tickets
- `createTicket($usuario_id, $asunto, $descripcion, $ubicacion)` - Crear ticket
- `getTicketById($ticket_id)` - Obtener ticket
- `getUserTickets($usuario_id)` - Obtener tickets del usuario
- `getAllTickets($limit, $offset)` - Obtener todos los tickets (admin)
- `updateTicketStatus($ticket_id, $estado, $asignado_a)` - Cambiar estado
- `getTicketResponses($ticket_id)` - Obtener respuestas de un ticket
- `addResponseToTicket($ticket_id, $usuario_id, $mensaje)` - Agregar respuesta

### Estadísticas
- `countTotalTickets()` - Contar total de tickets
- `countTicketsByStatus()` - Contar por estado
- `countTicketsByLocation()` - Contar por ubicación

---

## 🚀 Mejoras Futuras (Roadmap)

- [ ] Notificaciones por email
- [ ] Adjuntos de archivos en tickets
- [ ] Búsqueda y filtros avanzados
- [ ] Prioridades dinámicas
- [ ] Exportar reportes (PDF, Excel)
- [ ] Auditoría de cambios
- [ ] 2FA (Autenticación de dos factores)
- [ ] API REST JSON
- [ ] Webhooks
- [ ] Chat en vivo
- [ ] Satisfacción del cliente (ratings)
- [ ] Integración Slack/Discord

---

## 🐛 Troubleshooting

### Error: "Error de conexión a base de datos"
- Verificar que MySQL está corriendo
- Verificar credenciales en `config/database.php`
- Asegurarse que la BD `tickets_ayuda` existe

### Error: "No tienes permiso para ver este ticket"
- Verificar que el ticket pertenece al usuario logueado
- Solo admins pueden ver todos los tickets

### Password no funciona en login
- Verificar que las contraseñas en BD están hasheadas correctamente
- Usar `password_hash('contraseña', PASSWORD_BCRYPT)`

### Problemas con redirecciones
- Verificar que `session_start()` está en la primera línea de cada página
- Asegurarse que no hay espacios en blanco antes de `<?php`

---

## 📧 Contacto y Soporte

Para problemas o sugerencias, crear un issue en el repositorio.

---

## 📄 Licencia

Este proyecto es de código abierto y está bajo licencia MIT.

---

**Versión:** 1.0.0  
**Última actualización:** Mayo 2026  
**Autor:** Tu Nombre