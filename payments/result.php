<?php

require_once './common.php';

try {
    // get transaction status from my database
    $status = $paymentsDatabase->getTransactionStatus(
        $_GET['id'], // transId
        $_GET['refId'] // refId
    );
    header("Location: http://slantour.cz/dekujeme.php?refid=" . $_GET['refId']);
} catch (Exception $e) {
    header("Location: http://slantour.cz/dekujeme.php?status=" . SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_ERROR);
}

