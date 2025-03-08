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


 if (isset($_GET['mje'])) {
$mensaje=$_GET['mje'];
} else $mensaje="";

require_once("../Connections/con1.php");

$panel = "categorias";
$title = "Listado Categorias";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es">
<?php include("etiquetaHead.php"); ?>
<body>
  <?php include("header.php"); ?>
  <section id="listado" class="">
    <div class="content-fluid">
        <div class="row d-flex align-items-start justify-content-center">
            <div class="col-12">
              <div id="tabla-filtro"></div>
            </div>
          </div>
          <div class="invisibles">
              <input name="mensaje" type="text" id="mensaje"  value="<?php echo $mensaje; ?>" >
              <input name="ultima_busqueda" type="text" id="ultima_busqueda"  value="" >
              <input name="panel" type="text" id="panel"  value="<?php echo $panel; ?>" >
          </div>
      </div>
      <!-- Boton hacia arriba -->
      <a class="ir-arriba"  javascript:void(0) title="Volver arriba">
        <span class="">
          <i class="bi bi-arrow-up-circle-fill"></i>
        </span>
      </a>
  </section>
<?php include("scriptJS.php"); ?>
<script>

        
    document.addEventListener('DOMContentLoaded', function() {

      
        cargarFiltro(); 
        mostrar_mensaje()
        const observer = new MutationObserver((mutations) => {
            const rowId = sessionStorage.getItem('filaAEnfocar');
            if (rowId) {
              // alert(rowId);
                const row = document.getElementById(rowId);
                if (row) {
                    row.scrollIntoView({behavior: 'auto', block: 'center'});
                    sessionStorage.removeItem('filaAEnfocar');
                    observer.disconnect();
                }
            }
        });

    observer.observe(document.body, {childList: true, subtree: true});

    });
</script>
</body>
</html>