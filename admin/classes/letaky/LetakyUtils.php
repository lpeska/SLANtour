<?php

//todo tahle trida byla asi zamyslena jako trida pro editaci view voucheru, tzn pro editaci vsech udaju co se maji promitnout v PDF, takze jeste neni nikde vyuzita? Radeji zkontrolovat.
class LetakyUtils
{
public static function typLetaku($serial)
    { 
        
    
        switch ($serial->dataSerial->doprava) {
            case 2:
                return "Autokarov� z�jezd";
                break;
            case 3:
                return "Leteck� z�jezd";
                break;
        }
    }

   
}

?>
