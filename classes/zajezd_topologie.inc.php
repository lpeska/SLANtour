<?php
/** 
* trida pro zobrazení seznamu cen zájezdu vè. kapacit
*/
/*------------------- SEZNAM CEN -------------------  */
/*rozsireni tridy Serial_with_zajezd o seznam cen*/
class Zajezd_topologie extends Generic_list{
	protected $id_zajezdu;
	protected $id_serialu;
	protected $pocet_cen;
	protected $last_typ_ceny;
        protected $celkova_castka;
	protected $vse_volne; //funguje az po precteni seznamu cen!! hlasi, zda jsou vsechny ceny volne
	private $id_pole;
	public $database; //trida pro odesilani dotazu
	private $scriptStart;
        private $scriptEnd;
	//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì id serialu a zajezdu*/
	function __construct($id_tok_topologie){
            require_once "admin/classes/dataContainers/tsTopologie.php";
            require_once "admin/classes/dataContainers/tsPolozkaTopologie.php";
            
            require_once "admin/classes/ts/topologie_dao.inc.php";
           // require_once "admin/classes/ts/objednavka_dao.inc.php";
            require_once "admin/classes/ts/topologie_displayer.inc.php";
            require_once "admin/classes/ts/topologie_ts.inc.php";
            require_once "admin/classes/ts/utils_ts.inc.php";            
		//trida pro odesilani dotazu
				
            $this->id_tok_topologie = $this->check_int($id_tok_topologie);
            $topologieDisplayer = new TopologieDisplayer($this->id_tok_topologie,1);
            $html .= $topologieDisplayer->getHeaderKlient();
            //polozky
            $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:2px;width:190px;\" >  "
                . $topologieDisplayer->getPolozkyKlient().
                "</table>";
            //pata
            $html .= $topologieDisplayer->getFooterKlient();
            
            $this->html =  $html;
	}	
        public function get_html(){
            return $this->html;
        }
        static function get_id_tok_topologie($id_zajezd) {
            $sql = "SELECT * FROM `zajezd_tok_topologie` 
                join topologie_tok on (topologie_tok.id_tok_topologie = zajezd_tok_topologie.id_tok_topologie and topologie_tok.zobrazit_topologii=1) 
                WHERE `id_zajezd` = ".intval($id_zajezd)."";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $ids = array();
            while ($row = mysqli_fetch_array($data)) {
                $ids[] = $row["id_tok_topologie"];
            }
            return $ids;
        }
//------------------- METODY TRIDY -----------------	
	
}




?>
