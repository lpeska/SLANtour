<?php
//note casem se zbavit core - pouziva se hlavne kvuli prihlasenemu uzivateli
require_once "./core/load_core.inc.php";

/* GLOBAL */
//cfg
require_once "../global/lib/cfg/CommonConfig.php";
require_once "../global/lib/cfg/DatabaseConfig.php";
require_once "../global/lib/cfg/ViewConfig.php";
//db
require_once "../global/lib/db/DatabaseProvider.php";
require_once "../global/lib/db/SQLQuery.php";
require_once "../global/lib/db/DBResult.php";
//model
require_once "../global/lib/model/entyties/ObjednavkaEnt.php";
require_once "../global/lib/model/entyties/UserKlientEnt.php";
require_once "../global/lib/model/entyties/SerialEnt.php";
require_once "../global/lib/model/entyties/FakturaEnt.php";
require_once "../global/lib/model/entyties/OrganizaceEnt.php";
require_once "../global/lib/model/entyties/AdresaEnt.php";
require_once "../global/lib/model/entyties/SluzbaEnt.php";
require_once "../global/lib/model/entyties/PlatbaEnt.php";
require_once "../global/lib/model/entyties/FotoEnt.php";
require_once "../global/lib/model/entyties/ZajezdEnt.php";
require_once "../global/lib/model/entyties/SlevaEnt.php";
require_once "../global/lib/model/entyties/SmluvniPodminkyNazevEnt.php";
require_once "../global/lib/model/entyties/SmluvniPodminkyEnt.php";
require_once "../global/lib/model/entyties/TerminObjektoveKategorieEnt.php";
require_once "../global/lib/model/entyties/ObjektovaKategorieEnt.php";
require_once "../global/lib/model/entyties/ObjektEnt.php";
require_once "../global/lib/model/holders/AdresaHolder.php";
require_once "../global/lib/model/holders/UserKlientHolder.php";
require_once "../global/lib/model/holders/SluzbaHolder.php";
require_once "../global/lib/model/holders/FakturaHolder.php";
require_once "../global/lib/model/holders/PlatbaHolder.php";
require_once "../global/lib/model/holders/SlevaHolder.php";
require_once "../global/lib/model/holders/SmluvniPodminkyHolder.php";
require_once "../global/lib/model/holders/TerminObjektoveKategorieHolder.php";
require_once "../global/lib/model/holders/TerminObjektoveKategorieHolder.php";
require_once "../global/lib/model/holders/ObjektovaKategorieHolder.php";
require_once "../global/lib/model/validators/ValuesValidator.php";
require_once "../global/lib/model/validators/SluzbaValidator.php";
require_once "../global/lib/model/validators/SlevaValidator.php";
require_once "../global/lib/model/validators/UserKlientValidator.php";
require_once "../global/lib/model/validators/PlatbaValidator.php";
require_once "../global/lib/model/validators/entities/ValidatorResponse.php";
require_once "../global/lib/model/sessions/ToggleSectionsSessionEnt.php";
//dao
require_once "../global/lib/db/dao/ObjednavkyDAO.php";
require_once "../global/lib/db/dao/sql/ObjednavkySQLBuilder.php";
//utils
require_once "../global/lib/utils/CommonUtils.php";
require_once "../global/lib/utils/ViewUtils.php";
//tpl engine


/* LOCAL */
//model
require_once "./classes/objednavky/lib/cfg/ObjednavkyModelConfig.php";
require_once "./classes/objednavky/lib/model/ObjednavkyModel.php";
require_once "./classes/objednavky/lib/model/iface/IObjednavkyObserver.php";
//ctrl
require_once "./classes/objednavky/lib/ctrl/ObjednavkyController.php";
//view
require_once "./classes/objednavky/lib/view/ObjednavkyView.php";
require_once "./classes/objednavky/lib/view/ObjednavkyViewObjednavkaDetail.php";

/* NEW GUI */
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";


//require_once "../global/res/plugins/Twig_objed/Autoloader.php";
//require_once "../global/res/plugins/Twig_objed/ExtensionInterface.php";
//require_once "../global/res/plugins/Twig_objed/Extension.php";
//require_once "../global/res/plugins/Twig_objed/Extension/Text.php";

   /*
Twig_Autoloader::register();

if (class_exists('Twig_Environment') ) {
    echo 'Twig1 is loaded!';
} else if (class_exists('\Twig\Environment')) {
    echo 'Twig2 is loaded!';
} else{
    echo 'Twig is not loaded!';
}
       */


  /*
//init twig template engine
echo "before loader" ;
$loader = new Twig_Loader_Filesystem(__DIR__ . "/classes/objednavky/lib/view/screens");
//if (CommonConfig::DEBUG)
echo "before loader" ;
    $twig = new Twig_Environment($loader, array('charset' => 'cp1251', 'debug' => true));
//else
//    $twig = new Twig_Environment($loader, array('charset' => 'cp1251', "cache" => "./classes/objednavky/lib/view/screens/cache"));
echo "before loader" ;
$twig->addExtension(new Twig_Extensions_Extension_Text());
echo "before loader" ;
//twig neumi volat staticke metody, takze ho to musim naucit
$twig->addFunction('staticCall', new Twig_Function_Function('staticCall'));
echo "before loader" ;
function staticCall($class, $function, $args = array())
{
    if (class_exists($class) && method_exists($class, $function))
        return call_user_func_array(array($class, $function), $args);
    return null;
}
echo "before loader" ;
*/



require_once __DIR__ .'/../vendor/autoload.php'; // Ensure you include Composer's autoload file


// Initialize Twig template engine
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/classes/objednavky/lib/view/screens');

// Set Twig environment configuration
$twig = new \Twig\Environment($loader, [
    // 'charset' => 'cp1250',
    'debug' => true, // Adjust this to false if not in debug mode
    // 'cache' => __DIR__ . '/classes/objednavky/lib/view/screens/cache', // Uncomment for caching
]);

// Add Text extension (if you are using `twig/extensions`, note that this library is deprecated; consider alternatives if possible)
//$twig->addExtension(new \Twig\Extensions\TextExtension());

// Add custom function for static method calls
$twig->addFunction(new \Twig\TwigFunction('staticCall', function ($class, $function, $args = []) {
    if (class_exists($class) && method_exists($class, $function)) {
        return call_user_func_array([$class, $function], $args);
    }
    return null;
}));


$twig->addFilter(new \Twig\TwigFilter('truncate', function ($text, $length = 30, $ellipsis = '...') {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . $ellipsis;
    }
    return $text;
}));
              
//init DAO db connection - note zbavit se tohodle - pristupu k DB nebude vic nez 1 a kdyz bude potreba upravit, upravi se ten jeden, takze muzu v klidu pouzivat DB staticky
ObjednavkyDAO::init();

//load MVC
$model = new ObjednavkyModel();
new ObjednavkyView();
new ObjednavkyViewObjednavkaDetail($model, $twig);
$ctrl = new ObjednavkyController($model);

