<?php

namespace MVC;
define('NO_INIC', 0);
define('PERMISO', 1);
define('NO_PERMISO', 2);

class Router {
    public $rutas = [];
    public $rutasMiddl = [];
    public $parametros = [];
    private $ruta;
    private $metodo;
    private $controlador;
	public $link404 = ERROR404;
    private $alerts = [];

    public function route($url, $fn){
        $this->rutas[] = ["url" => $url, "funcion" => $fn];
    }

    public function middleware($controlador, $metodo, $middleware){
        $this->rutasMiddl[] = ["controlador" => $controlador, "metodo" => $metodo, "nombre" => $middleware];
    }

    public function getPath(){
        return $this->ruta;
    }

    public function getMethod(){
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    function getIP() {
        $ip = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip= $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    public function getRuta() {
        $ret = ["", "", "", ""];

        $divide = explode("/", $this->ruta);
        $numDivide = count($divide) -1;

        foreach($this->rutas as $linea) {
            $barras = substr_count($linea['url'], "/");

            $compara = "";
            for ($i=0; $i<=$barras -1; $i++) {
                if ($numDivide >= $i+1)
                    if ($divide[$i+1] != "") $compara .= "/".$divide[$i+1];
            }

            if ($compara == "") $compara = "/";

            if ($compara == $linea['url']) {
                $params = substr($this->ruta, strlen($linea['url']));
                if (strlen($params)< $ret[2] || $ret[2] == ""){
                    $middleware = "";

                    if (gettype($linea['funcion']) == 'string') 
                        $ret = $linea['funcion'];
                    else {
                        if (count($linea['funcion'])>2)
                            $middleware = $linea['funcion'][2];
                        $ret = [$linea['funcion'][0], $linea['funcion'][1], explode("/", $params), $middleware];

                        if ($ret[0] == "" && $ret[1] == "" && $ret[2] == "")
                            $ret = null;
                    }
                }
            }
        }

        return $ret;
    }

    public function getAllParams() {
        if (count($this->parametros) > 1)
            return $this->parametros;
        else
            if ($this->method == 'post')
                return $_POST;
            else
                return $_GET;
    }

    public function setParam($index, $value){
        if (is_numeric($index)) {
            $this->parametros[$index-1] = $value;
        } else {
            if ($this->getMethod() == 'post')
                $_POST[$index] = $value;
            else
                $_GET[$index] = $index;
        }
    }

    public function getParam($index) {
        if (is_numeric($index)) {
            $totalParametros = count($this->parametros);
            $inicio = 1;

            if ($totalParametros > 1) {
                if ($this->parametros[0] != "") {
                    $index--;
                    $inicio = 0;
                } else $totalParametros--;

                if ($index < $inicio || $index > $totalParametros)
                    return "null";

                return htmlspecialchars($this->parametros[$index], ENT_QUOTES, 'UTF-8');

            } else return "null";
        } else {
            if ($this->method == 'post')
                if (isset($_POST[$index]))
                    return $_POST[$index];
                else
                    return false;
            else
                if (isset($_GET[$index]))
                    return $_GET[$index];
                else
                    return false;
        }
    }

    public function countParams(){
        if (count($this->parametros) == 1)
            if ($this->parametros[0] == "")
                return 0;
            else 
                return 1;
        else
            return count($this->parametros);
    }

    public function showErrors($cant = "all", $clase = "error") {
        if (count($this->alerts) > 0)
            foreach($this->alerts as $mensaje) {
                foreach($mensaje as $key => $valor)
                    if ($key == $cant || $cant == "all")
                        echo '<div class="'.$clase.'">'.$valor.'</div>';
            }
        }

    public function getErrors(){
        return $this->alertas;
    }

    public function addErrors($errores) {
        $this->alertas += $errores;
    }
    
    public function validate($validacion, $clase = "", $inicio = false){
        if (!$inicio) $this->alerts = [];
        
        $datos = $this->getAllParams();
        foreach($datos as $campo => $dato) {
            foreach($validacion as $key => $value){
                if (gettype($value) == "string") 
                    $this->compruebaCondicion($key, $value, $campo, $dato, $clase);
                else
                    foreach($value as $v) 
                        $this->compruebaCondicion($key, $v, $campo, $dato, $clase);
            }
        }

        return count($this->alerts);
    }

    private function compruebaCondicion($key, $value, $campo, $dato, $clase){
        $vals = explode("|", $value);
        if (count($vals) > 1) {
            $code = $vals[0];
            $message = $vals[1];
        } else {
            $code = $value;
            $message = "";
        }

        $vals = explode(":", $code);
        if (count($vals) > 1) {
            $code = $vals[0];
            $val = $vals[1];
        } else $value= "";

        $msg = "";
        if ($campo == $key) {
            switch ($code) {
                case 'require':
                    if (empty($dato))
                        if ($message == "")
                            $msg = "Debe rellenar el campo ".$key;
                        else
                            $msg = $message;
                    break;
                case 'max':
                    $error = false;

                    if (is_numeric($dato)) {
                        if ($dato > $val) 
                            $error = true;
                    } else 
                        if (strlen($dato) > $val)
                            $error = true;

                    if ($error)
                        if ($message == "")
                            $msg = "El campo ".$key." debe tener una longitud máxima de ".$val." caracteres.";
                        else
                            $msg = $message;
                    break;
                case 'min':
                    $error = false;

                    if (is_numeric($dato)) {
                        if ($dato < $val) 
                            $error = true;
                    } else 
                        if (strlen($dato) < $val)
                            $error = true;

                    if ($error)
                        if ($message == "")
                            $msg = "El campo ".$key." debe tener una longitud mínima de ".$val." caracteres.";
                        else
                            $msg = $message;
                    break;
                case 'length':
                    if (strlen($dato) != $val)
                        if ($message == "")
                            $msg = "El campo ".$key." debe tener ".$val." caracteres.";
                        else
                            $msg = $message;
                    break;
                case 'start':
                    if (str_starts_with($dato, $val))
                        if ($message == "")
                            $msg = "El campo ".$key." debe empezar por ".$val.".";
                        else
                            $msg = $message;
                    break;
                case 'end':
                    if (str_ends_with($dato, $val))
                        if ($message == "")
                            $msg = "El campo ".$key." debe empezar por ".$val.".";
                        else
                            $msg = $message;
                    break;
                case 'email':
                    if (!filter_var($dato, FILTER_VALIDATE_EMAIL)) 
                        if ($message == "")
                            $msg = "El campo ".$key." debe ser un email correcto.";
                        else 
                            $msg = $message;
                    break;
                case 'text':
                    if (!is_string($dato))
                        if ($message == "")
                            $msg = "El campo ".$key." debe ser un texto.";
                        else
                            $msg = $message;
                    break;
                case 'number':
                    if (!is_numeric($dato))
                        if ($message == "")
                            $msg = "El campo ".$key." debe ser un número.";
                        else
                            $msg = $message;
                    break;
                case 'unique':
                    if (!$clase->unique($campo, $dato, $clase))
                        if ($message == "")
                            $msg = "El campo ".$key." debe ser único.";
                        else
                            $msg = $message;
                    break;
            }

            if ($msg != "")
                $this->alerts[] = [$campo => $msg];
        }
    }

    public function comprobarRutas() {
        $this->ruta = $this->limpia($_SERVER['REQUEST_URI']) ?? '/';
        $this->method = strtolower($_SERVER['REQUEST_METHOD']);

        $pos = strpos($this->ruta, "?");
        if ($pos) $this->ruta = substr($this->ruta, 0, $pos);
   
        $fn = $this->getRuta();
        if ($fn == null) 
            $this->redirecciona("error404");
        
        if (gettype($fn) == "string")
            $this->render($fn, []);
        else {
            $this->parametros = $fn[2];

            $last = strpos($fn[0], "\\") + 1;
            $nombre = substr($fn[0], $last);
            $archivo = '../app/controladores/'.$nombre.'.php';
            if (file_exists($archivo)) {
                include_once "../app/controladores/".$nombre.".php";

                if ($fn) {
                    $this->metodo = $fn[1];
                    $this->controlador = substr($fn[0], 12);
                    session_start();

                    $permiso = NO_INIC;
                    foreach($this->rutasMiddl as $md){
                        if (($md['controlador'] == substr($fn[0], 12) || $md['controlador'] == "*") && ($md['metodo'] == $fn[1] || $md['metodo'] == "*")) 
                            if (call_user_func(["MVC\Middleware", $md['nombre']], $this))
                                $permiso = PERMISO;
                            else 
                                $permiso = NO_PERMISO;
                    }

                    if ($permiso == PERMISO || $permiso == NO_INIC) {
                        if ($fn[3] != "") {
                            if (call_user_func(["MVC\Middleware", $fn[3]], $this)) 
                                call_user_func([$fn[0], $fn[1]], $this);
                            else
                                $this->redirecciona("errorMiddl");
                        } else
                            call_user_func([$fn[0], $fn[1]], $this);
                    }
                } else
                    $this->redirecciona("error404");
            } else 
                echo "ERROR: Controlador no existe.";
        }
    }

    private function redirecciona($redir) {
        if ($this->link404) 
            goHome();
        else
            $this->render($redir, []);
    }

    // Variable $titulo para definir un título de la web
    // Variable $descripción para definir una descripción en las SERP para SEO
    public function render($view, $args = []) {
        
        foreach ($args as $key => $value){
            $$key = $value;
        }
		$titulo = TITLE;
		$descripcion = DESCRIPTION;
        ob_start();
        include "app/vistas/".$view.".php";

        $contenido = ob_get_clean();

        include "app/vistas/layout.php";
    }

    private function limpia($ruta) {
        $base = substr($_SERVER['PHP_SELF'], 0, strlen($_SERVER['PHP_SELF'])-10);

        if (strlen($ruta) < strlen($base))
            return $ruta;
        
        if (substr($ruta, 0, strlen($base)) == $base)
            $ruta = substr($ruta, strlen($base), strlen($ruta) - strlen($base));

        return $ruta;
    }
}
