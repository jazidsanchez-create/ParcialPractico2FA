# Parcial práctico 2FA – Desarrollo Web (UTP)

Sistema de registro e inicio de sesión con **autenticación de dos factores (TOTP / Google Authenticator)** en PHP + MySQL.

## Requisitos
- WAMP (PHP 8.x, MySQL) y Composer
- App Google Authenticator en el celular
- Internet (el QR se genera con `api.qrserver.com`)

## Instalación
1. Copiar el proyecto en `C:\wamp64\www\EjemploLogin_2fa`
2. `composer install` (o `composer require sonata-project/google-authenticator`)
3. En phpMyAdmin ejecutar `sql/2fa.sql` y luego `sql/privilegios.sql`
4. Abrir `http://localhost/EjemploLogin_2fa/registro.php`

## Flujo
1. **Registro** (`registro.php`): validaciones en el navegador (AJAX para usuario/correo duplicados) y en el servidor.
2. **QR** (`registro_qr.php`): se escanea con Google Authenticator y se confirma con un código; el secreto se guarda en `secret_2fa`.
3. **Login** (`login.php` → `index.php`): usuario/correo + contraseña.
4. **Código** (`verificar_2fa.php`): se pide el código de 6 dígitos. Al validarlo se crea una sesión nueva (`2fa_verificado`).
5. **Evidencias** (`evidencias.php`): privilegios, sesión y tablas.

## Estructura
| Ruta | Función |
|---|---|
| `clases/SanitizarEntrada.php` | Métodos estáticos para sanitizar/validar datos |
| `clases/RegistroUsuario.php` | Clase del formulario de registro (métodos de una sola responsabilidad) |
| `clases/PasswordHasher.php` | Interfaz `IHashPassword` + implementación bcrypt |
| `clases/Auth2FA.php` | Secreto, QR y verificación TOTP |
| `Utilidades/CSRFProtection.php` | Tokens Anti-CSRF |
| `sql/` | Esquema, privilegios mínimos y migración |

## Seguridad
- Usuarios MySQL con privilegios mínimos (`app_web`, `app_reportes`): no se usa `root`.
- Consultas preparadas (PDO) y sanitización de entradas.
- Tokens Anti-CSRF en todos los formularios y en la petición AJAX.
- `password_hash` (bcrypt) para contraseñas.
- Máximo 5 intentos de código 2FA por sesión.

> Las contraseñas de MySQL de este repositorio son de práctica; no las uses en producción.
