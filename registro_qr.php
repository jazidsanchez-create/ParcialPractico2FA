<?php
session_start();
require_once 'clases/mysql.inc.php';
require_once 'clases/SanitizarEntrada.php';
require_once 'clases/Auth2FA.php';
require_once 'Utilidades/CSRFProtection.php';

// Solo llega aquí quien acaba de registrarse (lo deja listo registro.php)
if (empty($_SESSION['reg_id']) || empty($_SESSION['reg_secret'])) {
    header('Location: login.php');
    exit;
}

$db      = new mod_db();
$auth    = new Auth2FA($db);
$id      = (int)$_SESSION['reg_id'];
$usuario = $_SESSION['reg_user'];
$correo  = $_SESSION['reg_correo'] ?? '';
$secret  = $_SESSION['reg_secret'];
$mensaje = '';
$hecho   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFProtection::validarToken($_POST['csrf_token'] ?? '')) {
        $mensaje = 'Solicitud no válida (token CSRF).';
    } elseif ($auth->verificar($secret, SanitizarEntrada::limpiarCodigo($_POST['codigo'] ?? ''))) {
        $auth->guardarSecret($id, $secret);   // secreto guardado para este usuario
        unset($_SESSION['reg_id'], $_SESSION['reg_user'], $_SESSION['reg_correo'], $_SESSION['reg_secret']);
        $hecho = true;
    } else {
        $mensaje = 'Código incorrecto. Revisa que escaneaste el QR y que la hora del PC sea correcta.';
    }
}
function e($s) { return SanitizarEntrada::escaparHtml($s); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Activa tu 2FA</title>
<link rel="stylesheet" href="css/2fa.css">
</head>
<body>
<div class="card">
  <h1>Activa tu verificación en dos pasos</h1>
  <p class="sub">Cuenta creada: <strong><?= e($usuario) ?></strong> (<?= e($correo) ?>)</p>

  <?php if ($hecho): ?>
    <div class="msg ok">✅ ¡Listo! Tu 2FA quedó activado. Desde ahora, al iniciar sesión se te pedirá el código.</div>
    <a class="btn" href="login.php">Ir a iniciar sesión</a>
  <?php else: ?>
    <?php if ($mensaje): ?><div class="msg err">❌ <?= e($mensaje) ?></div><?php endif; ?>

    <ol class="pasos">
      <li>Abre <strong>Google Authenticator</strong> en tu celular.</li>
      <li>Toca <strong>+</strong> y elige <strong>Escanear código QR</strong>.</li>
      <li>Escribe aquí el código de 6 dígitos que aparece.</li>
    </ol>
    <div class="qr"><img src="<?= e($auth->urlQr($correo, $secret)) ?>" width="220" height="220" alt="Escanea este QR con Google Authenticator"></div>
    <p class="sub" style="margin-bottom:0">¿No puedes escanear? Escribe esta clave a mano:</p>
    <div class="clave"><?= e($secret) ?></div>

    <form method="post" action="registro_qr.php" autocomplete="off">
      <?= CSRFProtection::campoHidden() ?>
      <label for="codigo">Código de verificación</label>
      <input type="text" id="codigo" name="codigo" class="codigo" maxlength="6"
             inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required autofocus>
      <button type="submit">Verificar y activar</button>
    </form>
    <div class="links"><a href="login.php">Hacerlo después (iniciar sesión sin 2FA)</a></div>
  <?php endif; ?>
</div>
</body>
</html>
