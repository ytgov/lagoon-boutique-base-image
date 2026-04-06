
(function ($, Drupal, drupalSettings, once) {
//
// Pipelining function for DataTables. To be used to the `ajax` option of DataTables
//

$.fn.dataTable.pipeline = function ( opts ) {
    // Configuration options
    var conf = $.extend( {
        pages: 5,     // number of pages to cache
        url: '',      // script url
        data: null,   // function or object with parameters to send to the server
                      // matching how `ajax.data` works in DataTables
        method: 'GET' // Ajax HTTP method
    }, opts );

    // Private variables for storing the cache
    var cacheLower = -1;
    var cacheUpper = null;
    var cacheLastRequest = null;
    var cacheLastJson = null;

    return function ( request, drawCallback, settings ) {
        var ajax          = false;
        var requestStart  = request.start;
        var drawStart     = request.start;
        var requestLength = request.length;
        var requestEnd    = requestStart + requestLength;
        if ( settings.clearCache ) {
            // API requested that the cache be cleared
            ajax = true;
            settings.clearCache = false;
        }
        else if ( cacheLower < 0 || requestStart < cacheLower || requestEnd > cacheUpper ) {
            // outside cached data - need to make a request
            ajax = true;
        }
        else if ( JSON.stringify( request.order )   !== JSON.stringify( cacheLastRequest.order ) ||
                  JSON.stringify( request.columns ) !== JSON.stringify( cacheLastRequest.columns ) ||
                  JSON.stringify( request.search )  !== JSON.stringify( cacheLastRequest.search )
        ) {
            // properties changed (ordering, columns, searching)
            ajax = true;
        }

        // Store the request for checking next time around
        cacheLastRequest = $.extend( true, {}, request );

        if ( ajax ) {
            // Need data from the server
            if ( requestStart < cacheLower ) {
                requestStart = requestStart - (requestLength*(conf.pages-1));
 
                if ( requestStart < 0 ) {
                    requestStart = 0;
                }
            }

            cacheLower = requestStart;
            cacheUpper = requestStart + (requestLength * conf.pages);

            request.start = requestStart;
            request.length = requestLength*conf.pages;

            // Provide the same `data` options as DataTables.
            if ( typeof conf.data === 'function' ) {
                // As a function it is executed with the data object as an arg
                // for manipulation. If an object is returned, it is used as the
                // data object to submit
                var d = conf.data( request );
                if ( d ) {
                    $.extend( request, d );
                }
            }
            else if ( $.isPlainObject( conf.data ) ) {
                // As an object, the data given extends the default
                $.extend( request, conf.data );
            }

            return $.ajax( {
                "type":     conf.method,
                "url":      conf.url,
                "data":     request,
                "dataType": "json",
                "cache":    false,
                "cors": false,
                "crossDomain": true,
                "headers": {'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*'},
                "success":  function ( json ) {
                    json = JSON.parse(json);
                    cacheLastJson = $.extend(true, {}, json);
                    if ( cacheLower != drawStart ) {
                        json.data.splice( 0, drawStart-cacheLower );
                    }
                    if ( requestLength >= -1 ) {
                        json.data.splice( requestLength, json.data.length );
                    }
                    drawCallback( json );
                }
            } );
        }
        else {
            json = $.extend( true, {}, cacheLastJson );
            json.draw = request.draw; // Update the echo for each response
            json.data.splice( 0, requestStart-cacheLower );
            json.data.splice( requestLength, json.data.length );

            drawCallback(json);
        }
    }
};


// Register an API method that will empty the pipelined data, forcing an Ajax
// fetch on the next draw (i.e. `table.clearPipeline().draw()`)
$.fn.dataTable.Api.register( 'clearPipeline()', function () {
    return this.iterator( 'table', function ( settings ) {
        settings.clearCache = true;
    } );
} );


  var varsToPatch;
	var sid, webformId,flagToModify,date,count, status_version,current_count, current_language;

    Drupal.behaviors.customTable = {
        attach: function (context, settings) {
            current_language = settings.path.currentLanguage;
            var sort_column = drupalSettings.biz_block_plugin.sort_column ?  drupalSettings.biz_block_plugin.sort_column : 0 ;
            var sort_order = drupalSettings.biz_block_plugin.sort_order ? drupalSettings.biz_block_plugin.sort_order : 'desc' ;

            if(drupalSettings.biz_block_plugin.url_api == ''){
              const tableId = '#' + drupalSettings.biz_block_plugin.id;

              // 1. Use the new once() syntax: once(id, selector, context)
              // 2. Wrap the result in $() to use jQuery methods like .on() and .DataTable()
              $(once('dataTable', tableId, context)).each(function () {
                const $table = $(this);
        
                $table
                  .on('order.dt', function () {
                    addEvent2Confirm(context, settings);
                  })
                  .on('page.dt', function () {
                    addEvent2Confirm(context, settings);
                  })
                  .DataTable({
                    "pagingType": "full_numbers",
                    "searching": false,
                    "order": [[ sort_column, sort_order ]],
                  });
              });

             const selector = '#' + drupalSettings.biz_block_plugin.id;

              // 2. Use the new once(id, selector, context) syntax
              // 3. Wrap the result in $() and use .each() to apply your events
              $(once('dataTable', selector, context)).each(function () {
                $(this)
                  .on('order.dt', function () { 
                    addEvent2Confirm(context, settings); 
                  })
                  .on('page.dt', function () { 
                    addEvent2Confirm(context, settings); 
                  });
              });

            }
            else{
              $('#'+drupalSettings.biz_block_plugin.id).once(drupalSettings.biz_block_plugin.id).each(function () {
                if(drupalSettings.biz_block_plugin.add_html_top !== ''){
                  $('#'+drupalSettings.biz_block_plugin.id).closest( "section" ).prepend( drupalSettings.biz_block_plugin.add_html_top );
                }
                $('#'+drupalSettings.biz_block_plugin.id).DataTable({
                    "ordering": false,
                    "pagingType": "full_numbers",
                    "retrieve": true,
                    "processing": true,
                    "serverSide": true,
                    "searching": false,
                    "ajax": $.fn.dataTable.pipeline( {
                      url: drupalSettings.biz_block_plugin.url_api,
                      pages: 5 // number of pages to cache
                  } )
                });
              });
            }
            const tableSelector = '#' + drupalSettings.biz_block_plugin.id + ' tbody';

              // 2. Use the new once(id, selector, context) syntax
              $(once('compare-version', tableSelector, context)).each(function () {
                // 'this' inside .each() is the native DOM element
                $(this).on('click', '.compare-versions', function () {
                  const varsToGetRevision = $(this).children("div").attr("data");
                  
                  // Use 'let' or 'const' for your variables to avoid global scope leaks
                  let [date, sid, count, status_version, current_count] = varsToGetRevision.split('&');
                  date = date.replaceAll("/", "-");
        
                  if ($("#compare-modal-" + date).length) {
                    $("#compare-modal-" + date).modal('show');
                  } else {
                    getVersions();
                  }
                });
              });
        }
    };
    function addEvent2Confirm(context, settings){
        setTimeout(function(){
            $(".delete-action, .accept-action").off();
        		$(".delete-action, .accept-action").on("click", function(e){
        		    varsToPatch = $(this).children("div").attr("data");
        		    [sid, webformId,flagToModify] = varsToPatch.split('&');
        		    $("#confirmModal").modal('show');
        		});

        		$("#modal-btn-yes").on("click", function(){
            		switch(flagToModify){
                		case "Active":
                            patchNode({ "frontend_status": flagToModify, "status": flagToModify, 'user_uid': settings.user.uid });
                		break;
                		default:
                            patchNode({ "deleted": true });
                		break;
            		}
        		});
        		$("#modal-btn-no").on("click", function(){
        		    $("#confirmModal").modal('hide');
        		});
        }, 100);
    }
    function patchNode(data) {
        $.ajax({
            url:    '/patch/' + webformId    + '/' + sid + '/' + JSON.stringify(data),
            method: 'PATCH',
            cors: false,
            headers: {'Content-Type': 'application/json'},
            success: function (data) {
    	        $("#confirmModal").modal('hide');
    	        location.reload(true);
            }
        });
    }
    function getVersions(){
      date = date.replaceAll("/", "-");
       $.ajax({
            url: '/' + current_language + '/get-version/'  + sid + '/'+ date + '/'+ count + '/'+current_count,
            method: 'GET',
            cors: false,
            headers: {'Content-Type': 'application/json'},
            success: function (data) {
              $('body').append(data);
        		  $("#compare-modal-"+date).modal('show');
            }
        });
    }
})(jQuery, Drupal, drupalSettings, once);