<?php
/**
 * klient_list.inc.php - tridy pro zobrazeni seznamu klientù
 */


/*------------------- SEZNAM klientu -------------------  */

class Klient_list extends Generic_list
{
    //vstupni data
    protected $id_serial;
    protected $id_zajezd;

    protected $typ_pozadavku;
    protected $order_by;
    protected $jmeno;
    protected $prijmeni;
    protected $datum_narozeni;
    protected $rok_narozeni;
    protected $mesto;
    protected $je_ca;

    protected $zacatek;

    protected $moznosti_editace;

//------------------- KONSTRUKTOR  -----------------
    /**konstruktor tøídy*/
    function __construct($typ_pozadavku, $jmeno, $prijmeni, $datum_narozeni, $je_ca, $zacatek, $order_by, $moznosti_editace, $pocet_zaznamu = POCET_ZAZNAMU, $id_serial = "", $id_zajezd = "")
    {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->moznosti_editace = $this->check($moznosti_editace);

        $this->id_serial = $this->check_int($id_serial);
        $this->id_zajezd = $this->check_int($id_zajezd);
        $this->mesto = $this->check($_SESSION["klient_mesto"]);
        $this->rok_narozeni = $this->check_int($_SESSION["klient_rok_narozeni"]);
        
        $this->jmeno = $this->check(iconv("UTF-8", "cp1250", $jmeno));
        $this->prijmeni = $this->check(iconv("UTF-8", "cp1250", $prijmeni));

        if ($this->typ_pozadavku == "show_all") {
            $this->jmeno = $this->check($jmeno);
            $this->prijmeni = $this->check($prijmeni);
        }
//        echo $jmeno . $this->jmeno . $prijmeni . $this->prijmeni;

        $this->datum_narozeni = $this->check($datum_narozeni);

        $this->je_ca = $this->check_int($je_ca);

        $this->zacatek = $this->check_int($zacatek);
        $this->order_by = $this->check($order_by);
        $this->pocet_zaznamu = $this->check_int($pocet_zaznamu);

        //pokud mam dostatecna prava pokracovat
        if ($this->legal()) {
            //ziskam celkovy pocet zajezdu ktere odpovidaji
            $data_pocet = $this->database->query($this->create_query($this->typ_pozadavku, 1))
            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            $zaznam_pocet = mysqli_fetch_array($data_pocet);
            $this->pocet_zajezdu = $zaznam_pocet["pocet"];

            //ziskani seznamu z databaze
            $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            //zjistuju, zda mam neco k zobrazeni
            if (mysqli_num_rows($this->data) == 0) {
                $this->chyba("Zadaným podmínkám nevyhovuje žádný objekt");
            }

        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }

    }

//------------------- METODY TRIDY -----------------
    /**vytvoreni dotazu ze zadanych parametru*/
    function create_query($typ_pozadavku, $only_count = 0)
    {
        if ($typ_pozadavku == "show_all" or $typ_pozadavku == "show_all_ajax" or $typ_pozadavku == "show_complex_filter") {
            //tvorba podminek dotazu
            if ($this->jmeno != "") {
                $where_jmeno = " `user_klient`.`jmeno` like '%" . $this->jmeno . "%' and";
            } else {
                $where_jmeno = " ";
            }
            if ($this->prijmeni != "") {
                $where_prijmeni = " `user_klient`.`prijmeni` like '%" . $this->prijmeni . "%' and";
            } else {
                $where_prijmeni = " ";
            }
            if ($this->mesto != "") {
                $where_mesto = " `user_klient`.`mesto` like '%" . $this->mesto . "%' and";
            } else {
                $where_mesto = " ";
            }
            if ($this->rok_narozeni != "" and $this->rok_narozeni != 0) {
                if(strlen((string) $this->rok_narozeni)==4){
                    $rok_rodne_cislo = substr((string) $this->rok_narozeni, 2);
                }else{
                    $rok_rodne_cislo = $this->rok_narozeni;
                }
                $where_datum_narozeni = " (`user_klient`.`datum_narozeni` like '%$this->rok_narozeni%' or
					`user_klient`.`rodne_cislo` like '" . $rok_rodne_cislo . "%' ) and";
            } else {
                $where_datum_narozeni = " ";
            }

            //	$where_ca=" `user_klient`.`uzivatel_je_ca` = ".$this->je_ca." and";


            if ($this->zacatek != "") { //pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
                $limit = " limit " . $this->zacatek . "," . $this->pocet_zaznamu . " ";
            } else {
                $limit = " limit 0," . $this->pocet_zaznamu . " ";
            }
            $order = $this->order_by($this->order_by);



            if($this->typ_pozadavku == "show_all"){
                    $join = "
                        left join `objednavka` on `objednavka`.`id_klient` = `user_klient`.`id_klient`
                        left join `objednavka_osoby` on `objednavka_osoby`.`id_klient` = `user_klient`.`id_klient`                
                    " ;
                    $select_count = ",
                        count(distinct `objednavka`.`id_objednavka`) as `objednavajici_count`,
                        count(distinct `objednavka_osoby`.`id_objednavka`) as `ucastnik_count`             
                    " ;                    
                    $gb = "group by `user_klient`.`id_klient`"     ;
            }else{
                $join = "";
                $select_count = "";
                $gb = "";
            
            }
            
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
            if ($only_count == 1) {
                $select = "select count(*) as `pocet`";
                $limit = "";
                $join = "";
                $select_count = "";
                $gb = "";
                $order = "`user_klient`.`id_klient`";
            
            } else {
                $select = "select `user_klient`.* ";
            }

            if ($typ_pozadavku == "show_complex_filter") {
                //print_r($_POST);
                
                $where_email = " `user_klient`.`email` != '' and";
                
                

                
                if ($_POST["id_serial"] > 0) {
                    $where_serial = " `serial`.`id_serial` = ".$this->check_int($_POST["id_serial"])." and";
                } else {
                    $where_serial = " ";
                }
                
                if ($_POST["id_typ"] > 0) {
                    $where_typ = " `serial`.`id_typ` = ".$this->check_int($_POST["id_typ"])." and";
                } else {
                    $where_typ = " ";
                }
                
                if ($_POST["od"] != "") {
                    $where_od = " `zajezd`.`od` >= '".$this->change_date_cz_en($this->check($_POST["od"]))."' and";
                } else {
                    $where_od = " ";
                }
                
                if ($_POST["do"] != "") {
                    $where_do = " `zajezd`.`do` <= '".$this->change_date_cz_en($this->check($_POST["do"]))."' and";
                } else {
                    $where_do = " ";
                }
                
                
                if ($_POST["zeme"] != "") {
                    $where_zeme = []  ;
                    foreach( $_POST["zeme"] as $zemeID ){
                        if($zemeID >0)  {
                            $where_zeme[] = " `zeme_serial`.`id_zeme` = ".$this->check_int($zemeID)." ";
                        }
                    }
                    if(sizeof((array)$where_zeme) > 0){
                        $where_zeme = "(".implode($where_zeme," or ").") and" ;
                    } else{
                        $where_zeme = " ";
                    }
                    
                } else {
                    $where_zeme = " ";
                }
                
                if ($_POST["export_csv"] == "") { //pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
                    $appliedLimit = $limit;
                } else {
                    $appliedLimit = "";
                }
                $order = $this->order_by($this->order_by);
                
                
                if ($_POST["checkboxNoZajezd"] > 0) {
                    $dotaz = "
                        SELECT user_klient.id_klient,jmeno, prijmeni, email, telefon, datum_narozeni, mesto,ulice,psc, 0 as objednavajici_count, 0 as  ucastnik_count
                        FROM user_klient
                              left join (
                                    `objednavka` as o1
                                    join serial as s1 on  `o1`.`id_serial` = `s1`.`id_serial`
                                    join zeme_serial as zs1 on  `zs1`.`id_serial` = `s1`.`id_serial`
                                    join zajezd as z1 on  `o1`.`id_zajezd` = `z1`.`id_zajezd`
                                  ) on `o1`.`id_klient` = `user_klient`.`id_klient`  
                                  
                              left join (
                                    `objednavka_osoby`
                                    join objednavka  on  `objednavka`.`id_objednavka` = `objednavka_osoby`.`id_objednavka`
                                    join serial on  `objednavka`.`id_serial` = `serial`.`id_serial`
                                    join zeme_serial on  `zeme_serial`.`id_serial` = `serial`.`id_serial`
                                    join zajezd on  `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`
                                  ) on `objednavka_osoby`.`id_klient` = `user_klient`.`id_klient`                                                     
                                  
                                  WHERE $where_email  `o1`.`id_klient` is null and   `objednavka_osoby`.`id_klient` is null
                                  
                            order by `prijmeni`,`jmeno`
                            $appliedLimit 
                           "; 
                }else{ 
                
                    $dotaz = "
                    SELECT id_klient,jmeno, prijmeni, email, telefon, datum_narozeni, mesto,ulice,psc, max(objednavajici_count) as objednavajici_count, max(ucastnik_count) as  ucastnik_count
                        FROM
                  
                        ((SELECT user_klient.*, count(distinct `objednavka`.`id_objednavka`) as `objednavajici_count`, 0 as `ucastnik_count`
                                  FROM user_klient
                                  left join (
                                    `objednavka`
                                    join serial on  `objednavka`.`id_serial` = `serial`.`id_serial`
                                    join zeme_serial on  `zeme_serial`.`id_serial` = `serial`.`id_serial`
                                    join zajezd on  `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`
                                  ) on `objednavka`.`id_klient` = `user_klient`.`id_klient`                                                      
                                  
                                  WHERE  $where_email $where_typ $where_zeme $where_serial $where_od $where_do 1
                                  group by `user_klient`.`email`
                                  
                            )    
                            UNION ALL 
                            (SELECT user_klient.*  , 0 as `objednavajici_count`, count(distinct `objednavka_osoby`.`id_objednavka`) as `ucastnik_count` 
                                  FROM user_klient
                                  left join (
                                    `objednavka_osoby`
                                    join objednavka  on  `objednavka`.`id_objednavka` = `objednavka_osoby`.`id_objednavka`
                                    join serial on  `objednavka`.`id_serial` = `serial`.`id_serial`
                                    join zeme_serial on  `zeme_serial`.`id_serial` = `serial`.`id_serial`
                                    join zajezd on  `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`
                                  ) on `objednavka_osoby`.`id_klient` = `user_klient`.`id_klient`   
                                  
                                  WHERE  $where_email $where_typ $where_zeme $where_serial $where_od $where_do  1
                                  group by `user_klient`.`email`
                                  
                            )) as tbl   
                            
                            group by `prijmeni`,`jmeno`,`email` 
                            order by `prijmeni`,`jmeno`
                            $appliedLimit  
                                    
                            ";
                   }                                
                   //echo $dotaz . "\n";
                return $dotaz;
              
          } else {
              //vysledny dotaz
              $dotaz = $select . $select_count . "
    					 from `user_klient` ".$join."
    					where " . $where_datum_narozeni . $where_jmeno . $where_prijmeni . $where_mesto. " 1
                        ".$gb."
    					order by " . $order . "
    					" . $limit . "";
    //           echo $dotaz . "<br/>";
    //            echo $this->typ_pozadavku;
              if ($this->typ_pozadavku == "show_all_ajax" || $this->typ_pozadavku == "show_no_limit_ajax") {
                    // $dotaz = iconv("cp1250", "UTF-8", $dotaz);
                    mysqli_query($GLOBALS["core"]->database->db_spojeni,"SET character_set_results=utf8");
                    mysqli_query($GLOBALS["core"]->database->db_spojeni,"SET character_set_connection=utf8");
                    mysqli_query($GLOBALS["core"]->database->db_spojeni,"SET character_set_client=cp1250");
              }
    //                        echo $dotaz;
              return $dotaz;
          }
        } else if ($typ_pozadavku == "show_no_limit_ajax") {
            mysqli_query($GLOBALS["core"]->database->db_spojeni,"SET character_set_results=utf8mb4");
            mysqli_query($GLOBALS["core"]->database->db_spojeni,"SET character_set_connection=utf8mb4");
            mysqli_query($GLOBALS["core"]->database->db_spojeni,"SET character_set_client=utf8mb4");
            $dotaz = "SELECT *
                            FROM user_klient
                            WHERE
                                CONCAT_WS(' ' COLLATE utf8mb4_general_ci, prijmeni, jmeno) LIKE '%".$_GET["query"]."%' ||
                                CONCAT_WS(' ' COLLATE utf8mb4_general_ci, jmeno, prijmeni) LIKE '%".$_GET["query"]."%'
                                COLLATE utf8mb4_general_ci
                            UNION SELECT *
                                FROM user_klient
                                WHERE id_klient LIKE '%".$_GET["query"]."%';";
//                echo $dotaz . "\n";
            return $dotaz;
        } 
        
        
    }

    /**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
    function order_by($vstup)
    {
        switch ($vstup) {
            case "id_up":
                return "`user_klient`.`id_klient`";
                break;
            case "id_down":
                return "`user_klient`.`id_klient` desc";
                break;
            case "jmeno_up":
                return "`user_klient`.`prijmeni`,`user_klient`.`jmeno`";
                break;
            case "jmeno_down":
                return "`user_klient`.`prijmeni` desc,`user_klient`.`jmeno` desc";
                break;
            case "narozeni_up":
                return "`user_klient`.`datum_narozeni`";
                break;
            case "narozeni_down":
                return "`user_klient`.`datum_narozeni` desc";
                break;
                
                
            case "objednavajici_up":
                return "objednavajici_count";
                break;
            case "objednavajici_down":
                return "objednavajici_count desc";
                break;
            case "ucastnik_up":
                return "ucastnik_count";
                break;
            case "ucastnik_down":
                return "ucastnik_count desc";
                break;                                

            case "adresa_up":
                return "`user_klient`.`mesto`,`user_klient`.`ulice`";
                break;
            case "adresa_down":
                return "`user_klient`.`mesto` desc,`user_klient`.`ulice` desc";
                break;
        }
        //pokud zadan nespravny vstup, vratime zajezd.od
        return "`user_klient`.`prijmeni`,`user_klient`.`jmeno`";
    }

    /**zobrazi formular pro filtorvani vypisu serialu*/
    function show_filtr()
    {

        //predani id_objednavka (pokud existuje - editace serialu->foto)
        $_GET["id_objednavka"] ? ($objednavka = "&amp;id_objednavka=" . $_GET["id_objednavka"] . "") : ($objednavka = "");

        //promenne, ktere musime pridat do odkazu
        $vars = "&amp;moznosti_editace=" . $this->moznosti_editace;

        //tvroba input uzivatelske jmeno
        $input_rok_narozeni = "<input name=\"klient_rok_narozeni\" type=\"text\" value=\"" . $this->rok_narozeni . "\" />";
        //tvroba input jmeno
        $input_jmeno = "<input name=\"klient_jmeno\" type=\"text\" value=\"" . $this->jmeno . "\" />";
        //tvroba input prijmeni
        $input_prijmeni = "<input name=\"klient_prijmeni\" type=\"text\" value=\"" . $this->prijmeni . "\" />";
        $input_mesto = "<input name=\"klient_mesto\" type=\"text\" value=\"" . $this->mesto . "\" />";
        //tvroba input prijmeni


        //$input_ca="Zobrazit agentury: <input name=\"je_ca\" type=\"radio\" value=\"1\" ".$selected_ca1."/> klienty: <input name=\"je_ca\" type=\"radio\" value=\"0\" ".$selected_ca0."/> ";


        //tlacitko pro odeslani
        $submit = "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";

        //vysledny formular
        $vystup = "
			<form method=\"post\" action=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=jmeno_prijmeni_datum" . $objednavka . $vars . "\">
			<table class=\"filtr\">
				<tr>
				    <td>Pøíjmení: " . $input_prijmeni . "</td>
					<td>Jméno: " . $input_jmeno . "</td>
					<td>Rok narození: " . $input_rok_narozeni . "</td>
                                        <td>Mìsto: " . $input_mesto . "</td>
					<td>" . $submit . "</td>
				</tr>
			</table>
			</form>
		";
        return $vystup;
    }

    function show_complex_filter()
    {

        //predani id_objednavka (pokud existuje - editace serialu->foto)
        
        $checkoxNoZajezd = "<input name=\"checkboxNoZajezd\" type=\"checkbox\" value=\"1\" " . (($_POST["checkboxNoZajezd"]==1)?("checked=\"checked\""):("")) . "\" />";

        $input_serial = "<input name=\"id_serial\" type=\"text\" value=\"" . $_POST["id_serial"] . "\" />";
        
        $input_od = "<input name=\"od\" class=\"calendar-ymd\" type=\"text\" value=\"" . $_POST["od"] . "\" />";      
        
        $input_do = "<input name=\"do\" class=\"calendar-ymd\" type=\"text\" value=\"" . $_POST["do"] . "\" />";

    		//tvorba select typ-podtyp
    		$typ="<select name=\"id_typ\">\n<option value=\"0\">--libovolný--</option>\n";
    		//do promenne typy_serialu vytvorim instanci tridy seznam typu serialu a nasledne vypisu seznam typu					
    		$typy_serialu = new Typ_list($this->id_zamestnance,"",$_POST["id_typ"],0);
    		//vypisu seznam typu a podtypu
    		$typ = $typ.$typy_serialu->show_list("select_typ");											
    		$typ=$typ."</select>\n\n";	


        //do promenne typy_serialu vytvorim instanci tridy seznam typu serialu a nasledne vypisu seznam typu
        $typy_zeme = new Zeme_list($this->id_zamestnance, "", $_POST["zeme"], "");

        $zeme = "<select name=\"zeme[]\"  multiple style=\"height:150px\">\n<option value=\"0\">--libovolná--</option>\n";
        $zeme = $zeme . $typy_zeme->show_list("select_zeme");
        $zeme = $zeme . "</select>\n\n";




        $submit = "
          <input type=\"submit\" name=\"submit_nahled\" value=\"Zobrazit náhled\" />
          <input type=\"submit\" name=\"export_csv\"  value=\"Export CSV\" />";

        //vysledny formular
        
        
        $vystup = "
			<form method=\"post\" action=\"?typ=klient_list&amp;pozadavek=show_complex_filter\">
			<table class=\"filtr\">
				<tr>	
            <td>Pouze klienti bez zájezdu: " . $checkoxNoZajezd . "</td>			  
            <td>Typ seriálu: " . $typ . "</td>
            <td>Zájezd od: " . $input_od . "</td>
            <td>do: " .  $input_do. "</td>
            <td>ID seriálu: " . $input_serial . "</td>
            <td>Zemì: " . $zeme . "</td>
					<td>" . $submit . "</td>
				</tr>
			</table>
			</form>
		";
        return $vystup;
    }

    /**zobrazi nadpis seznamu*/
    function show_header()
    {
        if ($this->moznosti_editace == "select_klient_objednavky") {
            $vystup = "
				<h3>Vyberte klienta, který zájezd objednává</h3>
			";
        } else {
            $vystup = "
				<h3>Seznam klientù</h3>
			";
        }
        return $vystup;
    }

    /**zobrazi hlavicku k seznamu seriálù*/
    function show_list_header()
    {
        if (!$this->get_error_message()) {
            $vars = "&amp;moznosti_editace=" . $this->moznosti_editace;

            $vystup = "
				<table class=\"list\">
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=id_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=id_down" . $vars . "\"></a>
							</div>
						</th>
						<th>Pøíjmení a jméno
						<div class='sort'>
							<a class='sort-up' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=jmeno_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=jmeno_down" . $vars . "\"></a>
							</div>
						</th>
						<th>Datum narození
						<div class='sort'>
							<a class='sort-up' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=narozeni_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=narozeni_down" . $vars . "\"></a>
							</div>
						</th>
                        <th>E-mail		
						</th>
						<th>Adresa
						<div class='sort'>
							<a class='sort-up' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=adresa_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=adresa_down" . $vars . "\"></a>
							</div>
						</th>
                        <th>Objednávající
						<div class='sort'>
							<a class='sort-up' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=objednavajici_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=objednavajici_down" . $vars . "\"></a>
							</div>
						</th>
                        <th>Úèastník
						<div class='sort'>
							<a class='sort-up' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=ucastnik_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=klient_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;klient_order_by=ucastnik_down" . $vars . "\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
			";


            return $vystup;
        }
    }

    /**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
    function show_list_item($typ_zobrazeni)
    {
        if (!$this->get_error_message()) {
            //z jadra ziskame informace o soucasnem modulu
            $core = Core::get_instance();
            $current_modul = $core->show_current_modul();
            $adresa_modulu = $core->get_adress_modul_from_typ("klienti");

            if ($typ_zobrazeni == "tabulka") {
                if ($this->suda == 1) {
                    $vypis = "<tr class=\"suda\">";
                } else {
                    $vypis = "<tr class=\"licha\">";
                }
                $bg_obj = "";
                //text pro typ informaci
                if($this->objednavajici_count() > 0){
                    $bg_obj = " style=\"background-color:lightgreen\"" ;
                }
                if($this->ucastnik_count() > 0){
                    $bg_uc = " style=\"background-color:lightgreen\"" ;
                }
                $vypis = $vypis . "
							<td class=\"id\">" . $this->get_id_klient() . "</td>
							<td class=\"jmeno\">" . $this->get_prijmeni() . " " . $this->get_jmeno() . "</td>
							<td class=\"datum_narozeni\">" . $this->change_date_en_cz($this->get_datum_narozeni()) . "</td>
                            <td class=\"adresa\">" . $this->get_email() . "</td>
							<td class=\"adresa\">" . $this->get_mesto() . ", " . $this->get_ulice() . ", " . $this->get_psc() . "</td>

                            <td class=\"adresa\"$bg_obj>" . $this->objednavajici_count() . "</td>
                            <td class=\"adresa\"$bg_uc>" . $this->ucastnik_count() . "</td>

							<td class=\"menu\">";


                if ($this->moznosti_editace == "select_klient_objednavky") {
                    if ($adresa_objednavka = $core->get_adress_modul_from_typ("objednavky")) {
                        $vypis = $vypis . "
						 <a href=\"" . $adresa_objednavka . "?typ=rezervace&amp;pozadavek=new&amp;id_klient=" . $this->get_id_klient() . "\">vybrat klienta</a>
						 | <a href=\"" . $adresa_modulu . "?id_klient=" . $this->get_id_klient() . "&amp;typ=klient&amp;pozadavek=edit&amp;moznosti_editace=" . $this->moznosti_editace . "\">upravit/zobrazit</a>";
                    }
                } else {

                    if ($adresa_objednavka = $core->get_adress_modul_from_typ("objednavky")) {
                        $vypis = $vypis . "
							  <a href=\"" . $adresa_modulu . "?id_klient=" . $this->get_id_klient() . "&amp;typ=klient&amp;pozadavek=objednavky\">objednávky</a>
	  							| <a href=\"" . $adresa_objednavka . "?typ=rezervace&amp;pozadavek=new&amp;id_klient=" . $this->get_id_klient() . "\">vytvoøit objednávku</a>";
                    }
                    $vypis = $vypis . "
								| <a href=\"" . $adresa_modulu . "?id_klient=" . $this->get_id_klient() . "&amp;typ=klient&amp;pozadavek=edit\">upravit/zobrazit</a>
                                                                | <a target=\"_blank\" href=\"" . "slouceni_klientu.php?page=duplicit_merge&amp;action=show-clients&amp;filter-firstname=".$this->get_jmeno()."&amp;filter-surname=".$this->get_prijmeni()."&amp;filter-email=".$this->get_email()."\">slouèit duplicitní</a>
								| <a class='anchor-delete' href=\"" . $adresa_modulu . "?id_klient=" . $this->get_id_klient() . "&amp;typ=klient&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							";
                    //pokud je klient vytvoøen cestovní kanceláøí, je zde odkaz pro vytvoøení uživ. jména a hesla
                }

                $vypis = $vypis . "

							</td>
						</tr>";

                return $vypis;


            } else if ($typ_zobrazeni == "tabulka_objednavka") {
                GLOBAL $rezervace;
                if ($this->suda == 1) {
                    $vypis = "<tr class=\"suda\">";
                } else {
                    $vypis = "<tr class=\"licha\">";
                }
                //text pro typ informaci
                $vypis = $vypis . "
							<td class=\"id\">" . $this->get_id_klient() . "</td>
							<td class=\"jmeno\">" . $this->get_prijmeni() . " " . $this->get_jmeno() . "</td>
							<td class=\"datum_narozeni\">" . $this->change_date_en_cz($this->get_datum_narozeni()) . "</td>
							<td class=\"adresa\">" . $this->get_mesto() . ", " . $this->get_ulice() . ", " . $this->get_psc() . "</td>
							<td class=\"menu\">";

                //mam-li povoleni otevrit klienty, vypisu menu na jejich zobrazeni
                $adresa_objednavka = $core->get_adress_modul_from_typ("objednavky");
                if ($adresa_objednavka !== false) {
                    $vypis = $vypis . "
							  <a href=\"" . $adresa_objednavka . "?id_klient=" . $this->get_id_klient() . "&amp;id_objednavka=" . $rezervace->get_id() . "&amp;typ=rezervace_osoby&amp;pozadavek=create\">pøidat klienta</a>";
                }

                $vypis = $vypis . "
								| <a href=\"" . $adresa_modulu . "?id_klient=" . $this->get_id_klient() . "&amp;typ=klient&amp;pozadavek=edit\">upravit/zobrazit</a>
							</td>
						</tr>";

                return $vypis;
            } else if ($typ_zobrazeni == "klient_ajax") {
                if ($this->suda == 1) {
                    $vypis .= "<tr class=\"suda\">";
                } else {
                    $vypis .= "<tr class=\"licha\">";
                }
                $vypis .= "<td>" . $this->get_id_klient() . "</td>";
                $vypis .= "<td>" . $this->get_prijmeni() . "</td>";
                $vypis .= "<td>" . $this->get_jmeno() . "</td>";
                $vypis .= "<td>" . $this->change_date_en_cz($this->get_datum_narozeni()) . "</td>";
                $vypis .= "<td>" . $this->get_telefon() . "</td>";
                $vypis .= "<td>" . $this->get_email() . "</td>";
                $vypis .= "<td>" . $this->get_cislo_pasu() . " / " . $this->get_cislo_op() . "</td>"; //$string = iconv("UTF-8", "windows-1250", $this->get_ulice());
                $vypis .= "<td>" . $this->get_mesto() . ", " . $this->get_ulice() . ", " . $this->get_psc() . "</td>";
                $vypis .= "<td class='edit'>
                                <form method='post' action='rezervace.php?id_klient=" . $this->get_id_klient() . "&id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace_osoby&pozadavek=create'>
                                    <input type='submit' value='Pridat' />
                                </form>
                               </td>";
                $vypis .= "</tr>";

                return $vypis;
            } else if ($typ_zobrazeni == "klient_ajax_uzivatele") {
                if ($this->suda == 1) {
                    $vypis .= "<tr class=\"suda\">";
                } else {
                    $vypis .= "<tr class=\"licha\">";
                }
                $vypis .= "<td class='id_klient'>" . iconv("cp1250", "UTF-8", $this->get_id_klient()) . "</td>";
                $vypis .= "<td class='prijmeni'>" . iconv("cp1250", "UTF-8", $this->get_prijmeni()) . "</td>";
                $vypis .= "<td class='jmeno'>" . iconv("cp1250", "UTF-8", $this->get_jmeno()) . "</td>";
                $vypis .= "<td class='telefon'>" . iconv("cp1250", "UTF-8", $this->get_telefon()) . "</td>";
                $vypis .= "<td class='email'>" . iconv("cp1250", "UTF-8", $this->get_email()) . "</td>";
                $vypis .= "<td class='edit'>
                                <input type='submit' onclick='copy_user(" . $this->get_id_klient() . ", this);' value='" . iconv("cp1250", "UTF-8", "Použít") . "' />
                               </td>";
                $vypis .= "</tr>";

                return $vypis;
            } else if ($typ_zobrazeni == "csv_export") {

                $vypis = "\"".$this->get_id_klient()."\";\"".$this->get_prijmeni()."\";\"".$this->get_jmeno()."\";\"".$this->get_email()."\";\"".$this->get_telefon()."\";\"".$this->get_mesto()."\"\n";              

                return $vypis;
            }

        }
        //no error message
    }

    public function printJson()
    {
        $klienti = array();
        while ($this->get_next_radek()) {
            if ($this->radek["jmeno"] == "" || $this->radek["prijmeni"] == "")
                continue;
            $klient = new stdClass();
            $datumNarozeniBrackets = $this->radek["datum_narozeni"] == "0000-00-00" ? "" : " (" . $this->change_date_en_cz($this->radek["datum_narozeni"]) . ")";
            $datumNarozeni = $this->radek["datum_narozeni"] == "0000-00-00" ? "" : $this->change_date_en_cz($this->radek["datum_narozeni"]);
            $mesto = $this->radek["mesto"] == "" ? "" : " (" . $this->radek["mesto"] . ")";
            $klient->id = $this->radek["id_klient"];
            $klient->nazev = $this->radek["prijmeni"] . " " . $this->radek["jmeno"] . $mesto . "$datumNarozeniBrackets";
            $klient->prijmeni = $this->radek["prijmeni"];
            $klient->jmeno = $this->radek["jmeno"];
            $klient->titul = $this->radek["titul"];
            $klient->datumNarozeni = $this->radek["datum_narozeni"];
            $klient->rodneCislo = $this->radek["rodne_cislo"];
            $klient->email = $this->radek["email"];
            $klient->telefon = $this->radek["telefon"];
            $klient->cisloOP = $this->radek["cislo_op"];
            $klient->cisloPasu = $this->radek["cislo_pasu"];
            $klient->mesto = $this->radek["mesto"];
            $klient->ulice = $this->radek["ulice"];
            $klient->psc = $this->radek["psc"];
            $klient->datumNarozeni = $datumNarozeni;
            $klienti[] = $klient;
        }

        return json_encode($klienti);
    }

    /**zobrazi odkazy na dalsi stranky vypisu*/
    function show_strankovani()
    {
        if ($this->pocet_zajezdu != 0 and $this->pocet_zaznamu != 0) {
            //prvni cislo stranky ktere zobrazime
            $act_str = $this->zacatek - (10 * $this->pocet_zaznamu);
            if ($act_str < 0) {
                $act_str = 0;
            }

            //zjistim vsechny dalsi promenne, odstranim z nich ale str
            $promenne = ereg_replace("str=[0-9]*&?", "", $_SERVER["QUERY_STRING"]);

            //odkaz na prvni stranku
            $vypis = "<div class=\"strankovani\"><a href=\"?str=0&amp;" . $promenne . "\" title=\"první stránka\">&lt;&lt;</a> &nbsp;";

            //odkaz na dalsi stranky z rozsahu
            while (($act_str < $this->pocet_zajezdu) and ($act_str <= $this->zacatek + (10 * $this->pocet_zaznamu))) {
                if ($this->zacatek != $act_str) {
                    $vypis = $vypis . "<a href=\"?str=" . $act_str . "&amp;" . $promenne . "\" title=\"strana " . (1 + ($act_str / $this->pocet_zaznamu)) . "\">" . (1 + ($act_str / $this->pocet_zaznamu)) . "</a> ";
                } else {
                    $vypis = $vypis . (1 + ($act_str / $this->pocet_zaznamu)) . " ";
                }
                $act_str = $act_str + $this->pocet_zaznamu;
            }

            //odkaz na posledni stranku
            $posl_str = $this->pocet_zaznamu * floor(($this->pocet_zajezdu - 1) / $this->pocet_zaznamu);
            $vypis = $vypis . " &nbsp; <a href=\"?str=" . $posl_str . "&amp;" . $promenne . "\" title=\"poslední stránka\">&gt;&gt;</a></div>";

            return $vypis;
        }
    }


    /**zjistim, zda mam opravneni k pozadovane akci*/
    function legal()
    {
        $zamestnanec = User_zamestnanec::get_instance();
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        return $zamestnanec->get_bool_prava($id_modul, "read");
    }

    //vrati true, pokud klient má aktivovaný úèet
    function get_ucet()
    {
        if (!$this->radek["uzivatelske_jmeno"] and $this->radek["vytvoren_ck"] and !$this->radek["ucet_potvrzen_klientem"]) {
            return true;
        }
        return false;
    }

    /*metody pro pristup k parametrum*/
    function get_id_klient()
    {
        return $this->radek["id_klient"];
    }

    function get_jmeno()
    {
        return $this->radek["jmeno"];
    }

    function get_prijmeni()
    {
        return $this->radek["prijmeni"];
    }

    function get_datum_narozeni()
    {
        return $this->radek["datum_narozeni"];
    }

    function get_ico()
    {
        return $this->radek["ico"];
    }

    function get_telefon()
    {
        return $this->radek["telefon"];
    }

    function get_email()
    {
        return $this->radek["email"];
    }

    function get_cislo_pasu()
    {
        return $this->radek["cislo_pasu"];
    }

    function get_cislo_op()
    {
        return $this->radek["cislo_op"];
    }

    function get_mesto()
    {
        return $this->radek["mesto"];
    }

    function get_ulice()
    {
        return $this->radek["ulice"];
    }

    function get_psc()
    {
        return $this->radek["psc"];
    }


    function objednavajici_count()
    {
        return $this->radek["objednavajici_count"];
    }
    function ucastnik_count()
    {
        return $this->radek["ucastnik_count"];
    }


    public function getZacatek()
    {
        return $this->zacatek;
    }

    public function getPocetZajezdu()
    {
        return $this->pocet_zajezdu;
    }

    public function getPocetZaznamu()
    {
        return $this->pocet_zaznamu;
    }

}

?>
