<?php
session_start();
date_default_timezone_set('America/Panama');
require_once 'clases/mysql.inc.php';
require_once 'clases/SanitizarEntrada.php';
require_once 'clases/PasswordHasher.php';
require_once 'clases/RegistroUsuario.php';
require_once 'Utilidades/CSRFProtection.php';
require_once 'clases/Auth2FA.php';

$db       = new mod_db();
$registro = new RegistroUsuario($db->getConexion(), new PasswordHasher());
$auth     = new Auth2FA($db);

$errores = [];
$v = ['nombre' => '', 'apellido' => '', 'usuario' => '', 'correo' => '', 'sexo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFProtection::validarToken($_POST['csrf_token'] ?? '')) {
        $errores[] = 'Solicitud no válida (token CSRF). Recarga la página e inténtalo de nuevo.';
    } else {
        $res = $registro->registrar($_POST);
        foreach (array_keys($v) as $k) { $v[$k] = $res['datos'][$k]; }

        if ($res['ok']) {
            // En el momento de crear el registro se genera el secreto y se muestra el QR.
            // El secreto se guarda en la BD cuando el usuario confirma el código (registro_qr.php).
            $_SESSION['reg_id']     = $res['id'];
            $_SESSION['reg_user']   = $res['datos']['usuario'];
            $_SESSION['reg_correo'] = $res['datos']['correo'];
            $_SESSION['reg_secret'] = $auth->generarSecreto();
            header('Location: registro_qr.php');
            exit;
        }
        $errores = $res['errores'];
    }
}
function e($s) { return SanitizarEntrada::escaparHtml($s); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro de usuario</title>
<link rel="stylesheet" href="css/2fa.css">
</head>
<body>
<div class="card">
  <h1>Crear cuenta</h1>
  <p class="sub">Al terminar se mostrará el QR para activar el 2FA con Google Authenticator.</p>

  <?php foreach ($errores as $err): ?>
    <div class="msg err">❌ <?= e($err) ?></div>
  <?php endforeach; ?>

  <form id="formRegistro" method="post" action="registro.php" autocomplete="off">
    <?= CSRFProtection::campoHidden() ?>
    <div class="fila">
      <div>
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($v['nombre']) ?>" required>
      </div>
      <div>
        <label for="apellido">Apellido</label>
        <input type="text" id="apellido" name="apellido" value="<?= e($v['apellido']) ?>" required>
      </div>
    </div>

    <label for="usuario">Usuario (letras, números y _)</label>
    <input type="text" id="usuario" name="usuario" value="<?= e($v['usuario']) ?>"
           minlength="4" maxlength="30" pattern="[A-Za-z0-9_]{4,30}" required>
    <span class="ayuda" id="ayuda-usuario"></span>

    <label for="correo">Correo electrónico</label>
    <input type="email" id="correo" name="correo" value="<?= e($v['correo']) ?>" required>
    <span class="ayuda" id="ayuda-correo"></span>

    <label for="clave">Contraseña (8 a 72, con letra y número)</label>
    <input type="password" id="clave" name="clave" minlength="8" maxlength="72" required>
    <span class="ayuda" id="ayuda-clave"></span>

    <label for="clave2">Repite la contraseña</label>
    <input type="password" id="clave2" name="clave2" minlength="8" maxlength="72" required>
    <span class="ayuda" id="ayuda-clave2"></span>

    <label for="sexo">Sexo</label>
    <select id="sexo" name="sexo" required>
      <option value="">-- Selecciona --</option>
      <option value="M" <?= $v['sexo'] === 'M' ? 'selected' : '' ?>>M</option>
      <option value="F" <?= $v['sexo'] === 'F' ? 'selected' : '' ?>>F</option>
    </select>

    <button type="submit">Registrarme</button>
  </form>
  <div class="links"><a href="login.php">← Ir al inicio de sesión</a></div>
</div>

<script>
// Validaciones del lado del cliente (front-end)
(function () {
  var form    = document.getElementById('formRegistro');
  var usuario = document.getElementById('usuario');
  var correo  = document.getElementById('correo');
  var clave   = document.getElementById('clave');
  var clave2  = document.getElementById('clave2');
  var token   = form.querySelector('[name=csrf_token]').value;
  var duplicado = { usuario: false, correo: false };

  function estado(input, ayuda, ok, texto) {
    input.classList.toggle('invalido', !ok);
    input.classList.toggle('valido', ok && input.value !== '');
    ayuda.textContent = texto || '';
    ayuda.className = 'ayuda ' + (ok ? 'bien' : 'mal');
  }

  // Pregunta al servidor (AJAX) si el usuario o el correo ya existen
  function revisarDuplicado(campo, input, ayuda, nombre) {
    duplicado[campo] = false;
    var datos = new URLSearchParams({ csrf_token: token });
    datos.append(campo, input.value.trim());
    fetch('verificar_correo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: datos
    })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      duplicado[campo] = !!j[campo + 'Existe'];
      if (duplicado[campo]) estado(input, ayuda, false, 'Ese ' + nombre + ' ya está registrado.');
      else estado(input, ayuda, true, nombre.charAt(0).toUpperCase() + nombre.slice(1) + ' disponible ✔');
    })
    .catch(function () { estado(input, ayuda, true, ''); });
  }

  function revisarUsuario() {
    var ayuda = document.getElementById('ayuda-usuario');
    if (!/^[A-Za-z0-9_]{4,30}$/.test(usuario.value)) {
      duplicado.usuario = false;
      estado(usuario, ayuda, false, 'Entre 4 y 30 caracteres: letras, números o guion bajo.');
      return;
    }
    revisarDuplicado('usuario', usuario, ayuda, 'usuario');
  }

  function revisarCorreo() {
    var ayuda = document.getElementById('ayuda-correo');
    duplicado.correo = false;
    if (correo.value.trim() === '') { estado(correo, ayuda, false, 'El correo es obligatorio.'); return; }
    if (!correo.checkValidity())   { estado(correo, ayuda, false, 'Escribe un correo válido (ej: nombre@dominio.com).'); return; }
    revisarDuplicado('correo', correo, ayuda, 'correo');
  }

  function revisarClave() {
    var ayuda = document.getElementById('ayuda-clave');
    var v = clave.value;
    if (v.length < 8 || v.length > 72) estado(clave, ayuda, false, 'Debe tener entre 8 y 72 caracteres.');
    else if (!/[A-Za-z]/.test(v) || !/\d/.test(v)) estado(clave, ayuda, false, 'Incluye al menos una letra y un número.');
    else estado(clave, ayuda, true, 'Contraseña válida ✔');
    if (clave2.value !== '') revisarClave2();
  }

  function revisarClave2() {
    var ayuda = document.getElementById('ayuda-clave2');
    if (clave2.value !== clave.value) estado(clave2, ayuda, false, 'Las contraseñas no coinciden.');
    else estado(clave2, ayuda, true, 'Coinciden ✔');
  }

  usuario.addEventListener('blur', revisarUsuario);
  correo.addEventListener('blur', revisarCorreo);
  clave.addEventListener('input', revisarClave);
  clave2.addEventListener('input', revisarClave2);

  form.addEventListener('submit', function (e) {
    var claveOk = clave.value.length >= 8 && clave.value.length <= 72 && /[A-Za-z]/.test(clave.value) && /\d/.test(clave.value);
    if (!form.checkValidity() || duplicado.usuario || duplicado.correo || !claveOk || clave.value !== clave2.value) {
      e.preventDefault();
      revisarUsuario(); revisarCorreo(); revisarClave(); revisarClave2();
      form.reportValidity();
    }
  });
})();
</script>
</body>
</html>
