<?php

class Datos {


    public static function guardarJSON($archivo, $objeto)
    {   
        $arrayJSON = array();
        // LEEMOS
        if(is_file($archivo)){

            $file = fopen($archivo, 'r+');

            $arrayString = fread($file, filesize($archivo));
    
            $arrayJSON = json_decode($arrayString);
    
            fclose($file);
        }

        array_push($arrayJSON, $objeto);

        // ESCRIBIMOS
        $file = fopen($archivo, 'w');

        $rta = fwrite($file, json_encode($arrayJSON));

        fclose($file);

        return $rta;
    }


    public static function leerJSON($archivo) {

        $arrayJSON = array();
        if(is_file($archivo)){
            $file = fopen($archivo, 'r+');

            $arrayString = fread($file, filesize($archivo));

            $arrayJSON = json_decode($arrayString);

            fclose($file);

            return $arrayJSON;
        }else {
            return null;
        }

        
    }





















    public static function leerTodo($archivo) {
        $file = fopen($archivo, 'r');

        $lista = array();

        while (!feof($file)) {
            # code...
            $linea = fgets($file);

            $explode = explode('@', $linea);
            
            if (count($explode)  > 1) {
                array_push($lista, $explode);
            }
        }

        fclose($file);
        
        return $lista;
    }
}