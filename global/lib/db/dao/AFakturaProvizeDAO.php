<?php

class AFakturaProvizeDAO
{
    /**
     * @var DatabaseProvider
     */
    private static $db;

    public static function init()
    {
        self::$db = DatabaseProvider::get_instance();
    }

    public static function readFakturyProvize($filter)
    {
        self::$db->connect();

        //obj
        $faktury = null;
        $stmt = self::$db->query(AFakturaProvizeSQLBuilder::readFakturyProvizeSQL($filter));
        $stmtFR = self::$db->query(SQLBuilder::readFoundRows());
        $foundRows = $stmtFR->fetch(PDO::FETCH_OBJ)->cnt;

        while(@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $faktura = new FakturaEnt($row->id_faktury, $row->cislo_faktury, null, $row->celkova_castka, $row->datum_vystaveni, null, $row->zaplaceno);
            $faktura->pdfFilename = $row->id_objednavka.".pdf";
            $faktura->poznamka = $row->poznamka;
            $pdfFilePath = CommonConfig::GET_FAKTURA_PROVIZE_PDF_FOLDER() . $faktura->pdfFilename;
            //$faktura->pdfFilename = file_exists($pdfFilePath) ? "/$row->id_objednavka.pdf" : null;
            $faktura->pdfFilename = "/$row->id_objednavka.pdf";

            //objednavka
            $objednavka = new ObjednavkaEnt($row->id_objednavka, null, null, $row->celkova_cena, null, null, $row->datum_rezervace, $row->stav, $row->termin_od, $row->termin_do, null, null);
            //prodejce
            $prodejce = new OrganizaceEnt($row->id_organizace, $row->nazev, $row->ico, $row->dic, $row->role);
            $objednavka->setProdejce($prodejce);
            //objednatel
            $objednavajici = new UserKlientEnt($row->id_klient, $row->titul, $row->jmeno, $row->prijmeni, $row->email, $row->telefon);
            $objednavka->objednavajici = $objednavajici;
            //serial
            $serial = new SerialEnt($row->id_serial, $row->s_nazev, null);
            $objednavka->setSerial($serial);

            $faktura->objednavka = $objednavka;
            $faktury[] = $faktura;
        }

        self::$db->close();

        return new DBResult($faktury, $foundRows);
    }

    public static function payFakturaById($idFaktura)
    {
        self::$db->connect();

        //obj
        $ucastnici = null;
        $query = AFakturaProvizeSQLBuilder::payFakturaByIdSQL([$idFaktura]);
        self::$db->prepare($query->sql);
        $result = self::$db->execute($query->params);

        self::$db->close();

        return $result;
    }

}