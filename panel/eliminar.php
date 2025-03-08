<?php 

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (!isset($_SESSION)) {
  session_start();
}



require_once('../Connections/con1.php'); 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {

    

 if(isset($_POST['tabla'])&&($_POST['tabla']!="")) {
    $variable_name = "tabla_".$_POST['tabla'];
    $tabla = $$variable_name;  // Esto accede al valor de $tabla_prendas
    // $tabla1 = "$tabla_"+$_POST['tabla'];
}

// Preparar la consulta SQL
    $ids = implode(',', array_map('intval', $_POST['ids']));
    $sql = "DELETE FROM $tabla WHERE id IN ($ids)";

   if ($con1->query($sql) === TRUE) {
        echo "1";
    } else {
        echo "X";
    }


   } else {
    echo "error";
}

?>