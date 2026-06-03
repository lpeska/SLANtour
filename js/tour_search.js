//TODO: selected filters udelat pristupne zvenku = inicialni nastaveni vyhledavani
/*var selected_filters = ["txt_Lo","minPrice_100","maxPrice_20000"];*/  
var tourLocations = {};

var page = 1;

function parseSelectedFilter(filter) {
    var separatorPosition = filter.indexOf("_");
    if (separatorPosition === -1) {
        return null;
    }

    return {
        key: filter.slice(0, separatorPosition),
        value: filter.slice(separatorPosition + 1)
    };
}

function searchDateRangeToNative(value) {
    if (!value) {
        return null;
    }

    var dateRange = value.split(" > ");
    if (dateRange.length !== 2) {
        return null;
    }

    var fromDate = displayDateToNative(dateRange[0]);
    var toDate = displayDateToNative(dateRange[1]);
    if (!fromDate || !toDate) {
        return null;
    }
    if (fromDate > toDate) {
        var originalFromDate = fromDate;
        fromDate = toDate;
        toDate = originalFromDate;
    }

    return {
        from: fromDate,
        to: toDate
    };
}

function displayDateToNative(value) {
    var nativeDate = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (nativeDate) {
        return value;
    }

    var dateParts = value.match(/^(\d{2})[/.](\d{2})[/.](\d{2}|\d{4})$/);
    if (!dateParts) {
        return "";
    }

    var year = dateParts[3].length === 2 ? "20" + dateParts[3] : dateParts[3];
    return year + "-" + dateParts[2] + "-" + dateParts[1];
}

function nativeDateToSearch(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return "";
    }

    var dateParts = value.split("-");
    return dateParts[2] + "/" + dateParts[1] + "/" + dateParts[0].slice(-2);
}

function updateSearchFieldValueState($field) {
    $field.toggleClass("has-value", String($field.val() || "").trim() !== "");
}

function updateSearchFieldValueStates($fields) {
    $fields.each(function () {
        updateSearchFieldValueState($(this));
    });
}

function setSearchResultDateInputsFromRange() {
    var dateRange = searchDateRangeToNative($("#datesInput").val());
    if (!dateRange) {
        return;
    }

    $(".search-result-date-from").val(dateRange.from);
    $(".search-result-date-to").val(dateRange.to);
}

function syncSearchResultDateRange() {
    var $dateField = $("#datesInput");
    if (!$dateField.length) {
        return;
    }

    var fromDate = $(".search-result-date-from").val();
    var toDate = $(".search-result-date-to").val();

    if (!fromDate && !toDate) {
        $dateField.val("");
        return;
    }

    if (!fromDate) {
        fromDate = toDate;
    }
    if (!toDate) {
        toDate = fromDate;
    }
    if (fromDate > toDate) {
        var originalFromDate = fromDate;
        fromDate = toDate;
        toDate = originalFromDate;
    }

    $dateField.val(nativeDateToSearch(fromDate) + " > " + nativeDateToSearch(toDate));
}

async function getData(){
    
    var sz_res = await fetch('serial_zeme.json');
    var sz = await sz_res.json();
    
    var sd_res = await fetch('serial_destinace.json');
    var sd = await sd_res.json();
    
    var t_res = await fetch('tour_types.json');
    var t = await t_res.json();
    
    var k_res = await fetch('data_katalog.json');
    var k = await k_res.json();
    
    var sv_res = await fetch('sales_vol.json');
    var sv = await sv_res.json();
    /*var res = await fetch('data_group.json');*/                     
    var res = await fetch('data_group.json');
    var data = await res.json();
    data.forEach(function(elem,index,arr){
        data[index].castka = JSON.parse(data[index].castka);
        let numberArray = data[index].castka.map(Number);
        data[index].castka = numberArray;
        
        data[index].castkaMin = Math.min(...data[index].castka) /*.toString().padStart(6, '0')*/;
        data[index].id_zajezd = JSON.parse(data[index].id_zajezd);
        data[index].od = data[index].od.split(",");
        data[index].do = data[index].do.split(",");
        
        data[index].zajezdData = Array.prototype.map.call( data[index].od, function(e,i){return [e, data[index].do[i], data[index].castka[i], data[index].id_zajezd[i]];})
                        
        
        /*if (data[index].id_typ == "3" && data[index].nazev_ubytovani_web != undefined && data[index].nazev_ubytovani_web.length > 0){
            data[index].escapedName = data[index].nazev_ubytovani_web + "-" + data[index].nazev_web;                    
        }else{
            data[index].escapedName = data[index].nazev_web;
        }*/
        
        if (data[index].nazev_ubytovani == undefined){
            data[index].nazev_ubytovani = "";
        }
        let sID = parseInt(elem["id_serial"]);
        if (sz[sID]!= undefined){
            data[index].country = sz[sID]["zId"];
            data[index].zName = sz[sID]["zName"];
        }else{
            data[index].country = -1;
            data[index].zName = [""];
        }
        if (sv[sID]!= undefined){
            var popularity = parseInt(sv[sID]["pocet"]) + Math.log2(sv[sID]["suma"]/1000)
            data[index].popularity = popularity;
        }else{
            data[index].popularity = 1;
        }
        
        if (sd[sID]!= undefined){
            data[index].dId = sd[sID]["dId"]
            data[index].dName = sd[sID]["dName"];
        }else{                    
            data[index].dId = -1;
            data[index].dName = [""];
        }
        
        let tID = parseInt(elem["id_typ"]);
        if (t[tID]!= undefined){
            data[index].tName = t[tID]["nazev_typ"];
        }else{
            data[index].tName = "";
        }      
        
        if(elem["dlouhodobe_zajezdy"]=="1"){
            data[index].durNights = -1;
            data[index].durGroup = "variabilni";
        }else if(elem["od"][0] == elem["do"][0]){
            data[index].durNights = 0;
            data[index].durGroup = "jednodenni";
        }else{
            var terminOd = new Date(elem["od"][0]);
            var terminDo = new Date(elem["do"][0]);     
            var diffDays = (terminDo.getTime() - terminOd.getTime()) / (1000 * 3600 * 24);
            data[index].durNights = diffDays;
            if(diffDays < 6){
                data[index].durGroup = "1-5noci";
            }else if(diffDays < 11){
                data[index].durGroup = "6-10noci";                        
            }else{
                data[index].durGroup = "nad10noci";                        
            }
        }
        //vytvorit varianty bez diakritiky
        data[index].zNameNorm = data[index].zName.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        data[index].dNameNorm = data[index].dName.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        data[index].nazevNorm = data[index].nazev.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        data[index].nazevUbytovaniNorm = data[index].nazev_ubytovani.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        data[index].tNameNorm = data[index].tName.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        
        //let zeme_ids = defiant.search(szSnapshot, '//*[id_serial='+id_serial+']/id_zeme');
        //let destinace_ids = defiant.search(szSnapshot, '//*[id_serial='+id_serial+']/id_destinace');
        //console.log(zeme_ids);
        
    });
    //TODO: data attribution nefunguje... mozna externi akumulator? nebo spatne props?
    return data;
}

function checkFilter(el){
    if($(el).find("input").is(":checked")){
        return $(el).attr('id');
    }
    return false;
}

function pushFiltersToHistory(selected_filters) {
    let baseUrl = "/vyhledavani";
    
    // Filters that should NOT accommodate multiple values
    const singleValueFilters = new Set(["txt", "dates", "minPrice", "maxPrice"]);
    
    let params = new URLSearchParams();

    selected_filters.forEach(filter => {
        let parsedFilter = parseSelectedFilter(filter);
        if (!parsedFilter) {
            return;
        }

        // Ignore empty values (e.g. 'txt_' or 'dates_')
        if (parsedFilter.value !== undefined && parsedFilter.value !== "") {
            if (singleValueFilters.has(parsedFilter.key)) {
                params.set(parsedFilter.key, parsedFilter.value);
            } else {
                params.append(parsedFilter.key + "[]", parsedFilter.value);
            }
        }
    });

    let queryString = params.toString();
    let fullUrl = queryString ? baseUrl + "?" + queryString : baseUrl;

    // Push the new URL to the browser history without reloading the page
    history.pushState(null, "", fullUrl);
}

function updateFilters(){
    syncSearchResultDateRange();

    var selected_filters = [];
    $(".filter").each(function(idx,el){
        let r = checkFilter(el);
        if (r && !selected_filters.includes(r)) {
            selected_filters.push(r);
        }
    })
    
    selected_filters.push("txt_"+$("#autocomplete").val());
    selected_filters.push("minPrice_"+removeCurrency($("#range-min").val()));
    selected_filters.push("maxPrice_"+removeCurrency($("#range-max").val()));
    selected_filters.push("dates_"+$("#datesInput").val());
    
    console.log(selected_filters);

    pushFiltersToHistory(selected_filters);


    return selected_filters;
}


function filterZajezd(item){
    var dates = $("#datesInput").val();
    var minPrice = removeCurrency($("#range-min").val());
    var maxPrice = removeCurrency($("#range-max").val());

    var filteredZajezd = item.zajezdData
    if((typeof minPrice !== "undefined") && (typeof maxPrice !== "undefined") && parseInt(minPrice) > 0 && parseInt(maxPrice) > 0){
        let minP = parseInt(minPrice);
        let maxP = parseInt(maxPrice);
        filteredZajezd = filteredZajezd.filter((x) => x[2] >= minP && x[2] <= maxP);
        /*priceCheck = item.castka >= parseInt(filters.minPrice[0]) && item.castka <= parseInt(filters.maxPrice[0])*/


    }else if((typeof minPrice !== "undefined") && parseInt(minPrice) > 0){
        /*priceCheck = item.castka >= parseInt(filters.minPrice[0])*/

        let minP = parseInt(minPrice);
        filteredZajezd = filteredZajezd.filter((x) => x[2] >= minP);

    }else if((typeof maxPrice !== "undefined") && parseInt(maxPrice) > 0){
        /*priceCheck = item.castka <= parseInt(filters.maxPrice[0])*/                                            
        let maxP = parseInt(maxPrice);
        filteredZajezd = filteredZajezd.filter((x) => x[2] <= maxP);
    }  

    if(dates != ""){
        var dateRange = searchDateRangeToNative(dates);
        if(dateRange){
            if(item.durNights == -1){
                filteredZajezd = filteredZajezd.filter((x) => x[1] >= dateRange.from && x[0] <= dateRange.to);
                /*datesCheck = item.do >= dateRange.from && item.od <= dateRange.to;*/
            }else{
                filteredZajezd = filteredZajezd.filter((x) => x[0] >= dateRange.from && x[1] <= dateRange.to);
                /*datesCheck = item.od >= dateRange.from && item.do <= dateRange.to;*/
            }
        }
    }
    
    if(filteredZajezd.length > 0){
        return filteredZajezd[0][3];
    }else{
        return false;
    }                                   
}

                                      
async function filterData(data, selected_filters, page, resultsPerPage=20){
    var filters = {countryFilter:[],katalogFilter:[],destinaceFilter:[],tourTypeFilter:[],transportFilter:[],foodFilter:[],durGroupFilter:[],tourTheme:[],dates:[],txt:[],minPrice:[],maxPrice:[],dates:[]}
    selected_filters.forEach(f => {
        let parsedFilter = parseSelectedFilter(f);
        if(parsedFilter && filters[parsedFilter.key]){
            filters[parsedFilter.key].push(parsedFilter.value);
        }else{
            console.log(f);
        }
            
    
    });
    var classFilters = {}
    var katalogFilters = {}
    if( filters.tourTypeFilter.length >0){
        classFilters["id_typ"] = filters.tourTypeFilter;
    }
    if( filters.transportFilter.length >0){
        classFilters["doprava"] = filters.transportFilter;
    }
    if( filters.foodFilter.length >0){
        classFilters["strava"] = filters.foodFilter;
    }
    
    if( filters.durGroupFilter.length >0){
        classFilters["durGroup"] = filters.durGroupFilter;
    }
    if( filters.countryFilter.length >0){
        classFilters["country"] = filters.countryFilter;
    }   
    if( filters.katalogFilter.length >0){
        katalogFilters = filters.katalogFilter;
    }            
    
    var sortParamInput = $('input[name="listing_filter"]:checked').val();
    var sortPar = 'termin_asc';
    if(sortParamInput == "popular"){
        var sortPar = 'popularity_desc';
    }else if(sortParamInput == "cheapest"){
        var sortPar = 'price_asc';
    }else if(sortParamInput == "closest"){
        var sortPar = 'termin_asc';
    }else if(sortParamInput == "latest"){
        var sortPar = 'zajezd_id_desc';
    }
    
    var filteredData = await data
            .then(data => itemsjs(data, 
            {
                sortings: {
                  termin_asc: {
                    field: 'od',
                    order: 'asc'
                  },
                  termin_desc: {
                    field: 'od',
                    order: 'desc'
                  },
                  price_asc: {
                    field: 'castkaMin',
                    order: 'asc'
                  },
                  price_desc: {
                    field: 'castkaMin',
                    order: 'desc'
                  },     
                  zajezd_id_asc: {
                    field: 'id_zajezd',
                    order: 'asc'
                  },
                  zajezd_id_desc: {
                    field: 'id_zajezd',
                    order: 'desc'
                  },     
                  popularity_asc: {
                    field: 'popularity',
                    order: 'asc'
                  },
                  popularity_desc: {
                    field: 'popularity',
                    order: 'desc'
                  }                              
                },
                aggregations: {
                  id_typ: {
                    title: 'Typ zájezdu',
                    size: 15,
                    conjunction: false,
                    sort: "key",
                  },
                  strava: {
                    title: 'Typ stravování',
                    size: 10,
                    conjunction: false,
                    sort: "key",
                  },
                  doprava: {
                    title: 'Doprava',
                    size: 10,
                    conjunction: false,
                    sort: "key",
                  },   
                  durGroup: {
                    title: 'Délka pobytu',
                    size: 10,
                    conjunction: false,
                    sort: "key",
                  },      
                  country: {
                    title: 'Cílová země',
                    size: 200,
                    conjunction: false,
                    sort: "key",
                  },                           
                  ubytovani: {
                    title: 'Typ ubytování',
                    size: 10,
                    conjunction: false,
                    sort: "key",
                  }
                },
                searchableFields: ['nazevNorm', 'nazevUbytovaniNorm',"zNameNorm","dNameNorm","tNameNorm"]
            }))
            .then(function(ijs){ 
                
                return ijs.search(                                      
                        {
                            per_page: resultsPerPage,
                            page: page,
                            sort: sortPar,
                            query: (typeof filters.txt[0] !== "undefined")? filters.txt[0].normalize("NFD").replace(/[\u0300-\u036f]/g, ""):"",
                            filters: classFilters,
                            filter: function(item) {

                                var dates = $("#datesInput").val();
                                var priceCheck = 1;
                                var datesCheck = 1;
                                var katalogCheck = 1;
                                var filteredZajezd = item.zajezdData
                                
                                //katalog filter
                                
                                if(katalogFilters.length>0){
                                    katalogCheck = Math.max(...[katalogFilters.includes(item.id_serial),katalogFilters.includes(item.id_objektu)]);    
                                }
                                //price filter
                                if((typeof filters.minPrice[0] !== "undefined") && (typeof filters.maxPrice[0] !== "undefined") && parseInt(filters.minPrice[0]) > 0 && parseInt(filters.maxPrice[0]) > 0){
                                    let minP = parseInt(filters.minPrice[0]);
                                    let maxP = parseInt(filters.maxPrice[0]);
                                    priceCheck = item.zajezdData.some((x) => x[2] >= minP && x[2] <= maxP);
                                    filteredZajezd = filteredZajezd.filter((x) => x[2] >= minP && x[2] <= maxP);
                                    
                                    
                                }else if((typeof filters.minPrice[0] !== "undefined") && parseInt(filters.minPrice[0]) > 0){
                                    
                                    
                                    let minP = parseInt(filters.minPrice[0]);
                                    priceCheck = item.zajezdData.some((x) => x[2] >= minP);
                                    filteredZajezd = filteredZajezd.filter((x) => x[2] >= minP);
                                    
                                }else if((typeof filters.maxPrice[0] !== "undefined") && parseInt(filters.maxPrice[0]) > 0){
                                                                    
                                    let maxP = parseInt(filters.maxPrice[0]);
                                    priceCheck = item.zajezdData.some((x) => x[2] <= maxP);
                                    filteredZajezd = filteredZajezd.filter((x) => x[2] <= maxP);
                                }  
                                
                                //dates filter
                                if(dates != ""){
                                    var dateRange = searchDateRangeToNative(dates);
                                    if(dateRange){
                                        if(item.durNights == -1){
                                            datesCheck = item.zajezdData.some((x) => x[1] >= dateRange.from && x[0] <= dateRange.to);
                                            filteredZajezd = filteredZajezd.filter((x) => x[1] >= dateRange.from && x[0] <= dateRange.to);

                                        }else{
                                            datesCheck = item.zajezdData.some((x) => x[0] >= dateRange.from && x[1] <= dateRange.to);
                                            filteredZajezd = filteredZajezd.filter((x) => x[0] >= dateRange.from && x[1] <= dateRange.to);

                                        }
                                    }
                                }
                                if(filteredZajezd.length > 0 && katalogCheck){
                                    return true;
                                }else{
                                    return false;
                                }
                                
                               
                               }

                        });                                
                        
                    }
                
            );
      
    return filteredData;            
}

async function updateEnumElement(el,prefix){
    try{
        $("#"+prefix+"_"+el.key+" small").text("("+el.doc_count+")")
        if(el.selected==false){
           $("#"+prefix+"_"+el.key+" input").prop("checked",false) 
        }else{
           $("#"+prefix+"_"+el.key+" input").prop("checked",true)  
        }
        if(el.doc_count <= 0){
            if(prefix == "countryFilter"){
                $("#"+prefix+"_"+el.key).css("display","none");
            }
            $("#"+prefix+"_"+el.key).css("font-style","italic");
            $("#"+prefix+"_"+el.key).css("color","grey");
            $("#"+prefix+"_"+el.key+" input").prop("disabled",true)                    
        }else{
            if(prefix == "countryFilter"){
                $("#"+prefix+"_"+el.key).css("display","block");
            }
            $("#"+prefix+"_"+el.key).css("font-style","");
            $("#"+prefix+"_"+el.key).css("color","");
            $("#"+prefix+"_"+el.key+" input").prop("disabled",false)                    
                            
        }
    }catch(err){
        
    }
}

async function updatePrice(min,max){
try{
        updateInputs({from:min, to:max});
        instance = $range.data("ionRangeSlider");
        instance.update({
            from: min,
            to: max
        });
    }catch(err){
        console.log(err);
    }            
}    

async function updateTextSearch(text){
    try{
        $("#autocomplete").val(text)
    }catch(err){
        console.log(err);
    }            
}  

async function updateTotalTours(toursVolume, selectedFilters) {
    try {
        // Update the "toursVolume" button text
        $("#toursVolume").val("Ukázat " + toursVolume + " zájezdů");

        // Generate filter descriptions for Země and Typ
        let filterDescriptions = [];
        selectedFilters.forEach(filter => {
            let parsedFilter = parseSelectedFilter(filter);
            if (!parsedFilter) {
                return;
            }

            // Only process Země (countryFilter) and Typ (tourTypeFilter)
            if (parsedFilter.key === "countryFilter" || parsedFilter.key === "tourTypeFilter") {
                const label = getFilterLabel(parsedFilter.key, parsedFilter.value);
                if (label) {
                    filterDescriptions.push(label);
                }
            }
        });

        // Construct the header text
        const headerText = filterDescriptions.length > 0
            ? `${filterDescriptions.join(', ')} - nalezeno ${toursVolume} zájezdů`
            : `Nalezeno ${toursVolume} zájezdů`;

        // Update the "nalezenoHeader" header
        $("#nalezenoHeader").html(headerText);

        // Update the page title
        const originalTitle = "SLANtour | Vyhledávání zájezdů";
        const newTitle = filterDescriptions.length > 0
            ? `${originalTitle} | ${filterDescriptions.join(', ')}`
            : originalTitle;
        document.title = newTitle;

    } catch (err) {
        console.log(err);
    }
}

async function updateFilterWidgets(filters) {



    const widget = document.getElementById('selected-filters-widget');
    const filtersList = document.getElementById('filters-list');

    // Clear the current list to avoid duplicates
    filtersList.innerHTML = '';

    // Render each filter
    filters.forEach((filter, index) => {
        const filterItem = document.createElement('li');
        filterItem.className = 'filter-item';

        let parsedFilter = parseSelectedFilter(filter);
        if (!parsedFilter) {
            return;
        }

        var filterTypeId = parsedFilter.key;
        var filterTypeLabel = getTypeLabel(filterTypeId);
        var filterId = parsedFilter.value;
        var filterLabel = getFilterLabel(filterTypeId, filterId);
        if (!filterLabel) {
            return;
        }

        filterItem.innerHTML = `
            <span>${filterTypeLabel}</span> ${filterLabel}
            <button class="remove-filter fas fa-x" data-index="${filter}"></button>
        `;

        filtersList.appendChild(filterItem);
    });

    // Attach event listeners to the remove buttons
    filtersList.querySelectorAll('.remove-filter').forEach(button => {
        button.addEventListener('click', (event) => {
            const filterRemove = event.target.getAttribute('data-index');
            let parsedFilter = parseSelectedFilter(filterRemove);
            if (parsedFilter) {
                clearFilter(parsedFilter.key, parsedFilter.value);
            }
        });
    });
}

function getFilterLabel(typeId, filterId){
    var filterLabel = "";
    switch (typeId) {
        case "txt":
            filterLabel = filterId;
            break;
        case "minPrice":
            if (filterId != 0) {
                filterLabel = filterId + " Kč";
            }
            break;
        case "maxPrice":
            if (filterId != 100000) {
                filterLabel = filterId + " Kč";
            }
            break;
        case "dates":
            filterLabel = filterId;
            break;
        default:
            // Get only the text content of the label excluding child elements
            const textOnly =  $("#" + typeId + "_" + filterId).contents()
                .filter(function () {
                    return this.nodeType === Node.TEXT_NODE; // Keep only text nodes
                })
                .text()
                .trim(); // Remove leading/trailing whitespace
            filterLabel = textOnly;
    }
    return filterLabel;
}

function clearFilter(typeId, filterId){
    switch (typeId) {
        case "txt":
            const searchField = $("#autocomplete");
            searchField.val('');
            searchField.trigger('change');
            break;
        case "minPrice":
            const minPrice = $("#range-min");
            minPrice.val(0);
            minPrice.trigger('change');
            break;
        case "maxPrice":
            const maxPrice = $("#range-max");
            maxPrice.val(1000000);
            maxPrice.trigger('change');
            break;
        case "dates":
            const dateField = $("#datesInput");
            dateField.val('');
            $(".search-result-date-from").val('');
            $(".search-result-date-to").val('');
            dateField.trigger('change');
            break;
        default:
            const filterItem = $("#"+typeId+"_"+filterId+" input");
            filterItem.prop("checked",false);
            filterItem.trigger('change');
    }
}

function getTypeLabel(typeId){
    var typeLabel = "";
    switch (typeId) {
        case "countryFilter":
            typeLabel = "Země: ";
            break;
        case "katalogFilter":
            typeLabel = "Ubytování: ";
            break;
        case "tourTypeFilter":
            typeLabel = "Typ: ";
            break;
        case "transportFilter":
            typeLabel = "Doprava: ";
            break;
        case "foodFilter":
            typeLabel = "Strava: ";
            break;
        case "durGroupFilter":
            typeLabel = "Délka: ";
            break;
        case "txt":
            typeLabel = "Text: ";
            break;
        case "minPrice":
            typeLabel = "Min cena: ";
            break;
        case "maxPrice":
            typeLabel = "Max cena: ";
            break;
        case "dates":
            typeLabel = "Datum: ";
            break;
    }
    return typeLabel;
}  

async function updateKatalog(fd, katalogFilters, destinaceFilters, expandKatalog){

    var fdRes = await fd;
    var serialIDs = new Set([...fdRes.data.items.map(item => item.id_serial)].flat(Infinity));
    var ubytIDs = new Set([...fdRes.data.items.map(item => item.id_objektu)].flat(Infinity));
    var zemeIDs = new Set([...fdRes.data.items.map(item => item.country)].flat(Infinity));
    var destinaceIDs = new Set([...fdRes.data.items.map(item => item.dId)].flat(Infinity));
    
    var katTours = $(".katalog_zeme");
    katTours.each((index, k) => {
        let zemeID = $(k).attr("id").split("_")[2];
        if(zemeIDs.has(zemeID)){
            //v soucasnych vysledcich se nachazi aktualni ubytko
            $(k).show();
        }else{
            $(k).hide();
        }
        
    });
    
    var katTours = $(".katalog_destinace");
    katTours.each((index, k) => {
        let destinaceID = $(k).attr("id").split("_")[2];
        if(destinaceIDs.has(destinaceID)){
            //v soucasnych vysledcich se nachazi aktualni ubytko
            $(k).show();
            if (destinaceFilters.includes(destinaceID)) {
                $(k).find('input[type="checkbox"]').prop("checked", true);
                $(k).find('input[type="checkbox"]').trigger('change');
                // Remove the destinaceID from destinaceFilters if present
                const index = destinaceFilters.indexOf(destinaceID);
                if (index !== -1) {
                    destinaceFilters.splice(index, 1);
                }
            }
        }else{
            $(k).hide();
        }
        
    });
    
    var totalMenuItems = 0;
    var katTours = $(".katalog_objekt");
    katTours.each((index, k) => {
        let ubytID = $(k).attr("id").split("_")[2];
        if(ubytIDs.has(ubytID) || serialIDs.has(ubytID)){
            //v soucasnych vysledcich se nachazi aktualni ubytko
            $(k).show();
            if (katalogFilters.includes(ubytID)) {
                $(k).find('input[type="checkbox"]').prop("checked", true);
                // Remove the ubytID from katalogFilters if present
                const index = katalogFilters.indexOf(ubytID);
                if (index !== -1) {
                    katalogFilters.splice(index, 1);
                }
            }
            totalMenuItems += 1;
        }else{
            $(k).hide();
        }
        
    }); 
    
    const myCollapse = document.getElementById('katalog_filter');
    const bsCollapse = new bootstrap.Collapse(myCollapse, { toggle: false }); // Initialize collapse without toggling

    if(totalMenuItems <= 20 || expandKatalog || katalogFilters.length > 0){
        // Expand the katalog filter if there are less than 20 items or if expandKatalog is true    
        bsCollapse.show();
    } else {
        bsCollapse.hide();
    }
}  


async function showData(filteredData,filteredDataNoKatalog,selected_filters,append){ 
    var filters = {countryFilter:[],katalogFilter:[],destinaceFilter:[],tourTypeFilter:[],transportFilter:[],foodFilter:[],durGroupFilter:[],txt:[],minPrice:[],maxPrice:[],dates:[]}
    selected_filters.forEach(f => {
        let parsedFilter = parseSelectedFilter(f);
        if(parsedFilter && filters[parsedFilter.key]){
            filters[parsedFilter.key].push(parsedFilter.value);
        }else{
            console.log(f);
        }
    
    });
    updateFilterWidgets(selected_filters);
    filteredDataNoKatalog.then(fdnk => 
    {
        var expandKatalog = filters.countryFilter.length == 1 && filters.tourTypeFilter.length == 1;    
        updateKatalog(fdnk, filters.katalogFilter, filters.destinaceFilter, expandKatalog);                    
        //console.dir(fdnk);
    });  
    
    filteredData.then(fd => 
    {
        fd.data.aggregations.strava.buckets.forEach(el => updateEnumElement(el,"foodFilter"));
        fd.data.aggregations.doprava.buckets.forEach(el => updateEnumElement(el,"transportFilter"));
        fd.data.aggregations.id_typ.buckets.forEach(el => updateEnumElement(el,"tourTypeFilter"));
        fd.data.aggregations.durGroup.buckets.forEach(el => updateEnumElement(el,"durGroupFilter"));
        fd.data.aggregations.country.buckets.forEach(el => updateEnumElement(el,"countryFilter"));
            
        // Update the page title for Země and Typ filters
        updateTotalTours(fd.pagination.total, selected_filters);

        updateTextSearch(filters.txt);
        //console.dir(fd);
        
        //load corresponding data and show them on appropriate location
        var dataAnchor = $("#tours_container");
        var toursToDisplay = fd.data.items;
        var ttdIDs = []
        var sIDs = []
        toursToDisplay.forEach(function(elem){
            ttdIDs.push(filterZajezd(elem));
            sIDs.push(elem.id_serial);
        });
        
        const formData = new FormData();
        formData.append("zajezdIDs", ttdIDs);  
        formData.append("serialIDs", sIDs);
        fetch("/ajax-load-data-for-vyhledat-zajezd.php", {
            method: "POST",
            body: formData,
        })
        .then((response) => response.text())
        .then((text) => {
            if(append){
               dataAnchor.append(text); 
            }else{
               tourLocations = {};
               dataAnchor.html(text); 
            }
            
        });
    }        
    );
}

function arraysEqual(a, b) {
    if (a === b) return true;
    if (a == null || b == null) return false;
    if (a.length !== b.length) return false;

    // If you don't care about the order of the elements inside
    // the array, you should sort both arrays here.
    // Please note that calling sort on an array will modify that array.
    // you might want to clone your array first.

    for (var i = 0; i < a.length; ++i) {
      if (a[i] !== b[i]) return false;
    }
    return true;
}

$(function(){
    setSearchResultDateInputsFromRange();
    syncSearchResultDateRange();
    var $accessibleSearchFields = $(".custom-search-input-2.search-box-accessible .form-control, .custom-search-input-2.search-box-accessible select");
    updateSearchFieldValueStates($accessibleSearchFields);
    $accessibleSearchFields.on("input change", function () {
        updateSearchFieldValueState($(this));
    });
    var today = new Date();
    var localToday = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    $(".search-result-date-from, .search-result-date-to").attr("min", localToday);

    
    var data = getData();
    var selected_filters_noKatalog = selected_filters.filter((filter) => 1-filter.startsWith("katalogFilter_"));
    var filteredDataNoKatalog = filterData(data,selected_filters_noKatalog, page, 10000);
    
    var filteredData = filterData(data,selected_filters, page, 20);                        
    
    showData(filteredData, filteredDataNoKatalog, selected_filters,false);
    
    
    
    /*setInterval(function(){
        var newFilters = updateFilters();
        if (!arraysEqual(newFilters,selected_filters)){
            //filters were modified in between
            selected_filters = newFilters;
            var filteredData = filterData(data, selected_filters, page);
            showData(filteredData,selected_filters,false);                      
        }
    }, 5000);*/
    


    function prepDataLoad() {
        page = 1;
        console.log("prepDataLoad");
        selected_filters = updateFilters();
        
        var selected_filters_noKatalog = selected_filters.filter((filter) => 1-filter.startsWith("katalogFilter_"));
        var filteredDataNoKatalog = filterData(data,selected_filters_noKatalog, page, 10000);
        var filteredData = filterData(data, selected_filters, page, 20);
        showData(filteredData, filteredDataNoKatalog, selected_filters, false);
    }

    let typingTimerTo;
    const typingDelay = 1000;

    const searchField = document.getElementById('autocomplete');
    const clearIcon = document.getElementById('clearIcon');
    const whereIcon = document.getElementById('whereIcon');

    function updateWhereClearState() {
        const hasValue = searchField.value.trim() !== '';
        clearIcon.style.display = hasValue ? 'block' : 'none';
        whereIcon.style.display = '';
    }

    updateWhereClearState();

    // Clear the input when the close icon is clicked
    clearIcon.addEventListener('click', function () {
        searchField.value = '';
        updateWhereClearState();

        $("#autocomplete").trigger('change');
        searchField.focus(); // Optionally refocus the input field
    });

    $("#autocomplete").on("input", function () {
        updateWhereClearState();

        clearTimeout(typingTimerTo);   // Clear the previous timer on each keystroke
        typingTimerTo = setTimeout(function() {
            // Code to execute after delay
            $("#autocomplete").trigger('change'); // Trigger the event
        }, typingDelay);
    });

    $(".search-result-date-from, .search-result-date-to").on("input change", function () {
        syncSearchResultDateRange();
        prepDataLoad();
    });

    $("#datesInput").change(function () {
        updateSearchFieldValueStates($(".search-result-date-from, .search-result-date-to"));
        prepDataLoad();
    });

    $(".form-control").not(".search-result-date-from, .search-result-date-to").change(function () {
        prepDataLoad();
    });

    $(".sorting-control").change(function () {
        prepDataLoad();
    });

    $(".filter input").change(function () {
        prepDataLoad();
    });

    $(".destinace input").change(function () {
        var checked = this.checked;
        var destinace = this.closest('.katalog_destinace');
        $(destinace).find('ul.ubyt input').each(function () {
            // Perform an action on each input
            $(this).prop("checked", checked);
        });
        prepDataLoad();
    });

    $("#loadMore").click(function () {
        page = page + 1;
        selected_filters = updateFilters();

        var selected_filters_noKatalog = selected_filters.filter((filter) => 1-filter.startsWith("katalogFilter_"));
        var filteredDataNoKatalog = filterData(data,selected_filters_noKatalog, page, 10000); 

        var filteredData = filterData(data, selected_filters, page, 20);                            
        showData(filteredData, filteredDataNoKatalog, selected_filters, true);
    });

});
