<?php

require_once "./core/load_core.inc.php";

/* GLOBAL */
//cfg
require_once "../global/lib/cfg/CommonConfig.php";
require_once "../global/lib/cfg/DatabaseConfig.php";
require_once "../global/lib/cfg/ViewConfig.php";
//db
require_once "../global/lib/db/DatabaseProvider.php";
require_once "../global/lib/db/SQLQuery.php";
require_once "../global/lib/db/DBResult.php";


require_once "../global/lib/db/dao/SerialDAO.php";
require_once "../global/lib/db/dao/sql/SerialSQLBuilder.php";
require_once "./classes/ts/objednavka_dao.inc.php";

//utils
require_once "../global/lib/utils/CommonUtils.php";
require_once "../global/lib/utils/ViewUtils.php";

/* LOCAL */

require_once "./classes/dataContainers/tsSluzba.php";
require_once "./classes/dataContainers/tsZajezd.php";
require_once "./classes/dataContainers/tsSerial.php";
require_once "./classes/dataContainers/tsSleva.php";
require_once "./classes/dataContainers/tsFoto.php";

require_once "./classes/letaky/iface/ILetakyModelEditObserver.php";
require_once "./classes/letaky/iface/ILetakyModelPdfObserver.php";

require_once "./classes/letaky/LetakyFilter.php";
require_once "./classes/letaky/LetakyUtils.php";
require_once "./classes/letaky/LetakyModel.php";
require_once "./classes/letaky/LetakyModelConfig.php";
require_once "./classes/letaky/LetakyController.php";
require_once "./classes/letaky/LetakyViewEdit.php";
require_once "./classes/letaky/LetakyViewPdf.php";
//require_once "./classes/letaky/LetakyViewEmail.php";

require_once "./classes/letaky/entities/LetakyZajezdHolder.php";

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

SerialDAO::init();

$model = new LetakyModel();
$ctrl = new LetakyController($model);
$viewMain = new LetakyViewEdit($model);
$viewPdf = new LetakyViewPdf($model);

$ctrl->dispatchRequests();

