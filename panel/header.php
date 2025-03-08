<header id="header_listado" class="lightgray">
    <nav class="navbar fixed-top navbar-expand-lg navbar-light bg-light ">
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse w-100 flex-wrap" id="navbarTogglerDemo01">
        <div class="row d-flex justify-content-between align-items-center w-100 pl-5 pr-5">
            <div class="d-inline-flex  mr-5">
              <h1 class="mb-0 text-uppercase"><?php echo $panel; ?></h1>
            </div>
            <ul class="navbar-nav mr-auto mt-2 mt-lg-0 botones">
              <li class="nav-item">
                <button class='btn btn-info px-1' onclick='mostrar_select();'  title='Seleccionar'>&nbsp;<i class="bi bi-check2-square mr-2"></i>Seleccionar</button>
              </li>
              <li class="nav-item selecciones invisibles">
                <button id='btn-eliminar' class='btn btn-danger' title='Eliminar Seleccionados' onclick='eliminar_seleccionados("<?php echo $panel; ?>")'><i class='bi bi-trash mr-2'></i>Borrar</button>
              </li>
              <li class="nav-item <?php if($panel!='prendas') echo "invisibles"; ?>">
                <a href="agregar-<?= $panel ?>.php" class="btn btn-info" title="Agregar Prenda"><i class="bi bi-file-earmark-plus mr-2"></i>Agregar Prenda</a>
              </li>
              
              
              <li class="nav-item <?php if($panel!='prendas') echo "invisibles"; ?>">
                <a href="agregar-listado-<?= $panel ?>.php" class="btn btn-info" title="Agregar Listado Prendas"><i class="bi bi-file-earmark-plus mr-2"></i>Agregar Listado Prendas</a>
              </li>
              <li class="nav-item <?php if($panel!='categorias') echo "invisibles"; ?>">
                <a href="agregar-<?= $panel ?>.php" class="btn btn-info" title="Agregar Categoría"><i class="bi bi-file-earmark-plus mr-2"></i>Agregar Categoría</a>
              </li>
              <li class="nav-item <?php if(($panel=='prendas')) echo "invisibles"; ?>">
                <a href="listado-prendas.php" class="btn btn-secondary" title="Listado Prendas"><i class="bi bi-card-list mr-2"></i>Prendas</a>
              </li>
              <li class="nav-item <?php if(($panel=='categorias')) echo "invisibles"; ?>">
                <a href="listado-categorias.php" class="btn btn-secondary" title="Listado Categorías"><i class="bi bi-card-list mr-2"></i>Categorías</a>
              </li>
            </ul>
            <div class="form-row form-inline my-2 my-lg-0 justify-content-end align-items-normal">
              <div class="col-md-6">
                <input class="form-control mr-sm-2" type="search" placeholder="Buscar" name="buscado" id="buscado" title="Buscar por id, documento, nombre o apellido" aria-label="Small" aria-describedby="inputGroup-sizing-sm">
              </div>
              <button class="btn btn-outline-info my-2 my-sm-0" type="text" onclick="buscar();"><i class="bi bi-search"></i></button>
              <a href="salir.php" class="btn btn-outline-danger px-1 ml-3 " title="Salir">&nbsp;<i class="bi bi-box-arrow-right"></i>&nbsp;</a>
            </div>
        </div>
        
      </div>
    </nav>
  </header>
