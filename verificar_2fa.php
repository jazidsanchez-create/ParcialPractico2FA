<?php
session_start();
date_default_timezone_set('America/Panama');
// Solo se llega aquí después de pasar usuario + contraseña (lo deja listo index.php)
if (empty($_SESSION['pre_2fa_id']) || empty($_SESSION['pre_2fa_user'])) {
    header('Location: login.php');
    exit;
}

require_once 'clases/mysql.inc.php';
require_once 'clases/Auth2FA.php';
require_once 'Utilidades/CSRFProtection.php';

$db   = new mod_db();
$auth = new Auth2FA($db);

const MAX_INTENTOS = 5;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !CSRFProtection::validarToken($_POST['csrf_token'] ?? '')) {
    $error = 'Solicitud no válida (token CSRF). Recarga la página.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? '';
    $secret = $auth->obtenerSecret($_SESSION['pre_2fa_id']);

    if ($secret !== null && $auth->verificar($secret, $codigo)) {
        // PASO 2 OK: ahora sí se concede el acceso completo
        $usuario = $_SESSION['pre_2fa_user'];
        unset($_SESSION['pre_2fa_id'], $_SESSION['pre_2fa_user'], $_SESSION['pre_2fa_intentos']);
        session_regenerate_id(true);
        $_SESSION['autenticado'] = 'SI';
        $_SESSION['Usuario']     = $usuario;
        // Sesión NUEVA (id regenerado arriba) que confirma que la fase del código fue exitosa
        $_SESSION['2fa_verificado'] = true;
        $_SESSION['2fa_hora']       = date('Y-m-d H:i:s');
        header('Location: formularios/PanelControl.php');
        exit;
    }

    // Código incorrecto -> acceso denegado
    $_SESSION['pre_2fa_intentos'] = ($_SESSION['pre_2fa_intentos'] ?? 0) + 1;
    if ($_SESSION['pre_2fa_intentos'] >= MAX_INTENTOS) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $restan = MAX_INTENTOS - $_SESSION['pre_2fa_intentos'];
    $error  = "Código incorrecto. Te quedan $restan intento(s).";
}
function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificación en dos pasos</title>
<link rel="stylesheet" href="css/2fa.css">
</head>
<body>
<div class="card">
  <h1>Verificación en dos pasos</h1>
  <p class="sub">Hola <strong><?= e($_SESSION['pre_2fa_user']) ?></strong>. Abre Google Authenticator y escribe el código de 6 dígitos.</p>

  <?php if ($error): ?>
    <div class="msg err">❌ <?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="verificar_2fa.php" autocomplete="off">
    <?= CSRFProtection::campoHidden() ?>
    <label for="codigo">Código temporal</label>
    <input type="text" id="codigo" name="codigo" class="codigo" maxlength="6"
           inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required autofocus>
    <button type="submit">Verificar</button>
  </form>
  <div class="links"><a href="login.php">Cancelar y volver al login</a></div>
</div>
</body>
</html>
