<?php
// Clase que encapsula todo lo de 2FA (TOTP / Google Authenticator)
require_once __DIR__ . '/../vendor/autoload.php'; // lo crea: composer require sonata-project/google-authenticator

require_once __DIR__ . '/SanitizarEntrada.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

class Auth2FA
{
    const APP = 'EjemploLogin';   // nombre que verás dentro de Google Authenticator

    private $pdo;
    private $g;

    public function __construct($db)   // $db es el objeto mod_db de mysql.inc.php
    {
        $this->pdo = $db->getConexion();
        $this->g   = new GoogleAuthenticator();
    }

    // Genera un secreto nuevo (base32) para un usuario
    public function generarSecreto()
    {
        return $this->g->generateSecret();
    }

    // Guarda (o borra si es null) el secreto del usuario en la columna secret_2fa
    public function guardarSecret($idUsuario, $secret)
    {
        // el secreto debe ser base32 válido (o null para desactivar el 2FA)
        if ($secret !== null && !SanitizarEntrada::validarSecreto2FA($secret)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE usuarios SET secret_2fa = :s WHERE id = :id");
        return $stmt->execute([':s' => $secret, ':id' => (int)$idUsuario]);
    }

    // Lee el secreto guardado del usuario (null si no tiene 2FA activo)
    public function obtenerSecret($idUsuario)
    {
        $stmt = $this->pdo->prepare("SELECT secret_2fa FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => (int)$idUsuario]);
        $s = $stmt->fetchColumn();
        return ($s === false || $s === '' || $s === null) ? null : $s;
    }

    // Verifica el código de 6 dígitos (acepta +-30 s de desfase de reloj)
    public function verificar($secret, $codigo)
    {
        $codigo = preg_replace('/\D/', '', (string)$codigo);
        if ($secret === null || $secret === '' || strlen($codigo) !== 6) {
            return false;
        }
        return $this->g->checkCode($secret, $codigo);
    }

    // URI estándar que entiende Google Authenticator
    public function urlOtpauth($correo, $secret)
    {
        return 'otpauth://totp/' . rawurlencode(self::APP . ':' . $correo)
             . '?secret=' . $secret . '&issuer=' . rawurlencode(self::APP);
    }

    // URL de la imagen QR (se genera dinámicamente, como pide la guía)
    public function urlQr($correo, $secret)
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data='
             . urlencode($this->urlOtpauth($correo, $secret));
    }
}
