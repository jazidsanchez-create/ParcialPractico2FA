<?PHP
session_start();
include("clases/mysql.inc.php");
$db = new mod_db();

include("clases/SanitizarEntrada.php");
include("comunes/loginfunciones.php");
include("Utilidades/CSRFProtection.php");
include("clases/PasswordHasher.php");
include("clases/objLoginAdmin.php");

$tolog = isset($_POST["tolog"]) ? $_POST["tolog"] : false;

if ($tolog === "true" && $_SERVER['REQUEST_METHOD'] === 'POST') {

	// Anti-CSRF: si el token no coincide, se rechaza el formulario
	if (!CSRFProtection::validarToken(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
		$_SESSION["emsg"] = 2;
		redireccionar("login.php");
		exit;
	}

	$Usuario  = isset($_POST['usuario'])    ? $_POST['usuario']    : '';
	$ClaveKey = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
	$ipRemoto = $_SERVER['REMOTE_ADDR'];

	$Logearme = new ValidacionLogin($Usuario, $ClaveKey, $ipRemoto, $db);

	if ($Logearme->logger()) {
		$Logearme->autenticar();

		if ($Logearme->getIntentoLogin()) {
			// PASO 1 OK: usuario y contraseña correctos
			$Logearme->registrarIntentos();
			session_regenerate_id(true);
			CSRFProtection::regenerarToken();

			if ($Logearme->tiene2FA()) {
				// Tiene 2FA: todavía NO hay acceso. Se guarda un pase temporal
				// y se pide el código en la segunda pantalla.
				unset($_SESSION['autenticado']);
				$_SESSION['pre_2fa_id']        = $Logearme->getId();
				$_SESSION['pre_2fa_user']      = $Logearme->getUsuario();
				$_SESSION['pre_2fa_intentos']  = 0;
				redireccionar("verificar_2fa.php");
			} else {
				$_SESSION['autenticado']     = "SI";
				$_SESSION['Usuario']         = $Logearme->getUsuario();
				$_SESSION['2fa_verificado']  = false;
				redireccionar("formularios/PanelControl.php");
			}
		} else {
			$Logearme->registrarIntentos();
			$_SESSION["emsg"] = 1;
			redireccionar("login.php");
		}
	} else {
		$_SESSION["emsg"] = 1;
		redireccionar("login.php");
	}

} else {
	redireccionar("login.php");
}
?>
