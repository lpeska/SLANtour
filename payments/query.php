<?php
require_once dirname(__FILE__) . '/common.php';
//var_dump($_GET);
//var_dump($_POST);
//var_dump($_SESSION);



//echo $_SESSION["payID"];

$refId = (int)$_GET["refID"];

//$payId = $_GET["payID"];

$payId = $paymentsDatabase->getTransactionId($refId); 
$row = $paymentsDatabase->getRow($refId); 
$price = $row->price;
$orderId = $row->id_objednavka;

            require_once ('logger.php');
            require_once ('crypto.php');
            require_once ('setup.php');

            $action = "status";

            echo 'processing payment/' . htmlspecialchars($action) . "\n\n";

            $gateway_url = null;

            //$merchantId = $merchantId from setup.php;
            //$payId = $_GET ['pay_id'];

            $dttm = (new DateTime ())->format("YmdHis");

            $params = createGetParams($merchantId, htmlspecialchars($payId), $dttm, $privateKey, $privateKeyPassword);

            $data = null;
            $custom_req = null;
            switch ($action) {
                case 'status':
                    $gateway_url = $url . NativeApiMethod::$status . $params;
                    $custom_req = "GET";
                    break;
               /* case 'close':
                    $gateway_url = $url . NativeApiMethod::$close;
                    $custom_req = "PUT";
                    $data = preparePutRequest($merchantId, $payId, $dttm, $privateKey, $privateKeyPassword);
                    break;
                case 'reverse':
                    $gateway_url = $url . NativeApiMethod::$reverse;
                    $custom_req = "PUT";
                    $data = preparePutRequest($merchantId, $payId, $dttm, $privateKey, $privateKeyPassword);
                    break;
                case 'refund':
                    $gateway_url = $url . NativeApiMethod::$refund;
                    $custom_req = "PUT";
                    $data = preparePutRequest($merchantId, $payId, $dttm, $privateKey, $privateKeyPassword);
                    break;  */
            }

            echo "http method: " . htmlspecialchars($custom_req) . "\n";
            echo "gateway url: " . htmlspecialchars($gateway_url) . "\n";
            if (!is_null($data)) {
                echo "req: " . htmlspecialchars(json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)) . "\n";
            }

            if (!is_null($action)) {
                $ch = curl_init($gateway_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                if ($custom_req == 'PUT') {
                    $putData = json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_req);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $putData);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Accept: application/json;charset=UTF-8'
                    ));
                } else {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Accept: application/json;charset=UTF-8'
                    ));
                }


                $result = curl_exec($ch);
                echo "\n";

                if (curl_errno($ch)) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, reason: ' . htmlspecialchars(curl_error($ch));
                    header("location: ".$thankYouURL."dekujeme.php?refID=".htmlspecialchars($refId)."&status=".SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_ERROR ."&payID=".htmlspecialchars($payId) );
                    die();
                }

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                echo "http status: " . htmlspecialchars($httpCode) . "\n\n";

                if ($httpCode != 200) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, http response: ' . htmlspecialchars($httpCode);
                    header("location: ".$thankYouURL."dekujeme.php?refID=".htmlspecialchars($refId)."&status=".SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_ERROR."&payID=".htmlspecialchars($payId)  );
                    die();
                }
                curl_close($ch);

                echo 'response: ' . htmlspecialchars($result) . "\n\n";

                $result_array = json_decode($result, true);
                if (is_null($result_array ['resultCode'])) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, missing resultCode';
                    header("location: ".$thankYouURL."dekujeme.php?refID=".htmlspecialchars($refId)."&status=".SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_ERROR ."&payID=".htmlspecialchars($payId) );
                    die();
                }

                if (verifyResponse($result_array, $publicKey, "payment/" . $action . " verify") == false) {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, unable to verify signature';
                    header("location: ".$thankYouURL."dekujeme.php?refID=".htmlspecialchars($refId)."&status=".SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_ERROR ."&payID=".htmlspecialchars($payId) );
                    die();
                }

                if ($result_array ['resultCode'] == '130') {
                    //transaction timed out
                    if($result_array['paymentStatus'] == 3 or $result_array['paymentStatus'] == 6  ){
                        $status = SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_CANCELED ;
                
                    } 
                } else if ($result_array ['resultCode'] != '0') {
                    echo 'payment/' . htmlspecialchars($action) . ' failed, reason: ' . htmlspecialchars($result_array ['resultMessage']);
                    header("location: ".$thankYouURL."dekujeme.php?refID=".htmlspecialchars($refId)."&status=".SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_ERROR."&payID=".htmlspecialchars($payId)  );
                    die();
                }
                
                $status = SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_PENDING;
                //status inquiry went as expected, process the query:
                //payment was successful
                if ($result_array['paymentStatus'] == 4 or $result_array['paymentStatus'] == 7 or $result_array['paymentStatus'] == 8) {                  
                    $status = SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_PAID   ;
                 
                //pending  (strange) 
                }else if($result_array['paymentStatus'] == 2 ) {
                    $status = SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_PENDING  ;
                
                //canceled or was not approved - either way, payment did not get through
                }else if($result_array['paymentStatus'] == 3 or $result_array['paymentStatus'] == 6  ){
                    $status = SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_CANCELED ;
                
                } 
                
                //run database update based on the status
try {
    // check transaction parameters in my database
    $paymentsDatabase->checkTransaction(
        $payId,
        $refId
    );

    //todo nasledujici 2 kroky by mely byt realizovany v ramci jedne transakce a pri odchyceni vyjimky by mel byt zavolan rollback
    // save new transaction status to my database
    $paymentsDatabase->updateTransaction(
        $payId,
        $refId,
        $status
    );

    // if payment was received (status == 'PAID'), write payment into database
    if ($status === SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_PAID) {
        $orderId = $paymentsDatabase->createPayment(
            $payId,
            $refId,
            "CARD"
        );
    }
    
    $paymentsData = array(
          "status"=>  $status  ,
          "price"=>  $price   ,
          "refId"=>  $refId  ,
          "payId"=>  $payId   
          
        );
        
    SlantourPaymentsUtils::sendEkonomMail($paymentsData, $orderId, $config['test']);    
    

    // return OK
    echo 'code=0&message=OK';
    echo "location: ".$thankYouURL."dekujeme.php?refID=".htmlspecialchars($refId)."&payID=".htmlspecialchars($payId)  ;
    header("location: ".$thankYouURL."dekujeme.php?refID=".htmlspecialchars($refId)."&payID=".htmlspecialchars($payId)  );
    die();

} catch (Exception $e) {

    // return ERROR
    echo 'code=1&message=' . urlencode($e->getMessage());
    header("location: ".$thankYouURL."dekujeme.php?refID=".htmlspecialchars($refId)."&status=".SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_ERROR."&payID=".htmlspecialchars($payId)  );
    die();

}
                
                
                
                
            }
   
