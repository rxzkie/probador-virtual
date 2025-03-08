<?php

$hostname_con1 = "localhost";
$database_con1 = "asd123";
$username_con1 = "root";
$password_con1 = "";
$tabla_categorias = "categorias";
$tabla_prendas = "prendas";
$tabla_modelos = "modelos";
$tabla_usuarios = "usuarios";


$con1 = mysqli_connect($hostname_con1, $username_con1, $password_con1, $database_con1,3306);
mysqli_set_charset($con1,'utf8');

if (!$con1) {
    echo "Error: No se pudo conectar a MySQL.<br>" . PHP_EOL;
    echo "error de depuración: " . mysqli_connect_errno() . PHP_EOL;
    echo "<br>error de depuración: " . mysqli_connect_error() . PHP_EOL;
    exit;
}
 


//UPLOAD
// define('BASE_URL', '/Inscripciones_Web/2025/'.$codEvento);
$evento = "Probador Virtual";
$codEvento = "probador-virtual";
$urlRaiz="https://sartoriacielomilano.com/".$codEvento."/"; 
$carpetaUploads="Uploads/";
$carpetaTrabajos="Uploads/";

// PARA VER EL LINK DEL ARCHIVO SUBIDO
 function generarLinkImagen($imgNombre) {
                    //   include 'configuracion.php';
                    //   $urlRaiz="equipamiento/Uploads/";
                      $carpetaUploads="../Uploads/";
                      if($imgNombre==""){ return "-";}
                      else if($imgNombre=="-"){ return "-";}
                      else{		
                          $href = $carpetaUploads.$imgNombre; 		
                        $link="<a target='_blank' href='".$href."'>".$imgNombre."</a>";
                        return $link;
                        } 
                      }

if (!function_exists("GetSQLValueString")) {
  function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
  {
    global $con1;
  
    $theValue = function_exists("mysqli_real_escape_string") ? mysqli_real_escape_string($con1,$theValue) : mysqli_escape_string($con1,$theValue);
  
    switch ($theType) {
      case "text":
        $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
        break;    
      case "long":
      case "int":
        $theValue = ($theValue != "") ? intval($theValue) : "NULL";
        break;
      case "double":
        $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
        break;
      case "date":
        $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
        break;
      case "defined":
        $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
        break;
    }
    return $theValue;
  }
} 

// Función para procesar el archivo subido, verificar si es AVIF y guardarlo con nombre personalizado
function procesarArchivo($nombreCampo, $nombrePersonalizado, $esMiniatura = false, $rutaPersonalizada = '') {
    // Array para almacenar respuesta
    $respuesta = array('estado' => false, 'mensaje' => '', 'nombre_archivo' => '');
    
    // Verificar si existe el archivo
    if(isset($_FILES[$nombreCampo]) && $_FILES[$nombreCampo]['error'] == 0) {
        // Obtener información del archivo
        $archivo_nombre = $_FILES[$nombreCampo]['name'];
        $archivo_tmp = $_FILES[$nombreCampo]['tmp_name'];
        $archivo_tipo = $_FILES[$nombreCampo]['type'];
        $archivo_tamano = $_FILES[$nombreCampo]['size'];
        
        // Obtener la extensión del archivo
        $archivo_ext = strtolower(pathinfo($archivo_nombre, PATHINFO_EXTENSION));
        
        // Verificar si es un archivo AVIF
        if($archivo_ext != 'avif') {
            $respuesta['mensaje'] = "Solo se permiten archivos AVIF";
            return $respuesta;
        }
        
        // Verificar tamaño máximo (ajustar según necesidades, 5MB en este ejemplo)
        if($archivo_tamano > 1048576) {
            $respuesta['mensaje'] = "El archivo es demasiado grande (máximo 1MB)";
            return $respuesta;
        }
        
        // Construir el nombre de archivo según sea miniatura o no
        if ($esMiniatura) {
            $nombre_nuevo = 'tn-' . $nombrePersonalizado . '.avif';
            $nombre_archivo = 'tn-' . $nombrePersonalizado;
            $directorio_destino = $rutaPersonalizada . '/miniaturas/';
        } else {
            $nombre_nuevo = $nombrePersonalizado . '.avif';
            $nombre_archivo = $nombrePersonalizado;
            $directorio_destino = $rutaPersonalizada . '/';
        }
        
        // Asegurarse de que existe el directorio
        if(!is_dir($directorio_destino)) {
            mkdir($directorio_destino, 0755, true);
        }
        
        // Intentar mover el archivo
        if(move_uploaded_file($archivo_tmp, $directorio_destino . $nombre_nuevo)) {
            $respuesta['estado'] = true;
            $respuesta['mensaje'] = "Archivo subido correctamente";
            $respuesta['nombre_archivo'] = $nombre_archivo;
            $respuesta['ruta_completa'] = $directorio_destino . $nombre_nuevo;
        } else {
            $respuesta['mensaje'] = "Error al mover el archivo";
        }
    } else {
        $respuesta['mensaje'] = "No se ha seleccionado ningún archivo o hubo un error en la subida";
    }
    
    return $respuesta;
}
?>