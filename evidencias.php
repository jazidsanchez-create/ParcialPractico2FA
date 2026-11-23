<?php
session_start();
if (($_SESSION['autenticado'] ?? '') !== 'SI') {
    header('Location: login.php');
    exit;
}
require_once 'clases/mysql.inc.php';
require_once 'clases/SanitizarEntrada.php';

$db  = new mod_db();
$pdo = $db->getConexion();

$usuarioBD  = $pdo->query("SELECT CURRENT_USER()")->fetchColumn();
$grants     = $pdo->query("SHOW GRANTS")->fetchAll(PDO::FETCH_COLUMN);
$totUsr     = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totInt     = (int)$pdo->query("SELECT COUNT(*) FROM intentos_login")->fetchColumn();
$usuarios   = $pdo->query("SELECT id, Nombre, Apellido, Usuario, Correo, Sexo, FechaDelSistema,
                           IF(secret_2fa IS NULL, 'No', 'Sí') AS dosfa
                           FROM usuarios ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$intentos   = $pdo->query("SELECT id, Usuario, ipRemoto, deteccion_anomalia, fecha
                           FROM intentos_login ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
function e($s) { return SanitizarEntrada::escaparHtml($s); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Evidencias del laboratorio</title>
<link rel="stylesheet" href="css/2fa.css">
</head>
<body>
<div class="card ancho">
  <h1>Evidencias</h1>
  <p class="sub">Sesión de <strong><?= e($_SESSION['Usuario']) ?></strong></p>

  <h2>1. Usuario de BD que usa la aplicación y sus privilegios</h2>
  <p>Conectado como: <strong><?= e($usuarioBD) ?></strong> (no es root)</p>
  <pre class="bloque"><?php foreach ($grants as $g) { echo e($g) . "\n"; } ?></pre>

  <h2>2. Fase del código 2FA (otra sesión)</h2>
  <table class="datos">
    <tr><th>ID de sesión actual</th><td><?= e(session_id()) ?></td></tr>
    <tr><th>2FA verificado</th><td><?= !empty($_SESSION['2fa_verificado']) ? '✅ Sí' : '— No (esta cuenta no usa 2FA)' ?></td></tr>
    <tr><th>Hora de verificación</th><td><?= e($_SESSION['2fa_hora'] ?? '-') ?></td></tr>
  </table>

  <h2>3. Tabla usuarios (<?= $totUsr ?> registros)</h2>
  <table class="datos">
    <tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Usuario</th><th>Correo</th><th>Sexo</th><th>Fecha</th><th>2FA</th></tr>
    <?php foreach ($usuarios as $r): ?>
      <tr><td><?= e($r['id']) ?></td><td><?= e($r['Nombre']) ?></td><td><?= e($r['Apellido']) ?></td>
          <td><?= e($r['Usuario']) ?></td><td><?= e($r['Correo']) ?></td><td><?= e($r['Sexo']) ?></td><td><?= e($r['FechaDelSistema']) ?></td><td><?= e($r['dosfa']) ?></td></tr>
    <?php endforeach; ?>
  </table>

  <h2>4. Tabla intentos_login (<?= $totInt ?> registros)</h2>
  <table class="datos">
    <tr><th>ID</th><th>Usuario</th><th>IP</th><th>Éxito (1/0)</th><th>Fecha</th></tr>
    <?php foreach ($intentos as $r): ?>
      <tr><td><?= e($r['id']) ?></td><td><?= e($r['Usuario']) ?></td><td><?= e($r['ipRemoto']) ?></td>
          <td><?= e($r['deteccion_anomalia']) ?></td><td><?= e($r['fecha']) ?></td></tr>
    <?php endforeach; ?>
  </table>

  <div class="links"><a href="formularios/PanelControl.php">← Volver al panel</a></div>
</div>
</body>
</html>
