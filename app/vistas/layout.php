<?php 
    if (!isset($inicio)) $inicio = "";

    include "cabecera.php";
    echo $contenido;
    include "footer.php";

    if (isset($script)) {
        if (gettype($script)=="string")
            echo "<script src='/js/${script}.js'></script>";
        else {
            foreach ($script as $sc)
                echo "<script src='/js/${sc}.js'></script>";
        }
    }