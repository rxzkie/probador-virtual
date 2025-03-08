<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION)) {
  session_start();
}
$MM_authorizedUsers = "";
$MM_donotCheckaccess = "true";

// *** Restrict Access To Page: Grant or deny access to this page
function isAuthorized($strUsers, $strGroups, $UserName, $UserGroup) { 
  // For security, start by assuming the visitor is NOT authorized. 
  $isValid = False; 

  // When a visitor has logged into this site, the Session variable MM_Username set equal to their username. 
  // Therefore, we know that a user is NOT logged in if that Session variable is blank. 
  if (!empty($UserName)) { 
    // Besides being logged in, you may restrict access to only certain users based on an ID established when they login. 
    // Parse the strings into arrays. 
    $arrUsers = Explode(",", $strUsers); 
    $arrGroups = Explode(",", $strGroups); 
    if (in_array($UserName, $arrUsers)) { 
      $isValid = true; 
    } 
    // Or, you may restrict access to only certain users based on their username. 
    if (in_array($UserGroup, $arrGroups)) { 
      $isValid = true; 
    } 
    if (($strUsers == "") && true) { 
      $isValid = true; 
    } 
  } 
  return $isValid; 
}

$MM_restrictGoTo = "index.php";
if (!((isset($_SESSION['MM_Username'])) && (isAuthorized("",$MM_authorizedUsers, $_SESSION['MM_Username'], $_SESSION['MM_UserGroup'])))) {   
  $MM_qsChar = "?";
  $MM_referrer = $_SERVER['PHP_SELF'];
  if (strpos($MM_restrictGoTo, "?")) $MM_qsChar = "&";
  if (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0) 
  $MM_referrer .= "?" . $_SERVER['QUERY_STRING'];
  $MM_restrictGoTo = $MM_restrictGoTo. $MM_qsChar . "accesscheck=" . urlencode($MM_referrer);
  header("Location: ". $MM_restrictGoTo); 
  exit;
}

require_once('../Connections/con1.php'); 

$usuario = $_SESSION['MM_Username'];


if (isset($_GET['id'])) {
  $id=$_GET['id'];
  


        $sql = sprintf("SELECT * FROM $tabla WHERE id= %s ",
        GetSQLValueString($id, "int")
        );
        $result = mysqli_query($con1,$sql) or die (mysqli_error($con1)); 

        if (mysqli_num_rows($result) > 0) {
          $row = mysqli_fetch_assoc($result);

          // $codigo = $row['codigo'];

}
} 
// else {
// echo("<script>
// alert('No se ha encontrado');
// history.back();</script>");
// }



$loginFormAction = $_SERVER['PHP_SELF'];
if (isset($_GET['accesscheck'])) {
  $_SESSION['PrevUrl'] = $_GET['accesscheck'];
}


//guardo los datos del formulario en la base
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "formRegistro")) {

  $error=0;
  $mensaje="";

  
  // Chequear campos obligatorios con php
      $required_fields = array($_POST['nombre'],$_POST['apellido']);
      foreach ($required_fields as $required_field) {    
          if (!isset($required_field) || $required_field == '') { 
        $error=1;   
              echo("<script>
  alert('Por favor complete todos los campos obligatorios');
  history.back();</script>");
        //header("Location: formulario8.php");
          }
      }



  // if(isset($_POST['acoape1'])) {$acoape1=$_POST['acoape1'];} else $acoape1="";

  mysqli_select_db($con1, $database_con1);
  
  
  $update=sprintf("UPDATE $tabla SET nombres = %s, apellido = %s, ciudad = %s, provincia = %s, pais = %s, telefono = %s, email = %s, cargo = %s, institucion = %s WHERE id = %s",
             GetSQLValueString($_POST['nombre'], "text"),
             GetSQLValueString($_POST['apellido'], "text"),
             GetSQLValueString($_POST['ciudad'], "text"),
					   GetSQLValueString($_POST['provincia'], "text"),
             GetSQLValueString($_POST['pais'], "text"),
             GetSQLValueString($_POST['telefono'], "text"),
					   GetSQLValueString($_POST['email'], "text"),
					   GetSQLValueString($_POST['cargo'], "text"),
					   GetSQLValueString($_POST['institucion'], "text"),
             GetSQLValueString($_POST['codigo'], "int")
  
						);
   $Result2 = mysqli_query($con1, $update) or die(mysqli_error($con1));

   if(mysqli_affected_rows($con1) >0) {$mensaje="ok";} else {$mensaje="error";}


      header ("Location: listado-inscripciones.php?mje=".$mensaje);

}   
$title = "Editar Datos";  
?>
<!DOCTYPE html>
<html lang="es">
<?php include("etiquetaHead.php"); ?>
<body>
<main>
    <section class="confondo d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row d-flex align-items-start justify-content-center">
                <div class="col-12 col-lg-10 col-xl-10 ">
                    <div class="contenido rounded shadow-sm">
                        <form action="<?php echo $loginFormAction; ?>" method="POST" name="formRegistro" id="regForm" class="" onsubmit="document.getElementById('submit').disabled=true;">
                        <!-- <h5 class="mb-3 mt-0 text-center"></h5>     -->
                        <h3 class="mb-5 mt-1 text-center">Editar Datos:</h3>
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label for="nombre">Nombre <span class="red">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="" required value="<?php echo $row['nombres']; ?>">
                            </div>
                             <div class="form-group col-12 col-md-6">
                                <label for="apellido">Apellido <span class="red">*</span></label>
                                <input type="text" class="form-control" id="apellido" name="apellido" placeholder="" required value="<?php echo $row['apellido']; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="email">Email <span class="red">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="" required value="<?php echo $row['email']; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label for="ciudad">Ciudad <span class="red">*</span></label>
                                <input type="text" class="form-control" id="ciudad" name="ciudad" placeholder="" required value="<?php echo $row['ciudad']; ?>">
                            </div>
                             <div class="form-group col-12 col-md-6">
                                <label for="provincia">Provincia <span class="red">*</span></label>
                                <input type="text" class="form-control" id="provincia" name="provincia" placeholder="" required value="<?php echo $row['provincia']; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label for="pais">País <span class="red">*</span></label>
                                <input type="text" class="form-control" id="pais" name="pais" placeholder="" required value="<?php echo $row['pais']; ?>">
                            </div>
                             <div class="form-group col-12 col-md-6">
                                <label for="telefono">Celular <span class="red">*</span></label>
                                <input type="text" class="form-control" id="telefono" name="telefono" placeholder="" required value="<?php echo $row['telefono']; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12 col-lg-6">
                                <label for="institucion">Institución <span class="red">*</span></label>
                                <input type="text" class="form-control" id="institucion" name="institucion" placeholder="" value="<?php echo $row['institucion']; ?>">
                            </div>
                            <div class="form-group col-12 col-md-6">
                                <label for="cargo">Cargo/Responsabilidad/Area de trabajo <span class="red">*</span></label>
                            <input type="text" class="form-control" id="cargo" name="cargo" placeholder="" value="<?php echo $row['cargo']; ?>">
                            </div>
                        </div>
                        

                        
                        <div class="invisibles">
                            <input type="text" name="codigo" id="codigo"  value="<?php echo $id; ?>" >
                        </div>
                        <div class="form-row ">
                            <div class="form-group col-12 col-md-6">
                                <input class="btn btn-info btn-lg btn-block mt-4 bold" type="text" name="volver" id="volver" value="Volver" onClick="volverAtrasSimple();">
                            </div>
                            <div class="form-group col-12 col-md-6">
                                <input class="btn btn-success btn-lg btn-block mt-4 bold" type="submit" name="enviar" id="submit" value="Guardar" onClick="return document.MM_returnValue;">
                            </div>
                        </div>
                        <input type="hidden" name="MM_insert" value="formRegistro">
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include("scriptJS.php"); ?>
</body>
</html>
