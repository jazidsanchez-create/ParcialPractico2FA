<?PHP
//bloque de seguridad
session_start();
//comprueba que el usuario esta autenticado
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] != "SI"){
	// si no esta autenticado, se elimina la sesion y se va a la pagina de login
	unset($_SESSION['Usuario']);//libera la variable de sesion registrada
	session_destroy();//elimina la sesion actual y cualquier dato de la sesion

	// si el script que nos incluyo esta en una subcarpeta (ej. formularios/), el login esta un nivel arriba
	$enRaiz = (basename(dirname($_SERVER['SCRIPT_FILENAME'])) === basename(dirname(__DIR__)));
	header("Location: " . ($enRaiz ? "login.php" : "../login.php"));
	//salimos de este script
	exit();
}

	function nvl(&$var, $default="") {
		/* if $var is undefined, return $default, otherwise return $var */
		return isset($var) ? $var : $default;
	}//fin de la funcion

?>
