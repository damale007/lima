<?php
require_once '../app/includes/app.php';

use MVC\Router;
use Controlador\HomeController;

$router = new Router();

	$router->get('/', [HomeController::Class, 'home']);

$router->comprobarRutas();
?>
