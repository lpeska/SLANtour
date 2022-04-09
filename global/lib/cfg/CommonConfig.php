<?php

class CommonConfig {
    const DEBUG = true;
    const SLANTOUR_PHOTO_NAHLED_URL = '/foto/nahled/';

    const EMAIL_GLOBAL_SENDER = 'info@slantour.cz';
    const FAKTURA_PROVIZE_PDF_FOLDER_URL = '/classes/faktura_provize/res/pdf';

    /**
     * Konstanty neumoznuji pouziti __DIR__
     * @return string
     */
    public static function GET_FAKTURA_PROVIZE_PDF_FOLDER() {

        return __DIR__."/../../../classes/faktura_provize/res/pdf";
    }
}