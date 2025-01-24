<?php


class ModulView
{
    /**
     * @param $modulHolder AdminModulHolder
     * @param $user User_zamestnanec info zamestnance
     * @param $activeId int active modul id
     * @return string
     */
    public static function showNavigation($modulHolder, $user, $activeId)
    {
        $activeGroup = $modulHolder->modulGroupById($activeId);
        $modulHolder->saveLastVisitedModule($activeId);
        $out = "";

        $out .= "<div id='alb-header-wrapper'>";
        $out .= "   <div id='alb-header' class='$activeGroup'>";
        $out .= "       <div id='alb-header-top'>";
        $out .= "           <h1>Albatros 3000</h1>";

        $out .= self::showNavGroups($modulHolder, $activeGroup);

        $out .= "           <div id='alb-user'>";
        $out .= "               <div id='alb-user-name'>" . $user->get_jmeno() . " " . $user->get_prijmeni() . "</div><a href='?typ=logout'></a>";
        $out .= "           </div>";
        $out .= "           <div class='clearfix'></div>";
        $out .= "       </div>";

        $out .= "       <div id='alb-nav-list'>";

        switch ($activeGroup) {
            case AdminModul::GROUP_BOF:
                $out .= self::showModuleList($modulHolder->getBof(), "$activeGroup", $activeId);
                break;
            case AdminModul::GROUP_WEB:
                $out .= self::showModuleList($modulHolder->getWeb(), "$activeGroup", $activeId);
                break;
            case AdminModul::GROUP_ADMIN:
                $out .= self::showModuleList($modulHolder->getAdmin(), "$activeGroup", $activeId);
                break;
            case AdminModul::GROUP_OTHERS:
                $out .= self::showModuleList($modulHolder->getOthers(), "$activeGroup", $activeId);
                break;
            case AdminModul::GROUP_HIDDEN:
                $hiddenModules[] = $modulHolder->getModuleById($activeId);
                $out .= self::showModuleList($hiddenModules, "$activeGroup", $activeId, false);
                break;
        }

        $out .= "       </div>";

        $out .= "   </div>";
        $out .= "</div>";

        return $out;
    }

    public static function showHelp($napoveda)
    {
        $out = "";

        $out .= "<div id='alb-help-spacer'></div>";
        $out .= "<div id='alb-help-wrapper'>";
        $out .= "   <div id='alb-help'>";
        $out .= "       <div id='alb-help-header'>Nápovìda k modulu</div>";
        $out .= "       <p>$napoveda</p>";
        $out .= "   </div>";
        $out .= "</div>";

        return $out;
    }

    /**
     * Starsi (Ladovo) upravene strankovani
     * @param $actualRecord
     * @param $totalCount
     * @param $maxPerPage
     * @return string
     */
    public static function showPaging($actualRecord, $totalCount, $maxPerPage)
    {
        $out = "";

        if ($totalCount != 0 && $maxPerPage != 0) {
            //prvni cislo stranky ktere zobrazime
            $actualPage = $actualRecord - (10 * $maxPerPage);
            $actualPage = $actualPage < 0 ? 0 : $actualPage;

            //url current request variables
            $currentRequest = preg_replace("/str=[0-9]*&?/", "", $_SERVER["QUERY_STRING"]);

            //first page
            $out .= "<div class='pagination'><a href='?str=0&$currentRequest' title=první stránka>&laquo;</a>";

            //pages
            while (($actualPage < $totalCount) && ($actualPage <= $actualRecord + (10 * $maxPerPage))) {
                $page = (1 + ($actualPage / $maxPerPage));
                if ($actualRecord != $actualPage) {
                    $out .= "<a href='?str=$actualPage&$currentRequest' title='strana $page'>$page</a>";
                } else {
                    $out .= "<a class='selected' disabled='disabled'>$page</span>";
                }
                $actualPage += $maxPerPage;
            }

            //last page
            $lastPage = $maxPerPage * floor(($totalCount - 1) / $maxPerPage);
            $out .= "<a href='?str=$lastPage&$currentRequest' title='poslední stránka'>&raquo;</a></div>";
        }

        return $out;
    }

    /**
     * Novejsi (Martinovo) strankovani presunute na jedno misto
     * @param $filter AbstractFilter
     * @return string
     */
    public static function showPaging2($filter) {
        $out = "";

        if ($filter->getFoundRows() > $filter->pagingMaxPerPage) {
            $out .= "<div class='pagination'>";
            $out .= "   <ul>";

            for ($i = 0; $i <= $filter->getPagingTotalPages() - 1; $i++) {
                $out .= self::leftPagingControls($i, $filter->getPagingPage());
                $out .= self::midPagingControlls($i, $filter->getPagingPage(), $filter->getPagingLeft(), $filter->getPagingRight(), $i + 1);
                $out .= self::rightPagingControls($i, $filter->getPagingPage(), $filter->getPagingTotalPages());
            }

            $out .= "   </ul>";
            $out .= "</div>";
        }

        return $out;
    }

    public static function showLoginForm($userName)
    {
        $out = "";

        $out .= "<div id='alb-header-wrapper'>";
        $out .= "   <div id='alb-header'>";
        $out .= "       <div id='alb-login'>";
        $out .= "           <h1>Albatros 3000</h1>";
        $out .= "           <form action='https://" . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"] . "?typ=login' method='post'>";
        $out .= "               <label for='username'>uživatel:</label><input type='text' name='name' value='$userName' id='username' size='20' maxlength='20' />";
        $out .= "               <label for='password'>heslo:</label><input type='password' value='' name='passwd' id='password' size='20' maxlength='20' />";
        $out .= "               <input type='submit' value='pøihlásit' />";
        $out .= "           </form>";
        $out .= "       </div>";
        $out .= "   </div>";
        $out .= "</div>";

        return $out;
    }

    private static function showModuleList($modules, $class, $activeId, $anchor = true)
    {
        $out = "";

        $out .= "<ul class='$class'>";

        foreach ($modules as $m) {
            $nazevFirstChar = strtoupper(substr($m->name, 0, 1));
            $nazevRest = strtolower(substr($m->name, 1));
            $class = $m->id == $activeId ? "class='active'" : "";
            $out = $out . "<li><h2><a href='" . ($anchor ? $m->url : "#") . "' $class>[<span class='highlight'>$nazevFirstChar</span>$nazevRest]</a></h2></li>";
        }

        $out .= "</ul>";

        return $out;
    }

    /**
     * @param $modulHolder AdminModulHolder
     * @param $activeGroup
     * @return string
     */
    private static function showNavGroups($modulHolder, $activeGroup)
    {
        $out = "";
        $lastVisitedURL = $modulHolder->loadLastVisitedModules();

        $out .= "   <div id='alb-nav-grps'>";
        $out .= "       <ul>";
        
        if ($modulHolder->hasBof())
            $out .= "           <li><a class='bof' href='".$lastVisitedURL[AdminModul::GROUP_BOF]."'>BOF</a></li>";
        if ($modulHolder->hasWeb())
            $out .= "           <li><a class='web' href='".$lastVisitedURL[AdminModul::GROUP_WEB]."'>WEB</a></li>";
        if ($modulHolder->hasAdmin())
            $out .= "           <li><a class='admin' href='".$lastVisitedURL[AdminModul::GROUP_ADMIN]."'>ADMIN</a></li>";
        if ($modulHolder->hasOthers())
            $out .= "           <li><a class='others' href='".$lastVisitedURL[AdminModul::GROUP_OTHERS]."'>OSTATNÍ</a></li>";
        if($activeGroup == AdminModul::GROUP_HIDDEN)
            $out .= "           <li><a class='hidden' href=''>HIDDEN</a></li>";

        $out .= "       </ul>";
        $out .= "   </div>";

        return $out;
    }

    private static function leftPagingControls($i, $page)
    {
        $out = "";

        if ($i == 0) {
            if ($page > 1) {
                $out .= "<a href='" . CommonUtils::addUri(array('p', 1)) . "'>&laquo;</a>";
                $out .= "<a href='" . CommonUtils::addUri(array('p', $page - 1)) . "'>&lsaquo;</a>";
            } else {
                $out .= "<a class='disabled'>&laquo;</a>";
                $out .= "<a class='disabled'>&lsaquo;</a>";
            }
        }

        return $out;
    }

    private static function midPagingControlls($i, $page, $left, $right, $pageIndex)
    {
        $out = "";

        if ($i >= $left && $i <= $right) {
            if ($pageIndex == $page)
                $out .= "<a class='selected' href='#'>$pageIndex</a>";
            else
                $out .= "<a href='" . CommonUtils::addUri(array('p', $pageIndex)) . "'>$pageIndex</a>";
        }

        return $out;
    }

    private static function rightPagingControls($i, $page, $totalPages)
    {
        $out = "";

        if ($i == ($totalPages - 1)) {
            if ($page < $totalPages) {
                $out .= "<a href='" . CommonUtils::addUri(array('p', $page + 1)) . "'>&rsaquo;</a>";
                $out .= "<a href='" . CommonUtils::addUri(array('p', $totalPages)) . "'>&raquo;</a>";
            } else {
                $out .= "<a class='disabled'>&rsaquo;</a>";
                $out .= "<a class='disabled'>&raquo;</a>";
            }
        }

        return $out;
    }

}