<?php

class SlouceniKlientuView {

    /**
     * @var Core
     */
    public static $core;
    /**
     * @var User_zamestnanec
     */
    public static $employee;

    /***************************** PUBLIC METHODS ******************************/

    /**
     * View menu (rozcestnik)
     */
    public static function menu()
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::pMenu();
        SlouceniKlientuView::foot();
    }

    /**
     * View klient no info builder
     * @param $clients arr[obj]
     */
    public static function clientNoInfo($clients)
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::pClientNoInfo($clients);
        SlouceniKlientuView::foot();
    }

    /**
     * View klient invalid emails no obj builder
     * @param $clients arr[obj]
     */
    public static function invalidEmailsNoObj($clients)
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::pInvalidEmailsNoObj($clients);
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::foot();
    }

    /**
     * View klient invalid emails with obj builder
     * @param $clients arr[obj]
     */
    public static function invalidEmailsWithObj($clients)
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::pInvalidEmailsWithObj($clients);
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::foot();
    }

    /**
     * View test clients builder
     * @param $clients arr[obj]
     */
    public static function testClients($clients)
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::pTestClients($clients);
        SlouceniKlientuView::foot();
    }

    /**
     * View duplicate filter
     * @param null|String $firstname
     * @param null|String $surname
     * @param null|String $email
     */
    public static function duplicateFilter($firstname = null, $surname = null, $email = null)
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::clientFilter($firstname, $surname, $email, "on", "on", "on", "on", "on");
        SlouceniKlientuView::foot();
    }

    /**
     * View duplicate merge found builder
     * @param null $filteredClients
     * @param null|String $firstname
     * @param null|String $surname
     * @param null|String $email
     * @param null $chFirstname
     * @param null $chSurname
     * @param null $chStreet
     * @param null $chCity
     * @param null $chPsc
     * @internal param $ null|arr[obj] $filteredClients
     */
    public static function duplicateMergeFound($filteredClients = null, $firstname = null, $surname = null, $email = null, $chFirstname = null, $chSurname = null, $chStreet = null, $chCity = null, $chPsc = null)
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::clientFilter($firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
        SlouceniKlientuView::pDuplicateMergeFound($filteredClients);
        SlouceniKlientuView::clearFloat();
        SlouceniKlientuView::foot();
    }

    public static function duplicateMerge($filteredClients = null, $clientsToMerge = null, $clientBeforeMerge = null, $clientAfterMerge = null, $firstname = null, $surname = null, $email = null, $id = null, $chFirstname = null, $chSurname = null, $chStreet = null, $chCity = null, $chPsc = null)
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::clientFilter($firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
        SlouceniKlientuView::pDuplicateMergeFound($filteredClients, $id);
        SlouceniKlientuView::pDuplicateMergeToMerge($clientsToMerge, $clientBeforeMerge, $clientAfterMerge);
        SlouceniKlientuView::clearFloat();
        SlouceniKlientuView::foot();
    }

    public static function duplicateMergePreMerge($filteredClients = null, $clientsToMerge = null, $clientBeforeMerge = null, $clientAfterMerge = null, $firstname = null, $surname = null, $email = null, $id = null, $chFirstname = null, $chSurname = null, $chStreet = null, $chCity = null, $chPsc = null)
    {
        SlouceniKlientuView::head();
        SlouceniKlientuView::homeAnchor();
        SlouceniKlientuView::clientFilter($firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
        SlouceniKlientuView::pDuplicateMergeFound($filteredClients, $id);
        SlouceniKlientuView::pDuplicateMergeToMerge($clientsToMerge, $clientBeforeMerge, $clientAfterMerge);
        SlouceniKlientuView::clearFloat();
        SlouceniKlientuView::foot();
    }

    public static function recentClientsFound($recentClientsToMerge)
    {
        SlouceniKlientuView::head();
        $prevPage = is_null($_REQUEST['p']) || $_REQUEST['p'] == '' ? 0 : ($_REQUEST['p'] - 1 < 0 ? 0 : $_REQUEST['p'] - 1);
        $nextPage = is_null($_REQUEST['p']) || $_REQUEST['p'] == '' ? 1 : $_REQUEST['p'] + 1;
        echo "<div class='submenu'>
                    <a href='slouceni_klientu.php'><< zpìt do hlavního menu</a>
                    <a href='slouceni_klientu.php?page=duplicit_merge_recent&p=$prevPage'><< pøedchozích 100</a>
                    <a href='slouceni_klientu.php?page=duplicit_merge_recent&p=$nextPage'>dalších 100 >></a>
                </div>";
        SlouceniKlientuView::pRecentClientsToMerge($recentClientsToMerge);
        SlouceniKlientuView::clearFloat();
        SlouceniKlientuView::foot();
    }

    /**
     * View uzivatel neni prihlasen
     */
    public static function loginErr()
    {
        $out = "";

        $out .= self::htmlHead();
        $out .= ModulView::showLoginForm(self::$employee->get_uzivatelske_jmeno());
        $out .= self::$employee->get_error_message();
        $out .= self::htmlFoot();

        echo $out;
    }

    /***************************** PRIVATE METHODS ******************************/

    /**
     * View zakladni menu - rozcestnik
     */
    private static function pMenu()
    {
        echo "<div class='submenu'>";
        echo "  <a href='slouceni_klientu.php?page=duplicit_merge_recent'>slouèení duplicitních klientù za poslední dobu</a><br/>";
        echo "  <a href='slouceni_klientu.php?page=duplicit_merge'>slouèení duplicitních klientù</a><br/>";
        echo "  <a href='slouceni_klientu.php?page=no_info'>klienti bez jména, pøíjmení a adresy, kteøí nejsou úèastníky zájezdu ale mohou být objednateli</a><br/>";
        echo "  <a href='slouceni_klientu.php?page=invalid_email_no_obj'>klienti, kteøí nemají platný email a zároveò nefigurují v žádné objednávce</a><br/>";
        echo "  <a href='slouceni_klientu.php?page=invalid_email_with_obj'>klienti, kteøí nemají platný email a figurují v objednávce</a><br/>";
        echo "  <a href='slouceni_klientu.php?page=test_clients'>testovací klienti</a>";
        echo "</div>";
    }

    /**
     * View klienti beze jmena, prijmeni a adresy, kteri nejsou ucastniky zajezdu ale mohou byt objednateli
     * @param $clients arr[obj] zobrazovani klienti
     */
    private static function pClientNoInfo($clients)
    {
        if (is_null($clients)) {
            MessageManager::addWarning("Žádné záznamy.");
            return;
        }

        echo "<form method='post' action='slouceni_klientu.php?page=no_info&action=delete'>";
        echo "<h3>Klienti beze jména, pøíjmení a adresy, kteøí nejsou úèastníky zájezdu ale mohou být objednateli</h3>";
        echo "  <table class='list'>";
        echo "      <tr><th><input type='checkbox' id='select_all_001'></th><th>id</th><th>klient je objednavatelem objednávky</th></tr>";
        $i = 1;
        foreach ($clients as $c) {
            $class = $i % 2 == 0 ? "suda" : "licha";
            $klientHref = "klienti.php?id_klient=$c->id_klient&typ=klient&pozadavek=edit";
            $objHref = "rezervace.php?id_objednavka=$c->id_obj_objednavatel&typ=rezervace&pozadavek=show";
            echo "<tr class='$class'>
                    <td><input type='checkbox' class='cb_001' name='cb_clientNoInfo[]' value='$c->id_klient-$c->id_obj_objednavatel' /></td>
                    <td><a target='_blank' href='$klientHref'>$c->id_klient</a></td>
                    <td><a target='_blank' href='$objHref'>$c->id_obj_objednavatel</a></td>
                  </tr>";
            $i++;
        }
        echo "      <tr class='edit'>";
        echo "          <td colspan='3'><input type='submit' class='action-delete' value='Smazat oznaèené i s objednávkou' id='delete-001' /></td>";
        echo "      </tr>";
        echo "  </table>";
        echo "</form><br/><br/>";
    }

    /**
     * Veiw klienti, kteri nemaji platny email a zaroven nefiguruji v zadné objednavce
     * @param $clients arr[obj] zobrazovani klienti
     */
    private static function pInvalidEmailsNoObj($clients)
    {
        if (is_null($clients)) {
            MessageManager::addWarning("Žádné záznamy.");
            return;
        }

        echo "<form method='post' action='slouceni_klientu.php?page=invalid_email_no_obj&action=delete'>";
        echo "<h3>Klienti, kteøí nemají platný email a zároveò nefigurují v žádné objednávce</h3>";
        echo "  <table class='list'>";
        echo "      <tr><th><input type='checkbox' id='select_all_002'></th><th>email</th><th>id klientù s tímto emailem</th></tr>";
        $i = 1;
        foreach ($clients as $c) {
            $class = $i % 2 == 0 ? "suda" : "licha";
            echo "<tr class='$class'>";
            echo "  <td><input type='checkbox' class='cb_002' name='cb_invalidEmailsNoObj[]' value='".$c->clientsId[0]."' /></td>";
            echo "  <td>$c->email</td>";     

            echo "  <td>" . SlouceniKlientuView::showIds($c->clientsId, $c->clientsFirstname, $c->clientsLastname) . "</td>";
            echo "</tr>";
            $i++;
            
        }
        echo "      <tr class='edit'>";
        echo "          <td colspan='3'><input type='submit' class='action-delete' value='Smazat oznaèené' id='delete-002' /></td>";
        echo "      </tr>";
        echo "  </table>";
        echo "</form><br/><br/>";
    }

    /**
     * View klienti kteri nemaji platny email a figuruji v objednavce
     * @param $clients arr[obj] zobrazovani klienti
     */
    private static function pInvalidEmailsWithObj($clients)
    {
        if (is_null($clients)) {
            MessageManager::addWarning("Žádné záznamy.");
            return;
        }

        echo "<form method='post'>";
        echo "<h3>Klienti, kteøí nemají platný email a figurují v objednávce</h3>";
        echo "  <table class='list'>";
        echo "      <tr>";
        echo "          <th>id</th>";
        echo "          <th>email</th>";
        echo "          <th>jméno</th>";
        echo "          <th>klient je<br/>objednavatelem<br/> objednávky</th>";
        echo "          <th>klient je<br/>úèastníkem<br/> objednávky</th>";
        echo "      </tr>";
        $i = 1;
        foreach ($clients as $c) {
            $class = $i % 2 == 0 ? "suda" : "licha";
            $klientHref = "klienti.php?id_klient=$c->id_klient&typ=klient&pozadavek=edit";
            $objednavatelHref = "rezervace.php?id_objednavka=$c->id_obj_objednavatel&typ=rezervace&pozadavek=show";
            $ucastnikHref = "rezervace.php?id_objednavka=$c->id_obj_ucastnik&typ=rezervace&pozadavek=show";
            echo "<tr class='$class'>
                    <td><a target='_blank' href='$klientHref'>$c->id_klient</a></td>
                    <td><span id='email_$c->id_klient'>$c->email</span></td>
                    <td>$c->jmeno $c->prijmeni</td>
                    <td><a target='_blank' href='$objednavatelHref'>$c->id_obj_objednavatel</a></td>
                    <td><a target='_blank' href='$ucastnikHref'>$c->id_obj_ucastnik</a></td>
                  </tr>";
            $i++;
        }
        echo "  </table>";
        echo "</form><br/><br/>";
    }

    /**
     * View testovaci klienti
     * @param $clients arr[obj] zobrazovani klienti
     */
    private static function pTestClients($clients)
    {
        if (is_null($clients)) {
            MessageManager::addWarning("Žádné záznamy.");
            return;
        }

        echo "<form method='post' action='slouceni_klientu.php?page=test_clients&action=delete'>";
        echo "  <h3>Testovací klienti</h3>";
        echo "  <table class='list'>";
        echo "      <tr>";
        echo "          <th><input type='checkbox' id='select_all_003'></th>";
        echo "          <th>id</th>";
        echo "          <th>email</th>";
        echo "          <th>jméno</th>";
        echo "          <th>pøíjmení</th>";
        echo "          <th>klient je<br/>objednavatelem<br/> objednávky</th>";
        echo "          <th>klient je<br/>úèastníkem<br/> objednávky</th>";
        echo "      </tr>";
        $i = 1;
        foreach ($clients as $c) {
            $class = $i % 2 == 0 ? "suda" : "licha";
            $klientHref = "klienti.php?id_klient=$c->id_klient&typ=klient&pozadavek=edit";
            $objednavatelHref = "rezervace.php?id_objednavka=$c->id_obj_objednavatel&typ=rezervace&pozadavek=show";
            $ucastnikHref = "rezervace.php?id_objednavka=$c->id_obj_ucastnik&typ=rezervace&pozadavek=show";
            echo "<tr class='$class'>
                    <td><input type='checkbox' class='cb_003' name='cb_testClients[]' value='$c->id_klient-$c->id_obj_objednavatel-$c->id_obj_ucastnik' />
                    <td><a target='_blank' href='$klientHref'>$c->id_klient</a></td>
                    <td><span id='email_$c->id_klient'>$c->email</span></td>
                    <td>$c->jmeno</td>
                    <td>$c->prijmeni</td>
                    <td><a target='_blank' href='$objednavatelHref'>$c->id_obj_objednavatel</a></td>
                    <td><a target='_blank' href='$ucastnikHref'>$c->id_obj_ucastnik</a></td>
                  </tr>";
            $i++;
        }
        echo "      <tr class='edit'>";
        echo "          <td colspan='7'>";
        echo "              <input type='submit' class='action-delete' value='Smazat oznaèené i s objednávkou' id='delete-003' />";
        echo "          </td>";
        echo "      </tr>";
        echo "  </table>";
        echo "</form><br/><br/>";
    }

    /**
     * View nalezeni klienti, ktere je potreba spojit
     * @param $clients arr[obj] zobrazovani klienti
     * @param null $id
     */
    private static function pDuplicateMergeFound($clients, $id = null)
    {
        if (is_null($clients)) {
            MessageManager::addWarning("Žádné záznamy.");
        }

        if (!is_null($clients)) {
            echo "<div class='float-left'>";
            echo "<form method='post' action=''>";
            echo "  <h3>Nalezení klienti</h3>";
            echo "  <table class='list'>";
            echo "      <tr>";
            echo "          <th>id</th>";
            echo "          <th>jméno pøíjmení<br/>email</th>";
            echo "          <th class='small-text'>klient je<br/> objednavatelem<br/> objednávky</th>";
            echo "          <th class='small-text'>klient je<br/>úèastníkem<br/> objednávky</th>";
            echo "          <th>akce</th>";
            echo "      </tr>";
            $i = 1;
            foreach ($clients as $c) {
                $class = $i % 2 == 0 ? "suda" : "licha";
                $class .= $id == $c->id_klient ? " picked" : "";
                $klientHref = "klienti.php?id_klient=$c->id_klient&typ=klient&pozadavek=edit";
                $objednavatelHref = "rezervace.php?id_objednavka=$c->id_obj_objednavatel&typ=rezervace&pozadavek=show";
                $ucastnikHref = "rezervace.php?id_objednavka=$c->id_obj_ucastnik&typ=rezervace&pozadavek=show";
                $duplicatesHref = "slouceni_klientu.php?page=duplicit_merge&action=show-duplicates&id=$c->id_klient#tr_$c->id_klient";
                $mergeHref = "slouceni_klientu.php?page=duplicit_merge&action=show-clients&subaction=merge-all&id=$c->id_klient";
                echo "<tr id='tr_$c->id_klient' class='$class'>
                        <td><a target='_blank' href='$klientHref'>$c->id_klient</a></td>
                        <td class='max-width1'>$c->jmeno $c->prijmeni<br/>$c->email<br/>$c->ulice<br/>$c->mesto, $c->psc</td>
                        <td><a target='_blank' href='$objednavatelHref'>$c->id_obj_objednavatel</a></td>
                        <td><a target='_blank' href='$ucastnikHref'>$c->id_obj_ucastnik</a></td>
                        <td><a href='$duplicatesHref'>duplikáty</a><a class='anchor-delete' style='margin-left: 5px;' href='$mergeHref'>slouèit</a></td>
                      </tr>";
                $i++;
            }
            echo "  </table>";
            echo "</form><br/><br/>";
            echo "</div>";
        }
    }

    /**
     * View duplicitni klienti, ktere je potreba pripojit k jednomu klientovi
     * @param $clients arr[obj] zobrazovani klienti
     * @param null $clientBeforeMerge
     * @param null $clientAfterMerge
     */
    private static function pDuplicateMergeToMerge($clients, $clientBeforeMerge = null, $clientAfterMerge = null)
    {
        if (is_null($clients)) {
            MessageManager::addWarning("Žádné záznamy.");
        }

        if (!is_null($clients)) {
            $id_merged = $_REQUEST["id"];
            echo "<div id='clients-to-merge' class='float-left offset-left-10'>";
            echo "<form method='post' action='slouceni_klientu.php?page=duplicit_merge&action=show-duplicates&subaction=merge-all&id=$id_merged#tr_$id_merged'>";
            echo "<h3>Klienti ke slouèení</h3>";
            echo "  <table class='list'>";
            echo "      <tr>";
            echo "          <th>id</th>";
            echo "          <th>jméno pøíjmení<br/>email</th>";
            echo "          <th class='small-text'>klient je<br/> objednavatelem<br/> objednávky</th>";
            echo "          <th class='small-text'>klient je<br/>úèastníkem<br/> objednávky</th>";
            echo "          <th>akce</th>";
            echo "      </tr>";
            $i = 1;
            foreach ($clients as $c) {
                $class = $i % 2 == 0 ? "suda" : "licha";
                $klientHref = "klienti.php?id_klient=$c->id_klient&typ=klient&pozadavek=edit";
                $objednavatelHref = "rezervace.php?id_objednavka=$c->id_obj_objednavatel&typ=rezervace&pozadavek=show";
                $ucastnikHref = "rezervace.php?id_objednavka=$c->id_obj_ucastnik&typ=rezervace&pozadavek=show";
                $mergeHref = "slouceni_klientu.php?page=duplicit_merge&action=show-duplicates&subaction=pre-merge&id=$id_merged&id_to_merge=$c->id_klient#tr_$id_merged";

                echo "<tr class='$class'>
                        <td><a target='_blank' href='$klientHref'>$c->id_klient</a></td>
                        <td class='max-width1'>$c->jmeno $c->prijmeni<br/>$c->email</td>
                        <td><a target='_blank' href='$objednavatelHref'>$c->id_obj_objednavatel</a></td>
                        <td><a target='_blank' href='$ucastnikHref'>$c->id_obj_ucastnik</a></td>
                        <td>
                            <a href='$mergeHref'>zobrazit</a>
                            <a class='anchor-delete' style='margin-left: 5px;' href='slouceni_klientu.php?page=duplicit_merge&action=show-duplicates&subaction=merge&id=" . $_REQUEST["id"] . "&id_to_merge=$c->id_klient#tr_" . $_REQUEST["id"] . "'>slouèit</a>
                        </td>
                      </tr>";
                $i++;
            }
            echo "  </table>";
            echo "</form>";

            if (!is_null($clientBeforeMerge) && !is_null($clientAfterMerge))
                SlouceniKlientuView::printCompareClients($clientBeforeMerge, $clientAfterMerge);

            echo "</div>";

            echo "<script>moveClientsToMerge(" . $_REQUEST["id"] . ");</script>";
        }
    }

    private static function pRecentClientsToMerge($recentClientsToMerge)
    {
        if (is_null($recentClientsToMerge)) {
            MessageManager::addWarning("Žádné záznamy.");
            return;
        }

        echo "<div id='clients-to-merge' class='float-left offset-left-10'>";
        echo "<h3>Nedávno pøidaní klienti</h3>";
        echo "  <table class='list'>";
        echo "      <tr>";
        echo "          <th>id</th>";
        echo "          <th>jméno pøíjmení</th>";
        echo "          <th>email</th>";
        echo "          <th>datum narození</th>";
        echo "          <th>poèet duplikátù</th>";
        echo "          <th class='small-text'>objednávka</th>";
        echo "          <th class='small-text'>objednávka rezervována</th>";
        echo "          <th>akce</th>";
        echo "      </tr>";
        $i = 1;
        foreach ($recentClientsToMerge as $c) {
            $class = $i % 2 == 0 ? "suda" : "licha";
            $klientHref = "klienti.php?id_klient=$c->id_klient&typ=klient&pozadavek=edit";
            $objednavatelHref = "https://slantour.cz/admin/objednavky.php?idObjednavka=$c->id_objednavka";
            $mergeHref = "slouceni_klientu.php?page=duplicit_merge_recent&action=merge-duplicate&id=$c->id_klient";

            echo "<tr class='$class'>
                        <td><a target='_blank' href='$klientHref'>$c->id_klient</a></td>
                        <td class='max-width1'>$c->jmeno $c->prijmeni</td>
                        <td>$c->email</td>
                        <td>" . CommonUtils::czechDate($c->datum_narozeni) . "</td>
                        <td>" . ($c->count - 1) . "</td>
                        <td><a target='_blank' href='$objednavatelHref'>$c->id_objednavka</a></td>
                        <td>" . CommonUtils::czechDatetime($c->datum_rezervace) . "</td>
                        <td>
                            <a class='anchor-delete' style='margin-left: 5px;' href='$mergeHref'>slouèit</a>
                        </td>
                      </tr>";
            $i++;
        }
        echo "  </table>";
        echo "</div>";
    }

    private static function htmlHead()
    {
        $out = "";

        $out .= "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>";
        $out .= "<html>";
        $out .= "  <head>";
        $out .= "      <title>" . SlouceniKlientuView::$core->show_nazev_modulu() . " | Administrace systému RSCK</title>";
        $out .= "      <meta http-equiv='Content-Type' content='ext/html; charset=windows-1250'/>";
        $out .= "      <meta name='copyright' content='&copy; Slantour'/>";
        $out .= "      <meta http-equiv= 'pragma' content='no-cache' />";
        $out .= "      <meta name='robots' content='noindex,noFOLLOW' />";
        $out .= "      <link href='https://fonts.googleapis.com/css?family=Roboto:400,100italic,100,300,300italic,400italic,500,500italic,700,700italic&subset=latin,latin-ext' rel='stylesheet' type='text/css'>";
        $out .= "      <link rel='stylesheet' type='text/css' href='css/reset-min.css'>";
        $out .= "      <link rel='stylesheet' type='text/css' href='./new-menu/style.css' media='all'/>";
        $out .= "      <script src='js/jquery-min.js' type='text/javascript'></script>";
        $out .= "      <script src='js/slouceni-klientu.js' type='text/javascript'></script>";
        $out .= "  </head>";
        $out .= "<body>";

        return $out;
    }

    /**
     * View hlavicka
     */
    private static function head()
    {
        echo self::htmlHead();

        //zobrazeni hlavniho menu
        echo ModulView::showNavigation(new AdminModulHolder(self::$core->show_all_allowed_moduls()), self::$employee, self::$core->get_id_modul());

        echo "<div class='main-wrapper'>";
        echo "  <div class='main'>";
        if (SlouceniKlientuController::$DEBUG)
            echo "  <h2 class='red'>Napojeno na testovací tabulky s pøedponou test_</h2>";
        else
            echo "  <h2 class='red'>Napojeno na reálnou databázi</h2>";
    }

    /**
     * View paticka
     */
    private static function foot()
    {
        echo "      </div>";
        echo "  </div>";
        echo "</div>";

        echo ModulView::showHelp(self::$core->show_current_modul()["napoveda"]);

        echo self::htmlFoot();
    }

    private static function htmlFoot()
    {
        $out = "";

        $out .= "</body>";
        $out .= "</html>";

        return $out;
    }

    /**
     * View filter klientu
     */
    private static function clientFilter($firstname = null, $surname = null, $email = null, $chFirstname = null, $chSurname = null, $chStreet = null, $chCity = null, $chPsc = null)
    {
        $chFirstname = is_null($chFirstname) ? "" : "checked";
        $chSurname = is_null($chSurname) ? "" : "checked";
        $chStreet = is_null($chStreet) ? "" : "checked";
        $chCity = is_null($chCity) ? "" : "checked";
        $chPsc = is_null($chPsc) ? "" : "checked";
        echo "<form method='post' action='slouceni_klientu.php?page=duplicit_merge&action=show-clients' role='form'>";
        echo "<table class='filtr'>";
        echo "  <tr>";
        echo "  <td>";
        echo "      jméno: <input type='text' name='filter-firstname' value='$firstname'/>";
        echo "  </td>";
        echo "  <td>";
        echo "      pøíjmení: <input type='text' name='filter-surname' value='$surname'/>";
        echo "  </td>";
        echo "  <td>";
        echo "      email: <input type='text' name='filter-email' value='$email'/>";
        echo "  </td>";
        echo "  <td>";
        echo "      najít duplicity podle: ";
        echo "      <label><input type='checkbox' name='filter-merge-firstname' $chFirstname /> jméno</div>";
        echo "      <label><input type='checkbox' name='filter-merge-surname' $chSurname /> pøíjmení</div>";
        echo "      <label><input type='checkbox' name='filter-merge-street' $chStreet /> ulice</div>";
        echo "      <label><input type='checkbox' name='filter-merge-city' $chCity /> mìsto</div>";
        echo "      <label><input type='checkbox' name='filter-merge-psc' $chPsc /> psè</div>";
        echo "  </td>";
        echo "  <td>";
        echo "      <input type='submit' name='btn-filter' value='Filtrovat' class='btn'/>";
        echo "  </td>";
        echo "  </tr>";
        echo "</table>";
        echo "</form>";
    }

    /**
     * Zobrazi idecka kompaktne pokud je jich vice jak 10
     * @param $ids arr[int] idcka k zobrazeni
     * @param $firstnames
     * @param $lastnames
     * @return string zobrazeni v HTML
     */
    private static function showIds($ids, $firstnames, $lastnames)
    {
        $cnt = count((array)$ids);
        $output = "";
        if ($cnt > 10) {
            $output .= "$cnt klientù <a href='#' onclick='return showSibling(this);' style='color: #46588A; cursor: pointer;'>+ rozbalit</a>";
            $output .= "<div class='hidden_ids' style='display: none;'>";
        }

        for ($i = 0; $i < count((array)$ids); $i++) {
            $klientHref = "klienti.php?id_klient=$ids[$i]&typ=klient&pozadavek=edit";
            $output .= "<div>";
            $output .= "<a target='_blank' href='$klientHref'>[$ids[$i]]</a> - ";
            $output .= " $firstnames[$i]";
            $output .= " $lastnames[$i]";
            $output .= " <a href='#' class='anchor-delete' onclick='return removeClientAjax(this, $ids[$i]);'>smazat klienta</a>";
            $output .= "</div>";
        }

        if ($cnt > 10) {
            $output .= "</div>";
        }

        return $output;
    }

    /**
     * View odkaz na hlavni menu
     */
    private static function homeAnchor()
    {
        echo "<div class='submenu'><a href='slouceni_klientu.php'><< zpìt do hlavního menu</a></div>";
    }

    /**
     * View clear float
     */
    private static function clearFloat()
    {
        echo "<div class='clear-float'></div>";
    }

    /**
     * View tabulka porovnani klientu
     * @param $client1 obj klient 1
     * @param $client2 obj klient 2
     */
    public static function printCompareClients($client1, $client2)
    {
        $userNameDiff = $client1->uzivatelske_jmeno == $client2->uzivatelske_jmeno ? "" : "diff";
        $firstnameDiff = $client1->jmeno == $client2->jmeno ? "" : "diff";
        $surnameDiff = $client1->prijmeni == $client2->prijmeni ? "" : "diff";
        $titleDiff = $client1->titul == $client2->titul ? "" : "diff";
        $phoneDiff = $client1->telefon == $client2->telefon ? "" : "diff";
        $birthDateDiff = $client1->datum_narozeni == $client2->datum_narozeni ? "" : "diff";
        $passNoDiff = $client1->cislo_pasu == $client2->cislo_pasu ? "" : "diff";
        $opNoDiff = $client1->cislo_op == $client2->cislo_op ? "" : "diff";
        $idNoDiff = $client1->rodne_cislo == $client2->rodne_cislo ? "" : "diff";
        $streetDiff = $client1->ulice == $client2->ulice ? "" : "diff";
        $cityDiff = $client1->mesto == $client2->mesto ? "" : "diff";
        $zipCodeDiff = $client1->psc == $client2->psc ? "" : "diff";
        $icoDiff = $client1->ico == $client2->ico ? "" : "diff";
        echo "<h3>Porovnání klientù</h3>";
        echo "<table class='list'>";
        echo "  <tr><th></th><th>pøed slouèením</th><th>po slouèení</th></tr>";
        echo "  <tr class='licha'><td>email</td><td>$client1->email</td><td>$client2->email</td></tr>";
        echo "  <tr class='suda $userNameDiff'><td>uživatelské jméno</td><td>$client1->uzivatelske_jmeno</td><td>$client2->uzivatelske_jmeno</td></tr>";
        echo "  <tr class='licha $firstnameDiff'><td>jméno</td><td>$client1->jmeno</td><td>$client2->jmeno</td></tr>";
        echo "  <tr class='suda $surnameDiff'><td>pøíjmení</td><td>$client1->prijmeni</td><td>$client2->prijmeni</td></tr>";
        echo "  <tr class='licha $titleDiff'><td>titul</td><td>$client1->titul</td><td>$client2->titul</td></tr>";
        echo "  <tr class='suda $phoneDiff'><td>telefon</td><td>$client1->telefon</td><td>$client2->telefon</td></tr>";
        echo "  <tr class='licha $birthDateDiff'><td>datum narození</td><td>$client1->datum_narozeni</td><td>$client2->datum_narozeni</td></tr>";
        echo "  <tr class='suda $passNoDiff'><td>èíslo pasu</td><td>$client1->cislo_pasu</td><td>$client2->cislo_pasu</td></tr>";
        echo "  <tr class='licha $opNoDiff'><td>èíslo op</td><td>$client1->cislo_op</td><td>$client2->cislo_op</td></tr>";
        echo "  <tr class='suda $idNoDiff'><td>rodné èíslo</td><td>$client1->rodne_cislo</td><td>$client2->rodne_cislo</td></tr>";
        echo "  <tr class='licha $streetDiff'><td>ulice</td><td>$client1->ulice</td><td>$client2->ulice</td></tr>";
        echo "  <tr class='suda $cityDiff'><td>mìsto</td><td>$client1->mesto</td><td>$client2->mesto</td></tr>";
        echo "  <tr class='licha $zipCodeDiff'><td>psè</td><td>$client1->psc</td><td>$client2->psc</td></tr>";
        echo "  <tr class='suda $icoDiff'><td>ièo</td><td>$client1->ico</td><td>$client2->ico</td></tr>";
        echo "  <tr class='edit'>";
        echo "      <td colspan='3' class='align-center'><a class='anchor-delete' href='slouceni_klientu.php?page=duplicit_merge&action=show-duplicates&subaction=merge&id=$client1->id_klient&id_to_merge=" . $_REQUEST["id_to_merge"] . "#tr_$client1->id_klient'>slouèit</a></td>";
        echo "  </tr>";
        echo "</table>";
    }
}

?>
