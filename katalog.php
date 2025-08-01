<?php
// Redirect logic for deprecated katalog.php

//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";
require_once "./classes/serial_lists.inc.php";


if (isset($_GET["lev1"])) {
    $id_typ = Serial_list::get_id_from_typ($_GET["lev1"]);
    $id_zeme = Serial_list::get_id_from_zeme($_GET["lev1"]);
    if ($id_typ > 0) {
        if (!empty($_GET["lev2"])) {
            $id_zeme = Serial_list::get_id_from_zeme($_GET["lev2"]);
            if ($id_zeme > 0) {
                // Handle destinace, use IDs for vyhledavani, ignore podtyp
                $query = "/vyhledavani?tourTypeFilter[]=" . urlencode($id_typ) . "&countryFilter[]=" . urlencode($id_zeme);
                if (!empty($_GET["lev3"])) {
                    $id_dest = Serial_list::get_id_from_destinace($_GET["lev3"], $_GET["lev2"]);
                    if ($id_dest > 0) {
                        $query .= "&destinaceFilter[]=" . urlencode($id_dest);
                    }
                }
                header("Location: $query", true, 301);
                exit;
            }
        } else {
            // Only type is set
            header("Location: /zajezdy/typ-zajezdu/" . urlencode($_GET['lev1']), true, 301);
            exit;
        }
    } else if ($id_zeme > 0) {
         if (!empty($_GET["lev2"])) {
            $id_dest = Serial_list::get_id_from_destinace($_GET["lev2"], $_GET["lev1"]);
            if ($id_dest > 0) {
                // Handle destinace with zeme, use IDs for vyhledavani
                header("Location: /vyhledavani?countryFilter[]=" . urlencode($id_zeme) . "&destinaceFilter[]=" . urlencode($id_dest), true, 301);
                exit;
            }
        } else {
            // Only country is set
            header("Location: /zajezdy/zeme/" . urlencode($_GET['lev1']), true, 301);
            exit;
        }
    }
}
// If nothing matches, fallback redirect
header("Location: /vyhledavani", true, 301);
exit;
