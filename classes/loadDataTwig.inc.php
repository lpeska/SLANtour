<?php
require_once "./core/load_core.inc.php";
require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu
require_once "./classes/informace_zeme.inc.php"; //seznam informaci katalogu
require_once "./classes/ohlasy_list.inc.php"; //seznam ohlasu

function getDiscountTours(String $typeName, $countryName)
{
    $discountTours = array();
    $slevy_array = array();
    $slevy_poradi = array();
    $slevy_list = new Serial_list($typeName, $countryName, "", "", "", "", "", "", "", "random", 40, "select_slevy");
    while ($slevy_list->get_next_radek()) {
        $slevyObj = $slevy_list->show_list_item("slevy_list");

        $od = $slevy_list->get_termin_od();
        $data = explode("-", $od);
        if ($dlouhodobe) {
            $dny_rozdil = 60;
        } else {
            $time_od = mktime(0, 0, 0, $data[1], $data[2], $data[0]);
            $time_now = mktime(0, 0, 0, Date("m"), Date("d"), Date("Y"));
            $dny_rozdil = ($time_od - $time_now) / 86400;

            if ($dny_rozdil < 0 or $dny_rozdil > 80) {
                //dlouhodobe zajezdy, nemaji prednost
                $dny_rozdil = 80;
            }
            if ($dny_rozdil < 5) {
                $dny_rozdil = 5;
            }
        }
        $dny_rozdil = log($dny_rozdil);
        $sleva = $slevy_list->get_max_sleva_zajezd();
        $rand = mt_rand(1, 1000000) / 500000;
        $poradi = ($sleva / $dny_rozdil) + $rand;

        if ($slevyObj["best_zajezd"] > -1) {
            $slevy_poradi[] = $poradi;
            $slevy_array[] = $slevyObj;
        }
    }
    //print_r($slevy_array);
    if (count($slevy_poradi) > 2) {

        arsort($slevy_poradi);
        $k = 0;
        foreach ($slevy_poradi as $key => $val) {
            $k++;
            $currTour = $slevy_array[$key];
            $bestTermin = $currTour["terminy"][$currTour["best_zajezd"]];
            if ($k <= 4) {
                $discountTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"], "", $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"] - 1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"], array(), "", $currTour["doprava"], $currTour["id_typ"]);
            } else {
                break;
            }
        }
    }
    return $discountTours;
}

function getPopularTours($typeName, $countryName)
{
    $popularTours = array();
    $popular_array = array();
    $popular_zajezdy = new Serial_list($typeName, $countryName, "", "", "", "", "", "", "", "random", 10, "select_vahy");
    $i = 0;
    while ($popular_zajezdy->get_next_radek()) {
        $i++;
        $toursObj = $popular_zajezdy->show_list_item("new_tour_list");
        if ($toursObj["best_zajezd"] > -1) {
            $popular_array[] = $toursObj;
        }
    }
    //print_r($popular_array);
    shuffle($popular_array);
    $k = 0;
    foreach ($popular_array as $key => $currTour) {
        $k++;
        //$currTour = $novinky_array[$key];
        // print_r($currTour);
        // echo "</br>";
        
        // $doprava = Serial_library::get_typ_dopravy($serial->serial["doprava"]-1);
        $bestTermin = $currTour["terminy"][$currTour["best_zajezd"]];
        if ($k <= 4) {
            $popularTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"], "", $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"] - 1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"], array(), "", $currTour["doprava"], $currTour["id_typ"]);
        } else {
            break;
        }
    }
    return $popularTours;
}

function getNewTours($typeName, $countryName)
{
    $newTours = array();
    $novinky_array = array();
    $novinky_zajezdy = new Serial_list($typeName, $countryName, "", "", "", "", "", "", "", "random", 20, "select_nove_zajezdy");
    $i = 0;
    while ($novinky_zajezdy->get_next_radek()) {
        $i++;
        $toursObj = $novinky_zajezdy->show_list_item("new_tour_list");
        $rand = mt_rand(1, 150) / 10;
        $poradi = $i + $rand;

        if ($toursObj["best_zajezd"] > -1) {
            $novinky_poradi[] = $poradi;
            $novinky_array[] = $toursObj;
        }
    }
    //print_r($novinky_array);
    //print_r($novinky_poradi);
    asort($novinky_poradi);
    $k = 0;
    foreach ($novinky_poradi as $key => $val) {
        $k++;
        $currTour = $novinky_array[$key];
        $bestTermin = $currTour["terminy"][$currTour["best_zajezd"]];
        if ($k <= 4) {
            $newTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"], "", $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"] - 1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"], array(), "", $currTour["doprava"], $currTour["id_typ"]);
        } else {
            break;
        }
    }
    return $newTours;
}

function getTourType($typeName)
{
    $menu = new Menu_katalog("dotaz_typy", "", "", "");
    $typ = $menu->get_typ_pobytu($typeName);

    //setup img for type
    $foto = getTypeImage($typ["id_typ"], $typ["foto_url"]);
    $description = getTypeDescription($typ["nazev_typ_web"]);
    $metaDescription = getTypeMetaDescription($typ["nazev_typ_web"], $typ["nazev_typ"]);
    $type = new TourType($typ["id_typ"], $typ["nazev_typ"], $typ["tourCount"], $typ["tourPrice"], $foto,  $description, "/zajezdy/typ-zajezdu/" . $typ["nazev_typ_web"], $metaDescription);
    // print_r($type);
    return $type;
}

function getTypeMetaDescription($typeSlug, $typeName)
{
    $metaDescriptions = [
        'poznavaci-zajezdy' => 'Objevte nejkrásnější místa Evropy i světa s CK SLAN tour. Poznávací zájezdy s průvodcem, bohatým programem a více než 30 letou zkušeností. Letecky, vlakem i autokarem. Rezervujte online.',
        'eurovikendy' => 'Eurovíkendy v nejkrásnějších metropolích Evropy. Letecky i vlakem. CK SLAN tour vám zajistí letenky, hotel i program. Vydejte se do Londýna, Paříže, Říma, Dublinu, Budapešti a dalších měst.',
        'exoticka-dovolena' => 'Sníte o dalekých krajích? CK SLAN tour vás vezme do exotiky! Objevte krásy Mexika, Bali, Brazílie, Malediv či Emirátů. Prožijte nezapomenutelnou dovolenou a leťte s námi.',
        'jednodenni-zajezdy' => 'Tipy na jednodenní výlety po Česku i Evropě. Pohodlné cestování autobusem, zajímavý program a zkušený průvodce. Čekají vás Adventní trhy, výstavy, hrady i nákupy v zahraničí.',
        'za-sportem' => 'Zažijte atmosféru světových sportovních událostí. CK SLAN tour zajišťuje vstupenky a zájezdy na fotbal, tenis, Formuli 1, ragby, hokej i další prestižní sportovní akce.',
        'lazne-a-wellness' => 'Odpočiňte si v lázních, termálech i wellness hotelech. CK SLAN tour nabízí lázeňské a wellness pobyty v ČR, Maďarsku, Slovensku a dalších zemích. Rezervujte hned.',
        'lazenske-pobyty' => 'Odpočiňte si v lázních, termálech i wellness hotelech. CK SLAN tour nabízí lázeňské a wellness pobyty v ČR, Maďarsku, Slovensku a dalších zemích. Rezervujte hned.',
        'dovolena' => 'Užijte si skvělou dovolenou nejen u moře s CK SLAN tour. Rezervujte online.',
        'na-hory' => 'Vydejte se za horskou turistikou a nádhernými výhledy. CK SLAN tour nabízí horské pobyty v Krkonoších, na Šumavě, v Jeseníkách, Tatrách i v Himalájích. Vyberte si svůj termín hned.',
        'pobyty-hory' => 'Vydejte se za horskou turistikou a nádhernými výhledy. CK SLAN tour nabízí horské pobyty v Krkonoších, na Šumavě, v Jeseníkách, Tatrách i v Himalájích. Vyberte si svůj termín hned.',
        'fly-drive' => 'Cestujte svobodně a bez starostí. S programem Fly and Drive od CK SLAN tour získáte letenky, hotel i pronájem auta. Vyražte na Island, do Andalusie, na Sicílii či jinam.',
        'fly-and-drive' => 'Cestujte svobodně a bez starostí. S programem Fly and Drive od CK SLAN tour získáte letenky, hotel i pronájem auta. Vyražte na Island, do Andalusie, na Sicílii či jinam.',
    ];

    $nameFallbacks = [
        'Poznávací zájezdy' => $metaDescriptions['poznavaci-zajezdy'],
        'Eurovíkendy' => $metaDescriptions['eurovikendy'],
        'Exotická dovolená' => $metaDescriptions['exoticka-dovolena'],
        'Jednodenní zájezdy' => $metaDescriptions['jednodenni-zajezdy'],
        'Sportovní zájezdy' => $metaDescriptions['za-sportem'],
        'Lázně a wellness' => $metaDescriptions['lazne-a-wellness'],
        'Dovolená' => $metaDescriptions['dovolena'],
        'Na hory' => $metaDescriptions['na-hory'],
        'Fly & Drive' => $metaDescriptions['fly-drive'],
    ];

    if (isset($metaDescriptions[$typeSlug])) {
        return $metaDescriptions[$typeSlug];
    }

    if (isset($nameFallbacks[$typeName])) {
        return $nameFallbacks[$typeName];
    }

    return "SLANtour - " . $typeName . " - nabídka zájezdů a pobytů. Prozkoumejte širokou nabídku zájezdů v kategorii " . $typeName . ".";
}

function getAllTourTypes()
{
    /*Loading tour types*/
    $menu = new Menu_katalog("dotaz_typy", "", "", "");
    $typy = $menu->get_typy_pobytu();
    //print_r($typy);

    $tourTypes = array();
    foreach ($typy as $typ) {
        $foto = getTypeImage($typ["id_typ"], $typ["foto_url"]);
        $t = new TourType(
            $typ["id_typ"],
            $typ["nazev_typ"],
            $typ["tourCount"],
            $typ["tourPrice"],
            $foto,
            $typ["description"],
            "/zajezdy/typ-zajezdu/" . $typ["nazev_typ_web"]
        );

        $tourTypes[$typ["id_typ"]] = $t;
    }
    return $tourTypes;
}

function getCountry($countryName)
{
    $menu = new Menu_katalog("dotaz_zeme_from_nazev", "", $countryName, "");
    $countryDB = $menu->get_zeme($countryName);
    // echo print_r($countryDB);
    //tady poresit chybu kdyz se data o zemi nenajdou...
    $infoDB = new Informace_zeme("zeme", "", $countryName, "", 0, "random", 1);
    $infoDB->get_next_radek();
    
    if(strlen($infoDB->get_popisek())>0){
        $popisek = $infoDB->get_popisek();        
    }else{
        $popisek = "";
    }
    $metaDescription = buildCountryMetaDescription($countryDB["nazev_zeme"], $popisek);
    
    $country = new Country(
        $countryDB["id_zeme"],
        $countryDB["nazev_zeme"],
        $popisek,
        $countryDB["tourCount"],
        $countryDB["tourPrice"], //tady nastane chyba ze cena neexistuje pokud zeme nema platny zajezd
        "https://slantour.cz/foto/full/".$infoDB->get_foto_url(),
        "/zeme/" . $countryDB["nazev_zeme_web"],
        $metaDescription,
        $popisek
    );
    return $country;
}

function buildCountryMetaDescription($countryName, $description, $maxLength = 180)
{
    $cleanDescription = trim(html_entity_decode(strip_tags($description), ENT_QUOTES, 'UTF-8'));
    $cleanDescription = preg_replace('/\s+/u', ' ', $cleanDescription);
    $prefix = "Zájezdy do " . $countryName . " s CK SLAN tour.";

    if ($cleanDescription === '') {
        return "SLANtour - " . $countryName . " - nabídka zájezdů a pobytů. Prozkoumejte krásy této země s našimi zájezdy.";
    }

    $metaDescription = $prefix . " " . $cleanDescription;

    if (mb_strlen($metaDescription, 'UTF-8') <= $maxLength) {
        return $metaDescription;
    }

    $shortened = mb_substr($metaDescription, 0, $maxLength, 'UTF-8');
    $lastSpace = mb_strrpos($shortened, ' ', 0, 'UTF-8');

    if ($lastSpace !== false) {
        $shortened = mb_substr($shortened, 0, $lastSpace, 'UTF-8');
    }

    return rtrim($shortened, " .,;:-") . "...";
}

function getAllCountries($continent, $typ = "")
{
    /*Loading countries*/
    $menu = new Menu_katalog("dotaz_zeme_list", $typ, "", "");
    $countriesDB = $menu->get_zeme_list();
    //echo print_r($countriesDB);

    $countries = array();
    foreach ($countriesDB as $country) {
        $c = new Country(
            $country["id_zeme"],
            $country["nazev_zeme"],
            "no description",
            $country["tourCount"],
            $country["tourPrice"],
            $country["foto_url"],
            "/zeme/" . $country["nazev_zeme_web"]
        );
        if ($continent == "zeme-seznam" || $continent == "") {
            $countries[$country["id_zeme"]] = $c;
        } else if ($continent == "evropa" && isEurope($country["nazev_zeme_web"])) {
            $countries[$country["id_zeme"]] = $c;
        } else if ($continent == "svet" && !isEurope($country["nazev_zeme_web"])) {
            $countries[$country["id_zeme"]] = $c;
        }
    }
    return $countries;
}

function getCountriesMenu()
{
    /*Loading countries*/
    $menu = new Menu_katalog("dotaz_zeme_list", "", "", "");
    $countriesDB = $menu->get_zeme_list();
    //echo print_r($countriesDB);

    $countries = array();
    $euCountries = array();
    $worldCountries = array();
    $sportCountries = array();
    $euCount = 0;
    $worldCount = 0;
    $sportCount = 0;
    foreach ($countriesDB as $country) {
        // echo $country["nazev_zeme_web"];
        // echo "</br>";
        // echo "isEu: " . isEurope($country["nazev_zeme_web"]);
        // echo "</br>";
        $c = new Country(
            $country["id_zeme"],
            $country["nazev_zeme"],
            "no description",
            $country["tourCount"],
            $country["tourPrice"],
            $country["foto_url"],
            "/zeme/" . $country["nazev_zeme_web"]
        );

        if( isEurope($country["nazev_zeme_web"])) {
            $euCountries[$country["id_zeme"]] = $c;
        } else {
            $worldCountries[$country["id_zeme"]] = $c;
        }
    }

    $sportCountries = getSportCountries();

    $euCount = count($euCountries);
    $worldCount = count($worldCountries);
    $sportCount = count($sportCountries);

    $countries = array_merge(getTopCountries($euCountries, 10), getTopCountries($worldCountries, 5), getTopCountries($sportCountries, 5));  
    
    return new CountryMenu($countries, $euCount, $worldCount, $sportCount);
}



function getKatalogMenu()
{
    /*Loading countries*/
    $menu = new Menu_katalog("dotaz_zeme_destinace_ubytovani", "", "", "");
    $katalogDB = $menu->get_allRows();
    return $katalogDB;
}



function getSportCountries()
{
    $menuSport = new Menu_katalog("dotaz_sport_list", "", "", ""); 
    $topSportsDB = $menuSport->get_zeme_list();
    // print_r($topSportsDB);
    foreach ($topSportsDB as $topSport) {
        // echo $topSport["nazev_zeme_web"];
        // echo "</br>";
        // print_r($topSport);
        // echo "</br>";
        // echo "</br>";
        $c = new Country(
            $topSport["id_zeme"],
            $topSport["nazev_zeme"],
            "no description",
            $topSport["tourCount"],
            $topSport["tourPrice"],
            $topSport["foto_url"],
            "/zeme/" . $topSport["nazev_zeme_web"]
        );
        $sportCountries[$topSport["id_zeme"]] = $c;
    }
    return $sportCountries;
}


function getTopCountries($countries, $count)
{
    // echo "start";
    // echo "</br>";
    // echo printCountries($countries);
    // echo "</br>";
    // echo "</br>";
    // echo "sorted";
    // echo "</br>";
    usort($countries, fn($a, $b) => $b->numberOfTours - $a->numberOfTours);
    // echo printCountries($countries);
    // echo "</br>";
    // echo "</br>";
    // echo "sliced";
    // echo "</br>";
    // echo printCountries(array_slice($countries, 0, $count));
    return array_slice($countries, 0, $count);
}

function getOhlasy(int $limit) 
{
    $ohlasy_list = new Ohlasy_list($limit);
    $ohlasyDB = $ohlasy_list->get_ohlasy_list();
    $ohlasy = array();
    foreach ($ohlasyDB as $ohlas) {
        $review = new Review(
            $ohlas["id_ohlasu"],
            $ohlas["nadpis"],
            $ohlas["kr_popis"],
            $ohlas["foto_url"],
            $ohlas["zajezd_url"],
            $ohlas["zajezd_title"],
        );
        // print_r($review);
        $ohlasy[$ohlas["id_ohlasu"]] = $review;
    }
    
    //print_r($ohlasy);
    return $ohlasy;
}

function printCountries($array)
{
    echo "size: " . sizeof($array);
    echo "</br>";
    foreach ($array as $c) {
        echo print_r($c);
        echo "</br>";
    }
}

function getTotalTours(array $tourTypes)
{
    $totalTours = 0;
    foreach ($tourTypes as $type) {
        $totalTours += $type->numberOfTours;
    }
    return $totalTours;
}

function getTypeImage($typeId, $defaultFoto)
{
    switch ($typeId) {
        case 1:
            $foto = '/img/dovolena.png';
            break;
        case 2:
            $foto = '/img/poznavaci.png';
            break;
        case 29:
            $foto = '/img/eurovikendy.png';
            break;
        case 3:
            $foto = '/img/lazne.png';
            break;
        case 4:
            $foto = '/img/sport.png';
            break;
        default:
            $foto = $defaultFoto;
    }
    return $foto;
}

function isEurope($countryNameWeb) {
     $euCountries = array(
        "albanie",
        "anglie",
        "belgie",
        "cerna-hora",
        "ceska-republika",
        "dansko",
        "estonsko",
        "finsko",
        "francie",
        "holandsko",
        "chorvatsko",
        "irsko",
        "island",
        "italie",
        "madarsko",
        "monako",
        "nemecko",
        "norsko",
        "polsko",
        "portugalsko",
        "rakousko",
        "recko",
        "severni-irsko",
        "skotsko",
        "slovensko",
        "slovinsko",
        "spanelsko",
        "svedsko",
        "svycarsko",
        "turecko",
        "wales"
     );
    return in_array($countryNameWeb, $euCountries);
}

function getTypeDescription($typeName) {
    $tourDescriptions = array(
        "poznavaci-zajezdy" => "<p>Toužíte po dobrodružství, které vás obohatí a zanechá ve vás vzpomínky na celý život? S CK SLAN tour se stanete skutečným cestovatelem, nejen pouhým turistou. Naše poznávací zájezdy vás zavedou do samotného srdce těch nejkrásnějších destinací v Evropě i na druhém konci světa. Ponořte se do místních tradic, odhalte fascinující historii a ochutnejte autentickou kuchyni tam, kde to žije. Poznávací zájezdy připravujeme pro naše klienty již více než 35 let.</p>
        <p class='list'>Proč vyrazit za poznáním právě s CK SLAN tour?</p>
        <ul>
        <li><strong>Zkušení průvodci:</strong> Naši průvodci nejsou jen „živé encyklopedie“. Jsou to specialisté, kteří daná místa dokonale znají, mluví místním jazykem a ukážou vám i skryté poklady, které v běžných bedekrech nenajdete.</li>
        <li><strong>Historie a kultura na dosah:</strong> Otevřeme vám dveře do slavných muzeí, projdeme s vámi ikonické památky UNESCO i malebná, zapomenutá zákoutí.</li>
        <li><strong>Zážitky pro všechny smysly:</strong> Poznávání pro nás neznamená jen fotografování památek. Čekají vás degustace místních specialit, ochutnávky vyhlášených vín i přímý kontakt s místní kulturou.</li>
        <li><strong>Komfort a bezstarostná organizace:</strong> Vyberte si mezi pohodlnou leteckou dopravou pro vzdálenější země, moderními autobusy nebo romantickou jízdou vlakem. O ubytování, transfery a program se kompletně postaráme my. Vy už jen objevujete a užívejte.</li>
        </ul>
        <p>Chcete se dozvědět před vaší cestou něco více o vybrané zemi či destinaci? Zkuste náš magazín o cestování: <a href='https://poznavaci.info/'>https://poznavaci.info/</a></p>",

        "za-sportem" => "<p>Naše zájezdy za sportem jsou tady pro všechny nadšené sportovní fanoušky, kterým nestačí sportovní atmosféra jen u televize.</p>",

        "pobytove-zajezdy" => "<p>Užijte si vaši dovolenou u nás i v cizině. Na plážích u moře či na jezerech a přehradách v tuzemsku či na Slovensku.</p>",

        "lazenske-pobyty" => "<p>Nabízíme široký výběr jak tradičních lázní, tak i lázní termálních či oblíbených wellness hotelů.
        Nyní máte jedinečnou příležitost načerpat novou energii a revitalizovat svou mysl a tělo v nádherném prostředí lázeňských destinací.</p>",

        "jednodenni-zajezdy" => "<p>Není třeba čekat na dovolenou - připojte se k nám na jednodenních zájezdech a zažijte jedinečné zážitky každý víkend.</p>",

        "pobyty-hory" => "<p>Vydejte se do  výšek a objevte krásu hor! Naše zájezdy do hor vás zavedou do rozličných  horských  krajin, 
        kde se budete moci těšit na nezapomenutelné dobrodružství, čistý vzduch  i překrásné výhledy.</p>",

        "exotika" => "<p>Čekají na vás sluneční paprsky, nádherné pláže, jemný písek a teplé moře.</p>",

        "fly-and-drive" => "<p>Prozkoumejte svět zcela novým způsobem - s volností a nezávislostí, kterou nabízí automobil. Od hory po oceán, od města k městu, 
        budete mít kontrolu nad každým kilometrem svého dobrodružství.</p>
        <p class='list'>Co vás čeká: </p>
        <ul>
        <li>Výběr z nekonečných tras a cílů po celém světě </li> 
        <li>Možnost objevovat tajemná místa, která nejsou turisticky přeplněná </li> 
        <li>Rezervace ubytování přizpůsobeného vašim preferencím </li> 
        <li>Lokální gastronomické zážitky na cestě </li> 
        <li>Itinerář, vytvořený podle vašich zájmů a časového plánu</li> 
        </ul>
        
        <p>Náš Fly and Drive balíček vám umožní naplánovat si cestu dle svých představ a zároveň vám poskytne bezstarostný zážitek s podporou 
        našeho týmu odborníků na cestování. Stačí  objednat auto, naložit si zavazadla a vydat se na cestu, na kterou budete vzpomínat po zbytek života.
        Nemáme pro vás jen zájezdy - máme pro vás nezapomenutelná dobrodružství, která vám umožní prozkoumat svět jako nikdy předtím. 
        S Fly and Drive zájezdy máte svět na dosah ruky.</p>
        ",
 
        "eurovikendy" => "<p>Přestaňte snít a začněte plánovat svůj nezapomenutelný Eurovíkend! Do evropských i světových metropolí s námi 
        můžete zamířit nejen letecky, ale také vlakem.</p>
        <p class='list'>Co můžete očekávat: </p>
        <ul>
        <li>Procházky historickými uličkami starobylých měst </li> 
        <li>Degustace nejlepších evropských vín a gastronomických lahůdek </li> 
        <li>Návštěvy světově proslulých muzeí a galerií </li> 
        <li>Nakupování v luxusních obchodech i na trzích s místními poklady </li> 
        <li>Noci plné zábavy a tance v nejlepších evropských klubech</li> 
        </ul>"
    );
    return $tourDescriptions[$typeName];
}

class Breadcrumb
{
    public string $label;
    public string $link;

    public function __construct(string $label, string $link)
    {
        $this->label = $label;
        $this->link = $link;
    }
}


class Tour
{
    public string $name;
    public string $escapedName;
    public string $type;
    public int $id_zajezd;
    public int $id_typ;
    public int $price;
    public  $priceDiscount;
    public  $priceOriginal;
    public  $nights;
    public string $meals;
    public string $destination;
    public string $image;
    public $terminy;
    public array $features;
    public string $description;
    public string $transport;

    public function __construct(string $name, string $escapedName, string $type, int $id_zajezd, int $price,  $priceDiscount,  $priceOriginal, $nights, string $meals, string $destination, string $image, $terminy = "", array $features, string $description, string $transport, int $id_typ)
    {
        $this->name = $name;
        $this->escapedName = $escapedName;
        $this->type = $type;
        $this->id_zajezd = $id_zajezd;
        $this->price = $price;
        $this->priceDiscount = $priceDiscount;
        $this->priceOriginal = $priceOriginal;
        $this->nights = $nights;
        $this->meals = $meals;
        $this->destination = $destination;
        $this->image = $image;
        $this->terminy = $terminy;
        $this->features = $features;
        $this->description = $description;
        $this->transport = $transport;
        $this->id_typ = $id_typ;
    }
}

class TourType
{
    public int $id;
    public string $name;
    public int $numberOfTours;
    public int $priceFrom;
    public string $image;
    public $description;
    public $url;
    public $metaDescription;

    public function __construct(int $id, string $name, int $numberOfTours, int $priceFrom, string $image, $description, $url, $metaDescription = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->numberOfTours = $numberOfTours;
        $this->priceFrom = $priceFrom;
        $this->image = $image;
        $this->description = $description;
        $this->url = $url;
        $this->metaDescription = $metaDescription;
    }
}

class News
{
    public string $title;
    public string $description;
    public string $day;
    public string $month;
    public string $image;

    public function __construct(string $title, string $description, string $day, string $month, string $image)
    {
        $this->title = $title;
        $this->description = $description;
        $this->day = $day;
        $this->month = $month;
        $this->image = $image;
    }
}

class Country
{
    public int $id;
    public string $name;
    public string $description;
    public int $numberOfTours;
    public int $priceFrom;
    public string $image;
    public $url;
    public $metaDescription;
    public $metaDescriptionSource;

    public function __construct(int $id, string $name, string $description, int $numberOfTours, int $priceFrom, string $image, $url, $metaDescription = null, $metaDescriptionSource = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->numberOfTours = $numberOfTours;
        $this->priceFrom = $priceFrom;
        $this->image = $image;
        $this->url = $url;
        $this->metaDescription = $metaDescription;
        $this->metaDescriptionSource = $metaDescriptionSource;
    }
}

class CountryMenu
{
    public array $countries;
    public int $euCount;
    public int $worldCount;
    public int $sportCount;

    public function __construct(array $countries, int $euCount, int $worldCount, int $sportCount)
    {
        $this->countries = $countries;
        $this->euCount = $euCount;
        $this->worldCount = $worldCount;
        $this->sportCount = $sportCount;
    }
}

class Feature
{
    public string $icon;
    public string $text;

    public function __construct(string $icon, string $text)
    {
        $this->icon = $icon;
        $this->text = $text;
    }
}

class Foto
{
    public string $url;
    public string $description;

    public function __construct(string $url, string $description)
    {
        $this->url = $url;
        $this->description = $description;
    }
}

class Program {
    public string $day;
    public string $title;
    public string $description;
    public string $image;

    public function __construct(string $day, string $title, string $description, string $image) {
        $this->day = $day;
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
    }
}

class Service
{
    public string $title;
    public string $capacity;
    public int $price;
    public int $priceBefore;
    public int $basicService;

    public function __construct(string $title, string $capacity, int $price, int $basicService = 0)
    {
        $this->title = $title;
        $this->capacity = $capacity;
        $this->price = $price;
        $this->priceBefore = -1;
        $this->basicService = $basicService;
    }
}

class TourDate
{
    public int $dateID;
    public string $date;
    public int $price;
    public int $priceBefore;
    public string $state;
    public string $discount;
    public string $details;
    public array $services;
    public array $extraFees;
    public array $pickupSpots;
    public array $discounts;

    public function __construct(int $dateID, string $date, int $price, string $state, string $discount, string $details, array $services, array $extraFees, array $pickupSpots, array $discounts)
    {
        $this->dateID = $dateID;
        $this->date = $date;
        $this->price = $price;
        $this->state = $state;
        $this->discount = $discount;
        $this->details = $details;
        $this->services = $services;
        $this->extraFees = $extraFees;
        $this->pickupSpots = $pickupSpots;
        $d = [];
        foreach ($discounts as $discount) {
            $d[] = new Discount(...$discount);
        }
                
        $this->discounts = $d;
        $this->priceBefore = -1;
    }
}

class Discount
{
    public $title;
    public $short_title;
    public $value;
    public $currency;
    public $notes;
    public $type;


    public function __construct($title, $short_title, $value, $currency, $notes, $type)
    {
        $this->title = $title;
        $this->short_title = $short_title;
        $this->value = $value;
        $this->currency = $currency;
        $this->notes = $notes;
        $this->type = $type;
    }
}


class Review
{
    public int $id;
    public string $title;
    public string $description;
    public string $image;
    public string $zajezd_url;
    public string $zajezd_title;

    public function __construct(int $id, string $title, string $description, string $image, string $zajezd_url, string $zajezd_title)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
        $this->zajezd_url = $zajezd_url;
        $this->zajezd_title = $zajezd_title;
    }
}

class Document
{
    public int $id;
    public string $title;
    public string $description;
    public string $url;

    public function __construct(int $id, string $title, string $description, string $url)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
    }
}
