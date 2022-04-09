<?php

class VarovnaZprava {   
    private $text;
    private $barva;
    private $bgBarva;
    const STYL_DEFAULT = 0;
    const STYL_OBJ_KONECNY_VYPIS = 1;
    
    function __construct($text, $barva, $bgBarva) {
        $this->text = $text;
        $this->barva = $barva;
        $this->bgBarva = $bgBarva;
    }
    
    function vypis($styl = STYL_DEFAULT) {
        $vypis = "";
        
        if($styl == "default") {
            
        } else if($styl == VarovnaZprava::STYL_OBJ_KONECNY_VYPIS) {
            $vypis .= "<div style='background-color: $this->bgBarva; padding: 5px;'>";
            $vypis .= "<h2 style='color: $this->barva'>Varování</h2>$this->text";
            $vypis .= "</div><br/>";
        }
        
        return $vypis;
    }        
}

?>
