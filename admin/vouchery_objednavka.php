<?php

require_once "./core/load_core.inc.php";

require_once "./classes/dataContainers/tsObjednavajici.php";
require_once "./classes/dataContainers/tsObjednavka.php";
require_once "./classes/dataContainers/tsOsoba.php";
require_once "./classes/dataContainers/tsPlatba.php";
require_once "./classes/dataContainers/tsProdejce.php";
require_once "./classes/dataContainers/tsSluzba.php";
require_once "./classes/dataContainers/tsZajezd.php";
require_once "./classes/dataContainers/tsProvize.php";
require_once "./classes/dataContainers/tsSleva.php";
require_once "./classes/dataContainers/tsSmluvniPodminky.php";
require_once "./classes/dataContainers/tsObjekt.php";
require_once "./classes/dataContainers/tsObjektovaKategorie.php";
require_once "./classes/dataContainers/tsOrganizace.php";
require_once "./classes/dataContainers/tsAdresa.php";

require_once "./classes/ts/objednavka_dao.inc.php";
require_once "./classes/ts/objednavka_displayer.inc.php";
require_once "./classes/ts/objednavka_ts.inc.php";
require_once "./classes/ts/utils_ts.inc.php";

require_once "./classes/vouchery/iface/IVoucheryModelEditObserver.php";
require_once "./classes/vouchery/iface/IVoucheryModelPdfObserver.php";
require_once "./classes/vouchery/iface/IVoucheryModelEmailObserver.php";
require_once "./classes/vouchery/VoucheryUtils.php";
require_once "./classes/vouchery/VoucheryModel.php";
require_once "./classes/vouchery/VoucheryModelConfig.php";
require_once "./classes/vouchery/VoucheryController.php";
require_once "./classes/vouchery/VoucheryViewEdit.php";
require_once "./classes/vouchery/VoucheryViewPdf.php";
require_once "./classes/vouchery/VoucheryViewEmail.php";

require_once "./classes/vouchery/entyties/VoucheryObjednavkaHolder.php";

require_once "../phpMailer/minimal/class.phpmailer.php";

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

$model = new VoucheryModel();
$ctrl = new VoucheryController($model);
$viewMain = new VoucheryViewEdit($model);
$viewPdf = new VoucheryViewPdf($model);
$viewEmail = new VoucheryViewEmail($model);

$ctrl->dispatchRequests();

