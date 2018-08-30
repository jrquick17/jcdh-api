<?php
namespace Encounting\Example;

use Encounting\Jcdh\Enums\JcdhTypes;
use Encounting\Jcdh\JcdhApi;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Jcdh/JcdhApi.php';
require __DIR__ . '/../src/Jcdh/Enums/JcdhTypes.php';

class ExampleApi {
    public function __construct() {

    }

    private function _error($error) {
        return $this->_response(
            [
                'error' => $error
            ]
        );
    }

    private function _response($response) {
        return json_encode($response);
    }

    function handle() {
        $letters = array_key_exists('letters', $_GET) ? $_GET['letters'] : 'A';
        $output = array_key_exists('output', $_GET) ? $_GET['output'] : false;
        $deductions = array_key_exists('deductions', $_GET) ? $_GET['deductions'] == 'true' : false;
        $types = array_key_exists('types', $_GET) ? $_GET['types'] : JcdhTypes::FOOD;

        $api = new JcdhApi($output);
        $response = $api->getScores($types, $letters);

        if ($api->hasErrors()) {
            return $this->_error($api->getErrors());
        } else if ($response === false) {
            return $this->_error('Something went wrong.');
        } else {
            if ($deductions) {
                foreach ($response as $type) {
                    foreach ($type as $item) {
                        $item = $api->getDeductions($item);
                    }
                }
            }

            return $this->_response($response);
        }
    }
}

$api = new ExampleApi();

echo json_encode($api->handle());