<?php

use Supsign\ContaoGeoDataApiBundle\Controller\FrontendModule\GeoDataApi;

var_dump(__NAMESPACE__);

if (isset($_GET['search']) ) {

	echo (new GeoDataApi)->getCityOrZipJson($_GET['search']);
}