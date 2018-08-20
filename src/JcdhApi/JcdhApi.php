<?php
namespace Encounting\JcdhApi;

use Encounting\DolBls\Models\JcdhCommunalLiving;
use Encounting\DolBls\Models\JcdhDeduction;
use Encounting\DolBls\Models\JcdhFood;
use Encounting\DolBls\Models\JcdhHotel;
use Encounting\DolBls\Models\JcdhPool;
use Encounting\DolBls\Models\JcdhTanning;
use function Encounting\Vendor\str_get_html;
use SimpleXMLElement;

/**
 * I belong to a class
 */
class JcdhApi {
    const BASE_URL = 'https://api.bls.gov/publicAPI/v2/';

    const REQUEST_FAIL = 'REQUEST_NOT_PROCESSED';
    const REQUEST_SUCCESS = 'REQUEST_SUCCESS';

    const TYPE_COMMUNAL_LIVING = 'communal';
    const TYPE_FOOD = 'food';
    const TYPE_HOTEL = 'hotel';
    const TYPE_POOL = 'pool';
    const TYPE_TANNING = 'tanning';

    const JSON = 'json';
    const XML = 'xml';

    private $_errors = false;

    private $_useJson = false;
    private $_useXml = false;

    /**
     * JcdhApi constructor.
     *
     * @param string|false $use  Return results in a specific format; options 'json', 'xml', or false for a stdClass
     *
     * @return void
     */
    public function __construct($use = false) {
        $this->_setUse($use);
    }

    /**
     * Remove all errors
     *
     * @return void
     */
    private function _clearErrors() {
        $this->_setErrors(false);
    }

    private function _getLatLng($address) {
        $prepAddr = str_replace(' ', '+', $address);
        $geocode = file_get_contents('https://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false&key=AIzaSyAXIcHCTRrm5KLBVtsF_q5Hj5vYeAlfWqI');
        $output = json_decode($geocode);

        return $output->results[0]->geometry->location;
    }

    private function _getCommunalLivingScores() {
        $html = $this->_request(
            'https://webapps.jcdh.org/scores/ehcl/communallivingscores.aspx'
        );

        $scores = [];
        foreach($html->find('tr') as $id => $tr) {
            $tds = $tr->children;

            if (count($tds) === 3 && $tds[0]->plaintext !== 'Score') {
                $communal = new JcdhCommunalLiving();

                $communal->score = $tds[0]->plaintext;

                $nameAndAddress = $this->_splitNameAddress($tds[1]->plaintext);
                $communal->name = trim($nameAndAddress[0]);
                $communal->address = trim($nameAndAddress[1]);

//                TODO:
//                $business->location = _getLatLng($business->address);

                $communal->date = $tds[2]->plaintext;

                $scores[] = $communal;
            }
        }

        return $scores;
    }

    private function _getFoodScores() {
        $html = $this->_request(
            'https://webapps.jcdh.org/scores/ehfs/foodservicescores.aspx'
        );

        $scores = [];
        foreach($html->find('tr') as $id => $tr) {
            $tds = $tr->children;

            if (count($tds) === 5 && $tds[0]->plaintext !== 'PermitNbr') {
                $food = new JcdhFood();

                $food->permit_no = $tds[0]->plaintext;
                $food->score = $tds[1]->plaintext;

                $nameAndAddress = $this->_splitNameAddress($tds[2]->plaintext);
                $food->name = trim($nameAndAddress[0]);
                $food->address = trim($nameAndAddress[1]);

//                TODO:
//                $business->location = _getLatLng($business->address);

                $food->date = $tds[3]->plaintext;

                $food->smoke_free = $tds[4] === 'Y';

                $food->deductions = null;

                $scores[] = $food;
            }
        }

        return $scores;
    }

    private function _getHotelScores() {
        $html = $this->_request(
            'https://webapps.jcdh.org/scores/ehhls/hotellodgingscores.aspx'
        );

        $scores = [];
        foreach($html->find('tr') as $id => $tr) {
            $tds = $tr->children;
            if (count($tds) === 5 && $tds[0]->plaintext !== 'EstabNbr') {
                $hotel = new JcdhHotel();

                $hotel->establishment_number = $tds[0]->plaintext;

                $hotel->score = $tds[1]->plaintext;

                $nameAndAddress = $this->_splitNameAddress($tds[2]->plaintext);
                $hotel->name = trim($nameAndAddress[0]);
                $hotel->address = trim($nameAndAddress[1]);

//                TODO
//                $business->location = getLatLng($business->address);

                $hotel->date = $tds[3]->plaintext;

                $hotel->number_of_units = $tds[4]->plaintext;

                $scores[] = $hotel;
            }
        }

        return $scores;
    }

    private function _getPoolScores() {
        $html = $this->_request(
            'https://webapps.jcdh.org/scores/ehps/poolscores.aspx'
        );

        $scores = [];
        foreach($html->find('tr') as $id => $tr) {
            $tds = $tr->children;
            if (count($tds) === 4 && $tds[0]->plaintext !== 'Score') {
                $business = new JcdhPool();

                $business->score = $tds[0]->plaintext;

                $business->type = $tds[1]->plaintext;

                $nameAndAddress = $this->_splitNameAddress($tds[2]->plaintext);
                $business->name = trim($nameAndAddress[0]);
                $business->address = trim($nameAndAddress[1]);

//                TODO
//                $business->location = getLatLng($business->address);

                $business->date = $tds[3]->plaintext;

                $scores[] = $business;
            }
        }

        return $scores;
    }

    private function _getTanningScores() {
        $html = $this->_request(
            'https://webapps.jcdh.org/scores/ehts/tanningscores.aspx'
        );

        $scores = [];
        foreach($html->find('tr') as $id => $tr) {
            $tds = $tr->children;
            if (count($tds) === 4 && $tds[0]->plaintext !== 'PermitNbr') {
                $business = new JcdhTanning();

                $business->permit_no = $tds[0]->plaintext;
                $business->score = $tds[1]->plaintext;

                $nameAndAddress = preg_split('/\n|\r\n?/', $tds[2]->plaintext);
                $business->name = trim($nameAndAddress[0]);
                $business->address = trim($nameAndAddress[1]);

//                TODO
//                $business->location = getLatLng($business->address);

                $business->date = $tds[3]->plaintext;

                $scores[] = $business;
            }
        }

        return $scores;
    }

    private function _getFoodReport($permitNo) {
        // TODO: Refactor and make work
        $deductions = [];

        $content = file_get_contents('https://webapps.jcdh.org/scores/ehfs/FSSDetails.aspx?PermitNbr='.$permitNo.'&InspNbr=13');

        $html = str_get_html($content);

        $deductionTable = $html->find('#MainContent_TabContainer1_TabPanel1_Score1Ctrl1_GVScoreDetails');
        if (is_array($deductionTable) && count($deductionTable) > 0) {
            $deductionTable = $deductionTable[0];

            foreach ($deductionTable->children as $tr) {
                $tds = $tr->find('td');

                if (is_array($tds) && count($tds) > 0) {
                    $deduction = new JcdhDeduction();
                    $deduction->value = $tds[0]->plaintext;

                    $notes = $tds[1]->plaintext;

                    $codeRegex = '[0-9]{1}-[0-9]+.[0-9]+(\([A-Z]\))*';
                    $codeBeforeParenthesis = '\s[0-9]{1}-[0-9]+.[0-9]+(\([A-Z]\))*\s([A-Za-z0-9]|\s)+';
                    $codeParenthesis = '';
                    $codeAfterParenthesis = '[A-Za-z0-9]*\.';
                    $afterPeriodParenthesis = '(\s\((.*)\))*';

                    $regex = $codeRegex.$codeBeforeParenthesis.$codeParenthesis.$codeAfterParenthesis.$afterPeriodParenthesis;

                    $notes = preg_split('/'.$codeRegex.'/', $notes);
                    foreach ($notes as $note) {

                    }

                    $deductions[] = $deduction;
                }
            }
        } else {
            error_log('Unable to find the deduction table.');
        }

        return $deductions;
    }

    private function _processResults($results) {
        if ($this->_useJson) {
            $results = json_encode($results);
        } else if ($this->_useXml) {
            $xml = new SimpleXMLElement('<root/>');

            array_walk_recursive(
                json_decode(json_encode($results), true),
                [
                    $xml,
                    'addChild'
                ]
            );

            $results = $xml->asXML();
        }

        return $results;
    }

    private function _request($url) {
        $content = file_get_contents($url);

        return str_get_html($content);
    }

    /**
     * Set error messages
     *
     * @param bool           $messages The error message(s) to add
     *
     * @return bool|mixed
     */
    private function _setErrors($messages = false) {
        if (is_bool($messages)) {
            $messages = false;
        } else if (!is_array($messages)) {
            $messages = [ $messages ];
        }

        $this->_errors = $messages;

        if (is_array($this->_errors)) {
            foreach ($this->_errors as $error) {
                error_log($error);
            }
        }

        return $this->_errors !== false;
    }

    /**
     * Set whether to use stdClass, JSON, or XML
     *
     * @param string|false $use
     *
     * @return void
     */
    private function _setUse($use) {
        if (is_string($use) && strtolower($use) === JcdhApi::JSON) {
            $this->_useJson = true;
        } else if (is_string($use) && strtolower($use) === JcdhApi::XML) {
            $this->_useXml = true;
        }
    }

    private function _splitNameAddress($string) {
        return preg_split('/\n|\r\n?/', $string);
    }

    public function getScores($types = self::TYPE_FOOD) {
        if (is_string($types)) {
            $types = explode(',', $types);
        }

        $scores = [];
        foreach ($types as $type) {
            switch($type) {
                case self::TYPE_COMMUNAL_LIVING:
                    $scores[$type] = $this->_getCommunalLivingScores();
                    break;
                case self::TYPE_FOOD:
                    $scores[$type] = $this->_getFoodScores();
                    break;
                case self::TYPE_HOTEL:
                    $scores[$type] = $this->_getHotelScores();
                    break;
                case self::TYPE_POOL:
                    $scores[$type] = $this->_getPoolScores();
                    break;
                case self::TYPE_TANNING:
                    $scores[$type] = $this->_getTanningScores();
                    break;
            }
        }

        return $this->_processResults($scores);
    }

    public function getReport($permitNo) {
//        TODO: Should work for all types
        return $this->_getFoodReport($permitNo);
    }

    /**
     * Get errors
     *
     * @return bool|string[]
     */
    public function getErrors() {
        return $this->_errors;
    }

    /**
     * Check if there are any errors
     *
     * @return bool
     */
    public function hasErrors() {
        return !is_bool($this->_errors);
    }
}