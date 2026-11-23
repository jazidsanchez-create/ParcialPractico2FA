<?php
// Responde JSON con {"usuarioExiste":bool,"correoExiste":bool}. Lo llama registro.php con fetch().
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'clases/mysql.inc.php';
require_once 'clases/SanitizarEntrada.php';
require_once 'clases/PasswordHasher.php';
require_once 'clases/RegistroUsuario.php';
require_once 'Utilidades/CSRFProtection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'método no permitido']);
    exit;
}
CSRFProtection::verificarAjax();   // si el token no coincide, responde 403 y termina

$db       = new mod_db();
$registro = new RegistroUsuario($db->getConexion(), new PasswordHasher());
$resp     = [];

if (isset($_POST['usuario'])) {
    $u = SanitizarEntrada::limpiarUsuario($_POST['usuario']);
    $resp['usuarioExiste'] = ($u !== '' && $registro->usuarioExiste($u));
}
if (isset($_POST['correo'])) {
    $c = SanitizarEntrada::limpiarCorreo($_POST['correo']);
    $resp['correoExiste'] = ($c !== '' && $registro->correoExiste($c));
}
echo json_encode($resp);
