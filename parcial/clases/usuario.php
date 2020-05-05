<?php

include './servicios/datos.php';

class Usuario{

public $correo;
public $clave;
public $id;

public function __construct($correo, $clave){

    $this->correo = $correo; 
    $this->clave = $clave; 
    $this->id = 0;
    $this->registrarUsuario();

}

public function registrarUsuario(){

    $usuario = $this;
    $usuario->id = Autenticador::generarId('users.json');
    $rta = Datos::guardarJson('users.json', $usuario);
    echo 'Usuario registrado! \n';

}

public static function listadoUsuarios(){
    $listado = Datos::leerJson('users.json');
    if($listado != null){
        return $listado;
    }else {
        return 'No hay usuarios registrados.';
    }
}


public static function recibirPostUsuario($array){
    $correo = $array['email'] ?? null;
    $clave = $array['clave'] ?? null;

    if ($correo != null && $clave != null) {
        if ($correo != " " && $clave != " ") {
            if (strpos($correo, '@') == true) {
                
                $usuario = new Usuario($correo, $clave);
                echo json_encode($usuario);
            }else {
                echo 'El correo electrico debe tener un @.';
            }
        }else {
            echo 'No se permiten campos vacios.';
        }
    }else {
        echo 'Falta ingresar datos.';
    }
    

}










}





















