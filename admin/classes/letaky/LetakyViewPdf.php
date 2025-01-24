<?php

class LetakyViewPdf implements ILetakyModelPdfObserver
{
    const TYP_SLEVA = 3;
    const TYP_ODJEZDOVE_MISTO = 5;
    const SABLONA_ZAKLADNI = 0;
    const SABLONA_VODOROVNE = 1;
    const SABLONA_TERMINY = 2;
    const SABLONA_VODOROVNE_TERMINY = 3;
    
    /**
     * @var LetakyModel
     */
    private $model;
    /**
     * @var string[][] retezce (popisky) v ruznych jazycich - cs, en
     */
    private $langStrings;

    /**
     * @param $model LetakyModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerPdfObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelPdfLetakyChanged()
    {
        $this->createPdfLetaky();
    }


    // PRIVATE METHODS *******************************************************************

    private function createPdfLetaky()
    {
        
        $html = "";
        $html = "<html><head><link rel=\"stylesheet\" type=\"text/css\" href=\"./classes/letaky/css/letaky_default.css\" media=\"all\"/></head>";       
        $html .= $this->headerLetaky();
        $html .= $this->letakBody();
        $html .= $this->footer();

        //echo $html;
        $this->model->generatePdf($html);
    }


    private function headerLetaky()
    {
        $f = $this->model->getFilter();
        
        $html = "
            ";
        $html .= "<body>";
        $html .= "<table class=\"layout\" cellpadding='0' cellspacing='2'>";
        $html .= "  <tr>";
        $html .= "      <th align=\"left\" valign=\"top\"><h6 style=\"".$f->getPreheaderStyle()."\">".$f->getPreheaderText()."</h6>";
        $html .= "      <th align=\"right\" valign=\"bottom\"><h5 style=\"".$f->getHeaderStyle()."\">".$f->getHeaderText()."</h5>";        
        $html .= "      <th align=\"right\"><img src='https://slantour.cz/foto/full/14628-logo-slantour.jpg' width='150' height='83' />";        
        $html .= "      </th>";
        $html .= "  </tr>";        


        return $html;
    }
    //vyfiltruje pouze zaznamy list, ktere se nacahzeji v IDs
    public static function filterFotografie($list, $IDs){
        $out = array();
        foreach ($list as $key => $foto) {
            if(in_array($foto->idFoto, $IDs)){
                $out[] = $foto;
            }
        }
        return $out;
    }

    //vyfiltruje pouze zaznamy list, ktere se nacahzeji v IDs
    public static function filterZajezdy($list, $zajezdIDs, $sluzbyIDs, $slevyIDs){
        $out = array();
        foreach ($list as $key => $zajezd) {
            if(in_array($zajezd->id, $zajezdIDs)){
                $sl = array();
                foreach ($zajezd->sluzby as $sluzba) {
                    if(in_array($sluzba->id_cena, $sluzbyIDs)){
                        $sl[] = $sluzba;
                    }
                }
                $zajezd->sluzby = $sl;
                
                $slevy = array();
                foreach ($zajezd->slevy as $sleva) {
                    if(in_array($sleva->id_slevy, $slevyIDs)){
                        $slevy[] = $sleva;
                    }
                }
                $zajezd->slevy = $slevy;
                
                $out[] = $zajezd;
            }
        }
        return $out;
    }    
    
    private static function createList($input){
        $input = strip_tags($input);
        $input = explode("\n", $input);
        $ul = array();
        foreach ($input as $li) {
            if(trim($li)!=""){
                $ul[] = trim($li);
            }
        }
        return "<ul><li>".implode("<li>", $ul)."</ul>";
    }
    
    private function letakBody()
    {
        $serialHolder = $this->model->getZajezdHolder();
        $serial = $serialHolder->getSerial();
        $f = $this->model->getFilter();
        
        $zajezdy = self::filterZajezdy($serial->zajezdy,$f->getZajezdyIDs(),$f->getSluzbyIDs(), $f->getSlevyIDs());
        $fotky = self::filterFotografie($serial->foto, $f->getFotoIDs());
        
        $zajezd = reset($zajezdy);
        $zakladni_sluzba = reset($zajezd->sluzby);
        
        $serial->dataSerial->cena_zahrnuje = self::createList($serial->dataSerial->cena_zahrnuje);
        $serial->dataSerial->cena_nezahrnuje = self::createList($serial->dataSerial->cena_nezahrnuje);

        $sablonaID = $f->getTypSablony();

        
        $seznamTerminu = "";
        if($sablonaID == self::SABLONA_TERMINY or $sablonaID == self::SABLONA_VODOROVNE_TERMINY){
            $seznamTerminu .= "          <tr><th align=\"left\" >Termíny zájezdu:";
            $seznamTerminu .= "          <tr><td align=\"left\" >
                <table class=\"terminy\">";
            foreach ($zajezdy as $zajezd) {
                $sluzba = reset($zajezd->sluzby);            
                $seznamTerminu .= "<tr><td>";
                if($zajezd->nazevZajezdu != ""){
                    $seznamTerminu .= $zajezd->nazevZajezdu;
                }else if($zajezd->terminOd != $zajezd->terminDo){
                    $seznamTerminu .= CommonUtils::czechDate($zajezd->terminOd,0)."- ". CommonUtils::czechDate($zajezd->terminDo,1);
                }else{
                    $seznamTerminu .= CommonUtils::czechDate($zajezd->terminDo,1);
                }
                $seznamTerminu .= "<td class=\"ceny_terminu\" valign=\"top\">";                                
                $seznamTerminu .= CommonUtils::formatPrice($sluzba->castka,0,".") ." ".$sluzba->mena;
            }
            $seznamTerminu .= "</table>";
        }
        
        $mezera = "<tr>";
        if($f->getUseMezeraHeadery()){
            $mezera = "  <tr><td colspan='3' style=\"height:15px;\"> <tr>";
        }
        $nadpisyZajezd = "";
        //nadpisy se nepouzivaji, pokud chceme zobrazit seznam termínù
        if($sablonaID == self::SABLONA_ZAKLADNI or $sablonaID == self::SABLONA_VODOROVNE){
            $nadpisyZajezd .= $mezera;
            $nadpisyZajezd .= "      <th colspan='3' align=\"left\" class=\"termin\">";
            $nadpisyZajezd .= "      <h3 style=\"".$f->getDatumStyle()."\">".$f->getDatumText()."</h3><br/>";
            $nadpisyZajezd .= "      </th>";
            $nadpisyZajezd .= "  </tr>";         
            $nadpisyZajezd .= $mezera;
            $nadpisyZajezd .= "      <th colspan='3' align=\"right\" class=\"cena\">";
            $nadpisyZajezd .= "      <h4 style=\"".$f->getCenaStyle()."\">".$f->getCenaText()."</span></h4><br/>";
            $nadpisyZajezd .= "      </th>";
            $nadpisyZajezd .= "  </tr>";             
        }        
    
        $nadpisy = "  <tr>";
        $nadpisy .= "      <th colspan='3' class=\"nazev\">";
        $nadpisy .= "      <h1 style=\"".$f->getNazevSerialuStyle()."\">".$f->getNazevSerialuText()."</h1><br/>";
        $nadpisy .= "      </th>";
        $nadpisy .= "  </tr>";
        $nadpisy .= $mezera;
        $nadpisy .= "      <th colspan='3' class=\"highlights\">";
        $nadpisy .= "      <h2 style=\"".$f->getHighlightsStyle()."\">".$f->getHighlightsText()."</h2><br/>";
        $nadpisy .= "      </th>";
        $nadpisy .= "  </tr>";  
        $nadpisy .= $nadpisyZajezd ;
        
        
        
        if($f->getUsePopisek()){
            $popisek = "          <tr><th align=\"left\" >" . $serial->dataSerial->popisek. "";
        }
        
        if($f->getUseProgram()){
            $program = "          <tr><th align=\"left\" >Program zájezdu:";
            $program .= "          <tr><td align=\"left\" >" . $serial->dataSerial->program_zajezdu. "";
        }
        
       
        
        if($f->getUseCenaZahrnuje()){
            $cenaZahrnuje = "          <tr><th align=\"left\" >Cena zahrnuje:";
            $cenaZahrnuje .= "          <tr>";
            $cenaZahrnuje .= "              <td width=\"500\" valign=\"top\" style=\"padding-left:10px;\">";
            $cenaZahrnuje .= "              " . $serial->dataSerial->cena_zahrnuje. "";
            $cenaZahrnuje .= "              </td>";
        }
        
        if($f->getUseCenaNezahrnuje()){
            $cenaNezahrnuje = "          <tr><th align=\"left\" >Cena nezahrnuje:";
            $cenaNezahrnuje .= "          <tr>";
            $cenaNezahrnuje .= "              <td width=\"500\" valign=\"top\" style=\"padding-left:10px;\">";
            $cenaNezahrnuje .= "              " . $serial->dataSerial->cena_nezahrnuje. "";
            $cenaNezahrnuje .= "              </td>";
        }  
        
        if($f->getUseDalsiSluzby()){
            $dalsiSluzby =            $this->dalsiSluzby($zajezd, $zakladni_sluzba);
        }
        
        if($f->getUseOdjezdovaMista()){
            $odjezdovaMista =            $this->odjezdoveMista($zajezd);
        }
                                                
        if($sablonaID == self::SABLONA_ZAKLADNI){
            $html = "$nadpisy";
            $html .= "  <tr><th align=\"left\" valign=\"top\" colspan='2'>";
            $html .= "      <table>
                                 $popisek $program $cenaZahrnuje $cenaNezahrnuje $dalsiSluzby $odjezdovaMista
                            </table>";
            $html .= "  <td align=\"right\" valign=\"top\"  width=\"300\" >
                                ".$this->fotografie(300, -1, 5, $fotky)."
                        </tr>"; 
            $html .= "  <tr><td colspan=\"3\">". $f->getFooterText();

        } else if($sablonaID == self::SABLONA_VODOROVNE){   
            $html = "$nadpisy";
            $html .= "  <tr><th align=\"center\" valign=\"top\" colspan='3'>
                    ".$this->fotografie(-1, 175, 5, $fotky, "&nbsp;")."
                ";
            $html .= "  <tr><th align=\"left\" valign=\"top\" colspan='3'>";
            $html .= "      <table>
                                 $popisek $program $seznamTerminu $cenaZahrnuje $cenaNezahrnuje $dalsiSluzby $odjezdovaMista
                                 <tr><td>". $f->getFooterText()."
                            </table>";        
            
        } else if($sablonaID == self::SABLONA_TERMINY){  
            $html = "$nadpisy";
            $html .= "  <tr><th align=\"left\" valign=\"top\" colspan='2'>";
            $html .= "      <table>
                                 $popisek $program $seznamTerminu $cenaZahrnuje $cenaNezahrnuje
                            </table>";
            $html .= "  <td align=\"right\" valign=\"top\"  width=\"250\" >
                                ".$this->fotografie(250, -1, 5, $fotky)."
                        </tr>"; 
            $html .= "  <tr><td colspan=\"3\">". $f->getFooterText();     
            
        } else if($sablonaID == self::SABLONA_VODOROVNE_TERMINY){   
            $html = "$nadpisy";
            $html .= "  <tr><th align=\"center\" valign=\"top\" colspan='3'>
                    ".$this->fotografie(-1, 175, 5, $fotky, "&nbsp;")."
                ";
            $html .= "  <tr><th align=\"left\" valign=\"top\" colspan='3'>";
            $html .= "      <table>
                                 $popisek $program $seznamTerminu $cenaZahrnuje $cenaNezahrnuje 
                                 <tr><td>". $f->getFooterText()."
                            </table>";        
        }   
        return $html;
    }
    

    private function fotografie($width,$height, $max, $fotografie, $text="")
    { 
        $i=0;
        $html = "";
        foreach ($fotografie as $f) {
            if($i == $max){
                break;                
            } 
            $par = "";
            if($width>0){
                $par .= " width=\"$width\"";
            }
            if($height>0){
                $par .= " height=\"$height\"";
            }
            $html .= "<img src=\"".tsFoto::URL_FULL.$f->url."\" $par class=\"foto\" /> $text"; 
            $i++;
        }
        return $html;
    }
    
    private function dalsiSluzby($zajezd, $zakladni_sluzba)
    {  
        $f = $this->model->getFilter();        
        $last_typ = "";    
        $html = "<tr><th align=\"left\">
            <table>";
        $slevyAlreadyDisplayed = false;
        reset($zajezd->sluzby);
        reset($zajezd->slevy);
        foreach ($zajezd->sluzby as $key => $sluzba) {
            if(($sluzba->id_cena != $zakladni_sluzba->id_cena or !$f->getVynechatZakladniCenu()) and $sluzba->typ != self::TYP_ODJEZDOVE_MISTO){
                if($last_typ != $sluzba->typ){
                    $html .= "  <tr><th colspan='2' align=\"left\">".$sluzba->getNazevTyp()."";
                    $last_typ = $sluzba->typ;
                    if($sluzba->typ == self::TYP_SLEVA and sizeof((array)$zajezd->slevy) > 0 ){
                        $html .= $this->displaySlevy($zajezd->slevy);
                        $slevyAlreadyDisplayed = true;
                    }
                }                
                $html .= "  <tr><td>$sluzba->nazev_ceny<td align=\"right\" valign=\"top\">".  CommonUtils::formatPrice($sluzba->castka,0,".")." $sluzba->mena ";
            }            
        }
        if(!$slevyAlreadyDisplayed and sizeof((array)$zajezd->slevy) > 0 ){
            $html .= $this->displaySlevy($zajezd->slevy, true);
            $slevyAlreadyDisplayed = true;
        }
        $html .= "  </table>";
        return $html;
    }
  
    private function displaySlevy($slevy, $showHeader = false)
    {  
        $html = "";
        if($showHeader){
            $html .= "  <tr><th colspan='2' align=\"left\">Slevy";
        }
        foreach ($slevy as $key => $sleva) {            
            $html .= "  <tr><td>$sleva->nazev_slevy<td align=\"right\" valign=\"top\">".  CommonUtils::formatPrice($sleva->velikost_slevy,0,".")." $sleva->mena_slevy ";
        }
        return $html;
    }
    
    
    private function odjezdoveMista($zajezd)
    {  
        $mista = array();   
        $html = "<tr><th align=\"left\">Odjezdová místa:
                    <tr><td >
            ";

        reset($zajezd->sluzby);
        foreach ($zajezd->sluzby as $key => $sluzba) {
            if( $sluzba->typ == self::TYP_ODJEZDOVE_MISTO){
                $misto = $sluzba->nazev_ceny;
                if($sluzba->castka >0){
                    $misto .= " (".CommonUtils::formatPrice($sluzba->castka,0,".")." $sluzba->mena)";
                }
                $mista[] = $misto;
            }
        }
        $html .= implode(", \n", $mista);
        return $html;
    }    
            
    private function footer()
    {
        $centralniData = $this->model->getZajezdHolder()->getCentralniData();
        $serialHolder = $this->model->getZajezdHolder();
        $serial = $serialHolder->getSerial();
        $f = $this->model->getFilter();
        
        $html = "";
        
        
        $html .= "</table>";                
        $html .= "
            <div style=\"position:absolute;bottom:10px;width:740px;text-align:center;border: 1px solid grey;font-size:0.85em;\">
                <b>".$centralniData["nazev_spolecnosti"]."</b>,
                ".$centralniData["adresa"] . " <br/>
                tel.: " . $centralniData["telefon"] . ", 
                e-mail: <a href=\"mailto:" . $centralniData["email"] . "\">" . $centralniData["email"] . "</a>,    
                web: <a href=\"https://" . $centralniData["web"] . "\">" . $centralniData["web"] . "</a>,    
                IÈO: " . $centralniData["ico"] . "  
            </div> ";
        $html .= "</body>";
        return $html;
    }
}