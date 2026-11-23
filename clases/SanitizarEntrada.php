<?PHP
// Clase con métodos ESTÁTICOS para sanitizar y validar datos de entrada
class SanitizarEntrada {

    // 1) Quita espacios y etiquetas HTML
    public static function limpiarCadena($cadena) {
        return trim(strip_tags((string)$cadena));
    }

    // 2) Nombres/apellidos: solo letras (con tildes), espacios, apóstrofe y guion
    public static function limpiarNombre($nombre) {
        $n = self::limpiarCadena($nombre);
        $n = preg_replace('/[^\p{L}\s\'\-]/u', '', $n);
        return trim(preg_replace('/\s+/', ' ', $n));
    }

    // 3) Usuario: solo letras, números y guion bajo (máximo 30)
    public static function limpiarUsuario($usuario) {
        $u = preg_replace('/[^A-Za-z0-9_]/', '', self::limpiarCadena($usuario));
        return substr($u, 0, 30);
    }

    // 4) Correo: minúsculas y solo caracteres permitidos en un email
    public static function limpiarCorreo($correo) {
        $c = strtolower(self::limpiarCadena($correo));
        return (string)filter_var($c, FILTER_SANITIZE_EMAIL);
    }

    // 5) Sexo: solo se acepta un valor de la lista fija (M o F)
    public static function validarSexo($sexo) {
        $s = strtoupper(self::limpiarCadena($sexo));
        return in_array($s, ['M', 'F'], true) ? $s : '';
    }

    // 6) Secreto 2FA: cadena base32 válida (A-Z, 2-7 y =)
    public static function validarSecreto2FA($secreto) {
        return is_string($secreto) && preg_match('/^[A-Z2-7=]+$/', $secreto) === 1;
    }

    // 7) Código 2FA: solo dígitos
    public static function limpiarCodigo($codigo) {
        return preg_replace('/\D/', '', (string)$codigo);
    }

    // 8) Enteros
    public static function limpiarEntero($valor) {
        return (int)filter_var($valor, FILTER_SANITIZE_NUMBER_INT);
    }

    // 9) Para imprimir en pantalla sin riesgo de XSS
    public static function escaparHtml($texto) {
        return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
    }
}
?>
