<?php

/**
 * trida pro zobrazeni konkretni klientovy objednavky zajezdu
 */
/* --------------------- SERIAL ------------------------------------------- */
class Pdf_objednavka_prepare extends Rezervace_zobrazit {
const ZALOHA_DATE_TRESHOLD = 3;
    //vstupni data
    protected $security_code;
    protected $celkova_cena;
    protected $zakladni_cena = 0;
    protected $pocet_osob;
    private $rozpis_plateb;

//------------------- KONSTRUKTOR -----------------
    /*     * konstruktor tøídy na základì id objednávky */
    function __construct($id_objednavka, $security_code) {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->id_objednavka = $this->check_int($id_objednavka);
        $this->security_code = $this->check($security_code);

        //ziskani seznamu z databaze	
        $data_objednavka = $this->database->query($this->create_query("get_objednavka"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $pocet_objednavek = mysqli_num_rows($data_objednavka);


        //zjistuju, zda mam neco k zobrazeni
        if ($pocet_objednavek == 0) {
            $this->chyba("Nemáte pøístup k dané objednávce!");
        }
    }


//------------------- METODY TRIDY -----------------	
    /*     * vytvoreni dotazu na zaklade typu pozadavku */
    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "get_objednavka") {
            $dotaz = "SELECT * FROM `objednavka` 
						WHERE `id_objednavka`=" . $this->id_objednavka . " and `security_code`='" . $this->security_code . "'
						LIMIT 1";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_objednavka") {
            $dotaz = "SELECT * FROM `objednavka` 
						WHERE `id_objednavka`=" . $this->id_objednavka . " and `security_code`='" . $this->security_code . "'
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_klient") {
            $dotaz = "SELECT * FROM `user_klient` 
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_objednavajici_org") {
            $dotaz = "SELECT org.nazev, org.ico, org.dic, oe.email, ot.telefon, oa.ulice, oa.mesto, oa.psc
						FROM objednavka o
						  RIGHT JOIN organizace org ON (o.id_organizace = org.id_organizace)
						  LEFT JOIN organizace_adresa oa ON (o.id_organizace = oa.id_organizace)
						  LEFT JOIN organizace_email oe ON (o.id_organizace = oe.id_organizace)
						  LEFT JOIN organizace_telefon ot ON (o.id_organizace = ot.id_organizace)
						WHERE o.id_objednavka = $this->id_objednavka
						LIMIT 1";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_zajezd") {
            $dotaz = "SELECT `serial`.*,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`,`zajezd`.`nazev_zajezdu`,
                            
                            
                                `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`

						FROM `serial` JOIN  `zajezd` ON (`serial`.`id_serial` = `zajezd`.`id_serial`)
                                                left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                            
                                        
						WHERE `serial`.`id_serial`=" . $this->id_serial . " and `zajezd`.`id_zajezd`=" . $this->id_zajezd . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ceny") {
            $dotaz = "SELECT `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`typ_ceny`,`cena`.`use_pocet_noci`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`objednavka_cena`.`pocet`,
                        `objednavka_cena`.`cena_castka`,`objednavka_cena`.`cena_mena`,`objednavka_cena`.`use_pocet_noci` as `cena_use_pocet_noci`
						FROM `serial` 
							JOIN  `cena` ON (`serial`.`id_serial` = `cena`.`id_serial`)
							JOIN  `cena_zajezd` ON (`cena_zajezd`.`id_cena` = `cena`.`id_cena`)
							JOIN `objednavka_cena` ON (`cena`.`id_cena` = `objednavka_cena`.`id_cena` and `objednavka_cena`.`id_objednavka`=" . $this->id_objednavka . ")

						WHERE `serial`.`id_serial`=" . $this->id_serial . " and `cena_zajezd`.`id_zajezd`=" . $this->id_zajezd . " and `objednavka_cena`.`pocet`>0
                                                order by `poradi_ceny`, `typ_ceny`
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ceny2") {
            $dotaz = "  SELECT `id_cena`, `nazev_ceny`, `castka`, `mena`, `use_pocet_noci`, `pocet`
                        FROM `objednavka_cena2`
                        WHERE `id_objednavka`='$this->id_objednavka' AND `pocet` > 0;";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_smluvni_podminky_zaloha") {
            $dotaz = "  SELECT sp.*
                        FROM `objednavka` o 
                            JOIN `serial` s ON (o.id_serial = s.id_serial)
                            JOIN `smluvni_podminky_nazev` spn ON (s.id_sml_podm = spn.id_smluvni_podminky_nazev)
                            JOIN `smluvni_podminky` sp ON (spn.id_smluvni_podminky_nazev = sp.id_smluvni_podminky_nazev)
                        WHERE o.`id_objednavka`='$this->id_objednavka' AND sp.typ='záloha';";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_smluvni_podminky_doplatek") {
            $dotaz = "  SELECT sp.*
                        FROM `objednavka` o 
                            JOIN `serial` s ON (o.id_serial = s.id_serial)
                            JOIN `smluvni_podminky_nazev` spn ON (s.id_sml_podm = spn.id_smluvni_podminky_nazev)
                            JOIN `smluvni_podminky` sp ON (spn.id_smluvni_podminky_nazev = sp.id_smluvni_podminky_nazev)
                        WHERE o.`id_objednavka`='$this->id_objednavka' AND sp.typ='doplatek';";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_platby") {
            $dotaz = "select `objednavka`.`mena`, `objednavka`.`id_objednavka`,`objednavka_platba`.`id_platba`,`objednavka_platba`.`castka`, `objednavka_platba`.`cislo_dokladu`,
						`objednavka_platba`.`vystaveno`,`objednavka_platba`.`splatit_do`, `objednavka_platba`.`splaceno`, `objednavka_platba`.`zpusob_uhrady`,`objednavka_platba`.`typ_dokladu`
					from `objednavka_platba`
					join `objednavka` on ( `objednavka_platba`.`id_objednavka` = `objednavka`.`id_objednavka` )
					where `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
					order by `objednavka_platba`.`id_platba`
					";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_slevy") {
            $dotaz = "select `objednavka_sleva`.*
					from `objednavka_sleva`
					where `id_objednavka`=" . $this->id_objednavka . "
					order by `objednavka_sleva`.`velikost_slevy` desc
					";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_slevy_global_model_change") {
            $dotaz = "select `objednavka_sleva`.*
					from `objednavka_sleva`
					where `id_objednavka`=" . $this->id_objednavka . "
					order by `objednavka_sleva`.`castka_slevy` desc
					";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_osoby") {
            $dotaz = "select `objednavka_osoby`.`id_objednavka`,`objednavka_osoby`.`id_klient`,`jmeno`,`prijmeni`,`titul`,
								`email`,`telefon`,`datum_narozeni`,`rodne_cislo`,`cislo_pasu`,`cislo_op`,`ulice`,`mesto`,`psc`
					  from 	`objednavka_osoby`
					  			JOIN `user_klient` ON (`objednavka_osoby`.`id_klient`=`user_klient`.`id_klient`)
								WHERE `objednavka_osoby`.`id_objednavka` = " . $this->id_objednavka . "
					  order by `id_klient` ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_agentura") {
            $dotaz = "SELECT `objednavka`.`id_objednavka`,`objednavka`.`id_agentury`,`organizace`.`nazev` as `nazev_agentury`,`organizace`.`ico`,
                                        `organizace_email`.`email`,`organizace_email`.`poznamka` as `kontaktni_osoba`,
                                        `organizace_telefon`.`telefon`,
                                        `stat`,`mesto`,`ulice`,`psc`,
                                        `nazev_banky`,`kod_banky`,`cislo_uctu`
                                    from `objednavka`
                                     join `organizace` on (`organizace`.`id_organizace` = `objednavka`.`id_agentury`)
                                     left join `organizace_adresa` on (`organizace`.`id_organizace` = `organizace_adresa`.`id_organizace` and `organizace_adresa`.`typ_kontaktu` = 1)
                                     left join `organizace_email` on (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` and `organizace_email`.`typ_kontaktu` = 0)
                                     left join `organizace_telefon` on (`organizace`.`id_organizace` = `organizace_telefon`.`id_organizace` and `organizace_telefon`.`typ_kontaktu` = 0)
                                     left join `organizace_www` on (`organizace`.`id_organizace` = `organizace_www`.`id_organizace` and `organizace_www`.`typ_kontaktu` = 0)
                                     left join `organizace_bankovni_spojeni` on (`organizace`.`id_organizace` = `organizace_bankovni_spojeni`.`id_organizace` and `organizace_bankovni_spojeni`.`typ_kontaktu` = 1)
				WHERE `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
				LIMIT 1";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_centralni_data") {
            $dotaz = "SELECT * FROM `centralni_data` 
						WHERE `nazev` like \"hlavicka:%\"
			";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_agentura_old") {
            $dotaz = "select `objednavka`.`id_objednavka`,`objednavka`.`id_agentury`,`jmeno`,`prijmeni`,
								`email`,`telefon`,`ulice`,`mesto`,`psc`,`ico`
					  from 	`objednavka`
					  			JOIN `user_klient` ON (`objednavka`.`id_agentury`=`user_klient`.`id_klient`)
								WHERE `objednavka`.`id_objednavka` = " . $this->id_objednavka . "
					  limit 1 ";
            //echo $dotaz;
            return $dotaz;
        }
    }

//$cena2["castka"], $cena2["pocet"], $objednavka["pocet_noci"], $cena2["use_pocet_noci"]
    function calculate_prize($castka, $pocet, $pocet_noci, $use_pocet_noci = 0, $typ_ceny = null) {
        if ($pocet_noci == 0) {
            $pocet_noci = 1;
        }
        if ($use_pocet_noci != 0) {
            $cena = $castka * $pocet * $pocet_noci;

            $this->celkova_cena = $this->celkova_cena + ($cena);

            if (!is_null($typ_ceny) && ($typ_ceny == SluzbaEnt::TYP_SLUZBA || $typ_ceny == SluzbaEnt::TYP_LAST_MINUTE)) {
                $this->zakladni_cena += $cena;
            }

            return $cena;
        } else {
            $cena = $castka * $pocet;

            $this->celkova_cena = $this->celkova_cena + ($cena);

            if (!is_null($typ_ceny) && ($typ_ceny == SluzbaEnt::TYP_SLUZBA || $typ_ceny == SluzbaEnt::TYP_LAST_MINUTE)) {
                $this->zakladni_cena += $cena;
            }

            return $cena;
        }
    }

    function get_celkova_cena() {
        return $this->celkova_cena;
    }

    function get_splacene_platby_celkem() {
        $celkem = 0;
        $platby = $this->database->query($this->create_query("select_platby"));
        while ($p = mysqli_fetch_array($platby)) {
            $p["splaceno"] = $this->change_date_en_cz($p["splaceno"]);
            if ($p["splaceno"] == "00.00.0000") {
                $p["splaceno"] = "";
            }
            if ($p["splaceno"] == "")
                continue;
            $celkem += $p["castka"];
        }
        return $celkem;
    }


    
    /*LP 2.12.2015:pøidání funkcionality poèítat rozpis plateb. Hodnì hnusné øešení, ale nepodaøilo se mi rozumnì nasdílet funkcionalitu z objednavka_ts - spíše do budoucna bude tøeba tuto TS sjednotit s objednavka_ts*/
 private function computeDnuDoOdjezdu()
    {
        $now = new DateTime($this->dataObjednavka->datum_rezervace);
        $ref = new DateTime($this->dataZajezd->terminOd . " 00:00:00");
        $diff = $now->diff($ref, true);
        $dnu_do_odjezdu = $diff->days;
        return $dnu_do_odjezdu;
    }

    private function computeFirstZaloha()
    {
        $zaloha = 0;
        $dnu_do_odjezdu = $this->computeDnuDoOdjezdu();
        foreach ($this->dataZaloha as $row) {
            if ($dnu_do_odjezdu >= $row->prodleva) {
                return $this->computeZaloha($row);
            }
        }
    }

    private function computeLastZaloha()
    {
        $zaloha = 0;
        foreach ($this->dataZaloha as $row) {
            $zaloha = $this->computeZaloha($row);
        }
        return $zaloha;
    }

    private function computeZaloha($row)
    {
        if ($row->castka == 0) {
            $totalPrice = $this->computeTotalPrice();
            $zaloha = round($totalPrice * $row->procento / 100);
        } else {
            $zaloha = $row->castka;
        }
        return $zaloha;
    }

    private function computeFirstZalohaDate()
    {
        return date('d-m-Y', strtotime($this->dataObjednavka->datum_rezervace . " + " . self::ZALOHA_DATE_TRESHOLD . " days"));
    }

    private function computeNextZalohaDate($prodleva)
    {
        return date('d-m-Y', strtotime($this->dataZajezd->terminOd . " - " . $prodleva . " days"));
    }

    private function computeDoplatek()
    {
        $totalPrice = $this->computeTotalPrice();
        $zaloha = $this->computeLastZaloha();
        $zaplaceno = $this->computeAlreadyPaid();

        $doplatek = $totalPrice - $zaloha;

        //provedl klient nejakou platbu?
        if ($zaplaceno > 0) {
            $zalohaZbyvaZaplatit = max(0, $zaloha - $zaplaceno);
            $doplatek = $totalPrice - $zalohaZbyvaZaplatit - $zaplaceno;
        }

        return $doplatek;
    }

    private function computeDoplatekDate($prodleva)
    {
        return date('d-m-Y', strtotime($this->dataZajezd->terminOd . ' - ' . $prodleva . ' days'));
    }

    private function computePlatbaObjednavkyDate()
    {
        $platbaObjednavkyDate = date('d-m-Y', strtotime($this->dataObjednavka->datum_rezervace . ' + 3 days'));
        if (strtotime($platbaObjednavkyDate) > strtotime(date('d-m-Y', strtotime($this->dataZajezd->terminOd . ' - 1 days')))) {
            $platbaObjednavkyDate = date('d-m-Y', strtotime($this->dataZajezd->terminOd . ' - 1 days'));
        }
        return $platbaObjednavkyDate;
    }
    
   
  private function computeAlreadyPaid()
    {
        return $this->get_splacene_platby_celkem();
    }    
  private function computeTotalPrice()
    {
        return $this->get_celkova_cena();
    }
    
      /**
     * Misto vypocitanych dat zapise do promenych data, ktera jsou zapsana u objednavky a maji prednost
     * @param $totalPrice
     * @param $zaloha
     * @param $doplatek
     * @param $zalohaDate
     * @param $doplatekDate
     * @param $totalPriceDate
     */
    private function correctKUhradeData(&$totalPrice, &$zaloha, &$doplatek, &$zalohaDate, &$doplatekDate, &$totalPriceDate)
    {
        //data vyplnena u objednavky maji prednost
        if ($this->dataObjednavka->k_uhrade_celkem != 0)
            $totalPrice = $this->dataObjednavka->k_uhrade_celkem;
        if ($this->dataObjednavka->k_uhrade_zaloha != 0)
            $zaloha = $this->dataObjednavka->k_uhrade_zaloha;
        if ($this->dataObjednavka->k_uhrade_doplatek != 0)
            $doplatek = $this->dataObjednavka->k_uhrade_doplatek;
        if ($this->dataObjednavka->k_uhrade_zaloha_datspl != "0000-00-00")
            $zalohaDate = $this->dataObjednavka->k_uhrade_zaloha_datspl;
        if ($this->dataObjednavka->k_uhrade_doplatek_datspl != "0000-00-00")
            $doplatekDate = $this->dataObjednavka->k_uhrade_doplatek_datspl;
        if ($this->dataObjednavka->k_uhrade_celkem_datspl != "0000-00-00")
            $totalPriceDate = $this->dataObjednavka->k_uhrade_celkem_datspl;
    }
    
    
    
     public function getUhradit()
    {
        $idObjednavka = $this->id_objednavka; 
        $zaplaceno = $this->get_splacene_platby_celkem();
        $totalPrice = $this->get_celkova_cena(); //note '$totalPrice = $this->computeTotalPrice();' nahrazeno globalnim modelem
        $html = "";
        $this->dataObjednavka = ObjednavkaDAO::dataObjednavka($idObjednavka);
        $this->dataZajezd = ObjednavkaDAO::dataZajezd($idObjednavka);
        $this->dataZaloha = ObjednavkaDAO::dataZaloha($idObjednavka);
        $this->dataDoplatek = ObjednavkaDAO::dataDoplatek($idObjednavka);

//pro zaplacene a stornovane objednavky nezobrazovat rozpis plateb
        if ($zaplaceno >= $totalPrice)
            return $html;
        if ($this->stav == 8)
            return $html;

        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >  ";
        $html .= "  <tr>
                        <td valign='top' colspan='3' style='padding-left:0;padding-right:0;padding-bottom:8px;'><h2>Rozpis plateb</h2></td>
                    </tr>
                    ";
        $html .= "  <tr>
                        <th align='left' class='border2' style='width:65%'><strong>Èíslo úètu:</strong> 19-6706930207/0100, <strong>variabilní symbol:</strong> " . $this->id_objednavka . "</th>
                        <th align='left' class='border2' style='width:15%'>Datum splatnosti</th>
                        <th align='right' class='border2'>Èástka</th>
                    </tr>";


        //zalohu zobraz jen pokud neni nulova
        $dnu_do_odjezdu = $this->computeDnuDoOdjezdu();
        $cislo_zalohy = 0;
        $sum_zaloha = 0;
        foreach ($this->dataZaloha as $zaloha) {

            $zalohaCastka = $this->computeZaloha($zaloha);
            $zalohaCastka -= $sum_zaloha;
            $sum_zaloha += $zalohaCastka;

            $currency = "Kè";
            $last_prodleva = 0;
            if ($dnu_do_odjezdu >= $zaloha->prodleva) {//platná záloha
                $cislo_zalohy++;
                if ($cislo_zalohy == 1) {
                    $zalohaDate = $this->computeFirstZalohaDate();
                    $this->correctKUhradeData($foo, $zalohaCastka, $foo, $zalohaDate, $foo, $foo);
                    $html .= "       <tr>
                        <td class='border2'><strong>Záloha</strong></td>
                        <td class='border2'>" . UtilsTS::czechDate($this->computeFirstZalohaDate()) . "</td>
                        <td class='border2' align='right'>$zalohaCastka $currency</td>
                      </tr>";
                } else {
                    $html .= "       <tr>
                        <td class='border2'><strong>Záloha</strong></td>
                        <td class='border2'>" . UtilsTS::czechDate($this->computeNextZalohaDate($last_prodleva)) . "</td>
                        <td class='border2' align='right'>$zalohaCastka $currency</td>
                      </tr>";
                }
                $last_prodleva = $zaloha->prodleva;
            }
        }
        $doplatekDate = $this->computeDoplatekDate($last_prodleva);
        $doplatek = $this->computeDoplatek();
        $this->correctKUhradeData($foo, $foo, $doplatek, $foo, $doplatekDate, $foo);
        if ($cislo_zalohy != 0) {
            $html .= "<tr>
                    <td class='border2'><strong>Doplatek</strong></td>
                    <td class='border2'>" . UtilsTS::czechDate($doplatekDate) . "</td>
                    <td class='border2' align='right'>$doplatek $currency</td>
                  </tr>";
        } else {
            $totalPriceDate = $this->computePlatbaObjednavkyDate();
            $this->correctKUhradeData($totalPrice, $foo, $foo, $foo, $foo, $totalPriceDate);
            $html .= "<tr>
                    <td class='border2'><strong>Platba objednávky</strong></td>
                    <td class='border2'>" . UtilsTS::czechDate($totalPriceDate) . "</td>
                    <td class='border2' align='right'>" . $totalPrice . " " . $currency . "</td>
                  </tr>";
        }

        $html .= "</table>";

        return $html;
    }
    
    
    
    function create_pdf_objednavka() {
        if (!$this->get_error_message()) {
            
            $objednavka = mysqli_fetch_array($this->database->query($this->create_query("show_objednavka")))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            $this->id_serial = $objednavka["id_serial"];
            $this->id_zajezd = $objednavka["id_zajezd"];
            $this->id_klient = $objednavka["id_klient"];
            $this->stav = $objednavka["stav"];

            $klient = isset($this->id_klient) ? mysqli_fetch_array($this->database->query($this->create_query("select_klient"))) : null;

            $klientOrg = @mysqli_fetch_array($this->database->query($this->create_query("select_objednavajici_org")));

            $zajezd = mysqli_fetch_array($this->database->query($this->create_query("select_zajezd")))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            $ceny = $this->database->query($this->create_query("select_ceny"))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            $ceny2 = $this->database->query($this->create_query("select_ceny2"))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

//            $smluvni_podminky_zaloha = mysqli_fetch_object($this->database->query($this->create_query("select_smluvni_podminky_zaloha")))
//                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
//
//            $smluvni_podminky_doplatek = mysqli_fetch_object($this->database->query($this->create_query("select_smluvni_podminky_doplatek")))
//                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            $platby = $this->database->query($this->create_query("select_platby"))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            /* ZMENA 18.10. 2015 Martin Jelinek ----------------------- */
            /**
             * @deprecated
             * Do globalniho modelu slantouru, ktery postupne vytvarim a pridavam do nej veci, na ktere narazim, jsem zabudoval vypocet vsech sluzeb,
             * slev, celkovych cen objednavky a podobnych veci. Je univerzalni, takze do budoucna by bylo dobre ho pouzit vsude (tiskove sestavy...)
             * aby byl vypocet na 1 miste. Vse co se v nem pocita, se dela "zive", dynamicky, nasledkem toho jsou pak vsechny zmeny napriklad sluzeb hned
             * promitnuty do velikosti napriklad procentualni slevy. Diky tomu neni napriklad u slev nutne ukladat celkovou castku, kterou sleva predstavuje
             * v kontextu dane objednavky. Celkova castka se vypocitava dynamicky na zaklade predanych parametru (pocet osob, zakladni cena objednavky).
             * To ale odporuje dosavadnimu pristupu napriklad ke slevam, kde se pocitalo s celkovou castkou slevy ulozenou v databazi. Nechavam zde
             * zakomentovany stary kod, kvuli orientaci.
             */
//            $slevy = $this->database->query($this->create_query("select_slevy")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            require_once __DIR__ . '/../global/lib/model/entyties/ObjednavkaEnt.php';
            require_once __DIR__ . '/../global/lib/model/entyties/SlevaEnt.php';
            require_once __DIR__ . '/../global/lib/model/entyties/SluzbaEnt.php';
            require_once __DIR__ . '/../global/lib/cfg/ViewConfig.php';
            $slevyQuery = $this->database->query($this->create_query("select_slevy_global_model_change")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            /** @var $slevaEntList SlevaEnt[] */
            $slevaEntList = null;
            while ($rowSlevy = mysqli_fetch_array($slevyQuery)) {
                $slevaEnt = new SlevaEnt($rowSlevy['id_slevy'], $rowSlevy['nazev_slevy'], $rowSlevy['velikost_slevy'], $rowSlevy['mena'], $rowSlevy['sleva_staly_klient']);
                $slevaEntList[] = $slevaEnt;
            }

            /* END ZMENA ----------------------- */

            $osoby = $this->database->query($this->create_query("select_osoby"))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            $centralni_data = $this->database->query($this->create_query("get_centralni_data"))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            //nacteme centralni data do pole
            while ($row = mysqli_fetch_array($centralni_data)) {
                $row["nazev"] = str_replace("hlavicka:", "", $row["nazev"]);
                $this->centralni_data[$row["nazev"]] = $row["text"];
            }

            $this->celkova_cena = 0;



            $seznam_osob = "";
            $i = 0;
            while ($osoba = mysqli_fetch_array($osoby)) {
                $i++;
                $seznam_osob = $seznam_osob . "
        <tr>
					<td rowspan=\"3\" valign=\"top\" width=\"10px;\">
						<strong style=\"font-size:2em;\">$i</strong>
					</td>
					<td><strong>" . $osoba["titul"] . " " . $osoba["jmeno"] . " " . $osoba["prijmeni"] . "</strong></span></td>
          <td><b>e-mail:</b> " . $osoba["email"] . "</span></td> 
          <td><b>tel.:</b> " . $osoba["telefon"] . "</span></td>
				</tr>
				<tr>
					<td><b>datum nar.:</b> " . (($osoba["datum_narozeni"] == "0000-00-00") ? (" ") : ($this->change_date_en_cz($osoba["datum_narozeni"]))) . "</td>
          <td><b>RÈ:</b> " . $osoba["rodne_cislo"] . "</td>
          <td><b>è. dokladu:</b> " . (($osoba["cislo_pasu"] != "") ? ($osoba["cislo_pasu"]) : ($osoba["cislo_op"])) . "</td>
				</tr>
				<tr>
					<td colspan=\"3\"><b>Adresa:</b> " . $osoba["ulice"] . ", " . $osoba["psc"] . ", " . $osoba["mesto"] . "</td>
				</tr>		
				<tr>
					<td colspan=\"4\"><hr style=\"margin-top:-2px;\"/></td>
				</tr>	  										
					";
            }
            $this->pocet_osob = $i;
            $seznam_cen = "";
            if($objednavka["ubytovani"]!=""){
                $text_cena_ubytovani = $objednavka["ubytovani"];
            }else{
               if($zajezd["id_sablony_zobrazeni"]!=12){
                    $text_cena_ubytovani = "";
                }else if ($zajezd["nazev_ubytovani"]) {
                    $text_cena_ubytovani = $zajezd["nazev_ubytovani"];
               }
            }

            $text_doprava = $objednavka["doprava"];
            $text_stravovani = $objednavka["stravovani"];
            $text_pojisteni = $objednavka["pojisteni"];

            while ($cena = mysqli_fetch_array($ceny)) {
      
                if ($cena["typ_ceny"] == 5) {
                    $text_pred_cenou = "Doprava: $text_doprava &nbsp; &nbsp; &nbsp; nástup: ";
                    $doprava_done = true;
                } else {
                    $text_pred_cenou = "";
                }
                if(stripos($cena["nazev_ceny"], "pojištìní")!==FALSE){
                    $pojisteni_done = true;
                }
                $castka="";
                $mena="";
                $use_pocet_noci="";
                if($cena["cena_mena"]==""){
                //nemame puvodni udaje, zadame tam aktualni z tabulky cena_zajezd
                    $castka = $cena["castka"] ;
                    $mena =  $cena["mena"];
                    $use_pocet_noci = intval($cena["use_pocet_noci"]);

                }else{
                //mam puvodni udaje, dam moznost je editovat
                    $castka = $cena["cena_castka"] ;
                    $mena =  $cena["cena_mena"];
                    $use_pocet_noci = intval($cena["cena_use_pocet_noci"]);

                }
                $seznam_cen .= "<tr>
                                    <td  class=\"border2l borderDotted\" colspan=\"5\">" . $text_pred_cenou . $cena["nazev_ceny"] . "</td>
                                    <td class=\"border2l borderDotted\" align=\"right\">" . $castka . " " . $mena . "</td>
                                    <td  class=\"border2l borderDotted\" align=\"right\">" . $cena["pocet"] . "</td>
                                    <td class=\"border2l border2r borderDotted\" align=\"right\">" . $this->calculate_prize($castka, $cena["pocet"], $objednavka["pocet_noci"], $use_pocet_noci, $cena["typ_ceny"]) . " " . $mena . "</td>
							    </tr>
					";
            }

            while ($row_cena2 = mysqli_fetch_array($ceny2)) {
                if(stripos($row_cena2["nazev_ceny"], "pojištìní")!==FALSE){
                    $pojisteni_done = true;
                }
                $seznam_cen = $seznam_cen . "					
							<tr>	
                                                <td  class=\"border2l borderDotted\" colspan=\"5\">" . $row_cena2["nazev_ceny"] . "</td>
                                                <td class=\"border2l borderDotted\" align=\"right\">" . $row_cena2["castka"] . " " . $row_cena2["mena"] . "</td>
                                                <td  class=\"border2l borderDotted\" align=\"right\">" . $row_cena2["pocet"] . "</td>
                                                <td class=\"border2l border2r borderDotted\" align=\"right\">" . $this->calculate_prize($row_cena2["castka"], $row_cena2["pocet"], $objednavka["pocet_noci"], $row_cena2["use_pocet_noci"]) . " " . $row_cena2["mena"] . "</td>
							</tr>		
					";
            }
            $seznam_cen = $seznam_cen . "<tr>        
                        <td  class=\"border2l borderDotted\" colspan=\"5\">Ubytování: " . $text_cena_ubytovani . "</td>
                        <td class=\"border2l borderDotted\" align=\"right\"></td>
                        <td  class=\"border2l borderDotted\" align=\"right\"></td>
                        <td class=\"border2l border2r borderDotted\" align=\"right\"></td>
                      </tr>  
                ";
            if (!$doprava_done) {
                $seznam_cen = $seznam_cen . "<tr>        
                        <td  class=\"border2l borderDotted\" colspan=\"5\">Doprava: $text_doprava</td>
                        <td class=\"border2l borderDotted\" align=\"right\"></td>
                        <td  class=\"border2l borderDotted\" align=\"right\"></td>
                        <td class=\"border2l border2r borderDotted\" align=\"right\"></td>
                      </tr>  
                ";
            }
            $seznam_cen = $seznam_cen . "<tr>        
                        <td  class=\"border2l borderDotted\" colspan=\"5\">Stravování: $text_stravovani</td>
                        <td class=\"border2l borderDotted\" align=\"right\"></td>
                        <td  class=\"border2l borderDotted\" align=\"right\"></td>
                        <td class=\"border2l border2r borderDotted\" align=\"right\"></td>
                      </tr>  
                ";
            if(!$pojisteni_done or $text_pojisteni!=""){
            $seznam_cen = $seznam_cen . "<tr>
                        <td  class=\"border2l borderDotted\" colspan=\"5\">Pojištìní: $text_pojisteni</td>
                        <td class=\"border2l borderDotted\" align=\"right\"></td>
                        <td  class=\"border2l borderDotted\" align=\"right\"></td>
                        <td class=\"border2l border2r borderDotted\" align=\"right\"></td>
                      </tr>  
                ";
            }

            /* ZMENA 18.10. 2015 Martin Jelinek ----------------------- */

            if(is_array($slevaEntList)) {
                foreach ($slevaEntList as $sleva) {
                    $celkovaCastkaSlevy = $sleva->calcCelkovaCastkaSlevy($this->zakladni_cena, $this->pocet_osob);
                    $seznam_cen .= "<tr>";
                    $seznam_cen .= "    <td class='border2l borderDotted' colspan='5'>$sleva->nazev_slevy</td>";
                    $seznam_cen .= "    <td class='border2l borderDotted' align='right'>".$sleva->castka." $sleva->mena</td>";
                    $seznam_cen .= "    <td class='border2l borderDotted' align='right'></td>";
                    $seznam_cen .= "    <td class='border2l border2r borderDotted' align='right'>- $celkovaCastkaSlevy</td>";
                    $seznam_cen .= "</tr>";

                    $this->celkova_cena = $this->celkova_cena - $celkovaCastkaSlevy; //note vypocet celkove ceny se tak nejak prolina celym kodem, takze je treba zde odecist slevy
                }
            }

//            while ($row_slevy = mysqli_fetch_array($slevy)) {
//                $seznam_cen = $seznam_cen . "
//                    <tr>
//                        <td  class=\"border2l borderDotted\" colspan=\"5\">" . $row_slevy["nazev_slevy"] . "</td>
//                        <td class=\"border2l borderDotted\" align=\"right\">" . $row_slevy["velikost_slevy"] . " " . $row_slevy["mena"] . "</td>
//                        <td  class=\"border2l borderDotted\" align=\"right\"></td>
//                        <td class=\"border2l border2r borderDotted\" align=\"right\">- " . $row_slevy["castka_slevy"] . " Kè</td>
//                    </tr>";
//                $this->celkova_cena = $this->celkova_cena - $row_slevy["castka_slevy"];
//            }
            /*             * stary vypocet slevy zalozeny na datech primo v objednavce zajezdu 
              if ($objednavka["velikost_slevy"] != "") {
              $seznam_cen = $seznam_cen . "
              <tr>
              <td  class=\"border2l borderDotted\" colspan=\"4\">" . $objednavka["nazev_slevy"] . "</td>
              <td class=\"border2l borderDotted\" align=\"right\">" . $objednavka["castka_slevy"] . "</td>
              <td  class=\"border2l borderDotted\" align=\"right\"></td>
              <td class=\"border2l border2r borderDotted\" align=\"right\">- " . $objednavka["velikost_slevy"] . " Kè</td>
              </tr>
              ";
              $this->celkova_cena = $this->celkova_cena - $objednavka["velikost_slevy"];
              }
             */
            
            /* END ZMENA ----------------------- */


            if ($objednavka["id_agentury"] != 0) {
                $agentura = mysqli_fetch_array($this->database->query($this->create_query("select_agentura")))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $text_agentura = "      
			<strong>" . $agentura["nazev_agentury"] . "</strong> 
	    	 <p style=\"font-size:1.1em;\">
      		" . $agentura["ulice"] . ", " . $agentura["mesto"] . ", " . $agentura["psc"] . "<br/>
				tel.:" . $agentura["telefon"] . "<br/>
				IÈO: " . $agentura["ico"] . "<br/>
				e-mail: " . $agentura["email"] . "<br/>	    
       		</p> ";
            } else {
                $text_agentura = "";
            }



                       //zakladni nastaveni zobrazeni plateb - pokud neexistuje zadna zadana platba
            $platby_zaloha = "  <td colspan=\"2\" class=\"border2l border1b\"> </td>
                    <td class=\"border1l border1b\"> </td>
                    <td class=\"border1l border1b\"> </td>
                    <td class=\"border1l border1b\"> </td>";
            $platby_doplatek = "<td colspan=\"2\" class=\"border2l border1b\"> </td>
                    <td class=\"border1l border1b\"> </td>
                    <td class=\"border1l border1b\"> </td>
                    <td class=\"border1l border1b\"> </td>";
            $platby_dalsi_1 = "<td colspan=\"2\" class=\"border2l border1b\" > ";
            $platby_dalsi_2 = "<td class=\"border1l border1b\"> ";
            $platby_dalsi_3 = "<td class=\"border1l border1b\"> ";
            $platby_dalsi_4 = "<td class=\"border1l border1b\"> ";
            $pocet_plateb = 0;
            $celkova_platba = 0;

            //posunout az za nacteni celkove ceny
            while ($platba = mysqli_fetch_array($platby)) {
                $platba["splaceno"] = $this->change_date_en_cz($platba["splaceno"]);
                if ($platba["splaceno"] == "00.00.0000") {
                    $platba["splaceno"] = "";
                }

                $pocet_plateb++;
                $celkova_platba += $platba["castka"];
                if($celkova_platba >= $this->get_celkova_cena()){
                    if($pocet_plateb==1){
                        $nazev = "Platba, è.d.: ";
        //                $platby_doplatek = "<td class=\"border2\"  height=\"18\"></td> <td class=\"border2\"></td><td class=\"border2\"></td><td class=\"border2\"></td>";
                    }else if($platba["castka"]>=0){
                        $nazev = "Doplatek, è.d.: ";
                    }else{
                        $nazev = "Vratka, è.d.: ";
                    }
                }else{
                    if($platba["castka"]>=0){
                        $nazev = "Záloha, è.d.: ";
                    }else{
                        $nazev = "Vratka, è.d.: ";
                    }

                }


                if ($pocet_plateb == 1) {
                    $platby_zaloha = "<td colspan=\"2\" class=\"border2l border1b\">" . $platba["splaceno"] . " </td>
							  <td class=\"border1l border1b\">" . $platba["castka"] . " Kè</td>
							  <td class=\"border1l border1b\">" . $nazev . $platba["cislo_dokladu"] . "</td>
							  <td class=\"border1l border1b\">" . $platba["zpusob_uhrady"] . " </td>	";
                }else if( $pocet_plateb == 2){
                    $platby_doplatek = "<td colspan=\"2\" class=\"border2l border1b\">" . $platba["splaceno"] . " </td>
							  <td class=\"border1l border1b\">" . $platba["castka"] . " Kè</td>
							  <td class=\"border1l border1b\">" . $nazev . $platba["cislo_dokladu"] . "</td>
							  <td class=\"border1l border1b\">" . $platba["zpusob_uhrady"] . " </td>	";
                } else {
                    $platby_dalsi_1.=$platba["splaceno"] . " <br/>";                    
                    $platby_dalsi_2.=$platba["castka"] . " Kè<br/>";
                    $platby_dalsi_3.= $nazev . $platba["cislo_dokladu"] . " <br/>";
                    $platby_dalsi_4.=$platba["zpusob_uhrady"] . " <br/>";
                }
            }
      if($zajezd["id_sablony_zobrazeni"]!=12){
          $nazev_zajezdu = $zajezd["nazev"] ;
      }else if ($zajezd["nazev_ubytovani"]) {
          $nazev_zajezdu = $zajezd["nazev_ubytovani"] . ", ".$zajezd["nazev"] ;
      }else{
          $nazev_zajezdu = $zajezd["nazev"] ;
      }
      if($zajezd["nazev_zajezdu"] != ""){
          $nazev_zajezdu = $nazev_zajezdu."; ".$zajezd["nazev_zajezdu"];
      }

      if($zajezd["povinne_poplatky"]!=""){
          $povinne_poplatky = "Upozoròujeme na následující povinné poplatky: ".$zajezd["povinne_poplatky"]."<br/>";
      }else{
          $povinne_poplatky = "";
      }
      $rozpis_plateb = $this->getUhradit();
      
            $text_objednavka = "

			
			
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=windows-1250\" />
<style>
*{
	font-family: DejaVuSans, Helvetica, Arial,  sans-serif;
	font-size: 7pt;
	margin: 0;
	padding: 0;
	
}
body{
	font-family: DejaVuSans, Helvetica, Arial,  sans-serif;
	font-size: 7pt;
}
table{
	font-size: 7pt;
	margin-top:-3px;
	margin-bottom:-3px;
}
td{
	padding-left:8px;
	padding-right:8px;
}
th{
	padding-left:8px;
	padding-right:8px;
}
.content table{
	font-size: 7pt;
}


.border2{
	border: 2px solid #101010; 
	padding:3px 8px 3px 8px;
}
.border2l{
	border-left: 2px solid #101010; 
	padding-left:8px;
	padding-right:8px;
}
.border2r{
	border-right: 2px solid #101010; 
	padding-right:8px;
	padding-left:8px;
}
.border2t{
	border-top: 2px solid #101010; 
	padding-top:3px;
}
.border2b{
	border-bottom: 2px solid #101010; 
	padding-bottom:3px;
}

.border1{
	border: 1px solid #101010; 
	padding:3px 10px 3px 10px;
}
.border1l{
	border-left: 1px solid #101010; 
	padding-left:10px; 
  padding-top:4px;
}
.border1b{
	border-bottom: 1px solid #101010; 
	padding-bottom:3px; 
  padding-top:4px;
}

.borderDotted{
	border-bottom: 1px dotted #101010; 
	padding-bottom:3px; 
  padding-top:3px;
}

.content p{
	margin-left:20px;
	font-weight: bold;
	clear:left;
}
.content table{
	margin-left:20px;
	clear:left;
}
.content table td,
.content table th{
	padding-right:20px;
}
h1{
	font-size: 2.6em;
}
h2{
	font-size: 1.4em;
}


</style>
	<title>Objednávka</title>
</head>

<body>
<table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
	<tr>
	 <th colspan=\"3\" style=\"padding-left:0;padding-right:0;\">
   			<h1 style=\"font-size:24px;\">SMLOUVA O ZÁJEZDU - OBJEDNÁVKA CESTOVNÍCH SLUŽEB</h1>
    </th>
  </tr>
  <tr>
    <th colspan=\"3\" align=\"right\" style=\"padding-right:20px;\">   			
			<h3>uzavøená ve smyslu zákona è. 159/1999 Sb.</h3>
   </th>
	</tr>
  
  <tr>	   
		<td class=\"border2\" valign=\"top\" rowspan=\"2\">                
	      <h2>" . $this->centralni_data["nazev_spolecnosti"] . "</h2>
	     <p style=\"font-size:1.1em;\">
      	" . $this->centralni_data["adresa"] . "<br/>
				" . $this->centralni_data["firma_zapsana"] . "<br/>
				tel.: " . $this->centralni_data["telefon"] . "<br/>
				IÈO: " . $this->centralni_data["ico"] . " DIÈ: " . $this->centralni_data["dic"] . "<br/>
				Bankovní spojení: " . $this->centralni_data["bankovni_spojeni"] . "<br/>
				e-mail: " . $this->centralni_data["email"] . ", web: " . $this->centralni_data["web"] . "<br/>	    
       </p>  
		</td>	
		
		<td rowspan=\"2\"  class=\"border2\" valign=\"top\"  width=\"280\" >
      <h2>Obchodní zástupce - prodejce</h2> 
		" . $text_agentura . "
    </td>	
        
 		<td lign=\"center\" valign=\"top\" width=\"180\">
			<img src=\"pix/logo_slantour.gif\" width=\"150\" height=\"83\" /><br/>
			<b>ÈÍSLO SMLOUVY - REZERVACE:</b>
		</td>
	</tr>
  <tr>
    <td class=\"border2\"  align=\"center\">		
      <b>" . $this->id_objednavka . "
      </b>
		</td>    	
	</tr>
</table>  
<table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
	<tr>
		<td class=\"border2 content\" valign=\"top\">
			<h3>ZÁKAZNÍK - OBJEDNAVATEL</h3>";

            if($klientOrg !== false) {
                $text_objednavka .= "
                    <table width=\"100%\">
                        <tr>
                            <td><strong>" . $klientOrg["nazev"] . " </strong></td> <td><b>e-mail:</b> " . $klientOrg["email"] . "</td> <td><b>tel.:</b> " . $klientOrg["telefon"] . "</td>
                        </tr>
                        <tr>
                            <td colspan=\"2\"><b>Adresa:</b> " . $klientOrg["ulice"] . ", " . $klientOrg["psc"] . ", " . $klientOrg["mesto"] . "</td><td></td>
                        </tr>
                    </table>";
            } else {
                $text_objednavka .= "
                    <table width=\"100%\">
                        <tr>
                            <td><strong>" . $klient["titul"] . " " . $klient["jmeno"] . " " . $klient["prijmeni"] . " </strong></td> <td><b>e-mail:</b> " . $klient["email"] . "</td> <td><b>tel.:</b> " . $klient["telefon"] . "</td>
                        </tr>
                        <tr>
                            <td colspan=\"2\"><b>Adresa:</b> " . $klient["ulice"] . ", " . $klient["psc"] . ", " . $klient["mesto"] . "</td><td><b>RÈ/datum nar.:</b> " . (($klient["rodne_cislo"] != "") ? ($klient["rodne_cislo"]) : ($this->change_date_en_cz($klient["datum_narozeni"]))) . "</td>
                        </tr>
                    </table>";
            }

$text_objednavka .= "
		</td>				
	</tr>
</table>

<table cellpadding=\"0\" cellspacing=\"8\" width=\"810\">
	<tr>
		<td class=\"border2 content\" valign=\"top\">
			<h3>PØIHLAŠUJI K ZÁJEZDU/POBYTU TYTO OSOBY:</h3>
			<table width=\"100%\">		
        " . $seznam_osob . "      	                      										 		
			</table>
		</td>			
	</tr>
</table>

	<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >
        <tr>
            <td class=\"border2\" valign=\"top\" colspan=\"5\"  style=\"width:60%\">
                <h3>ZÁJEZD/LETOVISKO</h3>
                <strong>$nazev_zajezdu </strong>
            </td>
            <td nowrap class=\"border2\" valign=\"top\" align=\"left\" colspan=\"2\"  width=\"150\"><b>TERMÍN:</b><br/> " . (($objednavka["termin_od"] != "0000-00-00" and $objednavka["termin_od"] != "") ? ($this->change_date_en_cz($objednavka["termin_od"]) . " - " . $this->change_date_en_cz($objednavka["termin_do"])) : ($this->change_date_en_cz($zajezd["od"]) . " - " . $this->change_date_en_cz($zajezd["do"]))) . "</td>
            <td nowrap class=\"border2\" valign=\"top\" align=\"left\" width=\"100\"><b>POÈET NOCÍ:</b> " . $objednavka["pocet_noci"] . "<br/><br/></td>
        </tr>
        <tr>
		    <th colspan=\"5\" class=\"border2l border1b\" align=\"left\"  width=\"540\">Název služby</th>
            <th align=\"right\" class=\"border2l border1b\" width=\"100\">Cenový rozpis</th>
            <th align=\"right\" class=\"border2l border1b\">Poèet</th>
            <th align=\"right\" class=\"border2l border2r border1b\">Celkem</th>
	    </tr>
	    $seznam_cen
        <tr>
            <th align=\"left\" colspan=\"2\" class=\"border2\">Platby: Datum úhrady</th>
		    <th align=\"left\" class=\"border2\">Èástka</th>
			<th align=\"left\" class=\"border2\">Typ dokladu</th>
			<th align=\"left\" class=\"border2\">Zpùsob úhrady</th>
                        
			<th colspan=\"2\"  class=\"border2l border2t border2b\" align=\"left\"><strong>Celková cena</strong></th>
            <th class=\"border2r border2t border2b\" align=\"right\"><strong>" . $this->celkova_cena . " Kè</strong></th>
		</tr>
        <tr>
		    $platby_zaloha
			<td colspan=\"3\" rowspan=\"3\"  class=\"border2\"  width=\"250\">
                * Zákazník je povinen uhradit platbu CK. Úhradu provede sám (hotovì,
                složenkou na úèet nebo adresu, bankovním pøevodem), nebo udìlí plnou moc
                prodejci k provedení úhrady plateb. Za øádnou a vèasnou úhradu platby
                odpovídá cestovní kanceláøi vždy zákazník.
            </td>
        </tr>
        <tr>
		    $platby_doplatek
		</tr>
		<tr>
		    $platby_dalsi_1</td>$platby_dalsi_2</td>$platby_dalsi_3</td>$platby_dalsi_4</td>$platby_dalsi_5</td>
		</tr>
		<tr>
		    <td class=\"border2\" colspan=\"5\" rowspan=\"4\" valign=\"top\">
                <h3>POZNÁMKY/UPOZORNÌNÍ</h3>
                $povinne_poplatky
                " . nl2br($objednavka["poznamky"]) . "
            </td>
			<td class=\"border2\" colspan=\"3\">
                <h3>ÚDAJE CK</h3>
            </td>
		</tr>
		<tr>
		    <td class=\"border2\" colspan=\"2\"><b>Odeslání voucheru a pokynù</b></td>
			<td class=\"border2\">" . ($objednavka["stav"] == 7 ? "Odbaveno" : "") . " </td>
		</tr>
	    <tr>
		    <td class=\"border2\" colspan=\"2\"><b>STORNO DNE</b></td>
			<td class=\"border2\">" . ($objednavka["storno_poplatek"] != 0 ? $this->change_date_en_cz($objednavka["storno_datum"]) : "") . "</td>
		</tr>
		<tr>
		    <td class=\"border2\" colspan=\"2\"><b>STORNO POPLATEK</b></td>
			<td class=\"border2\">" . ($objednavka["storno_poplatek"] != 0 ? $objednavka["storno_poplatek"] . " Kè" : "") . "</td>
		</tr>
    </table>
    
$rozpis_plateb

	<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >
	<tr>
		<td class=\"border2\" valign=\"top\" colspan=\"3\">
			<h3>PROHLÁŠENÍ ZÁKAZNÍKA</h3>
      Prohlašuji že souhlasím se Všeobecnými podmínkami úèasti na zájezdech, které jsou nedílnou souèástí této cestovní smlouvy a s ostatními podmínkami uvedenými v této cestovní
smlouvì, a to i jménem výše uvedených osob, které mne k jejich pøihlášení a úèasti zmocnily.
		</td>				
	</tr>
	<tr>
		<td class=\"border2\" valign=\"top\" height=\"40\">
      <b>DATUM:</b> ".$this->change_date_en_cz_no_time($objednavka["datum_rezervace"])."
		</td>	
		<td class=\"border2\" valign=\"top\">
      <b>Podpis zákazníka:</b>
		</td>		
		<td class=\"border2\" valign=\"top\">
      <b>Podpis CK (prodejce):</b>
		</td>		        			
	</tr>	
</table>
		
	
</body>
</html>	
		";


            $ret = $text_objednavka;
            return $ret;
        } else {
            return "";
        }
    }

}

?>
