<?php
// INTERFAZ para generar y validar hashes de contraseña
interface IHashPassword
{
    public function generar($clave);               // devuelve el hash
    public function validar($clave, $hash);        // true si la clave coincide con el hash
    public function necesitaRehash($hash);         // true si el hash está desactualizado
}

// Implementación con bcrypt
final class PasswordHasher implements IHashPassword
{
    private $opciones = ['cost' => 12];

    public function generar($clave)
    {
        return password_hash($clave, PASSWORD_BCRYPT, $this->opciones);
    }

    public function validar($clave, $hash)
    {
        return password_verify($clave, $hash);
    }

    public function necesitaRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, $this->opciones);
    }
}
