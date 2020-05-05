<?php

require_once './servicios/autenticador.php';
require_once 'usuario.php';


class Login{


public function __construct(){}


public static function login($usuario){

    $correo = $usuario['email'] ?? null;
    $clave = $usuario['clave'] ?? null;

    $array = Usuario::listadoUsuarios();
    if (is_string($array)) {
        return $array;
    }else {
        $listado = array($array);
    }

    if ($correo != null && $clave != null) {
        if ($correo != " " && $clave != " ") {
           
            if (is_array($listado)) {
        
                foreach ($listado as $value) {
                    
                    for ($i=0; $i < count($value); $i++) { 
                        
                        if ($value[$i]->correo == $correo && $value[$i]->clave == $clave) {
                            
                            $token = Autenticador::generarToken($value[$i]->correo, $value[$i]->id);
                            return 'Usuario logueado. Su token es: ' . $token;
                        }
                    }
                    
                    echo 'Usuario no encontrado o inexistente.';
                }
            }

        }else {
            echo 'No se permiten campos vacios.';
        }
    }else {
        echo 'Falta ingresar datos.';
    }
    
    

    

}




























}



























