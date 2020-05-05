<?php


require_once './servicios/autenticador.php';
require_once './clases/usuario.php';
require_once './clases/login.php';
require_once './clases/materia.php';
require_once './clases/profesor.php';
require_once './clases/asignatura.php';


$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO']  : '';


switch ($path) {
    case '/usuario':
        switch ($method) {
            case 'POST':
                Usuario::recibirPostUsuario($_POST);
                break;
            default:
                echo 'Metodo no soportado.';
                break;
        }
        break;
    case '/login':
        switch ($method) {
            case 'POST':
                echo Login::login($_POST);
                break;
            default:
                echo 'Metodo no soportado.';
                break;
        }
        break;
    
    case '/materia':
        switch ($method) {
            case 'POST':
                Materia::recibirPostMateria($_POST);
                break;
            case 'GET':
                echo 'LISTADO DE MATERIAS:';
                echo json_encode(Materia::listadoMaterias());
                break;
            default:
                echo 'Metodo no soportado.';
                break;
        }
        break;
    case '/profesor':
        switch ($method) {
            case 'POST':
                Profesor::recibirPostProfe($_POST, $_FILES);
                break;
            case 'GET':
                echo 'LISTADO DE PROFESORES:';
                echo json_encode(Profesor::listadoProfesores());
                break;
            default:
                echo 'Metodo no soportado.';
                break;
        }
        break;
    default:
        echo 'La ruta indicada no existe.';
        break;
}




































