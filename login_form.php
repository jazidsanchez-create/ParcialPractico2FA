<?php
require_once __DIR__ . '/Utilidades/CSRFProtection.php';
$emsg = isset($_SESSION['emsg']) ? (int)$_SESSION['emsg'] : 0;
unset($_SESSION['emsg']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="Author" content="Irina Fong - dreamsweb7@gmail.com">
<link rel="shortcut icon" href="patria/5564844.png">
<link rel="stylesheet" href="css/2fa.css">
<title>Iniciar sesión</title>
</head>
<body>
<div class="card">
  <h1>Iniciar sesión</h1>
  <p class="sub">Desarrollo Web | UTP</p>

  <?php if ($emsg === 1): ?>
    <div class="msg err">❌ Usuario o contraseña incorrectos. Vuelve a digitar la información.</div>
  <?php elseif ($emsg === 2): ?>
    <div class="msg err">❌ La sesión expiró o la solicitud no es válida. Inténtalo de nuevo.</div>
  <?php endif; ?>

  <form id="formLogin" method="post" action="index.php" autocomplete="off">
    <?= CSRFProtection::campoHidden() ?>
    <input type="hidden" name="tolog" id="tolog" value="true">

    <label for="usuario">Usuario o correo</label>
    <input type="text" id="usuario" name="usuario" minlength="4" required autofocus>

    <label for="contrasena">Contraseña</label>
    <input type="password" id="contrasena" name="contrasena" required>

    <button type="submit">Entrar</button>
  </form>

  <div class="links">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></div>
  <div class="brand">&copy; Universidad Tecnológica de Panamá | <?= date('Y') ?></div>
</div>
<script>
document.getElementById('formLogin').addEventListener('submit', function (e) {
  var u = document.getElementById('usuario').value.trim();
  var c = document.getElementById('contrasena').value;
  if (u.length < 4 || c === '') { e.preventDefault(); alert('Escribe tu usuario y tu contraseña.'); }
});
</script>
</body>
</html>
