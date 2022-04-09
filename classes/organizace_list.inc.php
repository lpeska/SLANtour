<?php

class Organizace_list extends Generic_list {

    //vstupni data
    protected $nazev;
    protected $ulice;
    protected $mesto;
    protected $psc;
    protected $telefon;
    protected $order_by;
    public $database;

    function __construct($nazev, $ulice, $mesto, $psc, $telefon, $order_by, $typ_pozadavku) {
        $this->database = Database::get_instance();

        $this->nazev = $this->check($nazev);
        $this->ulice = $this->check($ulice);
        $this->mesto = $this->check($mesto);
        $this->psc = $this->check($psc);
        $this->telefon = $this->check($telefon);
        $this->order_by = $this->check($order_by);
        $this->zobrazeni = $this->check($pozadavek);

        //ziskani seznamu z databaze	
        $this->data = $this->database->query($this->create_query("show"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        //zjistuju, zda mam neco k zobrazeni
        if (mysqli_num_rows($this->data) == 0) {
            $this->chyba("Zadaným podmínkám nevyhovuje žádný objekt");
        }
        $this->pocet_organizaci = mysqli_num_rows($this->data);
    }

    public function get_pocet_organizaci() {
        return $this->pocet_organizaci ;
    }
    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "show") {
            if ($this->nazev != "") {
                $where_nazev = " o.nazev like '%" . $this->nazev . "%' &&";
            } else {
                $where_nazev = " ";
            }
            if ($this->ulice != "") {
                $where_ulice = " oa.ulice like '%" . $this->ulice . "%' &&";
            } else {
                $where_ulice = " ";
            }
            if ($this->mesto != "") {
                $where_mesto = " oa.mesto like '%" . $this->mesto . "%' &&";
            } else {
                $where_mesto = " ";
            }
            if ($this->psc != "") {
                $where_psc = " oa.psc like '%" . $this->psc . "%' &&";
            } else {
                $where_psc = " ";
            }

            if ($this->order_by != "") {
                $order_by = $this->order_by($this->order_by);
            } else {
                $order_by = " oa.mesto";
            }

            //pokud nema adresu typ_kontaktu = 2 najdi adresu typ_kontaktu = 1
            $sql = "SELECT o.*, oa.*, ot.telefon
                    FROM organizace o
                        LEFT JOIN organizace_adresa oa ON (
                                    o.id_organizace = oa.id_organizace &&
                                    CASE WHEN (SELECT id_organizace FROM organizace_adresa WHERE id_organizace = o.id_organizace && typ_kontaktu = 2) IS NOT NULL
                                        THEN oa.typ_kontaktu = 2
                                        ELSE oa.typ_kontaktu = 1
                                    END
                                )
                        LEFT JOIN organizace_telefon ot ON (o.id_organizace = ot.id_organizace && ot.typ_kontaktu = 0)
                    WHERE $where_nazev $where_ulice $where_mesto $where_psc (o.role = 1 || o.role = 6)
                    ORDER BY $order_by";
//            echo $dotaz;
            return $sql;
        }
    }

    function order_by($vstup) {
        switch ($vstup) {
            case "id_up":
                return "`id_prodejny` ";
                break;
            case "id_down":
                return "`id_prodejny` desc";
                break;
            case "nazev_up":
                return "`nazev`";
                break;
            case "nazev_down":
                return "`nazev` desc";
                break;
            case "ulice_up":
                return "`ulice`";
                break;
            case "ulice_down":
                return "`ulice` desc";
                break;
            case "mesto_up":
                return "`mesto`";
                break;
            case "mesto_down":
                return "`mesto` desc";
                break;
        }
        return "`id_prodejny`"; 
    }

    function show_filtr() {
        //tvroba input nazev
        $input_nazev = "<input size=\"14\" name=\"nazev\" type=\"text\" value=\"" . $this->nazev . "\" />";
        $input_ulice = "<input size=\"14\" name=\"ulice\" type=\"text\" value=\"" . $this->ulice . "\" />";
        $input_mesto = "<input size=\"14\" name=\"mesto\" type=\"text\" value=\"" . $this->mesto . "\" />";
        $input_psc = "<input size=\"5\" name=\"psc\" type=\"text\" value=\"" . $this->psc . "\" />";
        $input_telefon = "<input size=\"14\" name=\"telefon\" type=\"text\" value=\"" . $this->telefon . "\" />";
        //tlacitko pro odeslani
        $submit = "<input type=\"submit\" value=\"Zmìnit filtrování\" />";

        //vysledny formular
        $vystup = " <form method=\"post\" action=\"\" style=\"margin: 10px;\">
                        <table>
                                <tr>
                                        <td>Název: $input_nazev</td>
                                        <td>Ulice: $input_ulice</td>
                                        <td>Mìsto: $input_mesto</td>
                                        <td>PSÈ: $input_psc</td>
                                        <td>Telefon: $input_telefon</td>
                                        <td valign=\"bottom\">$submit</td>
                                </tr>
                        </table>
                    </form>";
        return $vystup;
    }

    function show_list_header() {
        $vystup = " <table style=\"background-color: #F9E3B3;margin: 10px; border:black solid .1em;\" rules=\"all\">
                        <tr style=\"background-color: #E8BE53;border-top-left-radius: 5px;border-top-left-radius: 5px;\">
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\">Název</th>
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\">Ulice</th>						
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\">Mìsto</th>
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\" width=\"50px\">PSÈ</th>
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\">Telefon</th>                                
                        </tr>";
//        $vystup = " <table>
//                        <tr>
//                            <th>Název
//                                <a href=\"?typ=prodejni_mista_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up" . $serial . "\"><img src=\"img/up.gif\" alt=\"seøadit vzestupnì\" title=\"seøadit vzestupnì\" /></a>
//                                <a href=\"\"><img src=\"img/down.gif\" alt=\"seøadit sestupnì\" title=\"seøadit sestupnì\" /></a>
//                            </th>
//                            <th>Ulice
//                                <a href=\"\"><img src=\"img/up.gif\" alt=\"seøadit vzestupnì\" title=\"seøadit vzestupnì\" /></a>
//                                <a href=\"\"><img src=\"img/down.gif\" alt=\"seøadit sestupnì\" title=\"seøadit sestupnì\" /></a>
//                            </th>						
//                            <th>Mìsto
//                                <a href=\"\"><img src=\"img/up.gif\" alt=\"seøadit vzestupnì\" title=\"seøadit vzestupnì\" /></a>
//                                <a href=\"\"><img src=\"img/down.gif\" alt=\"seøadit sestupnì\" title=\"seøadit sestupnì\" /></a>
//                            </th>
//                            <th width=\"40px\">PSÈ
//                                <a href=\"\"><img src=\"img/up.gif\" alt=\"seøadit vzestupnì\" title=\"seøadit vzestupnì\" /></a>
//                                <a href=\"\"><img src=\"img/down.gif\" alt=\"seøadit sestupnì\" title=\"seøadit sestupnì\" /></a>
//                            </th>
//                            <th>Telefon</th>                                
//                        </tr>";
        return $vystup;
    }
    
    function show_list_item($typ_zobrazeni = "") {
        $vypis = "";
        if ($typ_zobrazeni == "") {
            $vypis .= " <tr>";
            $vypis .= "     <td style=\"padding: 2px 4px 2px;\">" . $this->get_nazev() . "</td>
                            <td style=\"padding: 2px 4px 2px;\">" . $this->get_ulice() . "</td>									
                            <td style=\"padding: 2px 4px 2px;\">" . $this->get_mesto() . "</td>
                            <td style=\"padding: 2px 4px 2px;\">" . $this->get_psc() . "</td>
                            <td style=\"padding: 2px 4px 2px;\">" . $this->get_telefon() . "</td>";
            $vypis .= " </tr>";
        } else if ($typ_zobrazeni == "addresy") {
            $vypis .= "['" . $this->get_mesto() . "', '" . $this->get_ulice() . "', '" . $this->get_lat() . "', '" . $this->get_lng() . "', '" . $this->get_nazev() . "', '" . $this->get_role() . "'], ";
        }
        return $vypis;
    }



function show_map(){
    $vystup = "" ;
    $validData = False;
    $locations = [];
    $latX = [];
    $latY = [];
    while( $this->radek=mysqli_fetch_array($this->data)){
        if($this->radek["lat"] != 0 and  $this->radek["lng"] != 0){
            $validData = True;
            $text= "     <strong>" . $this->get_nazev() . "</strong><br>
                          ".$this->get_ulice().", " . $this->get_mesto() . ", " . $this->get_psc() . "<br>
                          tel.:	" . $this->get_telefon() . "";           
            
            $text = iconv( "Windows-1250", "UTF-8", ($text));
            $latX[] = floatval($this->radek["lng"]);
            $latY[] = floatval($this->radek["lat"]);
            $locations[] = array('x'=>$this->radek["lng"], 'y'=> $this->radek["lat"], 'txt'=>$text);
        
        }
    }
    #rewind data
    mysqli_data_seek($this->data, 0);
    

    if($validData){
    
        #calculate map center and dimensions
        $meanX = array_sum($latX)/count((array)$latX);
        $meanY = array_sum($latY)/count((array)$latY);
        
        if(count((array)$latX) >1) {
        
            $stdX = max($latX)  - min($latX) ;
            $stdY = max($latY)  - min($latY) ;
            $std = max([$stdX,$stdY])  ;
            /*$stdY = sqrt(array_sum(array_map(function ($x) use ($meanY) { 
                    return pow($x - $meanY, 2);
                }, $latY)) / count((array)$latY));
            $meanSTD = ($stdX+$stdY)/2   ; */
            $deg = floor(360/$std);
            $zoom = floor( log($deg,2) )+2;
            
            if ( $zoom < 1){
                $zoom = 1;
            }else if($zoom > 18){
                $zoom = 18;
            }
        }else{
            $zoom = 7;
        }
        
        
    
         $vystup = '
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>
            <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>
            <script>
              var latX =  '.str_replace(",",".",$meanX).';
              var latY =  '.str_replace(",",".",$meanY).';
              var markers =  '.json_encode($locations).';
            </script>
            <style>
                .main #mapid img{
                     border:none;
                     padding:0;
                     margin:0;
                }
            </style>
            <script>   
                function addMarker(item, mymap) {
                    var marker2 = L.marker([item["y"],item["x"]]).addTo(mymap);
                    marker2.bindPopup(item["txt"]);
                }
            
               window.addEventListener("load", function(){
                  var mymap = L.map("mapid").setView([latY, latX], '.$zoom.');
                  L.tileLayer(\'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoibHBlc2thIiwiYSI6ImNrYmx5dGh4cjA3MHMycW1pdHp4Y2ZheGoifQ.e-0fQLJYoUUxsM0X6Z-gxQ\', {
                    attribution: \'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>\',
                    maxZoom: 18,
                    minZoom: 1,
                    id: "mapbox/streets-v11",
                    tileSize: 512,
                    zoomOffset: -1,
                    accessToken: "pk.eyJ1IjoibHBlc2thIiwiYSI6ImNrYmx5dGh4cjA3MHMycW1pdHp4Y2ZheGoifQ.e-0fQLJYoUUxsM0X6Z-gxQ"
                  }).addTo(mymap);
            
                  markers.forEach(function(item){addMarker(item, mymap)});
                });

            </script>
            <div  id="mapid" style="width:710px; height:450px;">
            </div>
         ';    
    
    }
    return $vystup;
  }
    






    function show_list_footer() {
        return "</table>";
    }
    
    function get_nazev() {
        return $this->radek["nazev"];
    }

    function get_ulice() {
        return $this->radek["ulice"];
    }

    function get_mesto() {
        return $this->radek["mesto"];
    }

    function get_psc() {
        return $this->radek["psc"];
    }

    function get_telefon() {
        return $this->radek["telefon"];
    }

    function get_lat() {
        return $this->radek["lat"];
    }

    function get_lng() {
        return $this->radek["lng"];
    }

    function get_role() {
        return $this->radek["role"];
    }

}

?>
