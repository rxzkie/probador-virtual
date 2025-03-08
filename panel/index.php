<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../Connections/con1.php'); 


// *** Validate request to login to this site.
if (!isset($_SESSION)) {
  session_start();
}

if (isset($_GET['mje'])) {
$mensaje=$_GET['mje'];
} else $mensaje="";

$loginFormAction = $_SERVER['PHP_SELF'];
if (isset($_GET['accesscheck'])) {
  $_SESSION['PrevUrl'] = $_GET['accesscheck'];
}

if (isset($_POST['user'])) {
  $loginUsername=$_POST['user'];
  $password=$_POST['pass'].$_POST['user'];
  // $password=$_POST['pass'];
  $password=md5($password);
  $MM_fldUserAuthorization = "level";
  $MM_redirectLoginSuccess = "listado-prendas.php";
  $MM_redirectLoginFailed = "index.php?mje=loginerror";
  $MM_redirectLoginFailedNA = "index.php?mje=nohabilitado";
  $MM_redirecttoReferrer = false;
  
  //echo $loginUsername.": ".md5($loginUsername)."<br>";  
  //echo $password.": ".md5($password)."<br>";  
  
  mysqli_select_db($con1, $database_con1);
	//chequeo si el usuario existe y su tipo
	$consulta_sql=sprintf("SELECT * FROM $tabla_usuarios WHERE usuario=%s and pass=%s",
  GetSQLValueString($loginUsername, "text"), GetSQLValueString($password, "text"));
	$consulta= mysqli_query($con1, $consulta_sql) or die(mysqli_error($con1));
	
	$encontrados = mysqli_num_rows($consulta);
	$registro = mysqli_fetch_assoc($consulta);
  
  
  if ($encontrados) {
 
    $activo = $registro["activo"];

     if ($activo == "SI") { // solo activo la sesion si el usuario esta habilitado

          $_SESSION['MM_Username'] = $loginUsername;
          $_SESSION['MM_UserGroup'] = $registro["tipo"]; 	  	      
          $_SESSION['activo'] = $activo;
          // $_SESSION['nivel'] = $nivel;

        
        
        //$loginStrGroup  = mysql_result($LoginRS,0,'level');
        
        //declare two session variables and assign them

        if (isset($_SESSION['PrevUrl']) && false) {
            $MM_redirectLoginSuccess = $_SESSION['PrevUrl'];exit;
          }
          if($registro["tipo"]=="admin") {

            header("Location: " . $MM_redirectLoginSuccess );exit;

           } else if($registro["tipo"]=="op") {
              header("Location: " . $MM_redirectLoginSuccess );exit;

           } else 
              header("Location: ". $MM_redirectLoginFailedNA );exit;
        }
        else {
          header("Location: ". $MM_redirectLoginFailedNA );exit;
        }
    
  }
  else {
    header("Location: ". $MM_redirectLoginFailed );exit;
  }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="shortcut icon" type="image/png" href="../img/favicon.webp" />
<title>Ingresar | Probador Virtual</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<style>@import url('https://fonts.googleapis.com/css2?family=Poppins');</style>
<link rel="stylesheet" type="text/css" href="../css/probador-virtual.css" media="screen" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- <script src="../js/probador-virtual.js"></script> -->
<meta name="robots" content="noindex">
</head>

<body>
<main>

    <section class="confondo d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row d-flex align-items-center justify-content-center">
                <div class="col-12 col-lg-8 col-xl-8">
                      <form id="form1" name="form1" method="POST" action="<?php echo $loginFormAction; ?>" class="mt-3 p-5 contenido rounded shadow-sm row justify-content-center">
                        <div class="col-10">
                          <h4 class="text-center mt-3 mb-3">Por favor ingrese al sistema</h4>
                          <div class="form-group">
                            <label for="user">Usuario</label>
                            <input type="text" class="form-control" name="user" id="user">
                          </div>
                          <div class="form-group">
                            <label for="pass">Contraseña</label>
                            <div class="input-group">
                              <input type="password" class="form-control" name="pass" id="pass">
                              <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                  <i class="fa fa-eye" id="eyeIcon"></i>
                                </button>
                              </div>
                            </div>
                          </div>
                          <div class="form-group d-flex justify-content-end">
                            <button type="submit" class="btn btn-success text-right">Ingresar</button>
                          </div>
                        </div>
                      </form>
                      <div class="invisibles">
                        <input name="mensaje" type="text" id="mensaje"  value="<?php echo $mensaje; ?>" >
                      </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
   $(document).ready(function() {

        mostrar_mensaje();

    });
    
function mostrar_mensaje() {
  var mensaje = document.getElementById("mensaje").value;
  $tiempo = 2000;//tiempo en milisegundos que dura el mensaje antes de cerrarse
  if (mensaje != "") {
    if (mensaje == "ACCESS-ERROR") {
      // swal("", "Por favor complete los campos en rojo", "error");
      Swal.fire(
        "Usuario o Contraseña incorrectos",
        "Intente nuevamente",
        "error"
      );
    } else if (mensaje == "noactivo") {
      // swal("", "Por favor complete los campos en rojo", "error");
      Swal.fire(
        "Usuario No Activo",
        "Consulte con Administración",
        "error"
      );
    } 
    // document.getElementById("mensaje_mostrado").value = 1;
    setTimeout(function () {
      // wait for 5 secs(2)
      // location.reload(); // then reload the page.(3)
      // location.href = "listar-usuarios.php";
    }, $tiempo);
     
  }
}
// function cerrar_ventana(){
//   var customWindow = window.open('', '_blank', '');
//     customWindow.close();
//                 // window.close();
//             }
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('pass');
    const eyeIcon = document.getElementById('eyeIcon');
    
    togglePassword.addEventListener('click', function() {
      // Cambiar el tipo de input entre "password" y "text"
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      // Cambiar el icono entre ojo y ojo tachado
      if (type === 'password') {
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
      } else {
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
      }
    });
  });
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</body>
</html>