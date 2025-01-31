<?php

require_once dirname(__FILE__) . '/../phpMailer/minimal/class.phpmailer.php';
class SlantourPaymentsUtils
{
    private static $EMAIL_SENDER = "info@slantour.cz";
    private static $EMAIL_EKONOM = "ekonom@slantour.cz";
    private static $EMAIL_TEST = "lpeska@seznam.cz";

    private static function sendEmailWithAttachment($sender, $reciever, $subject, $message, $file = null)
    {
        $mail = new PHPMailer();
        $mail->From = $sender;
        $mail->FromName = $sender;
        $mail->addAddress($reciever);
        if (!is_null($file))
            $mail->addAttachment($file);
        $mail->Subject = $subject;
        $mail->Body = $message;

        return $mail->send();
    }

    /**
     * @param $paymentsProtocol AgmoPaymentsSimpleProtocol
     * @param $orderId
     * @param $test
     */
    public static function sendEkonomMail($paymentData, $orderId, $test)
    {
        $status = $paymentData["status"];
        $price  = $paymentData["price"]." CZK";
        $refId = $paymentData["refId"];
        $transId = $paymentData["payId"];
        $subject = "potvrzeni platby CSOB";
        if($status != 'PAID'){
             $subject = "potvrzeni platby CSOB - chyba";
        }

        $emailReciever = $test ? self::$EMAIL_TEST : self::$EMAIL_EKONOM;
        $emailReciever = self::$EMAIL_TEST;
        
        $message = "Potvrzení platby CSOB. \n\n
                    Èíslo objednávky(id): ".htmlspecialchars($orderId)."\n
                    èástka:  ".htmlspecialchars($orderId)."\n
                    stav platby: ".htmlspecialchars($status)."\n
                    referenèní id: ".htmlspecialchars($refId)."\n
                    id transakce:  ".htmlspecialchars($transId)."\n\n
                    
                    testovací provoz:  ".htmlspecialchars($test)."\n";

        self::sendEmailWithAttachment(
            SlantourPaymentsUtils::$EMAIL_SENDER,
            $emailReciever,
            $subject,
            $message
        );
    }

}


    $paymentsData = array(
          "status"=>  "CANCELLED"  ,
          "price"=>  123   ,
          "refId"=>  456  ,
          "payId"=>  "coiasf65"   
          
        );
    $orderId = 65498 ;  
    $config['test']=false  ;
    SlantourPaymentsUtils::sendEkonomMail($paymentsData, $orderId, $config['test']);    
    