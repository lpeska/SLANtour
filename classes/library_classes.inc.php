<?php
/** \file
* library_classes.inc.php - obsahuje definice knihovnich trid
*/

/** trida pro staticka data, ktera souvisi s objektem seri�l (typ ubytov�n�, typ dopravy,...)*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Serial_library{
	static private $doprava = array("vlastn�","autokar","letecky");
	static private $strava = array("bez stravy","sn�dan�","polopenze","pln� penze","all inclusive");
	static private $ubytovani = array("bez ubytov�n�","stan","chatky","apartm�ny","penzion","hotel","hotel 2*","hotel 3*","hotel 4*","hotel 5*");
	static private $typ_ceny = array("cena","last minute","sleva","p��platek");
	
	//priv�tn� konstruktor
	private function __construct(){
	
	}
	
	/** vrati typ dopravy daneho cisla*/
	static function get_typ_dopravy($cislo_typu){
		if( array_key_exists($cislo_typu,self::$doprava) ){
			return self::$doprava[$cislo_typu];
		}else{
			return "";
		}
	}

	/** vrati typ stravy daneho cisla*/
	static function get_typ_stravy($cislo_typu){
		if( array_key_exists($cislo_typu,self::$strava) ){
			return self::$strava[$cislo_typu];
		}else{
			return "";
		}		
	}	
		
		/** vrati typ ubytovani daneho cisla*/
	static function get_typ_ubytovani($cislo_typu){
		if( array_key_exists($cislo_typu,self::$ubytovani) ){
			return self::$ubytovani[$cislo_typu];
		}else{
			return "";
		}		
	}	
	/** vrati typ ceny daneho cisla*/
	static function get_typ_ceny($cislo_typu){
		if( array_key_exists($cislo_typu,self::$typ_ceny) ){
			return self::$typ_ceny[$cislo_typu];
		}else{
			return "";
		}		
	}		
}

/** trida pro statick� metody validace polo�ek formul��e*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Validace{
	//priv�tn� konstruktor
	private function __construct(){
	
	}
	
	/**kontrola obecnych textovych informaci*/
	static function text($vstup){
		if( preg_match("^[^@\$<>]+$",$vstup) ){
			return true;
		}else{
			return false;
		}
	}

	/**kontrola data ve form�tu ISO*/
	static function datum_en($vstup){
		if( ereg ("^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$", $vstup) ){
			return true;
		}else{
			return false;
		}
	}	
	
	/**kontrola �esk�ho form�tu data*/
	static function datum_cz($vstup){
		if( ereg ("^[0-9]{1,2} ?\.[0-9]{1,2} ?\.[0-9]{4}$", $vstup) ){
			return true;
		}else{
			return false;
		}
	}	
	
	/**kontrola e-mailu*/
	static function email($vstup){
		if( preg_match("^[a-zA-Z0-9_\+\-\.]+@[a-zA-Z0-9_\+\-\.]+\.[a-zA-Z]+$",$vstup) ){
			return true;
		}else{
			return false;
		}
	}	
	
	/**kontrola integeru*/
	static function int($vstup){
		if( preg_match("[0-9]+$",$vstup) ){
			return true;
		}else{
			return false;
		}
	}
	/**kontrola integeru s omezen�m minim�ln� hodnoty*/
	static function int_min($vstup,$min){
		if( preg_match("[0-9]+$",$vstup) and $vstup >= $min ){
			return true;
		}else{
			return false;
		}
	}	
	/** kontrola integeru s omezen�m minim�ln� i maxim�ln� hodnoty*/
	static function int_min_max($vstup,$min,$max){
		if( preg_match("[0-9]+$",$vstup) and $vstup >= $min and $vstup <= $max ){
			return true;
		}else{
			return false;
		}
	}		
}

/** trida pro staticke funkce parsov�n� p��choz�ch po�adavk� (viz bc pr�ce, tvary adres 3 a 2)*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Parameter_parser{
	//priv�tn� konstruktor
	private function __construct(){
	
	}
	
	/**premena pozadavku ze tvaru "poz1/poz2/poz3... na tvar ?lev1=poz1&lev2=poz2&lev3=poz3...*/
	static function parse($parametry){
		if( $parametry!="" ){
			$array_parametry = explode("/",$parametry);
		}
		$i=0;
		while($array_parametry[$i] != ""){
			$level = "lev".($i+1);
			$_GET[$level] = $array_parametry[$i];
			$i++;
		}
	}
}

/** trida pro staticka data, ktera souvisi s objektem informace*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Informace_library{
	static private $typ_informace = array("popis zem�","popis destinace","informace");

	//priv�tn� konstruktoroby�ejn� informace
	private function __construct(){
	
	}
	
	/** vrati typ informace daneho cisla*/
	static function get_typ_informace($cislo_typu){
		if( array_key_exists($cislo_typu,self::$typ_informace) ){
			return self::$typ_informace[$cislo_typu];
		}else{
			return "";
		}
	}	
}

/** trida pro staticka data, ktera souvisi s objektem rezervace (stav rezervace)*/
//private kostruktor + final class zarucuje, ze trida nebude instancovana
final class Rezervace_library{
	//stavy zmeneny
	//static private $stav_rezervace = array("p�edb�n� popt�vka","po�adavek na rezervaci","opce","rezervace","z�loha","zaplaceno","odbaveno","storno");
	static private $stav_rezervace = array("p�edb�n� popt�vka","po�adavek na rezervaci","opce","rezervace","z�loha","prod�no","odbaveno","storno");
    static private $stav_rezervace_styl = array("stav-predb","stav-pozad","stav-opce","stav-rez","stav-zal","stav-prodano","stav-odbav","stav-storno");
    static public $STAV_PREDBEZNA_POPTAVKA = 1;
    static public $STAV_POZADAVEK_NA_REZERVACI = 2;
    static public $STAV_OPCE = 3;
    static public $STAV_REZERVACE = 4;
    static public $STAV_ZALOHA = 5;
    static public $STAV_PRODANO = 6;
    static public $STAV_ODBAVENO = 7;
    static public $STAV_STORNO = 8;

	//priv�tn� konstruktor oby�ejn� informace
	private function __construct(){
	
	}
	
	/** vrati stav rezervace daneho cisla*/
	static function get_stav($cislo_stavu){
		if( array_key_exists($cislo_stavu,self::$stav_rezervace) ){
			return self::$stav_rezervace[$cislo_stavu];
		}else{
			return "";
		}
	}
        
        /** vrati styl rezervace daneho cisla*/
	static function get_stav_styl($cislo_stavu){
		if( array_key_exists($cislo_stavu,self::$stav_rezervace_styl) ){
			return self::$stav_rezervace_styl[$cislo_stavu];
		}else{
			return "";
		}
	}       
}


?>
