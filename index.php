<?php
$version = time();
echo "Versión: $version";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('Connections/con1.php');

// Carga de productos desde la API (páginas 1 y 2)
$api_url_page1 = "https://sartoriacielomilano.com/wp-json/wc/store/v1/products?per_page=100&page=1";
$response_page1 = file_get_contents($api_url_page1);
$api_products_page1 = json_decode($response_page1, true);

$api_url_page2 = "https://sartoriacielomilano.com/wp-json/wc/store/v1/products?per_page=100&page=2";
$response_page2 = file_get_contents($api_url_page2);
$api_products_page2 = json_decode($response_page2, true);

// Fusionamos todos los productos de ambas páginas
$api_products = array_merge($api_products_page1, $api_products_page2);

// Función para "slugificar" categorías
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return $text;
}

// Recolectamos categorías
$categorias = [];
foreach ($api_products as $product) {
    foreach ($product['categories'] as $cat) {
        $slug = slugify($cat['name']);
        if (!isset($categorias[$slug])) {
            $categorias[$slug] = $cat['name'];
        }
    }
}

// Agrupamos los productos de la API por categoría
$api_categorias = [];
foreach ($categorias as $slug => $nombre) {
    $api_categorias[$slug] = array_filter($api_products, function($product) use ($nombre) {
        return in_array($nombre, array_column($product['categories'], 'name'));
    });
}

// Cargamos modelos de la base de datos
$modelos = [];
mysqli_select_db($con1, $database_con1);
$sql = "SELECT modelo FROM $tabla_modelos WHERE habilitado = 'SI' ORDER BY orden";
$result = $con1->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $modelos[] = $row['modelo'];
    }
} else {
    // Si no hay modelos en la BD, definimos algunos por defecto
    $modelos = ['modelo1','modelo2','modelo3'];
}

// Armamos el array $prendas, usando la PRIMERA imagen para el panel y la SEGUNDA para el maniquí
$prendas = [];
foreach ($categorias as $slug => $nombre) {
    $prendas[$slug] = [];
    if (isset($api_categorias[$slug])) {
        foreach ($api_categorias[$slug] as $api_p) {
            // Primera imagen para el panel de selección
            $thumb = isset($api_p['images'][0]['src']) ? $api_p['images'][0]['src'] : '';
            
            // Segunda imagen para el maniquí, si no existe, usamos la primera
            $mannequin = isset($api_p['images'][1]['src']) ? $api_p['images'][1]['src'] : $thumb;

            $price = $api_p['prices']['sale_price'] ?? $api_p['prices']['price'] ?? '';
            $prendas[$slug][] = [
                'nombre'              => $api_p['name'],
                'thumbnail'           => $thumb, // Primera imagen (panel de selección)
                'imagen'              => $mannequin, // Segunda imagen (maniquí)
                'nombre_categoria'    => $slug,
                'contenedor_categoria'=> $slug,
                'titulo_categoria'    => $nombre,
                'descripcion'         => '',
                'precio'              => $price,
                'sku'                 => $api_p['sku'],
                'id'                  => $api_p['id']
            ];
        }
    }
}

// Para la barra lateral, tomamos la primera prenda de cada categoría
$prenda_sidebar = [];
$categoriasEnUso = [];
foreach ($categorias as $slug => $nombre) {
    if (!empty($prendas[$slug])) {
        $prenda_sidebar[$slug] = $prendas[$slug][0];
        $categoriasEnUso[] = $slug;
    }
}

// Para mostrar una prenda inicial de cada categoría (si se desea mostrar algo al cargar)
$prenda_inicial = [];
foreach ($categorias as $slug => $nombre) {
    if (!empty($prendas[$slug])) {
        $prenda_inicial[$slug] = $prendas[$slug][0];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Probador Virtual</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" type="image/png" href="img/favicon.webp" />
  <meta name="robots" content="noindex">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="css/probador-virtual.css?v=<?= $version ?>" media="screen" />
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Priorizamos estilos locales con !important para evitar conflictos */
    .nomobile { display: inline !important; }
    @media (max-width: 768px) {
      .nomobile { display: none !important; }
    }
    .btn_quitar {
      background: none !important;
      border: none !important;
      display: flex !important;
      align-items: center !important;
      cursor: pointer !important;
      padding: 1px 4px !important; /* Más pequeño */
      margin-bottom: 6px !important;
      font-size: 0.7rem !important; /* Más pequeño */
    }
    .btn_quitar .eYoBEa {
      margin-left: 2px !important;
    }
    .womN {
      margin-bottom: 10px !important;
    }
    .contenedor-modelo img {
      max-width: 100% !important;
      height: auto !important;
    }
    .manos {
      position: absolute !important;
      top: 0 !important;
      left: 0 !important;
      z-index: 10 !important;
    }
    .shop-look-btn {
      background-color: #000 !important;
      color: #fff !important;
      text-decoration: none !important;
      padding: 8px 15px !important;
      border-radius: 4px !important;
      font-weight: bold !important;
    }
    .shop-look-btn:hover {
      background-color: #333 !important;
      color: #fff !important;
    }
    .seleccionado {
      border: 2px solid #007bff !important;
      border-radius: 4px !important;
    }
    /* Posiciones de cada categoría en el maniquí */
    #div-pantalones { position: absolute !important; top: 31% !important; left: 90 !important; width: 100% !important; z-index: 5 !important; }
    #div-camisas { position: absolute !important; top: 31% !important; left: 0 !important; width: 100% !important; z-index: 6 !important; }
    #div-sacos { position: absolute !important; top: 31% !important; left: 0 !important; width: 100% !important; z-index: 7 !important; }
    #div-zapatos { position: absolute !important; top: 31% !important; left: 0 !important; width: 100% !important; z-index: 4 !important; }
    #div-corbatas { position: absolute !important; top: 31% !important; left: 0 !important; width: 100% !important; z-index: 8 !important; }
    #div-cinturones { position: absolute !important; top: 31% !important; left: 0 !important; width: 100% !important; z-index: 9 !important; }
    #div-accesorios { position: absolute !important; top: 31% !important; left: 0 !important; width: 100% !important; z-index: 10 !important; }
    #div-calcetines { position: absolute !important; top: 31% !important; left: 0 !important; width: 100% !important; z-index: 3 !important; }
    #div-sombreros { position: absolute !important; top: 31% !important; left: 0 !important; width: 100% !important; z-index: 11 !important; }
    #div-bano {
      position: absolute !important;
      top: 25% !important; /* Ajustado para alinearse con la cintura */
      left: 50% !important; /* Centrado horizontalmente */
      transform: translateX(-50%) !important; /* Ajuste para centrar la imagen */
      width: 100% !important;
      max-width: 300px !important; /* Limita el ancho para que no exceda al modelo */
      z-index: 5 !important;
    }
    #div-bano img {
      width: 100% !important; /* Asegura que la imagen se ajuste al contenedor */
      height: auto !important;
    }
    #menu {
      min-width: 250px !important;
      padding: 10px !important;
    }
    .total-categoria {
      margin-left: 10px !important;
      font-size: 0.9em !important;
      color: #555 !important;
    }
    #quitar-global {
      position: absolute !important;
      bottom: 10px !important; /* Justo debajo del traje de baño */
      left: 50% !important;
      transform: translateX(-50%) !important;
      z-index: 1000 !important;
      background-color: rgba(255,255,255,0.8) !important;
      border-radius: 3px !important; /* Más pequeño */
      padding: 2px 6px !important; /* Más pequeño */
      box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
      font-size: 0.7rem !important; /* Más pequeño */
    }
    #resumen-compra {
      position: fixed !important;
      bottom: 20px !important;
      left: 20px !important;
      z-index: 999 !important;
      background-color: rgba(255,255,255,0.9) !important;
      border-radius: 5px !important;
      padding: 10px !important;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
      max-width: 300px !important;
      max-height: 300px !important;
      overflow-y: auto !important;
    }
    .resumen-item {
      display: flex !important;
      justify-content: space-between !important;
      margin-bottom: 5px !important;
      font-size: 0.9em !important;
    }
    .resumen-total {
      border-top: 1px solid #ddd !important;
      margin-top: 5px !important;
      padding-top: 5px !important;
      font-weight: bold !important;
    }
  </style>
</head>
<body>
<div class="full-height">
  <div>
    <div class="TaAFo">
      <div id="probador" class="hIrAZy" style="position: relative;">
        <!-- Loader -->
        <div id="loader" class="position-absolute top-50 start-50 translate-middle">
          <div class="spinner-border"></div>
        </div>
        <!-- Maniquí principal -->
        <div id="manequin" class="idGQcZ">
          <div class="gKuJZd jWhCmT">
            <!-- Contenedor de modelos (tono de piel) -->
            <div id="contenedor-modelos" class="contenedor-modelo">
              <?php if (!empty($modelos)): ?>
                <img src="modelos/<?= $modelos[0] ?>.avif?v=<?= $version ?>">
                <img src="modelos/manos/manos-<?= $modelos[0] ?>.avif?v=<?= $version ?>" class="manos">
              <?php else: ?>
                <img src="modelos/default.avif?v=<?= $version ?>">
                <img src="modelos/manos/manos-default.avif?v=<?= $version ?>" class="manos">
              <?php endif; ?>
            </div>
            <!-- Capas de prendas iniciales (si se desea mostrar algo al cargar) -->
            <?php foreach ($prenda_inicial as $key => $row): ?>
              <?php $imgSrc = $row['imagen']; ?>
              <div id="div-<?= $row['nombre_categoria'] ?>" class="gKuJZd hfgSWw" style="display: none;">
                <div id="contenedor-<?= $row['contenedor_categoria'] ?>" class="contenedor">
                  <img src="<?= $imgSrc ?>" style="max-width: 100%; height: auto;">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Barra lateral (menú principal) -->
      <div id="sidebar" class="sidebar">
        <div id="menu" class="iQNMuV">
          <div class="fHoayM">
            <div class="eRxhqm" style="opacity: 1">
              <?php foreach ($prenda_sidebar as $key => $prenda): ?>
                <button class="jJlTfc" onclick="habilitar('<?= $key ?>','<?= $prenda['contenedor_categoria'] ?>');">
                  <div class="dnzqbB">
                    <div class="kwQXt" style="opacity: 1; transform: none">
                      <figure class="nPPeX" style="transform: none">
                        <picture class="gHfhyG">
                          <img 
                            src="<?= $prenda['thumbnail'] ?>"
                            onerror="this.onerror=null; this.src='productos/<?= $key ?>/miniaturas/tn-<?= $key ?>.avif?v=<?= $version ?>';"
                          />
                        </picture>
                      </figure>
                    </div>
                  </div>
                  <div class="cpPtLA" style="opacity: 1; transform: none">
                    <span class="fsRkrk"><?= $prenda['titulo_categoria'] ?></span>
                    <span id="total-<?= $key ?>" class="total-categoria">$0</span>
                  </div>
                </button>
              <?php endforeach; ?>
              <!-- Botón para cambiar el tono de piel -->
              <button class="jJlTfc" onclick="mostrarModelos();">
                <div class="hzfrPi">
                  <figure class="dkFAPQ" style="opacity: 1; transform: none">
                    <div class="cTEkWK">
                      <?php foreach ($modelos as $modelo): ?>
                        <div onclick="cambiarModelo('<?= $modelo ?>');">
                          <img src="modelos/tn-<?= $modelo ?>.avif?v=<?= $version ?>" alt="<?= $modelo ?>" class="WiCYT" />
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
          <!-- Contador y botón "Shop Look" -->
          <div style="margin-top: 20px; text-align: center;">
            <span id="itemCounter" style="font-weight: bold;">Items: 0 | Total: $0</span>
            <a href="#" id="shopLookButton" class="shop-look-btn" style="margin-left: 10px;">Shop Look</a>
          </div>
        </div>

        <!-- Sección que se muestra al hacer clic en una categoría -->
        <?php foreach ($categorias as $slug => $nombre): ?>
          <?php if (!empty($prendas[$slug])): ?>
            <div id="<?= $slug ?>" class="CCGaA invisibles">
              <div class="korxed" style="opacity: 1">
                <div class="fHoayM">
                  <div class="cpezMr" style="transform: none">
                    <?php foreach ($prendas[$slug] as $prenda): ?>
                      <?php 
                        // Generamos un id único para cada botón
                        $botonId = "B" . md5($prenda['thumbnail'].$prenda['imagen']); 
                      ?>
                      <button 
                        id="<?= $botonId ?>" 
                        aria-pressed="false" 
                        class="jBEGle"
                        data-sku="<?= $prenda['sku'] ?>"
                        data-id="<?= $prenda['id'] ?>"
                        data-precio="<?= $prenda['precio'] ?>"
                        data-nombre="<?= $prenda['nombre'] ?>"
                        data-categoria="<?= $slug ?>"
                        onclick="cambiarPrenda('<?= $slug ?>','<?= $prenda['contenedor_categoria'] ?>','<?= $prenda['imagen'] ?>','<?= $botonId ?>','<?= $prenda['sku'] ?>','<?= $prenda['id'] ?>','<?= $prenda['precio'] ?>','<?= $prenda['nombre'] ?>')"
                      >
                        <div class="jrkhdw">
                          <div class="cNOKjb" style="opacity: 1; transform: none">
                            <figure class="nPPeX" style="transform: none">
                              <picture class="gHfhyG">
                                <img 
                                  src="<?= $prenda['thumbnail'] ?>"
                                  onerror="this.onerror=null; this.src='productos/<?= $slug ?>/miniaturas/tn-<?= $slug ?>.avif?v=<?= $version ?>';"
                                />
                              </picture>
                            </figure>
                          </div>
                        </div>
                        <div id="descripcion-<?= md5($prenda['nombre']) ?>" class="descripcion-producto">
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
                <div class="ziGjo" style="opacity: 1">
                  <div class="hYoVLg" style="opacity: 1">
                    <button class="btn cYxzbB btn_volver" onclick="habilitar('menu');"></button>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <!-- Ventana para seleccionar el tono de piel -->
      <div id="tonosdepiel" class="vjFXj" style="display: none;">
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

<!-- Botón global para quitar todas las prendas seleccionadas -->
<button id="quitar-global" class="btn_quitar" onclick="quitarPrendaSeleccionada();">
  <svg width="12" height="12" fill="none" xmlns="http://www.w3.org/2000/svg" class="sc-fecFrY iyEpvL">
    <circle cx="6" cy="6" r="5.4" stroke="currentColor" stroke-width="1.2"></circle>
    <path d="M9.7856 2.21436 2.21411 9.7859" stroke="currentColor" stroke-width="1.2" stroke-linecap="square"></path>
  </svg>
  <span class="eYoBEa">Quitar</span>
</button>

<!-- Resumen de compra -->
<div id="resumen-compra">
  <h5>Resumen de compra</h5>
  <div id="resumen-items"></div>
  <div id="resumen-total" class="resumen-total">Total: $0</div>
</div>

<script>
// Variables globales para manejo de selección
document.addEventListener('DOMContentLoaded', function() {
  window.selectedItems = {};
  window.selectedCount = 0;
  window.selectedPrice = 0;
  window.lastSelectedCategory = null;

  // Manejo del botón "Shop Look"
  const shopLookButton = document.getElementById('shopLookButton');
  if (shopLookButton) {
    shopLookButton.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('loader').style.display = 'block';
      const selectedIds = Object.values(window.selectedItems).map(item => item.id);
      if (selectedIds.length > 0) {
        let cartUrl = 'https://sartoriacielomilano.com/?';
        selectedIds.forEach((id, index) => {
          cartUrl += `add-to-cart[${index}]=${id}&quantity[${index}]=1&`;
        });
        cartUrl += 'redirect_to=' + encodeURIComponent('https://sartoriacielomilano.com/carrito/');
        window.location.href = cartUrl;
      } else {
        document.getElementById('loader').style.display = 'none';
        alert('Por favor, selecciona al menos un producto.');
      }
    });
  }
  document.getElementById('loader').style.display = 'none';
});

// Función para cambiar la prenda seleccionada en el maniquí
function cambiarPrenda(categoria, contenedor, imagen, boton, sku, id, precio, nombre) {
  window.lastSelectedCategory = categoria;
  // Mostramos el contenedor de la prenda
  document.getElementById(`div-${categoria}`).style.display = "block";
  const container = document.getElementById(`contenedor-${contenedor}`);
  if (container && container.querySelector('img')) {
    container.querySelector('img').src = imagen;
  }
  // Actualizamos estado de botones
  const buttons = document.querySelectorAll(`#${categoria} [id^="B"]`);
  buttons.forEach(btn => {
    if (btn.id === boton) {
      btn.setAttribute("aria-pressed", "true");
      btn.classList.add("seleccionado");
      window.selectedItems[categoria] = {
        id, 
        sku, 
        precio: parseFloat(precio) || 0, 
        nombre: nombre, 
        categoria: categoria
      };
    } else {
      btn.setAttribute("aria-pressed", "false");
      btn.classList.remove("seleccionado");
    }
  });
  // Mostramos precio en el botón de la barra lateral
  document.getElementById(`total-${categoria}`).textContent = `$${parseFloat(precio) || 0}`;
  // Actualizamos contadores y resumen
  actualizarContador();
  actualizarResumenCompra();
}

// Función para quitar la prenda de una categoría
function quitarPrenda(categoria) {
  const divPrenda = document.getElementById(`div-${categoria}`);
  if (divPrenda) divPrenda.style.display = "none";
  // Quitamos selección de los botones
  const buttons = document.querySelectorAll(`#${categoria} [id^="B"]`);
  buttons.forEach(btn => {
    btn.setAttribute("aria-pressed", "false");
    btn.classList.remove("seleccionado");
  });
  // Removemos del array de items seleccionados
  delete window.selectedItems[categoria];
  // Reseteamos precio en la barra lateral
  document.getElementById(`total-${categoria}`).textContent = '$0';
  actualizarContador();
  actualizarResumenCompra();
}

// Función para quitar TODAS las prendas seleccionadas
function quitarPrendaSeleccionada() {
  const categories = Object.keys(window.selectedItems);
  categories.forEach(function(category) {
    quitarPrenda(category);
  });
  window.lastSelectedCategory = null;
}

// Actualiza el contador general de ítems y el precio total
function actualizarContador() {
  window.selectedCount = Object.keys(window.selectedItems).length;
  window.selectedPrice = Object.values(window.selectedItems)
    .reduce((total, item) => total + parseFloat(item.precio || 0), 0);
  document.getElementById('itemCounter').textContent = `Items: ${window.selectedCount} | Total: $${window.selectedPrice}`;
}

// Actualiza el resumen de compra en el recuadro fijo
function actualizarResumenCompra() {
  const resumenItems = document.getElementById('resumen-items');
  const resumenTotal = document.getElementById('resumen-total');
  resumenItems.innerHTML = '';
  let total = 0;
  Object.values(window.selectedItems).forEach(item => {
    const precio = parseFloat(item.precio) || 0;
    total += precio;
    const itemDiv = document.createElement('div');
    itemDiv.className = 'resumen-item';
    itemDiv.innerHTML = `<span>${item.nombre}</span><span>$${precio}</span>`;
    resumenItems.appendChild(itemDiv);
  });
  resumenTotal.textContent = `Total: $${total}`;
}

// Muestra el panel de selección de categoría o regresa al menú principal
function habilitar(id) {
  const panels = document.querySelectorAll('.CCGaA, #menu');
  panels.forEach(panel => panel.classList.add('invisibles'));
  if (id !== 'menu') {
    document.getElementById(id).classList.remove('invisibles');
  } else {
    document.getElementById('menu').classList.remove('invisibles');
  }
}

// Muestra/oculta la sección de tonos de piel
function mostrarModelos() {
  document.getElementById('tonosdepiel').style.display = 'block';
}
function ocultarModelos() {
  document.getElementById('tonosdepiel').style.display = 'none';
}

// Cambia el modelo (tono de piel) del maniquí
function cambiarModelo(modelo) {
  const modelContainer = document.getElementById('contenedor-modelos');
  if (modelContainer) {
    const imgs = modelContainer.querySelectorAll('img');
    // La primera imagen es el cuerpo
    if (imgs[0]) imgs[0].src = `modelos/${modelo}.avif?v=<?= $version ?>`;
    // La segunda imagen son las manos
    if (imgs[1]) imgs[1].src = `modelos/manos/manos-${modelo}.avif?v=<?= $version ?>`;
  }
}

// Versión para móvil (cierra la ventana de selección al cambiar)
function cambiarModeloMob(modelo) {
  cambiarModelo(modelo);
  ocultarModelos();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>