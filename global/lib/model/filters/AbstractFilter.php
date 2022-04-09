<?php

/**
 * Class AbstractFilter abstraktni trida pro implementaci filtru - note save/load by melo byt extrahovane mimo tuhle tridu - trida ma takhle 2 responsibility
 */
abstract class AbstractFilter
{
    protected static $PAGING_FIRST_PAGE = 1;
    protected static $PAGING_MAX_PAGES = 17;
    protected static $PAGING_MAX_PER_PAGE = 20;

    protected $foundRows;
    protected $pagingTotalPages;
    protected $pagingLeft;
    protected $pagingRight;
    protected $pagingPage;
    public $pagingMaxPages;
    public $pagingMaxPerPage;

    //region GETTERS *********************************************************************

    /**
     * @return int
     */
    public function getPagingPage()
    {
        return $this->pagingPage;
    }

    /**
     * @return mixed
     */
    public function getPagingLeft()
    {
        return $this->pagingLeft;
    }

    /**
     * @return mixed
     */
    public function getPagingTotalPages()
    {
        return $this->pagingTotalPages;
    }

    /**
     * @return mixed
     */
    public function getPagingRight()
    {
        return $this->pagingRight;
    }

    /**
     * @return mixed
     */
    public function getFoundRows()
    {
        return $this->foundRows;
    }

    //endregion

    //region SETTERS *********************************************************************

    /**
     * @param string $page
     */
    public function setPagingPage($page)
    {
        if (isset($page) && !is_null($page) && $page != "")
            $this->pagingPage = $page;
        else
            $this->pagingPage = self::$PAGING_FIRST_PAGE;
    }

    //endregion

    /**
     * note php neumi dedit staticke metody, takze tohle reseni asi neni spravne, ale asi to funguije, jenom to neni pekny reseni, ono si s tim nejak PHP ale poradi
    */
    public abstract static function loadFilter($name, $pagingMaxPages, $pagingMaxPerPage);

    public abstract function calculatePaging($foundRows);

    public abstract function saveFilter($name);

    //region PROTECTED METHODS ****************************************************************

    protected function calcPaging($foundRows, $MAX_PER_PAGE, $MAX_PAGES)
    {
        if($foundRows <= 0)
            return;
        
        $this->foundRows = $foundRows;
        $this->pagingTotalPages = ceil($foundRows / $MAX_PER_PAGE);
        $this->pagingLeft = $this->pagingPage - ceil($MAX_PAGES / 2);
        $this->pagingRight = $this->pagingPage + ceil($MAX_PAGES / 2) - 2;
        if ($this->pagingLeft < 0)
            $this->pagingRight = $MAX_PAGES - 1;
        if ($this->pagingRight >= $this->pagingTotalPages)
            $this->pagingLeft = $this->pagingTotalPages - $MAX_PAGES;
    }

    protected function parseMultipleIds($ids)
    {
        $returnIds = null;

        if (!is_null($ids) && $ids != "") {
            if (is_array($ids)) {
                $returnIds = $ids;
            } else {
                $idDump = explode(",", $ids);
                foreach ($idDump as $dump)
                    $returnIds[] = trim($dump);
            }
        }

        return $returnIds;
    }

    protected function parseMultipleIdsAsString($idsToParse)
    {
        $ids = "";

        if (!is_null($idsToParse)) {
            //todo muze byt prazdne z nakeho duvodu, zjistit proc (tyka se urcite seznamu ucastniku) a bud tomu zamezit nebo ukazat hlasku
            foreach ((array)$idsToParse as $id) {
                $ids .= "$id, ";
            }
            $ids = substr($ids, 0, strlen($ids) - 2);
        }

        return $ids;
    }

    //endregion
}