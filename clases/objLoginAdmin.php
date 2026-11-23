<?PHP
final class ValidacionLogin{

	private $id;
	private $usuario;
	private $contrasena;
	private $hastGenerado;
	private $secret2fa;
	private $loginExitoso = 0;
	private $ip;
	private $pdo;
	private $hasher;

	public function __construct($usuario, $contrasena, $ipRemoto, $pdo, $hasher = null){
		$this->usuario    = SanitizarEntrada::limpiarCadena($usuario);
		$this->contrasena = SanitizarEntrada::limpiarCadena($contrasena);
		$this->ip  = $ipRemoto;
		$this->pdo = $pdo;
		$this->hasher = $hasher ? $hasher : new PasswordHasher(); // IHashPassword
	}

	// Busca el usuario en la BD y carga su hash y su secreto 2FA
	public function logger(){
		$usuariologueado = $this->pdo->log($this->usuario);

		if ($usuariologueado) {
			$this->id           = $usuariologueado->id;
			$this->hastGenerado = $usuariologueado->HashMagic;
			$this->secret2fa    = isset($usuariologueado->secret_2fa) ? $usuariologueado->secret_2fa : null;
			return true;
		}
		return false;
	}

	// Compara la contraseña escrita contra el hash guardado
	public function autenticar(){
		if ($this->hasher->validar($this->contrasena, $this->hastGenerado)) {
			$this->loginExitoso = 1;
		} else {
			$this->loginExitoso = 0;
		}
	}

	public function getIntentoLogin(){ return $this->loginExitoso; }
	public function getUsuario(){ return $this->usuario; }
	public function getId(){ return $this->id; }

	// NUEVO: ¿este usuario tiene 2FA activado?
	public function tiene2FA(){
		return !empty($this->secret2fa);
	}

	public function registrarIntentos(){
		$data = array(
			"Usuario"            => $this->usuario,
			"ipRemoto"           => $this->ip,
			"deteccion_anomalia" => $this->loginExitoso
		);
		$this->pdo->insertSeguro("intentos_login", $data);
	}

} //fin ValidacionLogin
?>
