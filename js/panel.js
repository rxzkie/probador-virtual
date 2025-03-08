
function objetoAjax(){
    var xmlhttp = false;
    try {
        xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {

        try {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (E) {
            xmlhttp = false; }
    }

    if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
      xmlhttp = new XMLHttpRequest();
    }
    return xmlhttp;
}

function borrar(id, tabla) {
  // var qres=0;
  // qres = document.formRegistro.sillas_reservadas.value;

  // var dato2 = document.formRegistro.sesion.value;

  const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
      confirmButton: "btn btn-success ml-3",
      cancelButton: "btn btn-danger mr-3",
    },
    buttonsStyling: false,
  });

  swalWithBootstrapButtons
    .fire({
      title: "Está por eliminar el registro nro " + id,
      text: "Está acción no puede deshacerse",
      // showDenyButton: true,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Continuar",
      cancelButtonText: "Cancelar",
      reverseButtons: true,
    })
    .then((result) => {
      if (result.isConfirmed) {
        var parametros = {
          id: id,
          tabla: tabla,
        };

        $.ajax({
          data: parametros,
          url: "borrar-id.php",
          type: "POST",

          // beforeSend: function () {

          //   //  return confirm("Are you sure?");
          // },

          success: function (mensaje) {
            //alert (mensaje);
            if (mensaje == 1) {
              // swal("Listo!", "", "success");
              Swal.fire("Listo", "", "success");
              // window.location.href = "lista-inscriptos.php";
              // alert("Listo!");
              // setTimeout(function () {
              //   // wait for 5 secs(2)
              //   location.reload(); // then reload the page.(3)
              // }, 500);
            
            } else {
              // swal("Lo siento", "Intente nuevamente", "error");
              Swal.fire("Hubo un error", "", "info");
              // alert("error");
            }
            dato1 = id + 1;
            const idFila = "fila" + dato1;
            sessionStorage.setItem("filaAEnfocar", idFila);
            cargarFiltro(); // then reload the page.(3)
            setTimeout(function () {
              // wait for 5 secs(2)
              Swal.close();
            }, 1000);
          },
          
        });
      }
    });
}


function poner_pagado(dato1) {
  
  const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
      confirmButton: "btn btn-success ml-3",
      cancelButton: "btn btn-danger mr-3",
    },
    buttonsStyling: false,
  });

  const inputValue = "";

  swalWithBootstrapButtons
    .fire({
      title: "Confirma que ha pagado el registro nro " + dato1,
      text: "Está acción no puede deshacerse",
      // showDenyButton: true,
      input: "text",
      inputValue: inputValue,
      inputLabel: "Comentarios sobre el pago",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Continuar",
      cancelButtonText: "Cancelar",
      reverseButtons: true,
    })
    .then((result) => {
      if (result.isConfirmed) {
        var parametros = {
          id: dato1,
          pagoforma: result.value,
        };

        $.ajax({
          data: parametros,
          url: "../pagado.php",
          type: "POST",

          // beforeSend: function () {

          //   //  return confirm("Are you sure?");
          // },

          success: function (mensaje) {
            //alert (mensaje);
            if (mensaje == 1) {
              // swal("Listo!", "", "success");
              Swal.fire("Listo", "", "success");
              // window.location.href = "lista-inscriptos.php";
              // alert("Listo!");
              
            } else {
              // swal("Lo siento", "Intente nuevamente", "error");
              Swal.fire("Hubo un error", "", "info");
              // alert("error");
            }
            const idFila = "fila" + dato1;
            sessionStorage.setItem("filaAEnfocar", idFila);
            // cargar_datos(); // then reload the page.(3)
            cargarFiltro();
            setTimeout(function () {
              // wait for 5 secs(2)
               
              Swal.close();
            }, 1000);
          },
        });
      }
    });
}

function reenviar_mail(cod, email) {
  // var qres=0;
  // qres = document.formRegistro.sillas_reservadas.value;

  // var dato2 = document.formRegistro.sesion.value;

  const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
      confirmButton: "btn btn-success ml-3",
      cancelButton: "btn btn-danger mr-3",
    },
    buttonsStyling: false,
  });

  const inputValue = email;

  const { value: emailnew } = swalWithBootstrapButtons
    .fire({
      // title:
      //   "Está por reenviar la confirmación del registro nro " +
      //   cod +
      //   "al siguiente correo",
      html:
        "<h4>Está por reenviar la confirmación del registro nro <b>" +
        cod +
        "</b> al siguiente correo</h4>",
      // text: "Puede modificarlo si lo desea",
      input: "email",
      inputValue: inputValue,
      inputLabel: "Puede modificarlo si lo desea",
      // showDenyButton: true,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Continuar",
      cancelButtonText: "Cancelar",
      reverseButtons: true,
    })
    .then((result) => {
      if (result.isConfirmed) {
        var parametros = {
          id: cod,
          ema: result.value,
        };

        $.ajax({
          data: parametros,
          url: "../reenviar-mail.php",
          type: "POST",

          // beforeSend: function () {

          //   //  return confirm("Are you sure?");
          // },

          success: function (mensaje) {
            $tiempo = 1000;
            if (mensaje == 1) {
              // swal("Listo!", "", "success");
              Swal.fire("Mail reenviado a " + result.value, "", "success");
              // window.location.href = "lista-inscriptos.php";
              // alert("Listo!");
            } else {
              // swal("Lo siento", "Intente nuevamente", "error");
              Swal.fire(
                "Hubo un error al enviar a " + result.value,
                // "Por favor revise el email ingresado",
                mensaje,
                "info"
              );
              // alert("error");
            }
            setTimeout(function () {
              // wait for 5 secs(2)
              Swal.close();
            }, $tiempo);
          },
        });
      }
    });
}

$(document).ready(function () {
  irArriba();
}); //Hacia arriba

function irArriba() {
  $(".ir-arriba").click(function () {
    $("body,html").animate({ scrollTop: "0px" }, 1000);
  });
  $(window).scroll(function () {
    if ($(this).scrollTop() > 0) {
      $(".ir-arriba").slideDown(600);
    } else {
      $(".ir-arriba").slideUp(600);
    }
  });
  $(".ir-abajo").click(function () {
    $("body,html").animate({ scrollTop: "1000px" }, 1000);
  });
}


function exportar_listado(desde, tipo) {
  // var qres=0;
  // qres = document.formRegistro.sillas_reservadas.value;

  // var dato2 = document.formRegistro.sesion.value;

  const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
      confirmButton: "btn btn-success ml-3",
      cancelButton: "btn btn-danger mr-3",
    },
    buttonsStyling: false,
  });

  const inputValue = desde;

  const { value: nrodesde } = swalWithBootstrapButtons
    .fire({
      // title:
      //   "Está por reenviar la confirmación del registro nro " +
      //   cod +
      //   "al siguiente correo",
      html: "<h4>Indique desde qué Id quiere descargar el listado</h4><small>Para descargar completo deje en 1</small> ",
      // text: "",
      input: "number",
      inputValue: inputValue,
      // showDenyButton: true,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Continuar",
      cancelButtonText: "Cancelar",
      reverseButtons: true,
    })
    .then((result) => {
      if (result.isConfirmed) {
        location.href = "exportar-" + tipo + ".php?iddesde=" + result.value;
      }
    });
}

 


function eliminar_seleccionados(tabla) {
  // Manejador del clic en el botón eliminar
  // $('#btn_eliminar').on('click', function() {
  // Array para almacenar los IDs seleccionados
  var idsSeleccionados = [];


  // Recorrer todos los checkboxes seleccionados
  $('input[type="checkbox"].seleccionados:checked').each(function () {
    idsSeleccionados.push($(this).val());
  });

  if (idsSeleccionados.length > 1) {
    var textos = " filas";
  } else {
    var textos = " fila";
  }

  const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
      confirmButton: "btn btn-success ml-3",
      cancelButton: "btn btn-danger mr-3",
    },
    buttonsStyling: false,
  });

  // Verificar si se seleccionó al menos un elemento
  if (idsSeleccionados.length > 0) {
    
    swalWithBootstrapButtons
      .fire({
        title: "Está por eliminar " + idsSeleccionados.length + textos,
        text: "Está acción no puede deshacerse",
        // showDenyButton: true,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Continuar",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
      })
      .then((result) => {
        if (result.isConfirmed) {
          // Enviar los IDs al archivo PHP mediante AJAX
          $.ajax({
            url: "eliminar.php",
            method: "POST",
            data: {
              ids: idsSeleccionados,
              tabla: tabla,
            },

            success: function (mensaje) {
              //alert (mensaje);
              if (mensaje == 1) {
                // swal("Listo!", "", "success");
                Swal.fire("Listo", "", "success");
                // window.location.href = "lista-inscriptos.php";
                // alert("Listo!");
              } else {
                // swal("Lo siento", "Intente nuevamente", "error");
                Swal.fire("Hubo un error", "Intente nuevamente", "info");
                // alert("error");
              }
              cargarFiltro();
              mostrar_select();
              setTimeout(function () {
                // wait for 5 secs(2)
                // const idFila = "fila" + dato1;
                // sessionStorage.setItem("filaAEnfocar", idFila);
                // cargar_datos(); // then reload the page.(3)
                Swal.close();
              }, 1000);
            },
          });
        }
      });
  } else {
    Swal.fire("Debe seleccionar al menos una fila", "", "info");
  }
}

//FUNCIONES TRAIDAS
function cargar_datos() {
  cargarFiltro().then(focusTableRow);
}

function buscar() {
  const buscado = document.getElementById("buscado").value;
  document.getElementById("ultima_busqueda").value = buscado;
  cargarFiltro(buscado);
}
// Llamo a buscar cuando dan Enter en el campo busqueda
$("#buscado").keypress(function (event) {
  if (event.which == 13) {
    // 13 es el código de Enter
    buscar();
  }
});
function mostrar_mensaje() {
  return new Promise((resolve) => {
    var mensaje = document.getElementById("mensaje").value;
    let titulo, subtitulo, icono = "";
    tiempo = 1000; //tiempo en milisegundos que dura el mensaje antes de cerrarse
    if (mensaje != "") {
      if (mensaje == "ok") {
        titulo = "Datos Guardados!";
        subtitulo = "";
        icono = "success";
      } else if (mensaje == "error") {
        // swal("", "Por favor complete los campos en rojo", "error");
        // Swal.fire("No hubo cambios", "", "info");
        titulo = "No hubo cambios";
        subtitulo = "";
        icono = "info";
      
    } else if (mensaje == "loginerror") {
        titulo = "Usuario o Contraseña incorrectas";
        subtitulo = "";
        icono = "error";
        tiempo = 1500;
    } else if (mensaje == "nohabilitado") {
        titulo = "Hubo un error";
        subtitulo = "Su usuario no está habilitado para este panel";
        icono = "error";
        tiempo = 1500;
    }
      Swal.fire(titulo, subtitulo, icono);
      // document.getElementById("mensaje_mostrado").value = 1;
      setTimeout(function () {
        // wait for 5 secs(2)
        Swal.close();
      }, tiempo);
    }
    // document.getElementById("mensaje").value = "";
    removeURLParameter("mje");
  });
  
}
function editar(id, tipo) {
  const idFila = "fila" + id;
  sessionStorage.setItem("filaAEnfocar", idFila);
  location.href = "editar-" + tipo + ".php?id=" + id;
}

function resaltarFila(id,estado) {
  const idFila = "fila" + id;
  const fila = document.getElementById(idFila);
  if(estado) {
    fila.classList.add("destacado");
  } 
else {
  fila.classList.remove("destacado");
}
}

function descargarListado(panel) {
  const busqueda = document.getElementById("ultima_busqueda").value;
  location.href =
    "exportar-" + panel + ".php?panel=" + panel + "&busqueda=" + busqueda;
}
// PARA MOSTRAR LA BARRA SUPERIOR
let lastScrollTop = 0;
const nav = document.querySelector("nav");

window.addEventListener("scroll", function () {
  let currentScrollTop =
    window.pageYOffset || document.documentElement.scrollTop;

  if (currentScrollTop <= 0) {
    // En el top de la página, nav visible
    nav.classList.remove("hidden");
  } else if (currentScrollTop > lastScrollTop) {
    // Scrolling down
    nav.classList.add("hidden");
  } else {
    // Scrolling up
    // nav.classList.remove('hidden');
  }

  lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;
});

document.addEventListener("mousemove", function (e) {
  let currentScrollTop =
    window.pageYOffset || document.documentElement.scrollTop;
  const nav = document.querySelector("nav");

  if (currentScrollTop > 50) {
    // Verificar que el movimiento sea vertical
    if (e.clientY <= 10 && e.movementY !== 0) {
      // Dentro de los primeros 50px desde arriba
      nav.classList.remove("hidden");
    }
    // else nav.classList.add('hidden');
  }
});

function focusTableRow() {
  const rowId = sessionStorage.getItem("filaAEnfocar");
  // const table = document.getElementById("dataTable");
  // const row = table.querySelector(`tr[data-id="fila${rowId}"]`);
  // const fila = "fila"+rowId;
  const row = document.getElementById(rowId);
  //  alert(rowId);

  if (row) {
    // alert(rowId);
    // Centrar fila en pantalla
    row.scrollIntoView({
      behavior: "smooth",
      block: "center",
    });
    // Limpiar sessionStorage
    sessionStorage.removeItem("filaAEnfocar");
  } else alert(row);
}

function mostrar_select() {
  const selecciones = document.querySelectorAll(".selecciones");
  const seleccionados = document.querySelectorAll(".seleccionados");
  const filaSeleccionada = document.querySelectorAll(".filaSeleccionada");
  const borradores = document.querySelectorAll(".borradores");
  const element = document.querySelector("#fila-eliminar");
  if (element.matches(".invisibles")) {
    selecciones.forEach((el) => {
      el.classList.remove("invisibles");
    });
    borradores.forEach((el) => {
      el.classList.add("invisibles");
    });
  } else {
    selecciones.forEach((el) => {
      el.classList.add("invisibles");
    });
    seleccionados.forEach((el) => {
      el.checked = false;
    });
    document.getElementById("selectAll").checked = false;
    filaSeleccionada.forEach((el) => {
      el.classList.remove("destacado");
    });
    borradores.forEach((el) => {
      el.classList.remove("invisibles");
    });
  }
}

function selectAll(estado) {
  const seleccionados = document.querySelectorAll(".seleccionados");
  if (estado) {
    seleccionados.forEach((el) => {
      el.checked = true;
    });
  } else {
    seleccionados.forEach((el) => {
      el.checked = false;
    });
  }
}

function actualizar_total_filas(tabla) {
  $.ajax({
    url: "consultar-base.php",
    method: "POST",
    data: {
      tabla: tabla,
    },
    success: function (dato) {
      if(dato>0) {
        document.getElementById("total_filas").value = dato;
      }
    },
  });
}

function cargarFiltro(busqueda3, indice3, orden3) {
  return new Promise((resolve) => {
    // if (!indice3) {
    //   indice = "";
    // } else {
    //   indice = indice3;
    // }
    // if (!orden3) {
    //   orden = "";
    // } else {
    //   orden = orden3;
    // }
    if (!busqueda3) {
      busqueda = "";
    } else {
      busqueda = busqueda3;
    }
    const panel = document.getElementById("panel").value;
    $.ajax({
      url: "listar-" + panel + ".php",
      method: "POST",
      data: {
        busqueda: busqueda,
        panel: panel,
      },
      success: function (data) {
        $("#tabla-filtro").html(data);
      },
    });
    // actualizar_total_filas(panel);
    resolve();
  });
}

// Para quitar un parámetro específico
function removeURLParameter(paramName) {
   const url = new URL(window.location.href);
   url.searchParams.delete(paramName);
   window.history.pushState({}, document.title, url);
}
// Función para validar la extensión del archivo antes de enviar el formulario
function revisar_archivo(campo) {
  var archivo = document.getElementById(campo);
  
  // Verificar si se ha seleccionado un archivo
  if (archivo.files.length > 0) {
    var nombreArchivo = archivo.files[0].name;
    var extension = nombreArchivo.split('.').pop().toLowerCase();
    
    // Verificar si la extensión es avif
    if (extension !== 'avif') {
      // Mostrar alerta
      Swal.fire({
        icon: 'error',
        title: 'Formato no válido',
        text: 'Solo se permiten archivos en formato AVIF',
        confirmButtonText: 'Entendido'
      });
      
      // Limpiar el campo de archivo
      archivo.value = '';
      
      // Prevenir el envío del formulario
      document.MM_returnValue = false;
      return false;
    } else {
      // Si el archivo es válido, permitir el envío
      document.getElementById('control_upload').value = 'ok';
      document.MM_returnValue = true;
      return true;
    }
  } else {
    // Si no se ha seleccionado archivo y es requerido
    if (archivo.required) {
      Swal.fire({
        icon: 'warning',
        title: 'Archivo requerido',
        text: 'Debe seleccionar un archivo',
        confirmButtonText: 'Entendido'
      });
      
      document.MM_returnValue = false;
      return false;
    }
  }
  
  // Por defecto, permitir el envío
  document.MM_returnValue = true;
  return true;
}

// Función para ocultar el botón de envío y mostrar mensaje de procesamiento
function esconder_submit() {
  document.getElementById('submit').style.display = 'none';
  document.getElementById('texto_submit').classList.remove('invisibles');
  return true;
}

function asignarIdentificador() {
  var identificador = document.getElementById("identificador");
  var campo = document.getElementById("categoria");
  var indice = campo.selectedIndex;
  //   alert(indice);
  if (indice != 0) {
    var valor = campo.options[indice].value;

    var parametros = {
      categoria: valor,
    };

    $.ajax({
      data: parametros,
      url: "asignar-identificador.php",
      type: "POST",

      beforesend: function () {
        //$('#mostrar_mensaje').html("Mensaje antes de Enviar");
      },

      success: function (respuesta) {
        //alert (mensaje);
        if (respuesta != "ERROR") {
          identificador.value = respuesta;

          // Swal.fire(mensaje, "Este código ya está en uso", "error");
        } else Swal.fire("", "No se pudo asignar un identificador", "error");
      },
    });
  } else Swal.fire("", "Se debe elegir un categoria de prenda", "warning");
}

function controlarIdentificador() {
  
  var identificador = document.getElementById("identificador");
  var campo_categoria = document.getElementById("categoria");
  var categoria = campo_categoria.value;
  var busqueda = identificador.value;

  var parametros = {
    categoria: categoria,
    busqueda: busqueda,
  };

  $.ajax({
    data: parametros,
    url: "controlar-identificador.php",
    type: "POST",

    beforesend: function () {
      //$('#mostrar_mensaje').html("Mensaje antes de Enviar");
    },

    success: function (mensaje) {
      // alert (mensaje);
      if (mensaje != "OK") {
        Swal.fire(
          "Error",
          // mensaje,
          "El identificador " + busqueda + " ya está en uso",
          "error"
        );
        setTimeout(function () {
          Swal.close();
          asignarIdentificador();
          
        }, 2000);
      }
    },
  });
}

function controlarCategoria(categoria) {
  var parametros = {
    categoria: categoria,
  };

  if (!validarNombreCarpeta(categoria)) {
    console.log("El nombre contiene caracteres no permitidos para una carpeta");
    Swal.fire(
      "Error",
      // mensaje,
      "El nombre contiene caracteres no permitidos para una carpeta",
      "error"
    );
    setTimeout(function () {
      Swal.close();
      limpiar("categoria");
    }, 3000);
  }


  $.ajax({
    data: parametros,
    url: "controlar-categoria.php",
    type: "POST",

    beforesend: function () {
      //$('#mostrar_mensaje').html("Mensaje antes de Enviar");
    },

    success: function (mensaje) {
      // alert (mensaje);
      if (mensaje != "OK") {
        Swal.fire(
          "Error",
          // mensaje,
          "La carpeta  " + categoria + " ya está en uso",
          "error"
        );
        setTimeout(function () {
          Swal.close();
          limpiar("categoria");
        }, 2000);
      }
    },
  });
}

function limpiar(campo) {
  document.getElementById(campo).value = "";
}

function mayusc(string) {
  return string.toUpperCase();
}

function minusc(string) {
  return string.toLowerCase();
}

function validarNombreCarpeta(nombre) {
  // Opción 1: Verificar caracteres seguros (enfoque de lista blanca)
  // Solo permite letras, números, guiones y guiones bajos
  const regexSeguro = /^[a-zA-Z0-9_-]+$/;
  return regexSeguro.test(nombre);
}