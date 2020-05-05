<?php

    require_once './composer/vendor/autoload.php';
    use \Firebase\JWT\JWT;

class Autenticador 
{   
   
    public function __construct(){}


    public static function generarToken($userMail, $userId){
        
        $key = "prog3-parcial";
        $payload = array(
            "iat" => 1356999524,
            "nbf" => 1357000000,
            "user_mail" => $userMail,
            "user_id" => $userId
        );

        $token = JWT::encode($payload, $key);

        return $token;
       
    }

    public static function recibirToken(){
        $headers = getallheaders();
        return $headers['token'] ?? null;
    }


    public static function chequearTipo($token){

        try {

            $decoded = JWT::decode($token, 'prog3-parcial', array('HS256'));
            $decoded_array = (array) $decoded;
            return $decoded_array['user_type'];

        } catch (\Throwable $th) {
            echo 'ERROR, token invalido.';
        }
 
    }

    public static function chequearId($token){

        try {

            $decoded = JWT::decode($token, 'prog3-parcial', array('HS256'));
            $decoded_array = (array) $decoded;
            return $decoded_array['user_id'];

        } catch (\Throwable $th) {
            echo 'ERROR, token invalido.';
        }

    }


    public static function generarId($archivo){

        $id = 1;
    
        if (is_file($archivo)) {
            
            $arrayJSON = Datos::leerJSON($archivo);
    
            $listado = array($arrayJSON);
    
            foreach ($listado as $value) {
    
                for ($i=0; $i < count($value); $i++) { 
                    $id = $i + 2;
                }
            }
                
            return $id;
        }else {
    
            return $id;
        }
        
    
    }

    public static function generarIdSerial($archivo){

        $id = 1;
    
        if (is_file($archivo)) {
            
            $array = Datos::leerSerial($archivo);
            $listado = array($array);
            
            if ($listado != null) {
                foreach ($listado as $value) {
    
                    for ($i=0; $i < count($value); $i++) { 
                        $id = $i + 2;
                    }
                }
                return $id;
            }

        }else {
    
            return $id;
        }
        
    
    }















    
}
















