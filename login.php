<?PHP
session_start();
$emsg = isset($_SESSION['emsg']) ? $_SESSION['emsg'] : null;

// Se descarta la sesión anterior y se crea una nueva (así el token CSRF sí persiste)
$_SESSION = [];
session_regenerate_id(true);
if ($emsg !== null) {
    $_SESSION['emsg'] = $emsg;
}
include("login_form.php");
?>
