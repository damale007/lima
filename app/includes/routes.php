<?php
	use Controlador\HomeController;

	$router->route('/', [HomeController::Class, 'home']);
