# Parcial práctico 2FA – Autenticación de dos factores en PHP

**Universidad Tecnológica de Panamá** · Facultad de Ingeniería en Sistemas · Campus Víctor Levis Sasso
**Asignatura:** Desarrollo Web · **Período:** II Semestre 2026 · **Instructora:** Ing. Irina Fong

Sistema de registro e inicio de sesión con **autenticación de dos factores (TOTP / Google Authenticator)** en PHP y MySQL. Después de validar usuario y contraseña, el sistema pide un código temporal de 6 dígitos antes de dar acceso al panel.

---

## 1. Detalles del laboratorio

**Objetivo:** comprender la importancia del 2FA e implementarlo en un sistema de autenticación con PHP, MySQL y Google Authenticator, aplicando buenas prácticas de seguridad.

**Flujo del sistema**

1. **Registro** (`registro.php`): el usuario llena el formulario con validaciones en el navegador y en el servidor.
2. **Código QR** (`registro_qr.php`): al crear el registro se genera el secreto y se muestra el QR. El usuario lo escanea y confirma con un código; solo entonces el secreto se guarda en `secret_2fa`.
3. **Inicio de sesión** (`login.php` → `index.php`): usuario o correo + contraseña.
4. **Verificación del código** (`verificar_2fa.php`): si el usuario tiene 2FA, se pide el código. Al validarlo se crea una sesión nueva (`2fa_verificado`) y recién ahí se entra al panel.
5. **Evidencias** (`evidencias.php`): muestra el usuario de base de datos con sus privilegios, la fase del 2FA y el contenido de las tablas.

**Criterios de evaluación y dónde se cumplen**

| Criterio | Dónde está |
|---|---|
| Usuarios MySQL con privilegios mínimos (sin superusuario) | `sql/privilegios.sql`, `clases/mysql.inc.php`, `evidencias.php` |
| Formulario de registro con validaciones | `registro.php` |
| Validación de usuario y correo duplicados (frontend) | `registro.php` + `verificar_correo.php` (AJAX) |
| Clase del formulario de registro (mínima responsabilidad) | `clases/RegistroUsuario.php` |
| Clase con métodos estáticos para sanitizar (mín. 3) | `clases/SanitizarEntrada.php` (9 métodos) |
| Generación del código QR | `registro_qr.php`, `activar_2fa.php`, `clases/Auth2FA.php` |
| Login y luego solicitud del código | `index.php`, `verificar_2fa.php` |
| Otra sesión que confirma la fase del código | `verificar_2fa.php` (`session_regenerate_id` + `2fa_verificado`) |
| Interfaz para generar y validar el hash (POO) | `clases/PasswordHasher.php` (`IHashPassword`) |
| Protección CSRF con tokens | `Utilidades/CSRFProtection.php` |
| Tablas con datos | `usuarios`, `intentos_login` (`sql/2fa.sql`) |

---

## 2. Procesos de instalación

1. **Copiar el proyecto** en `C:\wamp64\www\EjemploLogin_2fa`.
2. **Instalar dependencias** (desde la carpeta del proyecto):
   ```powershell
   composer install
   ```
   Si es la primera vez: `composer require sonata-project/google-authenticator`.
3. **Crear la base de datos** (phpMyAdmin → pestaña SQL, como `root`): ejecutar `sql/2fa.sql`.
4. **Crear los usuarios con privilegios mínimos**: ejecutar `sql/privilegios.sql`.
   Si ya tenías la tabla de una versión anterior, ejecutar `sql/migracion_v3.sql`.
5. **Revisar la conexión** en `clases/mysql.inc.php`: base `parcial_2fa`, usuario `app_web`.
6. **Iniciar WAMP** (ícono en verde) y abrir `http://localhost/EjemploLogin_2fa/registro.php`.
7. Tener **Google Authenticator** instalado en el celular y la hora del equipo y del celular en automático.

---

## 3. Insertar registros (imágenes)

**Formulario de registro con validaciones en tiempo real** (usuario, correo y contraseñas):

![Formulario de registro](docs/img/01-registro-validaciones.png)

**Validación de usuario y correo duplicados** (consulta AJAX antes de enviar):

![Duplicados](docs/img/02-registro-duplicado.png)

**Otras validaciones del formulario** (usuario inválido, contraseñas que no coinciden, contraseña corta y sin número):

![Usuario inválido](docs/img/20-validacion-usuario-invalido.png)
![Contraseñas no coinciden](docs/img/21-contrasenas-no-coinciden.png)
![Contraseña corta](docs/img/22-contrasena-corta.png)
![Contraseña sin número](docs/img/23-contrasena-sin-numero.png)

**Código QR generado al crear el registro** y confirmación del 2FA:

![QR](docs/img/03-qr.png)
![2FA activado](docs/img/04-2fa-activado.png)

**Registros guardados en la base de datos** (tabla `usuarios` con la contraseña hasheada y tabla `intentos_login`):

![Tabla usuarios](docs/img/10-tabla-usuarios-phpmyadmin.png)
![Tabla intentos_login](docs/img/11-tabla-intentos-login.png)

---

## 4. Evidencia de acciones de Modificar (imágenes)

La acción de modificar es la **activación del 2FA**: el sistema actualiza (`UPDATE`) la columna `secret_2fa` del usuario cuando este confirma el código del QR.

**Antes** (usuario registrado sin 2FA, `secret_2fa` vacío):

![Antes de modificar](docs/img/16-modificar-antes.png)

**Activación:** el usuario escanea el QR, escribe el código de 6 dígitos y el sistema confirma:

![QR y código](docs/img/25-modificar-qr-codigo.png)
![Activación confirmada](docs/img/26-modificar-activacion-mensaje.png)

**Después** (2FA activado, `secret_2fa` con valor):

![Después de modificar](docs/img/17-modificar-despues.png)

---

## 5. Evidencia de acciones de Eliminar (imágenes)

El usuario `app_web` **no tiene el privilegio `DELETE`**, por el principio de privilegio mínimo, por lo que la aplicación no borra usuarios. La acción de eliminar disponible es **desactivar el 2FA** (`activar_2fa.php`), que elimina el secreto del usuario y requiere confirmar con un código válido.

**Desactivación del 2FA con código:**

![Desactivar 2FA](docs/img/18-eliminar-desactivar-2fa.png)

**Confirmación en pantalla** (el sistema informa que el 2FA fue desactivado):

![Mensaje de 2FA desactivado](docs/img/24-2fa-desactivado-mensaje.png)

**Resultado en la tabla** (`secret_2fa` vuelve a `NULL`):

![Resultado](docs/img/19-eliminar-resultado.png)

---

## 6. Controles utilizados

| Control | Descripción |
|---|---|
| **Privilegio mínimo en MySQL** | `app_web` solo tiene `SELECT`, `INSERT`, `UPDATE` sobre las tablas necesarias; `app_reportes` solo `SELECT`. No se usa `root`. |
| **Consultas preparadas (PDO)** | Evitan la inyección SQL. |
| **Sanitización y validación** | `SanitizarEntrada` (nombre, usuario, correo, sexo en lista fija, secreto 2FA con regex base32, código solo dígitos). |
| **Tokens Anti-CSRF** | Token aleatorio por sesión en todos los formularios y en la petición AJAX; se compara con `hash_equals`. |
| **Hash de contraseñas** | `password_hash` (bcrypt) mediante la interfaz `IHashPassword`. |
| **Segundo factor TOTP** | Código de 6 dígitos que cambia cada 30 segundos (RFC 6238). |
| **Sesiones** | `session_regenerate_id` al pasar cada fase; el panel solo abre con `autenticado = SI`. |
| **Límite de intentos** | Máximo 5 códigos 2FA incorrectos; luego se cierra la sesión. |
| **Registro de intentos** | Tabla `intentos_login` con usuario, IP, resultado y fecha. |
| **Validación doble** | Frontend (JavaScript + AJAX) y backend (PHP). |

**Evidencia del control de privilegios** (comando `SHOW GRANTS`):

```sql
SHOW GRANTS FOR 'app_web'@'localhost';
SHOW GRANTS FOR 'app_reportes'@'localhost';
```

![Privilegios app_web](docs/img/12-show-grants-app-web.png)
![Privilegios app_reportes](docs/img/13-show-grants-app-reportes.png)

**Evidencia del control CSRF** (token dentro del formulario y rechazo cuando falta):

![Token CSRF](docs/img/14-csrf-token-inspector.png)
![Rechazo sin token](docs/img/15-csrf-rechazo.png)

**Evidencia del flujo 2FA y de la página de evidencias:**

![Login](docs/img/05-login.png)
![Código 2FA](docs/img/06-verificacion-codigo.png)
![Código incorrecto](docs/img/07-codigo-incorrecto.png)
![Panel](docs/img/08-panel.png)
![Evidencias](docs/img/09-evidencias.png)

---

## 7. Tecnologías y versiones

| Tecnología | Versión |
|---|---|
| PHP | 8.3.28 |
| MySQL (WAMP) | _(completar: `SELECT VERSION();`)_ |
| phpMyAdmin | 5.2.3 |
| WampServer | _(completar)_ |
| Composer | _(completar: `composer --version`)_ |
| sonata-project/google-authenticator | _(completar: `composer show sonata-project/google-authenticator`)_ |
| API de códigos QR | api.qrserver.com |
| App de autenticación | Google Authenticator |
| Editor | Visual Studio Code |
| Frontend | HTML5, CSS3 y JavaScript (fetch/AJAX) |

---

## 8. Detalles del autor, fecha y referencias

- **Autor:** Jazid Sánchez
- **Universidad:** Universidad Tecnológica de Panamá – Facultad de Ingeniería en Sistemas
- **Asignatura:** Desarrollo Web · II Semestre 2026 · Instructora: Ing. Irina Fong
- **Fecha:** 23 de noviembre de 2026

**Referencias**

1. Fong, I. *Guía de Laboratorio: Implementación de Autenticación de Dos Factores (2FA) en PHP*. UTP, 2026.
2. Fong, I. *CSRF (Cross-Site Request Forgery)* y *Conexiones II* (presentaciones del curso). UTP, 2026.
3. Rúbrica del Parcial Práctico 2FA, Desarrollo Web, UTP, 2026.
4. M'Raihi, D. et al. *RFC 6238: TOTP – Time-Based One-Time Password Algorithm*. https://datatracker.ietf.org/doc/html/rfc6238
5. sonata-project/GoogleAuthenticator. https://github.com/sonata-project/GoogleAuthenticator
6. PHP Manual: `password_hash`, `filter_var`, PDO. https://www.php.net/manual/es/
7. OWASP. *Cross-Site Request Forgery Prevention Cheat Sheet*. https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
8. MySQL. *CREATE USER y GRANT*. https://dev.mysql.com/doc/refman/8.4/en/create-user.html
9. Nelko Dev. *Implementando autenticación de dos factores en PHP*. https://nelkodev.com/php/implementando-autenticacion-de-dos-factores-enphp-una-guia-integral/
10. API de códigos QR. https://goqr.me/api/

> Las contraseñas de MySQL de este repositorio son de práctica; no las uses en entornos reales.
