<?php


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
        //$emailReciever = self::$EMAIL_TEST;
        
        if($test==true){
          $test = "Testovaci provoz"   ;
        }else{
          $test = "Ostry provoz" ;
        }
        
        $message = "Potvrzení platby CSOB. \n\n
                    Císlo objednavky(id): ".htmlspecialchars($orderId)."\n
                    castka:  ".htmlspecialchars($price)."\n
                    stav platby: ".htmlspecialchars($status)."\n
                    referencni id: ".htmlspecialchars($refId)."\n
                    id transakce:  ".htmlspecialchars($transId)."\n\n
                    
                    testovani:  ".htmlspecialchars($test)."\n";

        self::sendEmailWithAttachment(
            SlantourPaymentsUtils::$EMAIL_SENDER,
            $emailReciever,
            $subject,
            $message
        );
    }

}