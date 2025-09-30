<?php
function autocargar($clase) {
    $last = strpos($clase, "\\") + 1;
    $nombre = substr($clase, $last);

    if ($nombre != "Router")
        include "../app/modelos/" . $nombre . ".php";
}

spl_autoload_register('autocargar');
include "../Router.php";