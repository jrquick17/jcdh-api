<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Encounting\Jcdh\JcdhApi;

$api = new JcdhApi();

$results = $api->getSurveys();
if ($results) {
    echo('SUCCESS');
}