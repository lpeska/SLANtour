<?php
/**
 * klient_list.inc.php - tridy pro zobrazeni seznamu klientù
 */


/*------------------- SEZNAM klientu -------------------  */

class Organizace_list extends Generic_list
{
    //vstupni data
    protected $typ_pozadavku;
    protected $order_by;
    protected $nazev;
    protected $ico;
    protected $typ_organizace;

    protected $zacatek;

    protected $moznosti_editace;

//------------------- KONSTRUKTOR  -----------------
    /**konstruktor tøídy*/
    function __construct($typ_pozadavku, $nazev, $ico, $typ_organizace, $zacatek, $order_by, $moznosti_editace, $pocet_zaznamu = POCET_ZAZNAMU)
    {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->moznosti_editace = $this->check($moznosti_editace);

        $this->nazev = $this->check($nazev);
        $this->ico = $this->check($ico);
        $this->typ_organizace = $this->check_int($typ_organizace);
        $this->mesto = $this->check($_SESSION["organizace_mesto"]);

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
        if ($typ_pozadavku == "show_all" or $typ_pozadavku == "show_agentury") {
            //tvorba podminek dotazu
            if ($typ_pozadavku == "show_agentury") {
                $this->typ_organizace = "1";
            }
            if ($this->nazev != "") {
                $where_nazev = " o.nazev like '%" . $this->nazev . "%' &&";
            } else {
                $where_nazev = " ";
            }
            if ($this->mesto != "") {
                $where_mesto = " oa.mesto like '%" . $this->mesto . "%' &&";
            } else {
                $where_mesto = " ";
            }
            if ($this->ico != "") {
                $where_ico = " o.ico like '%" . $this->ico . "%' &&";
            } else {
                $where_ico = " ";
            }
            if ($this->typ_organizace != "") {
                $where_typ_organizace = " o.role like '%" . $this->typ_organizace . "%'  &&";
            } else {
                $where_typ_organizace = " ";
            }

            if ($this->zacatek != "") { //pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
                $limit = " limit " . $this->zacatek . "," . $this->pocet_zaznamu . " ";
            } else {
                $limit = " limit 0," . $this->pocet_zaznamu . " ";
            }
            $order = $this->order_by($this->order_by);

            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
            if ($only_count == 1) {
                $select = "select count(o.id_organizace) AS pocet";
                $limit = "";
            } else {
                $select = "select o.*, p.last_logon, p.provizni_koeficient,  oa.stat, oa.mesto, oa.ulice, oa.psc ";
            }

            if($typ_pozadavku == "show_agentury") {
                $limit = "";
            }
            //vysledny dotaz
            $dotaz = "$select
                        FROM organizace o
                        LEFT JOIN organizace_adresa oa ON (
                                o.id_organizace = oa.id_organizace &&
		                        CASE WHEN (SELECT id_organizace FROM organizace_adresa WHERE id_organizace = o.id_organizace && typ_kontaktu = 1) IS NOT NULL
    		                        THEN oa.typ_kontaktu = 1
        	                        ELSE oa.typ_kontaktu = 2
    	                        END
                            )
                        LEFT JOIN prodejce p ON (o.id_organizace = p.id_organizace)
                        WHERE $where_nazev $where_mesto $where_ico $where_typ_organizace 1
                        ORDER BY $order
                        $limit;";
//			echo $dotaz;
            return $dotaz;
        }
    }

    /**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
    function order_by($vstup)
    {
        switch ($vstup) {
            case "id_up":
                return "o.id_organizace";
                break;
            case "id_down":
                return "o.id_organizace DESC";
                break;
            case "nazev_up":
                return "o.nazev";
                break;
            case "nazev_down":
                return "o.nazev DESC";
                break;
            case "typ_up":
                return "o.role";
                break;
            case "typ_down":
                return "o.role DESC";
                break;
            case "ico_up":
                return "o.ico";
                break;
            case "ico_down":
                return "o.ico DESC";
                break;
            case "last_logon_up":
                return "p.last_logon";
                break;
            case "last_logon_down":
                return "p.last_logon DESC";
                break;
            case "adresa_up":
                return "oa.stat, oa.mesto, oa.ulice";
                break;
            case "adresa_down":
                return "oa.stat DESC, oa.mesto DESC, oa.ulice DESC";
                break;
        }
        //pokud zadan nespravny vstup, vratime zajezd.od
        return "o.nazev";
    }

    /**zobrazi formular pro filtorvani vypisu serialu*/
    function show_filtr()
    {

        //predani id_objednavka (pokud existuje - editace serialu->foto)
        $_GET["id_objednavka"] ? ($objednavka = "&amp;id_objednavka=" . $_GET["id_objednavka"] . "") : ($objednavka = "");

        //promenne, ktere musime pridat do odkazu
        $vars = "&amp;moznosti_editace=" . $this->moznosti_editace;

        //tvroba input uzivatelske jmeno
        $input_organizace = "<input name=\"organizace_nazev\" type=\"text\" value=\"" . $this->nazev . "\" />";
        //tvroba input uzivatelske jmeno
        $input_mesto = "<input name=\"organizace_mesto\" type=\"text\" value=\"" . $this->mesto . "\" />";
        //tvroba input jmeno
        $input_ico = "<input name=\"organizace_ico\" type=\"text\" value=\"" . $this->ico . "\" />";
        //tvroba input prijmeni
        $role_organizace = "<select name=\"organizace_typ\" >
                                        <option value=\"0\">---</option>";
        $i = 1;
        while (Serial_library::get_typ_organizace($i) != "") {
            if ($this->typ_organizace == $i) {
                $selected_role = "selected=\"selected\"";
            } else {
                $selected_role = "";
            }
            $role_organizace .= "<option value=\"" . $i . "\" " . $selected_role . ">" . Serial_library::get_typ_organizace($i) . "</option>";
            $i++;
        }
        $role_organizace .= "</select>";


        //tlacitko pro odeslani
        $submit = "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";

        //vysledny formular
        $vystup = "
			<form method=\"post\" action=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=jmeno_prijmeni_datum" . $objednavka . $vars . "\">
			<table class=\"filtr\">
				<tr>
					<td>Název organizace: " . $input_organizace . "</td>
                                        <td>Adresa - mìsto: " . $input_mesto . "</td>    
					<td>IÈO: " . $input_ico . "</td>
					<td>Role organizace: " . $role_organizace . "</td>
                                         
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
				<h3>Seznam organizací</h3>
			";
        }
        return $vystup;
    }

    /**zobrazi hlavicku k seznamu seriálù*/
    function show_list_header()
    {
        if (!$this->get_error_message()) {
            $vars = "&amp;moznosti_editace=" . $this->moznosti_editace;
            $_GET["id_objednavka"] ? ($objednavka = "&amp;id_objednavka=" . $_GET["id_objednavka"] . "") : ($objednavka = "");
            $vystup = "
				<table class=\"list\">
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=id_up" . $objednavka . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=id_down" . $objednavka . $vars . "\"></a>
							</div>
						</th>
						<th>Název organizace
						<div class='sort'>
							<a class='sort-up' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=nazev_up" . $objednavka . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=nazev_down" . $objednavka . $vars . "\"></a>
							</div>
						</th>
						<th>IÈO
						<div class='sort'>
							<a class='sort-up' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=ico_up" . $objednavka . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=ico_down" . $objednavka . $vars . "\"></a>
							</div>
						</th>
                                                <th>Role
                                                <div class='sort'>
							<a class='sort-up' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=role_up" . $objednavka . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=role_down" . $objednavka . $vars . "\"></a>
							</div>
						</th>
						<th>Adresa
						<div class='sort'>
							<a class='sort-up' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=adresa_up" . $objednavka . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=adresa_down" . $objednavka . $vars . "\"></a>
							</div>
						</th>
                                                <th>Poslední pøihlášení
                                                <div class='sort'>
							<a class='sort-up' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=last_logon_up" . $objednavka . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=organizace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;organizace_order_by=last_logon_down" . $objednavka . $vars . "\"></a>
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
                $pk = "";
                if ($this->suda == 1) {
                    $vypis = "<tr class=\"suda\">";
                } else {
                    $vypis = "<tr class=\"licha\">";
                }
                //text pro typ informaci
                if($this->radek["role"]==1){
                    $pk = ", Koeficient:".$this->radek["provizni_koeficient"];
                    
                /*    if($this->radek["provizni_koeficient"]==""){
                        mysqli_query($GLOBALS["core"]->database->db_spojeni,"INSERT INTO `prodejce` (`id_organizace`,`provizni_koeficient`,`ucet_potvrzen`)
						VALUES (" . $this->radek["id_organizace"] . ",1,0)");
                    }*/
                }
                $vypis = $vypis . "
							<td class=\"id\">" . $this->radek["id_organizace"] . "</td>
							<td class=\"jmeno\"> " . $this->radek["nazev"] . "</td>
							<td class=\"datum_narozeni\">" . $this->radek["ico"] . "</td>
                                                        <td class=\"datum_narozeni\">" . Serial_library::get_typ_organizace($this->radek["role"]) . $pk . "</td>
							<td class=\"adresa\">" . $this->radek["mesto"] . ", " . $this->radek["ulice"] . ", " . $this->radek["psc"] . "</td>
                                                            
                                                        <td class=\"adresa\">" . $this->radek["last_logon"] . "</td>
							<td class=\"menu\">";


                if ($this->moznosti_editace == "select_klient_objednavky") {
                    if ($adresa_objednavka = $core->get_adress_modul_from_typ("objednavky")) {
                        $vypis = $vypis . "
						 <a href=\"" . $adresa_objednavka . "?typ=rezervace&amp;pozadavek=new&amp;id_klient=" . $this->get_id_klient() . "\">vybrat klienta</a>
						 | <a href=\"" . $adresa_modulu . "?id_klient=" . $this->get_id_klient() . "&amp;typ=klient&amp;pozadavek=edit&amp;moznosti_editace=" . $this->moznosti_editace . "\">upravit/zobrazit</a>";
                    }
                } else if ($this->moznosti_editace == "add_orgaizace_to_objednavka") {
                    $vypis = $vypis . "
					<a href=\"/admin/rezervace.php?typ=rezervace&amp;pozadavek=add_agentura&amp;id_objednavka=" . $_GET["id_objednavka"] . "&amp;id_organizace=" . $this->radek["id_organizace"] . "\">pøidat agenturu k objednávce</a>";

                } else {
                    $vypis = $vypis . "
								<a href=\"?id_organizace=" . $this->radek["id_organizace"] . "&amp;typ=organizace&amp;pozadavek=edit\">upravit/zobrazit</a>";
                    if($this->radek["role"] == 1 or $this->radek["role"] == 5)
                        $vypis .= " | <a href=\"organizace_prodejce.php?page=prodejce-objednavky&action=list&id=" . $this->radek["id_organizace"] . "\">objednávky</a>";
                    $vypis .= " | <a class='anchor-delete' href=\"?id_organizace=" . $this->radek["id_organizace"] . "&amp;typ=organizace&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
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
                $vypis .= "<td>" . iconv("cp1250", "UTF-8", $this->get_id_klient()) . "</td>";
                $vypis .= "<td>" . iconv("cp1250", "UTF-8", $this->get_prijmeni()) . "</td>";
                $vypis .= "<td>" . iconv("cp1250", "UTF-8", $this->get_jmeno()) . "</td>";
                $vypis .= "<td>" . iconv("cp1250", "UTF-8", $this->change_date_en_cz($this->get_datum_narozeni())) . "</td>";
                $vypis .= "<td>" . iconv("cp1250", "UTF-8", $this->get_telefon()) . "</td>";
                $vypis .= "<td>" . iconv("cp1250", "UTF-8", $this->get_email()) . "</td>";
                $vypis .= "<td>" . iconv("cp1250", "UTF-8", $this->get_cislo_pasu()) . " / " . iconv("cp1250", "UTF-8", $this->get_cislo_op()) . "</td>"; //$string = iconv("UTF-8", "windows-1250", $this->get_ulice());
                $vypis .= "<td>" . iconv("cp1250", "UTF-8", $this->get_mesto()) . ", " . iconv("cp1250", "UTF-8", $this->get_ulice()) . ", " . iconv("cp1250", "UTF-8", $this->get_psc()) . "</td>";
                $vypis .= "<td class='edit'>
                                <form method='post' action='rezervace.php?id_klient=" . $this->get_id_klient() . "&id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace_osoby&pozadavek=create'>
                                    <input type='submit' value='Pridat' />
                                </form>
                               </td>";
                $vypis .= "</tr>";

                return $vypis;
            } else if ($typ_zobrazeni == "selector") {
                $vypis = "<option>" . $this->radek["nazev"] . "</option>";
                return $vypis;
            }

        }
        //no error message
    }

    public function printJson() {
        $out = "[";
        while ($this->get_next_radek()) {
            $out .= "{\"id\": \"".$this->radek["id_organizace"]."\", \"nazev\": \"".$this->radek["nazev"]."\"},";
        }
        $out = substr($out, 0, strlen($out) - 1);
        $out .= "]";

        return $out;
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