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

/* =========================================================================
 * Helper: show a notification banner above the page content
 * ========================================================================= */
function vfShowNotification(type, message) {
    var cssClass = (type === 'success') ? 'alert-success' : 'alert-danger';
    var $n = $('<div class="alert ' + cssClass + ' vf-fe-notify" style="margin:10px 0">' + message + '</div>');
    // Remove any existing notification to avoid duplicates
    $('.vf-fe-notify').remove();
    // Try the same selectors the original inline JS used, in priority order
    if ($('.page-head').length) {
        $('.page-head').after($n);
    } else if ($('.page-header').length) {
        $('.page-header').after($n);
    } else if ($('#main-div').length) {
        $('#main-div').prepend($n);
    } else if ($('#content').length) {
        $('#content').prepend($n);
    } else {
        $('body').prepend($n);
    }
    setTimeout(function () { $n.fadeOut('slow', function () { $n.remove(); }); }, 7000);
}

$( document ).ready(function() {
  console.log('[VeriFactu] back.js cargado y document.ready ejecutado.');

/* =========================================================================
 * Handlers that require verifactu_ajax_url (order detail page, etc.)
 * ========================================================================= */
if (typeof verifactu_ajax_url !== 'undefined')
{

  // Función para llamar al controlador Ajax.
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
              // Disabled automatic reload — managed by PS cron hooks
          },
          error: function(xhr) {
              console.error("VeriFactu: Error en la llamada Ajax.", xhr.responseText);
          }
      });
  }

  $('#check_dni').on('click', function(){
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
                obj = data;
                if (!obj.error)
                {
                  if (obj.result == 'true')
                  {
                    $('#estado_envio_verifactu').removeClass('alert-danger');
                    $('#estado_envio_verifactu').addClass('alert-success');
                    $('#estado_envio_verifactu .alert-text').html('El DNI/NIF es correcto y se encuentra registrado correctamente en el censo de la AEAT');
                    $('#estado_envio_verifactu').fadeIn('slow');
                  }
                  else
                  {
                    $('#estado_envio_verifactu').removeClass('alert-success');
                    $('#estado_envio_verifactu').addClass('alert-danger');
                    $('#estado_envio_verifactu .alert-text').html('El DNI/NIF es incorrecto o no se encuentra registrado en el censo de la AEAT con este Nombre de cliente / Empresa');
                    $('#estado_envio_verifactu').fadeIn('slow');
                  }
                }
                else
                {
                  $('#estado_envio_verifactu').removeClass('alert-success');
                  $('#estado_envio_verifactu').addClass('alert-danger');
                  $('#estado_envio_verifactu .alert-text').html(obj.error);
                  $('#estado_envio_verifactu').fadeIn('slow');
                }

            },
            error : function (data){
            console.log(data);
            }
      });
  });


  $('#send_verifactu').on('click', function(){
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
                  $('#estado_envio_verifactu').removeClass('alert-success');
                  $('#estado_envio_verifactu').addClass('alert-warning');
                  $('#estado_envio_verifactu .alert-text').html('El registro de facturación está pendiente de respuesta');
                  $('#estado_envio_verifactu').fadeIn('slow').delay(1000).fadeOut(function() {
                     window.location.reload();
                  });
                }
                else
                {
                  if (obj.error) error = obj.error;
                  else error = 'Error enviando el registro a la API.<br>Vuelve a intentarlo más tarde...';
                  $('#estado_envio_verifactu').removeClass('alert-success');
                  $('#estado_envio_verifactu').addClass('alert-danger');
                  $('#estado_envio_verifactu .alert-text').html(error);
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

  // Listado de facturas y facturas de abono — reenvío
  $('.button-resend-verifactu').on('click', function(e){
    e.preventDefault();

    $(this).prop('disabled', true).addClass('disabled');
    $(this).find('i').removeClass('icon-refresh').addClass('icon-spinner icon-spin');

    $.ajax({
        type: 'POST',
        cache: false,
        dataType: 'json',
        url: verifactu_ajax_url,
        data: {
            ajax: true,
            action: 'enviarVerifactu',
            token: verifactu_token,
            id_order: $(this).data('id_order'),
            type: $(this).data('type')
        },
        success: function(data) {
            if (data.response == 'OK' || data.response == 'pendiente') {
                showSuccessMessage('Reenvío solicitado correctamente. La página se recargará para actualizar el estado.');
                setTimeout(function() {
                    location.reload();
                }, 2500);
            } else {
                showErrorMessage('Error en el reenvío: ' + (data.error || 'Respuesta desconocida del servidor.'));
                $(this).prop('disabled', false).removeClass('disabled');
                $(this).find('i').removeClass('icon-spinner icon-spin').addClass('icon-refresh');
            }
        },
        error: function() {
            showErrorMessage('Error de comunicación con el servidor al intentar reenviar.');
            $(this).prop('disabled', false).removeClass('disabled');
            $(this).find('i').removeClass('icon-spinner icon-spin').addClass('icon-refresh');
        }
    });
  });

  $('#send_anulacion_verifactu').on('click', function(e) {
    e.preventDefault();

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
        if (result.isConfirmed) {
            $('#send_anulacion_verifactu').prop('disabled', true);
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
                    if (obj.response == 'OK')
                    {
                      $('#estado_envio_verifactu').removeClass('alert-danger alert-warning').addClass('alert-success');
                      $('#estado_envio_verifactu .alert-text').html('Registro de anulación enviado correctamente.<br>En espera de respuesta de VeriFactu...');
                    }
                    else if (obj.response == 'pendiente')
                    {
                      $('#estado_envio_verifactu').removeClass('alert-danger alert-success').addClass('alert-warning');
                      $('#estado_envio_verifactu .alert-text').html('El registro de anulación ya está pendiente de respuesta.');
                    }
                    else
                    {
                      if (obj.error) error = obj.error;
                      else error = 'Error enviando el registro a la API.<br>Vuelve a intentarlo más tarde...';
                      $('#estado_envio_verifactu').removeClass('alert-success alert-warning').addClass('alert-danger');
                      $('#estado_envio_verifactu .alert-text').html(error);
                    }

                    $('#estado_envio_verifactu').fadeIn('slow').delay(2000).fadeOut(function() {
                        window.location.reload();
                    });
                },
                error: function (data) {
                    console.log(data);
                    Swal.fire('¡Error!', 'No se pudo comunicar con el servidor.', 'error');
                }
            });
          }
        });
    });

  $(document).on('click', '#check_api_status', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Comprobando estado...',
            text: 'Realizando consulta al servicio de la AEAT.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: verifactu_ajax_url + '&action=checkStatus&ajax=1',
            type: 'POST',
            dataType: 'json',
            data: {
                token: verifactu_token,
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({ title: '¡Servicio Operativo!', text: response.message, icon: 'success' });
                } else {
                    Swal.fire({ title: 'Error en el servicio Verifactu de la AEAT', text: response.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'Error de Comunicación', text: 'No se pudo contactar con el servidor para verificar el estado.', icon: 'error' });
            }
        });
    });

  $('#table-verifactu_reg_fact').on('click', '.verifactu-row-clickable', function() {
        var rowId = $(this).data('row-id');
        $('#details-' + rowId).slideToggle();
        $(this).toggleClass('row-expanded');
    });

} // end if (typeof verifactu_ajax_url !== 'undefined')

/* =========================================================================
 * Generar Factura Electrónica — registrar solo UNA VEZ aunque back.js
 * sea cargado múltiples veces por PrestaShop.
 * ========================================================================= */
if (!window.vfFeHandlersRegistered) {
    window.vfFeHandlersRegistered = true;

    /* --- Facturas de venta --- */
    $(document).on('click', '.btn-generar-fe', function(e) {
        e.preventDefault();
        console.log('[VeriFactu] Click en btn-generar-fe, id_order_invoice=', $(this).data('id_order_invoice'));
        var $btn      = $(this);
        var idInvoice = $btn.data('id_order_invoice');
        var ajaxUrl   = (typeof verifactu_ajax_url !== 'undefined') ? verifactu_ajax_url : '';
        var ajaxToken = (typeof verifactu_token !== 'undefined')    ? verifactu_token    : '';
        if (!ajaxUrl) { console.error('VeriFactu: verifactu_ajax_url no definido.'); return; }
        $btn.prop('disabled', true).find('i').attr('class', 'icon-spinner icon-spin');
        $.post(ajaxUrl, {
            ajax: 1,
            action: 'GenerarFacturae',
            id_order_invoice: idInvoice,
            token: ajaxToken
        }, function(resp) {
            $btn.prop('disabled', false).find('i').attr('class', 'icon-file-text');
            if (resp && resp.success) {
                vfShowNotification('success', 'Factura electrónica generada correctamente. Puedes descargarla desde la pestaña «Facturas Electrónicas».');
            } else {
                var errMsg = (resp && resp.error) ? resp.error : 'Error desconocido';
                vfShowNotification('error', 'Error al generar la factura electrónica: ' + errMsg);
            }
        }, 'json').fail(function(xhr) {
            $btn.prop('disabled', false).find('i').attr('class', 'icon-file-text');
            vfShowNotification('error', 'Error de conexión al generar la factura electrónica. (' + xhr.status + ')');
        });
    });

    /* --- Facturas de abono --- */
    $(document).on('click', '.btn-generar-fe-slip', function(e) {
        e.preventDefault();
        console.log('[VeriFactu] Click en btn-generar-fe-slip, id_order_slip=', $(this).data('id_order_slip'));
        var $btn      = $(this);
        var idSlip    = $btn.data('id_order_slip');
        var ajaxUrl   = (typeof verifactu_ajax_url !== 'undefined') ? verifactu_ajax_url : '';
        var ajaxToken = (typeof verifactu_token !== 'undefined')    ? verifactu_token    : '';
        if (!ajaxUrl) { console.error('VeriFactu: verifactu_ajax_url no definido.'); return; }
        $btn.prop('disabled', true).find('i').attr('class', 'icon-spinner icon-spin');
        $.post(ajaxUrl, {
            ajax: 1,
            action: 'GenerarFacturaeSlip',
            id_order_slip: idSlip,
            token: ajaxToken
        }, function(resp) {
            $btn.prop('disabled', false).find('i').attr('class', 'icon-file-text');
            if (resp && resp.success) {
                vfShowNotification('success', 'Factura electrónica de abono generada correctamente. Puedes descargarla desde la pestaña «Facturas Electrónicas».');
            } else {
                var errMsg = (resp && resp.error) ? resp.error : 'Error desconocido';
                vfShowNotification('error', 'Error al generar la factura electrónica: ' + errMsg);
            }
        }, 'json').fail(function(xhr) {
            $btn.prop('disabled', false).find('i').attr('class', 'icon-file-text');
            vfShowNotification('error', 'Error de conexión al generar la factura electrónica. (' + xhr.status + ')');
        });
    });

} // end if (!window.vfFeHandlersRegistered)

}); // end document.ready
