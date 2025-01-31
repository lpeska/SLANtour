<?php

//include phpmailer
require_once dirname(__FILE__) . '/../phpMailer/minimal/class.phpmailer.php';

//include utils
require_once dirname(__FILE__) . '/SlantourPaymentsUtils.php';

// include agmo libraries
require_once dirname(__FILE__) . '/AgmoPaymentsSimpleProtocol.php';
require_once dirname(__FILE__) . '/SlantourPaymentsSimpleDatabase.php';
require_once dirname(__FILE__) . '/Database.php';
require_once dirname(__FILE__) . '/EnumPaymentMethods.php';

// include configuration
require_once dirname(__FILE__) . '/config.php';

// initialize payments data object
$paymentsDatabase = new SlantourPaymentsSimpleDatabase(
    $config['merchant'],
    $config['test']
);

// initialize payments protocol object
$paymentsProtocol = new AgmoPaymentsSimpleProtocol(
    $config['paymentsUrl'],
    $config['merchant'],
    $config['test'],
    $config['certDirPath'],
    $config['secret']
);
