<?php

namespace Controlador;
use MVC\Router;


class HomeController {
    public static function home(Router $router){
        $helloWorld = "Esta es mi primera página";

        $router->render('home', [
            'helloWorld' => $helloWorld
        ]);
    }
}