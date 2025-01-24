<?php


class PlatebniDokladUtils
{

    const DATUM_CAS_NONE = '0000-00-00';

    public static function czechDate($datumCas)
    {
        if ($datumCas == self::DATUM_CAS_NONE)
            return '';
        return date("d.m. Y", strtotime($datumCas));
    }

    public static function czechDateTime($datumCas)
    {
        return date("d.m. Y H:i:s", strtotime($datumCas));
    }

    public static function refractorDate($dump)
    {
        $dateTimeDump = preg_replace("/-/", "/", $dump, 2);
        $dateTimeDump = preg_replace("/-/", " ", $dateTimeDump, 1);
        $dateTimeDump = preg_replace("/-/", ":", $dateTimeDump, 2);
        return $dateTimeDump;
    }

    public static function sendEmailWithAttachment($sender, $reciever, $subject, $message, $file)
    {
        $subject = iconv("cp1250", "utf-8", $subject);
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $message = iconv("cp1250", "utf-8", $message);
        $mail = new PHPMailer();
        $mail->CharSet = 'utf-8';
        $mail->From = $sender;
        $mail->FromName = $sender;
        $mail->addAddress($reciever);
        $mail->addAttachment(PlatebniDokladModelConfig::PDF_FOLDER . $file);
        $mail->Subject = $subject;
        $mail->Body = $message;

        return $mail->send();
    }

}