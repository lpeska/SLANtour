<?php

require_once "./core/load_core.inc.php";

require_once "./classes/dataContainers/tsObjekt.php";
require_once "./classes/dataContainers/tsSerial.php";
require_once "./classes/dataContainers/tsZajezd.php";
require_once "./classes/dataContainers/tsOsoba.php";
require_once "./classes/dataContainers/tsAdresa.php";
require_once "./classes/dataContainers/tsObjednavka.php";
require_once "./classes/dataContainers/tsOrganizace.php";
require_once "./classes/dataContainers/tsObjednavajici.php";
require_once "./classes/dataContainers/tsProdejce.php";
require_once "./classes/dataContainers/tsSluzba.php";
require_once "./classes/dataContainers/tsZeme.php";

require_once "./classes/seznamy_ucastniku/ifaces/ISeznamyUcastnikuModelSerialyObserver.php";
require_once "./classes/seznamy_ucastniku/ifaces/ISeznamyUcastnikuModelEmailObserver.php";
require_once "./classes/seznamy_ucastniku/ifaces/ISeznamyUcastnikuModelPdfObserver.php";
require_once "./classes/seznamy_ucastniku/ifaces/ISeznamyUcastnikuModelUcastniciObserver.php";
require_once "./classes/seznamy_ucastniku/ifaces/ISeznamyUcastnikuModelZajezdyObserver.php";

require_once "../global/lib/model/holders/SerialHolder.php";
require_once "../global/lib/db/DBResult.php";
require_once "../global/lib/model/filters/AbstractFilter.php";
require_once "../global/lib/model/filters/SerialFilter.php";
require_once "../global/lib/db/SQLQuery.php";
require_once "../global/lib/db/dao/sql/SQLBuilder.php";
require_once "../global/lib/db/dao/sql/SeznamyUcastnikuSQLBuilder.php";
require_once "../global/lib/db/dao/SeznamyUcastnikuDAO.php";
require_once "../global/lib/utils/CommonUtils.php";

require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuUtils.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuModel.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuModelConfig.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuController.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuView.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuViewSerialy.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuViewZajezdy.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuViewUcastnici.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuViewPdf.php";
require_once "./classes/seznamy_ucastniku/SeznamyUcastnikuViewEmail.php";

require_once "../phpMailer/minimal/class.phpmailer.php";

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

$model = new SeznamyUcastnikuModel();
$ctrl = new SeznamyUcastnikuController($model);
new SeznamyUcastnikuViewSerialy($model);
new SeznamyUcastnikuViewZajezdy($model);
new SeznamyUcastnikuViewUcastnici($model);
new SeznamyUcastnikuViewPdf($model);
new SeznamyUcastnikuViewEmail($model);

$ctrl->dispatchRequests();

