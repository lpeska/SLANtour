<?php
/**
 * informace_foto.inc.php - trida pro zobrazeni seznamu fotek informací
 *                                            - a jejich create, update, delete
 */

/*------------------- SEZNAM fotografii -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class Foto_objekty extends Generic_list
{
    protected $typ_pozadavku;
    protected $id_objektu;
    protected $id_zamestnance;
    protected $id_foto;
    protected $zakladni_foto;
    protected $zakladni_pro_typ;

    public $database; //trida pro odesilani dotazu

    //------------------- KONSTRUKTOR -----------------
    /** konstruktor tøídy na základì typu pozadavku*/
    function __construct($typ_pozadavku, $id_zamestnance, $id_objektu, $id_foto = "", $zakladni_foto = "", $zakladni_pro_typ = "", $id_objekt_kategorie = "")
    {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola dat
        $this->lastok = 0;
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->id_objektu = $this->check_int($id_objektu);
        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_foto = $this->check_int($id_foto);
        $this->zakladni_foto = $this->check_int($zakladni_foto);
        $this->zakladni_pro_typ = $this->check_int($zakladni_pro_typ);
        $this->id_objekt_kategorie = $this->check_int($id_objekt_kategorie);
        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku)) {
            //na zaklade typu pozadavku vytvorim dotaz

            //pokud chceme vytvorit zakladnifoto, nejdriv vsechny oznacime za nezakladni
            if (($this->typ_pozadavku == "update" or $this->typ_pozadavku == "create") and $this->zakladni_foto == 1) {
                $remove_zakladni = $this->database->query($this->create_query("remove_zakladni_foto"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
            $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            //vygenerování potvrzovací hlášky
            if (!$this->get_error_message()) {
                $this->confirm("Požadovaná akce probìhla úspìšnì");
            }
            if ($this->typ_pozadavku == "create") {
                /*uprava zavislych objektu*/
                $sql = "SELECT `serial`.`id_serial` FROM `serial` join `objekt_serial` on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                                        where `objekt_serial`.`id_objektu`=" . $this->id_objektu . " and `id_ridici_objekt`=" . $this->id_objektu . "";
                //echo $sql;

                $data_serial = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row_serial = mysqli_fetch_array($data_serial)) {
                    $create_foto = 1;
                    $sql_foto_exist = "select * from `foto_serial` where `id_serial`=" . $row_serial["id_serial"] . " and `id_foto`=" . $this->id_foto . " limit 1";
                    $data_foto_exist = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_foto_exist);
                    while ($row_foto_exist = mysqli_fetch_array($data_foto_exist)) {
                        $create_foto = 0;
                    }
                    if ($create_foto) {
                        $sql_insert_foto = "INSERT INTO `foto_serial`(`id_serial`, `id_foto`, `zakladni_foto`)
                                                VALUES (" . $row_serial["id_serial"] . "," . $this->id_foto . ",0)";
                        mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_insert_foto);
                    }
                }
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }


    }

//------------------- METODY TRIDY -----------------	
    /**vytvoreni dotazu na zaklade typu pozadavku*/
    function create_query($typ_pozadavku)
    {
        if ($typ_pozadavku == "show") {
            $dotaz = "select `foto_objekty`.`id_objektu`, `foto_objekty`.`zakladni_foto`,
							`foto`.`id_foto`,	`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url`
                                                        
					  from  `foto_objekty` join
						`foto` on (`foto`.`id_foto` =`foto_objekty`.`id_foto`) 
                                                    
					where `foto_objekty`.`id_objektu`= " . $this->id_objektu . "
					order by `foto_objekty`.`zakladni_foto` desc,`foto`.`id_foto` 
                                         ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_ok") {
            $dotaz = "
                              select    `objekt_kategorie`.`nazev`,`objekt_kategorie`.`id_objektu`,`foto_objekt_kategorie`.`id_objekt_kategorie`, `foto_objekt_kategorie`.`zakladni_foto`,
					`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url`
					  from  `foto_objekt_kategorie` join
						`foto` on (`foto`.`id_foto` =`foto_objekt_kategorie`.`id_foto`) join
                                                `objekt_kategorie` on (`foto_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie`)
					where `objekt_kategorie`.`id_objektu`= " . $this->id_objektu . "
					order by `foto_objekt_kategorie`.`id_objekt_kategorie`,`foto_objekt_kategorie`.`zakladni_foto` desc,`foto`.`id_foto`          ";
            // echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "remove_zakladni_foto") {
            $dotaz = "UPDATE `foto_objekty`
								SET `zakladni_foto`=0
								WHERE `id_objektu`=" . $this->id_objektu . " and `zakladni_foto`=1";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "create") {
            $dotaz = $dotaz .
                "INSERT INTO `foto_objekty`
                    (`id_objektu`,`id_foto`,`zakladni_foto`)
                VALUES
                    (" . $this->id_objektu . "," . $this->id_foto . "," . $this->zakladni_foto . ");";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "create_ok") {
            $dotaz = $dotaz .
                "INSERT INTO `foto_objekt_kategorie`
                    (`id_objekt_kategorie`,`id_foto`,`zakladni_foto`)
                VALUES
                    (" . $this->id_objekt_kategorie . "," . $this->id_foto . "," . $this->zakladni_foto . ");";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "update") {
            $dotaz = $dotaz . "UPDATE `foto_objekty`
						SET `zakladni_foto`=" . $this->zakladni_foto . "
						WHERE `id_objektu`=" . $this->id_objektu . " and `id_foto`=" . $this->id_foto . "
						LIMIT 1;";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "delete") {
            $dotaz = "DELETE FROM `foto_objekty`
						WHERE `id_objektu`=" . $this->id_objektu . " and `id_foto`=" . $this->id_foto . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete_ok") {
            $dotaz = "DELETE FROM `foto_objekt_kategorie`
						WHERE `id_objekt_kategorie`=" . $this->id_objekt_kategorie . " and `id_foto`=" . $this->id_foto . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `informace`
						WHERE `id_objektu`=" . $this->id_objektu . "
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
        $id_modul_foto = $core->get_id_modul_from_typ("fotografie");

        if ($typ_pozadavku == "show" or $typ_pozadavku == "show_ok") {
            return ($zamestnanec->get_bool_prava($id_modul, "read") and  $zamestnanec->get_bool_prava($id_modul_foto, "read"));

        } else if ($typ_pozadavku == "create" or $typ_pozadavku == "create_ok" or $typ_pozadavku == "update" or $typ_pozadavku == "delete" or $typ_pozadavku == "delete_ok") {
            if (($zamestnanec->get_bool_prava($id_modul, "edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_foto, "read")) or
                ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_foto, "read") and $zamestnanec->get_id() == $this->get_id_user_create())
            ) {
                return true;
            } else {
                return false;
            }
        }

        //neznámý požadavek zakážeme
        return false;
    }

    /**zobrazi hlavicku k seznamu fotografií*/
    function show_list_header()
    {
        $vystup = "
				<table class=\"list\">
					<tr>
						<th>Foto</th>
						<th>Id</th>
						<th>Název / popisek</th>
						<th>Možnosti editace</th>
					</tr>
		";
        return $vystup;
    }

    /**zobrazi prvek  seznamu fotek*/
    function show_list_item($typ_zobrazeni)
    {
        //z jadra ziskame informace o soucasnem modulu
        $core = Core::get_instance();
        $current_modul = $core->show_current_modul();
        $adresa_modulu = $current_modul["adresa_modulu"];

        $vypis = "";
        if ($typ_zobrazeni == "tabulka") {
            if ($this->lastok != $this->radek["id_objekt_kategorie"]) {
                $this->lastok = $this->radek["id_objekt_kategorie"];
                $vypis .= "<tr><th colspan=\"4\">" . $this->radek["nazev"] . "</th></tr>";

            }

            if ($this->suda == 1) {
                $vypis .= "<tr class=\"suda\">";
            } else {
                $vypis .= "<tr class=\"licha\">";
            }
            if ($this->typ_pozadavku == "show_ok") {
                //zobrazuju jine menu, nechci tam vubec davat zakladni foto + smazani ma jiny odkaz
                $delete_href = "<a href=\"" . $adresa_modulu . "?id_objektu=" . $this->get_id_objektu() . "&amp;id_foto=" . $this->get_id_foto() . "&amp;id_objekt_kategorie=" . $this->get_id_objekt_kategorie() . "&amp;typ=foto_ok&amp;pozadavek=delete_ok\">odebrat</a> <br/>";
            } else {
                //pokud fotka neni zakladni, dame do menu moznost ji vytvorit
                if (!$this->get_zakladni_foto()) {
                    $zakl_foto = "<a href=\"" . $adresa_modulu . "?id_objektu=" . $this->get_id_objektu() . "&amp;id_foto=" . $this->get_id_foto() . "&amp;typ=foto&amp;pozadavek=update&amp;zakladni_foto=1\">zmìnit na základní foto</a> | ";
                } else {
                    $text_zakladni = "<b style=\"color:green;\">Základní foto</b>";
                    $zakl_foto = "<a href=\"" . $adresa_modulu . "?id_objektu=" . $this->get_id_objektu() . "&amp;id_foto=" . $this->get_id_foto() . "&amp;typ=foto&amp;pozadavek=update&amp;zakladni_foto=0\">odznaèit základní</a> | ";
                }
                $delete_href = "<a href=\"" . $adresa_modulu . "?id_objektu=" . $this->get_id_objektu() . "&amp;id_foto=" . $this->get_id_foto() . "&amp;typ=foto&amp;pozadavek=delete\">odebrat</a> <br/>";
            }


            $vypis = $vypis . "
							<td  class=\"foto\">
								<a href=\"/" . ADRESAR_FULL . "/" . $this->get_foto_url() . "\">
								<img src=\"/" . ADRESAR_MINIIKONA . "/" . $this->get_foto_url() . "\"
									  alt=\"" . $this->get_nazev_foto() . " - " . $this->get_popisek_foto() . "\"
									  width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"nazev\">" . $this->get_id_foto() . "</td>
							<td class=\"nazev\">" . $this->get_nazev_foto() . "<br/>" . $this->get_popisek_foto() . $text_zakladni . "</td>
							<td class=\"menu\">
								" . $zakl_foto . "
                                                                " . $delete_href . "
							</td>
						</tr>";
            return $vypis;
        }
    }

    /*metody pro pristup k parametrum*/
    function get_id_objektu()
    {
        return $this->radek["id_objektu"];
    }

    function get_id_foto()
    {
        return $this->radek["id_foto"];
    }

    function get_id_objekt_kategorie()
    {
        return $this->radek["id_objekt_kategorie"];
    }

    function get_zakladni_foto()
    {
        return $this->radek["zakladni_foto"];
    }

    function get_nazev_foto()
    {
        return $this->radek["nazev_foto"];
    }

    function get_popisek_foto()
    {
        return $this->radek["popisek_foto"];
    }

    function get_foto_url()
    {
        return $this->radek["foto_url"];
    }

    function get_id_user_create()
    {
        //pokud uz id mame, vypiseme ho
        if ($this->id_user_create != 0) {
            return $this->id_user_create;
            //nemame id dokumentu (vytvarime ho)
        } else if ($this->id_objektu == 0) {
            return $this->id_zamestnance;
        } else {
            $data_id = mysqli_fetch_array($this->database->query($this->create_query("get_user_create")));
            $this->id_user_create = $data_id["id_user_create"];
            return $data_id["id_user_create"];
        }
    }
}

?>
