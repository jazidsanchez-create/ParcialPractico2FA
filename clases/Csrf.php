<?php
// Protección Anti-CSRF: token aleatorio guardado en la sesión y enviado en un campo oculto
class Csrf
{
    const CLAVE = 'csrf_token';

    private static function iniciarSesion()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    // Devuelve (y crea si no existe) el token de la sesión
    public static function token()
    {
        self::iniciarSesion();
        if (empty($_SESSION[self::CLAVE])) {
            $_SESSION[self::CLAVE] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CLAVE];
    }

    // Campo oculto listo para pegar dentro de un <form>
    public static function campo()
    {
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    // Compara el token recibido con el de la sesión (comparación segura)
    public static function validar($tokenRecibido)
    {
        self::iniciarSesion();
        return !empty($_SESSION[self::CLAVE])
            && is_string($tokenRecibido)
            && hash_equals($_SESSION[self::CLAVE], $tokenRecibido);
    }

    // Genera un token nuevo (se usa después de un login exitoso)
    public static function renovar()
    {
        self::iniciarSesion();
        unset($_SESSION[self::CLAVE]);
        return self::token();
    }
}
