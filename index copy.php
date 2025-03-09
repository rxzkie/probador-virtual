<?php
$version = time();
echo "Versión: $version";

// Cabeceras para evitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('Connections/con1.php');

// Obtiene los productos desde la API de WooCommerce
$api_url = "https://sartoriacielomilano.com/wp-json/wc/store/v1/products";
$response = file_get_contents($api_url);
$api_products = json_decode($response, true);

// Filtra productos de "Trajes de Baño"
$api_trajes_bano = array_filter($api_products, function($product) {
    return in_array('Trajes de Baño', array_column($product['categories'], 'name'));
});

// Consulta de modelos de piel
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

// Prepara array de prendas
$prendas = [];
$prendas['bano'] = [];
foreach ($api_trajes_bano as $api_b) {
    $thumb = isset($api_b['images'][0]['src']) ? $api_b['images'][0]['src'] : '';
    // Si existe una segunda imagen, la usamos. Si no, usamos la misma en 'thumb'
    $mannequin = isset($api_b['images'][1]['src']) ? $api_b['images'][1]['src'] : $thumb;
    // Precio
    $price = $api_b['prices']['sale_price'] ?? $api_b['prices']['price'] ?? '';

    $prendas['bano'][] = [
        'nombre'              => $api_b['name'],
        'thumbnail'           => $thumb,
        'imagen'              => $mannequin,
        'nombre_categoria'    => 'bano',
        'contenedor_categoria'=> 'pantalon',
        'titulo_categoria'    => 'Trajes de Baño',
        'descripcion'         => '',
        'precio'              => $price,
        'sku'                 => $api_b['sku'],
        'id'                  => $api_b['id']
    ];
}

// Para el sidebar y prenda inicial, tomamos la primera de "bano"
$prenda_sidebar = [];
$categoriasEnUso = [];
if (!empty($prendas['bano'])) {
    $prenda_sidebar['bano'] = $prendas['bano'][0];
    $categoriasEnUso[] = 'bano';
}

$prenda_inicial = [];
if (!empty($prendas['bano'])) {
    $prenda_inicial['bano'] = $prendas['bano'][0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Probador Virtual</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Favicons y meta tags para no indexar -->
  <link rel="shortcut icon" type="image/png" href="img/favicon.webp" />
  <meta name="robots" content="noindex">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Hoja de estilos -->
  <link rel="stylesheet" type="text/css" href="css/probador-virtual.css?v=<?= $version ?>" media="screen" />
  
  <!-- Fuentes Google -->
  <link property="stylesheet" rel="stylesheet" id="qwery-font-google_fonts-css" 
        href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700&amp;family=Cormorant+Garamond:wght@400;500;600;700&amp;subset=latin,latin-ext&amp;display=swap" 
        type="text/css" media="all">
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

    /* Ajuste para la posición del short (bano) */
    /* Eliminamos el width fijo y top:280px; y en su lugar usamos un % */
    #div-bano {
      position: absolute;
      top: 31%;
      z-index: 5; /* Ajusta si quieres superponer sobre otras prendas */
      /* No definimos width fijo para que el CSS general haga su trabajo */
    }
  </style>
</head>

<body>
<div class="full-height">
  <div>
    <div class="TaAFo">
      <!-- Contenedor principal del probador -->
      <div id="probador" class="hIrAZy" style="position: relative;">
        <!-- Loader -->
        <div id="loader" class="position-absolute top-50 start-50 translate-middle">
          <div class="spinner-border"></div>
        </div>
        
        <!-- Mannequin -->
        <div id="manequin" class="idGQcZ">
          <div class="gKuJZd jWhCmT">
            <!-- Contenedor del modelo de piel -->
            <div id="contenedor-modelos" class="contenedor-modelo">
              <?php if (!empty($modelos)): ?>
                <img src="modelos/<?= $modelos[0] ?>.avif?v=<?= $version ?>"> 
                <img src="modelos/manos/manos-<?= $modelos[0] ?>.avif?v=<?= $version ?>" class="manos">
              <?php else: ?>
                <img src="modelos/default.avif?v=<?= $version ?>">
                <img src="modelos/manos/manos-default.avif?v=<?= $version ?>" class="manos">
              <?php endif; ?>
            </div>

            <!-- Prenda inicial (Short de baño) -->
            <?php foreach ($prenda_inicial as $key => $row): ?>
              <?php
                // Si la imagen es URL completa (http...), la usamos tal cual
                $imgSrc = (strpos($row['imagen'], 'http') === 0) 
                          ? $row['imagen'] 
                          : "productos/{$row['nombre_categoria']}/{$row['imagen']}.avif?v={$version}";
              ?>
              <!-- Notar que hemos quitado el style inline con width: 300px y top: 280px -->
              <div id="div-<?= $row['nombre_categoria'] ?>" 
                   class="gKuJZd hfgSWw">
                <div id="contenedor-<?= $row['contenedor_categoria'] ?>" class="contenedor">
                  <img src="<?= $imgSrc ?>" style="max-width: 100%; height: auto;">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- SIDEBAR -->
      <div id="sidebar" class="sidebar">
        <div id="menu" class="iQNMuV">
          <div class="fHoayM">
            <div class="eRxhqm" style="opacity: 1">
              
              <!-- Botón de Trajes de Baño en el menú lateral -->
              <?php if (!empty($prenda_sidebar['bano'])): 
                $bano = $prenda_sidebar['bano']; ?>
                <button class="jJlTfc" onclick="habilitar('bano','<?= $bano['contenedor_categoria'] ?>');">
                  <div class="dnzqbB">
                    <div class="kwQXt" style="opacity: 1; transform: none">
                      <figure class="nPPeX" style="transform: none">
                        <picture class="gHfhyG">
                          <?php 
                            $thumbSrc = (strpos($bano['thumbnail'], 'http') === 0)
                                        ? $bano['thumbnail']
                                        : "productos/bano/miniaturas/tn-{$bano['thumbnail']}.avif?v={$version}";
                          ?>
                          <img src="<?= $thumbSrc ?>" 
                               onerror="this.onerror=null; this.src='productos/bano/miniaturas/tn-bano.avif?v=<?= $version ?>';"/>
                        </picture>
                      </figure>
                      <figure class="nPPeX" style="display: none"></figure>
                    </div>
                  </div>
                  <div class="cpPtLA" style="opacity: 1; transform: none">
                    <span class="fsRkrk"><?= $bano['titulo_categoria'] ?></span>
                  </div>
                </button>
              <?php endif; ?>

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
        </div>

        <!-- Panel con la lista de Trajes de Baño -->
        <?php if (!empty($prendas['bano'])): ?>
          <div id="bano" class="CCGaA invisibles">
            <div class="korxed" style="opacity: 1">
              <!-- Botón de quitar -->
              <div class="womN">
                <button id="btn_quitar" class="btn_quitar" onclick="quitarPrenda('bano');seleccionarBoton(this.id);">
                  <svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg" class="sc-fecFrY iyEpvL">
                    <circle cx="9" cy="9" r="8.4" stroke="currentColor" stroke-width="1.2"></circle>
                    <path d="M14.7856 3.21436 3.21411 14.7859" stroke="currentColor" stroke-width="1.2" stroke-linecap="square"></path>
                  </svg>
                  <span class="eYoBEa"><span class="nomobile">Quitar</span></span>
                </button>
              </div>
              
              <div class="fHoayM">
                <div class="cpezMr" style="transform: none">
                  <?php foreach ($prendas['bano'] as $prenda): ?>
                    <?php 
                      $botonId = "B" . md5($prenda['thumbnail'].$prenda['imagen']);
                    ?>
                    <button id="<?= $botonId ?>" aria-pressed="false" class="jBEGle"
                      data-sku="<?= $prenda['sku'] ?>"
                      data-id="<?= $prenda['id'] ?>"
                      data-precio="<?= $prenda['precio'] ?>"
                      onclick="cambiarPrenda('bano','<?= $prenda['contenedor_categoria'] ?>','<?= $prenda['imagen'] ?>','<?= $botonId ?>','<?= $prenda['sku'] ?>','<?= $prenda['id'] ?>','<?= $prenda['precio'] ?>')">
                      <div class="jrkhdw">
                        <div class="cNOKjb" style="opacity: 1; transform: none">
                          <figure class="nPPeX" style="transform: none">
                            <picture class="gHfhyG">
                              <?php 
                                $prendaThumb = (strpos($prenda['thumbnail'], 'http') === 0)
                                               ? $prenda['thumbnail']
                                               : "productos/bano/miniaturas/tn-{$prenda['thumbnail']}.avif?v={$version}";
                              ?>
                              <img src="<?= $prendaThumb ?>" 
                                   onerror="this.onerror=null; this.src='productos/bano/miniaturas/tn-bano.avif?v=<?= $version ?>';" />
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
      </div>

      <!-- Panel para seleccionar tono de piel -->
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

<!-- Contador de items y botón Shop Look -->
<div style="position:absolute; bottom:20px; right:20px; z-index:9999;">
  <span id="itemCounter" style="margin-right:15px; font-weight:bold;">Items: 0 | Total: $0</span>
  <a href="#" id="shopLookButton" class="shop-look-btn">Shop Look</a>
</div>

<script>
// Cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
  // Arrays y contadores para carrito
  window.selectedSKUs = [];
  window.selectedProductIds = [];
  window.selectedCount = 0;
  window.selectedPrice = 0;

  const shopLookButton = document.getElementById('shopLookButton');
  if (shopLookButton) {
    shopLookButton.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('loader').style.display = 'block';

      if (window.selectedProductIds.length > 0) {
        // Si tenemos ID, lo usamos (más confiable)
        const url = `https://sartoriacielomilano.com/?add-to-cart=${window.selectedProductIds[0]}&quantity=1`;
        fetch(url, { method: 'GET', credentials: 'same-origin' })
          .then(() => {
            window.location.href = 'https://sartoriacielomilano.com/carrito/';
          })
          .catch(error => {
            document.getElementById('loader').style.display = 'none';
            alert('Error al agregar el producto al carrito.');
          });
      } else if (window.selectedSKUs.length > 0) {
        // Como alternativa, usar el SKU
        const url = `https://sartoriacielomilano.com/?add-to-cart=${window.selectedSKUs[0]}&quantity=1`;
        fetch(url, { method: 'GET', credentials: 'same-origin' })
          .then(() => {
            window.location.href = 'https://sartoriacielomilano.com/carrito/';
          })
          .catch(error => {
            document.getElementById('loader').style.display = 'none';
            alert('Error al agregar el producto al carrito.');
          });
      } else {
        // Nada seleccionado
        document.getElementById('loader').style.display = 'none';
        alert('Por favor, selecciona al menos un producto.');
      }
    });
  }
  
  // Inicializar contador con valores de la prenda inicial
  const initialButtons = document.querySelectorAll('[id^="B"]');
  if (initialButtons.length > 0) {
    const firstButton = initialButtons[0];
    const id = firstButton.getAttribute('data-id');
    const sku = firstButton.getAttribute('data-sku');
    const precio = firstButton.getAttribute('data-precio') || 0;
    
    if (id) window.selectedProductIds = [id];
    if (sku) window.selectedSKUs = [sku];
    window.selectedCount = 1;
    window.selectedPrice = parseInt(precio) || 0;
    
    // Marcar el primer botón como seleccionado
    firstButton.setAttribute("aria-pressed", "true");
    firstButton.classList.add("seleccionado");
    
    // Actualizar contador
    document.getElementById('itemCounter').textContent = `Items: ${window.selectedCount} | Total: $${window.selectedPrice}`;
  }
  
  // Ocultamos el loader inicialmente
  document.getElementById('loader').style.display = 'none';
});

// Cambiar prenda
function cambiarPrenda(categoria, contenedor, imagen, boton, sku, id, precio) {
  // Mostrar el div (por si estaba oculto)
  document.getElementById(`div-${categoria}`).style.display = "block";

  // Cambiar la imagen
  const container = document.getElementById(`contenedor-${contenedor}`);
  if (container && container.querySelector('img')) {
    if (imagen.startsWith('http')) {
      container.querySelector('img').src = imagen;
    } else {
      container.querySelector('img').src = `productos/${categoria}/${imagen}.avif?v=<?= $version ?>`;
    }
  }

  // Quitar la selección de los demás botones
  const buttons = document.querySelectorAll(`[id^="B"]`);
  buttons.forEach(btn => {
    if (btn.id === boton) {
      btn.setAttribute("aria-pressed", "true");
      btn.classList.add("seleccionado");
      if (id) {
        window.selectedProductIds = [id];
      }
      if (sku) {
        window.selectedSKUs = [sku];
      }
      window.selectedCount = 1;
      window.selectedPrice = parseInt(precio) || 0;
    } else {
      btn.setAttribute("aria-pressed", "false");
      btn.classList.remove("seleccionado");
    }
  });

  // Actualizar contador
  document.getElementById('itemCounter').textContent = `Items: ${window.selectedCount} | Total: $${window.selectedPrice}`;
}

// Función para quitar una prenda
function quitarPrenda(categoria) {
  // Ocultar el div de la prenda
  const divPrenda = document.getElementById(`div-${categoria}`);
  if (divPrenda) {
    divPrenda.style.display = "none";
  }
  
  // Resetear selecciones
  window.selectedSKUs = [];
  window.selectedProductIds = [];
  window.selectedCount = 0;
  window.selectedPrice = 0;
  
  // Actualizar contador
  document.getElementById('itemCounter').textContent = `Items: ${window.selectedCount} | Total: $${window.selectedPrice}`;
  
  // Quitar selección de todos los botones
  const buttons = document.querySelectorAll(`[id^="B"]`);
  buttons.forEach(btn => {
    btn.setAttribute("aria-pressed", "false");
    btn.classList.remove("seleccionado");
  });
}

// Función para seleccionar un botón
function seleccionarBoton(id) {
  const boton = document.getElementById(id);
  if (boton) {
    const buttons = document.querySelectorAll('button');
    buttons.forEach(btn => {
      btn.classList.remove('seleccionado');
    });
    boton.classList.add('seleccionado');
  }
}

// Mostrar/ocultar paneles
function habilitar(id) {
  const panels = document.querySelectorAll('.CCGaA, #menu');
  panels.forEach(panel => {
    panel.classList.add('invisibles');
  });
  if (id !== 'menu') {
    document.getElementById(id).classList.remove('invisibles');
  } else {
    document.getElementById('menu').classList.remove('invisibles');
  }
}

// Tono de piel
function mostrarModelos() {
  document.getElementById('tonosdepiel').style.display = 'block';
}
function ocultarModelos() {
  document.getElementById('tonosdepiel').style.display = 'none';
}
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

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>