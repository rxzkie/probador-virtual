<?php
$version = time();
echo "Versión: $version";
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
require_once('Connections/con1.php');

$api_url = "https://sartoriacielomilano.com/wp-json/wc/store/v1/products";
$response = file_get_contents($api_url);
$api_products = json_decode($response, true);

// Filtrar productos de API para Pantalones
$api_pantalones = [];
if ($api_products) {
    $api_pantalones = array_filter($api_products, function($product) {
        return in_array('Pantalones', array_column($product['categories'], 'name'));
    });
}

// Filtrar productos de API para Camisas
$api_camisas = [];
if ($api_products) {
    $api_camisas = array_filter($api_products, function($product) {
        return in_array('Camisas', array_column($product['categories'], 'name'));
    });
}

$categorias = [];
$contenedores = [];
$prendas = [];
$modelos = [];

mysqli_select_db($con1, $database_con1);
$sql = "SELECT modelo FROM $tabla_modelos WHERE habilitado = 'SI' ORDER BY orden";
$result = $con1->query($sql);
if ($result === false) {
    echo "Error en la consulta: " . $con1->error;
} else {
    while($row = $result->fetch_assoc()) {
        $modelos[] = $row['modelo'];
    }
}
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
// Recorrer las categorías de la BD y omitir 'pantalon(es)' para cargarlo vía API
foreach ($categorias as $categoria) {
    if (strtolower($categoria) === 'pantalon' || strtolower($categoria) === 'pantalones' || strtolower($categoria) === 'camisa' || strtolower($categoria) === 'camisas') {
        continue;
    }
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

// Insertar productos de API para Pantalones
if (!empty($api_pantalones)) {
    $prendas['pantalon'] = [];
    foreach ($api_pantalones as $api_pantalon) {
        $selectedImage = $api_pantalon['images'][0]['src'] ?? '';
        if (!empty($api_pantalon['images']) && is_array($api_pantalon['images'])) {
            foreach ($api_pantalon['images'] as $img) {
                if (isset($img['name']) && strtolower($img['name']) === 'imagencalda') {
                    $selectedImage = $img['src'];
                    break;
                }
            }
        }
        $precioApi = '';
        if (isset($api_pantalon['prices']['price'])) {
            $precioApi = $api_pantalon['prices']['price'];
        }
        $api_prenda = [
            'nombre'              => $api_pantalon['name'],
            'imagen'              => $selectedImage,
            'nombre_categoria'    => 'pantalon',
            'contenedor_categoria'=> 'pantalon',
            'titulo_categoria'    => 'Pantalones',
            'descripcion'         => '',
            'precio'              => $precioApi,
            'sku'                 => $api_pantalon['sku'],
            'id'                  => $api_pantalon['id']
        ];
        $prendas['pantalon'][] = $api_prenda;
    }
}

// Insertar productos de API para Camisas
if (!empty($api_camisas)) {
    $prendas['camisa'] = [];
    foreach ($api_camisas as $api_camisa) {
        $selectedImage = $api_camisa['images'][0]['src'] ?? '';
        if (!empty($api_camisa['images']) && is_array($api_camisa['images'])) {
            foreach ($api_camisa['images'] as $img) {
                if (isset($img['name']) && strtolower($img['name']) === 'imagencalda') {
                    $selectedImage = $img['src'];
                    break;
                }
            }
        }
        $precioApi = '';
        if (isset($api_camisa['prices']['price'])) {
            $precioApi = $api_camisa['prices']['price'];
        }
        $api_prenda = [
            'nombre'              => $api_camisa['name'],
            'imagen'              => $selectedImage,
            'nombre_categoria'    => 'camisa',
            'contenedor_categoria'=> 'camisa',
            'titulo_categoria'    => 'Camisas',
            'descripcion'         => '',
            'precio'              => $precioApi,
            'sku'                 => $api_camisa['sku'],
            'id'                  => $api_camisa['id']
        ];
        $prendas['camisa'][] = $api_prenda;
    }
}

$prenda_sidebar = [];
$categoriasEnUso = [];
foreach ($prendas as $categoria => $items) {
    if (!empty($items) && isset($items[0])) {
        $prenda_sidebar[$categoria] = $items[0];
        $categoriasEnUso[] = $categoria;
    }
}
$contenedores_inicio = ['saco', 'camisa', 'pantalon', 'zapato'];
$prenda_inicial = [];
foreach ($prendas as $categoria => $items) {
    if (!empty($items)) {
        if (in_array($items[0]['nombre_categoria'], $contenedores_inicio)) {
            $prenda_inicial[$categoria] = $items[0];
        }
    }
}
if (isset($prendas['pantalon']) && isset($prendas['polera'])) {
    $tempPantalones = $prendas['pantalon'];
    unset($prendas['pantalon']);
    $nuevoOrden = [];
    foreach ($prendas as $cat => $val) {
        $nuevoOrden[$cat] = $val;
        if ($cat === 'polera') {
            $nuevoOrden['pantalon'] = $tempPantalones;
        }
    }
    $prendas = $nuevoOrden;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Probador Virtual</title>
    <link rel="shortcut icon" type="image/png" href="img/favicon.webp">
    <meta name="robots" content="noindex">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/probador-virtual.css?v=<?= $version ?>" media="screen">
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
                            <div id="contenedor-modelos" class="inyuYv">
                                <img src="modelos/<?= $modelos[0] ?>.avif?v=<?= $version ?>">
                                <img src="modelos/manos/manos-<?= $modelos[0] ?>.avif?v=<?= $version ?>" class="lakCAL">
                            </div>
                            <?php foreach ($prenda_inicial as $key => $row): ?>
                                <div id="div-<?= $row['nombre_categoria'] ?>" class="gKuJZd hfgSWw">
                                    <div id="contenedor-<?= $row['nombre_categoria'] ?>" class="inyuYv">
                                        <?php
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
                                                             alt="<?= $modelo ?>" class="WiCYT">
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
                    <?php foreach ($prendas as $categoria => $items):
                        if (empty($items)) continue;
                        $contenedor = $items[0]['contenedor_categoria'];
                    ?>
                        <div id="<?= $categoria ?>" class="CCGaA invisibles">
                            <div class="korxed" style="opacity: 1">
                                <div class="womN">
                                    <button class="btn-quitar-seleccion" onclick="quitarProductoSeleccionado()">
                                        <svg width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="9" cy="9" r="8.4" stroke="currentColor" stroke-width="1.2"></circle>
                                            <path d="M14.7856 3.21436 3.21411 14.7859" stroke="currentColor" stroke-width="1.2" stroke-linecap="square"></path>
                                        </svg>
                                        <span>Quitar</span>
                                    </button>
                                </div>
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
                                                data-precio="<?= $prenda['precio'] ?>"
                                                onclick="cambiarPrenda(
                                                    '<?= $prenda['nombre_categoria'] ?>',
                                                    '<?= $prenda['contenedor_categoria'] ?>',
                                                    '<?= $prenda['imagen'] ?>',
                                                    'B<?= $prenda['imagen'] ?>',
                                                    '<?= isset($prenda['sku']) ? $prenda['sku'] : '' ?>',
                                                    '<?= isset($prenda['id']) ? $prenda['id'] : '' ?>',
                                                    '<?= isset($prenda['precio']) ? $prenda['precio'] : '' ?>'
                                                )">
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
                                                                    onerror="this.onerror=null; this.src='productos/<?= $prenda['nombre_categoria'] ?>/miniaturas/tn-<?= $prenda['nombre_categoria'] ?>.avif?v=<?= $version ?>';">
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
                                                                <div class="precio">
                                                                    <?php if(!empty($prenda['precio'])): ?>
                                                                        $<?= $prenda['precio'] ?>
                                                                    <?php endif; ?>
                                                                </div>
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
                                                            <img alt="<?= $modelo ?>" src="modelos/tn-<?= $modelo ?>.avif?v=<?= $version ?>">
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

    <input type="hidden" id="primer-saco" value="<?php
        foreach ($prenda_inicial as $categoria => $prenda) {
            if ($prenda['nombre_categoria'] === 'saco') {
                echo $prenda['imagen'];
                break;
            }
        }
    ?>">
    <input type="hidden" id="primer-pantalon" value="<?php
        foreach ($prenda_inicial as $categoria => $prenda) {
            if ($prenda['nombre_categoria'] === 'pantalon') {
                echo $prenda['imagen'];
                break;
            }
        }
    ?>">

    <div class="d-flex align-items-center justify-content-center mt-3">
      <a href="#" id="shopLookButton" class="shop-look-btn me-3">
        Shop Look
      </a>
      <span id="itemCounter" class="fw-bold"></span>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
  window.selectedSKUs = [];
  window.selectedProductIds = [];
  window.selectedPrice = 0;
  updateItemCounter();

  const shopLookButton = document.getElementById('shopLookButton');
  if (shopLookButton) {
    shopLookButton.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('loader').style.display = 'block';
      if (window.selectedProductIds.length > 0) {
        const addToCartUrl = `https://sartoriacielomilano.com/?add-to-cart=${window.selectedProductIds[0]}&quantity=1`;
        fetch(addToCartUrl, { method: 'GET', credentials: 'same-origin' })
          .then(() => {
            window.location.href = 'https://sartoriacielomilano.com/carrito/';
          })
          .catch(error => {
            console.error('Error al agregar el producto por ID:', error);
            document.getElementById('loader').style.display = 'none';
            alert('Hubo un error al agregar el producto al carrito.');
          });
      } else if (window.selectedSKUs.length > 0) {
        const addToCartUrl = `https://sartoriacielomilano.com/?add-to-cart=${window.selectedSKUs[0]}&quantity=1`;
        fetch(addToCartUrl, { method: 'GET', credentials: 'same-origin' })
          .then(() => {
            window.location.href = 'https://sartoriacielomilano.com/carrito/';
          })
          .catch(error => {
            console.error('Error al agregar el producto por SKU:', error);
            document.getElementById('loader').style.display = 'none';
            alert('Hubo un error al agregar el producto al carrito.');
          });
      } else {
        document.getElementById('loader').style.display = 'none';
        alert('Por favor, selecciona al menos un producto.');
      }
    });
  } else {
    console.error('No se encontró el botón con id="shopLookButton".');
  }
});

function updateItemCounter() {
  const itemCounter = document.getElementById('itemCounter');
  if (!itemCounter) return;
  let count = 0;
  if (window.selectedProductIds.length > 0 || window.selectedSKUs.length > 0) {
    count = 1;
  }
  let total = window.selectedPrice && !isNaN(window.selectedPrice) ? parseFloat(window.selectedPrice) : 0;
  if (count > 0) {
    itemCounter.textContent = `${count} item seleccionado - $${total}`;
  } else {
    itemCounter.textContent = '0 items - $0';
  }
}

function quitarProductoSeleccionado() {
  if (window.selectedContainer) {
    document.getElementById(`div-${window.selectedContainer}`).style.display = "none";
  }
  window.selectedSKUs = [];
  window.selectedProductIds = [];
  window.selectedContainer = null;
  window.selectedPrice = 0;
  updateItemCounter();
}

function cambiarPrenda(categoria, contenedor, imagen, boton, sku, id, precio) {
  document.getElementById(`div-${contenedor}`).style.display = "block";
  if (imagen.startsWith('http')) {
    document.querySelector(`#contenedor-${contenedor} img`).src = imagen;
  } else {
    document.querySelector(`#contenedor-${contenedor} img`).src = `productos/${categoria}/${imagen}.avif?v=${cacheVersion}`;
  }
  const buttons = document.querySelectorAll(`[id^="B"]`);
  buttons.forEach(btn => {
    if (btn.id === boton) {
      btn.setAttribute("aria-pressed", "true");
      btn.classList.add("seleccionado");
      window.selectedContainer = contenedor;
      if (id) {
        window.selectedProductIds = [id];
      }
      if (sku) {
        window.selectedSKUs = [sku];
      } else if (imagen.startsWith('http')) {
        const skuFromData = btn.getAttribute('data-sku');
        if (skuFromData) {
          window.selectedSKUs = [skuFromData];
        }
        const idFromData = btn.getAttribute('data-id');
        if (idFromData) {
          window.selectedProductIds = [idFromData];
        }
      }
      window.selectedPrice = precio && !isNaN(precio) ? parseFloat(precio) : 0;
    } else {
      btn.setAttribute("aria-pressed", "false");
      btn.classList.remove("seleccionado");
    }
  });
  updateItemCounter();
}

function quitarPrenda(contenedor) {
  document.getElementById(`div-${contenedor}`).style.display = "none";
  if (contenedor === 'pantalon') {
    window.selectedSKUs = [];
    window.selectedProductIds = [];
    window.selectedPrice = 0;
    updateItemCounter();
  }
}

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

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('loader').style.display = 'none';
  document.getElementById('menu').classList.remove('invisibles');
  document.getElementById('tonosdepiel').style.display = 'none';
});
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
