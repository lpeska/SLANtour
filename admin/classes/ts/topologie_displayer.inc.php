<?php
//note michani view s modelem dohromady neni moc sikovny
class TopologieDisplayer
{

    private $id_tok_topologie;
    private $dataObjednavajici;
    private $dataTopologie;
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

    function __construct($id_tok_topologie, $klient=0)
    {
        $this->id_tok_topologie = $id_tok_topologie;
        $this->dataPolozky = TopologieDAO::dataPolozky($id_tok_topologie);
        $this->dataTopologie = TopologieDAO::dataTopologie($id_tok_topologie);
        if(!$klient){
            $this->dataVystavil = ObjednavkaDAO::dataVystavil(0);
            $this->dataCentralniData = ObjednavkaDAO::dataCentralniData(); 
        }

    }


    function getPolozky()
    {
        $count = 0;
        $last_row = 0;
        $width = 155;
        $height = 75;
        foreach ($this->dataPolozky as $polozka) {
            $polozka->odjezdova_mista = trim(str_replace(array("odjezdové","odjezdová","místa","místo","odjezd"), "", $polozka->odjezdova_mista));
            
            if($last_row < $polozka->posx){
                $html .= "<tr>";
                $last_row = $polozka->posx;
            }
            if(strpos($polozka->class, "noseat")===false){
                $border = "border2";
                $count ++;
                $text = "<b>$count</b><br/>";
            }else{
                $border = "border0";
                $text = "";
            }
            if($polozka->text!=""){
                $text .= $polozka->text."<br/>";
            }
            if($polozka->prijmeni!=""){
                $text .= "<span style=\"font-size:1.4em; font-weight:bold;\">".$polozka->prijmeni." ".substr($polozka->jmeno, 0,1).".</span><br/>";
            }
            if($polozka->obsazeno==1){
                if($polozka->text_obsazeno!=""){
                    $text .= "<span style=\"font-size:1.4em; font-weight:bold;\">$polozka->text_obsazeno</span><br/>"; 
                }else if($polozka->prijmeni==""){//nemame klienta
                    $text .= "<span style=\"font-size:1.4em; font-weight:bold;\">Obsazené místo</span><br/>"; 
                }
               
            }
            if($this->dataTopologie->zobrazit_id_klient and $polozka->id_klient != ""){
                $text .= "ID: ".$polozka->id_klient."<br/>";
            }
            if($this->dataTopologie->zobrazit_id_objednavka and $polozka->id_objednavka != ""){
                $text .= "ID obj:".$polozka->id_objednavka."<br/>";
            }
            if($this->dataTopologie->zobrazit_nazev and $polozka->nazev != ""){
                $text .= $polozka->nazev."<br/>";
            }
            if($this->dataTopologie->zobrazit_odjezd and $polozka->odjezdova_mista != ""){
                $text .= $polozka->odjezdova_mista."<br/>";
            }
             $html .= "<td valign=\"top\" align=\"center\" colspan=\"$polozka->rows\" height=\"".($height*$polozka->cols)."\" width=\"".($width*$polozka->rows)."\" rowspan=\"$polozka->cols\" class=\"$border\">$text</td>";
            
        }
        return $html;
        
    }

    function getPolozkyKlient()
    {
        $count = 0;
        $last_row = 0;
        $width = 35;
        $height = 15;
        foreach ($this->dataPolozky as $polozka) {
            $polozka->odjezdova_mista = trim(str_replace(array("odjezdové","odjezdová","místa","místo","odjezd"), "", $polozka->odjezdova_mista));
            
            if($last_row < $polozka->posx){
                $html .= "<tr>";
                $last_row = $polozka->posx;
            }
            if(strpos($polozka->class, "noseat")===false){
                $border = "1px solid white";
                $count ++;
                $text = "<b>$count</b><br/>";
                if($polozka->obsazeno==1){
                    $color = "#E23D30"; 
                    $title = "title=\"Obsazené místo\"";
                }else{
                    $color = "#96D680"; 
                    $title = "title=\"Volné místo\"";
                }                
            }else{
                $border = "1px solid #E0E0E0";
                $color = "#E0E0E0";
                $text = "";
                $title = "";
            }
            if($polozka->text!=""){
                $text .= $polozka->text."<br/>";
            }

            
            
             $html .= "<td valign=\"top\" align=\"center\" colspan=\"$polozka->rows\" height=\"".($height*$polozka->cols)."\" "
                     . "width=\"".($width*$polozka->rows)."\" rowspan=\"$polozka->cols\" style=\"border:$border;background-color:$color;\" $title>$text</td>";
            
        }
        return $html;
        
    }
    

    public function getPoznamka()
    {
        if ($this->dataTopologie->poznamka == "")
            return "";

        $html = "<table cellpadding=\"0\" cellspacing=\"8\" width=\"800\"  style=\"margin:5px;font-size:1.2em;\">
                    <tr>
                        <td style=\"width:80px;vertical-align:top;\">Poznámky:</td>
                        <td >" . $this->dataTopologie->poznamka . "</td>
                    </tr>
                </table>";
        return $html;
    }

    function getVystavil()
    {
        $html = "<tr>
                    <td style=\"width:80%\">Vystavil: " . $this->dataVystavil->jmeno . " " . $this->dataVystavil->prijmeni . "</td>
                    <td >Datum: " . Date("d.m.Y") . "</td>
                 </tr>";
        return $html;
    }


    function getHeader()
    {
        $html = "<table cellpadding=\"0\" cellspacing=\"0\"  width=\"810\" >
            <tr>
                <th style=\"text-align:center;\">
   			<h1 style=\"font-size: 2em;\">" . $this->dataTopologie->nazev . "</h1>
                </th>
            </tr>            
        </table>  ";
        return $html;
    }
    
    function getHeaderKlient()
    {
        $html = "<table cellpadding=\"0\" cellspacing=\"0\"  width=\"190\" >
            <tr>
                <th style=\"text-align:center;\">
   			<h3 class=\"side\">ZASEDACÍ POØÁDEK</h3>
                </th>
            </tr>            
        </table>  ";
        return $html;
    }
    
    function getFooterKlient()
    {
        $html ="";
        return $html;
    }
    
    function getFooter()
    {
        $html = $this->getPoznamka();
        $html .= "<table cellpadding=\"0\" cellspacing=\"8\" width=\"800\"  style=\"margin:5px;font-size:1.2em;\">
                
                <tr>	
                <td > Vygeneroval systém Albatros 3000 pro " . $this->dataCentralniData["nazev_spolecnosti"] . "<td>
                ".$this->getVystavil()."    
                 </table>";
        return $html;
    }


}
