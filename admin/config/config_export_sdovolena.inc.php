<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of config_export_sdovolena
 *
 * @author peska
 */
class config_export_sdovolena {
    //put your code here
    
   public static $typy = array(
  	"pobytove-zajezdy" => 100,
        "exotika" => 101,
        "lazenske-pobyty" => 103,
	"pobyty-na-horach" => 104,
	"poznavaci-zajezdy" => 200,
        "jednodenni-zajezdy" => 200,
       "eurovikendy" => 200,
       "fly-and-drive" => 200,
       "za-kulturou" => 200,
	"za-sportem" => 300,
	"lyzovani" => 304,
);

    public static $strava = array(
	"1" => 1,//bez
	"2" => 2,//snidane
	"Ve�e�e" => 3,
	"3" => 4,//polopenze
	"4" => 5,//plna
	"5" => 6,//ai
	"Dle programu" => 7,
);

    public static $doprava = array(
	"1" => 1,//vlastni
	"3" => 2,//letecky
	"2" => 4,//autokar
	"Lod�" => 5,
	"4"=> 6,//kombinovan�
        "5"=> 6 //kombinovan�
);

public static $ubytovani_nazev = array(
	1 => "bez ubytov�n�",
	2 => "stan",
	3 => "chatky",
	4 => "apartm�ny",
	5 => "penzion",
	6 => "hotel",
	7 => "hotel",
	8 => "hotel",
	9 => "hotel",
	10 => "hotel",
        11 =>  "l�ze�sk� d�m"
);

public static function calculate_pocet_noci($od, $do){
	 	$pole_od=explode("-", $od);
		$pole_do=explode("-", $do); 
		
	 	$time_od = mktime(0,0,0,$pole_od[1],$pole_od[2],$pole_od[0]);
		$time_do = mktime(0,0,0,$pole_do[1],$pole_do[2],$pole_do[0]);
		$pocet_noci = (round(($time_do - $time_od) / (24*60*60)));
		if($pocet_noci<0){
	 		$pocet_noci=0;
	 	}
		return $pocet_noci;
  }

public static $ubytovani_kategorie = array(
	"nespecifikov�no" => 1,
	"1" => 2,
	"*+" => 3,
	"2" => 4,
	"**+" => 5,
	"3" => 6,
	"***+" => 7,
	"4" => 8,
	"****+" => 9,
	"5" => 10,
        "*****+"=>	11
);

public static $ubytovani = array(
	"nespecifikov�no" => 1,
	"*" => 2,
	"*+" => 3,
	"7" => 4,
	"**+" => 5,
	"8" => 6,
	"***+" => 7,
	"9" => 8,
	"****+" => 9,
	"10" => 10,
        "*****+"=>	11
);

    public static $typy_cen = array(
	"Dosp�l�" => 1,
	"D�t�" => 2,
	"Senior" => 3,
);
    public static $zeme = array(
"Aruba"	 =>	"ABW"	,
"Afgh�nist�n"	 =>	"AFG"	,
"Angola"	 =>	"AGO"	,
"Anguilla"	 =>	"AIA"	,
"Alb�nie"	 =>	"ALB"	,
"Andorra"	 =>	"AND"	,
"Spojen� Arabsk� Emir�ty"	 =>	"ARE"	,
"Argentina"	 =>	"ARG"	,
"Arm�nie"	 =>	"ARM"	,
"Americk� Samoa"	 =>	"ASM"	,
"Antarktida"	 =>	"ATA"	,
"Antigua a Barbuda"	 =>	"ATG"	,
"Austr�lie"	 =>	"AUS"	,
"Rakousko"	 =>	"AUT"	,
"�zerb�jd��n"	 =>	"AZE"	,
"Burundi"	 =>	"BDI"	,
"Belgie"	 =>	"BEL"	,
"Benin"	 =>	"BEN"	,
"Burkina Faso"	 =>	"BFA"	,
"Banglad�"	 =>	"BGD"	,
"Bulharsko"	 =>	"BGR"	,
"Bahrajn"	 =>	"BHR"	,
"Bahamy"	 =>	"BHS"	,
"Bosna a Hercegovina"	 =>	"BIH"	,
"B�lorusko"	 =>	"BLR"	,
"Belize"	 =>	"BLZ"	,
"Bermudy"	 =>	"BMU"	,
"Bol�vie"	 =>	"BOL"	,
"Braz�lie"	 =>	"BRA"	,
"Barbados"	 =>	"BRB"	,
"Brunej"	 =>	"BRN"	,
"Bh�t�n"	 =>	"BTN"	,
"Botswana"	 =>	"BWA"	,
"St�edoafrick� republika"	 =>	"CAF"	,
"Kanada"	 =>	"CAN"	,
"Kokosov� ostrovy"	 =>	"CCK"	,
"Pob�e�� slonoviny"	 =>	"CIV"	,
"Kamerun"	 =>	"CMR"	,
"Kongo"	 =>	"COG"	,
"Cookovy ostrovy"	 =>	"COK"	,
"Kolumbie"	 =>	"COL"	,
"Komory"	 =>	"COM"	,
"Kapverdy"	 =>	"CPV"	,
"Kostarika"	 =>	"CRI"	,
"Kuba"	 =>	"CUB"	,
"V�no�n� ostrov"	 =>	"CXR"	,
"Kajmansk� ostrovy"	 =>	"CYM"	,
"Kypr"	 =>	"CYP"	,
"�esk� republika"	 =>	"CZE"	,
"N�mecko"	 =>	"DEU"	,
"N�MECKO"	 =>	"DEU"	,
"D�ibutsko"	 =>	"DJI"	,
"Dominika"	 =>	"DMA"	,
"D�nsko"	 =>	"DNK"	,
"Dominik�nsk� republika"	 =>	"DOM"	,
"Al��rsko"	 =>	"DZA"	,
"Ekv�dor"	 =>	"ECU"	,
"Egypt"	 =>	"EGY"	,
"Eritrea"	 =>	"ERI"	,
"Z�padn� Sahara"	 =>	"ESH"	,
"�pan�lsko"	 =>	"ESP"	,
"Estonsko"	 =>	"EST"	,
"Etiopie"	 =>	"ETH"	,
"Finsko"	 =>	"FIN"	,
"Fid�i"	 =>	"FJI"	,
"Falklandy (Malv�ny)"	 =>	"FLK"	,
"Francie"	 =>	"FRA"	,
"Faersk� ostrovy"	 =>	"FRO"	,
"Mikron�sie"	 =>	"FSM"	,
"Gabon"	 =>	"GAB"	,
"Velk� Brit�nie"	 =>	"GBR"	,
"Anglie"	 =>	"GBR"	,
"Skotsko"	 =>	"GBR"	,
"Gruzie"	 =>	"GEO"	,
"Ghana"	 =>	"GHA"	,
"Gibraltar"	 =>	"GIB"	,
"Guinea"	 =>	"GIN"	,
"Guadeloupe"	 =>	"GLP"	,
"Gambie"	 =>	"GMB"	,
"Guinea-Bissau"	 =>	"GNB"	,
"Rovn�kov� Guinea"	 =>	"GNQ"	,
"�ecko"	 =>	"GRC"	,
"Grenada"	 =>	"GRD"	,
"Gr�nsko"	 =>	"GRL"	,
"Guatemala"	 =>	"GTM"	,
"Francouzsk� Guyana"	 =>	"GUF"	,
"Guyana"	 =>	"GUY"	,
"Hongkong"	 =>	"HKG"	,
"Honduras"	 =>	"HND"	,
"Chorvatsko"	 =>	"HRV"	,
"Haiti"	 =>	"HTI"	,
"Ma�arsko"	 =>	"HUN"	,
"�v�carsko"	 =>	"CHE"	,
"Chile"	 =>	"CHL"	,
"��na"	 =>	"CHN"	,
"Indon�sie"	 =>	"IDN"	,
"Ostrov Man"	 =>	"IMN"	,
"Indie"	 =>	"IND"	,
"Irsko"	 =>	"IRL"	,
"�r�n"	 =>	"IRN"	,
"Ir�k"	 =>	"IRQ"	,
"Island"	 =>	"ISL"	,
"Izrael"	 =>	"ISR"	,
"It�lie"	 =>	"ITA"	,
"Jamajka"	 =>	"JAM"	,
"Jord�nsko"	 =>	"JOR"	,
"Japonsko"	 =>	"JPN"	,
"Kazachst�n"	 =>	"KAZ"	,
"Ke�a"	 =>	"KEN"	,
"Kyrgyzst�n"	 =>	"KGZ"	,
"Kambod�a"	 =>	"KHM"	,
"Svat� Kry�tof a Nevis"	 =>	"KNA"	,
"Ji�n� Korea"	 =>	"KOR"	,
"Kuvajt"	 =>	"KWT"	,
"Laos"	 =>	"LAO"	,
"Libanon"	 =>	"LBN"	,
"Lib�rie"	 =>	"LBR"	,
"Libye"	 =>	"LBY"	,
"Svat� Lucie"	 =>	"LCA"	,
"Lichten�tejnsko"	 =>	"LIE"	,
"Sr� Lanka"	 =>	"LKA"	,
"Lesotho"	 =>	"LSO"	,
"Litva"	 =>	"LTU"	,
"Lucembursko"	 =>	"LUX"	,
"Loty�sko"	 =>	"LVA"	,
"Macao"	 =>	"MAC"	,
"Maroko"	 =>	"MAR"	,
"Monako"	 =>	"MCO"	,
"Moldavsko"	 =>	"MDA"	,
"Madagaskar"	 =>	"MDG"	,
"Maledivy"	 =>	"MDV"	,
"Mexiko"	 =>	"MEX"	,
"Makedonie"	 =>	"MKD"	,
"Mali"	 =>	"MLI"	,
"Malta"	 =>	"MLT"	,
"Barma"	 =>	"MMR"	,
"�ern� Hora"	 =>	"MNE"	,
"Mongolsko"	 =>	"MNG"	,
"Mosambik"	 =>	"MOZ"	,
"Maurit�nie"	 =>	"MRT"	,
"Martinik"	 =>	"MTQ"	,
"Mauricius"	 =>	"MUS"	,
"Malawi"	 =>	"MWI"	,
"Malajsie"	 =>	"MYS"	,
"Mayotte"	 =>	"MYT"	,
"Namibie"	 =>	"NAM"	,
"Nov� Kaledonie"	 =>	"NCL"	,
"Niger"	 =>	"NER"	,
"Nig�rie"	 =>	"NGA"	,
"Nikaragua"	 =>	"NIC"	,
"Nizozemsko"	 =>	"NLD"	,
"Holandsko"	 =>	"NLD"	,
"Norsko"	 =>	"NOR"	,
"Nep�l"	 =>	"NPL"	,
"Nov� Z�land"	 =>	"NZL"	,
"Om�n"	 =>	"OMN"	,
"P�kist�n"	 =>	"PAK"	,
"Panama"	 =>	"PAN"	,
"Peru"	 =>	"PER"	,
"Filip�ny"	 =>	"PHL"	,
"Palau"	 =>	"PLW"	,
"Papua Nov� Guinea"	 =>	"PNG"	,
"Polsko"	 =>	"POL"	,
"Portoriko"	 =>	"PRI"	,
"Severn� Korea"	 =>	"PRK"	,
"Portugalsko"	 =>	"PRT"	,
"Paraguay"	 =>	"PRY"	,
"Palestina"	 =>	"PSE"	,
"Francouzsk� Polyn�sie"	 =>	"PYF"	,
"Katar"	 =>	"QAT"	,
"R�union"	 =>	"REU"	,
"Rumunsko"	 =>	"ROU"	,
"Rusko"	 =>	"RUS"	,
"Rwanda"	 =>	"RWA"	,
"Sa�dsk� Ar�bie"	 =>	"SAU"	,
"S�d�n"	 =>	"SDN"	,
"Senegal"	 =>	"SEN"	,
"Singapur"	 =>	"SGP"	,
"Svat� Helena"	 =>	"SHN"	,
"�alamounovy ostrovy"	 =>	"SLB"	,
"Sierra Leone"	 =>	"SLE"	,
"Salvador"	 =>	"SLV"	,
"San Marino"	 =>	"SMR"	,
"Som�lsko"	 =>	"SOM"	,
"Srbsko"	 =>	"SRB"	,
"Svat� Tom�"	 =>	"STP"	,
"Surinam"	 =>	"SUR"	,
"Slovensko"	 =>	"SVK"	,
"Slovinsko"	 =>	"SVN"	,
"�v�dsko"	 =>	"SWE"	,
"Svazijsko"	 =>	"SWZ"	,
"Seychely"	 =>	"SYC"	,
"S�rie"	 =>	"SYR"	,
"�ad"	 =>	"TCD"	,
"Togo"	 =>	"TGO"	,
"Thajsko"	 =>	"THA"	,
"T�d�ikist�n"	 =>	"TJK"	,
"Turkmenist�n"	 =>	"TKM"	,
"Tonga"	 =>	"TON"	,
"Trinidad a Tobago"	 =>	"TTO"	,
"Tunisko"	 =>	"TUN"	,
"Turecko"	 =>	"TUR"	,
"Tchaj-wan"	 =>	"TWN"	,
"Tanzanie"	 =>	"TZA"	,
"Uganda"	 =>	"UGA"	,
"Ukrajina"	 =>	"UKR"	,
"Uruguay"	 =>	"URY"	,
"USA"	 =>	"USA"	,
"Uzbekist�n"	 =>	"UZB"	,
"Vatik�n"	 =>	"VAT"	,
"Svat� Vincenc a Grenadiny"	 =>	"VCT"	,
"Venezuela"	 =>	"VEN"	,
"Britsk� Panensk� ostrovy"	 =>	"VGB"	,
"Vietnam"	 =>	"VNM"	,
"Vanuatu"	 =>	"VUT"	,
"Samoa"	 =>	"WSM"	,
"Jemen"	 =>	"YEM"	,
"Jihoafrick� republika"	 =>	"ZAF"	,
"Zambie"	 =>	"ZMB"	,
"Zimbabwe"	 =>	"ZWE"

);
}
?>
