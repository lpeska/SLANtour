<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Martin
 * Date: 1/29/14
 * Time: 2:49 PM
 * To change this template use File | Settings | File Templates.
 */

//todo tyhle utils by meli byt pristupne globalne, to same palti u seznamu ucastniku
class VoucheryUtils
{
    public static function czechDatetime($datumCas)
    {
        return date("d.m. Y G:i:s", strtotime($datumCas));
    }

    public static function czechDate($datumCas)
    {
        return date("d.m. Y", strtotime($datumCas));
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
        $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
        //text zpravy je jiz v utf-8
        $mail = new PHPMailer();
        $mail->CharSet = 'utf-8';
        $mail->From = $sender;
        $mail->FromName = $sender;
        $mail->addAddress($reciever);
        $mail->addAttachment(VoucheryModelConfig::PDF_FOLDER . $file);
        $mail->Subject = $subject;
        $mail->Body = $message;

        return $mail->send();
    }

    public static function isDate($date)
    {
        if (substr($date, 4, 1) == '-')
            return true;
        return false;

    }

    public static function isEmptyDate($date)
    {
        if ($date == "0000-00-00")
            return true;
        return false;

    }

    public static function redirect($url, $statusCode = 303)
    {
        header('Location: ' . $url, true, $statusCode);
        die();
    }
}