<?php

//note namichane model a view dohromady
class HlaseniPojistovneDisplayer
{
    const ZALOHA_DATE_TRESHOLD = 3;
    const BEZ_STORNOVANYCH_OBJEDNAVEK = 1;
    private $id_objednavka;
    private $dataObjednavajici;
    private $dataObjednavka;
    private $dataPlatby;
    private $dataProdejce;
    private $dataProvize;
    private $dataSleva;
    private $dataSluzby;
    private $dataStaticDescription;
    private $dataVystavil;
    private $dataZajezd;
    private $dataCentralniData;
    private $dataZaloha;
    private $dataDoplatek;

    /**
     * @var ObjednavkaEnt
     */
    private $dataObjednavkaGlobal;

    function __construct($params, $type){

        //note objednavka z globalniho modelu
        $this->dataObjednavky = ObjednavkaDAO::dataObjednavkaList($params,$type, HlaseniPojistovneDisplayer::BEZ_STORNOVANYCH_OBJEDNAVEK);
        $this->dataObjednavkyAgregovane = $this->dataObjednavky["agregovane"];
        unset($this->dataObjednavky["agregovane"]);
        
        $this->dataCentralniData = ObjednavkaDAO::dataCentralniData();
    }

    //region COMPUTE *******************************************************************************************

   
    //endregion


    public function getRowsObjednavka()
    {
        $html = "
            <h3>Seznam Objedn·vek</h3>
            <table cellpadding=\"0\" cellspacing=\"8\"  class=\"list\">
            
            <tr>
                <th>ID objedn·vky</th>
                <th>Seri·l</th>
                <th>Z·jezd</th>
                <th>Objedn·vajÌcÌ</th>
                <th>PoËet osob</th>
                <th>Objedn·vka ze dne</th>
                <th>Aktu·lnÌ platba</th>
                <th>Celkov· cena objedn·vky</th>
            </tr>";
        foreach ($this->dataObjednavky as $objednavka) {
            if($objednavka->aktualni_platba > $objednavka->celkova_cena){
                $color = "blue";
            }else if($objednavka->aktualni_platba == $objednavka->celkova_cena){
                $color = "green";
            }else if($objednavka->aktualni_platba > 0){
                $color = "orange";
            }else if($objednavka->aktualni_platba <= 0){
                $color = "red";
            }
            $html .= "
                <tr>
                    <td><a href=\"/admin/objednavky.php?idObjednavka=".$objednavka->id_objednavka."\">".$objednavka->id_objednavka."</a>
                    <td>".$objednavka->zajezd->constructNazev()."
                    <td>".UtilsTS::change_date_en_cz($objednavka->zajezd->terminOd)." - ".UtilsTS::change_date_en_cz($objednavka->zajezd->terminDo)."
                    <td>".$objednavka->klient."   
                    <td>".$objednavka->pocet_osob."        
                    <td>".  UtilsTS::czechDate($objednavka->datum_rezervace)."       
                    <td class=\"align_right $color\">".$objednavka->aktualni_platba." KË                   
                    <td class=\"align_right $color\">".$objednavka->celkova_cena." KË
                    
                </tr>   
            ";            
        }
        $html .= "</table>";
        return $html;
    }    

    public function getHeader($type)
    {
        $html = "<h3>Hl·öenÌ pro pojiöùovnu</h3>";
        return $html;
    }

    public function getSumValues()
    {
        $html = "<table cellpadding=\"0\" cellspacing=\"8\" class=\"list\">
            <tr>
                <th>Suma poËtu osob </th>
                <th>Suma aktu·lnÌch plateb</th>
                <th>Suma celkov˝ch cen</th>
            </tr>
            <tr>
                <th>".$this->dataObjednavkyAgregovane["pocet_osob"]."</th>
                <th>".$this->dataObjednavkyAgregovane["aktualni_platba"]." KË</th>
                <th>".$this->dataObjednavkyAgregovane["celkova_castka"]." KË</th>
            </tr>
        </table>  ";
        return $html;
    }     
    

    public function getFooter()
    {
        /* $html = "<tr>	   
          <td valign=\"left\">
          <h2>SLAN tour s.r.o.</h2>
          <p style=\"font-size:1.1em;\">
          Wilsonova 597, Slan˝, 274 01<br/>
          Firma zaps·na v OR u MS v Praze, oddÌl C, vloûka 51266<br/>
          tel.: 312520084, 312523836<br/>
          I»O: 25118889 DI»: CZ25118889<br/>
          BankovnÌ spojenÌ: KB Kladno, 19-6706930207 / 0100<br/>
          e-mail: info@slantour.cz, web: www.slantour.cz<br/>
          </p>
          </td><tr>"; */
        $html = "<tr>	   
		 <td valign=\"left\">
                    <h2>" . $this->dataCentralniData["nazev_spolecnosti"] . "</h2>
                    <p style=\"font-size:1.1em;\">
                        " . $this->dataCentralniData["adresa"] . "<br/>
			" . $this->dataCentralniData["firma_zapsana"] . "<br/>
			tel.: " . $this->dataCentralniData["telefon"] . "<br/>
			I»O: " . $this->dataCentralniData["ico"] . " DI»: " . $this->dataCentralniData["dic"] . "<br/>
			BankovnÌ spojenÌ: " . $this->dataCentralniData["bankovni_spojeni"] . "<br/>
			e-mail: " . $this->dataCentralniData["email"] . ", web: " . $this->dataCentralniData["web"] . "<br/>	    
                    </p> 
		 </td><tr>";
        return $html;
    }

}
