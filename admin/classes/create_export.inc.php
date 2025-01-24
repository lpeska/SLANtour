<?php

include "../includes/funkce2.inc";//vlastni funkce
function create_export($format, $typ){
    var_dump($GLOBALS["core"]->database->db_spojeni);
            
    $sql_adresa_ck = "select * from centralni_data where nazev=\"admin:web\" limit 1";
    $data_adresa = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_adresa_ck);
    $adresa_ck = "";
    while ($row = mysqli_fetch_array($data_adresa)) {
       $adresa_ck = $row["text"] ;
    }
    $res="";
    if($format=="zakladni"){
        $res = generate_xml($typ,$adresa_ck);

    }else if($format=="cestujeme"){
        $res .= generate_xml_cestujeme($typ,$adresa_ck);

    }else if($format=="invia"){
        $res .= generate_xml_invia($typ,$adresa_ck);
        $res .= generate_xml_invia_nove($typ,$adresa_ck);

    }else if($format=="sdovolena"){
        //echo "testing";
        $res .= generate_xml_sdovolena($typ,$adresa_ck);

    }
    echo "adresa".$adresa_ck;
    $res.="probìhlo exportování";
    return $res;
}


function generate_xml_sdovolena($typ_serialu,$adresa_ck){
   // $sql_adresa

//------------------------------------------------------------------------------------------------

if($typ_serialu=="cele"){
    $fp = fopen ("../sdovolena/".$typ_serialu.".xml","w"); //otevru podle parametru
}else{
    $fp = fopen ("../sdovolena/xml-".$typ_serialu.".xml","w"); //otevru podle parametru
}
if(!$fp){
echo "<h4 style=\"color:black\">error, nepodarilo se otevrit soubor!!!</h4>";
}else{

echo "podarilo se otevrit/vytvorit soubor <i>\"sdovolena/xml-".$typ_serialu.".xml \"</i><br/> ";


//hlavicka
$final_text="<?xml version=\"1.0\" encoding=\"windows-1250\" ?>
<tours>
<currency_id>1</currency_id>\n";
if($typ_serialu=="cele"){
    $dotaz="SELECT distinct `serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`popis_stravovani`,`popis_ubytovani`,`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`cena_nezahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`nazev_typ_web`,
                                `nazev_zeme_web`,`strava`,`doprava`,`ubytovani`,`serial`.`id_serial`,`serial`.`id_sablony_zobrazeni`,

                                `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`

			FROM `serial` join
                                `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                                `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ` and `typ_serial`.`id_nadtyp`=0)
                                             
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                            
			where `zeme`.`geograficka_zeme`=1 and `od`>'".Date("Y-m-d")."'   and `serial`.`dlouhodobe_zajezdy`=0 
                            and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  and `serial`.`id_sablony_zobrazeni` != 8  
                        group by `serial`.`id_serial`    
                        order by `serial`.`id_serial`, `zeme_serial`.`zakladni_zeme` desc,`zeme_serial`.`polozka_menu` desc
                        ";
    //echo $dotaz; and `typ_serial`.`nazev_typ_web`!=\"za_sportem\" 
}else{
 /*   $dotaz="SELECT distinct `nazev`,`popisek`,`popis_stravovani`,`popis_ubytovani`,`program_zajezdu`,`popis`,`cena_zahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`strava`,`doprava`,`ubytovani`,`serial`.`id_serial`
			FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
			where `do`>'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=0 and `typ_serial`.`nazev_typ_web`=\"".$typ_serialu."\" order by `serial`.`id_serial` ";
*/
}
$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
//$text.=mysqli_errno($GLOBALS["core"]->database->db_spojeni) . ": " . mysqli_error($GLOBALS["core"]->database->db_spojeni). "<br/>\n";
while($zaznam = mysqli_Fetch_Array($data)){
$text="";
        
            if($zaznam["nazev_ubytovani"]!=""){
                $nazev_ubytovani = xml_valid(strip_tags(str_replace("*", "", $zaznam["nazev_ubytovani"] )));
            }else{
            $nazev_ubytovani = "<title>".xml_valid(strip_tags(str_replace("*", "", $zaznam["nazev"] )))."</title>";
            $nazev_ubytovani = str_ireplace("- víkendový pobyt", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace(", víkendový pobyt", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace("- seniorský pobyt", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace(", seniorský pobyt", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace("- wellness pobyt", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace(", wellness pobyt", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace("- velikonoèní pobyt", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace(", velikonoèní pobyt", "", $nazev_ubytovani );    
            $nazev_ubytovani = str_ireplace("( dovolená )", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace("- letecky", "", $nazev_ubytovani );
            $nazev_ubytovani = str_ireplace(", letecky", "", $nazev_ubytovani );
            }
        
        $tourcat = config_export_sdovolena::$typy[$zaznam["nazev_typ_web"]];
	//typ ubytovani
        $strava = config_export_sdovolena::$strava[$zaznam["strava"]];

        if($zaznam["doprava"]==2 and $zaznam["nazev_typ_web"]=="pobytove-zajezdy"){
           $zaznam["doprava"]=1;
           $desc_doprava = "<desc>Pozn.: Za pøíplatek možnost autokarové dopravy.</desc>";
        }else if($zaznam["doprava"]==4 or $zaznam["doprava"]==5 ){
            $desc_doprava = "<desc>".doprava($zaznam["doprava"])."</desc>";
        }else{
           $desc_doprava = "";
        }
        $doprava = config_export_sdovolena::$doprava[$zaznam["doprava"]];
        $hotelnazev = config_export_sdovolena::$ubytovani_nazev[$zaznam["ubytovani"]];
	if($zaznam["ubytovani"]>=7 and $zaznam["ubytovani"]<=10){
		$hotelkat=config_export_sdovolena::$ubytovani[$zaznam["ubytovani"]];
	}else if($zaznam["ubytovani_kategorie"]){
                $hotelkat=config_export_sdovolena::$ubytovani_kategorie[$zaznam["ubytovani_kategorie"]];
        }else if($zaznam["kategorie"] and  $zaznam["id_sablony_zobrazeni"]==12){
                $hotelkat=config_export_sdovolena::$ubytovani_kategorie[$zaznam["kategorie"]];        
        }else{
		$hotelkat=config_export_sdovolena::$ubytovani["nespecifikováno"];
	}
        if(stripos( xml_valid(strip_tags($zaznam["popis"])), "Program zájezdu" )!==FALSE and $zaznam["nazev_typ_web"]=="poznavaci-zajezdy"){
            $zaznam["program_zajezdu"]=xml_valid(strip_tags($zaznam["popis"].$zaznam["program_zajezdu"]));
            $zaznam["program_zajezdu"]=str_ireplace("Program zájezdu:", ""  , $zaznam["program_zajezdu"]);
            $zaznam["program_zajezdu"]=str_ireplace("Program zájezdu", ""  , $zaznam["program_zajezdu"]);
            $zaznam["popis"]="";
        }else{
            $zaznam["program_zajezdu"]=xml_valid(strip_tags($zaznam["program_zajezdu"]));
        }

	$text.="<tour>
		<tour_id>".$zaznam["id_serial"]."</tour_id>
                <tour_type>".$tourcat."</tour_type>";
	//zemì
		$dotaz_zeme="SELECT * FROM `zeme_serial` natural join `zeme`  where `id_serial`=".$zaznam["id_serial"]."";
		$data_zeme = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zeme);
                $text.="<locations>";
		while($zaznam_zeme = mysqli_Fetch_Array($data_zeme)){
		//hledani kodu zeme
                    $locID = config_export_sdovolena::$zeme[$zaznam_zeme["nazev_zeme"]];
        	//destinace pro kazdou zemi
                    if($locID!=""){
                    $dotaz_destinace="SELECT * FROM `destinace_serial` natural join `destinace` where `id_serial`=".$zaznam["id_serial"]." and `destinace`.`id_zeme` = ".$zaznam_zeme["id_zeme"]." limit 1 ";
                    $data_destinace = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_destinace);
                    $dest=false;
                    while($zaznam_destinace = mysqli_Fetch_Array($data_destinace)){
                            $dest=true;
				$text.=
                                "<location>
                                    <country_id>".$locID."</country_id>
                                    <area_title>".$zaznam_destinace["nazev_destinace"]."</area_title>
                                </location>\n";
                    }//destinace
                    if(!$dest){
			$text.=
                          "<location>
                                <country_id>".$locID."</country_id>
                          </location>\n";
                    }
                    }
		}//zeme
                $text.="</locations>\n";
                if($zaznam["nazev_ubytovani"]!="" and $zaznam["id_sablony_zobrazeni"]==12){
                    $nazev_serialu = $nazev_ubytovani.", ".xml_valid(strip_tags(str_replace("*", "", $zaznam["nazev"] )));
                }else{
                    $nazev_serialu = xml_valid(strip_tags(str_replace("*", "", $zaznam["nazev"] )));
                }
                //pokud mame zajezd za sportem, pridame do tour description jeste vsechny nazvy zajezdu
                $text_terminy="";
                $first=1;
                if($zaznam["nazev_typ_web"]=="za-sportem"){
                    $dotaz_zajezd="SELECT * FROM `zajezd` where `id_serial`=".$zaznam["id_serial"]." and `od`>'".Date("Y-m-d")."' and `zajezd`.`nezobrazovat_zajezd`<>1 ";
                    $data_zajezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zajezd);
                    while ($row = mysqli_fetch_array($data_zajezd)) {
                        if($row["nazev_zajezdu"]!=""){
                            if($first){
                                $first=0;
                                $text_terminy.="Dostupné termíny:\n";
                            }
                           
                            $text_terminy.= $row["nazev_zajezdu"].",\n";
                        }
                    }
                }
                $text.="<tour_title>".xml_valid(strip_tags(str_replace("*", "", $nazev_serialu )))."</tour_title>
                    <tour_desc>".xml_valid(strip_tags($zaznam["popisek"]))."\n\n
                        ".xml_valid(strip_tags($zaznam["popis"])).$desc_doprava."\n\n".  xml_valid(strip_tags($text_terminy))."
                        </tour_desc>
                    <accommodation>
                        <type>".$hotelnazev."</type>
                        <class_id>".$hotelkat."</class_id>".$nazev_ubytovani;
                if(xml_valid(strip_tags($zaznam["popis_ubytovani"]))!=""){
                    $text.="
                    <accommodation_descs>
                        <accommodation_desc>
                            ".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."
                        </accommodation_desc>
                    </accommodation_descs>
                    ";
                }
                $text.="</accommodation>
                    ";
                if(xml_valid(strip_tags($zaznam["program_zajezdu"]))!=""){
                  $text.="
                   <program>
                    <tour_program>";
                  $array_programs = preg_split( "/([0-9][0-9]?.\s?\-\s?[0-9][0-9]?.\s?[Dd][Ee][Nn]\s?:?)|([0-9][0-9]?.\s?[Dd][Ee][Nn]\s?:?)/" , $zaznam["program_zajezdu"], -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                  //print_r($array_programs);
                  $i=0;
                  if(!preg_match("/([0-9][0-9]?.\s?\-\s?[0-9][0-9]?.\s?[Dd][Ee][Nn]\s?:?)|([0-9][0-9]?.\s?[Dd][Ee][Nn]\s?:?)/", $array_programs[0])){
                      $i++;
                  }
                  while(xml_valid(strip_tags($array_programs[$i]))!=""){
                    $text.="
                    <day>
                    <title>".xml_valid(strip_tags($array_programs[$i]))."</title>
                    <desc>
                     ".xml_valid(strip_tags($array_programs[$i+1]))."
                    </desc>
                    </day>"  ;
                    $i=$i+2;
                  }
                    $text.="
                    </tour_program>
                   </program>"  ;
                }

	
	$dotaz_foto="SELECT `foto`.* FROM `foto` 
            join `foto_serial` on (foto_serial.id_foto = foto.id_foto)
            where `foto_serial`.`id_serial`= ".$zaznam["id_serial"]."
            union distinct
            SELECT `foto`.* FROM `foto` 
            join `foto_objekty` on (foto_objekty.id_foto = foto.id_foto)
            join `objekt_serial` on (foto_objekty.id_objektu = objekt_serial.id_objektu)
            where `objekt_serial`.`id_serial`= ".$zaznam["id_serial"]."
            union distinct
            SELECT `foto`.* FROM `foto` 
            join `foto_objekt_kategorie` on (foto_objekt_kategorie.id_foto = foto.id_foto)
            join `objekt_kategorie` on (objekt_kategorie.id_objekt_kategorie = foto_objekt_kategorie.id_objekt_kategorie)
            join `objekt_serial` on (objekt_kategorie.id_objektu = objekt_serial.id_objektu)
            where `objekt_serial`.`id_serial`= ".$zaznam["id_serial"]."";
	$data_foto = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_foto);
	$text.="<photos>";
        $i=0;
	while($zaznam_foto = mysqli_Fetch_Array($data_foto)){
		$pos1 = (strpos($zaznam_foto["foto_url"], " ") or (strpos($zaznam_foto["foto_url"], "´"))  or (strpos($zaznam_foto["foto_url"], "è")) or (strpos($zaznam_foto["foto_url"], "ö"))  or (strpos($zaznam_foto["foto_url"], "ü")) );
		if ($pos1 === false) {

                $i++;
		$text.="
                    <photo>
                        <order>".$i."</order>
                        <url>".$adresa_ck."foto/full/".$zaznam_foto["foto_url"]."</url>
                        <desc>".xml_valid($zaznam_foto["nazev_foto"])."</desc>
                    </photo>\n";


		}
	}	//foto
        $text.="</photos>";

	$text.="
            <term_groups>
		<term_group>
                        <board>
                            <id>".$strava."</id>
                            <desc>
                                ".xml_valid(strip_tags($zaznam["popis_stravovani"]))."
                            </desc>
                        </board>
                        <transport>
                            <id>".$doprava."</id>
                              $desc_doprava  
                        </transport>
                        <dept_places>
              ";

        $dotaz_odjezd="SELECT * FROM `cena` where `id_serial`=".$zaznam["id_serial"]." and `typ_ceny`=5 ";
		$data_odjezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_odjezd);
                $mista=0;
		while($odjezd = mysqli_Fetch_Array($data_odjezd)){
                    $odjezd["nazev_ceny"] = str_ireplace("odjezdové místo","",$odjezd["nazev_ceny"]);
                    $odjezd["nazev_ceny"] = str_ireplace("odjezdové místa","",$odjezd["nazev_ceny"]);
                    $odjezd["nazev_ceny"] = str_ireplace("odjezdová místa","",$odjezd["nazev_ceny"]);
                    $odjezd["nazev_ceny"] = str_ireplace("odletová místa","",$odjezd["nazev_ceny"]);
                    $odjezd["nazev_ceny"] = str_ireplace("odletové místo","",$odjezd["nazev_ceny"]);
                    $odjezd["nazev_ceny"] = str_ireplace(" - ","",$odjezd["nazev_ceny"]);
                    $odjezd["nazev_ceny"] = str_ireplace(":","",$odjezd["nazev_ceny"]);
                    $mista=1;
                    $text.=" <place>".$odjezd["nazev_ceny"]."</place>\n";
                }
                if($mista==0){
                    $text.=" <place>Praha</place>\n";
                }
                
        $text_zahrnuje = "";
        $text_nezahrnuje = "";
        $zahrnuje_array = explode("<li", $zaznam["cena_zahrnuje"]);
        $nezahrnuje_array = explode("<li", $zaznam["cena_nezahrnuje"]);
        
        foreach ($zahrnuje_array as $key => $value) {
            $polozka = trim(xml_valid(strip_tags($value)));
            if($polozka!=""){
                $text_zahrnuje .= " <item>
                                        <desc>".$polozka."</desc>
                                    </item>\n";
            }
        }
        foreach ($nezahrnuje_array as $key => $value) {
            $polozka = trim(xml_valid(strip_tags($value)));
            if($polozka!=""){
                $text_nezahrnuje .= " <item>
                                        <desc>".$polozka."</desc>
                                    </item>\n";
            }
        }                                 
                
                if(trim(xml_valid(strip_tags($zaznam["cena_nezahrnuje"])))==""){
                    $price_excl="";
                }else{
                    $price_excl="
                        <price_excl>
                            ".$text_nezahrnuje."
                        </price_excl>";
                }
        $text.="
                        </dept_places>
                    <price_incl>
                        ".$text_zahrnuje."
                    </price_incl>
                    ".$price_excl."";
	//slevy
		$slevy="";
		$dotaz_slevy = "select * from `slevy` join
							`slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy`)
							where `slevy_serial`.`id_serial` = ".$zaznam["id_serial"]."
							and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" )
							order by `slevy`.`castka` desc limit 3";
		//echo $dotaz_slevy;
		$data_slevy = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_slevy);
		while($sleva = mysqli_Fetch_Array($data_slevy)){
			$slevy .= "
				<item><desc>".xml_valid($sleva["zkraceny_nazev"])." - ".xml_valid($sleva["nazev_slevy"]).", ".$sleva["castka"]." ".$sleva["mena"]."</desc></item>";
		}
		if($slevy!=""){
			$text.="
				<discounts>
					".$slevy ."
				</discounts>	";
		}

	$dotaz_zajezd="SELECT * FROM `zajezd` where `id_serial`=".$zaznam["id_serial"]." and `od`>'".Date("Y-m-d")."' and `zajezd`.`nezobrazovat_zajezd`<>1 ";
	$data_zajezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zajezd);
        $text.="<terms>\n";
	while($zaznam_zajezd = mysqli_Fetch_Array($data_zajezd)){
            
		$text.="<term>
                            <id>".$zaznam_zajezd["id_zajezd"]."</id>
                            <start>".$zaznam_zajezd["od"]."</start>
                            <end>".$zaznam_zajezd["do"]."</end>
                            <day_count>".(config_export_sdovolena::calculate_pocet_noci($zaznam_zajezd["od"], $zaznam_zajezd["do"])+1)."</day_count>
                            <night_count>".config_export_sdovolena::calculate_pocet_noci($zaznam_zajezd["od"], $zaznam_zajezd["do"])."</night_count>
				";
            $text.="<prices>";
		$dotaz_cena="SELECT * FROM `cena` natural join `cena_zajezd`  where `id_zajezd`=".$zaznam_zajezd["id_zajezd"]." and  `id_serial`=".$zaznam["id_serial"]." and `zakladni_cena` = 1 and `vyprodano` = 0 and `cena_zajezd`.`nezobrazovat`<>1";
		$data_cena = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_cena);
                $cena_exist=false;
		while($zaznam_cena = mysqli_Fetch_Array($data_cena)){
                    $cena_exist=true;
			$text.="
                            <price>
                                <desc_id>1</desc_id>
                                <final_price>".$zaznam_cena["castka"]."</final_price>
                            </price>\n";

		}//cena
            $text.="</prices>
                    <purchase_url>
                        ".$adresa_ck."zajezdy/zobrazit/".$zaznam["nazev_typ_web"]."/".$zaznam["nazev_zeme_web"]."/".$zaznam["nazev_web"]."/".$zaznam_zajezd["id_zajezd"]."
                    </purchase_url>
                </term>
                ";
	}//zajezd


	$text.="
                </terms>
		</term_group>
               </term_groups>
		";
	$text.="</tour>\n\n	";
        if($cena_exist){
            $final_text.=$text;
        }
}//serial

$final_text.="	</tours>
";

fwrite ($fp, $final_text);
fclose ($fp);
echo "vse vygenerovano OK<br/>";
echo "Vytvarim zaznam do databaze dokumentu...<br/>";
                        if($typ_serialu=="cele"){
                           $dotaz='SELECT `id_dokument` FROM `dokument` WHERE `dokument_url`="'.$adresa_ck.'sdovolena/'.$typ_serialu.'.xml"';
                        }else{
                            $dotaz='SELECT `id_dokument` FROM `dokument` WHERE `dokument_url`="'.$adresa_ck.'sdovolena/xml-'.$typ_serialu.'.xml"';
                        }
                        //echo $dotaz;
			$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
			$zaznam = mysqli_Fetch_Array($data);
			if(mysqli_Num_Rows($data)==0){
                            if($typ_serialu=="cele"){
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'INSERT INTO `dokument` (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`) VALUES("'.Date("Y-m-d").'"," Sdovolena XML dokument '.$typ_serialu.'","datum vytvoreni:'.Date("Y-m-d").'","'.$adresa_ck.'sdovolena/'.$typ_serialu.'.xml") ');
                            }else{
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'INSERT INTO `dokument` (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`) VALUES("'.Date("Y-m-d").'"," Sdovolena XML dokument '.$typ_serialu.'","datum vytvoreni:'.Date("Y-m-d").'","'.$adresa_ck.'sdovolena/xml-'.$typ_serialu.'.xml") ');
                            }
				$autoid=mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
				if ($spravne){
					?><h4 style="color:green">dokument byl uspesne vytvoren</h4><?php
					}else{
					?><h4 style="color:black">vytvoreni dokumentu se nepovedlo (xml bylo vygenerovano, ale nebylo ulozeno v databazi)</h4><?php
				}
			}else{
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'update `dokument` set  `popisek_dokument`="datum vytvoreni:'.Date("Y-m-d H:i:s").'", `datum_vytvoreni`="'.Date("Y-m-d").'" where `id_dokument`='.$zaznam["id_dokument"].' ');
					?><h4 style="color:green">dokument jiz existuje (drivejsi verze)</h4><?php
			}

}//of else fopen
}//of function generate_xml



function generate_xml_invia($typ_serialu,$adresa_ck){
//-------------------------------------------------pole zemi--------------------------------------------------------
$zeme = array( 
"AF" => "Afghánistán",
"AX" => "A*landy",
"AL" => "Albánie",
"DZ" => "Alžírsko",
"AS" => "Americká Samoa",
"VI" => "Americké Panenské ostrovy",
"AD" => "Andorra",
"AO" => "Angola",
"AI" => "Anguilla",
"AQ" => "Antarktida",
"AG" => "Antigua a Barbuda",
"AR" => "Argentina",
"AM" => "Arménie",
"AW" => "Aruba",
"AU" => "Austrálie",
"AZ" => "Ázerbájdžán",
"BS" => "Bahamy",
"BH" => "Bahrajn",
"BD" => "Bangladéš",
"BB" => "Barbados",
"BE" => "Belgie",
"BZ" => "Belize",
"BY" => "Bìlorusko",
"BJ" => "Benin",
"BM" => "Bermudy",
"BT" => "Bhútán",
"BO" => "Bolívie",
"BA" => "Bosna a Hercegovina",
"BW" => "Botswana",
"BV" => "Bouvetùv ostrov",
"BR" => "Brazílie",
"IO" => "Britské indickooceánské území",
"VG" => "Britské Panenské ostrovy",
"BN" => "Brunej",
"BG" => "Bulharsko",
"BF" => "Burkina Faso",
"BI" => "Burundi",
"CK" => "Cookovy ostrovy",
"TD" => "Èad",
"ME" => "Èerná Hora",
"CZ" => "Èesko",
"CZ" => "Èeská Republika",
"CZ" => "Èeská republika, víkendové pobyty",
"CZ" => "Èeská republika",
"CN" => "Èína",
"DK" => "Dánsko",
"CD" => "Demokratická republika Kongo",
"DM" => "Dominika",
"DO" => "Dominikánská republika",
"DJ" => "Džibutsko",
"EG" => "Egypt",
"EC" => "Ekvádor",
"ER" => "Eritrea",
"EE" => "Estonsko",
"ET" => "Etiopie",
"FO" => "Faerské ostrovy",
"FK" => "Falklandy (Malvíny)",
"FJ" => "Fidži",
"PH" => "Filipíny",
"FI" => "Finsko",
"FR" => "Francie",
"GF" => "Francouzská Guyana",
"TF" => "Francouzská jižní a antarktická území",
"PF" => "Francouzská Polynésie",
"GA" => "Gabon",
"GM" => "Gambie",
"GH" => "Ghana",
"GI" => "Gibraltar",
"GD" => "Grenada",
"GL" => "Grónsko",
"GE" => "Gruzie",
"GP" => "Guadeloupe",
"GU" => "Guam",
"GT" => "Guatemala",
"GN" => "Guinea",
"GW" => "Guinea-Bissau",
"GG" => "Guernsey",
"GY" => "Guyana",
"HT" => "Haiti",
"HM" => "Heardùv ostrov a McDonaldovy ostrovy",
"HN" => "Honduras",
"HK" => "Hongkong",
"CL" => "Chile",
"HR" => "Chorvatsko",
"IN" => "Indie",
"ID" => "Indonésie",
"IQ" => "Irák",
"IR" => "Írán",
"IE" => "Irsko",
"IS" => "Island",
"IT" => "Itálie",
"IL" => "Izrael",
"JM" => "Jamajka",
"JP" => "Japonsko",
"YE" => "Jemen",
"JE" => "Jersey",
"ZA" => "Jihoafrická republika",
"GS" => "Jižní Georgie a Jižní Sandwichovy ostrovy",
"KR" => "Jižní Korea",
"JO" => "Jordánsko",
"KY" => "Kajmanské ostrovy",
"KH" => "Kambodža",
"CM" => "Kamerun",
"CA" => "Kanada",
"CV" => "Kapverdy",
"QA" => "Katar",
"KZ" => "Kazachstán",
"KE" => "Keòa",
"KI" => "Kiribati",
"CC" => "Kokosové ostrovy",
"CO" => "Kolumbie",
"KM" => "Komory",
"CG" => "Kongo",
"CR" => "Kostarika",
"CU" => "Kuba",
"KW" => "Kuvajt",
"CY" => "Kypr",
"KG" => "Kyrgyzstán",
"LA" => "Laos",
"LS" => "Lesotho",
"LB" => "Libanon",
"LR" => "Libérie",
"LY" => "Libye",
"LI" => "Lichtenštejnsko",
"LT" => "Litva",
"LV" => "Lotyšsko",
"LU" => "Lucembursko",
"MO" => "Macao",
"MG" => "Madagaskar",
"HU" => "Maïarsko",
"MK" => "Makedonie",
"MY" => "Malajsie",
"MW" => "Malawi",
"MV" => "Maledivy",
"ML" => "Mali",
"MT" => "Malta",
"IM" => "Ostrov Man",
"MA" => "Maroko",
"MH" => "Marshallovy ostrovy",
"MQ" => "Martinik",
"MU" => "Mauricius",
"MR" => "Mauritánie",
"YT" => "Mayotte",
"UM" => "Menší odlehlé ostrovy USA",
"MX" => "Mexiko",
"FM" => "Mikronésie",
"MD" => "Moldavsko",
"MC" => "Monako",
"MN" => "Mongolsko",
"MS" => "Montserrat",
"MZ" => "Mosambik",
"MM" => "Myanmar",
"NA" => "Namibie",
"NR" => "Nauru",
"DE" => "Nìmecko",
"DE" => "NÌMECKO",
"NP" => "Nepál",
"NE" => "Niger",
"NG" => "Nigérie",
"NI" => "Nikaragua",
"NU" => "Niue",
"AN" => "Nizozemské Antily",
"NL" => "Nizozemsko",
"NF" => "Norfolk",
"NO" => "Norsko",
"NC" => "Nová Kaledonie",
"NZ" => "Nový Zéland",
"OM" => "Omán",
"PK" => "Pákistán",
"PW" => "Palau",
"PS" => "Palestinská autonomie",
"PA" => "Panama",
"PG" => "Papua-Nová Guinea",
"PY" => "Paraguay",
"PE" => "Peru",
"PN" => "Pitcairnovy ostrovy",
"CI" => "Pobøeží slonoviny",
"PL" => "Polsko",
"PR" => "Portoriko",
"PT" => "Portugalsko",
"AT" => "Rakousko",
"RE" => "Réunion",
"GQ" => "Rovníková Guinea",
"RO" => "Rumunsko",
"RU" => "Rusko",
"RW" => "Rwanda",
"GR" => "Øecko",
"BL" => "Saint-Barthélemy",
"MF" => "Saint-Martin",
"PM" => "Saint-Pierre a Miquelon",
"SV" => "Salvador",
"WS" => "Samoa",
"SM" => "San Marino",
"SA" => "Saúdská Arábie",
"SN" => "Senegal",
"KP" => "Severní Korea",
"MP" => "Severní Mariany",
"SC" => "Seychely",
"SL" => "Sierra Leone",
"SG" => "Singapur",
"SK" => "Slovensko",
"SI" => "Slovinsko",
"SO" => "Somálsko",
"AE" => "Spojené arabské emiráty",
"GB1" => "Spojené království",
"GB11" => "Anglie",
"GB111" => "Skotsko",
"GB1111" => "Velká Británie",    
"US" => "Spojené státy americké",
"US1" => "USA",
"RS" => "Srbsko",
"CF" => "Støedoafrická republika",
"SD" => "Súdán",
"SR" => "Surinam",
"SH" => "Svatá Helena",
"LC" => "Svatá Lucie",
"KN" => "Svatý Kryštof a Nevis",
"ST" => "Svatý Tomáš a Princùv ostrov",
"VC" => "Svatý Vincenc a Grenadiny",
"SZ" => "Svazijsko",
"SY" => "Sýrie",
"SB" => "Šalamounovy ostrovy",
"ES" => "Španìlsko",
"SJ" => "Špicberky a Jan Mayen",
"LK" => "Šrí Lanka",
"SE" => "Švédsko",
"CH" => "Švýcarsko",
"TJ" => "Tádžikistán",
"TZ" => "Tanzanie",
"TH" => "Thajsko",
"TW" => "Tchaj-wan",
"TG" => "Togo",
"TK" => "Tokelau",
"TO" => "Tonga",
"TT" => "Trinidad a Tobago",
"TN" => "Tunisko",
"TR" => "Turecko",
"TM" => "Turkmenistán",
"TC" => "Turks a Caicos",
"TV" => "Tuvalu",
"UG" => "Uganda",
"UA" => "Ukrajina",
"UY" => "Uruguay",
"UZ" => "Uzbekistán",
"CX" => "Vánoèní ostrov",
"VU" => "Vanuatu",
"VA" => "Vatikán",
"VE" => "Venezuela",
"VN" => "Vietnam",
"TL" => "Východní Timor",
"WF" => "Wallis a Futuna",
"ZM" => "Zambie",
"EH" => "Západní Sahara",
"ZW" => "Zimbabwe"
);
//------------------------------------------------------------------------------------------------

if($typ_serialu!="cele"){
    $fp = fopen ("../invia/xml-".$typ_serialu."-".Date("Y-m-d").".xml","w"); //otevru podle parametru
}else{
    $fp = fopen ("../invia/xml-".$typ_serialu.".xml","w"); //otevru podle parametru
}

if(!$fp){
echo "<h4 style=\"color:black\">error, nepodarilo se otevrit soubor!!!</h4>";
}else{

//echo "podarilo se otevrit/vytvorit soubor <i>\"invia/xml-".$typ_serialu."(-".Date("Y-m-d").").xml \"</i><br/> ";

//hlavicka
$text="<?xml version=\"1.0\" encoding=\"windows-1250\" ?>

		<zajezdy>\n";

                                
if($typ_serialu=="cele"){
    
    $dotaz="SELECT distinct  `serial`.`nazev`,`serial`.`dlouhodobe_zajezdy`,`serial`.`popisek`,`serial`.`popis_stravovani`,`serial`.`popis_ubytovani`,`serial`.`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`cena_nezahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`nazev_typ_web`,`strava`,`doprava`,`ubytovani`,`ubytovani_kategorie`,`serial`.`id_serial`,`serial`.`id_sablony_zobrazeni` , 
			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`posX` as latitude, `objekt_ubytovani`.`posY` as longitude,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`, min(`cena_zajezd`.`vyprodano`) as `vyprodano`
                        FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`) join
                                 `zeme_serial` on (`serial`.`id_serial` = `zeme_serial`.`id_serial`) join   
                                 `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`) join
                                 `cena_zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`)
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                            
			where `zeme`.`geograficka_zeme`=1 and (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) 
                             and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `cena_zajezd`.`nezobrazovat`<>1  and `serial`.`id_sablony_zobrazeni` != 8  
                            group by `serial`.`id_serial`   
                            having `vyprodano` = 0
                            order by `serial`.`id_serial` , `zeme_serial`.`zakladni_zeme` desc,`zeme_serial`.`polozka_menu` desc
                            ";
}else{
    $dotaz="SELECT distinct `serial`.`nazev`,`dlouhodobe_zajezdy`,`serial`.`popisek`,`popis_stravovani`,`serial`.`popis_ubytovani`,`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`cena_nezahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`nazev_typ_web`,`strava`,`doprava`,`ubytovani`,`ubytovani_kategorie`,`serial`.`id_serial`,
 			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`
			FROM `serial` join 
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`) join
                                  `zeme_serial` on (`serial`.`id_serial` = `zeme_serial`.`id_serial`) join   
                                 `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`)                                
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                                            
			where `zeme`.`geograficka_zeme`=1 and (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) and `typ_serial`.`nazev_typ_web`=\"".$typ_serialu."\" 
                          and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  and `serial`.`id_sablony_zobrazeni` != 8                            
                            group by `serial`.`id_serial`     
                            order by `serial`.`id_serial` , `zeme_serial`.`zakladni_zeme` desc,`zeme_serial`.`polozka_menu` desc ";
}


$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
//$text.=mysqli_errno($GLOBALS["core"]->database->db_spojeni) . ": " . mysqli_error($GLOBALS["core"]->database->db_spojeni). "<br/>\n";
while($zaznam = mysqli_Fetch_Array($data)){

	//typ zajezdu
	if($zaznam["id_typ"] == 1 or $zaznam["id_typ"] == 7 or $zaznam["id_typ"] == 8){
		$tourcat="pobytove";
        }else if($zaznam["id_typ"] == 3 ){
                $tourcat="lazensky";
	}else if($zaznam["id_typ"] == 2 or $zaznam["id_typ"] == 6  or $zaznam["id_typ"] == 31){
		$tourcat="poznavaci";	
	}else if($zaznam["id_typ"] == 5){
		$tourcat="lyzarske";	
	}else if($zaznam["id_typ"] == 4 ){
		$tourcat="za-sportem";	
                $dotaz_sport = "
                    SELECT distinct  `zeme`.`nazev_zeme`, `zeme`.`nazev_zeme_web`
			FROM `zeme_serial`  join   
                             `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`) 
     
			where `zeme`.`geograficka_zeme`=0 and `zeme_serial`.`id_serial`=".$zaznam["id_serial"]."   
                        order by `zeme_serial`.`zakladni_zeme` desc limit 1                  
                    ";
                //echo $dotaz_sport;
                $data_sport = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_sport);
                while ($row_sport = mysqli_fetch_array($data_sport)) {
                    $tourcat=$row_sport["nazev_zeme_web"];	
                }
	}else if($zaznam["id_typ"] == 29){
		$tourcat="eurovikend";
        }else if($zaznam["id_typ"] == 30){
		$tourcat="poznavaci";
        }else{
		$tourcat="jine";
                
        }
	//typ ubytovani
	if($zaznam["ubytovani"]==7){
		$hotelkat="hotel_kat=\"2\"";
	}else if($zaznam["ubytovani"]==8){
		$hotelkat="hotel_kat=\"3\"";
	}else if($zaznam["ubytovani"]==9){
		$hotelkat="hotel_kat=\"4\"";
	}else if($zaznam["ubytovani"]==10){
		$hotelkat="hotel_kat=\"5\"";
	}else{
            if($zaznam["ubytovani_kategorie"]!=0){
                $hotelkat="hotel_kat=\"".$zaznam["ubytovani_kategorie"]."\"";
            }else if($zaznam["kategorie"]!=0 and  $zaznam["id_sablony_zobrazeni"]==12){
                $hotelkat="hotel_kat=\"".$zaznam["kategorie"]."\"";
            }else{
                $hotelkat="hotel_kat=\"1\"";
            }		
	}
	if($tourcat=="pobytove" or $tourcat=="lyzarske" or $tourcat=="lazensky"){
            if($zaznam["nazev_ubytovani"]!="" and $zaznam["id_sablony_zobrazeni"]==12){
                $hotel = "<hotel ".$hotelkat.">".xml_valid(strip_tags($zaznam["nazev_ubytovani"]))."</hotel>\n";
            }else{
                $hotel = "<hotel ".$hotelkat.">".xml_valid(strip_tags($zaznam["nazev"]))."</hotel>\n";
            }

	}else{
		$hotel = "<hotel ".$hotelkat."/>\n";
	}
        if($zaznam["nazev_ubytovani"]!="" and $zaznam["id_sablony_zobrazeni"]==12){
            $tour_title = xml_valid(strip_tags($zaznam["nazev_ubytovani"].", ".$zaznam["nazev"]));
        }else{
            $tour_title = xml_valid(strip_tags($zaznam["nazev"]));  
        }
        
        
    if($zaznam["latitude"] != "" and $zaznam["longitude"] != ""){
          $latlong =  " latitude=\"".$zaznam["latitude"]."\" longitude=\"".$zaznam["longitude"]."\" " ;
    } else{
          $latlong = "";
    }   
    
	$text.="<zajezd >
		<tour_id>".$zaznam["id_serial"]."</tour_id>
		<tour_title>".$tour_title."</tour_title>\n
		<tour_cat  name=\"".$tourcat."\"/>

		<ubytovani name=\"".ubytovani($zaznam["ubytovani"])."\"".$latlong."></ubytovani>
		".$hotel."";
		
	//zemì
		$dotaz_zeme="SELECT * FROM `zeme_serial` natural join `zeme`  where `zeme`.`geograficka_zeme`=1 and `id_serial`=".$zaznam["id_serial"]."";
		$data_zeme = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zeme);
		while($zaznam_zeme = mysqli_Fetch_Array($data_zeme)){
		//hledani kodu zeme
				$key = array_search($zaznam_zeme["nazev_zeme"], $zeme); 
                                $key = str_replace("1", "", $key);
				$text.="<zeme id=\"".$key."\">".$zaznam_zeme["nazev_zeme"]."</zeme>\n";
		}//zeme

                 //pridame i informace o sportech
                 if($zaznam["nazev_typ_web"]=="za-sportem"){
                    $dotaz_zeme2="SELECT * FROM `zeme_serial` join   
                                 `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`)
                                  where `id_serial`=".$zaznam["id_serial"]." and `zakladni_zeme` = 1 and `geograficka_zeme` = 0 ";
                    $data_zeme2 = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zeme2);
                    while ($row = mysqli_fetch_array($data_zeme2)) {
                        $text.="<zeme>".$row["nazev_zeme"]."</zeme>\n";
                    }
                }
                
	//destinace pro kazdou zemi
		$dotaz_destinace="SELECT * FROM `destinace_serial` natural join `destinace` where `id_serial`=".$zaznam["id_serial"]." limit 1 ";
		$data_destinace = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_destinace);
		while($zaznam_destinace = mysqli_Fetch_Array($data_destinace)){
				$text.="<misto>".$zaznam_destinace["nazev_destinace"]."</misto>\n";		
		}//destinace
                //
                //pokud mame zajezd za sportem, pridame do tour description jeste vsechny nazvy zajezdu
                $text_terminy="";
                $first=1;
                if($zaznam["nazev_typ_web"]=="za-sportem"){
                    $dotaz_zajezd="SELECT * FROM `zajezd` where `id_serial`=".$zaznam["id_serial"]." and `od`>'".Date("Y-m-d")."' and `zajezd`.`nezobrazovat_zajezd`<>1 ";
                    $data_zajezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zajezd);
                    while ($row = mysqli_fetch_array($data_zajezd)) {
                        if($row["nazev_zajezdu"]!=""){
                            if($first){
                                $first=0;
                                $text_terminy.="Dostupné termíny:\n";
                            }
                           
                            $text_terminy.= $row["nazev_zajezdu"]."\n";
                        }
                    }
                }		
		
	//rozdeleni popisu a programu dle typu zajezdu
    $popis_poznamka = "";
    $popis_poznamka_ubyt = "";
    if($zaznam["poznamky"]!="")	{
             $popis_poznamka =  "<popis name=\"Poznámka\">".xml_valid(strip_tags($zaznam["poznamky"]))."</popis>\n";
    }    
    if($zaznam["poznamka_ubytovani"]!="")	{
             $popis_poznamka_ubyt =  "<popis name=\"Poznámka k ubytování\">".xml_valid(strip_tags($zaznam["poznamka_ubytovani"]))."</popis>\n";
    }        
	if($tourcat=="poznavaci"){
		if($zaznam["program_zajezdu"]!=""){
		$program = "
			<program>
				<den prvni=\"Program zájezdu\" >".xml_valid(strip_tags(str_replace("Program zájezdu", "", $zaznam["program_zajezdu"])))."</den>\n
			</program>	";	
            

		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]))."</popis>\n
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popis"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
                ".$popis_poznamka.$popis_poznamka_ubyt."
			</popisy>		
		";		
		}else{
		$program = "
			<program>
				<den prvni=\"Program zájezdu\" >".xml_valid(strip_tags( str_replace("Program zájezdu", "", $zaznam["popis"])))."</den>\n
			</program>	";		
		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
                ".$popis_poznamka.$popis_poznamka_ubyt."
			</popisy>		
		";				
		}

	}else{
		$program = "";
		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]))."</popis>\n
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popis"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
                ".$popis_poznamka.$popis_poznamka_ubyt."
			</popisy>		
		";				
	}
	$text.="
		".$popisy.$program."
		";
		
		 
		
	$dotaz_foto="(SELECT `foto`.*, foto_serial.zakladni_foto as zakladni_foto 
            FROM `foto` 
            join `foto_serial` on (foto_serial.id_foto = foto.id_foto)
            where `foto_serial`.`id_serial`= ".$zaznam["id_serial"]." )
            union distinct
            (SELECT `foto`.*, foto_objekty.zakladni_foto as zakladni_foto 
            FROM `foto` 
            join `foto_objekty` on (foto_objekty.id_foto = foto.id_foto)
            join `objekt_serial` on (foto_objekty.id_objektu = objekt_serial.id_objektu)
            where `objekt_serial`.`id_serial`= ".$zaznam["id_serial"]."    )
            union distinct
            (SELECT `foto`.*, foto_objekt_kategorie.zakladni_foto as zakladni_foto 
            FROM `foto` 
            join `foto_objekt_kategorie` on (foto_objekt_kategorie.id_foto = foto.id_foto)
            join `objekt_kategorie` on (objekt_kategorie.id_objekt_kategorie = foto_objekt_kategorie.id_objekt_kategorie)
            join `objekt_serial` on (objekt_kategorie.id_objektu = objekt_serial.id_objektu)
            where `objekt_serial`.`id_serial`= ".$zaznam["id_serial"].")
            
            order by zakladni_foto desc
            
            ";
	$data_foto = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_foto);
	$first=1;
	while($zaznam_foto = mysqli_Fetch_Array($data_foto)){
				$pos1 = (strpos($zaznam_foto["foto_url"], " ") or (strpos($zaznam_foto["foto_url"], "´"))  or (strpos($zaznam_foto["foto_url"], "è")) or (strpos($zaznam_foto["foto_url"], "ö"))  or (strpos($zaznam_foto["foto_url"], "ü")) );
				if ($pos1 === false) {
					if($first){
						$text.="
						<fotky>
						";
						$first=0;
					}
					$text.="<fotka url=\"".$adresa_ck."foto/full/".$zaznam_foto["foto_url"]."\" zakladni=\"".$zaznam_foto["zakladni_foto"]."\">".xml_valid($zaznam_foto["nazev_foto"])."</fotka>\n";
				}
	}	//foto
	if(!$first){
		$text.="
		</fotky>
		";
		$first=0;
	}
	if($zaznam["dlouhodobe_zajezdy"]==1){
		$dl_zaj=" dlouhe_terminy=\"1\"";
	}else{
		$dl_zaj=" dlouhe_terminy=\"0\"";
	}
   
        $text_zahrnuje = "";
        $text_nezahrnuje = "";
        $zahrnuje_array = explode("<li", $zaznam["cena_zahrnuje"]);
        $nezahrnuje_array = explode("<li", $zaznam["cena_nezahrnuje"]);
        
        foreach ($zahrnuje_array as $key => $value) {
            $polozka = trim(xml_valid(strip_tags($value)));
            if($polozka!=""){
                $text_zahrnuje .= "<polozka>".$polozka."</polozka>\n";
            }
        }
        foreach ($nezahrnuje_array as $key => $value) {
            $polozka = trim(xml_valid(strip_tags($value)));
            if($polozka!=""){
                $text_nezahrnuje .= "<polozka>".$polozka."</polozka>\n";
            }
        }
        $odlety="";
        $odlet="";
        $prilet="";
        if($zaznam["doprava"]==3){
            $use_default=true;
            $query = "select distinct odjezdove_misto, kod_letiste from cena 
                where 
                    ((odjezdove_misto!=\"\" and odjezdove_misto is not null) or (kod_letiste!=\"\" and kod_letiste is not null) )
                    and id_serial=".$zaznam["id_serial"]."";
            $data_odlet = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
            while ($row_odlet = mysqli_fetch_array($data_odlet)) {
                $use_default=false;
                $odlet.="\n<odlet odlet_id=\"".$row_odlet["kod_letiste"]."\">".$row_odlet["odjezdove_misto"]."</odlet>";
                $prilet.="\n<prilet odlet_id=\"".$row_odlet["kod_letiste"]."\">".$row_odlet["odjezdove_misto"]."</prilet>";
            }
            
            if($use_default){
                $odlety="
                    <odlety>
                        <odlet odlet_id=\"PRG\">Praha</odlet>
                    </odlety>
                    <prilety>
                        <prilet odlet_id=\"PRG\">Praha</prilet>
                    </prilety>";
            }else{
                $odlety="
                    <odlety>
                        $odlet
                    </odlety>
                    <prilety>
                        $prilet
                    </prilety>                    
                    ";
            }
        }
        
	$text.="
		<term_group ".$dl_zaj.">
			<strava name=\"".strava($zaznam["strava"])."\"></strava>
			<doprava name=\"".doprava($zaznam["doprava"])."\"></doprava>
                           ".$odlety." 


			<zahrnuje>\n".$text_zahrnuje."</zahrnuje>
                        <nezahrnuje>\n".$text_nezahrnuje."</nezahrnuje>        			
	";

	//slevy
		$slevy="";
		$dotaz_slevy = "select * from `slevy` join
							`slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy`)
							where `slevy_serial`.`id_serial` = ".$zaznam["id_serial"]." 
							and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
							order by `slevy`.`castka` desc limit 3";
		//echo $dotaz_slevy;
		$data_slevy = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_slevy);
		while($sleva = mysqli_Fetch_Array($data_slevy)){
			if($sleva["mena"]=="Kè"){
				$sleva["mena"]="CZK";
			}
			$slevy .= "
				<polozka cena=\"".$sleva["castka"]."\" mena=\"".$sleva["mena"]."\" >".xml_valid($sleva["zkraceny_nazev"])." - ".xml_valid($sleva["nazev_slevy"])."</polozka>";
		}	
		if($slevy!=""){
			$text.="
				<slevy>
					".$slevy ."
				</slevy>	";		
		}

	
	
    $dotaz="SELECT distinct  `serial`.`nazev`,`serial`.`dlouhodobe_zajezdy`,`serial`.`popisek`,`serial`.`popis_stravovani`,`serial`.`popis_ubytovani`,`serial`.`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`cena_nezahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`nazev_typ_web`,`strava`,`doprava`,`ubytovani`,`ubytovani_kategorie`,`serial`.`id_serial`,`serial`.`id_sablony_zobrazeni` , 
			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`, min(`cena_zajezd`.`vyprodano`) as `vyprodano`
                        FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`) join
                                 `zeme_serial` on (`serial`.`id_serial` = `zeme_serial`.`id_serial`) join   
                                 `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`) join
                                 `cena_zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`)
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                            
			where `zeme`.`geograficka_zeme`=1 and (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) 
                             and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `cena_zajezd`.`nezobrazovat`<>1 and `serial`.`id_sablony_zobrazeni` != 8  
                            group by `serial`.`id_serial`   
                            having `vyprodano` = 0
                            order by `serial`.`id_serial` , `zeme_serial`.`zakladni_zeme` desc,`zeme_serial`.`polozka_menu` desc
                            ";                
                
	$dotaz_zajezd="SELECT `zajezd`.*, min(`cena_zajezd`.`vyprodano`) as `vyprodano` FROM `zajezd` join
                                 `cena_zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                 where `id_serial`=".$zaznam["id_serial"]." and `do`>'".Date("Y-m-d")."' and `zajezd`.`nezobrazovat_zajezd`<>1 and `cena_zajezd`.`nezobrazovat`<>1
                                 group by   `zajezd`.`id_zajezd`  
                                 having `vyprodano` = 0";
	$data_zajezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zajezd);
	while($zaznam_zajezd = mysqli_Fetch_Array($data_zajezd)){
                $text.="<termin id=\"".$zaznam_zajezd["id_zajezd"]."\"> 
					<d_start>".$zaznam_zajezd["od"]."</d_start>
					<d_konec>".$zaznam_zajezd["do"]."</d_konec>
				";

		$dotaz_cena="SELECT * FROM `cena` natural join `cena_zajezd`  where `id_zajezd`=".$zaznam_zajezd["id_zajezd"]." and  `id_serial`=".$zaznam["id_serial"]." and `cena_zajezd`.`nezobrazovat`<>1";
		$data_cena = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_cena);
                $cena_od = 0;
                $zakladni_cena = 0;
                $cena_od_existuje = false;
                $ceny="";
                
		while($zaznam_cena = mysqli_Fetch_Array($data_cena)){
                        if($zaznam_cena["zakladni_cena"]==1){
                            $zakladni_cena = $zaznam_cena["castka"];
                        }
                        if(($zaznam_cena["typ_ceny"]==1 or $zaznam_cena["typ_ceny"]==2) 
                                and ($zaznam_cena["poradi_ceny"]<150 or ($zaznam_cena["poradi_ceny"]>=200 and $zaznam_cena["poradi_ceny"]<300 ))
                                and stripos($zaznam_cena["nazev_ceny"], "dítì")===false
                                and stripos($zaznam_cena["nazev_ceny"], "pøíplatek")===false
                                and stripos($zaznam_cena["nazev_ceny"], "pøistýl")===false
                                and stripos($zaznam_cena["nazev_ceny"], "pøístýl")===false
                                and stripos($zaznam_cena["nazev_ceny"], "víza")===false
                                and stripos($zaznam_cena["nazev_ceny"], "vízum")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3. dosp")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4. dosp")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "5. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "5. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "5.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "5 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "6. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "6. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "6.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "6 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "7. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "7. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "7.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "7 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "8. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "8. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "8.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "8 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "senior")===false
                                and stripos($zaznam_cena["nazev_ceny"], "junior")===false
                                and stripos($zaznam_cena["nazev_ceny"], "doprava")===false
                                and $zaznam_cena["castka"] >0){
                            //jedna se o relevantni sluzbu
                            if($cena_od_existuje == false){
                                //vezmu libovolnou cenu
                                $cena_od_existuje = true;
                                $cena_od = $zaznam_cena["castka"];
                            }else if($cena_od > $zaznam_cena["castka"] ){
                                $cena_od = $zaznam_cena["castka"];
                            }
                        }
                        if($zaznam_cena["vyprodano"]=="1"){
                           $vyprodano = "vyprodano=\"1\""; 
                        }else{
                            $vyprodano = "vyprodano=\"0\"";
                        }    
                        if($zaznam_cena["typ_ceny"]=="4"){
                           $priplatek = " priplatek=\"1\" "; 
                        }else{
                            $priplatek = "";
                        }  
                        
			$ceny.="
					<cena cena=\"".$zaznam_cena["castka"]."\" $vyprodano $priplatek mena=\"CZK\" zakladni_cena=\"".$zaznam_cena["zakladni_cena"]."\">".xml_valid($zaznam_cena["nazev_ceny"])."</cena>
					";                        

						
		}//cena
                if($zakladni_cena > 0){
                    $cena_od = $zakladni_cena;
                }
                if ($cena_od_existuje) {
                    $text.= "<cena_od>" . $cena_od . "</cena_od>";
                    if ($zaznam_zajezd["cena_pred_akci"] != "" and $zaznam_zajezd["akcni_cena"] == $cena_od) {
                        $text.= "<konecna_cena>" . $zaznam_zajezd["akcni_cena"] . "</konecna_cena>";
                        $text.= "<puvodni_cena>" . $zaznam_zajezd["cena_pred_akci"] . "</puvodni_cena>";
                    } else {
                        $text.= "<konecna_cena>" . $cena_od . "</konecna_cena>";
                        $text.= "<puvodni_cena>" . $cena_od . "</puvodni_cena>";
                    }
                }else{
                   $text.= "<cena_od>" . $zakladni_cena . "</cena_od>"; 
                   $text.= "<konecna_cena>" . $zakladni_cena . "</konecna_cena>";
                   $text.= "<puvodni_cena>" . $zakladni_cena . "</puvodni_cena>";
                }
		$text.=$ceny."</termin>";
                if($cena_od < $zakladni_cena){
                  //  echo $cena_od."; ".$zakladni_cena ."; ".$zaznam["nazev_ubytovani"]." ".$zaznam["nazev"]."\n<br/>";
                }
	}//zajezd		
		
		
		
		
	$text.="
		</term_group>
		";



	$text.="</zajezd>\n\n	";
}//serial

$text.="	</zajezdy>
";

fwrite ($fp, $text);
fclose ($fp);
//echo "vse vygenerovano OK<br/>";
//echo "Vytvarim zaznam do databaze dokumentu...<br/>";

if($typ_serialu=="cele"){
    			$dotaz='SELECT `id_dokument` FROM `dokument` WHERE `dokument_url`="'.$adresa_ck.'invia/xml-'.$typ_serialu.'.xml"';
				//echo $dotaz;
			$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
			$zaznam = mysqli_Fetch_Array($data);
			if($zaznam["id_dokument"]==0){				
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'INSERT INTO `dokument` (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`) VALUES("'.Date("Y-m-d").'"," INVIA XML dokument '.$typ_serialu.' ","datum vytvoreni:'.Date("Y-m-d H:i:s").'","'.$adresa_ck.'invia/xml-'.$typ_serialu.'.xml") 
                                    ');
				$autoid=mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
				if ($spravne){
					?><h4 style="color:green">dokument byl uspesne vytvoren</h4><?php
					}else{
					?><h4 style="color:black">vytvoreni dokumentu se nepovedlo (xml bylo vygenerovano, ale nebylo ulozeno v databazi)</h4><?php
				}
			}else{
                            $update_dotaz = 'UPDATE `dokument` set `datum_vytvoreni` = "'.Date("Y-m-d").'", `popisek_dokument` = "datum vytvoreni:'.Date("Y-m-d H:i:s").'" where
                                            `id_dokument` =  '.$zaznam["id_dokument"].'  ';
                            $spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,$update_dotaz);
                            echo mysqli_error($GLOBALS["core"]->database->db_spojeni);
				
					?><h4 style="color:green">dokument jiz existuje, proveden update </h4><?php
			}
}else{
    			$dotaz='SELECT count(`id_dokument`) FROM `dokument` WHERE `dokument_url`="'.$adresa_ck.'invia/xml-'.$typ_serialu.'-'.Date("Y-m-d").'.xml"';
				//echo $dotaz;
			$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
			$zaznam = mysqli_Fetch_Array($data);
			if($zaznam["count(`id_dokument`)"]==0){				
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'INSERT INTO `dokument` (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`) VALUES("'.Date("Y-m-d").'"," INVIA XML dokument '.$typ_serialu.' ","datum vytvoreni:'.Date("Y-m-d").'","'.$adresa_ck.'invia/xml-'.$typ_serialu.'-'.Date("Y-m-d").'.xml") 
                                    ');
				$autoid=mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
				if ($spravne){
					?><h4 style="color:green">dokument byl uspesne vytvoren</h4><?php
					}else{
					?><h4 style="color:black">vytvoreni dokumentu se nepovedlo (xml bylo vygenerovano, ale nebylo ulozeno v databazi)</h4><?php
				}
			}else{
                            
					?><h4 style="color:green">dokument jiz existuje (drivejsi verze ze dneska)</h4><?php
			}
}



}//of else fopen
}//of function generate_xml




function generate_xml_invia_nove($typ_serialu,$adresa_ck){
//-------------------------------------------------pole zemi--------------------------------------------------------
$zeme = array( 
"AF" => "Afghánistán",
"AX" => "A*landy",
"AL" => "Albánie",
"DZ" => "Alžírsko",
"AS" => "Americká Samoa",
"VI" => "Americké Panenské ostrovy",
"AD" => "Andorra",
"AO" => "Angola",
"AI" => "Anguilla",
"AQ" => "Antarktida",
"AG" => "Antigua a Barbuda",
"AR" => "Argentina",
"AM" => "Arménie",
"AW" => "Aruba",
"AU" => "Austrálie",
"AZ" => "Ázerbájdžán",
"BS" => "Bahamy",
"BH" => "Bahrajn",
"BD" => "Bangladéš",
"BB" => "Barbados",
"BE" => "Belgie",
"BZ" => "Belize",
"BY" => "Bìlorusko",
"BJ" => "Benin",
"BM" => "Bermudy",
"BT" => "Bhútán",
"BO" => "Bolívie",
"BA" => "Bosna a Hercegovina",
"BW" => "Botswana",
"BV" => "Bouvetùv ostrov",
"BR" => "Brazílie",
"IO" => "Britské indickooceánské území",
"VG" => "Britské Panenské ostrovy",
"BN" => "Brunej",
"BG" => "Bulharsko",
"BF" => "Burkina Faso",
"BI" => "Burundi",
"CK" => "Cookovy ostrovy",
"TD" => "Èad",
"ME" => "Èerná Hora",
"CZ" => "Èesko",
"CZ" => "Èeská Republika",
"CZ" => "Èeská republika, víkendové pobyty",
"CZ" => "Èeská republika",
"CN" => "Èína",
"DK" => "Dánsko",
"CD" => "Demokratická republika Kongo",
"DM" => "Dominika",
"DO" => "Dominikánská republika",
"DJ" => "Džibutsko",
"EG" => "Egypt",
"EC" => "Ekvádor",
"ER" => "Eritrea",
"EE" => "Estonsko",
"ET" => "Etiopie",
"FO" => "Faerské ostrovy",
"FK" => "Falklandy (Malvíny)",
"FJ" => "Fidži",
"PH" => "Filipíny",
"FI" => "Finsko",
"FR" => "Francie",
"GF" => "Francouzská Guyana",
"TF" => "Francouzská jižní a antarktická území",
"PF" => "Francouzská Polynésie",
"GA" => "Gabon",
"GM" => "Gambie",
"GH" => "Ghana",
"GI" => "Gibraltar",
"GD" => "Grenada",
"GL" => "Grónsko",
"GE" => "Gruzie",
"GP" => "Guadeloupe",
"GU" => "Guam",
"GT" => "Guatemala",
"GN" => "Guinea",
"GW" => "Guinea-Bissau",
"GG" => "Guernsey",
"GY" => "Guyana",
"HT" => "Haiti",
"HM" => "Heardùv ostrov a McDonaldovy ostrovy",
"HN" => "Honduras",
"HK" => "Hongkong",
"CL" => "Chile",
"HR" => "Chorvatsko",
"IN" => "Indie",
"ID" => "Indonésie",
"IQ" => "Irák",
"IR" => "Írán",
"IE" => "Irsko",
"IS" => "Island",
"IT" => "Itálie",
"IL" => "Izrael",
"JM" => "Jamajka",
"JP" => "Japonsko",
"YE" => "Jemen",
"JE" => "Jersey",
"ZA" => "Jihoafrická republika",
"GS" => "Jižní Georgie a Jižní Sandwichovy ostrovy",
"KR" => "Jižní Korea",
"JO" => "Jordánsko",
"KY" => "Kajmanské ostrovy",
"KH" => "Kambodža",
"CM" => "Kamerun",
"CA" => "Kanada",
"CV" => "Kapverdy",
"QA" => "Katar",
"KZ" => "Kazachstán",
"KE" => "Keòa",
"KI" => "Kiribati",
"CC" => "Kokosové ostrovy",
"CO" => "Kolumbie",
"KM" => "Komory",
"CG" => "Kongo",
"CR" => "Kostarika",
"CU" => "Kuba",
"KW" => "Kuvajt",
"CY" => "Kypr",
"KG" => "Kyrgyzstán",
"LA" => "Laos",
"LS" => "Lesotho",
"LB" => "Libanon",
"LR" => "Libérie",
"LY" => "Libye",
"LI" => "Lichtenštejnsko",
"LT" => "Litva",
"LV" => "Lotyšsko",
"LU" => "Lucembursko",
"MO" => "Macao",
"MG" => "Madagaskar",
"HU" => "Maïarsko",
"MK" => "Makedonie",
"MY" => "Malajsie",
"MW" => "Malawi",
"MV" => "Maledivy",
"ML" => "Mali",
"MT" => "Malta",
"IM" => "Ostrov Man",
"MA" => "Maroko",
"MH" => "Marshallovy ostrovy",
"MQ" => "Martinik",
"MU" => "Mauricius",
"MR" => "Mauritánie",
"YT" => "Mayotte",
"UM" => "Menší odlehlé ostrovy USA",
"MX" => "Mexiko",
"FM" => "Mikronésie",
"MD" => "Moldavsko",
"MC" => "Monako",
"MN" => "Mongolsko",
"MS" => "Montserrat",
"MZ" => "Mosambik",
"MM" => "Myanmar",
"NA" => "Namibie",
"NR" => "Nauru",
"DE" => "Nìmecko",
"DE" => "NÌMECKO",
"NP" => "Nepál",
"NE" => "Niger",
"NG" => "Nigérie",
"NI" => "Nikaragua",
"NU" => "Niue",
"AN" => "Nizozemské Antily",
"NL" => "Nizozemsko",
"NF" => "Norfolk",
"NO" => "Norsko",
"NC" => "Nová Kaledonie",
"NZ" => "Nový Zéland",
"OM" => "Omán",
"PK" => "Pákistán",
"PW" => "Palau",
"PS" => "Palestinská autonomie",
"PA" => "Panama",
"PG" => "Papua-Nová Guinea",
"PY" => "Paraguay",
"PE" => "Peru",
"PN" => "Pitcairnovy ostrovy",
"CI" => "Pobøeží slonoviny",
"PL" => "Polsko",
"PR" => "Portoriko",
"PT" => "Portugalsko",
"AT" => "Rakousko",
"RE" => "Réunion",
"GQ" => "Rovníková Guinea",
"RO" => "Rumunsko",
"RU" => "Rusko",
"RW" => "Rwanda",
"GR" => "Øecko",
"BL" => "Saint-Barthélemy",
"MF" => "Saint-Martin",
"PM" => "Saint-Pierre a Miquelon",
"SV" => "Salvador",
"WS" => "Samoa",
"SM" => "San Marino",
"SA" => "Saúdská Arábie",
"SN" => "Senegal",
"KP" => "Severní Korea",
"MP" => "Severní Mariany",
"SC" => "Seychely",
"SL" => "Sierra Leone",
"SG" => "Singapur",
"SK" => "Slovensko",
"SI" => "Slovinsko",
"SO" => "Somálsko",
"AE" => "Spojené Arabské Emiráty",
"GB1" => "Spojené království",
"GB11" => "Anglie",
"GB111" => "Skotsko",
"GB1111" => "Velká Británie", 
"US" => "Spojené státy americké",
"US1" => "USA",
"RS" => "Srbsko",
"CF" => "Støedoafrická republika",
"SD" => "Súdán",
"SR" => "Surinam",
"SH" => "Svatá Helena",
"LC" => "Svatá Lucie",
"KN" => "Svatý Kryštof a Nevis",
"ST" => "Svatý Tomáš a Princùv ostrov",
"VC" => "Svatý Vincenc a Grenadiny",
"SZ" => "Svazijsko",
"SY" => "Sýrie",
"SB" => "Šalamounovy ostrovy",
"ES" => "Španìlsko",
"SJ" => "Špicberky a Jan Mayen",
"LK" => "Šrí Lanka",
"SE" => "Švédsko",
"CH" => "Švýcarsko",
"TJ" => "Tádžikistán",
"TZ" => "Tanzanie",
"TH" => "Thajsko",
"TW" => "Tchaj-wan",
"TG" => "Togo",
"TK" => "Tokelau",
"TO" => "Tonga",
"TT" => "Trinidad a Tobago",
"TN" => "Tunisko",
"TR" => "Turecko",
"TM" => "Turkmenistán",
"TC" => "Turks a Caicos",
"TV" => "Tuvalu",
"UG" => "Uganda",
"UA" => "Ukrajina",
"UY" => "Uruguay",
"UZ" => "Uzbekistán",
"CX" => "Vánoèní ostrov",
"VU" => "Vanuatu",
"VA" => "Vatikán",
"VE" => "Venezuela",
"VN" => "Vietnam",
"TL" => "Východní Timor",
"WF" => "Wallis a Futuna",
"ZM" => "Zambie",
"EH" => "Západní Sahara",
"ZW" => "Zimbabwe"
);
//------------------------------------------------------------------------------------------------

if($typ_serialu=="cele"){
    $fp = fopen ("../invia-nove/".$typ_serialu.".xml","w"); //otevru podle parametru
}else{
    $fp = fopen ("../invia-nove/xml-".$typ_serialu.".xml","w"); //otevru podle parametru
}
if(!$fp){
echo "<h4 style=\"color:black\">error, nepodarilo se otevrit soubor!!!</h4>";
}else{

echo "podarilo se otevrit/vytvorit soubor <i>\"invia-nove/xml-".$typ_serialu.".xml \"</i><br/> ";


//hlavicka
$text="<?xml version=\"1.0\" encoding=\"windows-1250\" ?>

		<zajezdy>\n";




if($typ_serialu=="cele"){
    $dotaz="SELECT distinct  `serial`.`nazev`, `dlouhodobe_zajezdy` , `serial`.`popisek`,`popis_stravovani`,`popis_ubytovani`,`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`cena_nezahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`nazev_typ_web`,`strava`,`doprava`,`ubytovani`,`ubytovani_kategorie`,`serial`.`id_serial`,`serial`.`id_sablony_zobrazeni`,
  			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`, min(`cena_zajezd`.`vyprodano`) as `vyprodano`
			FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)join
                                 `zeme_serial` on (`serial`.`id_serial` = `zeme_serial`.`id_serial`) join   
                                 `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`) join
                                 `cena_zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                 
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                                            
			where `zeme`.`geograficka_zeme`=1 and  `od`>'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=0  
                             and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `cena_zajezd`.`nezobrazovat`<>1 and `serial`.`id_sablony_zobrazeni` != 8  
                       group by `serial`.`id_serial`     
                       having `vyprodano` = 0
                            order by `serial`.`id_serial` , `zeme_serial`.`zakladni_zeme` desc,`zeme_serial`.`polozka_menu` desc";
}else{
    $dotaz="SELECT distinct  `serial`.`nazev`,`dlouhodobe_zajezdy`, `serial`.`popisek`,`popis_stravovani`,`popis_ubytovani`,`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`cena_nezahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`nazev_typ_web`,`strava`,`doprava`,`ubytovani`,`ubytovani_kategorie`,`serial`.`id_serial`,`serial`.`id_sablony_zobrazeni`,
  			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`
			FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)join
                                `zeme_serial` on (`serial`.`id_serial` = `zeme_serial`.`id_serial`) join   
                                 `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`) 
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                                            
			where `zeme`.`geograficka_zeme`=1 and `od`>'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=0 and `typ_serial`.`nazev_typ_web`=\"".$typ_serialu."\" 
                             and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  and `serial`.`id_sablony_zobrazeni` != 8  
                       group by `serial`.`id_serial`     
                            order by `serial`.`id_serial` , `zeme_serial`.`zakladni_zeme` desc,`zeme_serial`.`polozka_menu` desc ";

}

$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
//$text.=mysqli_errno($GLOBALS["core"]->database->db_spojeni) . ": " . mysqli_error($GLOBALS["core"]->database->db_spojeni). "<br/>\n";
while($zaznam = mysqli_Fetch_Array($data)){

	//typ zajezdu
	if($zaznam["id_typ"] == 1 or $zaznam["id_typ"] == 7 or $zaznam["id_typ"] == 8){
		$tourcat="pobytove";
        }else if($zaznam["id_typ"] == 3 ){
                $tourcat="lazensky";
	}else if($zaznam["id_typ"] == 2 or $zaznam["id_typ"] == 6 or $zaznam["id_typ"] == 31){
		$tourcat="poznavaci";	
	}else if($zaznam["id_typ"] == 5){
		$tourcat="lyzarske";	
	}else if($zaznam["id_typ"] == 4 ){
		$tourcat="za-sportem";	
                $dotaz_sport = "
                    SELECT distinct  `zeme`.`nazev_zeme`, `zeme`.`nazev_zeme_web`
			FROM `zeme_serial`  join   
                             `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`) 
     
			where `zeme`.`geograficka_zeme`=0 and `zeme_serial`.`id_serial`=".$zaznam["id_serial"]."   
                        order by `zeme_serial`.`zakladni_zeme` desc limit 1                  
                    ";
                $data_sport = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_sport);
                while ($row_sport = mysqli_fetch_array($data_sport)) {
                    $tourcat=$row_sport["nazev_zeme_web"];	
                }
        }else if($zaznam["id_typ"] == 29){
		$tourcat="eurovikend";
        }else if($zaznam["id_typ"] == 30){
		$tourcat="poznavaci";
        }else{
		$tourcat="jine";
                                
	}
	
	//typ ubytovani
	if($zaznam["ubytovani"]==7){
		$hotelkat="hotel_kat=\"2\"";
	}else if($zaznam["ubytovani"]==8){
		$hotelkat="hotel_kat=\"3\"";
	}else if($zaznam["ubytovani"]==9){
		$hotelkat="hotel_kat=\"4\"";
	}else if($zaznam["ubytovani"]==10){
		$hotelkat="hotel_kat=\"5\"";
	}else{
            if($zaznam["ubytovani_kategorie"]!=0){
                $hotelkat="hotel_kat=\"".$zaznam["ubytovani_kategorie"]."\"";
            }else if($zaznam["kategorie"]!=0 and  $zaznam["id_sablony_zobrazeni"]==12){
                $hotelkat="hotel_kat=\"".$zaznam["kategorie"]."\"";
            }else{
                $hotelkat="hotel_kat=\"1\"";
            }
	}
        
   if($tourcat=="pobytove" or $tourcat=="lyzarske" or $tourcat=="lazensky"){
            if($zaznam["nazev_ubytovani"]!="" and $zaznam["id_sablony_zobrazeni"]==12){
                $hotel = "<hotel ".$hotelkat.">".xml_valid(strip_tags($zaznam["nazev_ubytovani"]))."</hotel>\n";
            }else{
                $hotel = "<hotel ".$hotelkat.">".xml_valid(strip_tags($zaznam["nazev"]))."</hotel>\n";
            }

	}else{
		$hotel = "<hotel ".$hotelkat."/>\n";
	}
        if($zaznam["nazev_ubytovani"]!="" and $zaznam["id_sablony_zobrazeni"]==12){
            $tour_title = xml_valid(strip_tags($zaznam["nazev_ubytovani"].", ".$zaznam["nazev"]));
        }else{
            $tour_title = xml_valid(strip_tags($zaznam["nazev"]));  
        }
        
	$text.="<zajezd >
		<tour_id>".$zaznam["id_serial"]."</tour_id>
		<tour_title>".$tour_title."</tour_title>\n
		<tour_cat  name=\"".$tourcat."\"/>

		<ubytovani name=\"".ubytovani($zaznam["ubytovani"])."\"></ubytovani>
		".$hotel."";

		
	//zemì
		$dotaz_zeme="SELECT * FROM `zeme_serial` natural join `zeme`  where `zeme`.`geograficka_zeme`=1 and `id_serial`=".$zaznam["id_serial"]."";
		$data_zeme = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zeme);
		while($zaznam_zeme = mysqli_Fetch_Array($data_zeme)){
		//hledani kodu zeme
				$key = array_search($zaznam_zeme["nazev_zeme"], $zeme); 
                                $key = str_replace("1", "", $key);
				$text.="<zeme id=\"".$key."\">".$zaznam_zeme["nazev_zeme"]."</zeme>\n";
		}//zeme
                
                //pridame i informace o sportech
                 if($zaznam["nazev_typ_web"]=="za-sportem"){
                    $dotaz_zeme2="SELECT * FROM `zeme_serial` join   
                                 `zeme` on (`zeme`.`id_zeme` = `zeme_serial`.`id_zeme`)
                                  where `id_serial`=".$zaznam["id_serial"]." and `zakladni_zeme` = 1 and `geograficka_zeme` = 0 ";
                    $data_zeme2 = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zeme2);
                    while ($row = mysqli_fetch_array($data_zeme2)) {
                        $text.="<zeme>".$row["nazev_zeme"]."</zeme>\n";
                    }
                }
                
	//destinace pro kazdou zemi
		$dotaz_destinace="SELECT * FROM `destinace_serial` natural join `destinace` where `id_serial`=".$zaznam["id_serial"]." limit 1 ";
		$data_destinace = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_destinace);
		while($zaznam_destinace = mysqli_Fetch_Array($data_destinace)){
				$text.="<misto>".$zaznam_destinace["nazev_destinace"]."</misto>\n";		
		}//destinace
                //
                //pokud mame zajezd za sportem, pridame do tour description jeste vsechny nazvy zajezdu
                $text_terminy="";
                $first=1;
                if($zaznam["nazev_typ_web"]=="za-sportem"){

                    $dotaz_zajezd="SELECT * FROM `zajezd` where `id_serial`=".$zaznam["id_serial"]." and `od`>'".Date("Y-m-d")."' and `zajezd`.`nezobrazovat_zajezd`<>1 ";
                    $data_zajezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zajezd);
                    while ($row = mysqli_fetch_array($data_zajezd)) {
                        if($row["nazev_zajezdu"]!=""){
                            if($first){
                                $first=0;
                                $text_terminy.="Dostupné termíny:\n";
                            }
                           
                            $text_terminy.= $row["nazev_zajezdu"]."\n";
                        }
                    }
                }		
		
	//rozdeleni popisu a programu dle typu zajezdu
    //rozdeleni popisu a programu dle typu zajezdu
    $popis_poznamka = "";
    $popis_poznamka_ubyt = "";
    if($zaznam["poznamky"]!="")	{
             $popis_poznamka =  "<popis name=\"Poznámka\">".xml_valid(strip_tags($zaznam["poznamky"]))."</popis>\n";
    }    
    if($zaznam["poznamka_ubytovani"]!="")	{
             $popis_poznamka_ubyt =  "<popis name=\"Poznámka k ubytování\">".xml_valid(strip_tags($zaznam["poznamka_ubytovani"]))."</popis>\n";
    }        
	if($tourcat=="poznavaci"){
		if($zaznam["program_zajezdu"]!=""){
		$program = "
			<program>
				<den prvni=\"Program zájezdu\" >".xml_valid(strip_tags(str_replace("Program zájezdu", "", $zaznam["program_zajezdu"])))."</den>\n
			</program>	";	
            

		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]))."</popis>\n
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popis"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
                ".$popis_poznamka.$popis_poznamka_ubyt."
			</popisy>		
		";		
		}else{
		$program = "
			<program>
				<den prvni=\"Program zájezdu\" >".xml_valid(strip_tags( str_replace("Program zájezdu", "", $zaznam["popis"])))."</den>\n
			</program>	";		
		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
                ".$popis_poznamka.$popis_poznamka_ubyt."
			</popisy>		
		";				
		}

	}else{
		$program = "";
		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]))."</popis>\n
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popis"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
                ".$popis_poznamka.$popis_poznamka_ubyt."
			</popisy>		
		";				
	}
    
    
    /*
	if($tourcat=="poznavaci"){
		if($zaznam["program_zajezdu"]!=""){
		$program = "
			<program>
				<den prvni=\"Program zájezdu\" >".xml_valid(strip_tags(str_replace("Program zájezdu", "", $zaznam["program_zajezdu"])))."</den>\n
			</program>	";		
		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]))."</popis>\n
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popis"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
			</popisy>		
		";		
		}else{
		$program = "
			<program>
				<den prvni=\"Program zájezdu\" >".xml_valid(strip_tags( str_replace("Program zájezdu", "", $zaznam["popis"])))."</den>\n
			</program>	";		
		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
			</popisy>		
		";				
		}

	}else{
		$program = "";
		$popisy="
			<popisy>
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popisek"]))."</popis>\n
				<popis name=\"\">".xml_valid(strip_tags($zaznam["popis"]."\n\n".$text_terminy))."</popis>\n
				<popis name=\"Stravování\">".xml_valid(strip_tags($zaznam["popis_stravovani"]))."</popis>\n
				<popis name=\"Ubytování\">".xml_valid(strip_tags($zaznam["popis_ubytovani"]." ".$zaznam["popisek_ubytovani"]." ".$zaznam["ubytovani_popis_ubytovani"]))."</popis>\n
			</popisy>		
		";				
	}    
    */
	$text.="
		".$popisy.$program."
		";
		
		 
		
	$dotaz_foto="SELECT `foto`.* FROM `foto` 
            join `foto_serial` on (foto_serial.id_foto = foto.id_foto)
            where `foto_serial`.`id_serial`= ".$zaznam["id_serial"]."
            union distinct
            SELECT `foto`.* FROM `foto` 
            join `foto_objekty` on (foto_objekty.id_foto = foto.id_foto)
            join `objekt_serial` on (foto_objekty.id_objektu = objekt_serial.id_objektu)
            where `objekt_serial`.`id_serial`= ".$zaznam["id_serial"]."
            union distinct
            SELECT `foto`.* FROM `foto` 
            join `foto_objekt_kategorie` on (foto_objekt_kategorie.id_foto = foto.id_foto)
            join `objekt_kategorie` on (objekt_kategorie.id_objekt_kategorie = foto_objekt_kategorie.id_objekt_kategorie)
            join `objekt_serial` on (objekt_kategorie.id_objektu = objekt_serial.id_objektu)
            where `objekt_serial`.`id_serial`= ".$zaznam["id_serial"]."";
	$data_foto = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_foto);
	$first=1;
	while($zaznam_foto = mysqli_Fetch_Array($data_foto)){
				$pos1 = (strpos($zaznam_foto["foto_url"], " ") or (strpos($zaznam_foto["foto_url"], "´"))  or (strpos($zaznam_foto["foto_url"], "è")) or (strpos($zaznam_foto["foto_url"], "ö"))  or (strpos($zaznam_foto["foto_url"], "ü")) );
				if ($pos1 === false) {
					if($first){
						$text.="
						<fotky>
						";
						$first=0;
					}
					$text.="<fotka url=\"".$adresa_ck."foto/full/".$zaznam_foto["foto_url"]."\">".xml_valid($zaznam_foto["nazev_foto"])."</fotka>\n";
				}
	}	//foto
	if(!$first){
		$text.="
		</fotky>
		";
		$first=0;
	}
	if($zaznam["dlouhodobe_zajezdy"]==1){
		$dl_zaj=" dlouhe_terminy=\"1\"";
	}else{
		$dl_zaj="";
	}
        
        $text_zahrnuje = "";
        $text_nezahrnuje = "";
        $zahrnuje_array = explode("<li", $zaznam["cena_zahrnuje"]);
        $nezahrnuje_array = explode("<li", $zaznam["cena_nezahrnuje"]);
        
        foreach ($zahrnuje_array as $key => $value) {
            $polozka = trim(xml_valid(strip_tags($value)));
            if($polozka!=""){
                $text_zahrnuje .= "<polozka>".$polozka."</polozka>\n";
            }
        }
        foreach ($nezahrnuje_array as $key => $value) {
            $polozka = trim(xml_valid(strip_tags($value)));
            if($polozka!=""){
                $text_nezahrnuje .= "<polozka>".$polozka."</polozka>\n";
            }
        }
       $odlety="";
       $odlet="";
        $prilet="";
        if($zaznam["doprava"]==3){
            $use_default=true;
            $query = "select distinct odjezdove_misto, kod_letiste from cena 
                where 
                    ((odjezdove_misto!=\"\" and odjezdove_misto is not null) or (kod_letiste!=\"\" and kod_letiste is not null) )
                    and id_serial=".$zaznam["id_serial"]."";
            $data_odlet = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
            while ($row_odlet = mysqli_fetch_array($data_odlet)) {
                $use_default=false;
                $odlet.="<odlet odlet_id=\"".$row_odlet["kod_letiste"]."\">".$row_odlet["odjezdove_misto"]."</odlet>";
                $prilet.="<prilet odlet_id=\"".$row_odlet["kod_letiste"]."\">".$row_odlet["odjezdove_misto"]."</prilet>";
            }
            
            if($use_default){
                $odlety="
                    <odlety>
                        <odlet odlet_id=\"PRG\">Praha</odlet>
                    </odlety>
                    <prilety>
                        <prilet odlet_id=\"PRG\">Praha</prilet>
                    </prilety>";
            }else{
                $odlety="
                    <odlety>
                        $odlet
                    </odlety>
                    <prilety>
                        $prilet
                    </prilety>                    
                    ";
            }
        }
	$text.="
		<term_group ".$dl_zaj.">
			<strava name=\"".strava($zaznam["strava"])."\"></strava>
			<doprava name=\"".doprava($zaznam["doprava"])."\"></doprava>
                            ".$odlety."
			<zahrnuje>\n".$text_zahrnuje."</zahrnuje>
                        <nezahrnuje>\n".$text_nezahrnuje."</nezahrnuje>        			
	";
	//slevy
		$slevy="";
		$dotaz_slevy = "select * from `slevy` join
							`slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy`)
							where `slevy_serial`.`id_serial` = ".$zaznam["id_serial"]." 
							and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
							order by `slevy`.`castka` desc limit 3";
		//echo $dotaz_slevy;
		$data_slevy = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_slevy);
		while($sleva = mysqli_Fetch_Array($data_slevy)){
			if($sleva["mena"]=="Kè"){
				$sleva["mena"]="CZK";
			}
			$slevy .= "
				<polozka cena=\"".$sleva["castka"]."\" mena=\"".$sleva["mena"]."\" >".xml_valid($sleva["zkraceny_nazev"])." - ".xml_valid($sleva["nazev_slevy"])."</polozka>";
		}	
		if($slevy!=""){
			$text.="
				<slevy>
					".$slevy ."
				</slevy>	";		
		}

	
	$dotaz_zajezd="SELECT `zajezd`.*, min(`cena_zajezd`.`vyprodano`) as `vyprodano` FROM `zajezd` join
                                 `cena_zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                 where `id_serial`=".$zaznam["id_serial"]." and `od`>'".Date("Y-m-d")."' and `zajezd`.`nezobrazovat_zajezd`<>1 and `cena_zajezd`.`nezobrazovat`<>1
                                 group by   `zajezd`.`id_zajezd`  
                                 having `vyprodano` = 0";
	$data_zajezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zajezd);
	while($zaznam_zajezd = mysqli_Fetch_Array($data_zajezd)){
		$text.="<termin id=\"".$zaznam_zajezd["id_zajezd"]."\"> 
					<d_start>".$zaznam_zajezd["od"]."</d_start>
					<d_konec>".$zaznam_zajezd["do"]."</d_konec>
				";

		$dotaz_cena="SELECT * FROM `cena` natural join `cena_zajezd`  where `id_zajezd`=".$zaznam_zajezd["id_zajezd"]." and  `id_serial`=".$zaznam["id_serial"]." and `cena_zajezd`.`nezobrazovat`<>1";
		$data_cena = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_cena);
                $cena_od = 0;
                $cena_od_existuje = false;
                $ceny="";
		while($zaznam_cena = mysqli_Fetch_Array($data_cena)){
                        if($zaznam_cena["zakladni_cena"]==1){
                            $zakladni_cena = $zaznam_cena["castka"];
                        }
                        if(($zaznam_cena["typ_ceny"]==1 or $zaznam_cena["typ_ceny"]==2) 
                                and ($zaznam_cena["poradi_ceny"]<150 or ($zaznam_cena["poradi_ceny"]>=200 and $zaznam_cena["poradi_ceny"]<300 ))
                                and stripos($zaznam_cena["nazev_ceny"], "dítì")===false
                                and stripos($zaznam_cena["nazev_ceny"], "pøíplatek")===false
                                and stripos($zaznam_cena["nazev_ceny"], "pøistýl")===false
                                and stripos($zaznam_cena["nazev_ceny"], "pøístýl")===false
                                and stripos($zaznam_cena["nazev_ceny"], "víza")===false
                                and stripos($zaznam_cena["nazev_ceny"], "vízum")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3. dosp")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "3 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4. dosp")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "4 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "5. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "5. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "5.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "5 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "6. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "6. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "6.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "6 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "7. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "7. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "7.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "7 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "8. osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "8. os.")===false
                                and stripos($zaznam_cena["nazev_ceny"], "8.osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "8 osob")===false
                                and stripos($zaznam_cena["nazev_ceny"], "senior")===false
                                and stripos($zaznam_cena["nazev_ceny"], "junior")===false
                                and stripos($zaznam_cena["nazev_ceny"], "doprava")===false
                                and $zaznam_cena["castka"] >0){
                            //jedna se o relevantni sluzbu
                            if($cena_od_existuje == false){
                                //vezmu libovolnou cenu
                                $cena_od_existuje = true;
                                $cena_od = $zaznam_cena["castka"];
                            }else if($cena_od > $zaznam_cena["castka"] ){
                                $cena_od = $zaznam_cena["castka"];
                            }
                        }
                        if($zaznam_cena["vyprodano"]=="1"){
                           $vyprodano = "vyprodano=\"1\""; 
                        }else{
                            $vyprodano = "vyprodano=\"0\"";
                        }    
                        if($zaznam_cena["typ_ceny"]=="4"){
                           $priplatek = " priplatek=\"1\" "; 
                        }else{
                            $priplatek = "";
                        }  
                        
			$ceny.="
					<cena cena=\"".$zaznam_cena["castka"]."\" $vyprodano $priplatek mena=\"CZK\" zakladni_cena=\"".$zaznam_cena["zakladni_cena"]."\">".xml_valid($zaznam_cena["nazev_ceny"])."</cena>
					";  
						
		}//cena
                if($zakladni_cena > 0){
                    $cena_od = $zakladni_cena;
                }
                if ($cena_od_existuje) {
                    $text.= "<cena_od>" . $cena_od . "</cena_od>";
                    if ($zaznam_zajezd["cena_pred_akci"] != "" and $zaznam_zajezd["akcni_cena"] == $cena_od) {
                        $text.= "<konecna_cena>" . $zaznam_zajezd["akcni_cena"] . "</konecna_cena>";
                        $text.= "<puvodni_cena>" . $zaznam_zajezd["cena_pred_akci"] . "</puvodni_cena>";
                    } else {
                        $text.= "<konecna_cena>" . $cena_od . "</konecna_cena>";
                        $text.= "<puvodni_cena>" . $cena_od . "</puvodni_cena>";
                    }
                }else{
                   $text.= "<cena_od>" . $zakladni_cena . "</cena_od>"; 
                   $text.= "<konecna_cena>" . $zakladni_cena . "</konecna_cena>";
                   $text.= "<puvodni_cena>" . $zakladni_cena . "</puvodni_cena>";
                }
		$text.=$ceny."</termin>";
                if($cena_od < $zakladni_cena){
                  //  echo $cena_od."; ".$zakladni_cena ."; ".$zaznam["nazev_ubytovani"]." ".$zaznam["nazev"]."\n<br/>";
                }
		
	}//zajezd		
		
		
	$text.="
		</term_group>
		";



	$text.="</zajezd>\n\n	";
}//serial

$text.="	</zajezdy>
";

fwrite ($fp, $text);
fclose ($fp);
echo "vse vygenerovano OK<br/>";
echo "Vytvarim zaznam do databaze dokumentu...<br/>";
                        if($typ_serialu=="cele"){
                           $dotaz='SELECT `id_dokument` FROM `dokument` WHERE `dokument_url`="'.$adresa_ck.'invia-nove/'.$typ_serialu.'.xml"';
                        }else{
                            $dotaz='SELECT `id_dokument` FROM `dokument` WHERE `dokument_url`="'.$adresa_ck.'invia-nove/xml-'.$typ_serialu.'.xml"';
                        }
                        //echo $dotaz;
			$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
			$zaznam = mysqli_Fetch_Array($data);
			if(mysqli_Num_Rows($data)==0){
                            if($typ_serialu=="cele"){
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'INSERT INTO `dokument` (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`) VALUES("'.Date("Y-m-d").'"," INVIA XML dokument '.$typ_serialu.' bez dlouhodobých zájezdù","datum vytvoreni:'.Date("Y-m-d").'","'.$adresa_ck.'invia-nove/'.$typ_serialu.'.xml") ');
                            }else{
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'INSERT INTO `dokument` (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`) VALUES("'.Date("Y-m-d").'"," INVIA XML dokument '.$typ_serialu.' bez dlouhodobých zájezdù","datum vytvoreni:'.Date("Y-m-d").'","'.$adresa_ck.'invia-nove/xml-'.$typ_serialu.'.xml") ');
                            }
				$autoid=mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
				if ($spravne){
					?><h4 style="color:green">dokument byl uspesne vytvoren</h4><?php
					}else{
					?><h4 style="color:black">vytvoreni dokumentu se nepovedlo (xml bylo vygenerovano, ale nebylo ulozeno v databazi)</h4><?php
				}
			}else{
                             $update_dotaz = 'UPDATE `dokument` set `datum_vytvoreni` = "'.Date("Y-m-d").'", `popisek_dokument` = "datum vytvoreni:'.Date("Y-m-d H:i:s").'" where
                                            `id_dokument` =  '.$zaznam["id_dokument"].'  ';
                            $spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,$update_dotaz);
                            echo mysqli_error($GLOBALS["core"]->database->db_spojeni);
					?><h4 style="color:green">dokument jiz existuje (drivejsi verze)</h4><?php
			}

}//of else fopen
}//of function generate_xml




function generate_xml_cestujeme($typ_serialu,$adresa_ck){
$fp = fopen ("../cestujeme/xml-".$typ_serialu.".xml","w"); //otevru podle parametru
if(!$fp){
echo "<h4 style=\"color:black\">error, nepodarilo se otevrit soubor!!!</h4>";
}else{

echo "podarilo se otevrit/vytvorit soubor <i>\"/cestujeme/xml-".$typ_serialu.".xml\"</i><br/> ";
echo "text:".$text."endText<br/>";
//hlavicka
$text="<?xml version=\"1.0\" encoding=\"windows-1250\" ?>
<!DOCTYPE 
seznam_serialu [
		<!ELEMENT seznam_serialu (serial*)>
		<!ELEMENT serial (nazev,popisek,popis,cena_zahrnuje,poznamky,typ,strava,doprava,ubytovani,zeme+,foto*,zajezd+)>
			<!ELEMENT zeme (nazev_zeme,destinace*)>
			<!ELEMENT foto (nazev_foto,adresa_foto)>
			<!ELEMENT zajezd (nazev_zajezdu?,termin_od,termin_do,poznamky,cena+)>
			<!ELEMENT cena (popis_ceny,velikost_ceny,strava_vcetne?,doprava_vcetne?,pocet_dnu?,lastminute?)>
			
				<!ELEMENT nazev (#PCDATA)>
				<!ELEMENT popisek ANY>
				<!ELEMENT popis ANY>
				<!ELEMENT cena_zahrnuje (#PCDATA)>
				<!ELEMENT poznamky (#PCDATA)>
				<!ELEMENT typ (#PCDATA)>
				<!ELEMENT strava (#PCDATA)>
				<!ELEMENT doprava (#PCDATA)>
				<!ELEMENT ubytovani (#PCDATA)>
				<!ELEMENT nazev_zeme (#PCDATA)>
				<!ELEMENT destinace (#PCDATA)>
				<!ELEMENT nazev_foto (#PCDATA)>
				<!ELEMENT adresa_foto (#PCDATA)>
				<!ELEMENT nazev_zajezdu (#PCDATA)>
				<!ELEMENT termin_od (#PCDATA)>
				<!ELEMENT termin_do (#PCDATA)>
				<!ELEMENT popis_ceny (#PCDATA)>
				<!ELEMENT velikost_ceny (#PCDATA)>
				<!ELEMENT strava_vcetne (#PCDATA)>
				<!ELEMENT doprava_vcetne (#PCDATA)>
				<!ELEMENT pocet_dnu (#PCDATA)>
				<!ELEMENT lastminute (#PCDATA)>
				
				<!ATTLIST serial id_serial CDATA #REQUIRED>
				<!ATTLIST cena zakladni CDATA #REQUIRED>
		
		]>
		<seznam_serialu>\n";



if($typ_serialu=="cele"){
    $dotaz="SELECT distinct `serial`.`nazev`,`serial`.`popisek`,`popis_stravovani`,`popis_ubytovani`,`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`strava`,`doprava`,`ubytovani`,`serial`.`id_serial`,
 			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`
                        FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
                                
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                          
			where (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1))  
                             and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  and `serial`.`id_sablony_zobrazeni` != 8  
                       
                        order by `serial`.`id_serial` ";
}else{
    $dotaz="SELECT distinct `serial`.`nazev`,`serial`.`popisek`,`popis_stravovani`,`popis_ubytovani`,`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`strava`,`doprava`,`ubytovani`,`serial`.`id_serial`,
 			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`
                        FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
                                
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                          
			where (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1))  and `typ_serial`.`nazev_typ_web`=\"".$typ_serialu."\" 
                             and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  and `serial`.`id_sablony_zobrazeni` != 8  
                       
                        order by `serial`.`id_serial` ";
}

			
$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
//$text.=mysqli_errno($GLOBALS["core"]->database->db_spojeni) . ": " . mysqli_error($GLOBALS["core"]->database->db_spojeni). "<br/>\n";

while($zaznam = mysqli_Fetch_Array($data)){
        if($zaznam["nazev_ubytovani"]){
            $nazev = "<nazev>".xml_valid(strip_tags($zaznam["nazev_ubytovani"]." - ".$zaznam["nazev"]))."</nazev>\n";
            $popisek = "<popisek>".xml_valid(strip_tags($zaznam["popisek"]." ".$zaznam["popisek_ubytovani"]))."</popisek>\n";
        }else{
            $nazev = "<nazev>".xml_valid(strip_tags($zaznam["nazev"]))."</nazev>\n";
            $popisek = "<popisek>".xml_valid(strip_tags($zaznam["popisek"]))."</popisek>\n";
        }

	$text.="\n\n<serial id_serial=\"".$zaznam["id_serial"]."\" >\n
                ".$nazev.$popisek."
		<popis>".xml_valid(strip_tags($zaznam["popis"]))."\n
		".xml_valid(strip_tags($zaznam["program_zajezdu"]))."\n
		".xml_valid(strip_tags($zaznam["popis_stravovani"]))."\n
		".xml_valid(strip_tags($zaznam["popis_ubytovani"]))."\n
                ".xml_valid(strip_tags($zaznam["ubytovani_popis_ubytovani"]))."\n
		</popis>\n
	
					
		<cena_zahrnuje>".xml_valid(strip_tags($zaznam["cena_zahrnuje"]))."</cena_zahrnuje>\n
		<poznamky>".xml_valid(strip_tags($zaznam["poznamky"]))."</poznamky>\n
		<typ>".xml_valid(strip_tags($zaznam["nazev_typ"]))."</typ>\n
		<strava>".strava($zaznam["strava"])."</strava>\n
		<doprava>".doprava($zaznam["doprava"])."</doprava>\n
		<ubytovani>".ubytovani($zaznam["ubytovani"])."</ubytovani>\n";
	//zemì
		$dotaz_zeme="SELECT * FROM `zeme_serial` natural join `zeme`  where `id_serial`=".$zaznam["id_serial"]."";
		$data_zeme = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zeme);
		while($zaznam_zeme = mysqli_Fetch_Array($data_zeme)){
				$text_zeme="\n<zeme><nazev_zeme>".$zaznam_zeme["nazev_zeme"]."</nazev_zeme>\n";
	//destinace pro kazdou zemi
		$dotaz_destinace="SELECT * FROM `destinace_serial` natural join `destinace` where `id_serial`=".$zaznam["id_serial"]." and `id_zeme` = '".$zaznam_zeme["id_zeme"]."' ";
		$data_destinace = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_destinace);
		$zeme_premapovana=0;
		$text_destinace="";
		while($zaznam_destinace = mysqli_Fetch_Array($data_destinace)){
				if($zeme_premapovana==0){
					$nova_zeme = mapovani_zeme($zaznam_zeme["nazev_zeme"],$zaznam_destinace["nazev_destinace"]);
					if($nova_zeme!=$zaznam_zeme["nazev_zeme"]){
						$text_zeme="<zeme><nazev_zeme>".$nova_zeme."</nazev_zeme>";
						$zeme_premapovana=1;
					}
				}				
				$text_destinace.="<destinace>".$zaznam_destinace["nazev_destinace"]."</destinace>\n";		
		}//destinace
		
		$text=$text.$text_zeme.$text_destinace."\n</zeme>\n";
	}//zeme

	$dotaz_foto="SELECT * FROM `foto_serial` natural join `foto` where `id_serial`=".$zaznam["id_serial"]."";
	$data_foto = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_foto);
	while($zaznam_foto = mysqli_Fetch_Array($data_foto)){
				$text.="\n<foto> 
							<nazev_foto>".$zaznam_foto["nazev_foto"]."</nazev_foto>
							<adresa_foto>".$adresa_ck."foto/full/".$zaznam_foto["foto_url"]."</adresa_foto>
						</foto>";

	}	//foto


	$dotaz_zajezd="SELECT * FROM `zajezd` where `id_serial`=".$zaznam["id_serial"]." and `do`>'".Date("Y-m-d")."'";

	$data_zajezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zajezd);
	while($zaznam_zajezd = mysqli_Fetch_Array($data_zajezd)){
		if($zaznam_zajezd["nazev_zajezdu"]!=""){
			$nazev_zaj = "<nazev_zajezdu>".xml_valid(strip_tags($zaznam["nazev_zajezdu"]))."</nazev_zajezdu>";
		}else{
			$nazev_zaj = "";
		}
		$text.="\n<zajezd> 
					".$nazev_zaj."
					<termin_od>".$zaznam_zajezd["od"]."</termin_od>
					<termin_do>".$zaznam_zajezd["do"]."</termin_do>
					<poznamky>".xml_valid(strip_tags($zaznam_zajezd["nazev_zajezdu"]." ".$zaznam_zajezd["poznamky_zajezd"]))."</poznamky>";

		$dotaz_cena="SELECT * FROM `cena` natural join `cena_zajezd`  where `id_zajezd`=".$zaznam_zajezd["id_zajezd"]." and  `id_serial`=".$zaznam["id_serial"]." and `cena_zajezd`.`nezobrazovat`<>1";
		$data_cena = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_cena);
		while($zaznam_cena = mysqli_Fetch_Array($data_cena)){
			$text.="\n<cena zakladni=\"".$zaznam_cena["zakladni_cena"]."\">
						<popis_ceny>".$zaznam_cena["nazev_ceny"]."</popis_ceny>
						<velikost_ceny>".$zaznam_cena["castka"]."</velikost_ceny>";
			if($zaznam_cena["zakladni_cena"]=="1"){
				//pocet dni
				if($zaznam["dlouhodobe_zajezdy"]=="1"){
					$pocet_noci="1";
				}else{
					$pocet_noci="".calculate_pocet_noci($zaznam_zajezd["od"], $zaznam_zajezd["do"])."";
				}
				//lastminute
				if($zaznam_cena["typ_ceny"]=="2"){
					$last_minute="1";
				}else{
					$last_minute="0";
				}				
				//conclusion
				$text.="
					<strava_vcetne>".strava_cestujeme($zaznam["strava"])."</strava_vcetne>
					<doprava_vcetne>".doprava($zaznam["doprava"])."</doprava_vcetne>
					<pocet_dnu>".$pocet_noci."</pocet_dnu>
					<lastminute>".$last_minute."</lastminute>
				";
			}	
						
			$text.="</cena>\n";
						
		}//cena
		$text.="</zajezd>\n";
	}//zajezd

	$text.="</serial>	\n";
}//serial

$text.="	</seznam_serialu>
";

fwrite ($fp, $text);
fclose ($fp);
echo "vse vygenerovano OK<br/>";
echo "Vytvarim zaznam do databaze dokumentu...<br/>";

			$dotaz="SELECT `id_dokument` FROM `dokument` WHERE `dokument_url`=\"".$adresa_ck."cestujeme/xml-".$typ_serialu.".xml\"";
				//echo $dotaz;
			$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
			$zaznam = mysqli_Fetch_Array($data);
			if(mysqli_num_rows($data)==0){				
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'INSERT INTO `dokument` (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`) VALUES("'.Date("Y-m-d").'", "Cestujeme XML dokument '.$typ_serialu.' ","datum vytvoreni:'.Date("Y-m-d").'","'.$adresa_ck.'cestujeme/xml-'.$typ_serialu.'.xml") ');
				$autoid=mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
				if ($spravne){
					?><h4 style="color:green">dokument byl uspesne vytvoren</h4><?php
					}else{
					?><h4 style="color:black">vytvoreni dokumentu se nepovedlo (xml bylo vygenerovano, ale nebylo ulozeno v databazi)</h4><?php
				}
			}else{		
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'UPDATE `dokument` set `datum_vytvoreni`="'.Date("Y-m-d").'",`nazev_dokument`="Cestujeme XML dokument ",`popisek_dokument`="datum vytvoreni:'.Date("Y-m-d H:i:s").'", `dokument_url`="'.$adresa_ck.'cestujeme/xml-'.$typ_serialu.'.xml" where `id_dokument`='.$zaznam["id_dokument"].' limit 1 ');
				if ($spravne){
					?><h4 style="color:green">dokument byl uspesne vytvoren</h4><?php
					}else{
					?><h4 style="color:black">vytvoreni dokumentu se nepovedlo (xml bylo vygenerovano, ale nebylo ulozeno v databazi)</h4><?php
				}

			}

}//of else fopen
}//of function generate_xml_cestujeme

function generate_xml($typ_serialu,$adresa_ck){
$fp = fopen ("../xml/xml-".$typ_serialu.".xml","w"); //otevru podle parametru
if(!$fp){
echo "<h4 style=\"color:black\">error, nepodarilo se otevrit soubor!!!</h4>";
}else{

echo "podarilo se otevrit/vytvorit soubor <i>\"xml/xml-".$typ_serialu.".xml \"</i><br/> ";

//hlavicka
$text="<?xml version=\"1.0\" encoding=\"windows-1250\" ?>
<!DOCTYPE 
seznam_serialu [
		<!ELEMENT seznam_serialu (serial*)>
		<!ELEMENT serial (nazev,popisek,popis,cena_zahrnuje,poznamky,typ,strava,doprava,ubytovani,zeme+,foto*,zajezd+)>
			<!ELEMENT zeme (nazev_zeme,destinace*)>
			<!ELEMENT foto (nazev_foto,adresa_foto)>
			<!ELEMENT zajezd (termin_od,termin_do,poznamky,cena+)>
			<!ELEMENT cena (popis_ceny,velikost_ceny)>
			
				<!ELEMENT nazev (#PCDATA)>
				<!ELEMENT popisek ANY>
				<!ELEMENT popis ANY>
				<!ELEMENT cena_zahrnuje (#PCDATA)>
				<!ELEMENT poznamky (#PCDATA)>
				<!ELEMENT typ (#PCDATA)>
				<!ELEMENT strava (#PCDATA)>
				<!ELEMENT doprava (#PCDATA)>
				<!ELEMENT ubytovani (#PCDATA)>
				<!ELEMENT nazev_zeme (#PCDATA)>
				<!ELEMENT destinace (#PCDATA)>
				<!ELEMENT nazev_foto (#PCDATA)>
				<!ELEMENT adresa_foto (#PCDATA)>
				<!ELEMENT termin_od (#PCDATA)>
				<!ELEMENT termin_do (#PCDATA)>
				<!ELEMENT popis_ceny (#PCDATA)>
				<!ELEMENT velikost_ceny (#PCDATA)>
				
				<!ATTLIST serial id_serial CDATA #REQUIRED>
				<!ATTLIST cena zakladni CDATA #REQUIRED>
		
		]>
		<seznam_serialu>\n";
		

if($typ_serialu=="cele"){
    $dotaz="SELECT distinct `serial`.`nazev`,`serial`.`popisek`,`popis_stravovani`,`popis_ubytovani`,`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`strava`,`doprava`,`ubytovani`,`serial`.`id_serial`,
  			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`
                     FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
                                
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                                            
			where (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) 
                           and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  and `serial`.`id_sablony_zobrazeni` != 8  
                         
                        order by `serial`.`id_serial` ";
}else{
    $dotaz="SELECT distinct `serial`.`nazev`,`serial`.`popisek`,`popis_stravovani`,`popis_ubytovani`,`program_zajezdu`,`serial`.`popis`,`cena_zahrnuje`,`poznamky`,`serial`.`id_typ`,`nazev_typ`,`strava`,`doprava`,`ubytovani`,`serial`.`id_serial`,
 			        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`kategorie`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt`.`poznamka` as `poznamka_ubytovani`
                        FROM `serial` join
				`zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`) join
				`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
                                
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                                            
			where (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1))  and `typ_serial`.`nazev_typ_web`=\"".$typ_serialu."\" 
                             and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  and `serial`.`id_sablony_zobrazeni` != 8  
                       
                        order by `serial`.`id_serial` ";
}

$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);

while($zaznam = mysqli_Fetch_Array($data)){
        if($zaznam["nazev_ubytovani"]){
            $nazev = "<nazev>".xml_valid(strip_tags($zaznam["nazev_ubytovani"]." - ".$zaznam["nazev"]))."</nazev>\n";
            $popisek = "<popisek>".xml_valid(strip_tags($zaznam["popisek"]." ".$zaznam["popisek_ubytovani"]))."</popisek>\n";
        }else{
            $nazev = "<nazev>".xml_valid(strip_tags($zaznam["nazev"]))."</nazev>\n";
            $popisek = "<popisek>".xml_valid(strip_tags($zaznam["popisek"]))."</popisek>\n";
        }

	$text.="<serial id_serial=\"".$zaznam["id_serial"]."\" >\n
		".$nazev.$popisek."
		<popis>".xml_valid(strip_tags($zaznam["popis"]))."\n
		".xml_valid(strip_tags($zaznam["program_zajezdu"]))."\n
		".xml_valid(strip_tags($zaznam["popis_stravovani"]))."\n
		".xml_valid(strip_tags($zaznam["popis_ubytovani"]))."\n
                ".xml_valid(strip_tags($zaznam["ubytovani_popis_ubytovani"]))."\n
		</popis>\n
		<cena_zahrnuje>".xml_valid(strip_tags($zaznam["cena_zahrnuje"]))."</cena_zahrnuje>\n
		<poznamky>".xml_valid(strip_tags($zaznam["poznamky"]))."</poznamky>\n
		<typ>".xml_valid(strip_tags($zaznam["nazev_typ"]))."</typ>\n
		<strava>".strava($zaznam["strava"])."</strava>\n
		<doprava>".doprava($zaznam["doprava"])."</doprava>\n
		<ubytovani>".ubytovani($zaznam["ubytovani"])."</ubytovani>\n";
	//zemì
		$dotaz_zeme="SELECT * FROM `zeme_serial` natural join `zeme`  where `id_serial`=".$zaznam["id_serial"]."";
		$data_zeme = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zeme);
		while($zaznam_zeme = mysqli_Fetch_Array($data_zeme)){
				$text.="<zeme><nazev_zeme>".$zaznam_zeme["nazev_zeme"]."</nazev_zeme>";
	//destinace pro kazdou zemi
		$dotaz_destinace="SELECT * FROM `destinace_serial` natural join `destinace` where `id_serial`=".$zaznam["id_serial"]." and `id_zeme` = '".$zaznam_zeme["id_zeme"]."' ";
		$data_destinace = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_destinace);
		while($zaznam_destinace = mysqli_Fetch_Array($data_destinace)){
				$text.="<destinace>".$zaznam_destinace["nazev_destinace"]."</destinace>";		
		}//destinace
		$text.="</zeme>";
	}//zeme

	$dotaz_foto="SELECT * FROM `foto_serial` natural join `foto` where `id_serial`=".$zaznam["id_serial"]."";
	$data_foto = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_foto);
	while($zaznam_foto = mysqli_Fetch_Array($data_foto)){
				$text.="<foto> 
							<nazev_foto>".$zaznam_foto["nazev_foto"]."</nazev_foto>
							<adresa_foto>".$adresa_ck."foto/full/".$zaznam_foto["foto_url"]."</adresa_foto>
						</foto>";

	}	//foto


	$dotaz_zajezd="SELECT * FROM `zajezd` where `id_serial`=".$zaznam["id_serial"]." and `do`>'".Date("Y-m-d")."'";
	$data_zajezd = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zajezd);
	while($zaznam_zajezd = mysqli_Fetch_Array($data_zajezd)){
		$text.="<zajezd> 
					<termin_od>".$zaznam_zajezd["od"]."</termin_od>
					<termin_do>".$zaznam_zajezd["do"]."</termin_do>
					<poznamky>".xml_valid(strip_tags($zaznam_zajezd["nazev_zajezdu"]." ".$zaznam_zajezd["poznamky_zajezd"]))."</poznamky>";

		$dotaz_cena="SELECT * FROM `cena` natural join `cena_zajezd`  where `id_zajezd`=".$zaznam_zajezd["id_zajezd"]." and  `id_serial`=".$zaznam["id_serial"]." and `cena_zajezd`.`nezobrazovat`<>1";
		$data_cena = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_cena);
		while($zaznam_cena = mysqli_Fetch_Array($data_cena)){
			$text.="<cena zakladni=\"".$zaznam_cena["zakladni_cena"]."\">
						<popis_ceny>".$zaznam_cena["nazev_ceny"]."</popis_ceny>
						<velikost_ceny>".$zaznam_cena["castka"]."</velikost_ceny>
						</cena>";
						
		}//cena
		$text.="</zajezd>";
	}//zajezd

	$text.="</serial>	";
}//serial

$text.="	</seznam_serialu>
";

fwrite ($fp, $text);
fclose ($fp);
echo "vse vygenerovano OK<br/>";
echo "Vytvarim zaznam do databaze dokumentu...<br/>";

			$dotaz='SELECT `id_dokument` FROM `dokument` WHERE `dokument_url`="'.$adresa_ck.'xml/xml-'.$typ_serialu.'.xml"';
				//echo $dotaz;
			$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
			$zaznam = mysqli_Fetch_Array($data);
			if(mysqli_num_rows($data)==0){				
				$spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,'INSERT INTO `dokument` (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`) VALUES("'.Date("Y-m-d").'","XML dokument '.$typ_serialu.' ","datum vytvoreni:'.Date("Y-m-d H:i:s").'","'.$adresa_ck.'xml/xml-'.$typ_serialu.'.xml") ');
				$autoid=mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
				if ($spravne){
					?><h4 style="color:green">dokument byl uspesne vytvoren</h4><?php
					}else{
                                            echo mysqli_error($GLOBALS["core"]->database->db_spojeni);
					?><h4 style="color:black">vytvoreni dokumentu se nepovedlo (xml bylo vygenerovano, ale nebylo ulozeno v databazi)</h4><?php
				}
			}else{
                            $update_dotaz = 'UPDATE `dokument` set `datum_vytvoreni` = "'.Date("Y-m-d").'", `popisek_dokument` = "datum vytvoreni:'.Date("Y-m-d H:i:s").'" where
                                            `id_dokument` =  '.$zaznam["id_dokument"].'  ';
                            $spravne = mysqli_query($GLOBALS["core"]->database->db_spojeni,$update_dotaz);
                            echo mysqli_error($GLOBALS["core"]->database->db_spojeni);
                            ?><h4 style="color:green">dokument jiz existuje (drivejsi verze)</h4><?php
                            
			}

}//of else fopen
}//of function generate_xml

		
?>


