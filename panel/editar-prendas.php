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
$panel = "prendas";

// Revisar tanto GET como POST para el ID
$id = null;
if (isset($_GET['id'])) {
  $id = $_GET['id'];
} elseif (isset($_POST['prenda_id'])) {
  $id = $_POST['prenda_id'];
}

if ($id !== null) {
  // Obtener nombre de la tabla usando variable variable
  $variable_name = "tabla_".$panel;
  
  // Verificar si la variable existe
  if (!isset($$variable_name)) {
    echo("<script>alert('Error: variable $variable_name no definida'); history.back();</script>");
    exit;
  }

  $tabla = $$variable_name; 
  
$sql = sprintf("SELECT * FROM $tabla WHERE id= %s ",
        GetSQLValueString($id, "int")
        );
        $result = mysqli_query($con1,$sql) or die (mysqli_error($con1)); 

 if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
  } else {
    echo("<script>alert('No se encontró el registro con ID: $id'); history.back();</script>");
    exit;
  }
} else {
  echo("<script>alert('No se ha especificado un ID'); history.back();</script>");
  exit;
}


$loginFormAction = $_SERVER['PHP_SELF'];
if (isset($_GET['accesscheck'])) {
  $_SESSION['PrevUrl'] = $_GET['accesscheck'];
}


//guardo los datos del formulario en la base
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "formRegistro")) {

  $error=0;
  $mensaje="";

  
  // Chequear campos obligatorios con php
      $required_fields = array($_POST['nombre'],$_POST['categoria']);
      foreach ($required_fields as $required_field) {    
          if (!isset($required_field) || $required_field == '') { 
            $error=1;   
              echo("<script>
            alert('Por favor complete todos los campos obligatorios');
            history.back();</script>");
            exit;
          }
      }

  // Procesar archivos si no hay errores en el formulario
    if ($error == 0) {

        $nombre_personalizado = $_POST['nombre_imagen'];
        $nueva_imagen = $_POST['nombre_imagen'];
        $nueva_miniatura = "tn-".$_POST['nombre_imagen'];
        $nombre_categoria = $_POST['nombre_categoria'];
        $ruta_personalizada = '../productos/' . $nombre_categoria;

        // Verificar si se han subido nuevos archivos
        $hay_imagen_nueva = isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0 && $_FILES['imagen']['size'] > 0;
        $hay_miniatura_nueva = isset($_FILES['miniatura']) && $_FILES['miniatura']['error'] == 0 && $_FILES['miniatura']['size'] > 0;
        
        // Solo procesar archivos si se han subido nuevos
        if ($hay_imagen_nueva) {
            // 4. Procesar imagen principal
            $resultado_imagen = procesarArchivo('imagen', $nombre_personalizado, false, $ruta_personalizada);
            if(!$resultado_imagen['estado']) {
                echo("<script>
                    alert('" . $resultado_imagen['mensaje'] . "');
                    history.back();
                </script>");
                exit;
            }
            $nueva_imagen = $resultado_imagen['nombre_archivo'];
            
        }
        if ($hay_miniatura_nueva) {
            // 5. Procesar miniatura
            $resultado_miniatura = procesarArchivo('miniatura', $nombre_personalizado, true, $ruta_personalizada);
            if(!$resultado_miniatura['estado']) {
                echo("<script>
                    alert('" . $resultado_miniatura['mensaje'] . "');
                    history.back();
                </script>");
                exit;
            }
            $nueva_miniatura = $resultado_miniatura['nombre_archivo'];
            
        }
        

        // Actualizar en la base de datos
        $variable_name2 = "tabla_".$panel;
        $tabla = $$variable_name2; 

        $insertSQL = sprintf("UPDATE $tabla SET nombre= %s, descripcion= %s, imagen= %s, miniatura= %s WHERE id = %s",
            GetSQLValueString($_POST['nombre'], "text"),
            GetSQLValueString($_POST['descripcion'], "text"),
            GetSQLValueString($nueva_imagen, "text"),
            GetSQLValueString($nueva_miniatura, "text"),
            GetSQLValueString($_POST['prenda_id'], "int")
        );
  
        mysqli_select_db($con1, $database_con1);

        $Result1 = mysqli_query($con1, $insertSQL) or die(mysqli_error($con1));
        
        if(mysqli_affected_rows($con1) >= 0) {
            $mensaje = "ok";
        } else {
            $mensaje = "error";
        }
        
        header("Location: listado-".$panel.".php?mje=".$mensaje);
        exit;

    }     
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="shortcut icon" type="image/png" href="../img/favicon.webp" />
    <title>Editar <?= $panel ?></title>
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
                        <h3 class="mb-5 mt-1 text-center capitalize">Editar  <?= $panel ?>:</h3>
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label for="categoria">Categorías (no se puede modificar)</label>
                                
                                    <?php
                                    $sql = "SELECT * FROM $tabla_categorias ";
                                    $resultado1= mysqli_query($con1,$sql) or die (mysqli_error($con1)); 
                                    while ($consulta = mysqli_fetch_array($resultado1)) {
                                        if($consulta['id']==$row['id_categoria']) {
                                        echo '
                                        <select class="custom-select" name="categoria" id="categoria" required >
                                        <option selected value="' . $consulta['id'] . '">' . $consulta['categoria'] . '</option>
                                        </select>
                                        <input type="hidden" class="form-control" id="nombre_categoria" name="nombre_categoria" value="'.$consulta['categoria'].'">
                                        ';
                                      } 
                                    }
                                    ?>
                                
                               
                            </div>
                            <div class="form-group col-12 col-md-6">
                                <label for="identificador">Identificador (no se puede modificar)</label>
                                <input type="text" class="form-control" id="identificador" name="identificador" placeholder=""  value="<?= htmlspecialchars($row['identificador']); ?>" required readonly>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="nombre">Título <span class="red">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="" required value="<?= htmlspecialchars($row['nombre']); ?>">
                            </div>
                        </div> 
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label for="descripcion">Descripción </label>
                                <input type="text" class="form-control" id="descripcion" name="descripcion" placeholder=""  value="<?= htmlspecialchars($row['descripcion']); ?>">
                            </div>
                        </div> 
                        <div class="form-row">
                            <div class="form-group col-12 col-md-6">
                                <label class="mb-1">Adjuntar Imagen Calada</label>
                                <div class="form-group">
                                    <input type="file" class="form-control-file" id="imagen" name="imagen" onchange="revisar_archivo('imagen');">
                                </div>
                            </div>
                            <div class="form-group col-12 col-md-6">
                                <label class="mb-1">Adjuntar Miniatura</label>
                                <div class="form-group">
                                    <input type="file" class="form-control-file" id="miniatura" name="miniatura" onchange="revisar_archivo('miniatura');">
                                </div>
                            </div>
                        </div>
                        <div class="invisibles">
                          <input name="panel" type="text" id="panel"  value="<?= htmlspecialchars($panel); ?>" >
                          <input name="prenda_id" type="text" id="prenda_id"  value="<?= htmlspecialchars($id); ?>" >
                          <input name="nombre_imagen" type="text" id="nombre_imagen"  value="<?= htmlspecialchars($row['imagen']); ?>" >
                          <input name="control_upload" type="text" id="control_upload"  value="" >
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
