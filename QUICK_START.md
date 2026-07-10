# ⚡ Guía Rápida de Inicio

## 1️⃣ Configurar Base de Datos

### Opción A: Importar archivo SQL (Recomendado)
1. Abre **phpMyAdmin** → http://localhost/phpmyadmin
2. Haz clic en **Importar**
3. Selecciona el archivo: `setup_database.sql`
4. Haz clic en **Ejecutar**

### Opción B: Línea de comando
```bash
cd proyecto_ayuda_app
mysql -u root -p < setup_database.sql
```

## 2️⃣ Configurar Conexión PHP

Edita el archivo: `/config/database.php`

```php
define('DB_HOST', 'localhost');    // ← Cambiar si es necesario
define('DB_USER', 'root');         // ← Tu usuario MySQL
define('DB_PASS', '');             // ← Tu contraseña MySQL
define('DB_NAME', 'tickets_ayuda');
```

## 3️⃣ Abrir la Aplicación

### Opción A: XAMPP/WAMP (Local)
```
http://localhost/proyecto_ayuda_app/
```

### Opción B: Con Virtual Host configurado
```
http://tickets.local/
```

## 4️⃣ Login con Credenciales de Prueba

### Admin:
- **Email:** `admin@tickets.local`
- **Contraseña:** `admin123`

### Usuario Regular:
- **Email:** `usuario@tickets.local`
- **Contraseña:** `usuario123`

---

## ✅ Ya Está Listo

🎉 El sistema está funcionando. ¡A crear tickets!

---

## 🆘 Problemas?

| Problem | Solución |
|---------|----------|
| **No conecta a BD** | Verifica `config/database.php` y que MySQL está corriendo |
| **Login falla** | Asegúrate de haber importado `setup_database.sql` |
| **404 - página no encontrada** | Verifica la ruta URL o que Apache tiene mod_rewrite habilitado |
| **Contraseñas incorrectas** | Reimporta `setup_database.sql` para resetear usuarios |

---

Ver documentación completa en: **README.md**