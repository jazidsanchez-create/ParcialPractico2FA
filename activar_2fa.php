<?php
session_start();
// Solo usuarios que ya iniciaron sesión
if (($_SESSION['autenticado'] ?? '') !== 'SI') {
    header('Location: login.php');
    exit;
}

require_once 'clases/mysql.inc.php';
require_once 'clases/Auth2FA.php';
require_once 'Utilidades/CSRFProtection.php';

$db   = new mod_db();
$auth = new Auth2FA($db);

$usuario = $_SESSION['Usuario'];
$fila    = $db->log($usuario);
if (!$fila) {
    header('Location: salir.php');
    exit;
}
$id       = (int)$fila->id;
$yaActivo = !empty($fila->secret_2fa);
$mensaje  = '';
$tipo     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !CSRFProtection::validarToken($_POST['csrf_token'] ?? '')) {
    $mensaje = '❌ Solicitud no válida (token CSRF). Recarga la página.';
    $tipo = 'err';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $codigo = $_POST['codigo'] ?? '';

    if ($accion === 'activar' && !$yaActivo) {
        $secretTmp = $_SESSION['secret_2fa_tmp'] ?? '';
        // Solo se guarda en la BD si el usuario demuestra que escaneó el QR (código válido)
        if ($secretTmp !== '' && $auth->verificar($secretTmp, $codigo)) {
            $auth->guardarSecret($id, $secretTmp);   // <- secreto guardado por usuario
            unset($_SESSION['secret_2fa_tmp']);
            $yaActivo = true;
            $mensaje = '✅ ¡2FA activado correctamente! Desde ahora te pediremos el código al iniciar sesión.';
            $tipo = 'ok';
        } else {
            $mensaje = '❌ Código incorrecto. Revisa que escaneaste el QR y que la hora del PC sea correcta.';
            $tipo = 'err';
        }
    } elseif ($accion === 'desactivar' && $yaActivo) {
        if ($auth->verificar($fila->secret_2fa, $codigo)) {
            $auth->guardarSecret($id, null);
            $yaActivo = false;
            $mensaje = '2FA desactivado.';
            $tipo = 'info';
        } else {
            $mensaje = '❌ Código incorrecto, no se desactivó el 2FA.';
            $tipo = 'err';
        }
    }
}

$secret = '';
$qrUrl  = '';
if (!$yaActivo) {
    // El secreto se genera dinámicamente y se mantiene en sesión hasta confirmar
    if (empty($_SESSION['secret_2fa_tmp'])) {
        $_SESSION['secret_2fa_tmp'] = $auth->generarSecreto();
    }
    $secret = $_SESSION['secret_2fa_tmp'];
    $qrUrl  = $auth->urlQr(!empty($fila->Correo) ? $fila->Correo : $usuario, $secret);
}
function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Activar 2FA</title>
<link rel="stylesheet" href="css/2fa.css">
</head>
<body>
<div class="card">
  <h1>Autenticación en dos pasos</h1>
  <p class="sub">Usuario: <strong><?= e($usuario) ?></strong></p>

  <?php if ($mensaje): ?>
    <div class="msg <?= e($tipo) ?>"><?= e($mensaje) ?></div>
  <?php endif; ?>

  <?php if ($yaActivo): ?>
    <div class="msg ok">🔒 El 2FA está <strong>activo</strong> en tu cuenta.</div>
    <form method="post" action="activar_2fa.php">
      <input type="hidden" name="accion" value="desactivar">
      <?= CSRFProtection::campoHidden() ?>
      <label for="codigo">Para desactivarlo, escribe tu código actual</label>
      <input type="text" id="codigo" name="codigo" class="codigo" maxlength="6"
             inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required>
      <button type="submit" class="btn-rojo">Desactivar 2FA</button>
    </form>
  <?php else: ?>
    <ol class="pasos">
      <li>Abre <strong>Google Authenticator</strong> en tu celular.</li>
      <li>Toca <strong>+</strong> y elige <strong>Escanear código QR</strong>.</li>
      <li>Escribe aquí el código de 6 dígitos que aparece.</li>
    </ol>
    <div class="qr"><img src="<?= e($qrUrl) ?>" width="220" height="220" alt="Escanea este QR con Google Authenticator"></div>
    <p class="sub" style="margin-bottom:0">¿No puedes escanear? Escribe esta clave a mano:</p>
    <div class="clave"><?= e($secret) ?></div>

    <form method="post" action="activar_2fa.php">
      <input type="hidden" name="accion" value="activar">
      <?= CSRFProtection::campoHidden() ?>
      <label for="codigo">Código de verificación</label>
      <input type="text" id="codigo" name="codigo" class="codigo" maxlength="6"
             inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" required autofocus>
      <button type="submit">Verificar y activar</button>
    </form>
  <?php endif; ?>

  <div class="links"><a href="formularios/PanelControl.php">← Volver al panel</a></div>
</div>
</body>
</html>
