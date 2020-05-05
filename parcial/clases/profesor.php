<?php

require_once './servicios/datos.php';
require_once './servicios/autenticador.php';
require_once 'usuario.php';

class Profesor{

    public $nombre;
    public $foto;
    public $legajo;


    public function __construct($nombre, $legajo, $foto){

        $this->nombre = $nombre; 
        $this->foto = $foto; 
        $this->legajo = $legajo;
        $this->registrarProfesor();
    
    }
    
    public function registrarProfesor(){
    
        $profe = $this;

        $usuario = Autenticador::chequearId(Autenticador::recibirToken());
        if (Profesor::validarUsuario($usuario) == 0) {
            if (Profesor::validarLegajo($profe->legajo) == 0) {

                $rta = Datos::guardarJson('profesores.json', $profe);
                Profesor::guardarImagen($profe);
                echo 'Profesor registrado! \n';

            }else {
                return 1;
            }
            
        }else {
            return 1;
        }
    
    }
    
    public static function listadoProfesores(){
        $listado = Datos::leerJson('profesores.json');
        $usuario = Autenticador::chequearId(Autenticador::recibirToken());
        if (Materia::validarUsuario($usuario) == 0) {
            if($listado != null){
                return $listado;
            }else {
                return 'No hay profesores registrados.';
            }
        }
    }
    
    
    public static function recibirPostProfe($array, $file){
        $nombre = $array['nombre'] ?? null;
        $legajo = $array['legajo'] ?? null;
        $foto = $file['imagen'] ?? null;

        if ($nombre != null && $legajo != null && $foto != null) {
            if ($nombre != " " && $legajo != " " && $foto != " ") {
                    $profe = new Profesor($nombre, $legajo, $foto);
                    echo json_encode($profe);
            }else {
                echo 'No se permiten campos vacios.';
            }
        }else {
            echo 'Falta ingresar datos.';
        }
        
    
    }
    
    public static function validarLegajo($legajo){

        $array = Profesor::listadoProfesores();
        if (is_string($array)) {
            return $array;
        }else {
            $listado = array($array);
        }
        if (is_array($listado)) {
            
            foreach ($listado as $value) {
                for ($i=0; $i < count($value); $i++) { 
                    if ($value[$i]->legajo != $legajo) {
                        return 0;
                    }
                }
                echo 'Ese legajo ya existe.';
                return 1;
            }
        }else {
            echo $listado;
        }
    
    }

    public static function validarUsuario($id){

        $array = Usuario::listadoUsuarios();
        $listado = array($array); 
    
        if (is_array($listado)) {
            
            foreach ($listado as $value) {
                for ($i=0; $i < count($value); $i++) { 
                    if ($value[$i]->id == $id) {
                        return 0;
                    }
                }
                echo 'Usuario invalido.';
                return 1;
            }
        }else {
            echo $listado;
        }
    
    }
    
    
    
    public static function guardarImagen($objeto){

        $imagen = $objeto->foto;
        $legajo = $objeto->legajo;
        $nombre = $legajo . ' - ' . $imagen['name'];
        $origen = $imagen['tmp_name'];
        $destino = './imagenes/' . $nombre;
        $rta = move_uploaded_file($origen, $destino);
        
    }




















































}











































