<?php
/**
generic.inc.php - definuje obecne (abstraktni) tridy
 */

/*trida pro staticka data, ktera souvisi s objektem seri�l (typ ubytov�n�, typ dopravy,...)*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Serial_library
{
    static private $doprava = array("vlastn�", "autokar", "letecky", "vlakem", "vlastn� nebo autobus");
    static private $strava = array("bez stravy", "sn�dan�", "polopenze", "pln� penze", "all inclusive");
    static private $ubytovani = array("bez ubytov�n�", "stan", "chatky", "apartm�ny", "penzion", "hotel", "hotel 2*", "hotel 3*", "hotel 4*", "hotel 5*", "l�ze�sk� d�m");
    static private $typ_ceny = array("cena", "last minute", "sleva", "p��platek", "odjezdov� m�sto");

    static private $typ_kontaktu = array("Hlavn� kontakt", "��t�rna", "Rezervace", "Ostatn�");
    static private $typ_bankovniho_spojeni = array("", "Hlavn� kontakt", "ostatn�");
    static private $typ_organizace = array("", "Prodejce (CA)", "Ubytovac� za��zen�", "Partner", "Dopravce", "Jin�", "Pobo�ka");
    static private $typ_adresy = array("", "S�dlo spole�nosti", "Kontaktn� adresa", "Dal�� adresa");

    static private $typ_objektu = array("", "Ubytovac�", "Dopravn�", "Vstupenka", "Ostatn�", "Letu�ka API", "GoGlobal API");

    //priv�tn� konstruktor
    private function __construct()
    {

    }

    /*typy objektu*/
    static function get_organizace_objektu($id_organizace)
    {
        $query = "select `organizace`.`nazev`, `organizace`.`id_organizace`
                    from `objekt` join `organizace` on (`objekt`.`id_organizace` = `organizace`.`id_organizace`)
                    where 1
                    order by `organizace`.`nazev`";
        $result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
        $return = "";
        while ($row = mysqli_fetch_array($result)) {
            if ($row["id_organizace"] == $id_organizace) {
                $selected = "selected=\"selected\"";
            } else {
                $selected = "";
            }

            $return .= "<option value=\"" . $row["id_organizace"] . "\" " . $selected . ">" . $row["nazev"] . "</option>";
        }
        return $return;
    }

    /*typy dopravy*/
    static function get_typ_adresy($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$typ_adresy)) {
            return self::$typ_adresy[$cislo_typu];
        } else {
            return "";
        }
    }

    /*typy objektu*/
    static function get_typ_objektu($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$typ_objektu)) {
            return self::$typ_objektu[$cislo_typu];
        } else {
            return "";
        }
    }

    /*typy dopravy*/
    static function get_typ_organizace($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$typ_organizace)) {
            return self::$typ_organizace[$cislo_typu];
        } else {
            return "";
        }
    }

    /*typy dopravy*/
    static function get_typ_bankovniho_spojeni($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$typ_bankovniho_spojeni)) {
            return self::$typ_bankovniho_spojeni[$cislo_typu];
        } else {
            return "";
        }
    }

    /*typy dopravy*/
    static function get_typ_kontaktu($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$typ_kontaktu)) {
            return self::$typ_kontaktu[$cislo_typu];
        } else {
            return "";
        }
    }

    /*typy dopravy*/
    static function get_typ_dopravy($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$doprava)) {
            return self::$doprava[$cislo_typu];
        } else {
            return "";
        }
    }

    /*typy stravy*/
    static function get_typ_stravy($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$strava)) {
            return self::$strava[$cislo_typu];
        } else {
            return "";
        }
    }

    /*typy ubytovani*/
    static function get_typ_ubytovani($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$ubytovani)) {
            return self::$ubytovani[$cislo_typu];
        } else {
            return "";
        }
    }

    /*typy cen*/
    static function get_typ_ceny($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$typ_ceny)) {
            return self::$typ_ceny[$cislo_typu];
        } else {
            return "";
        }
    }

    static function change_date_en_cz($vstup)
    {
        if ($vstup != "") {
            $date_time = explode(" ", $vstup);
            $date_array = explode("-", $date_time[0]); //prvni parametr v datetime je datum, druhy cas
            $vystup = $date_array[2] . "." . $date_array[1] . "." . $date_array[0];
            if ($date_time[1] != "") {
                $vystup = $vystup . " " . $date_time[1];
            }
            return $vystup;
        } else {
            return "";
        }
    }

}


/*trida pro staticka data, ktera souvisi s objektem seri�l (typ ubytov�n�, typ dopravy,...)*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Validace{
	//priv�tn� konstruktor
	private function __construct(){
	
	}
	
	/**kontrola obecnych textovych informaci*/
	static function text($vstup){
		if( preg_match("/^[^@\$<>]+$/",$vstup) ){
			return true;
		}else{
			return false;
		}
	}

	/**kontrola data ve form�tu ISO*/
	static function datum_en($vstup){
		if( preg_match ("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/", $vstup) ){
			return true;
		}else{
			return false;
		}
	}	
	
	/**kontrola �esk�ho form�tu data*/
	static function datum_cz($vstup){
		if( preg_match ("/^[0-9]{1,2} ?\.[0-9]{1,2} ?\.[0-9]{4}$/", $vstup) ){
			return true;
		}else{
			return false;
		}
	}	
	
	/**kontrola e-mailu*/
	static function email($vstup){
		if( preg_match("/^[a-zA-Z0-9_\+\-\.]+@[a-zA-Z0-9_\+\-\.]+\.[a-zA-Z]+$/",$vstup) ){
			return true;
		}else{
			return false;
		}
	}	
	
	/**kontrola integeru*/
	static function int($vstup){
		if( preg_match("/[0-9]+/$",$vstup) ){
			return true;
		}else{
			return false;
		}
	}
	/**kontrola integeru s omezen�m minim�ln� hodnoty*/
	static function int_min($vstup,$min){
		if( preg_match("/[0-9]+$/",$vstup) and $vstup >= $min ){
			return true;
		}else{
			return false;
		}
	}	
	/** kontrola integeru s omezen�m minim�ln� i maxim�ln� hodnoty*/
	static function int_min_max($vstup,$min,$max){
		if( preg_match("/[0-9]+$/",$vstup) and $vstup >= $min and $vstup <= $max ){
			return true;
		}else{
			return false;
		}
	}		
}


/*trida pro staticka data, ktera souvisi s objektem seri�l (typ ubytov�n�, typ dopravy,...)*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Informace_library
{
    static private $typ_informace = array("popis zem�", "popis destinace", "informace");

    //priv�tn� konstruktoroby�ejn� informace
    private function __construct()
    {

    }

    /*typy dopravy*/
    static function get_typ_informace($cislo_typu)
    {
        if (array_key_exists($cislo_typu, self::$typ_informace)) {
            return self::$typ_informace[$cislo_typu];
        } else {
            return "";
        }
    }
}

/*trida pro staticka data, ktera souvisi s objektem rezervace (stav rezervace)*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Rezervace_library
{
    //stavy zmeneny
    //static private $stav_rezervace = array("p�edb�n� popt�vka","po�adavek na rezervaci","opce","rezervace","z�loha","zaplaceno","odbaveno","storno");
    static private $stav_rezervace = array("p�edb�n� popt�vka", "po�adavek na rezervaci", "opce", "rezervace", "z�loha", "prod�no", "odbaveno", "storno","storno CK","VOUCHER");
    static private $stav_rezervace_styl = array("stav-predb", "stav-pozad", "stav-opce", "stav-rez", "stav-zal", "stav-prodano", "stav-odbav", "stav-storno","stav-storno","stav-voucher");
    static public $STAV_PREDBEZNA_POPTAVKA = 1;
    static public $STAV_POZADAVEK_NA_REZERVACI = 2;
    static public $STAV_OPCE = 3;
    static public $STAV_REZERVACE = 4;
    static public $STAV_ZALOHA = 5;
    static public $STAV_PRODANO = 6;
    static public $STAV_ODBAVENO = 7;
    static public $STAV_STORNO = 8;
    static public $STAV_STORNO_CK = 9;
    static public $STAV_VOUCHER = 10;

    //priv�tn� konstruktoroby�ejn� informace
    private function __construct()
    {

    }

    /*typy dopravy*/
    static function get_stav($cislo_stavu)
    {
        if (array_key_exists($cislo_stavu, self::$stav_rezervace)) {
            return self::$stav_rezervace[$cislo_stavu];
        } else {
            return "";
        }
    }

    /** vrati styl rezervace daneho cisla*/
    static function get_stav_styl($cislo_stavu)
    {
        if (array_key_exists($cislo_stavu, self::$stav_rezervace_styl)) {
            return self::$stav_rezervace_styl[$cislo_stavu];
        } else {
            return "";
        }
    }
}


?>
