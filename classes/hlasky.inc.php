<?php
/** 
* trida pro zobrazeni seznamu serialu
*/
/*--------------------- SEZNAM SERIALU -----------------------------*/
/**jednodussi verze - vstupni parametry pouze typ, podtyp, zeme, zacatek vyberu a order by
    - odpovida dotazu z katalogu zajezdu
*/

class Hlasky{

    /**vytvoreni dotazu ze zadanych parametru*/
    static function get_hlaska($key){
        switch ($key) {
            case "letecky":
                return "
<strong>Letecké zájezdy</strong> na LOH 2012 v Londınì tvoøí základ naší nabídky zájezdù. 
Nabízíme opravdu širokı vıbìr termínù i typù  leteckıch zájezdù na jednotlivé èásti londınské olympiády. 
Letecké zájezdy jsou zajišovány s vyuitím renomovaného leteckého dopravce British Airways.<BR>
Kadı zájezd je s ubytováním na 2 a 5 noci a tedy s moností strávit na olympiádì v Londınì 3 a 6 dní.

";
                break;
            case "autokarem":
                return "
<strong>Autokarové zájezdy</strong> na LOH 2012 v Londınì mají podobnou skladbu programù, jako letecké zájezdy. Liší se pøedevším v nabídce ubytování a také v cenì zájezdù.

 

";
                break;
            case "vlastni-doprava":
                return "
Pøipavujeme i nabídku speciálních balíèkù <strong>\"vstupenka + ubytování\"</strong>. Tato nabídka však nebude k dispozici patrnì døíve, ne v druhé polovinì roku 2011. ";
                break;  
            case "olympic-dream":
                return "
Do programu zájezdù <strong>Olympic Dream</strong> vybíráme olympijské soutìe tak, aby si sportovní fanoušek mohl vychutnat a uít rùzné sportovní disciplíny a soutìe na Letních olympijskıch hrách 2012.
Zaít neopakovatelnu atmosféru olympijskıch her je dozajista snem kadého sportovního fanouška. V létì 2012 si jej mùete splnit.<BR>
Co byste si nemìli nechat pøi návštìvì londınské olympidáy ujít:<BR>

Zelenı trávník ve <B>Wimbledonu</B> - chrám všech tenistù. Centrální kurt, kterı zail legendární vítìzství Jana Kodeše i Martiny Navrátilové.<BR>
Fotbalovı stadion ve <B>Wembley</B> s neopakovatelnou atmosférou 90.000 divákù.<BR>
Úasnou architektonickou harmonii nového sportovního centra - <B>Olympijského parku</B>. Pøedevším pak Olympijskı stadion, velodrom èi plaveckı areál.<BR>
Návštìvu areálu <B>vodního slalomu</B> v Lee Valley. Jistotou je to, e a na toto sportovištì dorazíte kdykoliv, vdy budou naši reprezetnati bojovat o nejvyšší pøíèky.<BR>
Majestátní atmosféru<B> beachvolejbalového</B> stánku uprostøed historického Londına.<BR>
Vzrušující atmosféru <B>atletickıch soutìí</B> na  Olympijském stadionu. Tuhle návštìvu nemùete vynechat, protoe atletika je královnou sportù.<BR>
Basketbalové zápasy turnaje en a pøedevším pak vicemistryò svìta, <B>basketbalistek Èeské republiky</B> v èele s Hanou Horákovou.<BR>
Cyklistickou èasovku en s úèastí dvojnásobné olympijské vítìzky, rychlobruslaøky <B>Martiny Sáblíkové</B>. 

 
";
                break;
            case "olympijske-hity":
                return "
Program <strong>Olympijské hity</strong> nabídne sportovním fanouškùm vdy v daném termínu nejlepší èi nejoèekávanìjší sportovní souboje LOH 2012.
Mezi vrcholy olympijskıch her bude dozajista patøit:<BR>
01.8. plavání - finále muù 100 m volnı zpùsob<BR>
05.8. atletické finále v bìhu na 100 m muù<BR>
05.8. tenisové finále dvouhry muù ve Wimbledonu<BR>
5.8.- 7.8. finále ve sportovní gymnastice na jednotlivıch náøadích muù i en<BR>
07.8. triatlon - mui<BR>
09.8. beachvolejbalové finále muù<BR>
12.8. volejbalové finále muù<BR>
12.8. házená -  finále muù<BR>
<BR>
Všechny tyto vrcholy londınské olympiády i øadu dalších mùete nalézt v nabídce našich zájezdù.

";
                break;
            case "ceske-hvezdy":
                return "
V programu <strong>Èeské hvìzdy</strong> jsou zaøazeny ty sportovní disciplíny, kde pøedpokládáme úspìchy èi medailové boje èeskıch sportovcù. <BR>
Nejvìtší èeské medailové nadìje jsou spojeny pøedevším s tìmito sportovci èi olympijskımi soutìemi:<BR>
<B>Ondøej Synek</B>, veslování - skif:  semifinálové jízdy 1.8., finále 3.8.<BR>
<B>Deblkanoe</B> (C2), vodní slalom: semifinále a finále 2.8.<BR>
<B>Barbora Špotáková</B>, atletika / hod oštìpem: finále 9.8.<BR>


";
                break;
            case "slovenske-hvezdy":
                return "
Rádi bychom pøipravili i nìkolik  programù typu <strong>Slovenské hvìzdy</strong>. 
Do tìchto zájezdù chceme zaøadit ty sportovní disciplíny, kde pøedpokládáme úspìchy èi medailové boje slovenskıch sportovcù.
Zatím mùeme slovenskım sportovním fanouškùm alespoò nabídnout monost platby v Eurech na úèet vedenı na Slovensku.<BR>
Rádi bychom té slovenskım fandùm nabídli v pøípadì leteckıch zájezdù monost odletu z Vídnì èi z Budapešti.  ";
                break;
            default:
                break;
        }
    }


}


 ?>
