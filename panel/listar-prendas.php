<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once("../Connections/con1.php");
$indice = "id_categoria ASC, ";
$orden = "identificador ASC";


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
      $where="WHERE p.id >= $valor_buscado  ";} else {
        $where="WHERE p.id = '$busqueda' OR nombre LIKE '%$busqueda%' OR descripcion LIKE '%$busqueda%' OR imagen LIKE '%$busqueda%'  ";
      }}
       else $where="";
    
    

        $_SESSION['indice'] = $indice;
        $_SESSION['orden'] = $orden;

        $sql = "SELECT *, p.id as id
        FROM $tabla_prendas p
        LEFT JOIN  $tabla_categorias c ON p.id_categoria = c.id 
        $where
        ORDER BY $indice $orden
        ";
    
    $result = mysqli_query($con1,$sql) or die (mysqli_error($con1)); 
    $total_filas = mysqli_num_rows($result);

    if (mysqli_num_rows($result) > 0) {
         $users = array(
                            array('Categoría', 'categoria','mediano'),
                            array('Título', 'nombre','mediano'),
                            array('Descripción', 'descripcion','largo'),
                            
                            array('Imagen Calada', 'imagen','mediano'),
                            array('Miniatura', 'miniatura','mediano')
                        );
                        // <button id='btn-eliminar' class='btn btn-outline-danger btn-sm noborders' title='Eliminar Seleccionados' onclick='eliminar_seleccionados()'><i class='bi bi-trash'></i></button>
                        // <th id='fila-borrar' scope='col' class='corto text-center borradores'></th>
        echo "<table class='table table-striped tablaFija' id='dataTable'>
                <thead>
                     <tr class='corto bg_lightgray '>
                        <th id='fila-eliminar' class='corto selecciones invisibles'>
                            <div class='custom-control custom-checkbox'>
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

                       <td>".htmlspecialchars($row['categoria'
                       ])."</td> <td>".htmlspecialchars($row['nombre'  
                       ])."</td> <td>".htmlspecialchars($row['descripcion'
                       ])."</td> <td>".htmlspecialchars($row['imagen'
                       ])."</td> <td>".htmlspecialchars($row['miniatura'
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
