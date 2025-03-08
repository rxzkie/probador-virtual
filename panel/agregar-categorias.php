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
?>
<?php

require_once('../Connections/con1.php'); 

$usuario = $_SESSION['MM_Username'];
$panel = "categorias";

        $query_orden = "SELECT MAX(orden) as max_orden FROM $tabla_categorias ";
        $result_orden = mysqli_query($con1, $query_orden) or die(mysqli_error($con1));
        $row_orden = mysqli_fetch_assoc($result_orden);

        // Si no hay registros previos, comenzar desde 1, de lo contrario incrementar en 1
        $ultimo_orden = ($row_orden['max_orden'] !== NULL) ? $row_orden['max_orden'] : 0;
        $nuevo_orden = $ultimo_orden + 1;

$loginFormAction = $_SERVER['PHP_SELF'];
if (isset($_GET['accesscheck'])) {
  $_SESSION['PrevUrl'] = $_GET['accesscheck'];
}


//guardo los datos del formulario en la base camisa
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "formRegistro")) {

  $error=0;
  $mensaje="";

  
  // Chequear campos obligatorios con php
      $required_fields = array($_POST['categoria'],$_POST['contenedor'],$_POST['titulo'],$_POST['orden']);
      foreach ($required_fields as $required_field) {    
          if (!isset($required_field) || $required_field == '') { 
        $error=1;   
              echo("<script>
  alert('Por favor complete todos los campos obligatorios');
  history.back();</script>");
        //header("Location: formulario8.php");
          }
      }

  // Procesar archivos si no hay errores en el formulario
    if ($error == 0) {
        mysqli_select_db($con1, $database_con1);

        //Insertar en la base de datos
        $tabla = "$tabla_categorias";

        $insertSQL = sprintf("INSERT INTO $tabla (categoria, titulo, contenedor, orden, habilitado) VALUES (%s, %s, %s, %s, %s)",
            GetSQLValueString($_POST['categoria'], "text"),
            GetSQLValueString($_POST['titulo'], "text"),
            GetSQLValueString($_POST['contenedor'], "text"),
            GetSQLValueString($_POST['orden'], "text"),
            GetSQLValueString($_POST['habilitado'], "text")
        );
  
        mysqli_select_db($con1, $database_con1);

        $Result1 = mysqli_query($con1, $insertSQL) or die(mysqli_error($con1));
        
        if(mysqli_affected_rows($con1) > 0) {
            $mensaje = "ok";
        } else {
            $mensaje = "error";
        }
        
        header("Location: listado-".$panel.".php?mje=".$mensaje);

    }     
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="shortcut icon" type="image/png" href="../img/favicon.webp" />
    <title>Agregar <?= $panel ?></title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins');</style>
    <link rel="stylesheet" type="text/css" href="../css/panel.css" media="screen" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    

</head>
<body>
<main>
    <section class="confondo d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row d-flex align-items-start justify-content-center">
                <div class="col-12 col-lg-10 col-xl-10 ">
                    <div class="contenido rounded shadow-sm">
                        <form action="<?php echo $loginFormAction; ?>" method="POST" name="formRegistro" id="regForm" class="" onsubmit="esconder_submit();" enctype="multipart/form-data">
                        <!-- <h5 class="mb-3 mt-0 text-center"></h5>     -->
                        <h3 class="mb-5 mt-1 text-center capitalize">Agregar  <?= $panel ?>:</h3>
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label for="categoria">Nombre de la Carpeta <span class="red">*</span></label>
                                <input type="text" class="form-control" id="categoria" name="categoria" placeholder="Ej: polera" required value="" onBlur="this.value=minusc(this.value);controlarCategoria(this.value);" title="Se permiten sólo letras y números, sin espacios">
                                <!-- <p>Nombre de la carpeta donde se alojarán las imágenes de esta categoría.</p> -->
                            </div>
                            <div class="form-group col-12 col-md-6">
                                <label for="orden">Orden <span class="red">*</span></label>
                                <input type="text" class="form-control" id="orden" name="orden" placeholder="" required value="<?= $nuevo_orden; ?>" title="En qué orden se mostrará esta categoría">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label for="titulo">Nombre de la Categoría <span class="red">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Ej: Poleras cuello redondo" required value="" title="Cómo se verá el nombre en el Probador Virtual">
                            </div>
                            <div class="form-group col-12 col-md-6">
                                <label for="contenedor">Contenedor de las prendas <span class="red">*</span></label>
                                <select class="custom-select" name="contenedor" id="contenedor" required title="Dónde deben mostrarse las prendas de esta categoría">
                                    <option value="" selected>Seleccione...</option>
                                    <option value="saco" >Saco</option>
                                    <option value="camisa" >Camisa</option>
                                    <option value="pantalon" >Pantalón</option>
                                    <option value="zapato" >Zapatos</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label for="habilitado">Categoría habilitada <span class="red">*</span></label>
                                <select class="custom-select" name="habilitado" id="habilitado" required title="Quiere que se muestre esta categoría">
                                    <option value="SI" selected>SI</option>
                                    <option value="NO" >NO</option>
                                </select>
                            </div>
                        </div>
                        <div class="invisibles">
                          <input name="panel" type="text" id="panel"  value="<?= htmlspecialchars($panel); ?>" >
                        </div>
                        <div class="form-row ">
                            <div class="form-group col-12 col-md-6">
                                <input class="btn btn-info btn-lg btn-block mt-4 bold" type="text" name="volver" id="volver" value="Volver" onClick="volver_atras();">
                            </div>
                            <div class="form-group col-12 col-md-6">
                                <input class="btn btn-success btn-lg btn-block mt-4 bold" type="submit" name="enviar" id="submit" value="Guardar" onClick="return document.MM_returnValue;">
                                <h5 id="texto_submit" class="invisibles">Procesando...</h5>
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

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script language="JavaScript" type="text/JavaScript" src="../js/panel.js"> </script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function volver_atras() {
    panel = document.getElementById('panel').value;
          location.href="listado-"+panel+".php";
        }
</script>

</body>
</html>
