<?
/**    \file
 * typ_serialu.php  - administrace typu a podtypu seriálù
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_typ = id typu
 * @param $id_podtyp = id podtypu
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/card_req.inc.php"; //seznamy serialu

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

//require_once ( __DIR__."/../payments/common.php");
require_once ( __DIR__."/../payments/logger.php");
require_once ( __DIR__."/../payments/crypto.php");
require_once ( __DIR__."/../payments/struct.php");
require ( __DIR__."/../payments/setup.php");

print_r($_POST)  ;
print_r($_GET)  ;

function payment_status($payId){
      //check if the payment exists
      $database = Database::get_instance();	
      $action="status";
      $query =   "SELECT * FROM `platba_agmo` WHERE `transaction_id`= \"".mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$payId)."\"  limit 1" ;
      //echo $query;
      $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
      if(mysqli_num_rows($res) == 0){
           echo 'payment/' . htmlspecialchars($action) . ' failed, reason: unknown transaction '.htmlspecialchars($payId)." ";
           return "{'ERROR':1}";
      }
      
      
      require ( __DIR__."/../payments/setup.php");
      //using $merchantId, $url from setup.php
      $dttm = (new DateTime ())->format("YmdHis");
      
      $params = createGetParams($merchantId, $payId, $dttm, $privateKey, $privateKeyPassword);
        $gateway_url = $url . "/payment/status/" . $params;
        $ch = curl_init($gateway_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Accept: application/json;charset=UTF-8'
                    ));                                             
      $result = curl_exec($ch);
     if (curl_errno($ch)) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, reason: ' . htmlspecialchars(curl_error($ch));
                    return "{'ERROR':2}";
     }

     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

     if ($httpCode != 200) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, http response: ' . htmlspecialchars($httpCode);
                    return "{'ERROR':3}";
     }
     curl_close($ch);

     $result_array = json_decode($result, true); 
     print_r($result_array);     
     if (is_null($result_array ['resultCode'])) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, missing resultCode';
                    return "{'ERROR':4}";
                }                                      
     if (verifyResponse($result_array, $publicKey, "payment/" . $action . " verify") == false) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, unable to verify signature';
                    return "{'ERROR':5}";
     }          
     if ($result_array ['resultCode'] != '0') {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, reason: ' . htmlspecialchars($result_array ['resultMessage']);

                    
                    return "{'ERROR':6}";
     }   
     if($result_array ['paymentStatus'] == 3 or $result_array ['paymentStatus'] == 5 or $result_array ['paymentStatus'] == 6){
                        $query =   "UPDATE `platba_agmo` set `status` = \"CANCELLED\"  WHERE `transaction_id`= \"".mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$payId)."\" limit 1" ;
                        $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
                    }
      if($result_array ['paymentStatus'] == 4 or $result_array ['paymentStatus'] == 7 or $result_array ['paymentStatus'] == 8){
                        $query =   "UPDATE `platba_agmo` set `status` = \"PAID\"  WHERE `transaction_id`= \"".mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$payId)."\" limit 1" ;
                        $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
                    }
     return $result;             
      //TODO: update database and set payment to canceled upon correct request
      
}


function payment_reverse($payId){
      //check if the payment exists
      $database = Database::get_instance();	
      $query =   "SELECT * FROM `platba_agmo` WHERE `transaction_id`= \"".mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$payId)."\" and `status` not LIKE \"CANCELLED\" limit 1" ;
      //echo $query;
      $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
      if(mysqli_num_rows($res) == 0){
           echo 'payment/' . htmlspecialchars($action) . ' failed, reason: unknown transaction '.htmlspecialchars($payId)." or transaction already cancelled";
           return "{'ERROR':1}";
      }
      
      $action="reverse";
      require ( __DIR__."/../payments/setup.php");
      //using $merchantId, $url from setup.php
      $gateway_url = $url . "/payment/reverse/";
      $custom_req = "PUT";
      $dttm = (new DateTime ())->format("YmdHis");
      $data = preparePutRequest($merchantId, $payId, $dttm, $privateKey, $privateKeyPassword);
      echo "req: " . htmlspecialchars(json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)) . "\n";
      $ch = curl_init($gateway_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $putData = json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_req);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $putData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Accept: application/json;charset=UTF-8'
                ));                  
      $result = curl_exec($ch);
     if (curl_errno($ch)) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, reason: ' . htmlspecialchars(curl_error($ch));
                    return "{'ERROR':2}";
     }

     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

     if ($httpCode != 200) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, http response: ' . htmlspecialchars($httpCode);
                    return "{'ERROR':3}";
     }
     curl_close($ch);

     $result_array = json_decode($result, true); 
     print_r($result_array);     
     if (is_null($result_array ['resultCode'])) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, missing resultCode';
                    return "{'ERROR':4}";
                }                                      
     if (verifyResponse($result_array, $publicKey, "payment/" . $action . " verify") == false) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, unable to verify signature';
                    return "{'ERROR':5}";
     }          
     if ($result_array ['resultCode'] != '0') {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, reason: ' . htmlspecialchars($result_array ['resultMessage']);
                    if($result_array ['paymentStatus'] == 5){
                        $query =   "UPDATE `platba_agmo` set `status` = \"CANCELLED\"  WHERE `transaction_id`= \"".mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$payId)."\" limit 1" ;
                        $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
                    }
                    return "{'ERROR':6}";
     }   
     if($result_array ['paymentStatus'] == 5){
                        $query =   "UPDATE `platba_agmo` set `status` = \"CANCELLED\"  WHERE `transaction_id`= \"".mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$payId)."\" limit 1" ;
                        $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
                    }
     return $result;             
      //TODO: update database and set payment to canceled upon correct request
      
}
function gateway_echo($method){
      require ( __DIR__."/../payments/setup.php");
      //$merchantId, $url
     $dttm = (new DateTime ())->format("YmdHis");
     if($method == "get")  {
        $params = createGetParamsEcho($merchantId, $dttm, $privateKey, $privateKeyPassword);
        $gateway_url = $url . "/echo/" . $params;
        $ch = curl_init($gateway_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Accept: application/json;charset=UTF-8'
                    ));
                
     }else if($method == "post"){
        $gateway_url = $url . "/echo";
        //echo $gateway_url;
        $ch = curl_init($gateway_url);     
        $data = preparePutRequestEcho($merchantId, $dttm, $privateKey, $privateKeyPassword); 
        $putData = json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);        
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $putData);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Accept: application/json;charset=UTF-8'
                    ));
      
     }
     $result = curl_exec($ch);
     //echo $result; 
     
     if (curl_errno($ch)) {
                    echo 'echo/' . htmlspecialchars($action) . ' failed, reason: ' . htmlspecialchars(curl_error($ch));
                    die();
     }

     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

     if ($httpCode != 200) {
                    echo 'echo/' . htmlspecialchars($action) . ' failed, http response: ' . htmlspecialchars($httpCode);
                    die();
     }
     curl_close($ch);

     $result_array = json_decode($result, true);
     //print_r($result_array)     ;
     
     if (is_null($result_array ['resultCode'])) {
                    echo 'echo/' . htmlspecialchars($action) . ' failed, missing resultCode';
                    die();
                }

     if (verify($result_array["dttm"]. "|" . $result_array ['resultCode'] . "|" . $result_array ['resultMessage'], $result_array["signature"], $publicKey, "verify") == false) {
                    echo 'echo/' . htmlspecialchars($action) . ' failed, unable to verify signature';
                    die();
     }
     
     

     if ($result_array ['resultCode'] != '0') {
                    echo 'echo/' . htmlspecialchars($action) . ' failed, reason: ' . htmlspecialchars($result_array ['resultMessage']);
                    die();
     }
     
     return  $result;
     

}


/*--------------	POZADAVKY DO DATABAZE	-------------------------*/
//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

if ($zamestnanec->get_correct_login()) {
//obslouzim pozadavky do databaze - s automatickym reloadem stranky		
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)	
    $adress = "";
    /*--------------------- informace ---------------*/
    if ($_GET["typ"] == "card_req") {

        if ($_POST["pozadavek"] == "echo") {
            //insert do tabulky seriálù
            $dotaz = gateway_echo($_POST["method"]);

                $adress = $_SERVER['SCRIPT_NAME'] . "?";
                $_SESSION["query_response"] = $dotaz;


        } else if ($_POST["pozadavek"] == "payment/reverse") {
            $dotaz = payment_reverse($_POST["transaction_id"]);
                $adress = $_SERVER['SCRIPT_NAME'] . "?";
                $_SESSION["query_response"] = $dotaz;
            
        } else if ($_POST["pozadavek"] == "payment/status") {
            $dotaz = payment_status($_POST["transaction_id"]);
                $adress = $_SERVER['SCRIPT_NAME'] . "?";
                $_SESSION["query_response"] = $dotaz;
            
        }

    }

}

//pokud byl nejaky pozadavek na reload stranky, tak ho provedu
if ($adress) {
    //header("Location: https://" . $_SERVER['SERVER_NAME'] . $adress);
    //exit;
}
//zpracovani hlasky k vypsani (jsme za headerem pro presmerovani, takze ji v sessions smazeme a zobrazime ve vypisu)	
if ($_SESSION["hlaska"] != "") {
    $hlaska_k_vypsani = $_SESSION["hlaska"];
    $_SESSION["hlaska"] = "";
} else {
    $hlaska_k_vypsani = "";
}

if ($_SESSION["query_response"] != "") {
    $query_response = $_SESSION["query_response"];
    $_SESSION["query_response"] = "";
} else {
    $query_response = "";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <?
    $core = Core::get_instance();
    echo "<title>" . $core->show_nazev_modulu() . " | Administrace systému RSCK</title>";
    ?>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1250"/>
    <meta name="copyright" content="&copy; Slantour"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta name="robots" content="noindex,noFOLLOW"/>
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,100italic,100,300,300italic,400italic,500,500italic,700,700italic&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="css/reset-min.css">
    <link rel="stylesheet" type="text/css" href="./new-menu/style.css" media="all"/>
</head>
<body>

<?
if ($zamestnanec->get_correct_login()) {
//prihlaseni probehlo vporadku, muzu pokracovat
    //zobrazeni hlavniho menu
    echo ModulView::showNavigation(new AdminModulHolder($core->show_all_allowed_moduls()), $zamestnanec, $core->get_id_modul());

    //zobrazeni aktualnich informaci - nove rezervace, pozadavky...
    ?>
    <div class="main-wrapper">
        <div class="main">
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

                ?>
                <h3>Zpracování požadavkù do platební brány</h3>
                <?
                 $pozadavek = "
                   Typ dotazu: <select name=\"pozadavek\">
                      <option value=\"echo\">echo</option>
                      <option value=\"payment/reverse\">payment/reverse (zrusit platbu)</option>
                      <option value=\"payment/status\">payment/status (dotaz na stav)</option>
                   </select> <br/>     
                   
                   Zpùsob odeslání: <select name=\"method\">
                      <option value=\"get\">get</option>
                      <option value=\"post\">post</option>
                   </select> <br/>                                
                 ";
                 $transakce = "ID transakce: <input type=\"text\" name=\"transaction_id\" value=\"\"><br/>";                
                 $submit= "<input type=\"submit\" value=\"Odeslat požadavek\" />\n";
                 
                 $vystup=                 
                 "<form action=\"?typ=card_req\" method=\"post\" >".
						        $pozadavek.$transakce.$submit.
					       "</form>";
                 
                 if($query_response) {
                    echo "<h4>Výstup posledního dotazu</h4>";
                    echo "<pre>"; 
                    echo json_encode(json_decode($query_response, true), JSON_PRETTY_PRINT);
                    echo "</pre>"; 
                 
                 }
                 
                 
                 echo $vystup;
                

            ?>
        </div>
    </div>
    <?
    //zobrazeni napovedy k modulu
    $core = Core::get_instance();
    echo ModulView::showHelp($core->show_current_modul()["napoveda"]);
} else {
    //zadny uzivatel neni prihlasen, vypisu logovaci formular
    echo ModulView::showLoginForm($zamestnanec->get_uzivatelske_jmeno());
    echo $zamestnanec->get_error_message();

}
?>

</body>
</html>