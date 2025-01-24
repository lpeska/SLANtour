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
	"Veèeøe" => 3,
	"3" => 4,//polopenze
	"4" => 5,//plna
	"5" => 6,//ai
	"Dle programu" => 7,
);

    public static $doprava = array(
	"1" => 1,//vlastni
	"3" => 2,//letecky
	"2" => 4,//autokar
	"Lodí" => 5,
	"4"=> 6,//kombinovaná
        "5"=> 6 //kombinovaná
);

public static $ubytovani_nazev = array(
	1 => "bez ubytování",
	2 => "stan",
	3 => "chatky",
	4 => "apartmány",
	5 => "penzion",
	6 => "hotel",
	7 => "hotel",
	8 => "hotel",
	9 => "hotel",
	10 => "hotel",
        11 =>  "lázeòský dùm"
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
	"nespecifikováno" => 1,
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
	"nespecifikováno" => 1,
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
	"Dospìlý" => 1,
	"Dítì" => 2,
	"Senior" => 3,
);
    public static $zeme = array(
"Aruba"	 =>	"ABW"	,
"Afghánistán"	 =>	"AFG"	,
"Angola"	 =>	"AGO"	,
"Anguilla"	 =>	"AIA"	,
"Albánie"	 =>	"ALB"	,
"Andorra"	 =>	"AND"	,
"Spojené Arabské Emiráty"	 =>	"ARE"	,
"Argentina"	 =>	"ARG"	,
"Arménie"	 =>	"ARM"	,
"Americká Samoa"	 =>	"ASM"	,
"Antarktida"	 =>	"ATA"	,
"Antigua a Barbuda"	 =>	"ATG"	,
"Austrálie"	 =>	"AUS"	,
"Rakousko"	 =>	"AUT"	,
"Ázerbájdžán"	 =>	"AZE"	,
"Burundi"	 =>	"BDI"	,
"Belgie"	 =>	"BEL"	,
"Benin"	 =>	"BEN"	,
"Burkina Faso"	 =>	"BFA"	,
"Bangladéš"	 =>	"BGD"	,
"Bulharsko"	 =>	"BGR"	,
"Bahrajn"	 =>	"BHR"	,
"Bahamy"	 =>	"BHS"	,
"Bosna a Hercegovina"	 =>	"BIH"	,
"Bìlorusko"	 =>	"BLR"	,
"Belize"	 =>	"BLZ"	,
"Bermudy"	 =>	"BMU"	,
"Bolívie"	 =>	"BOL"	,
"Brazílie"	 =>	"BRA"	,
"Barbados"	 =>	"BRB"	,
"Brunej"	 =>	"BRN"	,
"Bhútán"	 =>	"BTN"	,
"Botswana"	 =>	"BWA"	,
"Støedoafrická republika"	 =>	"CAF"	,
"Kanada"	 =>	"CAN"	,
"Kokosové ostrovy"	 =>	"CCK"	,
"Pobøeží slonoviny"	 =>	"CIV"	,
"Kamerun"	 =>	"CMR"	,
"Kongo"	 =>	"COG"	,
"Cookovy ostrovy"	 =>	"COK"	,
"Kolumbie"	 =>	"COL"	,
"Komory"	 =>	"COM"	,
"Kapverdy"	 =>	"CPV"	,
"Kostarika"	 =>	"CRI"	,
"Kuba"	 =>	"CUB"	,
"Vánoèní ostrov"	 =>	"CXR"	,
"Kajmanské ostrovy"	 =>	"CYM"	,
"Kypr"	 =>	"CYP"	,
"Èeská republika"	 =>	"CZE"	,
"Nìmecko"	 =>	"DEU"	,
"NÌMECKO"	 =>	"DEU"	,
"Džibutsko"	 =>	"DJI"	,
"Dominika"	 =>	"DMA"	,
"Dánsko"	 =>	"DNK"	,
"Dominikánská republika"	 =>	"DOM"	,
"Alžírsko"	 =>	"DZA"	,
"Ekvádor"	 =>	"ECU"	,
"Egypt"	 =>	"EGY"	,
"Eritrea"	 =>	"ERI"	,
"Západní Sahara"	 =>	"ESH"	,
"Španìlsko"	 =>	"ESP"	,
"Estonsko"	 =>	"EST"	,
"Etiopie"	 =>	"ETH"	,
"Finsko"	 =>	"FIN"	,
"Fidži"	 =>	"FJI"	,
"Falklandy (Malvíny)"	 =>	"FLK"	,
"Francie"	 =>	"FRA"	,
"Faerské ostrovy"	 =>	"FRO"	,
"Mikronésie"	 =>	"FSM"	,
"Gabon"	 =>	"GAB"	,
"Velká Británie"	 =>	"GBR"	,
"Anglie"	 =>	"GBR"	,
"Skotsko"	 =>	"GBR"	,
"Gruzie"	 =>	"GEO"	,
"Ghana"	 =>	"GHA"	,
"Gibraltar"	 =>	"GIB"	,
"Guinea"	 =>	"GIN"	,
"Guadeloupe"	 =>	"GLP"	,
"Gambie"	 =>	"GMB"	,
"Guinea-Bissau"	 =>	"GNB"	,
"Rovníková Guinea"	 =>	"GNQ"	,
"Øecko"	 =>	"GRC"	,
"Grenada"	 =>	"GRD"	,
"Grónsko"	 =>	"GRL"	,
"Guatemala"	 =>	"GTM"	,
"Francouzská Guyana"	 =>	"GUF"	,
"Guyana"	 =>	"GUY"	,
"Hongkong"	 =>	"HKG"	,
"Honduras"	 =>	"HND"	,
"Chorvatsko"	 =>	"HRV"	,
"Haiti"	 =>	"HTI"	,
"Maïarsko"	 =>	"HUN"	,
"Švýcarsko"	 =>	"CHE"	,
"Chile"	 =>	"CHL"	,
"Èína"	 =>	"CHN"	,
"Indonésie"	 =>	"IDN"	,
"Ostrov Man"	 =>	"IMN"	,
"Indie"	 =>	"IND"	,
"Irsko"	 =>	"IRL"	,
"Írán"	 =>	"IRN"	,
"Irák"	 =>	"IRQ"	,
"Island"	 =>	"ISL"	,
"Izrael"	 =>	"ISR"	,
"Itálie"	 =>	"ITA"	,
"Jamajka"	 =>	"JAM"	,
"Jordánsko"	 =>	"JOR"	,
"Japonsko"	 =>	"JPN"	,
"Kazachstán"	 =>	"KAZ"	,
"Keòa"	 =>	"KEN"	,
"Kyrgyzstán"	 =>	"KGZ"	,
"Kambodža"	 =>	"KHM"	,
"Svatý Kryštof a Nevis"	 =>	"KNA"	,
"Jižní Korea"	 =>	"KOR"	,
"Kuvajt"	 =>	"KWT"	,
"Laos"	 =>	"LAO"	,
"Libanon"	 =>	"LBN"	,
"Libérie"	 =>	"LBR"	,
"Libye"	 =>	"LBY"	,
"Svatá Lucie"	 =>	"LCA"	,
"Lichtenštejnsko"	 =>	"LIE"	,
"Srí Lanka"	 =>	"LKA"	,
"Lesotho"	 =>	"LSO"	,
"Litva"	 =>	"LTU"	,
"Lucembursko"	 =>	"LUX"	,
"Lotyšsko"	 =>	"LVA"	,
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
"Èerná Hora"	 =>	"MNE"	,
"Mongolsko"	 =>	"MNG"	,
"Mosambik"	 =>	"MOZ"	,
"Mauritánie"	 =>	"MRT"	,
"Martinik"	 =>	"MTQ"	,
"Mauricius"	 =>	"MUS"	,
"Malawi"	 =>	"MWI"	,
"Malajsie"	 =>	"MYS"	,
"Mayotte"	 =>	"MYT"	,
"Namibie"	 =>	"NAM"	,
"Nová Kaledonie"	 =>	"NCL"	,
"Niger"	 =>	"NER"	,
"Nigérie"	 =>	"NGA"	,
"Nikaragua"	 =>	"NIC"	,
"Nizozemsko"	 =>	"NLD"	,
"Holandsko"	 =>	"NLD"	,
"Norsko"	 =>	"NOR"	,
"Nepál"	 =>	"NPL"	,
"Nový Zéland"	 =>	"NZL"	,
"Omán"	 =>	"OMN"	,
"Pákistán"	 =>	"PAK"	,
"Panama"	 =>	"PAN"	,
"Peru"	 =>	"PER"	,
"Filipíny"	 =>	"PHL"	,
"Palau"	 =>	"PLW"	,
"Papua Nová Guinea"	 =>	"PNG"	,
"Polsko"	 =>	"POL"	,
"Portoriko"	 =>	"PRI"	,
"Severní Korea"	 =>	"PRK"	,
"Portugalsko"	 =>	"PRT"	,
"Paraguay"	 =>	"PRY"	,
"Palestina"	 =>	"PSE"	,
"Francouzská Polynésie"	 =>	"PYF"	,
"Katar"	 =>	"QAT"	,
"Réunion"	 =>	"REU"	,
"Rumunsko"	 =>	"ROU"	,
"Rusko"	 =>	"RUS"	,
"Rwanda"	 =>	"RWA"	,
"Saúdská Arábie"	 =>	"SAU"	,
"Súdán"	 =>	"SDN"	,
"Senegal"	 =>	"SEN"	,
"Singapur"	 =>	"SGP"	,
"Svatá Helena"	 =>	"SHN"	,
"Šalamounovy ostrovy"	 =>	"SLB"	,
"Sierra Leone"	 =>	"SLE"	,
"Salvador"	 =>	"SLV"	,
"San Marino"	 =>	"SMR"	,
"Somálsko"	 =>	"SOM"	,
"Srbsko"	 =>	"SRB"	,
"Svatý Tomáš"	 =>	"STP"	,
"Surinam"	 =>	"SUR"	,
"Slovensko"	 =>	"SVK"	,
"Slovinsko"	 =>	"SVN"	,
"Švédsko"	 =>	"SWE"	,
"Svazijsko"	 =>	"SWZ"	,
"Seychely"	 =>	"SYC"	,
"Sýrie"	 =>	"SYR"	,
"Èad"	 =>	"TCD"	,
"Togo"	 =>	"TGO"	,
"Thajsko"	 =>	"THA"	,
"Tádžikistán"	 =>	"TJK"	,
"Turkmenistán"	 =>	"TKM"	,
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
"Uzbekistán"	 =>	"UZB"	,
"Vatikán"	 =>	"VAT"	,
"Svatý Vincenc a Grenadiny"	 =>	"VCT"	,
"Venezuela"	 =>	"VEN"	,
"Britské Panenské ostrovy"	 =>	"VGB"	,
"Vietnam"	 =>	"VNM"	,
"Vanuatu"	 =>	"VUT"	,
"Samoa"	 =>	"WSM"	,
"Jemen"	 =>	"YEM"	,
"Jihoafrická republika"	 =>	"ZAF"	,
"Zambie"	 =>	"ZMB"	,
"Zimbabwe"	 =>	"ZWE"

);
}
?>
