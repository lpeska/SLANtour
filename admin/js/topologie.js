   var gridster;
   var count = 0;
   var buttons_seat = '<a class="writeText" href="#" title="Napsat poznámku"><img src="/admin/img/write.gif" alt="Napsat poznámku"/></a><a class="makenoseat" href="#" title="Není sedadlo"><img src="/admin/img/noSeat.gif" alt="toto není sedadlo"/></a><a class="remove" href="#" title="Smazat buòku" style="color:red;font-weight:bold;"><img src="/admin/img/delete.gif" alt="Smazat buòku"/></a>';
   var buttons_noseat = '<a class="writeText" href="#" title="Napsat poznámku"><img src="/admin/img/write.gif" alt="Napsat poznámku"/></a><a class="makeseat" href="#" title="Vytvoøit sedadlo"><img src="/admin/img/makeseat.gif" alt="toto není sedadlo"/></a><a class="remove" href="#" title="Smazat buòku" style="color:red;font-weight:bold;"><img src="/admin/img/delete.gif" alt="Smazat buòku"/></a>';
   
   
function create_gridster_zasedaci_poradek(){
    gridster = $(".gridster > ul").gridster({
          widget_margins: [0, 0],
          widget_base_dimensions: [100, 80],
          helper: 'clone',
          resize: {
            enabled: true
          },
          draggable: {
            stop: function(e, ui, $widget) {
              recalculate_seat_numbers();
            }
          },
          serialize_params: function($w, wgd) { 
            return { 
            classes: $($w).attr('class'), 
            col: wgd.col, 
            row: wgd.row, 
            size_x: wgd.size_x, 
            size_y: wgd.size_y, 
            htmlContent : $($w).find(".text").html() 
         };
         }
      }).data('gridster');
    $(document).on( "click", ".gridster ul li a.remove", function() {
        $(this).parent().parent().addClass("activ");
        gridster.remove_widget($('.activ'));
        recalculate_seat_numbers();
        return false;
    });  
    $(document).on( "click", ".gridster ul li a.makenoseat", function() {
        $(this).parent().parent().addClass("noseat");
        $(this).parent().parent().find(".count").html("");
        $(this).parent().parent().find(".buttons").html(buttons_noseat);
        recalculate_seat_numbers();
        return false;
    }); 
    $(document).on( "click", ".gridster ul li a.makeseat", function() {
        $(this).parent().parent().removeClass("noseat");
        $(this).parent().parent().find(".count").html(count);
        $(this).parent().parent().find(".buttons").html(buttons_seat);   
        recalculate_seat_numbers();
        return false;
    });             
    $(document).on( "click", ".gridster ul li a.writeText", function() {
        var textElem = $(this).parent().parent().find(".text");
        var text = textElem.html();
        var input = '<input class="textInput" type="text" size="8" value="'+text+'"/><input type="button" class="textButton" value="&gt;"/>';
        textElem.html(input);
        return false;
    }); 
    $(document).on( "click", ".gridster ul li input.textButton", function() {
        var textElem = $(this).parent();
        var text = textElem.find(".textInput").val();
        textElem.html(text);
        return false;
    });     
    $(document).on( "click", ".ulozit_topologii", function() {
        recalculate_seat_numbers();
        var s = JSON.stringify(gridster.serialize());
        $("#serialized_grid").val(s);     
    });       
}      
   
   
function create_gridster(){
    var add_wig = "<a href=\"#\" onclick=\"add_single_widget();\">Pøidej místo</a>";
    document.getElementById("addSingleWidget").innerHTML = add_wig;
      gridster = $(".gridster > ul").gridster({
          widget_margins: [0, 0],
          widget_base_dimensions: [100, 80],
          helper: 'clone',
          resize: {
            enabled: true
          },
          draggable: {
            stop: function(e, ui, $widget) {
              recalculate_seat_numbers();
            }
          },
          serialize_params: function($w, wgd) { 
            return { 
            classes: $($w).attr('class'), 
            col: wgd.col, 
            row: wgd.row, 
            size_x: wgd.size_x, 
            size_y: wgd.size_y, 
            htmlContent : $($w).find(".text").html() 
         };
         }
      }).data('gridster');
    $(document).on( "click", ".gridster ul li a.remove", function() {
        $(this).parent().parent().addClass("activ");
        gridster.remove_widget($('.activ'));
        recalculate_seat_numbers();
        return false;
    });  
    $(document).on( "click", ".gridster ul li a.makenoseat", function() {
        $(this).parent().parent().addClass("noseat");
        $(this).parent().parent().find(".count").html("");
        $(this).parent().parent().find(".buttons").html(buttons_noseat);
        recalculate_seat_numbers();
        return false;
    }); 
    $(document).on( "click", ".gridster ul li a.makeseat", function() {
        $(this).parent().parent().removeClass("noseat");
        $(this).parent().parent().find(".count").html(count);
        $(this).parent().parent().find(".buttons").html(buttons_seat);   
        recalculate_seat_numbers();
        return false;
    });             
    $(document).on( "click", ".gridster ul li a.writeText", function() {
        var textElem = $(this).parent().parent().find(".text");
        var text = textElem.html();
        var input = '<input class="textInput" type="text" size="8" value="'+text+'"/><input type="button" class="textButton" value="&gt;"/>';
        textElem.html(input);
        return false;
    }); 
    $(document).on( "click", ".gridster ul li input.textButton", function() {
        var textElem = $(this).parent();
        var text = textElem.find(".textInput").val();
        textElem.html(text);
        return false;
    });     
    $(document).on( "click", ".ulozit_topologii", function() {
        recalculate_seat_numbers();
        var s = JSON.stringify(gridster.serialize());
        $("#serialized_grid").val(s);     
    });       
}   

function create_grid() {
    var x = document.getElementById("grid_x").value;
    var y = document.getElementById("grid_y").value;      

    create_gridster();
                
      for(var j=1;  j<=y; j++){
        for(var i=1; i<=x; i++){          
              count++;
              var widget = ['<li data-row="'+j+'" data-col="'+i+'"><span class="count">'+count+'</span><div class="buttons" style="float:right;">'+buttons_seat+'</div><div class="text" style="margin-top:20px;"></div></li>', 1, 1,i,j];
              gridster.add_widget.apply(gridster, widget);
          }
      }
      
     
}

function recalculate_seat_numbers(){
    count=0;
    var list = $(".gridster ul li");
    list.sort(
            function(x,y){
                if(parseInt($(x).attr("data-row"),10) > parseInt($(y).attr("data-row"),10)){
                    return 1;
                }else if(parseInt($(x).attr("data-row"),10) < parseInt($(y).attr("data-row"),10)){
                    return -1;
                }else{
                    if(parseInt($(x).attr("data-col"),10) > parseInt($(y).attr("data-col"),10)){
                        return 1;
                    }else {
                        return -1;
                    }
                }
            });
     list.each(
         function(){
             var classes = $(this).attr("class");
             if(classes.indexOf("noseat")>=0){
                 // tady sedadla nepocitame
                 $(this).find(".count").html("");
             }else{
                 count++;
                 $(this).find(".count").html(count);
             }             
         }   
    );
    
}

function add_single_widget() {
        var i=1;
        var j=1;
        count++;
             var widget = ['<li data-row="'+j+'" data-col="'+i+'"><span class="count">'+count+'</span><div class="buttons" style="float:right;">'+buttons_seat+'</div><div class="text" style="margin-top:20px;"></div></li>', 1, 1,i,j];
         gridster.add_widget.apply(gridster, widget);  
         recalculate_seat_numbers();
        return false;
}


/*swaping users - zasedaci poradek*/
$(function () {
            var clsDropTargetSwap = $('.drop-target-swap');
            var clsDropTarget = $('.drop-target');
            var clsDragable = $('.dragable');

            clsDragable.on('dragstart', function (event) {
                dragstart(event);
            });

            clsDropTargetSwap.on('dragover', function (event) {
                dragover(event);
            });
            clsDropTargetSwap.on('drop', function (event) {
                drop(event, $(this), true);
            });

            clsDropTarget.on('dragover', function (event) {
                dragover(event);
            });
            clsDropTarget.on('drop', function (event) {
                drop(event, $(this), false);
            });

            /**
             * On dragstart event store id of element being dragged to event data storage
             * @param event
             */
            function dragstart(event) {
                event.originalEvent.dataTransfer.setData("id", event.target.id);
            }

            /**
             * Prevent default behaviour (enable dragging over) when something is dragged over element.
             * @param event
             */
            function dragover(event) {
                event.preventDefault();
            }

            /**
             * On drop event get stored id of dragged element and add it to drop target as a child. Swap actual child for dragged one if there is already some in drop target element.
             * @param event
             * @param targetEl jQuery target element of drag action
             * @param swap bool should dragged element be swapped with actual child
             */
            function drop(event, targetEl, swap) {
                event.preventDefault();

                var id = event.originalEvent.dataTransfer.getData("id");
                var draggedElement = $('#' + id);

                if (swap) {
                    var actualItem = targetEl.children().get(0);
                    draggedElement.parent().append(actualItem);
                }

                targetEl.append(draggedElement);
            }
        });
                               