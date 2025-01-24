$( document ).ready(function () {
            var clsDropTargetSwap = $('.drop-target-swap');
            var clsDropTarget = $('.drop-target');
            var clsDragable = $('.dragable');  
            var new_fields_count = 0;
            
            var klientDisplayFields = $('.klientDisplayFields');
            klientDisplayFields.on('change', function(){
                changeDisplayKlient();
            }) ; 
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

            $( document ).ready(function() {
                changeDisplayKlient();
            });
            $('#new_field_create').on('click', function (event){
                new_fields_count ++;
                var text_new_field = $('#new_field_text').val();
                var new_field = "<div id=\"person-new-" + new_fields_count + "\" draggable=\"true\" class=\"new_field dragable\">" + text_new_field + "<input type=\"hidden\" name=\"text_-" + new_fields_count + "\" value=\"" + text_new_field + "\" /><input type=\"hidden\"  class=\"klient_id\"  name=\"id_klient_0\" value=\"-" + new_fields_count + "\" /></div>";
                var targetEl = $('#return_field');
                targetEl.after(new_field);
                $("#person-new-" + new_fields_count).on('dragstart', function (event) {
                    dragstart(event);
                });                
            });

            /**
             * check all fields of KlientDisplay and show only the checked ones
             */
            function changeDisplayKlient() {
                if($("#zobrazit_id_klient").is(':checked')){
                    $(".display_id_klient").show();
                }else{
                    $(".display_id_klient").hide();
                }
                
                if($("#zobrazit_id_objednavka").is(':checked')){
                    $(".display_id_objednavka").show();
                }else{
                    $(".display_id_objednavka").hide();
                }
                
                if($("#zobrazit_nazev").is(':checked')){
                    $(".display_nazev").show();
                }else{
                    $(".display_nazev").hide();
                }
                
                if($("#zobrazit_odjezd").is(':checked')){
                    $(".display_odjezd").show();
                }else{
                    $(".display_odjezd").hide();
                }
            }

            /**
             * On dragstart event store id of element being dragged to event data storage
             * @param event
             */
            function dragstart(event) {
                event.originalEvent.dataTransfer.setData("text", event.target.id);
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

                var id = event.originalEvent.dataTransfer.getData("text");
                var draggedElement = $('#' + id);
                if(targetEl.attr('class').indexOf("drop-target-swap")>=0){
                    swap = true;
                }else{
                    swap = false;
                }                                  
                var draggedParent = draggedElement.parent();  
                var actualPolozka = targetEl.find(".polozka_id").val();
                
                if (swap) {                    
                    var actualItem = targetEl.children().last();                       
                    var dragedPolozka = draggedParent.find(".polozka_id").val();
                    draggedParent.append(actualItem);
                    actualItem.find(".klient_id").attr("name", "id_klient_"+dragedPolozka);
                }else{
                    //neni swap ale presun, chci vyhodit swap tridu z puvodniho elementu                    
                    //vyhodim id klienta z predchoziho mista
                    var classList = draggedParent.attr('class');              
                    var newClassList = classList.replace("drop-target-swap ", "drop-target ")
                    draggedParent.attr('class', newClassList);
                }

                targetEl.append(draggedElement);
                //zmenim classu na swapovatelnou
                var classList = targetEl.attr('class');              
                var newClassList = classList.replace("drop-target ", "drop-target-swap ")
                targetEl.attr('class', newClassList);      
                targetEl.find(".klient_id").attr("name", "id_klient_"+actualPolozka);
            }
        });