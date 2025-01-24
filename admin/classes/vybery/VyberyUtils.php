<?php


class VyberyUtils {

    public static function czechDate($datumCas)
    {
        return date("d.m.Y", strtotime($datumCas));
    }
    
}