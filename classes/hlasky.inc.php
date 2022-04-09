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
<strong>Leteck� z�jezdy</strong> na LOH 2012 v Lond�n� tvo�� z�klad na�� nab�dky z�jezd�. 
Nab�z�me opravdu �irok� v�b�r term�n� i typ�  leteck�ch z�jezd� na jednotliv� ��sti lond�nsk� olympi�dy. 
Leteck� z�jezdy jsou zaji��ov�ny s vyu�it�m renomovan�ho leteck�ho dopravce British Airways.<BR>
Ka�d� z�jezd je s ubytov�n�m na 2 a� 5 noci a tedy s mo�nost� str�vit na olympi�d� v Lond�n� 3 a� 6 dn�.

";
                break;
            case "autokarem":
                return "
<strong>Autokarov� z�jezdy</strong> na LOH 2012 v Lond�n� maj� podobnou skladbu program�, jako leteck� z�jezdy. Li�� se p�edev��m v nab�dce ubytov�n� a tak� v cen� z�jezd�.

 

";
                break;
            case "vlastni-doprava":
                return "
P�ipavujeme i nab�dku speci�ln�ch bal��k� <strong>\"vstupenka + ubytov�n�\"</strong>. Tato nab�dka v�ak nebude k dispozici patrn� d��ve, ne� v druh� polovin� roku 2011. ";
                break;  
            case "olympic-dream":
                return "
Do programu z�jezd� <strong>Olympic Dream</strong> vyb�r�me olympijsk� sout�e tak, aby si sportovn� fanou�ek mohl vychutnat a u��t r�zn� sportovn� discipl�ny a sout�e na Letn�ch olympijsk�ch hr�ch 2012.
Za��t neopakovatelnu atmosf�ru olympijsk�ch her je dozajista snem ka�d�ho sportovn�ho fanou�ka. V l�t� 2012 si jej m��ete splnit.<BR>
Co byste si nem�li nechat p�i n�v�t�v� lond�nsk� olympid�y uj�t:<BR>

Zelen� tr�vn�k ve <B>Wimbledonu</B> - chr�m v�ech tenist�. Centr�ln� kurt, kter� za�il legend�rn� v�t�zstv� Jana Kode�e i Martiny Navr�tilov�.<BR>
Fotbalov� stadion ve <B>Wembley</B> s neopakovatelnou atmosf�rou 90.000 div�k�.<BR>
ڞasnou architektonickou harmonii nov�ho sportovn�ho centra - <B>Olympijsk�ho parku</B>. P�edev��m pak Olympijsk� stadion, velodrom �i plaveck� are�l.<BR>
N�v�t�vu are�lu <B>vodn�ho slalomu</B> v Lee Valley. Jistotou je to, �e a� na toto sportovi�t� doraz�te kdykoliv, v�dy budou na�i reprezetnati bojovat o nejvy��� p���ky.<BR>
Majest�tn� atmosf�ru<B> beachvolejbalov�ho</B> st�nku uprost�ed historick�ho Lond�na.<BR>
Vzru�uj�c� atmosf�ru <B>atletick�ch sout��</B> na  Olympijsk�m stadionu. Tuhle n�v�t�vu nem��ete vynechat, proto�e atletika je kr�lovnou sport�.<BR>
Basketbalov� z�pasy turnaje �en a p�edev��m pak vicemistry� sv�ta, <B>basketbalistek �esk� republiky</B> v �ele s Hanou Hor�kovou.<BR>
Cyklistickou �asovku �en s ��ast� dvojn�sobn� olympijsk� v�t�zky, rychlobrusla�ky <B>Martiny S�bl�kov�</B>. 

 
";
                break;
            case "olympijske-hity":
                return "
Program <strong>Olympijsk� hity</strong> nab�dne sportovn�m fanou�k�m v�dy v dan�m term�nu nejlep�� �i nejo�ek�van�j�� sportovn� souboje LOH 2012.
Mezi vrcholy olympijsk�ch her bude dozajista pat�it:<BR>
01.8. plav�n� - fin�le mu�� 100 m voln� zp�sob<BR>
05.8. atletick� fin�le v b�hu na 100 m mu��<BR>
05.8. tenisov� fin�le dvouhry mu�� ve Wimbledonu<BR>
5.8.- 7.8. fin�le ve sportovn� gymnastice na jednotliv�ch n��ad�ch mu�� i �en<BR>
07.8. triatlon - mu�i<BR>
09.8. beachvolejbalov� fin�le mu��<BR>
12.8. volejbalov� fin�le mu��<BR>
12.8. h�zen� -  fin�le mu��<BR>
<BR>
V�echny tyto vrcholy lond�nsk� olympi�dy i �adu dal��ch m��ete nal�zt v nab�dce na�ich z�jezd�.

";
                break;
            case "ceske-hvezdy":
                return "
V programu <strong>�esk� hv�zdy</strong> jsou za�azeny ty sportovn� discipl�ny, kde p�edpokl�d�me �sp�chy �i medailov� boje �esk�ch sportovc�. <BR>
Nejv�t�� �esk� medailov� nad�je jsou spojeny p�edev��m s t�mito sportovci �i olympijsk�mi sout�emi:<BR>
<B>Ond�ej Synek</B>, veslov�n� - skif:  semifin�lov� j�zdy 1.8., fin�le 3.8.<BR>
<B>Deblkanoe</B> (C2), vodn� slalom: semifin�le a fin�le 2.8.<BR>
<B>Barbora �pot�kov�</B>, atletika / hod o�t�pem: fin�le 9.8.<BR>


";
                break;
            case "slovenske-hvezdy":
                return "
R�di bychom p�ipravili i n�kolik  program� typu <strong>Slovensk� hv�zdy</strong>. 
Do t�chto z�jezd� chceme za�adit ty sportovn� discipl�ny, kde p�edpokl�d�me �sp�chy �i medailov� boje slovensk�ch sportovc�.
Zat�m m��eme slovensk�m sportovn�m fanou�k�m alespo� nab�dnout mo�nost platby v Eurech na ��et veden� na Slovensku.<BR>
R�di bychom t� slovensk�m fand�m nab�dli v p��pad� leteck�ch z�jezd� mo�nost odletu z V�dn� �i z Budape�ti.  ";
                break;
            default:
                break;
        }
    }


}


 ?>
