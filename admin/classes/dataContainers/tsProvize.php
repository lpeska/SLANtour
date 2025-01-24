<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsProvize
 *
 * @author Pesi
 */
class tsProvize {
    public $poznamka_provize;
    public $suma_provize;
    public $provize_vc_dph;

    public function __construct($poznamka_provize, $suma_provize, $provize_vc_dph){
        $this->poznamka_provize = $poznamka_provize;
        $this->suma_provize = $suma_provize;
        $this->provize_vc_dph = $provize_vc_dph;
    }
}

?>
