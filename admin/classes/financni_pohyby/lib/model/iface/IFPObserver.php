<?php

interface IFPObserver {
    public function serialListChanged();
    public function prehledChanged();
    public function prehledPdfChanged();
}