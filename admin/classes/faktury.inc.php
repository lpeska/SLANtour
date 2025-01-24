<?php
/**
 * informace.inc.php - tridy pro zobrazeni dalsich informaci
 */

/*--------------------- SERIAL -------------------------------------------*/
class Faktury extends Generic_data_class
{
    //vstupnidata
    const COMGATE_ID = 657;
    
    protected $typ_pozadavku;
    protected $minuly_pozadavek; //dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
    protected $id_zamestnance;

    protected $id_faktury;
    protected $nazev;
    protected $popisek;
    protected $popis;
    protected $popis_lazni;
    protected $popis_strediska;

    protected $mozni_prijemci;
    protected $mozni_prijemci_header;
    protected $id_zeme;
    protected $id_destinace;
    protected $typ_informace;
    protected $id_user_create;
    protected $platba_kartou;
    protected $data;
    protected $informace;

    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
    /** konstruktor tøídy na základì typu pozadavku*/
    function __construct($typ_pozadavku, $id_zamestnance, $id_faktury, $id_objednavka = "", $minuly_pozadavek = "")
    {
        $this->platba_kartou = false;
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->minuly_pozadavek = $this->check($minuly_pozadavek);
        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_faktury = $this->check_int($id_faktury);
        $this->id_objednavka = $this->check_int($id_objednavka);

        $centralni_data = $this->database->query($this->create_query("get_centralni_data"))
        or $this->chyba("Chyba pøi dotazu do databáze central data: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

        //nacteme centralni data do pole
        while ($row = mysqli_fetch_array($centralni_data)) {
            $row["nazev"] = str_replace("hlavicka:", "", $row["nazev"]);
            $this->centralni_data[$row["nazev"]] = $row["text"];
        }

        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {

            //pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
            if ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update" or $this->typ_pozadavku == "delete") {
                //natahneme data z formulare
                if ($typ_pozadavku == "update") {
                    $this->cislo_faktury = $this->check($_POST["cislo_faktury"]);
                }
                $this->typ_dokladu = $this->check($_POST["typ_dokladu"]);
                $this->typ_faktury = $this->check($_POST["typ_faktury"]);
                $this->cislo_faktury_vystavovatele = $this->check($_POST["cislo_faktury_vystavovatele"]);
                $this->mena = $this->check($_POST["mena"]);
                $this->kurz = $this->check($_POST["kurz"]);
                $this->text_faktury = $this->check_with_html($_POST["text_faktury"]);
                $this->datum_vystaveni = $this->check($this->change_date_cz_en($_POST["datum_vytvoreni"]));
                $this->datum_zdanitelneho_plneni = $this->check($this->change_date_cz_en($_POST["datum_zdanitelneho_plneni"]));
                $this->datum_splatnosti = $this->check($this->change_date_cz_en($_POST["datum_splatnosti"]));
                $this->zpusob_uhrady = $this->check($_POST["zpusob_uhrady"]);
                
                //tady pocitat az na serveru!!!                
                $this->zaplaceno = $this->check_int($_POST["zaplaceno"]);
                
                
                $this->celkova_castka = $this->check_int($_POST["celkova_castka"]);
                $this->dodavatel_text = $this->check_with_html($_POST["dodavatel_text"]);
                $this->prijemce_text = $this->check_with_html($_POST["prijemce_text"]);
                $this->pata_faktury = $this->check_with_html($_POST["pata_faktury"]);
                $this->pdf_url = "";
                $this->id_objednavatele_klient = $this->check_int($_POST["id_objednavatele_klient"]);
                $this->id_objednavatele_organizace = $this->check_int($_POST["id_objednavatele_organizace"]);
                if ($this->id_objednavatele_organizace <= 0) {
                    $this->id_objednavatele_organizace = "NULL";
                }
                if ($this->id_objednavatele_klient <= 0) {
                    $this->id_objednavatele_klient = "NULL";
                }
                // print_r($this);

                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni) . $this->create_query($this->typ_pozadavku));
                if($this->typ_pozadavku == "delete"){
                    $data = $this->database->query($this->create_query("deleteFakturaFromPlatby"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni) . $this->create_query("deleteFakturaFromPlatby"));
                }
                //pokud vytvarime novou fakturu, ulozime ID a vytvorime jeste polozky faktury
                if ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update") {
                    if($this->typ_pozadavku == "update"){
                        $data = $this->database->query($this->create_query("deletePolozkyFaktury"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni) . $this->create_query("deletePolozkyFaktury"));
                    }
                    if($this->typ_pozadavku == "create"){
                        $this->id_faktury = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                        $this->informace["id_faktury"] = $this->id_faktury;
                    }                                                            
                    for ($index = 0; $index < 50; $index++) {
                        if ($_POST["polozka_nazev_" . $index] != "") {
                            //ulozime polozku
                            $this->polozka_nazev = $this->check($_POST["polozka_nazev_" . $index]);
                            $this->polozka_jednotkova_cena = $this->check_int($_POST["polozka_jednotkova_cena_" . $index]);
                            $this->polozka_mnozstvi = $this->check_int($_POST["polozka_mnozstvi_" . $index]);
                            $this->polozka_dph = $this->check($_POST["polozka_dph_" . $index]);
                            $this->polozka_celkem = $this->check($_POST["polozka_celkem_" . $index]);
                            $data = $this->database->query($this->create_query("insertPolozkaFaktury"))
                                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni) . $this->create_query("insertPolozkaFaktury"));
                        }
                    }

                    //upravim platby pokud jsem je pridal k fakture
                    for ($index = 0; $index < 50; $index++) {
                        if($_POST["id_platby_" . $index]>0){
                            if ($_POST["check_platby_" . $index] > 0) {
                                //upravime platbu - pridame id_faktury
                                $sql = "UPDATE `objednavka_platba` SET `id_faktury`=".$this->id_faktury." WHERE `id_platba`=".$_POST["id_platby_" . $index]."";
                                $this->database->query($sql) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni) . $sql);
                            }else{
                                //upravime platbu - odeberu id_faktury
                                $sql = "UPDATE `objednavka_platba` SET `id_faktury`=NULL WHERE `id_platba`=".$_POST["id_platby_" . $index]." and `id_faktury`=".$this->id_faktury."";
                                $this->database->query($sql) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni) . $sql);
                            }
                        }
                    }    
                    
                    if($this->typ_pozadavku == "create"){
                        $sql = "SELECT max(`cislo_faktury`) as cislo FROM `faktury` WHERE `cislo_faktury` like \"".$this->centralni_data["ts_faktura:aktualni_predcisli"]."%\" ";
                        $cislo = 0;
                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                        while($row = mysqli_fetch_array($data)){
                            if($row["cislo"]==""){
                                //prvni zaznam daneho predcisli
                                $cislo = 0;
                            }else{
                                $cislo = $row["cislo"]-($this->centralni_data["ts_faktura:aktualni_predcisli"]*100000);
                            }
                            
                        }      
                        
                        $max_cislo_faktury = $cislo + 1;
                        //update cislo faktury
                        if ($max_cislo_faktury < 10) {
                            $cislo_faktury = "0000" . $max_cislo_faktury;
                        } else if ($max_cislo_faktury < 100) {
                            $cislo_faktury = "000" . $max_cislo_faktury;
                        } else if ($max_cislo_faktury < 1000) {
                            $cislo_faktury = "00" . $max_cislo_faktury;
                        } else if ($max_cislo_faktury < 10000) {
                            $cislo_faktury = "0" . $max_cislo_faktury;
                        } else{
                            $cislo_faktury = "" . $max_cislo_faktury;
                        } 

                        $this->cislo_faktury = $this->centralni_data["ts_faktura:aktualni_predcisli"] . $cislo_faktury;
                        $data = $this->database->query($this->create_query("updateCisloFaktury"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    }

                }

                if (!$this->get_error_message()) {
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }

                //pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
            } else if (($this->typ_pozadavku == "edit" or $this->typ_pozadavku == "show") and $this->minuly_pozadavek != "update") {
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));


                $this->informace = mysqli_fetch_array($this->data);
                //jednotlive sloupce ulozim do promennych tridy
                $this->id_objednavka = $this->informace["id_objednavka"];
                $this->cislo_faktury = $this->informace["cislo_faktury"];
                $this->typ_dokladu = $this->informace["typ_dokladu"];
                $this->typ_faktury = $this->informace["typ_faktury"];
                $this->cislo_faktury_vystavovatele = $this->informace["cislo_faktury_vystavovatele"];
                $this->mena = $this->informace["mena"];
                $this->kurz = $this->informace["kurz"];
                $this->text_faktury = $this->informace["text_faktury"];
                $this->datum_vystaveni = $this->change_date_en_cz($this->informace["datum_vystaveni"]);
                $this->datum_zdanitelneho_plneni = $this->change_date_en_cz($this->informace["datum_zdanitelneho_plneni"]);
                $this->datum_splatnosti = $this->change_date_en_cz($this->informace["datum_splatnosti"]);
                $this->zpusob_uhrady = $this->informace["zpusob_uhrady"];
                $this->zaplaceno = $this->informace["zaplaceno"];
                $this->celkova_castka = $this->informace["celkova_castka"];


                $this->id_objednavatele_klient = $this->informace["id_objednavatele_klient"];
                $this->id_objednavatele_organizace = $this->informace["id_objednavatele_organizace"];

                
                $this->dodavatel_text = $this->informace["dodavatel_text"];
                $this->prijemce_text = $this->informace["prijemce_text"];
                $this->pata_faktury = $this->informace["pata_faktury"];
                $this->pdf_url = $this->informace["pdf_url"];

                //pouzijeme DPH, ktere byla aktivni v dobe vzniku faktury
                $this->centralni_data["ts_faktura:dph_snizena"] = $this->informace["dph_snizene"];
                $this->centralni_data["ts_faktura:dph"] = $this->informace["dph_zakladni"];


                $this->id_user_create = $this->informace["id_user_create"];
                //ziskame info o platbach navazanych k teto faktuøe
              //zada platby do this->platby
                $this->show_platby();
                

                

                $data_polozky = $this->database->query($this->create_query("getPolozky"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->polozky_faktury = array();
                $i = 0;
                while ($row_polozky = mysqli_fetch_array($data_polozky)) {
                    $i++;
                    $this->polozky_faktury[$i]["nazev_polozky"] = $row_polozky["nazev_polozky"];
                    $this->polozky_faktury[$i]["jednotkova_cena"] = $row_polozky["jednotkova_cena"];
                    $this->polozky_faktury[$i]["mnozstvi"] = $row_polozky["pocet"];
                    $this->polozky_faktury[$i]["dph"] = $row_polozky["pocitat_dph"];
                }

                $data_serial = $this->database->query($this->create_query("get_zajezd"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->serial = mysqli_fetch_array($data_serial);

                $this->nazev_serialu = $this->serial["nazev"];
                if ($this->serial["nazev_ubytovani"] != "") {
                    $this->nazev_serialu = $this->serial["nazev_ubytovani"] . ", " . $this->nazev_serialu;
                }
                $this->nazev_zajezdu = $this->serial["nazev_zajezdu"];
                $this->od = $this->change_date_en_cz($this->serial["od"]);
                $this->do = $this->change_date_en_cz($this->serial["do"]);

                if ($this->serial["termin_od"] != "0000-00-00" and $this->serial["termin_od"] != "") {
                    $this->od = $this->change_date_en_cz($this->serial["termin_od"]);
                    $this->do = $this->change_date_en_cz($this->serial["termin_do"]);
                }
                $this->celkova_cena_objednavky = $this->serial["celkova_cena"];
                $this->klient_jmeno = $this->serial["jmeno"];
                $this->klient_prijmeni = $this->serial["prijmeni"];

            } else if ($this->typ_pozadavku == "new") {
                $data_serial = $this->database->query($this->create_query("get_zajezd"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->serial = mysqli_fetch_array($data_serial);
                $this->nazev_serialu = $this->serial["nazev"];
                if ($this->serial["nazev_ubytovani"] != "") {
                    $this->nazev_serialu = $this->serial["nazev_ubytovani"] . ", " . $this->nazev_serialu;
                }
                $this->nazev_zajezdu = $this->serial["nazev_zajezdu"];
                $this->od = $this->change_date_en_cz($this->serial["od"]);
                $this->do = $this->change_date_en_cz($this->serial["do"]);
                if ($this->serial["termin_od"] != "0000-00-00" and $this->serial["termin_od"] != "") {
                    $this->od = $this->change_date_en_cz($this->serial["termin_od"]);
                    $this->do = $this->change_date_en_cz($this->serial["termin_do"]);
                }
                $this->celkova_cena_objednavky = $this->serial["celkova_cena"];
                $this->mena = "Kè";
                $this->kurz = 1.00;

                //ziskame info o platbach navazanych k teto faktuøe
              //zada platby do this->platby
                $this->show_platby();

                $this->id_objednavatele_klient = $this->serial["id_klient"];
                $this->id_objednavatele_organizace = $this->serial["id_objednavajici_organizace"];
                
                //echo $this->id_objednavatele_klient.", ".$this->id_objednavatele_organizace;
                
                
                $this->id_agentury = $this->serial["id_organizace"];
                
                $this->klient_jmeno = $this->serial["jmeno"];
                $this->klient_prijmeni = $this->serial["prijmeni"];
                $this->klient_ulice = $this->serial["ulice"];
                $this->klient_mesto = $this->serial["mesto"];
                $this->klient_psc = $this->serial["psc"];
                $this->klient_email = $this->serial["email"];
                $this->klient_telefon = $this->serial["telefon"];
                $this->celkova_castka = $this->serial["celkova_cena"];
                //vytvorime jednu polozku faktury s celkovou castkou
                $this->polozky_faktury = array();
                if ($this->serial["celkova_cena"] != $this->serial["zbyva_zaplatit"]) {
                    //neco uz bylo uhrazeno
                    $this->polozky_faktury[1]["nazev_polozky"] = "Fakturujeme Vám za doplatek objednávky è. " . $this->id_objednavka . "";
                    $this->typ_faktury = "doplatek";
                } else {
                    //fakturujeme celou castku
                    $this->polozky_faktury[1]["nazev_polozky"] = "Fakturujeme Vám za plnou cenu objednávky è. " . $this->id_objednavka . "";
                }
                $this->polozky_faktury[1]["jednotkova_cena"] = $this->serial["zbyva_zaplatit"];
                $this->polozky_faktury[1]["mnozstvi"] = 1;
                $this->polozky_faktury[1]["dph"] = "vcetneDPH";

                $this->text_faktury = "Fakturujeme Vám za objednávku zájezdu/služeb:<br/>\n" . $this->nazev_serialu . ", " . $this->nazev_zajezdu . " " . $this->od . " - " . $this->do . "<br/>\n    <b>Objednavatel:</b> " . $this->klient_jmeno . " " . $this->klient_prijmeni . "<br/>\n" . $this->klient_ulice . ", " . $this->klient_psc . " " . $this->klient_mesto . "<br/>\n E-mail: " . $this->klient_email . "<br/>
Tel.: " . $this->klient_telefon . "<br/><br/>
<b>Rozpis služeb:</b>\n";
                $objednavka_displayer = new ObjednavkaDisplayer($this->id_objednavka);
                $this->text_faktury .= $objednavka_displayer->getSluzbyForFaktury();
                $this->datum_vystaveni = Date("d.m.Y");
                $this->datum_zdanitelneho_plneni = $this->do;
                $this->datum_splatnosti = Date('d.m.Y', strtotime("+10 days"));
                $this->dodavatel_text =
                    "<b>Dodavatel:</b><br/>
                        " . $this->centralni_data["nazev_spolecnosti"] . "<br/>
                                                          " . $this->centralni_data["adresa"] . "<br/>
                                                              Tel.: " . $this->centralni_data["telefon"] . "<br/>
                                                                  Èíslo úètu: " . $this->centralni_data["bankovni_spojeni"] . "<br/>
                                                                      IÈO: " . $this->centralni_data["ico"] . " DIÈ: " . $this->centralni_data["dic"] . "<br/><br/>
                                                                          " . $this->centralni_data["firma_zapsana"] . "";
                //prijemci dle prirazene agentury ci platby kartou
                $this->mozni_prijemci = array();
                //$str = str_replace(array("\r","\n"),"",$str);
                $this->mozni_prijemci_header = array();
                
                //klient to muze byt vzdy
               
                $this->prijemce_text = "<b>Odbìratel:</b><br/>
                                                     " . $this->klient_jmeno . " " . $this->klient_prijmeni . "<br/>
                                                         " . $this->klient_ulice . ", " . $this->klient_psc . " " . $this->klient_mesto . "<br/>
                                                             E-mail: " . $this->klient_email . "<br/>
                                                             Tel.: " . $this->klient_telefon . "";
                
                $this->mozni_prijemci_header[] =  $this->klient_jmeno . " " . $this->klient_prijmeni;
                $this->mozni_prijemci[] = str_replace(array("\r","\n"),"",$this->prijemce_text);
                
                if ($this->serial["id_organizace"] != 0) {
                    //rozlisujeme podle kontaktnich udaju
                    if ($this->serial["kontaktni_mesto"] != "") {
                        $this->prijemce_text = "<b>Adresa:</b><br/>
                                                     " . $this->serial["nazev_organizace"] . "<br/>
                                                         " . $this->serial["kontaktni_ulice"] . ", " . $this->serial["kontaktni_psc"] . " " . $this->serial["kontaktni_mesto"] .
                            "\n\n<b>Odbìratel:</b><br/>
                            " . $this->serial["nazev_organizace"] . "<br/>
                                                        " . $this->serial["organizace_ulice"] . ", " . $this->serial["organizace_psc"] . " " . $this->serial["organizace_mesto"] . "<br/>
                                                          IÈ: " . $this->serial["ico_organizace"] . ", DIÈ: " . $this->serial["dic_organizace"] . "";
                    } else {
                        $this->prijemce_text = "<b>Odbìratel:</b><br/>
                                                     " . $this->serial["nazev_organizace"] . "<br/>
                                                     " . $this->serial["organizace_ulice"] . "<br/> " . $this->serial["organizace_psc"] . " " . $this->serial["organizace_mesto"] . "<br/>
                                                     IÈ: " . $this->serial["ico_organizace"] . "<br/> DIÈ: " . $this->serial["dic_organizace"] . " ";
                    }
                    //mame-li agenturu, je to take mozny prijemce
                    $this->mozni_prijemci_header[] =  $this->serial["nazev_organizace"];
                    $this->mozni_prijemci[] = str_replace(array("\r","\n"),"",$this->prijemce_text);
                }
                if($this->serial["id_objednavajici_organizace"]!=0 ){
                    //rozlisujeme podle kontaktnich udaju
                    if ($this->serial["objednavajici_kontaktni_mesto"] != "") {
                        $this->prijemce_text = "<b>Adresa:</b><br/>
                                                     " . $this->serial["objednavajici_nazev_organizace"] . "<br/>
                                                         " . $this->serial["objednavajici_kontaktni_ulice"] . ", " . $this->serial["objednavajici_kontaktni_psc"] . " " . $this->serial["objednavajici_kontaktni_mesto"] .
                            "\n\n<b>Odbìratel:</b><br/>
                            " . $this->serial["objednavajici_nazev_organizace"] . "<br/>
                                                        " . $this->serial["objednavajici_organizace_ulice"] . ", " . $this->serial["objednavajici_organizace_psc"] . " " . $this->serial["objednavajici_organizace_mesto"] . "<br/>
                                                          IÈ: " . $this->serial["objednavajici_ico_organizace"] . ", DIÈ: " . $this->serial["objednavajici_dic_organizace"] . "";
                    } else {
                        $this->prijemce_text = "<b>Odbìratel:</b><br/>
                                                     " . $this->serial["objednavajici_nazev_organizace"] . "<br/>
                                                     " . $this->serial["objednavajici_organizace_ulice"] . "<br/> " . $this->serial["objednavajici_organizace_psc"] . " " . $this->serial["objednavajici_organizace_mesto"] . "<br/>
                                                     IÈ: " . $this->serial["objednavajici_ico_organizace"] . "<br/> DIÈ: " . $this->serial["objednavajici_dic_organizace"] . " ";
                    }
                    //mame-li agenturu, je to take mozny prijemce
                    $this->mozni_prijemci_header[] =  $this->serial["objednavajici_nazev_organizace"];
                    $this->mozni_prijemci[] = str_replace(array("\r","\n"),"",$this->prijemce_text);
                }
                //pokud probehla platba kartou, dalsi mozny prijemce je COMGATE, tento prijemce ma prednost pred ostatnimi
                if($this->platba_kartou==true){
                    $comgate_data = $this->database->query($this->create_query("get_comgate"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    while ($row_organizace = mysqli_fetch_array($comgate_data)) {
                        $this->prijemce_text = "<b>Odbìratel:</b><br/>
                                                     " . $row_organizace["nazev_organizace"] . "<br/>
                                                     " . $row_organizace["organizace_ulice"] . "<br/> " . $row_organizace["organizace_psc"] . " " . $row_organizace["organizace_mesto"] . "<br/><br/>
                                                     IÈ: " . $row_organizace["ico_organizace"] . "<br/> DIÈ: " . $row_organizace["dic_organizace"] . " ";
                        
                        $this->mozni_prijemci_header[] =  $row_organizace["nazev_organizace"];
                        $this->mozni_prijemci[] = str_replace(array("\r","\n"),"",$this->prijemce_text);
                    }
                    
                }
                
                $this->pata_faktury = "Použit zvláštní režim dle § 89 Zákona o DPH è. 235/2004 Sb.";
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }
    /*
     * Zkontroluje, zda je k faktuøe pøiøazeno dostatek plateb a je pøeplacena/uhrazena/castecne uhrazena/neuhrazena
     */
    function check_splaceno(){
        $platby = $this->database->query($this->create_query("get_platby"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $faktura_query = $this->database->query($this->create_query("show"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $faktura = mysqli_fetch_array($faktura_query);
        $celkova_castka = $faktura["celkova_castka"];
        $zaplacena_castka = 0;
        while ($row_platby = mysqli_fetch_array($platby)) {
            if($row_platby["id_faktury"] == $this->id_faktury){
                $zaplacena_castka+=$row_platby["castka"];
            }
        }
        if($celkova_castka < $zaplacena_castka){//preplaceno
            $splaceno = 3;
        }else if($celkova_castka == $zaplacena_castka){//splaceno
            $splaceno = 2;
        }else if($zaplacena_castka >0){//castecne
            $splaceno = 1;
        }else{//nesplaceno
            $splaceno = 0;
        }
        $sql = "UPDATE `faktury`
			SET  `zaplaceno`=" . $splaceno . "
			WHERE `id_faktury`=" . $this->id_faktury . "
			LIMIT 1";
        $this->database->query($sql);
        
    }
    
    function show_platby(){
                $platby = $this->database->query($this->create_query("get_platby"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->platby = "";
                $j = 0;
  
                while ($row_platby = mysqli_fetch_array($platby)) {
                    $j++;
                    //poznacim zda nekdy bylo pouzito platby kartou (na zaklade toho nabidnu dalsiho mozneho prijemce)
                    if($row_platby["zpusob_uhrady"]=="CARD_CZ_CS"){
                        $this->platba_kartou = true;
                    }
                    $platba_checkbox="";
                    if($row_platby["id_faktury"]>0){
                        if($row_platby["id_faktury"]!=$this->id_faktury){
                            //platba uz byla prirazena k jine fakture
                            $platba_checkbox = "<input type=\"hidden\" name=\"check_platby_$j\" value=\"-1\" \> Tato platba je již pøiøazena k faktuøe è. ".$row_platby["id_faktury"];
                        }else{
                            //platba byla prirazena k teto fakture
                            $platba_checkbox = "<input type=\"checkbox\" name=\"check_platby_$j\" value=\"1\" checked=\"checked\" onchange=\"changePriceName();\" \>";
                        }
                    }else{
                       $platba_checkbox = "<input type=\"checkbox\" name=\"check_platby_$j\" value=\"1\" onchange=\"changePriceName();\" \>"; 
                    }
                   // print_r($row_platby);
                    $this->platby .= "<tr>
                                        <td>ID: " . $row_platby["id_platba"] . "<input type=\"hidden\" name=\"typ_platby_$j\" value=\"".$row_platby["typ_dokladu"]."\" \> (".$this->typ_dokladu($row_platby["typ_dokladu"]).") <input type=\"hidden\" name=\"id_platby_$j\" value=\"".$row_platby["id_platba"]."\" \>
                                        <td>" . $row_platby["cislo_dokladu"] . "
                                        <td>" . $row_platby["castka"] . " Kè <input type=\"hidden\" name=\"castka_platby_$j\" value=\"".$row_platby["castka"]."\" \>
                                        <td>" . $this->change_date_en_cz($row_platby["splaceno"]) . "
                                        <td>" . $row_platby["zpusob_uhrady"] . "
                                        <td>$platba_checkbox
                                    ";
                }
                if ($this->platby != "") {
                    $this->platby = "K této objednávce již existují následující platby (<a href=\"/admin/rezervace.php?id_objednavka=$this->id_objednavka&amp;typ=rezervace&amp;pozadavek=show#platby\">zobrazit platby</a>):<br/>
                                                            <table colspan=\"2\" rowspan=\"2\"  class=\"list\" style=\"margin:5px;\"><tr id=\"tabulka_platba\"><th>ID<th>Èíslo dokladu<th>Èástka<th>Datum úhrady<th>Zpùsob úhrady<th>Pøidat k faktuøe
                                                                " . $this->platby . "
                                                               <tr id=\"nova_platba\">     
                                                            </table>";
                } else {
                    $this->platby = " <table colspan=\"2\" class=\"list\" rowspan=\"2\" style=\"margin:5px;\"><tr id=\"tabulka_platba\" style=\"display:none;\"><th>Èíslo dokladu<th>Èástka<th>Datum úhrady<th>Zpùsob úhrady
                                                               <tr id=\"nova_platba\">     
                                                            </table>";
                }
                $this->platby .= "<a href=\"#\" class=\"button\" onclick=\"nova_platba();\">nová platba</a></span>";        
    }
    
    function get_zaloha()
    {
        //aktualni zaloha
        $sql_podminky = "select `smluvni_podminky`.* from `smluvni_podminky`  where `id_smluvni_podminky_nazev`=" . $this->serial["id_sml_podm"] . " and `typ`=\"záloha\"
                                        order by `prodleva` desc ";
        //echo $sql_podminky;

        $query = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_podminky);
        $existuje_zaloha = 0;

        $prosla_zaloha = 0;
        $prosly_rozhodujici_termin = 0;
        $platba_cele_castky_najednou = 0;
        $nalezena_aktualni_zaloha = 0;
        $aktualni_zaloha = 100;
        $jednotka_zaloha = "%";
        $vyse_zalohy = $this->celkova_cena_objednavky;
        $aktualni_rozhodujici_termin = 0;

        $now = new DateTime(Date("Y-m-d h:i:s"));
        $ref = new DateTime($this->od . " 00:00:00");
        $diff = $now->diff($ref, true);
        $dnu_do_odjezdu = $diff->days;

        while ($row_podminky = mysqli_fetch_array($query)) {
            $podm .= implode(" ", $row_podminky);
            //nasli jsme aktualni polozku - bud zalohu nebo doplatek
            if (!$nalezena_aktualni_zaloha and ($row_podminky["prodleva"] + 5) <= $dnu_do_odjezdu) {
                //nasli jsme zalohu
                $nalezena_aktualni_zaloha = 1;
                $existuje_zaloha = 1;
                if ($row_podminky["procento"] > 0) {
                    $aktualni_zaloha = $row_podminky["procento"];
                    $jednotka_zaloha = "%";
                    $vyse_zalohy = $this->celkova_cena_objednavky * $aktualni_zaloha * 0.01;
                    return $vyse_zalohy;
                } else {
                    $aktualni_zaloha = $row_podminky["castka"];
                    $jednotka_zaloha = "Kè";
                    //castka vztazena na osobu
                    $vyse_zalohy = $this->serial["pocet_osob"] * $aktualni_zaloha;
                    return $vyse_zalohy;
                }
                $aktualni_rozhodujici_termin = $row_podminky["prodleva"];
            }
        }
        return $vyse_zalohy;
    }

    function get_doplatek()
    {
        //aktualni doplatek - bude tøeba upravit v návaznosti na další vytvoøené faktury a platby - až budou...

        //get all faktury
        $already_paid = 0;
        $sql_platba = "SELECT sum(`castka`) as `castka` FROM `objednavka_platba` where `id_faktury` is NULL and `id_objednavka`=" . $this->id_objednavka . "";
        $sql_faktura = "SELECT sum(`celkova_castka`) as `castka` FROM `faktury` where `id_objednavka`=" . $this->id_objednavka . "";
        // echo $sql_faktura;
        $data_faktura = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_faktura);
        $row_faktura = mysqli_fetch_array($data_faktura);
        $already_paid += $row_faktura["castka"];

        $data_platba = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_platba);
        $row_platba = mysqli_fetch_array($data_platba);
        $already_paid += $row_platba["castka"];

        if ($this->celkova_cena_objednavky - $already_paid < 0) {
            return 0;
        }
        return $this->celkova_cena_objednavky - $already_paid;

    }

//------------------- METODY TRIDY -----------------	
    /**vytvoreni dotazu na zaklade typu pozadavku*/
    function create_query($typ_pozadavku)
    {
        if ($typ_pozadavku == "create") {
            $dotaz = "INSERT INTO `faktury`
							(`id_objednavka`,`cislo_faktury`,`typ_dokladu`,`typ_faktury`,`cislo_faktury_vystavovatele`,
                                                        `mena`,`kurz`,`text_faktury`,`datum_vystaveni`,`datum_zdanitelneho_plneni`,`datum_splatnosti`,
                                                        `zpusob_uhrady`,`zaplaceno`,`celkova_castka`,`dph_snizene`,`dph_zakladni`,`id_vystavil`,`id_objednavatele_klient`,`id_objednavatele_organizace`,
                                                        `dodavatel_text`,`prijemce_text`,`pata_faktury`,`pdf_url`,`id_user_create`,`id_user_edit` )
						VALUES
							 (" . $this->id_objednavka . ",'" . $this->cislo_faktury . "','" . $this->typ_dokladu . "','" . $this->typ_faktury . "','" . $this->cislo_faktury_vystavovatele . "',
                                                          '" . $this->mena . "'," . $this->kurz . ",'" . $this->text_faktury . "','" . $this->datum_vystaveni . "','" . $this->datum_zdanitelneho_plneni . "','" . $this->datum_splatnosti . "',
							 '" . $this->zpusob_uhrady . "'," . $this->zaplaceno . "," . $this->celkova_castka . "," . $this->centralni_data["ts_faktura:dph_snizena"] . "," . $this->centralni_data["ts_faktura:dph"] . "," . $this->id_zamestnance . "," . $this->id_objednavatele_klient . "," . $this->id_objednavatele_organizace . ",
							 '" . $this->dodavatel_text . "','" . $this->prijemce_text . "','" . $this->pata_faktury . "','" . $this->pdf_url . "'," . $this->id_zamestnance . "," . $this->id_zamestnance . " )";

            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "update") {
            $dotaz = "UPDATE `faktury`
						SET
							`cislo_faktury`='" . $this->cislo_faktury . "',`cislo_faktury_vystavovatele`='" . $this->cislo_faktury_vystavovatele . "',
                                                        `mena`='" . $this->mena . "',`kurz`='" . $this->kurz . "',`text_faktury`='" . $this->text_faktury . "',
                                                        `datum_vystaveni`='" . $this->datum_vystaveni . "',`datum_zdanitelneho_plneni`='" . $this->datum_zdanitelneho_plneni . "',`datum_splatnosti`='" . $this->datum_splatnosti . "',
                                                        `zpusob_uhrady`='" . $this->zpusob_uhrady . "', `zaplaceno`=" . $this->zaplaceno . ",`celkova_castka`=" . $this->celkova_castka . ",`id_objednavatele_klient`=" . $this->id_objednavatele_klient . ",`id_objednavatele_organizace`=" . $this->id_objednavatele_organizace . ",
							`dodavatel_text`='" . $this->dodavatel_text . "', `prijemce_text`='" . $this->prijemce_text . "', `pata_faktury`='" . $this->pata_faktury . "', `pdf_url`='" . $this->pdf_url . "', `id_user_edit`=" . $this->id_zamestnance . "
						WHERE `id_faktury`=" . $this->id_faktury . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "updateCisloFaktury") {
            $dotaz = "UPDATE `faktury`
						SET
							`cislo_faktury`='" . $this->cislo_faktury . "'
                                                WHERE `id_faktury`=" . $this->id_faktury . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "insertPolozkaFaktury") {
            $dotaz = "INSERT INTO `faktury_polozka`
                                    (`id_faktury`, `nazev_polozky`, `jednotkova_cena`, `pocet`, `pocitat_dph`, `celkem`) 
                                VALUES (" . $this->id_faktury . ",'" . $this->polozka_nazev . "'," . $this->polozka_jednotkova_cena . "," . $this->polozka_mnozstvi . ",'" . $this->polozka_dph . "'," . $this->polozka_celkem . ")";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "deletePolozkyFaktury") {
            $dotaz = "DELETE FROM `faktury_polozka`
						WHERE `id_faktury`=" . $this->id_faktury . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete") {
            $dotaz = "DELETE FROM `faktury`
						WHERE `id_faktury`=" . $this->id_faktury . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "deleteFakturaFromPlatby") {
            $dotaz = "update `objednavka_platba` set `id_faktury`=NULL
						WHERE `id_faktury`=" . $this->id_faktury . "";
            //echo $dotaz;
            return $dotaz;            
        } else if ($typ_pozadavku == "edit") {
            $dotaz = "SELECT * FROM `faktury`
						WHERE `id_faktury`=" . $this->id_faktury . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_platby") {
            $dotaz = "SELECT * FROM `objednavka_platba`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_zajezd") {
            $dotaz = "SELECT `termin_od`, `termin_do`,`serial`.`id_serial`,`serial`.`nazev`, `zajezd`.`nazev_zajezdu`,`serial`.`id_sml_podm`, `zajezd`.`id_zajezd`,`zajezd`.`od`, `zajezd`.`do` , `user_klient`.`id_klient` , `user_klient`.`jmeno`, `user_klient`.`prijmeni` ,
                                        `user_klient`.`ulice` ,`user_klient`.`mesto` ,`user_klient`.`psc` ,`user_klient`.`email` ,`user_klient`.`telefon` ,  
                                        `organizace`.`id_organizace`,`organizace`.`nazev` as nazev_organizace, `organizace`.`ico` as ico_organizace, `organizace`.`dic` as dic_organizace,
                                        `organizace_adresa`.`stat` as organizace_stat, `organizace_adresa`.`mesto` as organizace_mesto, `organizace_adresa`.`ulice` as organizace_ulice, `organizace_adresa`.`psc` as organizace_psc,  
                                        `kontaktni_adresa`.`stat` as kontaktni_stat,   `kontaktni_adresa`.`mesto` as kontaktni_mesto,   `kontaktni_adresa`.`ulice` as kontaktni_ulice,   `kontaktni_adresa`.`psc` as kontaktni_psc, 

                                        `oo`.`id_organizace` as `id_objednavajici_organizace` ,`oo`.`nazev` as objednavajici_nazev_organizace, `oo`.`ico` as objednavajici_ico_organizace, `oo`.`dic` as objednavajici_dic_organizace,
                                        `oo_adresa`.`stat` as objednavajici_organizace_stat, `oo_adresa`.`mesto` as objednavajici_organizace_mesto, `oo_adresa`.`ulice` as objednavajici_organizace_ulice, `oo_adresa`.`psc` as objednavajici_organizace_psc,  
                                        `ook_adresa`.`stat` as objednavajici_kontaktni_stat,   `ook_adresa`.`mesto` as objednavajici_kontaktni_mesto,   `ook_adresa`.`ulice` as objednavajici_kontaktni_ulice,   `ook_adresa`.`psc` as objednavajici_kontaktni_psc, 
                                        
                                        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`,
                                        `objednavka`.`id_objednavka`,`objednavka`.`celkova_cena`,`objednavka`.`zbyva_zaplatit`, `objednavka`.`pocet_noci` , `objednavka`.`pocet_osob`
                                        FROM `objednavka` join `serial` on (`objednavka`.`id_serial` = `serial`.`id_serial`) 
                                        join `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd` )
                                        left join `user_klient` on (`objednavka`.`id_klient` = `user_klient`.`id_klient` )
                                        left join (`organizace` 
                                            join `organizace_adresa` on (`organizace_adresa`.`id_organizace` = `organizace`.`id_organizace` and `organizace_adresa`.`typ_kontaktu`=1) 
                                            left join `organizace_adresa` as `kontaktni_adresa` on (`kontaktni_adresa`.`id_organizace` = `organizace`.`id_organizace` and `kontaktni_adresa`.`typ_kontaktu`=2) 
                                            left join `organizace_bankovni_spojeni` on (`organizace_bankovni_spojeni`.`id_organizace` = `organizace`.`id_organizace` and `organizace_bankovni_spojeni`.`typ_kontaktu`=1) 
                                            )on (`objednavka`.`id_agentury` = `organizace`.`id_organizace` )
                                            
                                        left join (`organizace` as `oo`
                                            join `organizace_adresa` as `oo_adresa` on (`oo_adresa`.`id_organizace` = `oo`.`id_organizace` and `oo_adresa`.`typ_kontaktu`=1) 
                                            left join `organizace_adresa` as `ook_adresa` on (`ook_adresa`.`id_organizace` = `oo`.`id_organizace` and `ook_adresa`.`typ_kontaktu`=2) 
                                            left join `organizace_bankovni_spojeni` as `oobs` on (`oobs`.`id_organizace` = `oo`.`id_organizace` and `oobs`.`typ_kontaktu`=1) 
                                            )on (`objednavka`.`id_organizace` = `oo`.`id_organizace` )
                                            
                                        left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                                        
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
           // echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_comgate") {
            $dotaz = "SELECT `organizace`.`id_organizace`,`organizace`.`nazev` as nazev_organizace, `organizace`.`ico` as ico_organizace, `organizace`.`dic` as dic_organizace,
                                        `organizace_adresa`.`stat` as organizace_stat, `organizace_adresa`.`mesto` as organizace_mesto, `organizace_adresa`.`ulice` as organizace_ulice, `organizace_adresa`.`psc` as organizace_psc                                        
                                        FROM `organizace` 
                                            join `organizace_adresa` on (`organizace_adresa`.`id_organizace` = `organizace`.`id_organizace` and `organizace_adresa`.`typ_kontaktu`=1)                                         
					WHERE `organizace`.`id_organizace`=" . Faktury::COMGATE_ID . "
					LIMIT 1";
            //echo $dotaz;
            return $dotaz;            
        } else if ($typ_pozadavku == "getPolozky") {
            $dotaz = "SELECT * FROM `faktury_polozka`
						WHERE `id_faktury`=" . $this->id_faktury . "
						Order By `id_polozky`";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "show") {
            $dotaz = "SELECT * FROM `faktury`
						WHERE `id_faktury`=" . $this->id_faktury . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "get_centralni_data") {
            $dotaz = "SELECT * FROM `centralni_data`
                                      ";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `faktury`
						WHERE `id_faktury`=" . $this->id_faktury . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        }
    }

    /**kontrola zda smim provest danou akci*/
    function legal($typ_pozadavku)
    {
        $zamestnanec = User_zamestnanec::get_instance();
        //z jadra zjistim ide soucasneho modulu
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        //podle jednotlivych typu pozadavku
        if ($typ_pozadavku == "new") {
            return $zamestnanec->get_bool_prava($id_modul, "create");

        } else if ($typ_pozadavku == "edit") {
            return $zamestnanec->get_bool_prava($id_modul, "read");

        } else if ($typ_pozadavku == "show") {
            return $zamestnanec->get_bool_prava($id_modul, "read");

        } else if ($typ_pozadavku == "create") {
            return $zamestnanec->get_bool_prava($id_modul, "create");

        } else if ($typ_pozadavku == "update") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create())
            ) {
                return true;
            } else {
                return false;
            }

        } else if ($typ_pozadavku == "delete") {
            if ($zamestnanec->get_bool_prava($id_modul, "delete_cizi") or
                ($zamestnanec->get_bool_prava($id_modul, "delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create())
            ) {
                return true;
            } else {
                return false;
            }
        }

        //neznámý požadavek zakážeme
        return false;
    }

    /**kontrola zda mam odpovidajici data*/
    function correct_data($typ_pozadavku)
    {
        $ok = 1;
        //kontrolovaná data: název informace, popisek
        if ($typ_pozadavku == "create" or $typ_pozadavku == "update") {
            /*if(!Validace::text($this->nazev) ){
                $ok = 0;
                $this->chyba("Musíte vyplnit název informace");
            }
            if(!Validace::text($this->popisek) ){
                $ok = 0;
                $this->chyba("Musíte vyplnit popisek informace");
            }*/

        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**zobrazeni formulare pro vytvoreni/editaci informace*/
    function show_form()
    {
        //dotaz na serial dle objednavky
        $this->zaloha_castka = $this->get_zaloha();
        $this->doplatek_castka = $this->get_doplatek();

        $typ_dokladu = "<div class=\"form_row\"> <div class=\"label_float_left\">Typ dokladu:</div> <div class=\"value\">
		<select name=\"typ_dokladu\">
			<option value=\"vystavena\" " . (($this->typ_dokladu == "vystavena") ? ("selected=\"selected\"") : ("")) . ">Vystavená</option>
			<option value=\"prijata\" " . (($this->typ_dokladu == "prijata") ? ("selected=\"selected\"") : ("")) . ">Pøijatá</option>
		</select>
		</div></div>\n";
        $typ_faktury = "<div class=\"form_row\"> <div class=\"label_float_left\">Typ faktury:</div> <div class=\"value\">
		<select name=\"typ_faktury\" id=\"typ_faktury\" onchange=\"changePriceName();\">
			<option value=\"normalni\" " . (($this->typ_faktury == "normalni") ? ("selected=\"selected\"") : ("")) . ">Normální</option>
			<option value=\"zaloha\" " . (($this->typ_faktury == "zaloha") ? ("selected=\"selected\"") : ("")) . ">Záloha</option>
			<option value=\"doplatek\" " . (($this->typ_faktury == "doplatek") ? ("selected=\"selected\"") : ("")) . ">Doplatek</option>
                        <option value=\"dobropis\" " . (($this->typ_faktury == "dobropis") ? ("selected=\"selected\"") : ("")) . ">Dobropis</option>    
		</select>
		</div></div>\n";

        $cislo_faktury_vystavovatele = "<div class=\"form_row\"> <div class=\"label_float_left\">Èíslo faktury vystavovatele: </div> <div class=\"value\"><input name=\"cislo_faktury_vystavovatele\" type=\"text\" value=\"" . $this->cislo_faktury_vystavovatele . "\" /></div></div>\n";

       /* $objednavka = "
                    <div class=\"form_row\">
                        ID objednávky: " . $this->id_objednavka . "<br/>
                        Zájezd:  " . $this->nazev_serialu . ", " . $this->nazev_zajezdu . " " . $this->od . " - " . $this->do . "
                    </div>
                    ";*/
        if ($this->typ_pozadavku == "new") {
            $cislo_faktury = "
                    <div class=\"form_row\">
                        Èíslo faktury: bude pøiøazeno po uložení objednávky                         
                    </div>
                    ";
        } else {
            $cislo_faktury = "
                    <div class=\"form_row\">
                     <div class=\"label_float_left\">Èíslo faktury:</div> <div class=\"value\">
                         <input name=\"cislo_faktury\" type=\"text\" value=\"" . $this->cislo_faktury . "\" />\n
                    </div></div>
                    <script  type=\"text/javascript\">
                        var cislo_faktury = \"".$this->cislo_faktury."\";
                    </script>
                    ";
        }
        $pata_faktury = "
                    <div class=\"form_row\">
                     <div class=\"label_float_left\">Text v patì faktury:</div> <div class=\"value\">
                         <input name=\"pata_faktury\" type=\"text\" value=\"" . $this->pata_faktury . "\"  style=\"width:400px;\"/>\n
                    </div></div>
                    ";
        $castka = "<div class=\"form_row\">
                            <b>Celková cena objednávky: $this->celkova_cena_objednavky $this->mena</b><br/>
                            <b>Celková èástka faktury: <span id=\"celkova_castka\">$this->celkova_castka</span> $this->mena</b>; Kurz: $this->kurz
                        </div>
                        <input name=\"celkova_castka\" type=\"hidden\" id=\"celkova_castka_input\" value=\"" . $this->celkova_castka . "\" />\n
                        <input name=\"mena\" type=\"hidden\" value=\"" . $this->mena . "\" />\n
                        <input name=\"kurz\" type=\"hidden\" value=\"" . $this->kurz . "\" />\n
                        <input name=\"id_objednavky\" type=\"hidden\" value=\"" . $this->id_objednavky . "\" />\n
                         <input name=\"id_objednavatele_klient\" type=\"hidden\" value=\"" . $this->id_objednavatele_klient . "\" />\n
                        <input name=\"id_objednavatele_organizace\" type=\"hidden\" value=\"" . $this->id_objednavatele_organizace . "\" />\n
                        ";

        $text_faktury = "<div class=\"form_row\"> Text faktury: <br/>
                        <textarea name=\"text_faktury\"  id=\"text_faktury_\" rows=\"25\" cols=\"125\">" . $this->text_faktury . "</textarea></div>\n";

        
        if($this->typ_pozadavku=="new"){
            $javascript_prijemci = "function zmena_prijemce(id){
                var prijemci = [";
            $varianty_prijemcu = "<span>";
            foreach ($this->mozni_prijemci_header as $key => $value) {
                $varianty_prijemcu .= "<input name=\"mozny_prijemce\" value=\"$key\" type=\"radio\" onchange=\"zmena_prijemce($key)\"/> $value | ";
                $javascript_prijemci .= " '".$this->mozni_prijemci[$key]."', ";
            }
            $javascript_prijemci .= "0];
                var text = prijemci[id];
                idTa = \"prijemce_text_\";
                
                //showHTML();
                document.getElementsByName(\"prijemce_text\")[0].value = text;
                idTa = \"prijemce_text_\";                
                oW = window.frames[0];
                showDesign();
                var i = 0;
             }";
            $varianty_prijemcu .= "</span>";
        }else{
            $varianty_prijemcu = "";
            $javascript_prijemci = "";
        }
        $text_dodavatel_prijemce = "
                    <div class=\"form_row\"><table><tr><td>Dodavatel<td>Pøíjemce
                          <tr><td><br/><textarea name=\"dodavatel_text\"  id=\"dodavatel_text_\" rows=\"10\" cols=\"60\">" . $this->dodavatel_text . "</textarea>
                              <td><b>Pøeddefinovaní pøíjemci:</b> $varianty_prijemcu
                              <textarea name=\"prijemce_text\"  id=\"prijemce_text_\" rows=\"10\" cols=\"60\">" . $this->prijemce_text . "</textarea>
                    </table>
                    </div>\n";
        $data = "
                    <div class=\"form_row\"><table><tr><td>Datum vytvoøení<td>Datum zdanitelného plnìni<td>Datum splatnosti
                          <tr><td><input name=\"datum_vytvoreni\" type=\"text\" value=\"" . $this->datum_vystaveni . "\" class=\"wide calendar-ymd\"/>
                              <td><input name=\"datum_zdanitelneho_plneni\" type=\"text\" value=\"" . $this->datum_zdanitelneho_plneni . "\" class=\"wide calendar-ymd\"/>
                              <td><input name=\"datum_splatnosti\" type=\"text\" value=\"" . $this->datum_splatnosti . "\" class=\"wide calendar-ymd\"/>
                    </table>
                    </div>\n";
        $uhrada = "
                    <div class=\"form_row\">
                     <div class=\"label_float_left\">Zpùsob úhrady:</div> <div class=\"value\">
                     <select name=\"zpusob_uhrady\">
                        <option value=\"prevod\" " . ($this->zpusob_uhrady == "prevod" ? " selected=\"selected\"" : "") . ">Bankovním pøevodem</option>
                        <option value=\"hotove\" " . ($this->zpusob_uhrady == "hotove" ? " selected=\"selected\"" : "") . ">Hotovì</option>
                    </select>
                    </div></div>
                    <div class=\"form_row\">
                     <div class=\"label_float_left\">Zaplaceno:</div> <div class=\"value\"><span id=\"zaplacenoText\"></span><input name=\"zaplaceno\" type=\"hidden\" value=\"$this->zaplaceno\" />
                    </div></div>
                    <div class=\"form_row\">
                     <div class=\"label_float_left\">Platby za objednávku:</div> <div class=\"value\">$this->platby                             
                    </div></div>
                    ";
        $polozky = "
                    <div class=\"form_row\">
                    <table><tr>
                    <th>Název<th>Jednotková cena<th>Množství<th>sazba DPH<th>Celkem
                    ";
        $polozka_i = 1;
        foreach ($this->polozky_faktury as $key => $polozka) {
            $polozky .= "
                              <tr><td><input name=\"polozka_nazev_" . $polozka_i . "\" type=\"text\" value=\"" . $polozka["nazev_polozky"] . "\" style=\"width:350px;\"/>
                              <td><input name=\"polozka_jednotkova_cena_" . $polozka_i . "\" onChange=\"computePrices();\" type=\"text\" value=\"" . $polozka["jednotkova_cena"] . "\" />
                              <td><input name=\"polozka_mnozstvi_" . $polozka_i . "\"  onChange=\"computePrices();\" type=\"text\" value=\"" . $polozka["mnozstvi"] . "\" />
                              <td><select name=\"polozka_dph_" . $polozka_i . "\" onChange=\"computePrices();\" >
                                    <option value=\"vcetneDPH\" " . ($polozka["dph"] == "vcetneDPH" ? "selected=\"selected\"" : "") . ">Vèetnì DPH</option>
                                    <option value=\"snizena\" " . ($polozka["dph"] == "snizena" ? "selected=\"selected\"" : "") . ">Snížená</option>
                                    <option value=\"zakladni\" " . ($polozka["dph"] == "zakladni" ? "selected=\"selected\"" : "") . ">Základní</option>
                                  </select>
                              <td><input name=\"polozka_celkem_" . $polozka_i . "\" type=\"text\" value=\"\" />";
            $polozka_i++;
        }
        //vlozim jednu prazdnou polozku
        $polozky .= "
                              <tr><td><input name=\"polozka_nazev_" . $polozka_i . "\" type=\"text\" value=\"\" style=\"width:350px;\"/>
                              <td><input name=\"polozka_jednotkova_cena_" . $polozka_i . "\"  onChange=\"computePrices();\" type=\"text\" value=\"\" />
                              <td><input name=\"polozka_mnozstvi_" . $polozka_i . "\"   onChange=\"computePrices();\" type=\"text\" value=\"\" />
                                  <td><select name=\"polozka_dph_" . $polozka_i . "\" onChange=\"computePrices();\" >
                                    <option value=\"vcetneDPH\">Vèetnì DPH</option>
                                    <option value=\"snizena\">Snížená</option>
                                    <option value=\"zakladni\">Základní</option>
                                  </select>
                              <td><input name=\"polozka_celkem_" . $polozka_i . "\" type=\"text\" value=\"\" />";
        $polozka_i++;
        $polozky .= "
                    </table>
                    </div>\n
                    ";
        $dph_zakladni = $this->centralni_data["ts_faktura:dph"];
        $dph_snizena = $this->centralni_data["ts_faktura:dph_snizena"];        
        $javascript_cena = "<script type=\"text/javascript\">
                        $javascript_prijemci
                        
                        var typ_editace = \"$this->typ_pozadavku\";
                        var last_typ_faktury = \"$this->typ_faktury\";
                        function nova_platba(){
                            var position = document.getElementById(\"nova_platba\");
                            var tabulka_platby = document.getElementById(\"tabulka_platba\");
                            tabulka_platby.style.display='table-row';
                            var castka = document.getElementsByName(\"polozka_celkem_1\")[0].value;
                            position.innerHTML = '<td><input type=\"radio\"  name=\"typ_dokladu\" id=\"typ_dokladu_prijmovy\" checked=\"checked\" value=\"prijmovy\" /> Pøíjmový <input type=\"radio\" name=\"typ_dokladu\" value=\"vydajovy\" /> Výdajový <td><input type=\"text\" value=\"\" name=\"cislo_dokladu\"/><td><input type=\"text\" value=\"'+castka+'\" name=\"nova_platba_castka\"/ onchange=\"changePriceName();\" ><td><input type=\"text\" value=\"" . Date("d.m.Y") . "\" name=\"nova_platba_splaceno\"/><td><input type=\"text\" name=\"nova_platba_zpusob_uhrady\"/><td>Pøiøazeno k faktuøe';
                            changePriceName();    
                         }
                         function openPdfNewWindow(){
                            var form = document.getElementById('faktury_form');
                            form.target='_blank';
                            form.submit();
                            setTimeout(function () {   
                                window.location='/admin/faktury.php?typ=faktury_list';
                            }, 1000);
                         }

                         function changePriceName(){
                            var plna_cena = " . $this->celkova_castka . ";
                            var zaloha = " . $this->zaloha_castka . ";
                            var doplatek = " . $this->doplatek_castka . ";
                            var typ_faktury = document.getElementById(\"typ_faktury\").value;                         
                            var castka_platby = 0;
                            var total_platby = 0;
                            for(i=1;i<50;i++){
                                var elements = document.getElementsByName(\"check_platby_\"+i);
                                for (index = 0; index < elements.length; ++index) {
                                    //dana platba existuje - zkontroluju zda je zaskrtnuto aby se pridala
                                    var pridano = elements[index].checked;
                                    if(pridano==true){
                                        var castka = document.getElementsByName(\"castka_platby_\"+i)[index].value;
                                        castka_platby = Number(castka)+Number(castka_platby);
                                    }    
                                }
                            }
                            var nova_platba = document.getElementsByName(\"nova_platba_castka\");
                            var typ_dokladu = document.getElementById(\"typ_dokladu_prijmovy\");
                            for (index = 0; index < nova_platba.length; ++index) {
                                //existuje nova platba - pridam ji do celkove castky
                                //musime rozlisit prijmovy a vydajovy doklad
                                if(typ_dokladu.checked){
                                    castka_platby = Number(castka_platby) + Number(nova_platba[index].value); 
                                }else{
                                    castka_platby = Number(castka_platby) - Number(nova_platba[index].value); 
                                }
                                  
                            }
                            
                            var typ_faktury_changed = true;
                            if(last_typ_faktury ==  typ_faktury){
                                typ_faktury_changed = false;
                            }else{
                                typ_faktury_changed = true;
                                last_typ_faktury = typ_faktury;
                            }  
                            //pridal jsem k fakture platbu, to ma prednost pred jinym pocitanim castky faktury - ale pouze pokud se jedna o vytvareni faktury
                            if(castka_platby > 0 && typ_editace==\"new\"){
                                if(typ_faktury == \"normalni\"){
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám za plnou cenu objednávky è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = castka_platby;    
                                }else if(typ_faktury == \"zaloha\"){ 
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám zálohu na objednávku è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = castka_platby;      
                                }else if(typ_faktury == \"doplatek\"){
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám doplatek k objednávce è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = castka_platby;      
                                }                                                            
                            }else if(typ_editace==\"new\"){
                                if(typ_faktury == \"normalni\"){
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám za plnou cenu objednávky è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = plna_cena;    
                                }else if(typ_faktury == \"zaloha\"){ 
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám zálohu na objednávku è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = zaloha;      
                                }else if(typ_faktury == \"doplatek\"){
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám doplatek k objednávce è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = doplatek;      
                                }
                            }else if(typ_editace==\"edit\" && typ_faktury_changed == true){/*doslo ke zmene typu faktury, musim zmenit i castky*/
                                if(typ_faktury == \"normalni\"){
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám za plnou cenu objednávky è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = plna_cena;    
                                }else if(typ_faktury == \"zaloha\"){ 
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám zálohu na objednávku è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = zaloha;      
                                }else if(typ_faktury == \"doplatek\"){
                                    document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Fakturujeme Vám doplatek k objednávce è. " . $this->id_objednavka . "\";
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = doplatek;      
                                }                                
                            }else {
                                //editujeme fakturu a nedoslo ke zmene typu faktury ->nic nemenim
                            }
                            
                            /*je treba vyresit nove veci spojene s typem platby = dobropis*/
                            if(typ_faktury == \"dobropis\"){
                                document.getElementsByName(\"polozka_nazev_1\")[0].value=\"Dobropisujeme Vám za objednávku è. " . $this->id_objednavka . "\";
                                document.getElementsByName(\"datum_zdanitelneho_plneni\")[0].value=\"".Date("d.m.Y")."\";    
                                syncTextarea();    
                                var str = \"".str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string)$this->text_faktury), "\0..\37'\\")))."\";
                                str = str.replace(\"Fakturujeme\", \"Dobropisujeme\");
                                document.getElementById(\"text_faktury_\").value = str;
                                idTa = \"text_faktury_\";
                                oW = window.frames[2];
                                showDesign();
                                /*prevracime zaporne hodnoty vratek*/
                                castka_platby = - castka_platby;
                                if(castka_platby > 0 && typ_editace==\"new\"){
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = castka_platby;  
                                }else if(typ_editace==\"edit\" && typ_faktury_changed == true){
                                    document.getElementsByName(\"polozka_jednotkova_cena_1\")[0].value = ".($this->informace["celkova_castka"]-$this->informace["zbyva_zaplatit"])."; 
                                }else{
                                    //editujeme fakturu a nedoslo ke zmene typu faktury ->nic nemenim
                                }
                            }
                            computePrices();
                            changeSplaceno();
                         }
			 function computePrices(){
                                var dph_zakladni = " . $dph_zakladni . ";
                                var dph_snizena = " . $dph_snizena . ";
                                var kurz = " . $this->kurz . ";
				var pocet_cen = 50; //maximalni pocet polozek                               
				var i = 0;
				var soucet = 0;
				while(i < pocet_cen){
					i++;
					var castka_input = \"polozka_jednotkova_cena_\"+i;
					var x = document.getElementsByName(castka_input);
					
                                        if(x[0]!=undefined && parseInt(x[0].value) > 0){
                                            var castka = parseInt(x[0].value);
                                            var mnozstvi_input = \"polozka_mnozstvi_\"+i;
                                            var x = document.getElementsByName(mnozstvi_input);
                                            var mnozstvi = parseInt(x[0].value);
                                            var celkova_castka = castka * mnozstvi * kurz;

                                            var dph_input = \"polozka_dph_\"+i;
                                            var x = document.getElementsByName(dph_input);
                                            var dph = x[0].value;
                                            if(dph == \"zakladni\"){
                                                celkova_castka = celkova_castka + (celkova_castka*dph_zakladni/100);
                                            }else if(dph == \"snizena\"){
                                                celkova_castka = celkova_castka + (celkova_castka*dph_snizena/100);
                                            }
                                            
                                            var celkova_input = \"polozka_celkem_\"+i;
                                            var x = document.getElementsByName(celkova_input);
                                            x[0].value = celkova_castka;
                                            
                                            soucet = soucet + celkova_castka;    
                                        }
					
				}
				var y = document.getElementById(\"celkova_castka\");
				y.innerHTML = \"<b> \"+soucet+\"</b>\";
                                var z = document.getElementById(\"celkova_castka_input\");
				z.value = soucet;
                                
			}
		</script> ";
        $make_whizywig = "
				<script language=\"JavaScript\" type=\"text/javascript\">
					makeWhizzyWig(\"prijemce_text_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");                                
					makeWhizzyWig(\"dodavatel_text_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");			
					makeWhizzyWig(\"text_faktury_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
                                </script>
				";


        //tvorba select typ informace
        $i = 0;
        $typ = "<div class=\"form_row\"> <div class=\"label_float_left\">Typ informace:</div>\n
							 <div class=\"value\">
							 <select name=\"typ_informace\">\n";

        //tvorba select_zeme_destinace
        $zeme = "<div class=\"form_row\"> <div class=\"label_float_left\">Zemì a destinace informace (pro zobrazení v menu):</div>\n
						 <div class=\"value\">
						<select name=\"zeme-destinace\">\n";
        //do promenne typy_serialu vytvorim instanci tridy seznam zemi


        if ($this->typ_pozadavku == "new") {
            //cil formulare
            $action = "?typ=faktury&amp;pozadavek=create&amp;id_objednavka=" . $this->id_objednavka . "";
            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("create")) {
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                //<input type=\"submit\" id=\"submit_pdf\"  onclick=\"openPdfNewWindow();\" name=\"submit_button\" value=\"Uložit a vygenerovat PDF\" />
                $submit = "<input type=\"submit\" name=\"submit_button\" value=\"Uložit\" />
                                                <input type=\"submit\" name=\"submit_button\" value=\"Uložit a zavøít\" />\n";
                                                
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení faktury</strong>\n";
            }
        } else if ($this->typ_pozadavku == "edit") {
            //cil formulare
            $action = "?id_faktury=" . $this->id_faktury . "&amp;typ=faktury&amp;pozadavek=update&amp;id_objednavka=" . $this->id_objednavka . "";
            if ($this->legal("update")) {
                //<input type=\"submit\" id=\"submit_pdf\" onclick=\"openPdfNewWindow();\" name=\"submit_button\" value=\"Uložit a vygenerovat PDF\" />
                $submit = "<input type=\"submit\" name=\"submit_button\" value=\"Uložit\" />
                                                <input type=\"submit\" name=\"submit_button\" value=\"Uložit a zavøít\" />                                                                                                
                                                <input type=\"submit\" name=\"submit_button\" value=\"Uložit a vygenerovat PDF\" />
                                                <input type=\"reset\" name=\"submit_button\" value=\"Pùvodní hodnoty\" />\n";


                /* MARTIN EMAILY */
                //tlacitka "email" a "odeslat fakturu emailem" je v javascsriptu, asi by ho to chtelo dat do hlavicky, zatim sem to nechal pro prehlednost tady

                $objednavajiciEmail = $this->get_klient_email();
                $pdfFaktury = $this->get_faktury_pdf();
                $out = "";

                if (!is_null($pdfFaktury)) {
                    //SEZNAM EMAILU
                    $out .= "<script type='text/javascript' src='./js/jquery-min.js'></script>";
                    $out .= "<script type='text/javascript' src='./js/common_functions.js'></script>";
                    $out .= "<script type='text/javascript' src='./js/faktury.js'></script>";
                    $out .= "<div class='list-add'>";
                    $out .= "<div id='email-status'></div>";
                    $out .= "<div class='submenu'><a class='btn btn-action' id='btn-send-pdf' href=''>odeslat fakturu emailem</a></div>";
                    $out .= "<form target='_blank' id='form-send-pdf' method='post'>";
                    $out .= "   <table class='list' id='tbl-emaily'>";
                    $out .= "       <tr><th colspan='3' class='header header-green align-left'>Odeslat pdf na email</th></tr>";
                    $out .= "       <tr><th></th><th>email</th><th>role</th></tr>";
                    $out .= "       <tr>";
                    $out .= "           <td><input type='checkbox' name='cb-emaily[]' value='$objednavajiciEmail' /></td>";
                    $out .= "           <td class='email'>$objednavajiciEmail</td>";
                    $out .= "           <td>objednavající</td>";
                    $out .= "       </tr>";
                    $out .= "       <tr class='edit'>";
                    $out .= "           <td></td>";
                    $out .= "           <td><input type='text' name='new-email' value='' /></td>";
                    $out .= "           <td><a id='btn-add-email' href=''>pøidat email</a></td>";
                    $out .= "       </tr>";
                    $out .= "   </table>";

                    //SEZNAM FAKTUR
                    $out .= "   <table class='list' id='tbl-pdf-voucher'>";
                    $out .= "       <tr><th colspan='4' class='header header-green align-left'>Výbìr pdf k odeslání</th></tr>";
                    $out .= "       <tr><th></th><th>datum & èas</th><th>soubor</th><th></th></tr>";

                    //prvni je defaultne vybrany
                    $checked = "checked='checked'";
                    foreach ($pdfFaktury as $filePath) {
                        $filePathDump = explode("_", $filePath);
                        $filePathDump = explode(".", $filePathDump[1]);
                        $dateTime = date("d.m.Y G:i:s", strtotime(VoucheryUtils::refractorDate($filePathDump[0])));
                        $out .= "   <tr>";
                        $out .= "       <td><input type='radio' name='rb-pdf-faktura' $checked value='$filePath' /></td>";
                        $out .= "       <td>$dateTime</td>";
                        $out .= "       <td><a target='_blank' href='" . FakturaTS::PDF_FOLDER . "$filePath'>faktura.pdf</a></td>";
                        $out .= "       <td class='edit'>";
                        $out .= "           <a class='confirm-delete' href='ts_faktura.php?page=delete-pdf&pdf_filename=$filePath&id_faktury=$this->id_faktury'>";
                        $out .= "               <img width='10' src='./img/delete-cross.png'/>";
                        $out .= "           </a>";
                        $out .= "       </td>";
                        $out .= "   </tr>";
                        $checked = "";
                    }

                    $out .= "       <tr class='edit'>";
                    $out .= "           <td colspan='4' class='align-right'>";
                    $out .= "               <a class='anchor-delete confirm-delete' href='ts_faktura.php?page=delete-all-pdf&id_faktury=$this->id_faktury'>smazat všechny</a>";
                    $out .= "           </td>";
                    $out .= "       </tr>";

                    $out .= "   </table>";
                    $out .= "</form>";
                    $out .= "</div>";
                }

                $emaily = $out;

                /* endregion MARTIN EMAILY */


            } else {
                $submit = "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci této faktury</strong>\n";
            }
        }

        $javascript_funkce = "
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig63.js\"></script>
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script>			
		";

        $vystup = $javascript_funkce .
            "<form id=\"faktury_form\" action=\"" . $action . "\" method=\"post\">" . $javascript_cena .
            $objednavka . $cislo_faktury . $typ_dokladu . $typ_faktury . $cislo_faktury_vystavovatele . $pata_faktury . $uhrada .
            $castka . $text_dodavatel_prijemce. $text_faktury . $data . $polozky . $make_whizywig .
            $submit .
            "</form>$emaily
                                            <script type=\"text/javascript\" >
                                                computePrices();
                                            </script>";
        return $vystup;
    }

    function get_id()
    {
        return $this->informace["id_faktury"];
    }

    function get_nazev()
    {
        if($this->serial["nazev_ubytovani"]!=""){
            return $this->serial["nazev_ubytovani"].", ".$this->serial["nazev"];
        }
        return $this->serial["nazev"];
    }

    function get_id_serial()
    {
        return $this->serial["id_serial"];
    }
    
    function get_id_zajezd()
    {
        return $this->serial["id_zajezd"];
    }
    
    function get_id_objednavka()
    {
        return $this->serial["id_objednavka"];
    }
    function get_objednavajici()
    {
        if($this->serial["objednavajici_nazev_organizace"]!=""){
            return $this->serial["objednavajici_nazev_organizace"];
        }
        return $this->serial["jmeno"] . " " . $this->serial["prijmeni"];
    }

    function get_zajezd()
    {
        if($this->serial["nazev_zajezdu"]!="" ){
            return $this->serial["nazev_zajezdu"].", ".$this->od . " - " . $this->do;
        }
        return $this->od . " - " . $this->do;
    }

    
    function get_klient_cele_jmeno()
    {
        return $this->serial["jmeno"] . " " . $this->serial["prijmeni"];
    }

    function get_klient_email()
    {
        return $this->serial["email"];
    }

    function get_cislo_faktury()
    {
        return $this->cislo_faktury;
    }

    function get_id_user_create()
    {
        //pokud uz id mame, vypiseme ho
        if ($this->id_user_create != 0) {
            return $this->id_user_create;
            //nemame id dokumentu (vytvarime ho)
        } else if ($this->id_faktury == 0) {
            return $this->id_zamestnance;
        } else {
            $data_id = mysqli_fetch_array($this->database->query($this->create_query("get_user_create")));
            $this->id_user_create = $data_id["id_user_create"];
            return $data_id["id_user_create"];
        }
    }
    function typ_dokladu($typ) { 
            if($typ == "prijmovy"){
                return "Pøíjmový";
            }else{
                return "Výdajový";
            }
            //return $this->radek["typ_dokladu"];

        }
        
    private function get_faktury_pdf()
    {
        $pdfFaktury = null;
        if ($handle = opendir(FakturaTS::PDF_FOLDER)) {
            while (false !== ($entry = readdir($handle))) {
                $entryDump = explode("_", $entry);
                if ($entryDump[0] == $this->id_faktury) {
                    $pdfFaktury[] = $entry;
                }
            }
            closedir($handle);
        }
        //nejnovejsi nejdrive
        @rsort($pdfFaktury);

        return $pdfFaktury;
    }
}


?>
