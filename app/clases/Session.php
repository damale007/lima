<?php
namespace MVC;

class Session {
    public static function set($clave, $valor, $encrypt = false) {
        if ($clave == "sesionesEncriptadas")
            echo "ERROR: No puede usar una sesión con la clave sesionesEncriptadas, ya que es de uso interno del Framework";

        if ($encrypt) {
            $_SESSION[$clave] = encode($valor);
            static::add($clave);
        } else {
            $_SESSION[$clave] = $valor;
            static::remove($clave);
        }
    }

    public static function get($clave) {
        if (!isset($_SESSION[$clave])) 
            return false;

        if (static::exist_cr($clave)) 
            return decode($_SESSION[$clave]);
        else
            return $_SESSION[$clave];
    }

    public static function clear() {
        $_SESSION = [];
    }

    public static function exist($clave) {
        if (isset($_SESSION[$clave]))
            return true;
        else
            return false;
    }

    private static function add($clave) {
        if (!isset($_SESSION['sesionesEncriptadas']))
            $_SESSION['sesionesEncriptadas'] = $clave;
        else {
            if (strpos($_SESSION['sesionesEncriptadas'], $clave) == false)
                $_SESSION['sesionesEncriptadas'] .= "|".$clave;
        }
    }

    private static function remove($clave) {
        if (!isset($_SESSION['sesionesEncriptadas'])) return;

        $valores = explode("|", $_SESSION['sesionesEncriptadas']);
        $valoresOk = [];

        foreach($valores as $v) {
            if ($v != $clave) 
                $valoresOk[] = $v;
        }

        $_SESSION['sesionesEncriptadas'] = implode("|", $valoresOk);
    }

    private static function exist_cr($clave) {
        if (!isset($_SESSION['sesionesEncriptadas'])) return false;
        
        $valores = explode("|", $_SESSION['sesionesEncriptadas']);
        $valoresOk = [];

        foreach($valores as $v) {
            if ($v == $clave) 
                return true;
        }

        return false;
    }
}