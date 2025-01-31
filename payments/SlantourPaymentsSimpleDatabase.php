<?php


class SlantourPaymentsSimpleDatabase
{
    const TRANSACTION_STATUS_PENDING = 'PENDING';
    const TRANSACTION_STATUS_PAID = 'PAID';
    const TRANSACTION_STATUS_CANCELED = 'CANCELLED';
    const TRANSACTION_STATUS_ERROR = 'ERROR';

    private $TBL_PLATBA_AGMO = 'platba_agmo';
    private $TBL_OBJEDNAVKA_PLATBA = 'objednavka_platba';

    /**
     * @var Database
     */
    private $_database;

    private $_merchant;
    private $_test;

    /**
     * @param string $merchant
     *      merchants identifier
     * @param boolean $test
     *      TRUE = testing system variant
     *      FALSE = release (production) system variant
     * @internal param string $dataFolderName folder name where to save data*      folder name where to save data
     */
    public function __construct($merchant, $test)
    {
        $this->_database = Database::get_instance();
        $this->_merchant = $merchant;
        $this->_test = $test;
    }

    /**
     * returns next numeric identifier for a merchant transaction
     *
     * @return int
     * @throws Exception
     */
    public function createNextRefId()
    {
        $sql = "SHOW TABLE STATUS LIKE '$this->TBL_PLATBA_AGMO';";

        $this->_database->connect();
        $result = $this->_database->query($sql);
        $this->_database->close();

        if (!$result) {
            $error = error_get_last();
            throw new Exception('Cannot read refId from database \n\n' . $error['message']);
        }

        $row = mysqli_fetch_object($result);
        $refId = $row->Auto_increment;

        return $refId;
    }

    /**
     * store the transaction data in a database
     *
     * @param string $transId
     * @param int $refId
     * @param float $price
     * @param string $currency
     * @param string $status
     * @param int $objId
     *
     * @throws Exception
     *
     */
    public function saveTransaction($transId, $refId, $price, $currency, $status, $objId)
    {
        $datetime = date("Y-m-d H:i:s");
        $sql = "INSERT INTO $this->TBL_PLATBA_AGMO
                    (ref_id, transaction_id, id_objednavka, price, currency, status, test, created)
                VALUES
                    ($refId, '$transId', $objId, $price, '$currency', '$status', ".intval($this->_test).", '$datetime');";

        $this->_database->connect();
        $result = $this->_database->query($sql);
        $this->_database->close();

        if (!$result) {
            $error = error_get_last();
            throw new Exception('Cannot write transaction into database \n\n' . $error['message']);
        }
    }

    /**
     * returns transaction status from a database
     *
     * @param string $transId
     * @param string $refId
     *
     * @return string
     * @throws Exception
     */
    public function getTransactionStatus($transId, $refId)
    {
        $sql = "SELECT status FROM $this->TBL_PLATBA_AGMO WHERE ref_id = $refId && transaction_id = '$transId';";

        $this->_database->connect();
        $result = $this->_database->query($sql);
        $this->_database->close();

        $row = mysqli_fetch_object($result);
        if (!$row) {
            throw new Exception('Unknown transaction');
        }

        return $row->status;
    }

    public function getTransactionId($refId)
    {
        $refId = (int)$refId;
        $sql = "SELECT transaction_id FROM $this->TBL_PLATBA_AGMO WHERE ref_id = $refId ;";

        $this->_database->connect();
        $result = $this->_database->query($sql);
        $this->_database->close();

        $row = mysqli_fetch_object($result);
        if (!$row) {
            throw new Exception('Unknown transaction');
        }

        return $row->transaction_id;
    }

    public function getRow($refId)
    {
        $refId = (int)$refId;
        $sql = "SELECT * FROM $this->TBL_PLATBA_AGMO WHERE ref_id = $refId ;";

        $this->_database->connect();
        $result = $this->_database->query($sql);
        $this->_database->close();

        $row = mysqli_fetch_object($result);
        if (!$row) {
            throw new Exception('Unknown transaction');
        }

        return $row;
    }


    /**
     * checks transaction parameters in a database
     *
     * @param string $transId
     * @param string $refId
     * @param float $price
     * @param string $currency
     *
     * @throws Exception
     */
    public function checkTransaction($transId, $refId)
    {
        $sql = "SELECT test, price, currency FROM $this->TBL_PLATBA_AGMO WHERE ref_id = $refId && transaction_id = '$transId';";

        $this->_database->connect();
        $result = $this->_database->query($sql);
        $this->_database->close();

        $row = mysqli_fetch_object($result);

        //v db je test jako cislo, kdezto tady je jako true/false - pri ukladani do db si s tim poradi mysql samo, ale pri cteni to musi resit PHP
        if (($row->test ? true : false) !== $this->_test ) {
            throw new Exception('Invalid payment parameters');
        }
    }

    /**
     * update status of transaction in a databse
     *
     * @param string $transId
     * @param int $refId
     * @param float $price
     * @param string $currency
     * @param string $status
     * @param string $method
     *
     * @throws Exception
     */
    public function updateTransaction($transId, $refId, $status)
    {
        $sql = "UPDATE $this->TBL_PLATBA_AGMO
                SET status='$status'
                WHERE ref_id = $refId && transaction_id = '$transId';";

        $this->_database->connect();
        $result = $this->_database->query($sql);
        $this->_database->close();

        if (!$result) {
            $error = error_get_last();
            throw new Exception('Cannot update transaction in databse \n\n' . $error['message']);
        }
    }

    /**
     * create order payment in database
     * @param int $transId
     * @param string $refId
     * @param string $method
     * @return int order id
     *
     * @throws Exception
     */
    public function createPayment($transId, $refId, $method)
    {
        $date = date("Y-m-d");
        $this->_database->connect();
        $sql_objednavka =  "SELECT id_objednavka, price, '$method', '$date' as vystaveno, '$date' as splaceno FROM $this->TBL_PLATBA_AGMO WHERE ref_id = $refId && transaction_id = '$transId'";
        $result_objednavka = $this->_database->query($sql_objednavka);
        $row_objednavka = mysqli_fetch_array($result_objednavka);
        
        $sql = "INSERT INTO $this->TBL_OBJEDNAVKA_PLATBA
                    (id_objednavka, castka, zpusob_uhrady, vystaveno, splaceno)
                    SELECT id_objednavka, price, '$method', '$date' as vystaveno, '$date' as splaceno FROM $this->TBL_PLATBA_AGMO WHERE ref_id = $refId && transaction_id = '$transId';";

        
        $result = $this->_database->query($sql);
        $INC_DIR = $_SERVER["DOCUMENT_ROOT"]; 
        
         /*

        require_once "$INC_DIR/admin/core/generic_classes.inc.php"; //detail plateb rezervací
        //require_once "$INC_DIR/admin/core/core.inc.php"; //detail plateb rezervací
       // require_once "$INC_DIR/admin/core/uzivatel_zamestnanec.inc.php"; //detail plateb rezervací
        require_once "$INC_DIR/global/library_classes.inc.php"; //detail plateb rezervací
        require_once "$INC_DIR/admin/classes/rezervace.inc.php"; //detail rezervací
        require_once "$INC_DIR/admin/classes/rezervace_list.inc.php"; //detail rezervací
        require_once "$INC_DIR/admin/classes/rezervace_cena.inc.php"; //detail rezervací
        require_once "$INC_DIR/admin/classes/rezervace_osoba.inc.php"; //detail rezervací
        require_once "$INC_DIR/admin/classes/rezervace_platba_list.inc.php"; //seznamy plateb rezervací
        require_once "$INC_DIR/admin/classes/rezervace_platba.inc.php"; //detail plateb rezervací
        require_once "$INC_DIR/admin/classes/rezervace_sleva.inc.php"; //detail plateb rezervací
        
        require_once "$INC_DIR/classes/rezervace_zobrazit.inc.php"; //detail plateb rezervací        
        require_once "$INC_DIR/classes/pdf_objednavka_prepare.inc.php"; //detail plateb rezervací
        require_once "$INC_DIR/classes/pdf_objednavka_prepare_vstupenka.inc.php"; //detail plateb rezervací
        //echo "starting update zbyva zaplatit \n";
        Rezervace::update_zbyva_zaplatit($row_objednavka["id_objednavka"]);   */
        
        $this->_database->close();

        if (!$result) {
            $error = error_get_last();
            throw new Exception('Cannot insert order payment into databse \n\n' . $error['message']);
        }

        return $row_objednavka["id_objednavka"];
    }

    /**
     * check if method is valid string
     * @param string $method
     * @return mixed
     *
     * @throws Exception
     */
    public function validateMethod($method)
    {
        if(in_array($method, EnumPaymentMethods::getAll()))
            return $method;

        $error = error_get_last();
        throw new Exception('Cannot validate transaction method \n\n' . $error['message']);
    }

}
