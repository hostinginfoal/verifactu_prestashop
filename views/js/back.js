/**
* InFoAL S.L.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to hosting@infoal.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    InFoAL S.L. <hosting@infoal.com>
*  @copyright InFoAL S.L.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of InFoAL S.L.
*/

$( document ).ready(function() {

if (typeof verifactu_ajax_url !== 'undefined') {

  // Función para llamar al controlador Ajax. Se cargará cada vez que se navegue por la administración, pasados 5 segundos de la carga (por si el usuario navega rápido)
  function checkPendingStatus() 
  {
      console.log("VeriFactu: Iniciando sondeo en segundo plano...");
      $.ajax({
          url: verifactu_ajax_url,
          type: 'POST',
          dataType: 'json',
          data: {
              ajax: true,
              action: 'checkPendingStatus',
              token: verifactu_token
          },
          success: function(response) {
              /*if (response.success && response.updated > 0) {
                  // Si se actualizó algo, mostramos una notificación y recargamos
                  // la página para que el usuario vea los cambios en las tablas.
                  showSuccessMessage('Sincronización de VeriFactu completada. ' + response.message);
                  setTimeout(function() {
                      location.reload();
                  }, 2000);
              } else {
                  // Si no hay nada que actualizar, lo mostramos en la consola.
                  console.log("VeriFactu: No hay registros pendientes para actualizar.");
              }*/
          },
          error: function(xhr) {
              console.error("VeriFactu: Error en la llamada Ajax.", xhr.responseText);
          }
      });
  }

  // Ejecutamos la función una vez al cargar la página.
  checkPendingStatus();

  // ejecutamos pasados 5 segundos.
  //setTimeout(checkPendingStatus, 5000);

  // Y luego la ejecutamos cada 60 segundos.
  setInterval(checkPendingStatus, 60000);

  $('#check_dni').on('click', function(){
      // Deshabilitamos el botón INMEDIATAMENTE al hacer clic
      $(this).prop('disabled', true);
      $('#estado_envio_verifactu').hide();

        $.ajax({ 
            type: 'POST', 
            cache: false, 
            dataType: 'json', 
            url: verifactu_ajax_url,  
            data: { 
              ajax: true, 
              action: 'CheckDNI',
              token: verifactu_token,
              id_order: id_order
            }, 
              success : function (data) { 
                  //obj = JSON.parse(data);
                  obj = data;
                  if (obj.result == 'true')
                  {
                    $('#estado_envio_verifactu').removeClass('alert-danger');
                    $('#estado_envio_verifactu').addClass('alert-success');
                    $('#estado_envio_verifactu .alert-text').html('DNI Correcto');
                    $('#estado_envio_verifactu').fadeIn('slow')/*.delay(1000).fadeOut(function() {
                       window.location.reload();
                    })*/;
                  }
                  else
                  {
                    $('#estado_envio_verifactu').removeClass('alert-success'); //alert-info, alert-warning
                    $('#estado_envio_verifactu').addClass('alert-danger');
                    $('#estado_envio_verifactu .alert-text').html('DNI Incorrecto');
                    $('#estado_envio_verifactu').fadeIn('slow')/*.delay(1500).fadeOut(function() {
                       window.location.reload();
                    })*/;
                  }
                  
              }, 
              error : function (data){ 
              console.log(data); 
              } 
        });
    });

  //console.log("VeriFactu: Iniciando sondeo en segundo plano...");

	//Para actualizar el comercial con el selector
    $('#send_verifactu').on('click', function(){
      // Deshabilitamos el botón INMEDIATAMENTE al hacer clic
      $(this).prop('disabled', true);

    	$('#estado_envio_verifactu').hide();
        $.ajax({ 
            type: 'POST', 
            cache: false, 
            dataType: 'json', 
            url: verifactu_ajax_url, 
            data: { 
              ajax: true, 
              action: 'enviarVerifactu',
              token: verifactu_token,
              id_order: id_order
            }, 
              success : function (data) { 
                  //obj = JSON.parse(data);
                  obj = data;
                  if (obj.response == 'OK')
                  {
                    $('#estado_envio_verifactu').removeClass('alert-danger');
                    $('#estado_envio_verifactu').addClass('alert-success');
                    $('#estado_envio_verifactu .alert-text').html('Registro de facturación enviado correctamente.<br>En espera de respuesta verifactu...');
                    $('#estado_envio_verifactu').fadeIn('slow').delay(1000).fadeOut(function() {
                       window.location.reload();
                    });
                  }
                  else if (obj.response == 'pendiente')
                  {
                    $('#estado_envio_verifactu').removeClass('alert-success'); //alert-info, alert-warning
                    $('#estado_envio_verifactu').addClass('alert-warning');
                    $('#estado_envio_verifactu .alert-text').html('El registro de facturación está pendiente de respuesta');
                    $('#estado_envio_verifactu').fadeIn('slow').delay(1000).fadeOut(function() {
                       window.location.reload();
                    });
                  }
                  else
                  {
                    $('#estado_envio_verifactu').removeClass('alert-success'); //alert-info, alert-warning
                    $('#estado_envio_verifactu').addClass('alert-danger');
                    $('#estado_envio_verifactu .alert-text').html('Error enviando el registro.<br>Vuelve a intentarlo más tarde...');
                    $('#estado_envio_verifactu').fadeIn('slow').delay(1000).fadeOut(function() {
                       window.location.reload();
                    });
                  }

                  
                  
              }, 
              error : function (data){ 
              console.log(data); 
              } 
        });
    });


    $('#send_anulacion_verifactu').on('click', function(e) {
    e.preventDefault(); // Prevenimos la acción por defecto del botón

    // Usamos Swal (SweetAlert) para mostrar un modal de confirmación
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Vas a enviar un registro de anulación a VeriFactu. Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, enviar anulación',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        // Si el usuario hace clic en "Sí, enviar anulación"
        if (result.isConfirmed) {
            
            // Deshabilitamos el botón INMEDIATAMENTE al hacer clic
            $('#send_anulacion_verifactu').prop('disabled', true);
            // --- Aquí va tu código AJAX original ---
            $('#estado_envio_verifactu').hide();
            $.ajax({
                type: 'POST',
                cache: false,
                dataType: 'json',
                url: verifactu_ajax_url,
                data: {
                    ajax: true,
                    action: 'AnularVerifactu',
                    token: verifactu_token,
                    id_order: id_order
                },
                success: function (data) {
                    var obj = data;
                    if (obj.response == 'OK') {
                        $('#estado_envio_verifactu').removeClass('alert-danger alert-warning').addClass('alert-success');
                        $('#estado_envio_verifactu .alert-text').html('Registro de anulación enviado correctamente.<br>En espera de respuesta de VeriFactu...');
                    } else if (obj.response == 'pendiente') {
                        $('#estado_envio_verifactu').removeClass('alert-danger alert-success').addClass('alert-warning');
                        $('#estado_envio_verifactu .alert-text').html('El registro de anulación ya está pendiente de respuesta.');
                    } else {
                        $('#estado_envio_verifactu').removeClass('alert-success alert-warning').addClass('alert-danger');
                        $('#estado_envio_verifactu .alert-text').html('Error enviando el registro de anulación.<br>Vuelve a intentarlo más tarde...');
                    }

                    $('#estado_envio_verifactu').fadeIn('slow').delay(2000).fadeOut(function() {
                        window.location.reload();
                    });
                },
                error: function (data) {
                    console.log(data);
                    // Muestra un error más claro si la llamada AJAX falla
                    Swal.fire(
                        '¡Error!',
                        'No se pudo comunicar con el servidor.',
                        'error'
                    );
                }
            });
            // --- Fin del código AJAX ---
        }
    });
});

  }

});