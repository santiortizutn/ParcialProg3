<?php

require_once './servicios/datos.php';
require_once './servicios/autenticador.php';


class Materia{


    public $nombre;
    public $cuatrimestre;
    public $id;
    
    public function __construct($nombre, $cuatrimestre){
    
        $this->nombre = $nombre; 
        $this->cuatrimestre = $cuatrimestre; 
        $this->id = 0;
        $this->registrarMateria();
    
    }
    
    public function registrarMateria(){
    
        $materia = $this;
        $usuario = Autenticador::chequearId(Autenticador::recibirToken());
        if (Materia::validarUsuario($usuario) == 0) {
            $materia->id = Autenticador::generarId('materias.json');
            $rta = Datos::guardarJson('materias.json', $materia);
            echo 'Materia registrada! \n';
        }else {
            return 1;
        }

    
    }
    
    public static function listadoMaterias(){
        $listado = Datos::leerJson('materias.json');
        $usuario = Autenticador::chequearId(Autenticador::recibirToken());
        if (Materia::validarUsuario($usuario) == 0) {
            if($listado != null){
                return $listado;
            }else {
                return 'No hay materias registradas.';
            }
        }
    }
    
    
    public static function recibirPostMateria($array){
        $nombre = $array['nombre'] ?? null;
        $cuatri = $array['cuatrimestre'] ?? null;
    
        if ($nombre != null && $cuatri != null) {
            if ($nombre != " " && $cuatri != " ") {                
                    
                $materia = new Materia($nombre, $cuatri);
                echo json_encode($materia);

            }else {
                echo 'No se permiten campos vacios.';
            }
        }else {
            echo 'Falta ingresar datos.';
        }
        
    
    }

    public static function validarUsuario($id){

        $array = Usuario::listadoUsuarios();
        if (is_string($array)) {
            return $array;
        }else {
            $listado = array($array);
        }
    
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




























































