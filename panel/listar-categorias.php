<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("../Connections/con1.php");
$indice = "orden";
$orden = "ASC";


if (isset($_POST['indice'])) {
    $indice = $_POST['indice'];
    $orden = $_POST['orden'];
}
if (isset($_POST['busqueda'])) {
    $busqueda = $_POST['busqueda'];
} else $busqueda ="";

if (isset($_POST['panel'])) {
    $panel = $_POST['panel'];
} else $panel ="";
        
    if($orden!="ASC") $orden2="ASC"; else $orden2="DESC";

    if($busqueda!="") {
      if(substr($busqueda, 0, 1)===">") {
      $valor_buscado = substr($busqueda, 1);
      $where="WHERE id >= $valor_buscado  ";} else {
        $where="WHERE id = '$busqueda' OR doc LIKE '%$busqueda%' OR nombres LIKE '%$busqueda%' OR apellido LIKE '%$busqueda%'  ";
      }}
       else $where="";
    
    

        $_SESSION['indice'] = $indice;
        $_SESSION['orden'] = $orden;

        $sql = "SELECT *
        FROM $tabla_categorias
        $where
        ORDER BY $indice $orden
        ";
    
    $result = mysqli_query($con1,$sql) or die (mysqli_error($con1)); 
    $total_filas = mysqli_num_rows($result);

    if (mysqli_num_rows($result) > 0) {
         $users = array(
                            
                            array('Nombre', 'titulo','mediano'),
                            array('Categoría', 'categoria','mediano'),
                            array('Contenedor', 'contenedor','mediano'),
                            
                            array('Orden', 'orden','mediano'),
                            array('Habilitado', 'habilitado','mediano')
                        );
                        // <button id='btn-eliminar' class='btn btn-outline-danger btn-sm noborders' title='Eliminar Seleccionados' onclick='eliminar_seleccionados()'><i class='bi bi-trash'></i></button>
                        // <th id='fila-borrar' scope='col' class='corto text-center borradores'></th>
        echo "<table class='table table-striped tablaFija' id='dataTable'>
                <thead>
                     <tr class='corto bg_lightgray '>
                        <th id='fila-eliminar' class='corto selecciones invisibles'>
                            <div class='custom-control custom-checkbox text-center'>
                                <input type='checkbox' id='selectAll' name='selectAll' class='custom-control-input' onclick='selectAll(this.checked);'>
                                <label class='custom-control-label' for='selectAll' title='Seleccionar todo'>
                                </label>
                            </div>
                        </th>  
                        
                        <th class='corto text-center'></th>
                        
                        ";
                         foreach ($users as $user) {
                            list($key, $value, $class) = $user;
                            echo "<th scope='col' class='$class'><div class='blue pointer' onClick='cargarFiltro(``,`".$value."`,`".$orden2."`);'";
                            
                            if($indice==$value){
                            if($orden2=="ASC") {echo "title='Ordenar por $key ascendente'>$key<i class='bi bi-caret-down-fill'></i>";} else {echo "title='Ordenar por $key descendente'>$key<i class='bi bi-caret-up-fill'></i>";}
                            } else {echo "title='Ordenar por $key ascendente'>$key";}
                            echo "</div></th>";
                        }

                    echo "</tr>
                </thead>
                <tbody>";

        while ($row = mysqli_fetch_assoc($result)) {


                    $clase1 = "";
                    $clase2 = "";

                    //     echo("
                    //  <tr class='".$clase1." ".$clase2."'>");
                    // <td class='borradores'><button class='btn btn-outline-danger btn-sm' onclick='borrar(".$row['id'].")' title='Eliminar'><i class='bi-trash'></i></button></td>

                     echo("
                     
                     <tr id='fila".$row['id']."' class='".$clase1." ".$clase2." filaSeleccionada'>
                     <td class='text-center selecciones invisibles'>
                            <div class='custom-control custom-checkbox'>
                                 <input type='checkbox' id='sel".$row['id']."' name='sel".$row['id']."' class='custom-control-input seleccionados' value='".$row['id']."' onclick='resaltarFila(".$row['id'].",this.checked);'>
                                <label class='custom-control-label' for='sel".$row['id']."'>
                                </label>
                            </div>
                        </td>
                     <td><button class='btn btn-outline-info btn-sm' onclick='editar(".$row['id'].",`".$panel."`)' title='Editar Datos'><i class='bi bi-pencil-square'></i></button></td>
                     ");

                     echo("

                       <td>".htmlspecialchars($row['titulo'
                       ])."</td> <td>".htmlspecialchars($row['categoria'  
                       ])."</td> <td>".htmlspecialchars($row['contenedor'
                       ])."</td> <td>".htmlspecialchars($row['orden'
                       ])."</td> <td>".htmlspecialchars($row['habilitado'
                       ])."</td> 
                       </tr>
                       ");
           
        }
        echo "</tbody></table>";
    } else {
        echo "<h5 class='ml-5 mt-3'>No se encontraron registros para mostrar.</h5>";
    }
echo "
    <footer class='footer'>
        <div class='pl-5 d-flex align-items-center'>
            <small>$total_filas registros</small>
        </div>
    </footer>
    ";
