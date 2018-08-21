<?php
namespace Encounting\Example;

use Encounting\JcdhApi\JcdhApi;

function error($error) {
    return response(
        [
            'error' => $error
        ]
    );
}

function response($response) {
    return json_encode($response);
}

function handle() {
    $api = new JcdhApi(array_key_exists('output', $_GET) ? $_GET['output'] : JcdhApi::OUTPUT_JSON);

    $response = $api->getScores(array_key_exists('types', $_GET) ? $_GET['types'] : JcdhApi::TYPE_FOOD);

    if ($api->hasErrors()) {
        return error($api->getErrors());
    } else if ($response === false) {
        return error('Something went wrong.');
    } else {
        return response($response);
    }
}

echo json_encode(handle());