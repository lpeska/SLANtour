<?php

class PagingFilter extends AbstractFilter {

    private function __construct($pagingMaxPages = null, $pagingMaxPerPage = null)
    {
        if ($pagingMaxPages == null)
            $this->pagingMaxPages = self::$PAGING_MAX_PAGES;
        else
            $this->pagingMaxPages = $pagingMaxPages;

        if ($pagingMaxPerPage == null)
            $this->pagingMaxPerPage = self::$PAGING_MAX_PER_PAGE;
        else
            $this->pagingMaxPerPage = $pagingMaxPerPage;
    }

    public static function loadFilter($name, $pagingMaxPages, $pagingMaxPerPage)
    {
        $filter = new PagingFilter($pagingMaxPages, $pagingMaxPerPage);

        return $filter;
    }

    public function saveFilter($name)
    {
        // note: not interested in saving/loading filter
    }

    public function calculatePaging($foundRows)
    {
        parent::calcPaging($foundRows, $this->pagingMaxPerPage, $this->pagingMaxPages);
    }

}