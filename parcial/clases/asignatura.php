<?php

require_once './servicios/datos.php';
require_once './servicios/autenticador.php';
require_once 'usuario.php';

class Asignatura{


    public $legajo;
    public $id;
    public $turno;

    public function __construct($legajo, $id, $turno){

        $this->legajo = $legajo; 
        $this->foto = $foto; 
        $this->turno = $turno;
        $this->registrarAsignatura();
    
    }
    
    public function registrarAsignatura(){
    
        $asig = $this;

        $usuario = Autenticador::chequearId(Autenticador::recibirToken());
        if (Profesor::validarUsuario($usuario) == 0) {
            if (Profesor::validarLegajo($asig->legajo) == 0) {

                $rta = Datos::guardarJson('materias-profesores.json', $asig);
                Profesor::guardarImagen($asig);
                echo 'Asignatura registrada! \n';

            }else {
                return 1;
            }
            
        }else {
            return 1;
        }
    
    }
    
    public static function listadoAsignatura(){
        $listado = Datos::leerJson('materias-profesores.json');
        if($listado != null){
            return $listado;
        }else {
            return 'No hay asignaturas registradas.';
        }
        
    }
    
    
    public static function recibirPostAsig($array){
        $id = $array['id'] ?? null;
        $legajo = $array['legajo'] ?? null;
        $turno = $array['turno'] ?? null;

        if ($id != null && $legajo != null && $turno != null) {
            if ($id != " " && $legajo != " " && $turno != " ") {
                    $asig = new Asignatura($legajo, $id, $turno);
                    echo json_encode($asig);
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
    












































}




















































