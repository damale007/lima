<?php
require "../app/includes/includes.php";

set_exception_handler(['MVC\Error', 'exceptionHandler']);

use Modelo\ActiveRecord;
use MVC\Router;

// Conectarnos a la base de datos
//$db = conectarDB();
$db = new DataBase();

ActiveRecord::setDB($db);

$router = new Router();

include "../app/includes/routes.php";

$router->comprobarRutas();

