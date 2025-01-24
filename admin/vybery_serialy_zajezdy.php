<?php

require_once "./core/load_core.inc.php";

require_once "./classes/dataContainers/tsTypSerial.php";
require_once "./classes/dataContainers/tsZeme.php";
require_once "./classes/dataContainers/tsSerial.php";
require_once "./classes/dataContainers/tsZajezd.php";
require_once "./classes/dataContainers/tsObjekt.php";

require_once "./classes/vybery/VyberyModel.php";
require_once "./classes/vybery/VyberyController.php";
require_once "./classes/vybery/VyberyView.php";
require_once "./classes/vybery/VyberySQLBuilder.php";
require_once "./classes/vybery/VyberyUtils.php";

require_once "../global/lib/model/holders/SerialHolder.php";
require_once "../global/lib/db/DBResult.php";
require_once "../global/lib/model/filters/AbstractFilter.php";
require_once "../global/lib/model/filters/SerialFilter.php";
require_once "../global/lib/utils/CommonUtils.php";

require_once "./classes/vybery/VyberyDAO.php";
require_once "./classes/vybery/VyberyModelConfig.php";

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

$model = new VyberyModel();
$view = new VyberyView($model);
$ctrl = new VyberyController($model, $view);

$ctrl->dispatchRequests();

