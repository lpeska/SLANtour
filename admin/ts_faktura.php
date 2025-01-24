<?php
/**   \file
 * pdf_objednavka.php - hlavni stranka klientske casti systemu
 *    - je na ni provaden reload pø zpracování formuláøù ostatních modulù
 *    - zobrazuje podrobné vyhledávání a tipy na zájezdy
 */
//spusteni prace se sessions
//session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/dataContainers/tsFaktura.php";
require_once "./classes/dataContainers/tsFakturyPolozka.php";
require_once "./classes/dataContainers/tsOsoba.php";

require_once "./classes/ts/faktura_dao.inc.php";
require_once "./classes/ts/faktura_displayer.inc.php";
require_once "./classes/ts/faktura_ts.inc.php";
require_once "./classes/ts/utils_ts.inc.php";


/* MARTIN EMAILY */

require_once "../phpMailer/minimal/class.phpmailer.php";

/**
 * Odesle 1 email
 * @param $sender
 * @param $reciever
 * @param $subject
 * @param $message
 * @param $filePath
 * @return mixed
 */
function sendEmailWithAttachment($sender, $reciever, $subject, $message, $filePath)
{
    $subject = iconv("cp1250", "utf-8", $subject);
    $subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
    $message = iconv("cp1250", "utf-8", $message);
    $mail = new PHPMailer();
    $mail->CharSet = 'utf-8';
    $mail->From = $sender;
    $mail->FromName = $sender;
    $mail->addAddress($reciever);
    $mail->addAttachment(FakturaTS::PDF_FOLDER . $filePath, "faktura.pdf");
    $mail->Subject = $subject;
    $mail->Body = $message;

    return $mail->send();
}

/**
 * Odesle email s pdfkem faktury jako prilohou na adresy v poli $emailsSelected a vrati potvrzeni o odeslani ve formatu JSON, s kterym se dobre pracuje v JS
 * @param $subject
 * @param $message
 * @param $emailsSelected
 * @param $fakturaFilePath
 * @return string
 */
function sendPdfEmails($subject, $message, $emailsSelected, $fakturaFilePath)
{
    if (count((array)$emailsSelected) <= 0)
        $out = null;

    $sender = FakturaTS::EMAIL_SENDER;

    $out = array();
    for ($i = 0; $i < count((array)$emailsSelected); $i++) {
        $e = $emailsSelected[$i];
        $isEmailSend = sendEmailWithAttachment($sender, $e, $subject, $message, $fakturaFilePath);
        $out[] = array("email" => $e, "isSend" => $isEmailSend);
    }

    return json_encode($out);
}

function readFakturaPdfById($idFaktura)
{
    $pdfFaktury = null;
    if ($handle = opendir(FakturaTS::PDF_FOLDER)) {
        while (false !== ($entry = readdir($handle))) {
            $entryDump = explode("_", $entry);
            if ($entryDump[0] == $idFaktura) {
                $pdfFaktury[] = $entry;
            }
        }
        closedir($handle);
    }
    //nejnovejsi nejdrive
    @rsort($pdfFaktury);

    return $pdfFaktury;
}

function createNewFakturaPdf($id)
{
    $ts = new FakturaTS($_GET["id_faktury"]);

    $html = $ts->createHtml();
    define('_MPDF_PATH', '../mpdf/');
    include('../mpdf/mpdf.php');
    $mpdf = new mPDF('cs', 'A4', 7, 'DejaVuSans', 8, 8, 5, 5, 1, 1);

    $mpdf->keep_table_proportions = true;
    $mpdf->allow_charset_conversion = true;
    $mpdf->charset_in = 'windows-1250';
    $stylesheet = file_get_contents('classes/ts/ts_default.css');

    $mpdf->WriteHTML($stylesheet, 1);
    $mpdf->WriteHTML($html, 2);

    $mpdf->Output();

    $dateTime = date("Y-m-d-H-i-s");
    $pathToPdf = FakturaTS::PDF_FOLDER . $id . "_" . $dateTime . ".pdf";
    $mpdf->Output($pathToPdf, 'F');
}

function navAjax()
{
    $idFaktury = $_REQUEST["id_faktury"];
    $cisloFaktury = $_REQUEST["cislo_faktury"];
    $emailsSelected = $_REQUEST["cb-emaily"];
    $fakturaPdfFilePath = $_REQUEST["rb-pdf-faktura"];

    $subject = "Faktura è. $cisloFaktury";
    $message = "Dobrý den, 
    zasíláme Vám fakturu èíslo $cisloFaktury.
    
    Pøejeme Vám pìkný den
    SLAN tour s.r.o.
    Wilsonova 597, Slaný
    info@slantour.cz
    www.slantour.cz";

    $response = sendPdfEmails($subject, $message, $emailsSelected, $fakturaPdfFilePath);

    echo $response;

    exit();
}

function navMain()
{
    $idFaktury = $_GET["id_faktury"];
    $fakturyPdf = readFakturaPdfById($idFaktury);
    $fakturaPdfFilePath = is_array($fakturyPdf) && count((array)$fakturyPdf) > 0 ? $fakturyPdf[0] : $fakturyPdf;

    if (is_null($fakturaPdfFilePath)) {
        //vygenerovat novou fakturu pokud aktualni faktura neexistuje...
        createNewFakturaPdf($idFaktury);
    } else {
        //...nebo zobrazit aktualni pdf faktury
        header('Content-type: application/pdf');
        header("Content-Disposition: inline; filename='" . FakturaTS::PDF_FOLDER . "/$fakturaPdfFilePath'");
        readfile(FakturaTS::PDF_FOLDER . "/$fakturaPdfFilePath");
    }
}

function navNew()
{
    $idFaktury = $_GET["id_faktury"];
    createNewFakturaPdf($idFaktury);
}

function navDeleteFakturaPdf()
{
    @unlink(FakturaTS::PDF_FOLDER . $_REQUEST["pdf_filename"]);

    $url = "faktury.php?id_faktury=" . $_REQUEST['id_faktury'] . "&typ=faktury&pozadavek=edit";
    header("Location: $url");
    exit();
}

function navDeleteAllFakturaPdf()
{
    foreach (glob(FakturaTS::PDF_FOLDER . $_REQUEST["id_faktury"] . "*.pdf") as $filename) {
        @unlink($filename);
    }

    $url = "faktury.php?id_faktury=" . $_REQUEST['id_faktury'] . "&typ=faktury&pozadavek=edit";
    header("Location: $url");
    exit();
}

//controller
switch ($_REQUEST["page"]) {
    case "ajax":
        navAjax();
        break;
    case "create-new-pdf":
        navNew();
        break;
    case "delete-pdf":
        navDeleteFakturaPdf();
        break;
    case "delete-all-pdf":
        navDeleteAllFakturaPdf();
        break;
    default:
        navMain();
        break;
}
?>

<!-- TOTO BYL V PODSTATE UNREACHABLE STATEMENT - V PODMINCE PRO NASLEDUJICI KOD BYLO if($message != "") a posledni radek pred if byl $message = "" -->

<!--    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">-->
<!--    <html lang="cs">-->
<!--    <head>-->
<!--        <title>-->
<!--            SLAN tour | tvorba PDF faktury-->
<!--        </title>-->
<!--        <meta http-equiv="Content-Type" content="text/html; charset=windows-1250"/>-->
<!--        <meta name="Robots" content="noindex, nofollow"/>-->
<!---->
<!--    </head>-->
<!---->
<!--    <body>-->
<!--    <h2>--><?// echo $errorMessage ?><!--</h2>-->
<!--    </body>-->
<!--    </html>-->

/* endregion MARTIN EMAILY */


