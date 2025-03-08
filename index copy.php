<?php
// Número de versión para evitar el caché - esto se actualiza con cada carga de página
$version = time(); // Usar timestamp asegura una nueva versión en cada carga
echo "Versión: $version";
// Mostrar errores de PHP
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Establece cabeceras adicionales para control de caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once('Connections/con1.php');

// --- Inicio código para obtener pantalón desde API WooCommerce ---
// URL de la API de WooCommerce
$api_url = "https://sartoriacielomilano.com/wp-json/wc/store/v1/products";
$response = file_get_contents($api_url);
$api_products = json_decode($response, true);

if ($api_products) {
    // Filtrar productos de la categoría "Pantalones"
    $api_pantalones = array_filter($api_products, function($product) {
        return in_array('Pantalones', array_column($product['categories'], 'name'));
    });
    // Seleccionar el primer pantalón encontrado
    $api_pantalon = !empty($api_pantalones) ? reset($api_pantalones) : null;
} else {
    $api_pantalon = null;
}
// --- Fin código API ---

// Inicialización de arrays
$categorias = [];
$contenedores = [];
$prendas = [];
$modelos = [];

mysqli_select_db($con1, $database_con1);

// Consulto por los modelos
$sql = "SELECT modelo FROM $tabla_modelos WHERE habilitado = 'SI' ORDER BY orden";
$result = $con1->query($sql);
if ($result === false) {
    echo "Error en la consulta: " . $con1->error;
} else {
    while($row = $result->fetch_assoc()) {
        $modelos[] = $row['modelo'];
    }
}

// Consulto por las categorías
$sql = "SELECT categoria,contenedor FROM $tabla_categorias WHERE habilitado = 'SI' ORDER BY orden";
$result = $con1->query($sql);
if ($result === false) {
    echo "Error en la consulta: " . $con1->error;
} else {
    while($row = $result->fetch_assoc()) {
        $categorias[] = $row['categoria'];
        $contenedores[$row['categoria']] = $row['contenedor'];
    }
}
    
// Consultas para obtener prendas por cada categoría
foreach ($categorias as $categoria) {
    $sql = "SELECT p.*, c.categoria as nombre_categoria, c.contenedor as contenedor_categoria, c.titulo as titulo_categoria 
            FROM $tabla_prendas p 
            INNER JOIN $tabla_categorias c ON p.id_categoria = c.id 
            WHERE c.categoria = '$categoria' 
            ORDER BY p.id";
    $result = $con1->query($sql);
    if ($result && $result->num_rows > 0) {
        $prendas[$categoria] = [];
        while ($row = $result->fetch_assoc()) {
            $prendas[$categoria][] = $row;
        }
    }
}

// --- Agregar pantalón adicional desde la API a la categoría "pantalon" ---
// Se inserta después de cargar las prendas y se usa 'pantalon' en minúsculas
if ($api_pantalon) {
    // Por defecto usamos la primera imagen
    $selectedImage = $api_pantalon['images'][0]['src'];   // esta imagen es la que se carga
    // Si existe alguna imagen cuyo "name" sea "imagencalada", la usamos en lugar de la primera.
    if (isset($api_pantalon['images']) && is_array($api_pantalon['images'])) {
         foreach ($api_pantalon['images'] as $img) {
             if (isset($img['name']) && strtolower($img['name']) === 'imagencalda') {
                 $selectedImage = $img['src'];
                 break;
             }
         }
    }
    $api_prenda = [
        'nombre'              => $api_pantalon['name'],
        'imagen'              => $selectedImage, // Usamos la imagen seleccionada
        'nombre_categoria'    => 'pantalon',
        'contenedor_categoria'=> 'pantalon',
        'titulo_categoria'    => 'Pantalones',
        'descripcion'         => '',
        'precio'              => '',
        'sku'                 => $api_pantalon['sku'],
        'id'                  => $api_pantalon['id'] // Guardamos también el ID del producto
    ];
    if (isset($prendas['pantalon'])) {
        array_unshift($prendas['pantalon'], $api_prenda);
    } elseif (isset($prendas['Pantalones'])) {
        array_unshift($prendas['Pantalones'], $api_prenda);
    } else {
        $prendas['pantalon'] = [$api_prenda];
    }
}
// --- Fin agregar pantalón desde API ---

$prenda_sidebar = [];
$categoriasEnUso = [];
foreach ($prendas as $categoria => $items) {
    if (!empty($items) && isset($items[0])) {
        $prenda_sidebar[$categoria] = $items[0];
        $categoriasEnUso[] = $categoria;
    }
}

// Obtener la primera prenda de cada categoría para el Mannequín
// Se modifica para que para la categoría 'pantalon' se salte el producto API (si existe otro) para que no aparezca por defecto.
$contenedores_inicio = ['saco', 'camisa', 'pantalon', 'zapato'];
$prenda_inicial = [];
foreach ($prendas as $categoria => $items) {
    if (!empty($items)) {
        if (($categoria === 'pantalon' || $categoria === 'Pantalones') && count($items) > 1) {
            // Si el primero es del API (imagen que contiene la URL de la API) se toma el segundo para el modelo
            if (strpos($items[0]['imagen'], 'http') === 0) {
                $prenda_inicial[$categoria] = $items[1];
            } else {
                $prenda_inicial[$categoria] = $items[0];
            }
        } else {
            if (in_array($items[0]['nombre_categoria'], $contenedores_inicio)) {
                $prenda_inicial[$categoria] = $items[0];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Probador Virtual</title>
    <link rel="shortcut icon" type="image/png" href="img/favicon.webp" />
    <meta name="robots" content="noindex">
    <!-- Meta tags para control de caché -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/probador-virtual.css?v=<?= $version ?>" media="screen" />
    <link property="stylesheet" rel="stylesheet" id="qwery-font-google_fonts-css" 
          href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700&amp;family=Cormorant+Garamond:wght@400;500;600;700&amp;subset=latin,latin-ext&amp;display=swap" 
          type="text/css" media="all">
</head>
<body> 
    <div class="full-height">
        <div>
            <div class="TaAFo">
                <div id="probador" class="hIrAZy">
                    <div id="loader" class="position-absolute top-50 start-50 translate-middle">
                        <div class="spinner-border"></div>
                    </div>
                    <div id="manequin" class="idGQcZ">
                        <div class="gKuJZd jWhCmT">
                            <!-- Área del Modelo -->
                            <div id="contenedor-modelos" class="inyuYv">
                                <!-- Muestro el primer elemento de la tabla modelos -->
                                <img src="modelos/<?= $modelos[0] ?>.avif?v=<?= $version ?>"> 
                                <img src="modelos/manos/manos-<?= $modelos[0] ?>.avif?v=<?= $version ?>" class="lakCAL">
                            </div>
                            <!-- Capas de prendas iniciales -->
                            <?php foreach ($prenda_inicial as $key => $row): ?>
                                <div id="div-<?= $row['nombre_categoria'] ?>" class="gKuJZd hfgSWw">
                                    <div id="contenedor-<?= $row['nombre_categoria'] ?>" class="inyuYv">
                                        <?php
                                          // Si la imagen viene de la API (contiene http) se usa directo, sino se arma la ruta local.
                                          $imgSrc = (strpos($row['imagen'], 'http') === 0) 
                                                  ? $row['imagen'] 
                                                  : "productos/{$row['nombre_categoria']}/{$row['imagen']}.avif?v={$version}";
                                        ?>
                                        <img src="<?= $imgSrc ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div id="sidebar" class="sidebar">
                    <div id="menu" class="iQNMuV">
                        <div class="fHoayM">
                            <div class="eRxhqm" style="opacity: 1">
                                <?php foreach ($prenda_sidebar as $categoria => $row): ?>
                                    <button class="jJlTfc" onclick="habilitar('<?= $row['nombre_categoria'] ?>','<?= $row['contenedor_categoria'] ?>');">
                                        <div class="dnzqbB">
                                            <div class="kwQXt" style="opacity: 1; transform: none">
                                                <figure class="nPPeX" style="transform: none">
                                                    <picture class="gHfhyG">
                                                    <?php 
                                                      $thumbSrc = (strpos($row['imagen'], 'http') === 0) 
                                                                  ? $row['imagen'] 
                                                                  : "productos/{$row['nombre_categoria']}/miniaturas/tn-{$row['imagen']}.avif?v={$version}";
                                                    ?>
                                                    <img src="<?= $thumbSrc ?>" 
                                                         onerror="this.onerror=null; this.src='productos/<?= $row['nombre_categoria'] ?>/miniaturas/tn-<?= $row['nombre_categoria'] ?>.avif?v=<?= $version ?>';"/>
                                                    </picture>
                                                </figure>
                                                <figure class="nPPeX" style="display: none"></figure>
                                            </div>
                                        </div>
                                        <div class="cpPtLA" style="opacity: 1; transform: none">
                                            <span class="fsRkrk"><?= $row['titulo_categoria'] ?></span>
                                        </div>
                                    </button>
                                <?php endforeach; ?>
                                <button class="jJlTfc" onclick="mostrarModelos();">
                                    <div class="hzfrPi">
                                        <figure class="dkFAPQ" style="opacity: 1; transform: none">
                                            <div class="cTEkWK">
                                                <?php foreach ($modelos as $modelo): ?>
                                                    <div onclick="cambiarModelo('<?= $modelo ?>');">
                                                        <img src="modelos/tn-<?= $modelo ?>.avif?v=<?= $version ?>"
                                                             alt="<?= $modelo ?>" class="WiCYT" />
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </figure>
                                    </div>
                                    <div class="cpPtLA" style="opacity: 1; transform: none">
                                        <span class="fsRkrk">Tono de piel</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Paneles por cada categoría -->
                    <?php foreach ($prendas as $categoria => $items): 
                            if (empty($items)) continue;
                            $contenedor = $items[0]['contenedor_categoria'];
                        ?>
                        <div id="<?= $categoria ?>" class="CCGaA invisibles">
                            <div class="korxed" style="opacity: 1">
                                <?php if($contenedor == "saco") { ?>
                                    <div class="womN">
                                        <button class="btn_quitar" onclick="quitarPrenda('saco')">
                                            <svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" class="sc-fecFrY iyEpvL">
                                                <circle cx="9" cy="9" r="8.4" stroke="currentColor" stroke-width="1.2"></circle>
                                                <path d="M14.7856 3.21436 3.21411 14.7859" stroke="currentColor" stroke-width="1.2" stroke-linecap="square"></path>
                                            </svg>
                                            <span class="sc-bhlPkD eYoBEa"></span>
                                        </button>
                                    </div>
                                <?php } ?>
                                <div class="fHoayM">
                                    <div class="cpezMr" style="transform: none">
                                        <?php foreach ($items as $prenda): ?>
                                            <button id="B<?= $prenda['imagen'] ?>" aria-pressed="false" class="jBEGle"
                                                <?php if(isset($prenda['sku']) && !empty($prenda['sku'])): ?>
                                                    data-sku="<?= $prenda['sku'] ?>"
                                                <?php endif; ?>
                                                <?php if(isset($prenda['id']) && !empty($prenda['id'])): ?>
                                                    data-id="<?= $prenda['id'] ?>"
                                                <?php endif; ?>
                                                onclick="cambiarPrenda('<?= $prenda['nombre_categoria'] ?>', '<?= $prenda['contenedor_categoria'] ?>', '<?= $prenda['imagen'] ?>','B<?= $prenda['imagen'] ?>', '<?= isset($prenda['sku']) ? $prenda['sku'] : '' ?>', '<?= isset($prenda['id']) ? $prenda['id'] : '' ?>')">
                                                <div class="jrkhdw">
                                                    <div class="cNOKjb" style="opacity: 1; transform: none">
                                                        <figure class="nPPeX" style="transform: none">
                                                            <picture class="gHfhyG">
                                                            <?php 
                                                              $prendaThumb = (strpos($prenda['imagen'], 'http') === 0) 
                                                                              ? $prenda['imagen'] 
                                                                              : "productos/{$prenda['nombre_categoria']}/miniaturas/tn-{$prenda['imagen']}.avif?v={$version}";
                                                            ?>
                                                                <img src="<?= $prendaThumb ?>" 
                                                                    onerror="this.onerror=null; this.src='productos/<?= $prenda['nombre_categoria'] ?>/miniaturas/tn-<?= $prenda['nombre_categoria'] ?>.avif?v=<?= $version ?>';" />
                                                            </picture>
                                                        </figure>
                                                        <figure class="nPPeX" style="display: none; opacity: 0; transform: translateX(-60%) translateY(80%) scale(1.5) translateZ(0px);"></figure>
                                                    </div>
                                                </div>
                                                <div id="descripcion-<?= $prenda['imagen'] ?>" class="descripcion-producto">
                                                    <div class="gKnbKV" style="height: auto; opacity: 1">
                                                        <div class="bYlofk">
                                                            <div colspan="3" class="bprzmX"><?= $prenda['nombre'] ?></div>
                                                            <div class="dzetyW">
                                                                <div class="itFurM">
                                                                    <div class="ivPKty"><?= $prenda['descripcion'] ?></div>
                                                                    <div class="gqOXjX">•</div>
                                                                </div>
                                                                <div class="precio">$<?= $prenda['precio'] ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="ziGjo" style="opacity: 1">
                                <div class="hYoVLg" style="opacity: 1">
                                    <button class="btn cYxzbB btn_volver" onclick="habilitar('menu');"></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="tonosdepiel" class="vjFXj">
                    <div class="OboQv">
                        <div class="epDXnR" style="opacity: 1">
                            <span class="bVJgrT" style="height: auto; opacity: 1">Seleccione un tono de piel</span>
                            <div class="fHoayM">
                                <div class="dgqJQF" style="transform: none">
                                    <?php foreach ($modelos as $modelo): ?>
                                        <button id="1" aria-pressed="false" class="fqsEqB" onclick="cambiarModeloMob('<?= $modelo ?>');">
                                            <div class="fJGmGc">
                                                <div class="liFgsc" style="opacity: 1; transform: none">
                                                    <figure class="nPPeX" style="transform: none">
                                                        <picture class="gHfhyG">
                                                            <img alt="<?= $modelo ?>" src="modelos/tn-<?= $modelo ?>.avif?v=<?= $version ?>" />
                                                        </picture>
                                                    </figure>
                                                    <figure class="nPPeX" style="display: none; opacity: 0; transform: translateX(120%) translateY(10%) scale(1.5) translateZ(0px);"></figure>
                                                </div>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="jOJNLU" style="opacity: 1">
                        <div class="hYoVLg" style="opacity: 1">
                            <button type="button" aria-label="Accept" class="cYxzbB dWZCvR" onclick="habilitar('menu');ocultarModelos();">
                                <span class="gyanSr" style="opacity: 1">
                                    <svg width="17" height="12" viewBox="0 0 17 12" fill="none" xmlns="http://www.w3.org/2000/svg" class="sc-dzbdsH cqpjGt">
                                        <path d="M1 6L6 11L16 1" stroke="currentColor"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Inputs ocultos para almacenar valores iniciales -->
    <input type="hidden" id="primer-saco" value="<?php 
        foreach ($prenda_inicial as $categoria => $prenda) {
            if ($prenda['nombre_categoria'] === 'saco') { echo $prenda['imagen']; break; }
        }
    ?>">
    <input type="hidden" id="primer-pantalon" value="<?php 
        foreach ($prenda_inicial as $categoria => $prenda) {
            if ($prenda['nombre_categoria'] === 'pantalon') { echo $prenda['imagen']; break; }
        }
    ?>">
    <!-- Botón "Shop Look" -->
    <a href="#" id="shopLookButton" class="shop-look-btn">Shop Look</a>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Inicializar el array de SKUs y IDs seleccionados
      window.selectedSKUs = window.selectedSKUs || [];
      window.selectedProductIds = window.selectedProductIds || [];
      
      // Configurar el evento click para el botón Shop Look - SOLUCIÓN SIMPLE
      const shopLookButton = document.getElementById('shopLookButton');
      if (shopLookButton) {
        shopLookButton.addEventListener('click', function(e) {
          e.preventDefault();
          
          // Mostrar el loader
          document.getElementById('loader').style.display = 'block';
          
          console.log('Botón Shop Look presionado. SKUs en array:', window.selectedSKUs);
          console.log('IDs de productos seleccionados:', window.selectedProductIds);
          
          // Verificar si hay productos seleccionados
          if (window.selectedProductIds.length > 0) {
            // Usar el ID del producto directamente (más confiable que el SKU)
            window.location.href = `https://sartoriacielomilano.com/?add-to-cart=${window.selectedProductIds[0]}&quantity=1`;
          } 
          else if (window.selectedSKUs.length > 0) {
            // Usar el SKU como alternativa
            window.location.href = `https://sartoriacielomilano.com/?add-to-cart=${window.selectedSKUs[0]}&quantity=1`;
          } 
          else {
            document.getElementById('loader').style.display = 'none';
            alert('Por favor, selecciona al menos un producto.');
          }
        });
      } else {
        console.error('No se encontró el botón con id="shopLookButton".');
      }
    });
    </script>
    <!-- Bootstrap JS y dependencias -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const arrayIds = <?php echo json_encode($categoriasEnUso); ?>;
        let trajeSeleccionadoPreviamente = false;
        const cacheVersion = '<?= $version ?>';
        
        // Función para cambiar prendas con soporte para SKU e ID
        function cambiarPrenda(categoria, contenedor, imagen, boton, sku, id) {
            // Mostrar el div del contenedor
            document.getElementById(`div-${contenedor}`).style.display = "block";
            
            // Actualizar la imagen según su origen (API o local)
            if (imagen.startsWith('http')) {
                document.querySelector(`#contenedor-${contenedor} img`).src = imagen;
            } else {
                document.querySelector(`#contenedor-${contenedor} img`).src = `productos/${categoria}/${imagen}.avif?v=${cacheVersion}`;
            }
            
            // Actualizar estado de los botones
            const buttons = document.querySelectorAll(`[id^="B"]`);
            buttons.forEach(btn => {
                if (btn.id === boton) {
                    btn.setAttribute("aria-pressed", "true");
                    btn.classList.add("seleccionado");
                    
                    // Manejar el SKU y ID para productos
                    if (id) {
                        // Si tenemos ID, lo usamos (más confiable)
                        window.selectedProductIds = [id]; // Reemplazamos el array con solo este ID
                        console.log(`ID de producto agregado: ${id}`);
                    }
                    
                    if (sku) {
                        // Si tenemos SKU, lo usamos como respaldo
                        window.selectedSKUs = [sku]; // Reemplazamos el array con solo este SKU
                        console.log(`SKU agregado: ${sku}`);
                    } else if (imagen.startsWith('http')) {
                        // Para imágenes de API sin SKU explícito, intentamos obtenerlo del atributo data-sku
                        const skuFromData = btn.getAttribute('data-sku');
                        if (skuFromData) {
                            window.selectedSKUs = [skuFromData];
                            console.log(`SKU agregado desde data-attribute: ${skuFromData}`);
                        }
                        
                        // También intentamos obtener el ID
                        const idFromData = btn.getAttribute('data-id');
                        if (idFromData) {
                            window.selectedProductIds = [idFromData];
                            console.log(`ID agregado desde data-attribute: ${idFromData}`);
                        }
                    }
                } else {
                    btn.setAttribute("aria-pressed", "false");
                    btn.classList.remove("seleccionado");
                }
            });
        }
        
        // Función para quitar una prenda
        function quitarPrenda(contenedor) {
            document.getElementById(`div-${contenedor}`).style.display = "none";
            
            // Al quitar una prenda, limpiamos los arrays de selección
            if (contenedor === 'pantalon') {
                window.selectedSKUs = [];
                window.selectedProductIds = [];
                console.log('Se ha quitado el pantalón y limpiado los arrays de selección');
            }
        }
        
        // Función para habilitar un panel
        function habilitar(id, contenedor) {
            const elementos = document.querySelectorAll('.CCGaA, #menu');
            elementos.forEach(elemento => {
                elemento.classList.add('invisibles');
            });
            
            if (id !== 'menu') {
                document.getElementById(id).classList.remove('invisibles');
            } else {
                document.getElementById('menu').classList.remove('invisibles');
            }
        }
        
        // Funciones para manejar los modelos
        function mostrarModelos() {
            document.getElementById('tonosdepiel').style.display = 'block';
        }
        
        function ocultarModelos() {
            document.getElementById('tonosdepiel').style.display = 'none';
        }
        
        function cambiarModelo(modelo) {
            document.querySelector('#contenedor-modelos img:first-child').src = `modelos/${modelo}.avif?v=${cacheVersion}`;
            document.querySelector('#contenedor-modelos img:last-child').src = `modelos/manos/manos-${modelo}.avif?v=${cacheVersion}`;
        }
        
        function cambiarModeloMob(modelo) {
            cambiarModelo(modelo);
            ocultarModelos();
        }
        
        // Inicialización cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            // Ocultar el loader cuando todo esté cargado
            document.getElementById('loader').style.display = 'none';
            
            // Inicializar el panel de menú como visible
            document.getElementById('menu').classList.remove('invisibles');
            
            // Ocultar el panel de tonos de piel
            document.getElementById('tonosdepiel').style.display = 'none';
            
            // Inicializar arrays de selección
            window.selectedSKUs = [];
            window.selectedProductIds = [];
        });
    </script>
</body>
</html>



<!-- og code -->