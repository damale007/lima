<?php
namespace Modelo;

use MVC\Error;

class ActiveRecord {
    protected static $db;
    protected static $columnasDB = [];
    protected static $tabla = '';

    protected static $alertas = [];
    protected static $limite = 0;
    protected static $paginacion = 0;
    protected static $paginaActual = 1;
    protected static $totalRegistros = 0;
    protected static $caduca = false;

    public static function setDB($database) {
        self::$db = $database;
    }

    public static function pagina($pag = 0) {
        static::$paginacion = $pag;
    }

    public static function limite($valor, $caduca = true) {
        static::$limite = $valor;
        static::$caduca = $caduca;
    }

    public static function all($orden = '') {
        $query = "SELECT * FROM ".static::$tabla;        
        $query .= static::completa($orden, $query);
        
        $resultado = self::executeSQL($query);

        return $resultado;
    }

    public static function find($id) {
        $id = validar($id, "/");

        $query = "SELECT * FROM ".static::$tabla . " WHERE id = :id LIMIT 1";
        $params = [':id' => $id];
        $resultado = self::executeSQL($query, $params);

        return array_shift($resultado);
    }

    private static function completa($orden, $q) {
        $query = " ";
        $pagina = 1;

        if ($orden !="") $query.="ORDER BY ".$orden;
        if (static::$paginacion >0){
            $resultado = self::executeSQL($q);
            static::$totalRegistros = count($resultado);

            if (isset($_GET['pagina']))
                $pagina = $_GET['pagina'];

            $inicio = ($pagina - 1) * static::$paginacion;

            if (static::$totalRegistros > static::$paginacion)
                $query .= " LIMIT ".$inicio.", ".static::$paginacion;

        } else {
            if (static::$limite > 0) $query.=" LIMIT ".static::$limite;
            if (static::$caduca) static::$limite = 0;
        }

        return $query;
    }

    private static function urlPag($pag) {
        $urlActual = $_SERVER['REQUEST_URI'] ?? '/';

        if (str_contains($urlActual, "?"))
            $urlActual .= "&pagina=".$pag;
        else
            $urlActual .= "?pagina=".$pag;

        return $urlActual;
    }

    public static function paginacion($limite, $botones, $inicio, $delimitador) {
        $ret = "";
        $numPaginas = intval(static::$totalRegistros / static::$paginacion);

        $fin = $inicio;
        switch ($inicio) {
            case "[": $fin = "]";
            break;
            case "(": $fin = ")";
            break;
            case "{": $fin = "}";
            break;
        }

        $pagina = 1;
        if (isset($_GET['pagina']))
            $pagina = $_GET['pagina'];

        $primeraPag = 1;
        $ultimaPag = $numPaginas;

        if ($limite != 0) {
            $ultimaPag = $primeraPag + $limite -1;
            if ($ultimaPag > $numPaginas)
                    $ultimaPag = $numPaginas;

            if (intval($pagina) + intval($limite / 2) < $numPaginas && intval($pagina) >= intval($limite / 2)){
                $primeraPag = $pagina - intval($limite / 2);
                $ultimaPag = $primeraPag + $limite -1;
                if ($ultimaPag > $numPaginas)
                    $ultimaPag = $numPaginas;
            } 

            if (intval($pagina) + intval($limite / 2) > $numPaginas){
                $ultimaPag = $numPaginas;
                $primeraPag = $ultimaPag - $limite +1;
                if ($primeraPag < 1)
                    $primeraPag = 1;
            }
        }

        // Botón anterior
        if ($botones) {
            if ($pagina > 1) {
                $enlace = $pagina-1;
                $ret = "<a href='".static::urlPag($enlace)."'><button class='botonIndiceAnterior'>Pag. anterior</button></a>"; 
            }
        }

        $ret .= "<span class='indice'>";
        for ($i=$primeraPag; $i<= $ultimaPag; $i++){
            if ($i != $pagina) {
                $ret .= "<span class='paginaIndice'><a href='".static::urlPag($i)."'>".$inicio.$i.$fin."</a></span>";
                if ($i < $ultimaPag) $ret .= " ".$delimitador." ";
            } else {
                $ret .= "<span class='paginaActual'>".$inicio.$i.$fin."</span>";
                if ($i < $ultimaPag) $ret .= " ".$delimitador." ";
            }
        }
        $ret .= "</span>";

        // Botón siguiente
        if ($botones) {
            if ($pagina < $numPaginas) {
                $enlace = $pagina+1;
                $ret .= "<a href='".static::urlPag($enlace)."'><button class='botonIndiceSiguiente'>Pag. siguiente</button></a>"; 
            }
        }

        return $ret;
    }

    private static function extrae($condicion, $query) {
        $excluye = [];

        if (strpos($condicion, "[") === false) {
            return [$condicion, []];
        } else {
            $sin_comillas = preg_replace('/(["\'])(?:(?=(\\?))\2.)*?\1/', '', $condicion);
            preg_match_all('/\[(.*?)\]/', $sin_comillas, $coincidencias);
            $array_resultado = $coincidencias[1];

            if (count($array_resultado) %2 !=0)
                Error::viewException("No se corresponen los campos con los valores.", $query.$condicion); 

            $param = [];
            for($i=0; $i<count($array_resultado); $i+=2) {
                $condicion = str_replace("[".$array_resultado[$i]."]", $array_resultado[$i], $condicion);
                $condicion = str_replace("[".$array_resultado[$i+1]."]", "?", $condicion);
                $param[] = $array_resultado[$i+1];
            }

            return [$condicion, $param];
        }
    }

    public static function where($condicion, $orden = "") {
        $query = "SELECT * FROM ".static::$tabla . " WHERE ";

        $campos = self::extrae($condicion, $query);
        $query .= $campos[0];
        $query .= static::completa($orden, $query);
        
        $resultado = self::executeSQL($query, $campos[1]);

        return $resultado;
    }

    public function unique($campo, $valor, $clase){
        $comillas = "";
        if (!is_numeric($valor))
            $comillas = "'";

        $resultado = self::where($campo."=".$comillas.$valor.$comillas);
        if (count($resultado) > 0) {
            if (count($resultado) == 1 && $clase->id == $resultado[0]->id)
                return true;
            else
                return false;
        } else
            return true;
    }

    public static function consultarSQL($query) {
        $campos = self::extrae($query, "");
        return self::executeSQL($campos[0], $campos[1]);
    }

    public static function executeSQL($query, $params = []) {
        Error::setQuery($query, $params);
        $resultado = self::$db->query($query, $params);
        Error::resetQuery();

        $array = [];
        while($result = $resultado->fetch()) {
            $array[] = $objeto = self::creaObjeto($result);
        }

        return $array;
    }

    public static function ejecutarSQL($query) {
        $return = self::$db->query($query);

        return $return;
    }

    public static function transaction($tipo) {
        self::$db->transaction($tipo);
    }

    public static function creaObjeto($resultado) {
        $objeto = new static;

        foreach ($resultado as $key => $valor) {
            $objeto->$key = $valor;
        }
        return $objeto;
    }

    public function guardar() {
        $resultado = '';
        if(is_null($this->id) || $this->id == '') {
            $resultado = $this->crear();
        } else {
            // Creando un nuevo registro
            $resultado = $this->actualizar();
        }
        return $resultado;
    }

    public function crear() {
        // Sanear los datos
        $atributos = $this->sanearAtributos();

        $claves = [];
        $params = [];
        foreach($atributos as $key => $value) {
            $claves[] = "{$key}";
            
            if ($value == 'null')
                $value = null;
            if ($value == "[EMPTY]")
                $value = "";

            $params[] = $value;
        }

        // Insertar en la base de datos
        $query = " INSERT INTO " . static::$tabla . " ( ";
        $query .= join(', ', $claves);
        $query .= " ) VALUES (";
        for ($i=0; $i<count($params); $i++) 
            if ($i < count($params) -1)
                $query .= "?, ";
            else
                $query .= "?";

        $query .= ") ";

        // Resultado de la consulta
        $resultado = self::executeSQL($query, $params);
        return [
           'resultado' =>  $resultado,
           'id' => self::$db->lastIndex()
        ];
    }

    // Sincroniza BD con Objetos en memoria
    public function sincronizar($args=[]) { 
        foreach($args as $key => $value) {
            if(property_exists($this, $key) && !is_null($value)) {
            $this->$key = $value;
            }
        }
    }

    // Actualizar el registro
    public function actualizar() {
        // Sanitizar los datos

        $atributos = $this->sanearAtributos();

        // Iterar para ir agregando cada campo de la BD
        $valores = [];
        $params = [];
        foreach($atributos as $key => $value) {
            $valores[] = "{$key}= ?";
            $params[] = $value;
        }

        // Consulta SQL
        $query = "UPDATE " . static::$tabla ." SET ";
        $query .=  join(', ', $valores );
        $query .= " WHERE id = ?";
        $query .= " LIMIT 1 "; 

        $params[] = $this->id;

        // Actualizar BD
        return self::executeSQL($query, $params);
    }

    // Eliminar un Registro por su ID
    public function eliminar() {
        $query = "DELETE FROM "  . static::$tabla . " WHERE id = ? LIMIT 1";
        return self::executeSQL($query, [$this->id]);
    }

    public function sanearAtributos() {
        $atributos = $this->atributos();
        $saneado = [];
        foreach($atributos as $key => $value ) {
            if ($value != null) {
                if ($value == -1)
                    $saneado[$key] = 'null';
                else {
                    $saneado[$key] = $value;
                    $saneado[$key] = trim($saneado[$key]);
                }
            } else
                if ($value != 0)
                    $saneado[$key] = 'null';
                else
                    $saneado[$key] = $value;
        }
        return $saneado;
    }

    public function atributos() {
        $atributos = [];
        foreach(static::$columnasDB as $columna) {
            if($columna === 'id') continue;
            $atributos[$columna] = $this->$columna;
        }
        return $atributos;
    }
}