<?php
require_once 'vendor/autoload.php';


require_once "./core/load_core.inc.php"; 
require_once "./classes/serial_collection.inc.php"; //seznam serialu
$serialCol = new Serial_collection();

#get portion of data with zajezdy
$res = $serialCol->get_zajezdy_base();
$zajezdyArr = mysqli_fetch_all($res, MYSQLI_ASSOC);
$jsonData = json_encode($zajezdyArr);
file_put_contents("data.json",$jsonData,LOCK_EX);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Panagea - Premium site template fo r travel agencies, hotels and restaurant listing.">
    <meta name="author" content="Ansonika">
    <title>SLANtour</title>

    <!-- Favicons-->
    <link rel="shortcut icon" href="/img/logo_slan-fav.png" type="image/x-icon">
    <link rel="apple-touch-icon" type="image/x-icon" href="/html-template/img/apple-touch-icon-57x57-precomposed.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="/html-template/img/apple-touch-icon-72x72-precomposed.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="/html-template/img/apple-touch-icon-114x114-precomposed.png">
    <link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="/html-template/img/apple-touch-icon-144x144-precomposed.png">

    <!-- GOOGLE WEB FONT -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- BASE CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link href="/css/all.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
	<link href="/html-template/css/vendors.css" rel="stylesheet">

    <!-- YOUR CUSTOM CSS -->
    <link href="/css/custom.css" rel="stylesheet">
    
    <script src="/node_modules/itemsjs/dist/itemsjs.js"></script>
    
    <script>
        var minPrice = 100;
        var maxPrice = 4000;
        
        
        var data = fetch('data.json')
            .then((response) => response.json())
            .then((zajezdyData) => itemsjs(zajezdyData, 
                {
                    sortings: {
                      termin_asc: {
                        field: 'do',
                        order: 'asc'
                      },
                      termin_desc: {
                        field: 'do',
                        order: 'desc'
                      }              
                    },
                    aggregations: {
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
                      ubytovani: {
                        title: 'Typ ubytování',
                        size: 10,
                        conjunction: false,
                        sort: "key",
                      }
                    },
                    searchableFields: ['nazev', 'nazev_ubytovani']
                }))
            .then((ijs) => ijs.search(
                {
                    per_page: 20,
                    page: 1,
                    sort: 'termin_asc',
                    query: "Luhačov",
                    filters: {
                      doprava: [1,2,3]
                    },
                    filter: function(item) {
                        return item.castka >= minPrice && item.castka <= maxPrice && item.od >= "2022-11-01";
                    }
                    
                }))
            .then((tours) => console.dir(tours));
          
          
        ;
    </script>

</head>

<body class="datepicker_mobile_full">

</body>
</html><!-- comment -->

