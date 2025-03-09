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

$api_url_page1 = "https://sartoriacielomilano.com/wp-json/wc/store/v1/products?per_page=100&page=1";
$response_page1 = file_get_contents($api_url_page1);
$api_products_page1 = json_decode($response_page1, true);

$api_url_page2 = "https://sartoriacielomilano.com/wp-json/wc/store/v1/products?per_page=100&page=2";
$response_page2 = file_get_contents($api_url_page2);
$api_products_page2 = json_decode($response_page2, true);

$api_products = array_merge($api_products_page1, $api_products_page2);

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return $text;
}

$categorias = [];
foreach ($api_products as $product) {
    foreach ($product['categories'] as $cat) {
        $slug = slugify($cat['name']);
        if (!isset($categorias[$slug])) {
            $categorias[$slug] = $cat['name'];
        }
    }
}

$api_categorias = [];
foreach ($categorias as $slug => $nombre) {
    $api_categorias[$slug] = array_filter($api_products, function($product) use ($nombre) {
        return in_array($nombre, array_column($product['categories'], 'name'));
    });
}

$modelos = [];
mysqli_select_db($con1, $database_con1);
$sql = "SELECT modelo FROM $tabla_modelos WHERE habilitado = 'SI' ORDER BY orden";
$result = $con1->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $modelos[] = $row['modelo'];
    }
} else {
    $modelos = ['modelo1','modelo2','modelo3'];
}

$prendas = [];
foreach ($categorias as $slug => $nombre) {
    $prendas[$slug] = [];
    foreach ($api_categorias[$slug] as $api_p) {
        $thumb = isset($api_p['images'][0]['src']) ? $api_p['images'][0]['src'] : '';
        $mannequin = isset($api_p['images'][0]['src']) ? $api_p['images'][0]['src'] : $thumb;
        $price = $api_p['prices']['sale_price'] ?? $api_p['prices']['price'] ?? '';

        $prendas[$slug][] = [
            'nombre'              => $api_p['name'],
            'thumbnail'           => $thumb,
            'imagen'              => $mannequin,
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

$prenda_sidebar = [];
$categoriasEnUso = [];
foreach ($categorias as $slug => $nombre) {
    if (!empty($prendas[$slug])) {
        $prenda_sidebar[$slug] = $prendas[$slug][0];
        $categoriasEnUso[] = $slug;
    }
}

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
    .nomobile {
      display: inline;
    }
    @media (max-width: 768px) {
      .nomobile {
        display: none;
      }
    }
    .btn_quitar {
      background: none;
      border: none;
      display: flex;
      align-items: center;
      cursor: pointer;
      padding: 5px 10px;
      margin-bottom: 10px;
    }
    .btn_quitar .eYoBEa {
      margin-left: 5px;
    }
    .womN {
      margin-bottom: 10px;
    }
    .contenedor-modelo img {
      max-width: 100%;
      height: auto;
    }
    .manos {
      position: absolute;
      top: 0;
      left: 0;
      z-index: 10;
    }
    .shop-look-btn {
      background-color: #000;
      color: #fff;
      text-decoration: none;
      padding: 8px 15px;
      border-radius: 4px;
      font-weight: bold;
    }
    .shop-look-btn:hover {
      background-color: #333;
      color: #fff;
    }
    .seleccionado {
      border: 2px solid #007bff;
      border-radius: 4px;
    }
    #div-pantalones { position: absolute; top: 31%; left: 0; width: 100%; z-index: 5; }
    #div-camisas { position: absolute; top: 31%; left: 0; width: 100%; z-index: 6; }
    #div-sacos { position: absolute; top: 31%; left: 0; width: 100%; z-index: 7; }
    #div-zapatos { position: absolute; top: 31%; left: 0; width: 100%; z-index: 4; }
    #div-corbatas { position: absolute; top: 31%; left: 0; width: 100%; z-index: 8; }
    #div-cinturones { position: absolute; top: 31%; left: 0; width: 100%; z-index: 9; }
    #div-accesorios { position: absolute; top: 31%; left: 0; width: 100%; z-index: 10; }
    #div-calcetines { position: absolute; top: 31%; left: 0; width: 100%; z-index: 3; }
    #div-sombreros { position: absolute; top: 31%; left: 0; width: 100%; z-index: 11; }
    #div-bano { position: absolute; top: 31%; left: 0; width: 100%; z-index: 5; }
    #menu {
      min-width: 250px;
      padding: 10px;
    }
    .total-categoria {
      margin-left: 10px;
      font-size: 0.9em;
      color: #555;
    }
    #quitar-global {
      position: fixed;
      bottom: 20px;
      left: 20px;
      z-index: 1000;
      background-color: rgba(255, 255, 255, 0.8);
      border-radius: 5px;
      padding: 8px 15px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    #resumen-compra {
      position: fixed;
      bottom: 20px;
      left: 20px;
      z-index: 999;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 5px;
      padding: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      max-width: 300px;
      max-height: 300px;
      overflow-y: auto;
    }
    .resumen-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
      font-size: 0.9em;
    }
    .resumen-total {
      border-top: 1px solid #ddd;
      margin-top: 5px;
      padding-top: 5px;
      font-weight: bold;
    }
  </style>
</head>

<body>
<div class="full-height">
  <div>
    <div class="TaAFo">
      <div id="probador" class="hIrAZy" style="position: relative;">
        <div id="loader" class="position-absolute top-50 start-50 translate-middle">
          <div class="spinner-border"></div>
        </div>
        
        <div id="manequin" class="idGQcZ">
          <div class="gKuJZd jWhCmT">
            <div id="contenedor-modelos" class="contenedor-modelo">
              <?php if (!empty($modelos)): ?>
                <img src="modelos/<?= $modelos[0] ?>.avif?v=<?= $version ?>">
                <img src="modelos/manos/manos-<?= $modelos[0] ?>.avif?v=<?= $version ?>" class="manos">
              <?php else: ?>
                <img src="modelos/default.avif?v=<?= $version ?>">
                <img src="modelos/manos/manos-default.avif?v=<?= $version ?>" class="manos">
              <?php endif; ?>
            </div>

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
                          <img src="<?= $prenda['thumbnail'] ?>"
                               onerror="this.onerror=null; this.src='productos/<?= $key ?>/miniaturas/tn-<?= $key ?>.avif?v=<?= $version ?>';"/>
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
          <div style="margin-top: 20px; text-align: center;">
            <span id="itemCounter" style="font-weight: bold;">Items: 0 | Total: $0</span>
            <a href="#" id="shopLookButton" class="shop-look-btn" style="margin-left: 10px;">Shop Look</a>
          </div>
        </div>

        <?php foreach ($categorias as $slug => $nombre): ?>
          <?php if (!empty($prendas[$slug])): ?>
            <div id="<?= $slug ?>" class="CCGaA invisibles">
              <div class="korxed" style="opacity: 1">
                <div class="fHoayM">
                  <div class="cpezMr" style="transform: none">
                    <?php foreach ($prendas[$slug] as $prenda): ?>
                      <?php $botonId = "B" . md5($prenda['thumbnail'].$prenda['imagen']); ?>
                      <button id="<?= $botonId ?>" aria-pressed="false" class="jBEGle"
                        data-sku="<?= $prenda['sku'] ?>"
                        data-id="<?= $prenda['id'] ?>"
                        data-precio="<?= $prenda['precio'] ?>"
                        data-nombre="<?= $prenda['nombre'] ?>"
                        data-categoria="<?= $slug ?>"
                        onclick="cambiarPrenda('<?= $slug ?>','<?= $prenda['contenedor_categoria'] ?>','<?= $prenda['imagen'] ?>','<?= $botonId ?>','<?= $prenda['sku'] ?>','<?= $prenda['id'] ?>','<?= $prenda['precio'] ?>','<?= $prenda['nombre'] ?>')">
                        <div class="jrkhdw">
                          <div class="cNOKjb" style="opacity: 1; transform: none">
                            <figure class="nPPeX" style="transform: none">
                              <picture class="gHfhyG">
                                <img src="<?= $prenda['thumbnail'] ?>"
                                     onerror="this.onerror=null; this.src='productos/<?= $slug ?>/miniaturas/tn-<?= $slug ?>.avif?v=<?= $version ?>';" />
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

<!-- Botón único para quitar todas las prendas seleccionadas -->
<button id="quitar-global" class="btn_quitar" onclick="quitarPrendaSeleccionada();">
  <svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" class="sc-fecFrY iyEpvL">
    <circle cx="9" cy="9" r="8.4" stroke="currentColor" stroke-width="1.2"></circle>
    <path d="M14.7856 3.21436 3.21411 14.7859" stroke="currentColor" stroke-width="1.2" stroke-linecap="square"></path>
  </svg>
  <span class="eYoBEa">Quitar prenda</span>
</button>

<div id="resumen-compra">
  <h5>Resumen de compra</h5>
  <div id="resumen-items"></div>
  <div id="resumen-total" class="resumen-total">Total: $0</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  window.selectedItems = {};
  window.selectedCount = 0;
  window.selectedPrice = 0;
  window.lastSelectedCategory = null;

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

function cambiarPrenda(categoria, contenedor, imagen, boton, sku, id, precio, nombre) {
  window.lastSelectedCategory = categoria;
  
  document.getElementById(`div-${categoria}`).style.display = "block";
  const container = document.getElementById(`contenedor-${contenedor}`);
  if (container && container.querySelector('img')) {
    container.querySelector('img').src = imagen;
  }

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

  document.getElementById(`total-${categoria}`).textContent = `$${parseFloat(precio) || 0}`;
  actualizarContador();
  actualizarResumenCompra();
}

function quitarPrenda(categoria) {
  const divPrenda = document.getElementById(`div-${categoria}`);
  if (divPrenda) divPrenda.style.display = "none";

  const buttons = document.querySelectorAll(`#${categoria} [id^="B"]`);
  buttons.forEach(btn => {
    btn.setAttribute("aria-pressed", "false");
    btn.classList.remove("seleccionado");
  });

  delete window.selectedItems[categoria];
  document.getElementById(`total-${categoria}`).textContent = '$0';
  actualizarContador();
  actualizarResumenCompra();
}

function quitarPrendaSeleccionada() {
  // Elimina todas las prendas seleccionadas
  const categories = Object.keys(window.selectedItems);
  categories.forEach(function(category) {
    quitarPrenda(category);
  });
  window.lastSelectedCategory = null;
}

function actualizarContador() {
  window.selectedCount = Object.keys(window.selectedItems).length;
  window.selectedPrice = Object.values(window.selectedItems).reduce((total, item) => total + parseFloat(item.precio || 0), 0);
  document.getElementById('itemCounter').textContent = `Items: ${window.selectedCount} | Total: $${window.selectedPrice}`;
}

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
    itemDiv.innerHTML = `
      <span>${item.nombre}</span>
      <span>$${precio}</span>
    `;
    resumenItems.appendChild(itemDiv);
  });
  
  resumenTotal.textContent = `Total: $${total}`;
}

function seleccionarBoton(id) {
  const boton = document.getElementById(id);
  if (boton) {
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => btn.classList.remove('seleccionado'));
    boton.classList.add('seleccionado');
  }
}

function habilitar(id) {
  const panels = document.querySelectorAll('.CCGaA, #menu');
  panels.forEach(panel => panel.classList.add('invisibles'));
  if (id !== 'menu') {
    document.getElementById(id).classList.remove('invisibles');
  } else {
    document.getElementById('menu').classList.remove('invisibles');
  }
}

function mostrarModelos() { document.getElementById('tonosdepiel').style.display = 'block'; }
function ocultarModelos() { document.getElementById('tonosdepiel').style.display = 'none'; }
function cambiarModelo(modelo) {
  const modelContainer = document.getElementById('contenedor-modelos');
  if (modelContainer) {
    const imgs = modelContainer.querySelectorAll('img');
    if (imgs[0]) imgs[0].src = `modelos/${modelo}.avif?v=<?= $version ?>`;
    if (imgs[1]) imgs[1].src = `modelos/manos/manos-${modelo}.avif?v=<?= $version ?>`;
  }
}
function cambiarModeloMob(modelo) {
  cambiarModelo(modelo);
  ocultarModelos();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
