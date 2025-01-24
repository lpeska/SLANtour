<?php

class FakturaDisplayer
{

    private $id_faktury;
    private $dataFaktura;
    private $dataPolozky;
    private $dataVystavil;
    private $dataCentralniData;


    function __construct($id_faktury)
    {
        $this->id_faktury = $id_faktury;
        $this->dataFaktura = FakturaDAO::dataFaktura($id_faktury);
        $this->dataPolozky = FakturaDAO::dataPolozky($id_faktury);
        $this->dataVystavil = FakturaDAO::dataVystavil($id_faktury);
        $this->dataCentralniData = FakturaDAO::dataCentralniData();

    }

    function getPolozky()
    {
        $lenght = count((array)$this->dataPolozky);


        $html = "<table class=\"solidBorder\" cellpadding=\"0\" cellspacing=\"8\" style=\"border:1px solid black;font-size:1.2em;border-collapse: collapse;margin:8px;\" width=\"810\"><tr>
                    <th>Název<th>Jedn. cena<th>DPH<th>Množství<th>Celkem
                </tr>";

        foreach ($this->dataPolozky as $value) {
            if ($value->pocitat_dph == "snizena") {
                $sazba = $this->dataFaktura->dph_snizene . " %";
            } else if ($value->pocitat_dph == "zakladni") {
                $sazba = $this->dataFaktura->dph_zakladni . " %";
            } else {
                $sazba = "";
            }
            $html .= "<tr>	
                          <td >" . $value->nazev_polozky . "</td>
                          <td  align=\"right\">" . $value->jednotkova_cena . "</td>
                          <td  align=\"center\">" . UtilsTS::text_transform($value->pocitat_dph) . " $sazba</td>
                          <td  align=\"right\">" . $value->pocet . "</td>
                          <td align=\"right\">" . $value->celkem . " </td>								
                     </tr>";
        }
        $html .= "</table>
           <table class=\"solidBorder\" cellpadding=\"0\" cellspacing=\"8\" style=\"border:1px solid black;font-size:1.2em;border-collapse: collapse;margin:8px;\" width=\"810\"><tr>
                    <td><b>Celkem: </b><td align=\"right\"><b>" . $this->dataFaktura->celkova_castka . " " . $this->dataFaktura->mena . " </b>
            </table>";
        return $html;
    }

    function getVystavil()
    {
        $html = "<table class=\"solidBorder\" cellpadding=\"0\" cellspacing=\"8\" style=\"border:1px solid black;font-size:1.2em;border-collapse: collapse;margin:8px;\" width=\"810\"><tr>
                    <td><b>Vystavil: </b>" . $this->dataVystavil->jmeno . " " . $this->dataVystavil->prijmeni . "</td>
                    
                 </tr>
                 </table>";
        return $html;
    }

    function getFakturaInfo()
    {
        if ($this->dataFaktura->typ_faktury == "zaloha") {
            $text_nadpisu = "ZÁLOHOVÁ FAKTURA";
        } else if ($this->dataFaktura->typ_faktury == "dobropis") {
            $text_nadpisu = "DOBROPIS";
        } else {
            $text_nadpisu = "DAÒOVÝ DOKLAD";
        }
        $html = "<table class=\"solidBorder\" cellpadding=\"0\" cellspacing=\"8\" style=\"border:1px solid black;font-size:1.2em;border-collapse: collapse;margin:8px;\" width=\"810\">
            <tr>
                <td colspan=\"2\">
   			<h1>$text_nadpisu</h1>
                </td>
                <td><b>Èíslo Faktury:</b>
                <td>" . $this->dataFaktura->cislo_faktury . "
            </tr>
            <tr>
                <td><b>Typ dokladu:</b>
                <td>" . UtilsTS::text_transform($this->dataFaktura->typ_dokladu) . "
                <td><b>Kód objednávky / Variabilní symbol:</b>
                <td>" . $this->dataFaktura->id_objednavka . "
            </tr>     
            <tr>
                <td colspan=\"2\" valign=\"top\">" . $this->dataFaktura->dodavatel_text . " <br/><br/> <span style=\"color:white;\">.</span>
                <td colspan=\"2\" valign=\"top\">" . $this->dataFaktura->prijemce_text . "  <br/><br/><span style=\"color:white;\">.</span>
            </tr>  
            <tr>
                <td><b>Mìna:</b>
                <td>" . UtilsTS::text_transform($this->dataFaktura->mena) . "
                <td><b>Datum vystavení:</b>
                <td>" . UtilsTS::change_date_en_cz($this->dataFaktura->datum_vystaveni) . "
            </tr>
            <tr>
                <td><b>Kurz:</b>
                <td>" . $this->dataFaktura->kurz . "
                <td><b>Datum zdaò plnìní:</b>
                <td>" . UtilsTS::change_date_en_cz($this->dataFaktura->datum_zdanitelneho_plneni) . "
            </tr>
            <tr>
                <td><b>Zpùsob úhrady:</b>
                <td>" . UtilsTS::text_transform($this->dataFaktura->zpusob_uhrady) . "
                <td><b>Datum splatnosti:</b>
                <td>" . UtilsTS::change_date_en_cz($this->dataFaktura->datum_splatnosti) . "
            </tr>
        </table>  
        <table class=\"solidBorder\" cellpadding=\"0\" cellspacing=\"8\" style=\"border:1px solid black;font-size:1.2em;border-collapse: collapse;margin:8px;\" width=\"810\">
            <tr><td><b>Poznámka:</b>
             " . $this->dataFaktura->text_faktury . " <br/><br/> <span style=\"color:white;\">.</span>
        </table>";
        return $html;
    }

    function getHeader()
    {
        /* $html = "<tr>	   
          <td valign=\"left\">
          <h2>SLAN tour s.r.o.</h2>
          <p style=\"font-size:1.1em;\">
          Wilsonova 597, Slaný, 274 01<br/>
          Firma zapsána v OR u MS v Praze, oddíl C, vložka 51266<br/>
          tel.: 312520084, 312523836<br/>
          IÈO: 25118889 DIÈ: CZ25118889<br/>
          Bankovní spojení: KB Kladno, 19-6706930207 / 0100<br/>
          e-mail: info@slantour.cz, web: www.slantour.cz<br/>
          </p>
          </td><tr>"; */
        $html = "<table style=\"font-size:1.2em;border-collapse: collapse;margin:8px;\"><tr>	   
		 <td align=\"left\">
                    <img src='https://slantour.cz/foto/full/14628-logo-slantour.jpg' width='150' height='83' />
                 <td>  
                    " . $this->dataCentralniData["nazev_spolecnosti"] . " <br/>
                    " . $this->dataCentralniData["adresa"] . "<br/>
                    " . $this->dataCentralniData["email"] . "<br/>
                    " . $this->dataCentralniData["web"] . "  <br/>  
                 <td>
                    IÈO: " . $this->dataCentralniData["ico"] . " <br/>
                    DIÈ: " . $this->dataCentralniData["dic"] . "<br/>
                    Tel.: " . $this->dataCentralniData["telefon"] . "<br/>
                    Bankovní spojení: " . $this->dataCentralniData["bankovni_spojeni"] . "<br/>

		 </td></tr></table><hr/>";
        return $html;
    }

    function getFooter()
    {
        $html = "<table><tr>	   
		 <td>
                    " . $this->dataFaktura->pata_faktury . "
                 <tr>
                 <td align=\"center\"> 
                 <hr/>
                    <i>Vytištìno z programu RSCK</a>
                 </table>";
        return $html;
    }

}

?>
