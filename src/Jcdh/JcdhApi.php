<?php
namespace Encounting\Jcdh;

use Encounting\Jcdh\Enums\JcdhEventTargets;
use Encounting\Jcdh\Enums\JcdhOutputs;
use Encounting\Jcdh\Enums\JcdhTypes;
use Encounting\Jcdh\Enums\JcdhUrls;
use Encounting\Jcdh\Models\JcdhCommunalLiving;
use Encounting\Jcdh\Models\JcdhFood;
use Encounting\Jcdh\Models\JcdhHotel;
use Encounting\Jcdh\Models\JcdhPool;
use Encounting\Jcdh\Models\JcdhTanning;

use SimpleXMLElement;

/**
 * I belong to a class
 */
class JcdhApi {
    private $_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

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

    private function _buildUrl($type, $letter) {
        $url = false;

        switch ($type) {
            case JcdhTypes::COMMUNAL_LIVING:
                $url = JcdhUrls::COMMUNAL_LIVING_SCORES;
                break;
            case JcdhTypes::FOOD:
                $url = JcdhUrls::FOOD_SCORES;
                break;
            case JcdhTypes::HOTEL:
                $url = JcdhUrls::HOTEL_SCORES;
                break;
            case JcdhTypes::POOL:
                $url = JcdhUrls::POOL_SCORES;
                break;
            case JcdhTypes::TANNING:
                $url = JcdhUrls::TANNING_SCORES;
                break;
            default:
                error_log('Unable to get the url for: ' . $type);
        }

        if (is_string($letter)) {
            $url .= '?Letter='.$letter;
        }

        return $url;
    }

    /**
     * Remove all errors
     *
     * @return void
     */
    private function _clearErrors() {
        $this->_setErrors(false);
    }

    private function _convertToCommunalLiving($tds) {
        $communal = false;

        if (count($tds) === 3 && $tds[0]->plaintext !== 'Score') {
            $communal = new JcdhCommunalLiving();

            $communal->score = $tds[0]->plaintext;

            $nameAndAddress = $this->_splitNameAddress($tds[1]->plaintext);
            $communal->name = trim($nameAndAddress[0]);
            $communal->address = trim($nameAndAddress[1]);

//                TODO:
//                $business->location = _getLatLng($business->address);

            $communal->date = $tds[2]->plaintext;
        }

        return $communal;
    }

    private function _convertToFood($tds) {
        $food = false;

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
        }

        return $food;
    }

    private function _convertToHotel($tds) {
        $hotel = false;

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
        }

        return $hotel;
    }

    private function _convertToPool($tds) {
        $pool = false;

        if (count($tds) === 4 && $tds[0]->plaintext !== 'Score') {
            $pool = new JcdhPool();

            $pool->score = $tds[0]->plaintext;

            $pool->type = $tds[1]->plaintext;

            $nameAndAddress = $this->_splitNameAddress($tds[2]->plaintext);
            $pool->name = trim($nameAndAddress[0]);
            $pool->address = trim($nameAndAddress[1]);

//                TODO
//                $business->location = getLatLng($business->address);

            $pool->date = $tds[3]->plaintext;
        }

        return $pool;
    }

    private function _convertToTanning($tds) {
        $tanning = false;

        if (count($tds) === 4 && $tds[0]->plaintext !== 'PermitNbr') {
            $tanning = new JcdhTanning();

            $tanning->permit_no = $tds[0]->plaintext;
            $tanning->score = $tds[1]->plaintext;

            $nameAndAddress = preg_split('/\n|\r\n?/', $tds[2]->plaintext);
            $tanning->name = trim($nameAndAddress[0]);
            $tanning->address = trim($nameAndAddress[1]);

//                TODO
//                $business->location = getLatLng($business->address);

            $tanning->date = $tds[3]->plaintext;
        }

        return $tanning;
    }

    private function _findById($html, $id) {
        $returnVar = false;

        $results = $html->find('#'.$id);
        if (is_array($results) && count($results) > 0) {
            $input = $results[0];

            if (is_array($input->attr)) {
                $attributes = $input->attr;
                if (array_key_exists('value', $attributes)) {
                    $returnVar = $attributes['value'];
                }
            }
        }

        return $returnVar;
    }

    private function _getEventTarget($html, $type) {
        $eventTarget = $this->_findById($html, '__EVENTTARGET');

        if ($eventTarget === false) {
            switch($type) {
                case JcdhTypes::COMMUNAL_LIVING:
                    $eventTarget = JcdhEventTargets::COMMUNAL_LIVING;
                    break;
                case JcdhTypes::FOOD:
                    $eventTarget = JcdhEventTargets::FOOD;
                    break;
                case JcdhTypes::HOTEL:
                    $eventTarget = JcdhEventTargets::HOTEL;
                    break;
                case JcdhTypes::POOL:
                    $eventTarget = JcdhEventTargets::POOL;
                    break;
                case JcdhTypes::TANNING:
                    $eventTarget = JcdhEventTargets::TANNING;
                    break;
                default:
                    error_log('Unable to find the event target for: '.$type);
            }
        }

        if ($eventTarget === false) {
            error_log('Unable to find the event target for: '.$type);
        }

        return $eventTarget;
    }

    private function _getEventValidation($html, $type) {
        $eventValidation = $this->_findById($html, '__EVENTVALIDATION');

        if ($eventValidation === false) {
            error_log('Unable to find the event validation for: '.$type);
        }

        return $eventValidation;
    }

    private function _getLatLng($address) {
        $prepAddr = str_replace(' ', '+', $address);
        $geocode = file_get_contents(JcdhUrls::GOOGLE_GEOCODE.'?address=' . $prepAddr . '&sensor=false&key=');
        $output = json_decode($geocode);

        return $output->results[0]->geometry->location;
    }

    private function _getLetters() {
        return str_split($this->_LETTERS);
    }

    private function _getPageCount($html, $type) {
        $pages = [];

        $eventTarget = $this->_getEventTarget($html, $type);

        $tr = end($html->find('#MainContent_'.$eventTarget.' tr'));
        foreach ($tr->children as $td) {
            $page = trim($td->plaintext);

            if (is_numeric($page)) {
                $page = intval($page, 10);

                $pages[] = $page;
            }
        }

        $count = count($pages);
        if ($count === 0) {
            $count = 1;
        }

        return $count;
    }

    private function _getTypeScores($type = JcdhTypes::FOOD, $letter = false) {
        $url = $this->_buildUrl($type, $letter);

        $page = 0;
        $pageCount = 0;

        $scores = [];

        $html = false;

        do {
            $html = $this->_request($url, $type, $html, ++$page);

            if ($html) {
                if ($pageCount === 0) {
                    $pageCount = $this->_getPageCount($html, $type);
                }

                foreach ($html->find('tr') as $id => $tr) {
                    $tds = $tr->children;

                    $item = false;

                    switch ($type) {
                        case JcdhTypes::COMMUNAL_LIVING:
                            $item = $this->_convertToCommunalLiving($tds);
                            break;
                        case JcdhTypes::FOOD:
                            $item = $this->_convertToFood($tds);
                            break;
                        case JcdhTypes::HOTEL:
                            $item = $this->_convertToHotel($tds);
                            break;
                        case JcdhTypes::POOL:
                            $item = $this->_convertToPool($tds);
                            break;
                        case JcdhTypes::TANNING:
                            $item = $this->_convertToTanning($tds);
                            break;
                        default:
                            error_log('Unable to get the item for: ' . $type);
                    }

                    if ($item) {
                        $scores[] = $item;
                    }
                }
            }
        } while ($page <= $pageCount);

        return $scores;
    }

    private function _getViewState($html, $type) {
        $viewState = $this->_findById($html, '__VIEWSTATE');

        if ($viewState === false) {
            error_log('Unable to find the view state for: '.$type);
        }

        return $viewState;
    }

    private function _getViewStateGenerator($html, $type) {
        $viewStateGenerator = $this->_findById($html, '__VIEWSTATEGENERATOR');

        if ($viewStateGenerator === false) {
            error_log('Unable to find the view state generator for: '.$type);
        }

        return $viewStateGenerator;
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

    private function _request($url, $type = false, $html = false, $page = 1) {
        $context = null;

        if ($type && $html && $page !== 1) {
            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query(
                        [
                            '__EVENTTARGET'        => 'ctl00$MainContent$'.$this->_getEventTarget($html, $type),
                            '__EVENTARGUMENT'      => 'Page$'.$page,
                            '__VIEWSTATE'          => $this->_getViewState($html, $type),
                            '__VIEWSTATEGENERATOR' => $this->_getViewStateGenerator($html, $type),
                            '__EVENTVALIDATION'    => $this->_getEventValidation($html, $type)
                        ]
                    )
                ]
            ];

            $context = stream_context_create($opts);
        }

        $content = file_get_contents($url, false, $context);

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
        if (is_string($use) && strtolower($use) === JcdhOutputs::JSON) {
            $this->_useJson = true;
        } else if (is_string($use) && strtolower($use) === JcdhOutputs::XML) {
            $this->_useXml = true;
        }
    }

    private function _splitNameAddress($string) {
        return preg_split('/\n|\r\n?/', $string);
    }

    public function getCommunalLivingScores($letter = false) {
        return $this->_getTypeScores(JcdhTypes::COMMUNAL_LIVING, $letter);
    }

    /**
     * Get an array of food scores, all of the letter passed in or the most recent if $letter is false
     *
     * @param string|bool $letter
     *
     * @return JcdhFood[]
     */
    public function getFoodScores($letter = false) {
        return $this->getScores(JcdhTypes::FOOD, $letter);
    }

    public function getHotelScores($letter = false) {
        return $this->getScores(JcdhTypes::HOTEL, $letter);
    }

    public function getPoolScores($letter = false) {
        return $this->getScores(JcdhTypes::POOL, $letter);
    }

    public function getTanningScores($letter = false) {
        return $this->getScores(JcdhTypes::TANNING, $letter);
    }

    public function getScores($types = JcdhTypes::FOOD, $letters = false) {
        if (is_string($types)) {
            $types = explode(',', $types);
        }

        $scores = [];
        foreach ($types as $type) {
            $function = false;

            switch($type) {
                case JcdhTypes::COMMUNAL_LIVING:
                    $function = 'getCommunalLivingScores';
                    break;
                case JcdhTypes::FOOD:
                    $function = 'getFoodScores';
                    break;
                case JcdhTypes::HOTEL:
                    $function = 'getHotelScores';
                    break;
                case JcdhTypes::POOL:
                    $function = 'getPoolScores';
                    break;
                case JcdhTypes::TANNING:
                    $function = 'getTanningScores';
                    break;
            }

            if ($function) {
                $scores[$type] = [];

                if ($letters === false || (is_array($letters) && count($letters) > 0)) {
                    $letters = $this->_getLetters();
                }

                foreach ($letters as $letter) {
                    $moreScores = $this->$function($letter);

                    $scores[$types] = array_merge($scores[$types], $moreScores);
                }
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