<?php


class AdminModulHolder
{
    const SESSION_LAST_VISITED_MODULE = "last-visited-module";

    public static $DEFAULT_GOUP_MODULE = array(
        AdminModul::GROUP_BOF => "/admin/rezervace.php",
        AdminModul::GROUP_WEB => "/admin/foto.php",
        AdminModul::GROUP_ADMIN => "/admin/uzivatele.php",
        AdminModul::GROUP_OTHERS => "/admin/index.php"
    );

    /**
     * @var AdminModul[]
     */
    private $mAll;
    /**
     * @var AdminModul[]
     */
    private $mBof;
    /**
     * @var AdminModul[]
     */
    private $mWeb;
    /**
     * @var AdminModul[]
     */
    private $mAdmin;
    /**
     * @var AdminModul[]
     */
    private $mOthers;

    /**
     * @param $moduly
     */
    function __construct($moduly)
    {
        $this->parseModules($moduly);
    }

    private function parseModules($moduly)
    {
        foreach ($moduly as $m) {
            $menuModul = new AdminModul($m["id_modul"], $m["nazev_modulu"], $m["adresa_modulu"], $m["modul_group"]);
            $this->mAll[] = $menuModul;
            switch ($m["modul_group"]) {
                case AdminModul::GROUP_BOF:
                    $this->mBof[] = $menuModul;
                    break;
                case AdminModul::GROUP_WEB:
                    $this->mWeb[] = $menuModul;
                    break;
                case AdminModul::GROUP_ADMIN:
                    $this->mAdmin[] = $menuModul;
                    break;
                case AdminModul::GROUP_OTHERS:
                    $this->mOthers[] = $menuModul;
                    break;
            }
        }
    }

    public function hasBof() {
        return count((array)$this->mBof) > 0;
    }

    public function hasWeb() {
        return count((array)$this->mWeb) > 0;
    }

    public function hasAdmin() {
        return count((array)$this->mAdmin) > 0;
    }

    public function hasOthers() {
        return count((array)$this->mOthers) > 0;
    }

    public function getBof()
    {
        return $this->mBof;
    }

    public function getWeb()
    {
        return $this->mWeb;
    }

    public function getAdmin()
    {
        return $this->mAdmin;
    }

    public function getOthers()
    {
        return $this->mOthers;
    }

    public function getModuleById($moduleId)
    {
        foreach($this->mAll as $m) {
            if($m->id == $moduleId)
                return $m;
        }

        return null;
    }

    public function modulGroupById($modulId)
    {
        foreach($this->mAll as $m) {
            if($m->id == $modulId)
                return $m->group;
        }

        return null;
    }

    /**
     * Do seession ulozi pole s klici modul group - u kazdeho uklada url posledniho navstivenho modulu
     * @param int $activeId id
     */
    public function saveLastVisitedModule($activeId)
    {
        $m = $this->getModuleById($activeId);
        $_SESSION[self::SESSION_LAST_VISITED_MODULE][$m->group] = $m->url;
    }

    /**
     * Ziska posledni navstivene moduly podle modul group
     * @return string[]
     */
    public function loadLastVisitedModules()
    {
        $lastVisitedModules = array();

        foreach (AdminModul::$GROUP_LIST_VISIBLE as $group) {
            $lastVisitedModuleURL = $_SESSION[self::SESSION_LAST_VISITED_MODULE][$group];
            $lastVisitedModules[$group] = $lastVisitedModuleURL == "" ? self::$DEFAULT_GOUP_MODULE[$group] : $lastVisitedModuleURL;
        }

        return $lastVisitedModules;
    }

}