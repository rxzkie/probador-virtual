        // Función para añadir parámetro de versión a cualquier URL
        function agregarVersionAUrl(url) {
          // Comprobar si la URL ya tiene parámetros
          const separador = url.includes('?') ? '&' : '?';
          return `${url}${separador}v=${cacheVersion}`;
      }
      
      function isMobile() {
          return window.matchMedia("(max-width: 1024px)").matches;
      }

      function habilitar(categoria,contenedor) {
          // Lo que hacemos es recorrer el array, el producto que queremos mostrar le quitamos la clase invisibles y se la agregamos a todos los demas paneles
              arrayIds.push('menu'); //Agrego menu al array
              arrayIds.forEach(id => {
                  
                  const elemento = document.getElementById(id);
                  
                  if (id === categoria) {
                    // Acción si coincide
                    // elemento.style.display = 'block';
                    elemento.classList.remove("invisibles");
                    // muestro en mobile la descripcion del producto actual
                    if (isMobile() && categoria != "menu") {
                      mostrarDescripcionInicial(categoria,contenedor);
                    }
                    // if(producto=="traje") {
                    //     mostrarDescripcion('pantalon');
                    // } else {
                    //     var producto_actual = id+'1';
                    //     mostrarDescripcion(producto_actual);
                    // }
                  } else {
                    // Acción si no coincide
                    // elemento.style.display = 'none';
                    elemento.classList.add("invisibles");
                  }
              });
          

      }

      // Variable para rastrear el botón actualmente seleccionado
      let botonSeleccionadoId = null;
      
      function seleccionarBoton(id) {
        // Si hay un botón ya seleccionado, quitarle la clase
        if (botonSeleccionadoId) {
          const botonAnterior = document.getElementById(botonSeleccionadoId);
          if (botonAnterior) {
            botonAnterior.classList.remove("remarcado");
          }
        }

        // Marcar el nuevo botón
        const botonActual = document.getElementById(id);
        if (botonActual) {
          botonActual.classList.add("remarcado");
          botonSeleccionadoId = id;
        }
      }

      function cambiarPrenda(nombre_categoria,contenedor_categoria, nombreImagen, sinCuello, botonId) {
          // Obtener valores de las prendas iniciales
          const primerSaco = document.getElementById('primer-saco').value;
          const primerPantalon = document.getElementById('primer-pantalon').value;

          seleccionarBoton(botonId);

          switch (nombre_categoria) {
              case "traje": 
                  // Si seleccionamos un traje, marcar que hay un traje seleccionado
                  trajeSeleccionadoPreviamente = true;
                  cambiar_prenda(nombre_categoria,'saco', 'saco-'+nombreImagen);
                  cambiar_prenda(nombre_categoria,'pantalon', 'pantalon-'+nombreImagen);
              break;
              case "saco": 
                  // Si estamos cambiando un saco y previamente había un traje
                  if (trajeSeleccionadoPreviamente) {
                      // Cargar el primer pantalón
                      cambiar_prenda('pantalon', 'pantalon', primerPantalon);
                  } 
                  cambiar_prenda(nombre_categoria,contenedor_categoria, nombreImagen);
                  trajeSeleccionadoPreviamente = false;
              break;
              case "pantalon": 
              case "bermuda": 
              case "bano": 
                  // Si estamos cambiando un pantalón y previamente había un traje
                  if (trajeSeleccionadoPreviamente) {
                      // Cargar el primer saco
                      cambiar_prenda('saco', 'saco', primerSaco);
                  } 
                  cambiar_prenda(nombre_categoria,contenedor_categoria, nombreImagen);
                  trajeSeleccionadoPreviamente = false;
              break;
              default:
                  cambiar_prenda(nombre_categoria,contenedor_categoria, nombreImagen);
                  trajeSeleccionadoPreviamente = false;
                  break;
          }

          const divcamisa = document.getElementById("div-camisa");
          const divsaco = document.getElementById("div-saco");

          if (contenedor_categoria == "camisa") {
              divsaco.classList.remove("sincuello");
            if (nombre_categoria == "polera") {
              divcamisa.classList.add("zindex8");
              if (sinCuello == "SI") {
                divsaco.classList.add("sincuello");
              } else {
                divsaco.classList.remove("sincuello");
              }
            } else if (nombre_categoria == "camisa") {
              divcamisa.classList.remove("zindex8");
            }
          }
      }

      // Busco la imagen que esta cargada actualmente en un contenedor
      function imagenActual(nombreContenedor) {

          const contenedor = document.getElementById(
            nombreContenedor
          ); 
          const imagen = contenedor.querySelector('img');

          if (imagen) {
              // Obtiene la URL completa
              const srcCompleto = imagen.src;
              
              // Extrae el nombre del archivo con extensión
              const nombreConExtension = srcCompleto.split('/').pop();
              
              // Si quieres quitar la extensión (opcional)
              const nombreSinExtension = nombreConExtension.split('.')[0];
              
              return nombreSinExtension;
              } else {
                  console.log('No se encontró ninguna imagen en el contenedor');
                  return null;
              }

      }

      
      function mostrarDescripcionInicial(categoria,contenedor) {
        // Solo ejecutar en mobile
        if (!isMobile()) return;

        const descripciones = document.querySelectorAll(
          ".descripcion-producto"
        );

        // Declarar las variables fuera de los bloques condicionales
        let imagen_actual;
        let botonId;
        let partes;
        let descripcionId;

        // Busco la imagen actual del contenedor
        let divContenedor = "div-" + contenedor;
        imagen_actual = imagenActual(divContenedor);
        // alert(imagen_actual);

        // Comparo la imagen sin numeros con la categoria
        let imagenSinNumeros = quitarNumeros(imagen_actual);
        if(imagenSinNumeros == categoria) {
          botonId = "B" + imagen_actual;
          descripcionId = imagen_actual;
        } else {
          imagen_actual = imagenActual(categoria);
          partes = imagen_actual.split("-");
            botonId = "B" + partes[1];
            descripcionId = partes[1];
        }

        // Ahora botonId está disponible aquí
        seleccionarBoton(botonId);

        descripciones.forEach((desc) => {
          desc.style.display = "none";
        });

        const descripcionActual = document.getElementById(
          `descripcion-${descripcionId}`
        );
        if (descripcionActual) {
          descripcionActual.style.display = "flex";
        }
      }

      function mostrarDescripcion(categoria) {
        // Solo ejecutar en mobile
        if (!isMobile()) return;

        const descripciones = document.querySelectorAll(
          ".descripcion-producto"
        );

        descripciones.forEach((desc) => {
          // desc.classList.add('invisibles');
          desc.style.display = "none";
        });

        const descripcionActual = document.getElementById(
          `descripcion-${categoria}`
        );
        if (descripcionActual) {
          // descripcionActual.classList.remove('invisibles');
          descripcionActual.style.display = "flex";
        }
      }

      function quitarPrenda(contenedor_categoria) {
          if(contenedor_categoria == "traje") {
              quitar_prenda('saco');
              quitar_prenda('pantalon');
          } else {
              quitar_prenda(contenedor_categoria);
          }
          if (contenedor_categoria == "camisa") {
            const divsaco = document.getElementById("div-saco");
            divsaco.classList.add("sincuello");
          }
      }
      function quitar_prenda(contenedor_categoria) {
          // alert(contenedor);
          const contenedor = document.getElementById(`contenedor-${contenedor_categoria}`);
          if (contenedor) {
              contenedor.style.display = 'none';
              // const imagen = contenedor.getElementsByTagName('img')[0];
              // if (imagen) {
              //     contenedor.removeChild(imagen);
              // }
          }
      }

      function mostrarModelos() {
          // Solo ejecutar en mobile
          // if (!isMobile()) return;

          const tonos = document.getElementById('tonosdepiel');
          const sidebar = document.getElementById('sidebar');

          tonos.style.display="flex";
          sidebar.style.display="none";

          const manequin = document.getElementById('manequin');
          manequin.style.transform = `translate3d(0px, 170px, 0px) scale(1.6)`;//agrando el tamaño del manequin para que se vea mejor
      }
      function ocultarModelos() {
          // Solo ejecutar en mobile
          // if (!isMobile()) return;

          const tonos = document.getElementById('tonosdepiel');
          const sidebar = document.getElementById('sidebar');

          tonos.style.display="none";
          sidebar.style.display="flex";

          const manequin = document.getElementById('manequin');
          manequin.style.transform = `translate3d(0px, 0px, 0px) scale(1)`;
      }

      // Función para actualizar la altura
      function setVH() {
          let vh = window.innerHeight * 0.01;
          document.documentElement.style.setProperty('--vh', `${vh}px`);
      }

      // Ejecutar al cargar y cuando se redimensione
      window.addEventListener('load', setVH);
      window.addEventListener('resize', setVH);

  // ***************************************************
      document.addEventListener('DOMContentLoaded', () => {
          const loader = document.getElementById('loader');
          const modelContainer = document.getElementById('manequin');
          
          // Esperar a que todas las imágenes carguen
          Promise.all(
              Array.from(modelContainer.getElementsByTagName('img'))
                  .map(img => {
                      return new Promise((resolve) => {
                          if (img.complete) resolve();
                          img.addEventListener('load', resolve);
                          img.addEventListener('error', resolve); // Por si hay error, seguimos adelante
                      });
                  })
          ).then(() => {
              // Ocultar loader y mostrar modelo
              loader.style.display = 'none';
              modelContainer.style.opacity = '1';
          });
      });

      function quitarNumeros(texto) {
        // Esta expresión regular reemplaza todos los dígitos (0-9) por una cadena vacía
        return texto.replace(/\d/g, "");
      }

      function cambiar_prenda(
        nombre_categoria,
        contenedor_categoria,
        nombreImagen
      ) {
        const contenedor = document.getElementById(
          `contenedor-${contenedor_categoria}`
        );

        // Verificamos si el contenedor existe
        if (!contenedor) {
          console.error(
            `No se encontró el contenedor para ${contenedor_categoria}`
          );
          return;
        }

        // Crear la nueva imagen
        const nuevaImagen = document.createElement("img");

        // Configurar la nueva imagen
        const rutaImagen = `productos/${nombre_categoria}/${nombreImagen}.avif`;
        // Usar el parámetro de versión para forzar una carga nueva
        nuevaImagen.src = agregarVersionAUrl(rutaImagen);

        const imagenPrevia = contenedor.getElementsByTagName("img")[0];

        nuevaImagen.onload = () => {
          if (imagenPrevia) {
            contenedor.replaceChild(nuevaImagen, imagenPrevia);
          } else {
            contenedor.appendChild(nuevaImagen);
          }
        };
        // Mostrar descripcion del producto
        mostrarDescripcion(nombreImagen);
        contenedor.style.display = "block";
      }

      function cambiarModelo(nombreImagen) {
        // if (isMobile()) return; // No se ejecuta  en mobile
        const contenedor = document.getElementById(`contenedor-modelos`);

        // Verificamos si el contenedor existe
        if (!contenedor) {
          console.error(`No se encontró el contenedor`);
          return;
        }

        // Crear la nueva imagen
        const nuevaImagen1 = document.createElement("img");
        const nuevaImagen2 = document.createElement("img");

        // Configurar las nuevas imágenes
        const rutaImagen1 = `modelos/${nombreImagen}.avif`;
        const rutaImagen2 = `modelos/manos/manos-${nombreImagen}.avif`;
        // Usar el parámetro de versión para forzar una carga nueva
        nuevaImagen1.src = agregarVersionAUrl(rutaImagen1);
        nuevaImagen2.src = agregarVersionAUrl(rutaImagen2);
        nuevaImagen2.className = "manos"; // Agregamos la clase a la segunda imagen

        // Obtener las imágenes previas
        const imagenesPrevias = contenedor.getElementsByTagName("img");
        const imagenPrevia1 = imagenesPrevias[0];
        const imagenPrevia2 = imagenesPrevias[1];

        // Contador para verificar cuando ambas imágenes estén cargadas
        let imagenesListas = 0;

        const reemplazarImagenes = () => {
          imagenesListas++;
          if (imagenesListas === 2) {
            // Cuando ambas imágenes estén cargadas
            if (imagenPrevia1) {
              contenedor.replaceChild(nuevaImagen1, imagenPrevia1);
            } else {
              contenedor.appendChild(nuevaImagen1);
            }

            if (imagenPrevia2) {
              contenedor.replaceChild(nuevaImagen2, imagenPrevia2);
            } else {
              contenedor.appendChild(nuevaImagen2);
            }
          }
        };
        // Configurar los eventos onload para ambas imágenes
        nuevaImagen1.onload = reemplazarImagenes;
        nuevaImagen2.onload = reemplazarImagenes;
      }