<?php
// Clase del formulario "Registro de datos". Cada método hace UNA sola cosa.
class RegistroUsuario
{
    private $pdo;
    private $hasher;

    public function __construct(PDO $pdo, IHashPassword $hasher)
    {
        $this->pdo    = $pdo;
        $this->hasher = $hasher;
    }

    // 1) Solo sanitiza lo que llegó del formulario
    public function limpiar(array $post)
    {
        return [
            'nombre'   => SanitizarEntrada::limpiarNombre($post['nombre']   ?? ''),
            'apellido' => SanitizarEntrada::limpiarNombre($post['apellido'] ?? ''),
            'usuario'  => SanitizarEntrada::limpiarUsuario($post['usuario'] ?? ''),
            'correo'   => SanitizarEntrada::limpiarCorreo($post['correo']   ?? ''),
            'sexo'     => SanitizarEntrada::validarSexo($post['sexo']       ?? ''),
            // el login también limpia la contraseña con limpiarCadena(), por eso aquí igual
            'clave'    => SanitizarEntrada::limpiarCadena($post['clave']  ?? ''),
            'clave2'   => SanitizarEntrada::limpiarCadena($post['clave2'] ?? ''),
        ];
    }

    // 2) Solo valida formato/reglas (devuelve lista de errores)
    public function validar(array $d)
    {
        $e = [];
        if ($d['nombre'] === '')   $e[] = 'El nombre es obligatorio.';
        if ($d['apellido'] === '') $e[] = 'El apellido es obligatorio.';
        if (strlen($d['usuario']) < 4) $e[] = 'El usuario debe tener entre 4 y 30 caracteres (letras, números y guion bajo).';
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) $e[] = 'Escribe un correo electrónico válido.';
        if ($d['sexo'] === '') $e[] = 'Selecciona el sexo (M/F).';
        $e = array_merge($e, $this->validarClave($d['clave']));
        if ($d['clave'] !== $d['clave2']) $e[] = 'Las contraseñas no coinciden.';
        return $e;
    }

    // 3) Solo valida la contraseña en texto plano (antes de hashearla)
    private function validarClave($clave)
    {
        $e = [];
        if (strlen($clave) < 8 || strlen($clave) > 72) {
            $e[] = 'La contraseña debe tener entre 8 y 72 caracteres.';
        }
        if (!preg_match('/[A-Za-z]/', $clave) || !preg_match('/\d/', $clave)) {
            $e[] = 'La contraseña debe incluir al menos una letra y un número.';
        }
        return $e;
    }

    // 4) Solo consulta si un valor ya existe en una columna permitida
    private function existe($columna, $valor)
    {
        if (!in_array($columna, ['Usuario', 'Correo'], true)) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE $columna = :v LIMIT 1");
        $stmt->execute([':v' => $valor]);
        return (bool)$stmt->fetchColumn();
    }

    public function usuarioExiste($usuario) { return $this->existe('Usuario', $usuario); }
    public function correoExiste($correo)   { return $this->existe('Correo', $correo); }

    // 5) Solo inserta en la BD (secret_2fa queda NULL; se llena al confirmar el QR)
    private function insertar(array $d)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (Nombre, Apellido, Usuario, Correo, HashMagic, Sexo, FechaDelSistema)
             VALUES (:n, :a, :u, :c, :h, :s, :f)"
        );
        $stmt->execute([
            ':n' => $d['nombre'],
            ':a' => $d['apellido'],
            ':u' => $d['usuario'],
            ':c' => $d['correo'],
            ':h' => $this->hasher->generar($d['clave']),
            ':s' => $d['sexo'],
            ':f' => date('Y-m-d H:i:s'),   // la genera el servidor, no viene del usuario
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    // 6) Orquesta los pasos anteriores
    public function registrar(array $post)
    {
        $d = $this->limpiar($post);
        $errores = $this->validar($d);

        if (!$errores) {
            if ($this->usuarioExiste($d['usuario'])) $errores[] = 'Ese usuario ya está registrado.';
            if ($this->correoExiste($d['correo']))   $errores[] = 'Ese correo ya está registrado.';
        }
        if ($errores) {
            return ['ok' => false, 'errores' => $errores, 'datos' => $d, 'id' => 0];
        }
        try {
            $id = $this->insertar($d);
        } catch (PDOException $ex) {
            return ['ok' => false, 'errores' => ['No se pudo guardar el usuario.'], 'datos' => $d, 'id' => 0];
        }
        return ['ok' => true, 'errores' => [], 'datos' => $d, 'id' => $id];
    }
}
