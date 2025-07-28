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

  $('#check_dni').on('click', function(){
      $('#estado_envio_verifactu').hide();
        $.ajax({ 
            type: 'POST', 
            cache: false, 
            dataType: 'json', 
            url: urladmin, 
            data: { 
              ajax: true, 
              action: 'CheckDNI',
              //id_comercial: $('#comercial').val(),
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

	//Para actualizar el comercial con el selector
    $('#send_verifactu').on('click', function(){
    	$('#estado_envio_verifactu').hide();
        $.ajax({ 
            type: 'POST', 
            cache: false, 
            dataType: 'json', 
            url: urladmin, 
            data: { 
              ajax: true, 
              action: 'EnviarVerifactu',
              //id_comercial: $('#comercial').val(),
              id_order: id_order
            }, 
              success : function (data) { 
                  //obj = JSON.parse(data);
                  obj = data;
                  if (obj.EstadoRegistro == 'Correcto')
                  {
                    $('#estado_envio_verifactu').removeClass('alert-danger');
                    $('#estado_envio_verifactu').addClass('alert-success');
                    $('#estado_envio_verifactu .alert-text').html('Registro de facturación de alta enviado a verifactu correctamente');
                    $('#estado_envio_verifactu').fadeIn('slow').delay(1000).fadeOut(function() {
                       //window.location.reload();
                    });
                  }
                  else if (obj.EstadoRegistro == 'AceptadoConErrores')
                  {
                    $('#estado_envio_verifactu').removeClass('alert-success'); //alert-info, alert-warning
                    $('#estado_envio_verifactu').addClass('alert-warning');
                    $('#estado_envio_verifactu .alert-text').html(obj.DescripcionErrorRegistro + ' (' + obj.CodigoErrorRegistro   + ')');
                    $('#estado_envio_verifactu').fadeIn('slow').delay(1500).fadeOut(function() {
                       //window.location.reload();
                    });
                  }
                  else
                  {
                    $('#estado_envio_verifactu').removeClass('alert-success'); //alert-info, alert-warning
                    if (obj.CodigoErrorRegistro >= 3000 && obj.CodigoErrorRegistro < 4000)
                    {
                      $('#estado_envio_verifactu').addClass('alert-info');
                    }
                    else
                    {
                      $('#estado_envio_verifactu').addClass('alert-danger');
                    }
                    
                    if (obj.EstadoRegistro)
                    {
                      $('#estado_envio_verifactu .alert-text').html(obj.DescripcionErrorRegistro + ' (' + obj.CodigoErrorRegistro   + ')');
                      $('#estado_envio_verifactu').fadeIn('slow').delay(3000).fadeOut(function() {
                        //window.location.reload();
                      });
                    }
                    else //Es un error sql o genérico
                    {
                      $('#estado_envio_verifactu .alert-text').html(obj.error);
                      $('#estado_envio_verifactu').fadeIn('slow');
                    }
                    
                  }

                  
                  
              }, 
              error : function (data){ 
              console.log(data); 
              } 
        });
    });


    $('#send_anulacion_verifactu').on('click', function(){
      $('#estado_envio_verifactu').hide();
        $.ajax({ 
            type: 'POST', 
            cache: false, 
            dataType: 'json', 
            url: urladmin, 
            data: { 
              ajax: true, 
              action: 'AnularVerifactu',
              //id_comercial: $('#comercial').val(),
              id_order: id_order
            }, 
              success : function (data) { 
                  //obj = JSON.parse(data);
                  obj = data;
                  //$('#estado_envio_verifactu .alert-text').html(data);
                  if (obj.EstadoRegistro == 'Correcto')
                  {
                    $('#estado_envio_verifactu').removeClass('alert-danger');
                    $('#estado_envio_verifactu').addClass('alert-success');
                    $('#estado_envio_verifactu .alert-text').html('Registro de anulación enviado a verifactu correctamente');
                    $('#estado_envio_verifactu').fadeIn('slow')/*.delay(1000).fadeOut(function() {
                       window.location.reload();
                    })*/;
                  }
                  else if (obj.EstadoRegistro == 'AceptadoConErrores')
                  {
                    $('#estado_envio_verifactu').removeClass('alert-success'); //alert-info, alert-warning
                    $('#estado_envio_verifactu').addClass('alert-warning');
                    $('#estado_envio_verifactu .alert-text').html(obj.DescripcionErrorRegistro + ' (' + obj.CodigoErrorRegistro   + ')');
                    $('#estado_envio_verifactu').fadeIn('slow')/*.delay(1500).fadeOut(function() {
                       window.location.reload();
                    })*/;
                  }
                  else
                  {
                    $('#estado_envio_verifactu').removeClass('alert-success'); //alert-info, alert-warning
                    if (obj.CodigoErrorRegistro >= 3000 && obj.CodigoErrorRegistro < 4000)
                    {
                      $('#estado_envio_verifactu').addClass('alert-info');
                    }
                    else
                    {
                      $('#estado_envio_verifactu').addClass('alert-danger');
                    }
                    
                    if (obj.EstadoRegistro)
                    {
                      $('#estado_envio_verifactu .alert-text').html(obj.DescripcionErrorRegistro + ' (' + obj.CodigoErrorRegistro   + ')');
                      $('#estado_envio_verifactu').fadeIn('slow')/*.delay(3000).fadeOut(function() {
                        window.location.reload();
                      })*/;
                    }
                    else //Es un error sql o genérico
                    {
                      $('#estado_envio_verifactu .alert-text').html(obj.error);
                      $('#estado_envio_verifactu').fadeIn('slow');
                    }
                    
                  }

                  
                  
              }, 
              error : function (data){ 
              console.log(data); 
              } 
        });
    });
});