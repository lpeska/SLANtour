<?php

class ObjednavkaPlatbaView extends ObjednavkaView implements IObjednavkaModelPlatbaObserver
{
    /**
     * @var IObjednavkaModelForView
     */
    private $model;

    /**
     * @param $model IObjednavkaModelForView
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerPlatbaObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelPlatbaChanged()
    {
        echo $this->header();
        echo $this->srcWebHeader();
        echo $this->menu(3);
        echo $this->platba();
        echo $this->buttons();
        echo $this->footer();
    }

    // PRIVATE METHODS *******************************************************************

    private function platba()
    {
        $os = $this->model->getObjednavkaSession();
        $zajezd = $this->model->getZajezd();
        $zpusobPlatby = $os->getZpusobPlatby();
        $zpusobPlatby = is_null($zpusobPlatby) ? CentralniDataEnt::ID_PLATBA_HOTOVE : $zpusobPlatby;
        $zpusobPlatbyList = $this->model->getZpusobyPlateb();

        $isVolnaKapacita = $zajezd->getSluzbaHolder()->isVolnaKapacita($os->getPocetOsob());
        $isVyprodano = $zajezd->getSluzbaHolder()->hasVyprodanoSluzba();
        $isNaDotaz = $zajezd->getSluzbaHolder()->hasNaDotazSluzba();
        $isBlackdays = $zajezd->isInBlackdays(CommonUtils::engDate($os->getTerminOd()), CommonUtils::engDate($os->getTerminDo()));

        $out = "";

        $out .= "<form class='align-center' id='frm-platba' method ='post' action='index.php?page=souhrn'>";
        $out .= "   <fieldset>";
        $out .= "       <legend>Výběr platební metody</legend>";
        $out .= "       <div id='list-platba'>";

//        if(isset($_REQUEST["debug"])) {
//            echo "<pre>"; var_dump($isBlackdays); echo "</pre>";
//            echo "<pre>"; var_dump($isVyprodano); echo "</pre>";
//            echo "<pre>"; var_dump($isNaDotaz); echo "</pre>";
//            echo "<pre>"; var_dump(!$isVolnaKapacita); echo "</pre>";
//        }

        foreach ($zpusobPlatbyList as $zp) {
            if ($zp->id == CentralniDataEnt::ID_PLATBA_KARTOU)
		//continue; //LP EDIT: toto odstranit az zase budeme mit platbu kartou...
                if (($isBlackdays || $isVyprodano || $isNaDotaz || !$isVolnaKapacita))
                    continue;
            $out .= "       <div><input value='$zp->id' " . ($zpusobPlatby == $zp->id ? "checked='checked'" : "") . " type='radio' name='method' id='$zp->id' /><label class='optional' for='$zp->id'><span class='ico $zp->class float-left'></span><span class='text'>$zp->nazevWeb</span><span class='clearfix'></span></label></div>";
        }

        $out .= "       </div>";
        $out .= "   </fieldset>";
        $out .= "   <fieldset>";
        $out .= "       <legend>Informace o platební metodě</legend>";
        $out .= "       <div id='platba-info'>";

        foreach ($zpusobPlatbyList as $zp) {
            $display = $zpusobPlatby != $zp->id ? "class='no-display'" : "";
            $out .= "           <div $display id='info-$zp->id'>";
            $out .= "               <p>$zp->text</p>";
            $out .= "           </div>";
        }

        $out .= "       </div>";
        $out .= "   </fieldset>";
        $out .= "</form>";

        return $out;
    }

    private function buttons()
    {
        $out = "";

        $out .= "   <div id='order-btns'>";
        $out .= "       <a class='btn-round btn-medium btn-green btn-arrow-r-white-medium float-right' id='btn-s3-vybrat' href=''>Vybrat</a>";
        $out .= "       <a class='btn-round btn-small btn-white btn-arrow-l-black-small float-left' href='index.php?page=" . ObjednavkaController::PAGE_OSOBNI_UDAJE . "' id='btn-s3-zpet'>Zpět</a>";
        $out .= "   </div>";

        return $out;
    }
}