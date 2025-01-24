<?php

require_once "./core/load_core.inc.php";

//note - v casti TS je pouzit globalni model
require_once '../global/lib/model/entyties/ObjednavkaEnt.php';
require_once '../global/lib/model/entyties/SerialEnt.php';
require_once '../global/lib/model/entyties/ZajezdEnt.php';
require_once '../global/lib/model/entyties/SmluvniPodminkyNazevEnt.php';
require_once '../global/lib/model/entyties/SmluvniPodminkyEnt.php';
require_once '../global/lib/model/entyties/OrganizaceEnt.php';
require_once '../global/lib/model/entyties/AdresaEnt.php';
require_once '../global/lib/model/entyties/UserKlientEnt.php';
require_once '../global/lib/model/entyties/SluzbaEnt.php';
require_once '../global/lib/model/entyties/SlevaEnt.php';
require_once '../global/lib/model/entyties/FakturaEnt.php';
require_once '../global/lib/model/entyties/PlatbaEnt.php';
require_once '../global/lib/model/entyties/FotoEnt.php';
require_once "../global/lib/model/entyties/TerminObjektoveKategorieEnt.php";
require_once "../global/lib/model/entyties/ObjektovaKategorieEnt.php";
require_once "../global/lib/model/entyties/ObjektEnt.php";
require_once '../global/lib/model/holders/SmluvniPodminkyHolder.php';
require_once '../global/lib/model/holders/AdresaHolder.php';
require_once '../global/lib/model/holders/UserKlientHolder.php';
require_once '../global/lib/model/holders/SluzbaHolder.php';
require_once '../global/lib/model/holders/SlevaHolder.php';
require_once '../global/lib/model/holders/FakturaHolder.php';
require_once '../global/lib/model/holders/PlatbaHolder.php';
require_once "../global/lib/model/holders/TerminObjektoveKategorieHolder.php";
require_once "../global/lib/model/holders/ObjektovaKategorieHolder.php";
require_once '../global/lib/cfg/ViewConfig.php';
require_once '../global/lib/cfg/DatabaseConfig.php';
require_once '../global/lib/cfg/CommonConfig.php';
require_once '../global/lib/db/SQLQuery.php';
require_once '../global/lib/db/DatabaseProvider.php';
require_once '../global/lib/db/dao/ObjednavkyDAO.php';
require_once '../global/lib/db/dao/sql/ObjednavkySQLBuilder.php';
ObjednavkyDAO::init();

require_once "./classes/dataContainers/tsZajezd.php";
require_once "./classes/dataContainers/tsObjednavajici.php";
require_once "./classes/dataContainers/tsAdresa.php";
require_once "./classes/dataContainers/tsOsoba.php";
require_once "./classes/dataContainers/tsProdejce.php";
require_once "./classes/dataContainers/tsObjednavka.php";
require_once "./classes/dataContainers/tsPlatba.php";

require_once "./classes/dataContainers/tsProvize.php";
require_once "./classes/dataContainers/tsSmluvniPodminky.php";
require_once "./classes/dataContainers/tsSluzba.php";
require_once "./classes/dataContainers/tsStaticDescription.php";
require_once "./classes/dataContainers/tsObjektovaKategorie.php";
require_once "./classes/dataContainers/tsSleva.php";
require_once "./classes/dataContainers/tsOrganizace.php";
require_once "./classes/dataContainers/tsAdresa.php";

require_once "./classes/platebni_doklad/lib/utils/PlatebniDokladUtils.php";

require_once "./classes/platebni_doklad/lib/iface/IPlatebniDokladModelEditObserver.php";
require_once "./classes/platebni_doklad/lib/iface/IPlatebniDokladModelPdfObserver.php";
require_once "./classes/platebni_doklad/lib/iface/IPlatebniDokladModelEmailObserver.php";

require_once "./classes/platebni_doklad/lib/model/PlatebniDokladModel.php";
require_once "./classes/platebni_doklad/lib/model/PlatebniDokladModelConfig.php";
require_once "./classes/platebni_doklad/lib/ctrl/PlatebniDokladController.php";
require_once "./classes/platebni_doklad/lib/view/PlatebniDokladView.php";
require_once "./classes/platebni_doklad/lib/view/PlatebniDokladViewEdit.php";
require_once "./classes/platebni_doklad/lib/view/PlatebniDokladViewPdf.php";
require_once "./classes/platebni_doklad/lib/view/PlatebniDokladViewEmail.php";

require_once "./classes/platebni_doklad/lib/dao/PlatebniDokladDAO.php";
require_once "./classes/platebni_doklad/lib/dao/sql/PlatebniDokladSQLBuilder.php";

require_once "../phpMailer/minimal/class.phpmailer.php";

require_once "./classes/ts/objednavka_dao.inc.php";
require_once "./classes/ts/objednavka_displayer.inc.php";
require_once "./classes/ts/objednavka_ts.inc.php";
require_once "./classes/ts/utils_ts.inc.php";
require_once "./classes/vouchery/VoucheryModelConfig.php";

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

require_once '../global/lib/cfg/CommonConfig.php';

PlatebniDokladDAO::init();

$model = new PlatebniDokladModel();
$ctrl = new PlatebniDokladController($model);
new PlatebniDokladViewEdit($model);
new PlatebniDokladViewPdf($model);
new PlatebniDokladViewEmail($model);

$ctrl->dispatchRequests();
