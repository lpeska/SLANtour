<?php

class LetakyViewEdit implements ILetakyModelEditObserver
{
    /**
     * @var LetakyModel
     */
    private $model;

    /**
     * @param $model LetakyModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerEditObserver($this);
    }

    // PUBLIC METHODS ********************************************************************
    //todo: tahle metoda by mela byt v samostatnem view, ale kvuli 1 metode jsem nove nedelal, kdyz se jich objevi vic, muze se vytvborit dalsi view
    public static function loginErr()
    {
        LetakyUtils::redirect("/admin/index.php?typ=logout");
    }

    private static function htmlHead()
    {
        $out = "";

        $out .= "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>\n";
        $out .= "<html>\n";
        $out .= "   <head>\n";
        $out .= "       <title>" . LetakyModel::$core->show_nazev_modulu() . " | Administrace systému RSCK</title>\n";
        $out .= "       <meta http-equiv='Content-Type' content='text/html; charset=windows-1250'/>\n";
        $out .= "       <meta name='copyright' content='&copy; Slantour'/>\n";
        $out .= "       <meta http-equiv='pragma' content='no-cache' />\n";
        $out .= "       <meta name='robots' content='noindex,noFOLLOW' />\n";
        $out .= "       <link href='https://fonts.googleapis.com/css?family=Roboto:400,100italic,100,300,300italic,400italic,500,500italic,700,700italic&subset=latin,latin-ext' rel='stylesheet' type='text/css'>\n";
        $out .= "       <link rel='stylesheet' type='text/css' href='css/reset-min.css'>\n";
        $out .= "       <link rel='stylesheet' type='text/css' href='./new-menu/style.css' media='all'/>\n";
        $out .= "       <script type='text/javascript' src='./js/jquery-min.js'></script>\n";
        $out .= "       <script type='text/javascript' src='./js/jquery-ui-cze.min.js'></script>\n";
        $out .= "       <script type='text/javascript' src='./classes/letaky/js/letaky.js'></script>\n";
        $out .= "       <script type='text/javascript' src='./js/common_functions.js'></script>\n";
        $out .= "   </head>\n";
        $out .= "   <body>\n";

        return $out;
    }

    private static function htmlFoot()
    {
        $out = "";

        $out .= "</body></html>";

        return $out;
    }

    public function modelEditLetakyChanged()
    {
        $this->letakSettingsEdit();
    }

    // PRIVATE METHODS *******************************************************************
    private function letakSettingsEdit()
    {
        echo $this->head();
        echo $this->editMain();
        echo $this->foot();
    }

    private function head()
    {
        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(LetakyModel::$core->show_all_allowed_moduls()), LetakyModel::$zamestnanec, LetakyModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(LetakyModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function editMain()
    {
        $out = "";

        $out .= $this->letakSettingsHeader();
        
        $out .= $this->serialInfo();


        return $out;
    }

    private function letakSettingsHeader()
    {
        $serialHolder = $this->model->getZajezdHolder();
        $serial = $serialHolder->getSerial();
        
        $out = "";
        $out .= "<form target=\"_blank\" method=\"post\" action=\"?id_serial=".$serial->id."&page=create-pdf\">";
        $out .= "<div class='submenu'>";
        $out .= "   <a class='btn btn-action' href='serial.php?typ=serial_list'>zpìt</a>";
        $out .= "   <input type=\"submit\" value=\"vygenerovat pdf leták\">";
        $out .= "</div>";

        return $out;
    }
    private function sablonaSelect()
    {
        $f = $this->model->getFilter();
        $sablona = $f->getTypSablony();
        $out = "";
        $out .= "   <tr><th colspan='4'>Vybrat šablonu letáku:</th>
            <tr><td colspan='4'><select name=\"typ_sablony\" id=\"sablona\">\n";
        foreach ($this->model->getSablonyList() as $key => $sablona) {
            if($key == $sablona){
                $selected = "selected=\"selected\"";
            }else{
                $selected = "";
            }
            $out .= "<option value=\"$key\" $selected>$sablona</option>";
        }
        $out .= "</select>
            </td></tr>";
        return $out;
    }
    
    private function fotografie($zaskrtnute, $fotografie)
    { 
        $filtr = $this->model->getFilter();
        $exist_foto = $filtr->getFotoIDs();
        $i=0;
        $html = "";
        $checked = "checked=\"checked\"";   
        foreach ($fotografie as $f) {
            if((is_array($exist_foto) and in_array($f->idFoto, $exist_foto)) or ($filtr->getEmpty() and $i < $zaskrtnute)){
                $checked = "checked=\"checked\"";                  
            } else{
               $checked = ""; 
            }
            $html .= "<div class=\"foto float-left\">
                        <input type=\"checkbox\" $checked name=\"checkbox_foto[]\" id=\"checkbox_foto_$f->idFoto\" value=\"$f->idFoto\" /> 
                        <img src=\"".tsFoto::URL_ICO.$f->url."\" title=\"$f->nazevFoto\" alt=\"$f->nazevFoto\" />
                      </div>"; 
            $i++;
        }
        return $html;
    }
    
    private function textoveVstupy()
    { 
        $f = $this->model->getFilter();
        $serialHolder = $this->model->getZajezdHolder();
        $serial = $serialHolder->getSerial();   
        
        $html = "";
        $html .= "<input type=\"checkbox\" ".(($f->getUsePopisek() or $f->getEmpty())? " checked=\"checked\" " : "")." name=\"checkbox_popisek\" id=\"checkbox_popisek\" value=\"1\" /> Zobrazit popisek zájezdu <br/>"; 
        $html .= "<input type=\"checkbox\" ".(($f->getUseProgram())? " checked=\"checked\" " : "")." name=\"checkbox_program\" id=\"checkbox_program\" value=\"1\" /> Zobrazit program zájezdu <br/>"; 
        $html .= "<input type=\"checkbox\" ".(($f->getUseCenaZahrnuje() or $f->getEmpty())? " checked=\"checked\" " : "")." name=\"checkbox_cena_zahrnuje\" id=\"checkbox_cena_zahrnuje\" value=\"1\" /> Zobrazit cena zahrnuje <br/>"; 
        $html .= "<input type=\"checkbox\" ".(($f->getUseCenaNezahrnuje())? " checked=\"checked\" " : "")." name=\"checkbox_cena_nezahrnuje\" id=\"checkbox_cena_nezahrnuje\" value=\"1\" /> Zobrazit cena nezahrnuje <br/>"; 
        $html .= "<input type=\"checkbox\" ".(($f->getUseDalsiSluzby() or $f->getEmpty())? " checked=\"checked\" " : "")." name=\"checkbox_dalsi_sluzby\" id=\"checkbox_dalsi_sluzby\" value=\"1\" /> Zobrazit další služby <br/>"; 
        $html .= "<input type=\"checkbox\" ".(($f->getUseOdjezdovaMista() or $f->getEmpty())? " checked=\"checked\" " : "")." name=\"checkbox_odjezdova_mista\" id=\"checkbox_odjezdova_mista\" value=\"1\" /> Zobrazit odjezdová místa <br/>";         
        $html .= "<input type=\"checkbox\" ".(($f->getUseMezeraHeadery() or $f->getEmpty())? " checked=\"checked\" " : "")." name=\"checkbox_mezery_nadpisy\" id=\"checkbox_mezery_nadpisy\" value=\"1\" /> Použít mezery mezi nadpisy <br/>";  
        $html .= "<input type=\"checkbox\" ".(($f->getVynechatZakladniCenu() or $f->getEmpty())? " checked=\"checked\" " : "")." name=\"checkbox_vynechat_zakladni_cenu\" id=\"checkbox_vynechat_zakladni_cenu\" value=\"1\" /> Vynechat základní cenu z výpisu dalších služeb <br/>";         
          
  
        return $html;
    }
    
    private function zajezdySelect($serial)
    {
        $filtr = $this->model->getFilter();
        $exist_zaj = $filtr->getZajezdyIDs();
        $i=0;
        $zaskrtnute = 1;
        
        $out = "";
        $out .= " <tr><th>Použít<th>Termín <th>Název";
        
        foreach ($serial->zajezdy as $key => $zajezd) {
            if((is_array($exist_zaj) and in_array($zajezd->id, $exist_zaj)) or ($filtr->getEmpty() and $i < $zaskrtnute)){
                $checked = "checked=\"checked\"";                  
            } else{
               $checked = ""; 
            }
            $out .= "   <tr>
                            <td ><input $checked type=\"checkbox\" class=\"checkbox_zajezdy\" name=\"checkbox_zajezdy[]\" id=\"checkbox_zajezd_$zajezd->id\" value=\"$zajezd->id\"/>                             
                            <td >".CommonUtils::czechDate($zajezd->terminOd)." - ".CommonUtils::czechDate($zajezd->terminDo)."
                            <td >$zajezd->nazevZajezdu   
                        </tr>";
            $i++;
        }
        if(sizeof((array)$serial->zajezdy)==0){
            //neexistuje zadny vhodny zajezd
            $out .= "<tr><td colspan=\"3\" class=\"err\"><h2 >Žádný zájezd nebyl nalezen!</h2>";
        }
        return $out;
    }
    

    private function sluzbySelect($serial)
    {
        $filtr = $this->model->getFilter();
        $exist_sluz = $filtr->getSluzbyIDs();
        
        $out = "";
        $sluzby = array();
        $out .= " <tr><th>Použít<th>Název Služby<th>Pozn.
                <tr><td>";
        foreach ($serial->zajezdy as $key => $zajezd) {
            foreach ($zajezd->sluzby as $key => $sluzba) {
                if((is_array($exist_sluz) and in_array($sluzba->id_cena, $exist_sluz)) or $filtr->getEmpty()){
                    $checked = "checked=\"checked\"";                  
                } else{
                   $checked = ""; 
                }
                $s = array(
                    "<input $checked type=\"checkbox\"  class=\"checkbox_ceny\" value=\"$sluzba->id_cena\" name=\"checkbox_sluzby[]\" id=\"checkbox_sluzba_$sluzba->id_cena\" />",
                    "$sluzba->nazev_ceny",
                    ""
                );
                if($sluzba->zakladni){
                    $s[2].= "základní";
                }else{
                    $s[2].= $sluzba->getNazevTyp();
                }
                $sluzby[$sluzba->id_cena] = implode("<td>", $s);
            }            
        }
        $out .= implode("<tr><td>", $sluzby);
        
        if(sizeof((array)$sluzby)==0){
            //neexistuje zadny vhodny zajezd
            $out .= "<tr><td colspan=\"3\" class=\"err\"><h2 >Žádné služby nebyly nalezeny!</h2>";
        }
        
        return $out;
    }

    private function slevySelect($serial)
    {
        $filtr = $this->model->getFilter();
        $exist_slev = $filtr->getSlevyIDs();
        
        $out = "";
        $slevy = array();
        $out .= " <tr><th>Použít<th>Název Slevy<th>Výše slevy
                <tr><td>";
        foreach ($serial->zajezdy as $key => $zajezd) {
            foreach ($zajezd->slevy as $key => $sleva) {
                if((is_array($exist_slev) and in_array($sleva->id_slevy, $exist_slev)) or $filtr->getEmpty()){
                    $checked = "checked=\"checked\"";                  
                } else{
                   $checked = ""; 
                }
                $s = array(
                    "<input $checked type=\"checkbox\"  class=\"checkbox_slevy\" value=\"$sleva->id_slevy\" name=\"checkbox_slevy[]\" id=\"checkbox_sleva_$sleva->id_slevy\" />",
                    "$sleva->nazev_slevy",
                    "$sleva->velikost_slevy $sleva->mena_slevy"
                );
                $slevy[$sleva->id_slevy] = implode("<td>", $s);
            }            
        }
        $out .= implode("<tr><td>", $slevy);
        
        if(sizeof((array)$slevy)==0){
            //neexistuje zadny vhodny zajezd
            $out .= "<tr><td colspan=\"3\" class=\"err\"><h2 >Žádné slevy nebyly nalezeny!</h2>";
        }
        
        return $out;
    }
    
    
    
    private function nadpisy()
    {
        $f = $this->model->getFilter();
        $serialHolder = $this->model->getZajezdHolder();
        $serial = $serialHolder->getSerial();   
        $help = "<td rowspan=\"6\" valign=\"top\">
                <b>Pøíklady stylù:</b><br/>
                color:red / green / blue / #ffff00 /...<br/>
                font-size: 3.5em;<br/>
                font-weight: normal / bold <br/>
                
            
            </td>";
        $html = "";  
        $html .= "<tr><td>Text pøed nadpisem: <td><input type=\"text\" name=\"text_preheader\" id=\"text_preheader\" class=\"inputText\" value=\"".(($f->getEmpty())? "" : $f->getPreheaderText())."\" /> 
                    dodateèné stylování: <input type=\"text\" name=\"styl_preheader\" id=\"styl_preheader\" value=\"".$f->getPreheaderStyle()."\" /><br/>$help";    
                
        $html .= "<tr><td>Nadpis letáku: <td><input type=\"text\" name=\"text_nadpis_letaku\" id=\"text_nadpis_letaku\" class=\"inputText\" value=\"".(($f->getEmpty())? LetakyUtils::typLetaku($serial) : $f->getHeaderText())."\" /> 
                    dodateèné stylování: <input type=\"text\" name=\"styl_nadpis_letaku\" id=\"styl_nadpis_letaku\" value=\"".$f->getHeaderStyle()."\" /><br/>";    
        
        $html .= "<tr><td>Název seriálu: <td ><input type=\"text\" name=\"text_nazev_serialu\" id=\"text_nazev_serialu\" class=\"inputText\" value=\"".(($f->getEmpty())? $serial->getNazev() : $f->getNazevSerialuText())."\" /> 
                    dodateèné stylování: <input type=\"text\" name=\"styl_nazev_serialu\" id=\"styl_nazev_serialu\" value=\"".$f->getNazevSerialuStyle()."\" /><br/>";    

        $html .= "<tr><td>Highlights: <td><input type=\"text\" name=\"text_highlights\" id=\"text_highlights\" class=\"inputText\" value=\"".(($f->getEmpty())? $serial->dataSerial->highlights : $f->getHighlightsText())."\" /> 
                    dodateèné stylování: <input type=\"text\" name=\"styl_highlights\" id=\"styl_highlights\" value=\"".$f->getHighlightsStyle()."\" /><br/>";    
        
        $html .= "<tr><td>Datum zájezdu: <td><input type=\"text\" name=\"text_datum\" id=\"text_datum\" class=\"inputText\" value=\"".(($f->getEmpty())? "" : $f->getDatumText())."\" /> 
            dodateèné stylování: <input type=\"text\" name=\"styl_datum\" id=\"styl_datum\" value=\"".$f->getDatumStyle()."\" /><br/>";    
        
        $html .= "<tr><td>Cena zájezdu: <td ><input type=\"text\" name=\"text_cena\" id=\"text_cena\" class=\"inputText\" value=\"".(($f->getEmpty())? "" : $f->getCenaText())."\" /> 
            dodateèné stylování: <input type=\"text\" name=\"styl_cena\" id=\"styl_cena\" value=\"".$f->getCenaStyle()."\" /><br/>";    
        
        return $html;        
    }
    
    private function jsArrays(){
        $serialHolder = $this->model->getZajezdHolder();
        $serial = $serialHolder->getSerial();
        $f = $this->model->getFilter();
        $zajezdy = array();
        $ceny = array();
        $cenyRaw = array();
        $cenyMena = array();
        
        $slevy_exists = array();
        $slevy_vyse = array();
        $slevy_calculate = array();
        foreach ($serial->zajezdy as $zajezd) {
            if($zajezd->terminOd != $zajezd->terminDo){
                $termin = CommonUtils::czechDate($zajezd->terminOd,0)."- ". CommonUtils::czechDate($zajezd->terminDo,1);
            }else{
                $termin = CommonUtils::czechDate($zajezd->terminDo,1);
            }
            $termin = str_replace(". ", ".", $termin);
            if($zajezd->nazevZajezdu !=""){
                $termin = $zajezd->nazevZajezdu.", ".$termin;
            }
            $zajezdy[] = "z_$zajezd->id : \"$termin\" ";
            foreach ($zajezd->sluzby as $sluzba) {
                $ceny[] = "z_".$zajezd->id."_".$sluzba->id_cena." : \"" . CommonUtils::formatPrice($sluzba->castka,0,".") ."<span class='mena'> ".$sluzba->mena. "</span>\" ";
                $cenyRaw[] = "z_".$zajezd->id."_".$sluzba->id_cena." :$sluzba->castka ";
                $cenyMena[] = "z_".$zajezd->id."_".$sluzba->id_cena." : \"$sluzba->mena\" ";
            }
            foreach ($zajezd->slevy as $sleva) {
                $slevy_exists[] = "z_".$zajezd->id."_".$sleva->id_slevy." : true";
                $slevy_vyse[] = "z_".$zajezd->id."_".$sleva->id_slevy." : \"" . $sleva->velikost_slevy . " " . $sleva->mena_slevy . "\" ";
                if($sleva->velikost_slevy > 3 and $sleva->sleva_staly_klient == 0 ){
                    $slevy_calculate[] = "z_".$zajezd->id."_".$sleva->id_slevy." : true ";
                }
                
            }
        }
        $datum_zajezdu = "var dataZajezdu = {\n".implode(",\n", $zajezdy)."\n};";
        $ceny_zajezdu = "var cenyZajezdu = {\n".implode(",\n", $ceny)."\n};\n
            var cenyZajezduRaw = {\n".implode(",\n", $cenyRaw)."\n};\n 
            var cenyZajezduMena = {\n".implode(",\n", $cenyMena)."\n};";
       
        $sl_ex = "var slevyExists = {\n".implode(",\n", $slevy_exists)."\n};";
        $sl_v = "var slevyVyse = {\n".implode(",\n", $slevy_vyse)."\n};";
        $sl_calc = "var slevyPouzit = {\n".implode(",\n", $slevy_calculate)."\n};";
        if(!$f->getEmpty()){
            $default_cena = "var defaultCena = \"".$f->getCenaText()."\"; ";
            $default_datum = "var defaultZajezd = \"".$f->getDatumText()."\"; ";
        }
        $html = "
            <script type=\"text/javascript\">
                $ceny_zajezdu
                $datum_zajezdu    
                $default_cena
                $default_datum    
                $sl_ex
                $sl_v
                $sl_calc
            </script>
            ";
        return $html;
    }
    private function footerText(){
        $serialHolder = $this->model->getZajezdHolder();
        $serial = $serialHolder->getSerial();
        $f = $this->model->getFilter();
        
        $html = "<tr><th colspan='3'>Poznámky:</tr>";
        $html .= "<tr><td colspan='3'><textarea name=\"text_footer\">";
        if($f->getEmpty()){
            $html .= "Podrobné informace o zájezdu naleznete na: ";
            $html .= "<a href=\"https://www.slantour.cz/zajezdy/zobrazit/".$serial->dataSerial->nazev_web."\">slantour.cz/zajezdy/zobrazit/".$serial->dataSerial->nazev_web."</a>";                          
        }else{
            $html .= $f->getFooterText();
        }
        $html .= "</textarea>";
        return $html;

    }
    private function serialInfo()
    {
        
        $serialHolder = $this->model->getZajezdHolder();
        $serial = $serialHolder->getSerial();
//print_r($serial);
        $out = "";

        $out .= $this->jsArrays();
        $out .= "<table class='list'>";
        $out .= "      <tr><td valign=\"top\" style=\"width:60%\">
            <table class=\"list\">";
        
        $out .= "   <tr><th colspan='4' class='header header-blue align-left'>$serial->nazev</th></tr>";
        $out .= "   <tr><th colspan='4'>Nadpisy na letáku</td></tr>";
        $out .= $this->nadpisy();
        $out .= $this->sablonaSelect();
        $out .= "  <tr><th colspan='4'>Vybrat zájezd/zájezdy   ";
        $out .= $this->zajezdySelect($serial);      
        $out .= "  <tr><th colspan='4'>Vybrat služby  ";        
        $out .= $this->sluzbySelect($serial);          
        $out .= "<tr><th colspan='4'>Použít textové vstupy:</tr>";
        $out .= "<tr><td colspan='4'>".$this->textoveVstupy()."</tr>";        
        $out .= "</table>";
        
        $out .= "      <td valign=\"top\">
            <table class=\"list\">";

        $out .= "<tr><th colspan='3'>Vybrat fotografie:</tr>";
        $out .= "<tr><td colspan='3'>".$this->fotografie(3, $serial->foto)."</tr>";
        $out .= $this->slevySelect($serial); 
        $out .= $this->footerText();
        
        $out .= "</table>";

        $out .= "</table>";
        return $out;
    }

}
