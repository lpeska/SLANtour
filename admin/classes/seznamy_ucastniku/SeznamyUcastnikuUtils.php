<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Martin
 * Date: 1/29/14
 * Time: 2:49 PM
 * To change this template use File | Settings | File Templates.
 */

//todo tyhle utils by meli byt pristupne globalne, to same palti u voucheru
class SeznamyUcastnikuUtils
{
    const DATUM_CAS_NONE = '0000-00-00';

    public static function czechDate($datumCas)
    {
        if ($datumCas == self::DATUM_CAS_NONE)
            return '';
        return date("d.m. Y", strtotime($datumCas));
    }

    /**
     * Parsuje zaskrtla policka zajezdu, ktera se maji zobrazit v tiskove sestave
     * @return stdClass
     */
    public static function parseZajezdSetupRequest()
    {
        $setup = new stdClass();

        foreach ($_REQUEST as $rKey => $rValue) {
            if (strpos($rKey, "cb-f-zaj-") === 0) {
                $keyDump = explode("-", $rKey);
                $idZajezd = $keyDump[count((array)$keyDump) - 1];

                if ($rKey == "cb-f-zaj-titul-$idZajezd") {
                    @$setup->$idZajezd->titul = true;
                } else if ($rKey == "cb-f-zaj-datum-narozeni-$idZajezd") {
                    @$setup->$idZajezd->datumNarozeni = true;
                } else if ($rKey == "cb-f-zaj-rodne-cislo-$idZajezd") {
                    @$setup->$idZajezd->rodneCislo = true;
                } else if ($rKey == "cb-f-zaj-cislo-pasu-$idZajezd") {
                    @$setup->$idZajezd->cisloPasu = true;
                } else if ($rKey == "cb-f-zaj-adresa-$idZajezd") {
                    @$setup->$idZajezd->adresa = true;
                } else if ($rKey == "cb-f-zaj-telefon-$idZajezd") {
                    @$setup->$idZajezd->telefon = true;
                } else if ($rKey == "cb-f-zaj-email-$idZajezd") {
                    @$setup->$idZajezd->email = true;
                }
            }
        }

        return $setup;
    }

    /**
     * Parsuje zaskrtla policka objednavky, ktera se maji zobrazit v tiskove sestave
     * @return stdClass
     */
    public static function parseObjednavkaSetup()
    {
        $setup = new stdClass();

        foreach ($_REQUEST as $rKey => $rValue) {
            if (strpos($rKey, "cb-f-obj-") === 0) {
                $keyDump = explode("-", $rKey);
                $idObjednavka = $keyDump[count((array)$keyDump) - 1];
                if ($rKey == "cb-f-obj-id-$idObjednavka") {
                    @$setup->$idObjednavka->id = true;
                } else if ($rKey == "cb-f-obj-objednavajici-$idObjednavka") {
                    @$setup->$idObjednavka->objednavajici = true;
                } else if ($rKey == "cb-f-obj-nazev-$idObjednavka") {
                    @$setup->$idObjednavka->nazev = true;
                } else if ($rKey == "cb-f-obj-nastupni-misto-$idObjednavka") {
                    @$setup->$idObjednavka->nastupniMisto = true;
                }
            }
        }

        return $setup;
    }

    public static function refractorDate($dump)
    {
        $dateTimeDump = preg_replace("/-/", "/", $dump, 2);
        $dateTimeDump = preg_replace("/-/", " ", $dateTimeDump, 1);
        $dateTimeDump = preg_replace("/-/", ":", $dateTimeDump, 2);
        return $dateTimeDump;
    }

    /**
     * @param $filterValues SerialFilter
     * @return string pdf prefix
     */
    public static function generatePdfPrefix($filterValues)
    {
        $serialIds = $filterValues->getSerialIdsSelected();
        $zajezdIds = $filterValues->getZajezdIdsSelected();

        $pdfPrefix = "";

        foreach ($serialIds as $serialId) {
            $pdfPrefix .= $serialId;
        }
        $pdfPrefix .= "-";
        foreach ($zajezdIds as $zajezdId) {
            $pdfPrefix .= $zajezdId;
        }

        return md5($pdfPrefix);
    }

    public static function sendEmailWithAttachment($sender, $reciever, $subject, $message, $file)
    {
        $subject = iconv("cp1250", "utf-8", $subject);
        $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
        $message = iconv("cp1250", "utf-8", $message);
        $mail = new PHPMailer();
        $mail->CharSet = 'utf-8';
        $mail->From = $sender;
        $mail->FromName = $sender;
        $mail->addAddress($reciever);
        $mail->addAttachment(SeznamyUcastnikuModelConfig::PDF_FOLDER . $file);
        $mail->Subject = $subject;
        $mail->Body = $message;

        return $mail->send();
    }
}