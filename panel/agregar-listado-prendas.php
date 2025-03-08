<?php
// Incluir el archivo de conexión a la base de datos
require_once('../Connections/con1.php');

$panel = "prendas";

$loginFormAction = $_SERVER['PHP_SELF'];
if (isset($_GET['accesscheck'])) {
  $_SESSION['PrevUrl'] = $_GET['accesscheck'];
}
$mensaje = "";

// Solo procesar el archivo cuando el formulario ha sido enviado
if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'formRegistro') {
    // Verificar que se haya subido un archivo
    if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] === UPLOAD_ERR_OK) {
        
        // Comprobamos que el archivo sea un Excel
        $archivo_tmp = $_FILES['archivo_excel']['tmp_name'];
        $archivo_nombre = $_FILES['archivo_excel']['name'];
        $extension = pathinfo($archivo_nombre, PATHINFO_EXTENSION);
        
        if ($extension !== 'xlsx' && $extension !== 'xls') {
            $mensaje = "Error: El archivo debe ser de formato Excel (.xlsx o .xls)";
        } else {
            // Necesitamos la librería PHPExcel para leer archivos Excel
            require_once '../vendor/autoload.php'; // Ajusta la ruta según tu configuración
            
            if ($extension === 'xlsx') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            } else {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            }
            
            // Cargar el archivo Excel
            $spreadsheet = $reader->load($archivo_tmp);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            // Obtener los encabezados (primera fila)
            $encabezados = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $encabezados[] = trim($worksheet->getCellByColumnAndRow($col, 1)->getValue());
            }
            
            // Verificar que los encabezados necesarios estén presentes
            $campos_requeridos = ['identificador', 'nombre', 'descripcion', 'categoria'];
            $campos_encontrados = array_intersect($campos_requeridos, $encabezados);
            
            if (count($campos_encontrados) !== count($campos_requeridos)) {
                $campos_faltantes = array_diff($campos_requeridos, $encabezados);
                $mensaje = "Error: Faltan los siguientes campos en el Excel: " . implode(", ", $campos_faltantes);
            } else {
                // Mapear posiciones de columnas según encabezados
                $mapa_columnas = [];
                foreach ($encabezados as $indice => $campo) {
                    $mapa_columnas[$campo] = $indice + 1; // +1 porque en PHPSpreadsheet las columnas empiezan en 1
                }
                
                // Cargar todas las categorías de la base de datos en un array
                $categorias = [];
                $sql_categorias = "SELECT id, categoria FROM categorias";
                $resultado_categorias = mysqli_query($con1, $sql_categorias);
                
                if (!$resultado_categorias) {
                    $mensaje = "Error al consultar categorías: " . mysqli_error($con1);
                } else {
                    while ($fila = mysqli_fetch_assoc($resultado_categorias)) {
                        $categorias[strtolower(trim($fila['categoria']))] = $fila['id'];
                    }
                    
                    // Iniciar la transacción
                    mysqli_autocommit($con1, FALSE);
                    $errores = 0;
                    $filas_insertadas = 0;
                    $categorias_no_encontradas = [];
                    
                    // Procesar los datos (empezando desde la fila 2)
                    for ($row = 2; $row <= $highestRow; $row++) {
                        // Extraer valores básicos
                        $identificador = trim($worksheet->getCellByColumnAndRow($mapa_columnas['identificador'], $row)->getValue());
                        $nombre = trim($worksheet->getCellByColumnAndRow($mapa_columnas['nombre'], $row)->getValue());
                        $descripcion = trim($worksheet->getCellByColumnAndRow($mapa_columnas['descripcion'], $row)->getValue());
                        
                        // Obtener el nombre de la categoría del Excel
                        $categoria_nombre = trim($worksheet->getCellByColumnAndRow($mapa_columnas['categoria'], $row)->getValue());
                        
                        // Buscar el id_categoria correspondiente
                        $categoria_clave = strtolower($categoria_nombre);
                        if (!isset($categorias[$categoria_clave])) {
                            $errores++;
                            $categorias_no_encontradas[$categoria_nombre] = true;
                            $mensaje .= "Error en la fila $row: La categoría '$categoria_nombre' no existe en la base de datos.<br>";
                            continue; // Pasamos a la siguiente fila
                        }
                        
                        $id_categoria = $categorias[$categoria_clave];
                        
                        // Generar los valores de imagen y miniatura
                        $imagen = $categoria_nombre . $identificador;
                        $miniatura = "tn-" . $categoria_nombre . $identificador;
                        
                        // Escapar valores para evitar inyección SQL
                        $identificador = mysqli_real_escape_string($con1, $identificador);
                        $id_categoria = mysqli_real_escape_string($con1, $id_categoria);
                        $nombre = mysqli_real_escape_string($con1, $nombre);
                        $descripcion = mysqli_real_escape_string($con1, $descripcion);
                        $imagen = mysqli_real_escape_string($con1, $imagen);
                        $miniatura = mysqli_real_escape_string($con1, $miniatura);
                        
                        // Construir consulta SQL
                        $sql = "INSERT INTO $tabla_prendas (identificador, id_categoria, nombre, descripcion, imagen, miniatura) 
                                VALUES ('$identificador', '$id_categoria', '$nombre', '$descripcion', '$imagen', '$miniatura')";
                        
                        // Ejecutar consulta
                        if (!mysqli_query($con1, $sql)) {
                            $errores++;
                            $mensaje .= "Error en la fila $row: " . mysqli_error($con1) . "<br>";
                        } else {
                            $filas_insertadas++;
                        }
                    }
                    
                    // Finalizar la transacción
                    if ($errores > 0) {
                        mysqli_rollback($con1);
                        $mensaje .= "<br>Se encontraron $errores errores. No se realizó ninguna inserción.";
                        
                        if (!empty($categorias_no_encontradas)) {
                            $mensaje .= "<br><br>Las siguientes categorías no fueron encontradas en la base de datos:<br>";
                            $mensaje .= implode("<br>", array_keys($categorias_no_encontradas));
                            $mensaje .= "<br><br>Categorías disponibles en la base de datos:<br>";
                            
                            // Mostrar las categorías disponibles (ordenadas alfabéticamente)
                            $categorias_disponibles = [];
                            foreach ($categorias as $cat_nombre => $cat_id) {
                                $categorias_disponibles[] = ucfirst($cat_nombre);
                            }
                            sort($categorias_disponibles);
                            $mensaje .= implode("<br>", $categorias_disponibles);
                        }
                    } else {
                        mysqli_commit($con1);
                        $mensaje = "Importación completada con éxito. Se insertaron $filas_insertadas filas.";
                    }
                    
                    // Restaurar el modo autocommit
                    mysqli_autocommit($con1, TRUE);
                }
            }
        }
    } else {
        $mensaje = "Error: No se ha subido ningún archivo o ha ocurrido un error durante la subida.";
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="es" xml:lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="shortcut icon" type="image/png" href="../img/favicon.webp" />
    <title>Agregar Listado <?= $panel ?></title>
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
                        <h3 class="mb-5 mt-1 text-center capitalize">Importar Listado de <?= $panel ?>:</h3>
                        <div class="form-row">
                            <div class="form-group col-12">
                                <label class="mb-1">Importar archivo xlsx o xls</label>
                                <div class="form-group">
                                    <input type="file" class="form-control-file" id="archivo_excel" name="archivo_excel" required onchange="revisar_archivo('imagen');">
                                </div>
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
                                <input class="btn btn-success btn-lg btn-block mt-4 bold" type="submit" name="enviar" id="submit" value="Importar" onClick="return document.MM_returnValue;">
                                <h5 id="texto_submit" class="invisibles">Procesando...</h5>
                            </div>
                        </div>
                        <?php if (!empty($mensaje)): ?>
                        <div class="form-row">
                            <div class="form-group col-12">
                                <div class="alert alert-info">
                                    <?= $mensaje; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
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