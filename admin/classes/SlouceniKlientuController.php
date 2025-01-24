<?php

class SlouceniKlientuController {

    public static $DEBUG;

    /**
     * Inicializuje promenne a spusti navigaci
     */
    public static function run()
    {
        SlouceniKlientuController::init();
        SlouceniKlientuController::navigate();
    }

    /**
     * Inicializuje prihlaseneho uzivatele, core, pripojeni k databazi, nazvy tabulek a debug mode
     */
    private static function init()
    {
        SlouceniKlientuView::$employee = User_zamestnanec::get_instance();

        SlouceniKlientuView::$core = Core::get_instance();

        SlouceniKlientuModel::$db = Database::get_instance();

        //debug
        SlouceniKlientuModel::$TBL_USER_KLIENT = "user_klient";
        SlouceniKlientuModel::$TBL_OBJEDNAVKA = "objednavka";
        SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY = "objednavka_osoby";
        SlouceniKlientuController::$DEBUG = false;
    }

    /**
     * Navigacni rozcesti pro cely modul
     */
    private static function navigate()
    {
        if (SlouceniKlientuView::$employee->get_correct_login()) {
            switch ($_REQUEST["page"]) {
                case "duplicit_merge_recent":
                    SlouceniKlientuController::navDuplicateMergeRecent();
                    break;
                case "duplicit_merge":
                    SlouceniKlientuController::navDuplicateMerge();
                    break;
                case "no_info":
                    SlouceniKlientuController::navNoInfo();
                    break;
                case "invalid_email_no_obj":
                    SlouceniKlientuController::navInvalidEmailNoObj();
                    break;
                case "invalid_email_with_obj":
                    SlouceniKlientuController::navInvalidEmailsWithObj();
                    break;
                case "test_clients":
                    SlouceniKlientuController::navTestClients();
                    break;
                case "ajax":
                    SlouceniKlientuController::navAjax();
                    break;
                case "menu":
                default:
                    SlouceniKlientuView::menu();
                    break;
            }
        } else {
            SlouceniKlientuView::loginErr();
        }
    }

    /**
     * Navigace v sekci slouceni duplicitnich klientu za posledni dobu
     */
    private static function navDuplicateMergeRecent()
    {
        switch ($_REQUEST["action"]) {
            default:
            case "show-clients":
                SlouceniKlientuController::navDuplicateMergeRecentShowClients();
                break;
            case "merge-duplicate":
                SlouceniKlientuController::navDuplicateMergeRecentMergeDuplicate();
                break;
        }

    }

    private static function navDuplicateMergeRecentShowClients()
    {
        $p = $_REQUEST['p'] == '' ? 0 : $_REQUEST['p'] * SlouceniKlientuModel::GET_RECENT_CLIENTS_PAGING_LIMIT;
        $recentClients = SlouceniKlientuModel::getRecentClients($p);

        SlouceniKlientuView::recentClientsFound($recentClients);
    }

    private static function navDuplicateMergeRecentMergeDuplicate()
    {
        //get client to be merged - $mergedClient
        $mergedClient = SlouceniKlientuModel::getClient($_REQUEST["id"]);
        //get others to merge with - $clientsToMege
        $clientsToMege = SlouceniKlientuModel::getRecentClientsToMerge($mergedClient);
        if(!is_null($clientsToMege)){
            foreach ($clientsToMege as $clientToMerge) {
                SlouceniKlientuModel::mergeAndUpdateClient($mergedClient, $clientToMerge);
            }
        }

        $_REQUEST["action"] = 'show-clients';
        self::navDuplicateMergeRecent();
    }

    /**
     * Navigace v sekci slouceni duplicitnich klientu
     */
    private static function navDuplicateMerge()
    {
        switch ($_REQUEST["action"]) {
            case "show-clients":
                SlouceniKlientuController::navDuplicateMergeShowClients();
                break;
            case "show-duplicates":
                SlouceniKlientuController::navDuplicateMergeShowDuplicates();
                break;
            default:
                SlouceniKlientuView::duplicateFilter();
                break;
        }

    }

    /**
     * Navigace v sekci slouceni duplicitnich klientu - podsekci zobrazeni filtrovanych klientu
     */
    private static function navDuplicateMergeShowClients()
    {
	//print_r($_REQUEST);
        $clientsDuplicate = null;
        $firstname = $_REQUEST["btn-filter"] !== 'Filtrovat' ? SlouceniKlientuModel::getRequestValue("filter-firstname") : $_REQUEST["filter-firstname"];
        $surname = $_REQUEST["btn-filter"] !== 'Filtrovat' ? SlouceniKlientuModel::getRequestValue("filter-surname") : $_REQUEST["filter-surname"];
        $email = $_REQUEST["btn-filter"] !== 'Filtrovat' ? SlouceniKlientuModel::getRequestValue("filter-email") : $_REQUEST["filter-email"];
        $chFirstname = $_REQUEST["btn-filter"] !== 'Filtrovat' ? SlouceniKlientuModel::getRequestValue("filter-merge-firstname") : $_REQUEST["filter-merge-firstname"];
        $chSurname = $_REQUEST["btn-filter"] !== 'Filtrovat' ? SlouceniKlientuModel::getRequestValue("filter-merge-surname") : $_REQUEST["filter-merge-surname"];
        $chStreet = $_REQUEST["btn-filter"] !== 'Filtrovat' ? SlouceniKlientuModel::getRequestValue("filter-merge-street") : $_REQUEST["filter-merge-street"];
        $chCity = $_REQUEST["btn-filter"] !== 'Filtrovat' ? SlouceniKlientuModel::getRequestValue("filter-merge-city") : $_REQUEST["filter-merge-city"];
        $chPsc = $_REQUEST["btn-filter"] !== 'Filtrovat' ? SlouceniKlientuModel::getRequestValue("filter-merge-psc") : $_REQUEST["filter-merge-psc"];

        if (SlouceniKlientuModel::isValueSet($firstname) || SlouceniKlientuModel::isValueSet($surname) || SlouceniKlientuModel::isValueSet($email)) {
            $clientsDuplicate = SlouceniKlientuModel::getFilteredClients($firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
            SlouceniKlientuModel::saveRequestsInSession(array(
                    "filter-firstname" => $firstname,
                    "filter-surname" => $surname,
                    "filter-email" => $email,
                    "filter-merge-firstname" => $chFirstname,
                    "filter-merge-surname" => $chSurname,
                    "filter-merge-street" => $chStreet,
                    "filter-merge-city" => $chCity,
                    "filter-merge-psc" => $chPsc)
            );
        }

        //sloucit vsechny
        if ($_REQUEST["subaction"] == 'merge-all') {
            $clientsToMege = SlouceniKlientuModel::getClientsToMerge($_REQUEST["id"], $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
            $mergedClient = SlouceniKlientuModel::getClient($_REQUEST["id"]);
            foreach ($clientsToMege as $clientToMerge) {
                SlouceniKlientuModel::mergeAndUpdateClient($mergedClient, $clientToMerge);
            }
        }

        SlouceniKlientuView::duplicateMergeFound($clientsDuplicate, $firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
    }

    /**
     * Navigace v sekci slouceni duplicitnich klientu - podsekci zobrazeni duplicitnich klientu
     */
    private static function navDuplicateMergeShowDuplicates()
    {
        $firstname = SlouceniKlientuModel::getRequestValue("filter-firstname");
        $surname = SlouceniKlientuModel::getRequestValue("filter-surname");
        $email = SlouceniKlientuModel::getRequestValue("filter-email");
        $chFirstname = SlouceniKlientuModel::getRequestValue("filter-merge-firstname");
        $chSurname = SlouceniKlientuModel::getRequestValue("filter-merge-surname");
        $chStreet = SlouceniKlientuModel::getRequestValue("filter-merge-street");
        $chCity = SlouceniKlientuModel::getRequestValue("filter-merge-city");
        $chPsc = SlouceniKlientuModel::getRequestValue("filter-merge-psc");
        $id = $_REQUEST["id"];

        switch ($_REQUEST["subaction"]) {
            case "pre-merge":
                $idToMerge = $_REQUEST["id_to_merge"];
                $mergedClient = SlouceniKlientuModel::getClient($id);
                $clientToMerge = SlouceniKlientuModel::getClient($idToMerge);
                $clientAfterMerge = SlouceniKlientuModel::mergeClient($mergedClient, $clientToMerge);
                //ziskej klienty
                if (SlouceniKlientuModel::isValueSet($firstname) || SlouceniKlientuModel::isValueSet($surname) || SlouceniKlientuModel::isValueSet($email)) {
                    $filteredClients = SlouceniKlientuModel::getFilteredClients($firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                    $clientsMerge = SlouceniKlientuModel::getClientsToMerge($id, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                }

                SlouceniKlientuView::duplicateMergePreMerge($filteredClients, $clientsMerge, $mergedClient, $clientAfterMerge, $firstname, $surname, $email, $id, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                break;
            case "merge":
                $idToMerge = $_REQUEST["id_to_merge"];
                $mergedClient = SlouceniKlientuModel::getClient($id);
                $clientToMerge = SlouceniKlientuModel::getClient($idToMerge);
                SlouceniKlientuModel::mergeAndUpdateClient($mergedClient, $clientToMerge);
                //ziskej klienty
                if (SlouceniKlientuModel::isValueSet($firstname) || SlouceniKlientuModel::isValueSet($surname) || SlouceniKlientuModel::isValueSet($email)) {
                    $filteredClients = SlouceniKlientuModel::getFilteredClients($firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                    $clientsMerge = SlouceniKlientuModel::getClientsToMerge($id, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                }

                SlouceniKlientuView::duplicateMerge($filteredClients, $clientsMerge, $mergedClient, $clientAfterMerge, $firstname, $surname, $email, $id, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                break;
            default:
                //ziskej klienty
                if (SlouceniKlientuModel::isValueSet($firstname) || SlouceniKlientuModel::isValueSet($surname) || SlouceniKlientuModel::isValueSet($email)) {
                    $filteredClients = SlouceniKlientuModel::getFilteredClients($firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                    $clientsMerge = SlouceniKlientuModel::getClientsToMerge($id, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                }
                SlouceniKlientuView::duplicateMerge($filteredClients, $clientsMerge, $mergedClient, $clientAfterMerge, $firstname, $surname, $email, $id, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc);
                break;
        }
    }

    /**
     * Navigace v sekci klienti beze jmena, prijmeni a adresy, kteri nejsou ucastniky zajezdu ale mohou byt objednateli
     */
    private static function navNoInfo()
    {
        switch ($_REQUEST["action"]) {
            case "delete":
                SlouceniKlientuModel::delClientsNoInfo();
                break;
        }
        $clients = SlouceniKlientuModel::getClientNoInfo();

        SlouceniKlientuView::clientNoInfo($clients);
    }

    /**
     * Navigace v sekci klienti, kteri nemaji platny email a zaroven nefiguruji v zadné objednavce
     */
    private static function navInvalidEmailNoObj()
    {
        $idsNotDeleted = null;
        switch ($_REQUEST["action"]) {
            case "delete":
                SlouceniKlientuModel::delClients($_REQUEST["cb_invalidEmailsNoObj"]);
                break;
        }
        $clients = SlouceniKlientuModel::getInvalidEmailsNoObj();

        SlouceniKlientuView::invalidEmailsNoObj($clients);
    }

    /**
     * Navigace v sekci klienti kteri nemaji platny email a figuruji v objednavce
     */
    private static function navInvalidEmailsWithObj()
    {
        $clients = SlouceniKlientuModel::getInvalidEmailsWithObj();

        SlouceniKlientuView::invalidEmailsWithObj($clients);
    }

    /**
     * Navigace v sekci testovaci klienti
     */
    private static function navTestClients()
    {
        switch ($_REQUEST["action"]) {
            case "delete":
                SlouceniKlientuModel::delTestClientsWithObj();
                break;
        }
        $clients = SlouceniKlientuModel::getTestClients();

        SlouceniKlientuView::testClients($clients);
    }

    private static function navAjax()
    {
        switch ($_REQUEST["action"]) {
            case "delete-client":
                if (SlouceniKlientuModel::delClient())
                    header("HTTP/1.0 200 Deleted");
                else
                    header("HTTP/1.0 404 Not deleted");
                break;
        }
    }
}

?>
