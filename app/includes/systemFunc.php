<?php
include "config/variables.php";

use MVC\Session;

function incluirTemplate( string  $nombre) {
    include TEMPLATES_URL . "/${nombre}.php"; 
}

function validar($id, string $url) {
    $id = filter_var($id, FILTER_VALIDATE_INT);

    if(!$id) {
        header("Location: ${url} " );
    }

    return $id;
}

function debug($variable, $parar = true) {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";

    if ($parar) exit;
}

function loadURL($url) {
    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_HEADER, true);  //queremos las cabeceras de la respuesta
    curl_setopt($ch, CURLOPT_NOBODY, false);  //no queremos el cuerpo de la respuesta
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //el resultado lo almacenaremos en una cadena
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

function createFormToken() {
    Session::set('FormToken', bin2hex(random_bytes(16)));
    echo '<input type="hidden" value="'.Session::get('FormToken').'" name="token">';
}

function getFormToken($result) {
    $datos = $result->getAllParams();
    if (!isset($datos['token']))
        return false;

    if ($datos['token'] == Session::get('FormToken'))
        return true;

    return false;
}

function dateFormat($fecha, $larga = false, $hora = false) {
    $fechaString = "";

    $meses = array("", "enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre");
    $fecOrigen = explode(" ", $fecha);
    if (count($fecOrigen)>1) 
        $origen = $fecOrigen[0];
    else
        $origen = $fecha;

    $fec = explode("-", $origen);
    if ($larga)
        $fechaString = $fec[2]." de ".$meses[intval($fec[1])]." de ".$fec[0];
    else 
        $fechaString = $fec[2]." ".$meses[intval($fec[1])]." ".$fec[0];

    if ($hora) {
        if (count($fecOrigen) > 1) {
            $hor = explode(":", $fecOrigen[1]);
            if (count($hor) > 2) {
                $fechaString .= " - ".$hor[0].":".$hor[1]." h.";
            }
        }
    }

    return $fechaString;
}

function cropText($texto, $longitud) {
    if (strlen($texto) < $longitud)
        return $texto;

    $pos = $longitud;
    while (substr($texto, $pos, 1) != " " && $pos > 0) {
        $pos--;
    }

    if ($pos == 0)
        return substr($texto, 0, $longitud)."...";
    else
        return substr($texto, 0, $pos)."...";
}

function goHome(){
    go("/");
}

function go($url) {
    header('location: '.$url);
}

function dni($dni) {
    $letra = substr($dni, -1);
    $numeros = substr($dni, 0, -1);
    if ( substr("TRWAGMYFPDXBNJZSQVHLCKE", $numeros%23, 1) == $letra && strlen($letra) == 1 && strlen ($numeros) == 8 ){
      return true;
    }else{
      return false;
    }
}

function sendEmail($destino, $titulo, $mensaje) {
    $cabeceras = "From: ".EMAIL_WEB."\r\n" .
			"Reply-To: ".EMAIL_WEB . "\r\n" .
			'X-Mailer: PHP/' . phpversion();

    if (gettype($destino) == "string")
	    mail($destino, $titulo, $mensaje, $cabeceras);
    else {
        foreach($destino as $email){
            mail($email, $titulo, $mensaje, $cabeceras);
        }
    }
}

function encode($texto_plano, $clave = "") {
    $METODO_CIFRADO = 'AES-256-CBC';

    if ($clave == "")
        $clave = LLAVESECRETA;

    $iv_longitud = openssl_cipher_iv_length($METODO_CIFRADO);
    $iv = openssl_random_pseudo_bytes($iv_longitud);

    $texto_cifrado = openssl_encrypt(
        $texto_plano,
        $METODO_CIFRADO,
        $clave,
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($texto_cifrado === false) 
        return false; 

    $datos_combinados = $iv . $texto_cifrado;

    return base64_encode($datos_combinados);
}

function decode($mensaje_encriptado, $clave = "") {
    $CIPHER = 'AES-256-CBC';

    if ($clave == "")
        $clave = LLAVESECRETA;

    $datos_combinados = base64_decode($mensaje_encriptado);
    if ($datos_combinados === false) 
        return false;

    $iv_longitud = openssl_cipher_iv_length($CIPHER);
    if (strlen($datos_combinados) < $iv_longitud) 
        return false;

    $iv = substr($datos_combinados, 0, $iv_longitud);

    $texto_cifrado = substr($datos_combinados, $iv_longitud);
    if ($texto_cifrado === false || empty($texto_cifrado)) 
        return false;

    $texto_plano = openssl_decrypt(
        $texto_cifrado,
        $CIPHER,
        $clave,
        OPENSSL_RAW_DATA, // Usamos OPENSSL_RAW_DATA ya que la entrada es binaria.
        $iv
    );

    return $texto_plano;
}

function newToken() {
    return md5(uniqid());
}

function isAdmin() {
    $rol = Session::get('rol');

    if (!$rol || $rol > 1)
        goHome();
    else 
        return true;
}

function isUser() {
    if(Session::get('rol') >= 5) 
        return true;
    else
        return false;
}

function getFiles($ruta) {
    return array_diff(scandir($ruta), array('.', '..'));
}

function getID() {
    return (Session::get('id'));
}

function getAccessToken(){
    require_once "Lib/vendor/autoload.php";

    $client = new Google\Client();
    $client->setAuthConfig("../includes/service-acount.json");
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
    
    return $accessToken;
}

function sendPush($tokens, $id, $titulo, $message, $tipo, $texto, $imagen, $localizacion, $fecha) {
    $msg = array
    (
        'message'   => $message,
        'title'     => $titulo,
        'imagen'  => $imagen,
        'tipo'    => $tipo,
        'texto'   => $texto,
        'fecha'     => $fecha,
        'localizacion' => $localizacion,
        'id' => $id
    );

    $accessToken = getAccessToken();

    $headers = array (
        "Authorization: Bearer ".$accessToken."",
        "content-type:application/json;UTF-8"
    );

    $ch = curl_init ();
    curl_setopt ($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/'.API_FCM.'/messages:send'); 
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);

    foreach($tokens as $reg) {
        $datos["message"] = array(
            'token' => $reg,
            'data' => $msg
        );

        curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($datos));

        $result = curl_exec ($ch);
    }
    curl_close ($ch);
}
