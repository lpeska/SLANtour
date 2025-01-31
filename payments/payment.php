<?php
session_start();
require_once dirname(__FILE__) . '/common.php';
require_once ('logger.php');
require_once ('crypto.php');
require_once ('struct.php');
require ('setup.php');
//print_r(createCartData("test",1550))  ;

function payment_init($payments, $paymentsDatabase, $referenceId){

    require ('setup.php');

    echo "preparing payment init data ...\n\n";

    $merchantId = $payments ['merchant_id'];
    $orderNo = $payments ['order_no'];
    $totalAmount = $payments ['total_amount'];
    $shippingAmount = $payments ['shipping_amount'];
    $returnUrl = $payments ['return_url'];
    $goods_desc = $payments ['goods_desc'];
    $customerId = $payments ['customer_id'];
    
    $currency = $payments ['currency'];
    $language = $payments ['language'];
    
    $returnMethodPOST = "yes";
    $closePayment = true;
    $merchantData = null;
    //print_r($payments);
    //initiating new payment, in case of correct output, payID is returned
            if (!array_key_exists($currency, Constants::$CURRENCY)) {
                throw new Exception('Unsupported currency');
            }

            if (!array_key_exists($language, Constants::$LANGUAGE)) {
                throw new Exception('Unsupported language');
            }

            $dttm = (new DateTime ())->format("YmdHis");

            $cart = createCartData($goods_desc, $totalAmount);
            //echo "preparing cart data:\n";
            //var_dump($cart);
            //echo htmlspecialchars(json_encode($cart, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)) . "\n\n";

            $data = createPaymentInitData($merchantId, $orderNo, $dttm, $totalAmount, $returnUrl, $cart, $customerId, $privateKey, $privateKeyPassword, $closePayment, $merchantData, $returnMethodPOST, $currency, $language);

            //echo "prepared payment/init request:\n";
            //echo htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)) . "\n\n";

            //echo "processing payment/init request ...\n\n";

            $ch = curl_init($url . NativeApiMethod::$init);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json;charset=UTF-8'
            ));

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                echo 'payment/init failed, reason: ' . htmlspecialchars(curl_error($ch));
                return;
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode != 200) {
                echo 'payment/init failed, http response: ' . htmlspecialchars($httpCode);
                return;
            }

            curl_close($ch);

            echo "payment/init result:\n" . htmlspecialchars($result) . "\n\n";

            $result_array = json_decode($result, true);
            if (is_null($result_array ['resultCode'])) {
                echo 'payment/init failed, missing resultCode';
                return;
            }

            if (verifyResponse($result_array, $publicKey, "payment/init verify") == false) {
                echo 'payment/init failed, unable to verify signature';
                return;
            }

            if ($result_array ['resultCode'] != '0') {
                echo 'payment/init failed, reason: ' . htmlspecialchars($result_array ['resultMessage']);
                return;
            }

            $payId = $result_array ['payId'];
            
            
            // save transaction data into SLANtour database
            $paymentsDatabase->saveTransaction(
              $payId,                                                           // transId
              $referenceId,                                                             // refId
              $totalAmount,                                                             // price
              $currency,                                                          // currency
              SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_PENDING,         // status
              $orderNo                                                             //id objednavky
            );  
            
            $params = createGetParams($merchantId, $payId, $dttm, $privateKey, $privateKeyPassword);    
            
            return array($payId, $params);
}


//try {
    // prepare payment parameters
    $referenceId = $paymentsDatabase->createNextRefId();

    //debug
//    $_SESSION['method'] = "CARD_ALL";
//    $_SESSION['email'] = "jelen.job@gmail.com";
//    $_SESSION['price'] = "122";
//    $_SESSION['id_objednavky'] = "11962";

    $price = $_SESSION["price"];
    $email = $_SESSION["email"];
    $objId = $_SESSION["id_objednavky"];
    $method = $_SESSION['method'];

    $currency = 'CZK';
    $method = $paymentsDatabase->validateMethod($method);

    $payments["merchant_id"] = "M1MIPS8831";
    $payments["order_no"] = $objId;
    $payments["currency"] = $currency;
    $payments["language"] = "CZ";
    $payments["total_amount"] =  $price;
    $payments["return_url"] =  "https://www.slantour.cz/payments/query.php?refID=".htmlspecialchars($referenceId, ENT_QUOTES);
    $payments["goods_desc"] =  'Platba objednavky zajezdu'    ;
    $payments["description"] = 'Platba objednavky zajezdu od CK SLAN tour' ;
    $payments["customer_id"] = NULL;

    $res = payment_init($payments, $paymentsDatabase, $referenceId);

    $payID = $res[0];
    $params = $res[1];
    $_SESSION["payID"] =  $payID;
    
    echo $_SESSION["payID"];
    
    header('location: '.htmlspecialchars($url . NativeApiMethod::$process . $params, ENT_QUOTES));
     
    //echo '<a href="'.htmlspecialchars($url . NativeApiMethod::$process . $params, ENT_QUOTES).'">payment/process</a><br/>';
    //echo '<a href="query.php?action=status&merchant_id='. htmlspecialchars($payments["merchant_id"], ENT_QUOTES).'&pay_id='.htmlspecialchars($payID, ENT_QUOTES).'">payment/status</a><br/>';
    
    /*

    // create new payment transaction
    $paymentsProtocol->createTransaction(     merchant_id,    order_no,    currency,  total_amount,   return_url , goods_desc, description, customer_id
        'CZ',               // country
        $price,             // price
        $currency,          // currency
        'platba objednavky',     // label
        $refId,             // refId
        NULL,               // payerId
        'STANDARD',         // vatPL
        'DIGITAL',          // category
        $method,            // method
        '',                 //account
        $email              //email
    );
    $transId = $paymentsProtocol->getTransactionId();



    // redirect to agmo payments system
    header('location: '.$paymentsProtocol->getRedirectUrl());
    */

/*
catch (Exception $e) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "ERROR\n\n";
    echo $e->getMessage();
}
      */