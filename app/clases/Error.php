<?php
namespace MVC;

class Error {
    protected static $query = "";

    public static function exceptionHandler($exception) {
        http_response_code(500);
        Error::showError($exception);
        echo "<div style= 'margin: 40px;'>";

        echo "<h2>Rastreo del error:</h2>";
        $inic = false;
        foreach ($exception->getTrace() as $err) {
            if (isset($err['file'])) {
                if (!$inic) 
                    echo "<br><br>";
                else 
                    $inic = false;

                echo "<b>".$err['file']." en la línea ".$err['line']."</b><br>";
            }
            
            if (isset($err['class']))
                echo "Clase: ".$err['class']." - Función: ".$err['function']."<br>"; 
        }

        echo "</div>";
        die();
    }

    public static function viewException($mensaje, $condicion) {
        http_response_code(500);
        Error::showErrorMini($mensaje, $condicion);
        die();
    }

    public static function setQuery($query, $params = []) {
        if ($params != null && count($params) >0) {
            $claves = array_keys($params);
            $valores = array_values($params);

            for($i=0; $i<count($claves); $i++) 
                $query = str_replace($claves[$i], "'".$valores[$i]."'", $query);
        }

        Error::$query = $query;
    }

    public static function resetQuery() {
        Error::$query = "";
    }

    private static function showError($exception) {
        echo "<div style='background: red; color: white; margin: 40px; padding: 20px;'>";
        echo "<h1>¡Oops! Algo salió mal.</h1> <b>Error: " . $exception->getMessage();
        if (Error::$query != "")
            echo "<br><br>SQL erróneo: </b>".Error::$query;
        else
            echo "</b>";

        echo "<br><br>Error en el archivo: ".$exception->getFile();
        echo "</div>";
    }

    private static function showErrorMini($exception, $condicion) {
        echo "<div style='background: red; color: white; margin: 40px; padding: 20px;'>";
        echo "<h1>¡Oops! Algo salió mal.</h1> <b>Error: " . $exception;
            echo "<br><br>SQL erróneo: </b>".$condicion;

        echo "</div>";
    }
}