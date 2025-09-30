<?php
namespace Modelo;

class Migrations extends ActiveRecord{
    public function create(){
    }

    public static function createTable($tabla, $atributos) {
        $sql = createNewTable($tabla, $atributos);
        self::ejecutarSQL('DROP TABLE IF EXISTS '.$tabla.';');
        foreach($sql as $l) {
            self::ejecutarSQL($l);
        }
    }
}