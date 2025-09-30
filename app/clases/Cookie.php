<?php
namespace MVC;

class Cookie {
    public static function set($clave, $valor, $duracion, $seguridad = false) {
        $tiempo = $duracion * 24 * 60 * 60 + time();
        setcookie($clave, $valor, $tiempo, "", "", $seguridad, !$seguridad);
    }

    public static function get($clave) {
        if (!isset($_COOKIE[$clave]))
            return false;

        return $_COOKIE[$clave];
    }

    public static function reset($clave) {
        if (isset($_COOKIE[$clave]))
            static::set($clave, "", 0);
    }

    public static function exist($clave) {
        if (isset($_COOKIE[$clave]))
            return true;
        else
            return false;
    }
}