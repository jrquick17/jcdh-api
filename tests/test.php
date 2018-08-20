<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Encounting\DolBls\DolBlsApi;

$api = new DolBlsApi();

$results = $api->getSurveys();
if ($results) {
    echo('SUCCESS');
}