<?php
namespace Encounting\Jcdh;

use Encounting\Jcdh\Models\JcdhCommunalLiving;
use Encounting\Jcdh\Models\JcdhDeduction;
use Encounting\Jcdh\Models\JcdhFood;
use Encounting\Jcdh\Models\JcdhHotel;
use Encounting\Jcdh\Models\JcdhPool;
use Encounting\Jcdh\Models\JcdhTanning;

use SimpleXMLElement;

/**
 * I belong to a class
 */
class JcdhApi {
    const REQUEST_FAIL = 'REQUEST_NOT_PROCESSED';
    const REQUEST_SUCCESS = 'REQUEST_SUCCESS';

    const TYPE_COMMUNAL_LIVING = 'communal';
    const TYPE_FOOD = 'food';
    const TYPE_HOTEL = 'hotel';
    const TYPE_POOL = 'pool';
    const TYPE_TANNING = 'tanning';

    const OUTPUT_JSON = 'json';
    const OUTPUT_XML = 'xml';

    const URL_COMMUNAL_LIVING_SCORES = 'https://webapps.jcdh.org/scores/ehcl/communallivingscores.aspx';
    const URL_FOOD_SCORES = 'https://webapps.jcdh.org/scores/ehfs/foodservicescores.aspx';
    const URL_HOTEL_SCORES = 'https://webapps.jcdh.org/scores/ehhls/hotellodgingscores.aspx';
    const URL_POOL_SCORES = 'https://webapps.jcdh.org/scores/ehps/poolscores.aspx';
    const URL_TANNING_SCORES = 'https://webapps.jcdh.org/scores/ehts/tanningscores.aspx';

    const EVENT_TARGET_COMMUNAL_LIVING = 'gvFoodScores';
    const EVENT_TARGET_FOOD = 'gvFoodScores';
    const EVENT_TARGET_HOTEL = 'gvFoodScores';
    const EVENT_TARGET_POOL = 'gvFoodScores';
    const EVENT_TARGET_TANNING = 'gvFoodScores';

    const EVENT_VALIDATION_COMMUNAL_LIVING = 'gvFoodScores';
    const EVENT_VALIDATION_FOOD = '\'/wEdAAlmnKzTfO+xh+qbhreLa0C3yl6TejLOUKUZ8Y9kTykFa5+I/HPZ86NEZcbXwR9jxA7msDRYuZWmPMEu0d+94igePYooppvH2PzZ2BoJoTrJKKJo+s/l+97RrfDL3JdcYysMT7kLOdxk9pikDqPtCWwjF5AOA1D09yc0cAmRHJBCCZX1wu7rL64lwdAute68Jh5rGjDaU5llTFWa1e/bMTmESCBNDv41wE2a6f1Vc0bZMw==\'';
    const EVENT_VALIDATION_HOTEL = 'gvFoodScores';
    const EVENT_VALIDATION_POOL = 'gvFoodScores';
    const EVENT_VALIDATION_TANNING = 'gvFoodScores';

    const VIEW_STATE_GENERATOR_COMMUNAL_LIVING = 'C440D02D';
    const VIEW_STATE_GENERATOR_FOOD = '93ED4CD7';
    const VIEW_STATE_GENERATOR_HOTEL = 'C440D02D';
    const VIEW_STATE_GENERATOR_POOL = 'C440D02D';
    const VIEW_STATE_GENERATOR_TANNING = 'C440D02D';

    const URL_GOOGLE_GEOCODE = 'https://maps.google.com/maps/api/geocode/json';

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
            case self::TYPE_COMMUNAL_LIVING:
                $url = self::URL_COMMUNAL_LIVING_SCORES;
                break;
            case self::TYPE_FOOD:
                $url = self::URL_FOOD_SCORES;
                break;
            case self::TYPE_HOTEL:
                $url = self::URL_HOTEL_SCORES;
                break;
            case self::TYPE_POOL:
                $url = self::URL_POOL_SCORES;
                break;
            case self::TYPE_TANNING:
                $url = self::URL_TANNING_SCORES;
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

    private function _getEventTarget($html, $type) {
        $eventTarget = $this->_findById($html, '__EVENTTARGET');

        if ($eventTarget === false) {
            switch($type) {
                case self::TYPE_COMMUNAL_LIVING:
                    $eventTarget = self::EVENT_TARGET_COMMUNAL_LIVING;
                    break;
                case self::TYPE_FOOD:
                    $eventTarget = self::EVENT_TARGET_FOOD;
                    break;
                case self::TYPE_HOTEL:
                    $eventTarget = self::EVENT_TARGET_HOTEL;
                    break;
                case self::TYPE_POOL:
                    $eventTarget = self::EVENT_TARGET_POOL;
                    break;
                case self::TYPE_TANNING:
                    $eventTarget = self::EVENT_TARGET_TANNING;
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

    private function _getLatLng($address) {
        $prepAddr = str_replace(' ', '+', $address);
        $geocode = file_get_contents(self::URL_GOOGLE_GEOCODE.'?address=' . $prepAddr . '&sensor=false&key=');
        $output = json_decode($geocode);

        return $output->results[0]->geometry->location;
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

    private function _getTypeScores($type = self::TYPE_FOOD, $letter = false) {
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
                        case self::TYPE_COMMUNAL_LIVING:
                            $item = $this->_convertToCommunalLiving($tds);
                            break;
                        case self::TYPE_FOOD:
                            $item = $this->_convertToFood($tds);
                            break;
                        case self::TYPE_HOTEL:
                            $item = $this->_convertToHotel($tds);
                            break;
                        case self::TYPE_POOL:
                            $item = $this->_convertToPool($tds);
                            break;
                        case self::TYPE_TANNING:
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

    private function _getEventValidation($html, $type) {
        $eventValidation = $this->_findById($html, '__EVENTVALIDATION');

        if ($eventValidation === false) {
            error_log('Unable to find the event validation for: '.$type);
        }

        return $eventValidation;
    }

    private function _getLetters() {
        return str_split($this->_LETTERS);
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
        if (is_string($use) && strtolower($use) === JcdhApi::OUTPUT_JSON) {
            $this->_useJson = true;
        } else if (is_string($use) && strtolower($use) === JcdhApi::OUTPUT_XML) {
            $this->_useXml = true;
        }
    }

    private function _splitNameAddress($string) {
        return preg_split('/\n|\r\n?/', $string);
    }

    public function getCommunalLivingScores($letter = false) {
        return $this->_getTypeScores(self::TYPE_COMMUNAL_LIVING, $letter);
    }

    /**
     * Get an array of food scores, all of the letter passed in or the most recent if $letter is false
     *
     * @param string|bool $letter
     *
     * @return JcdhFood[]
     */
    public function getFoodScores($letter = false) {
        return $this->_getTypeScores(self::TYPE_FOOD, $letter);
    }

    public function getHotelScores($letter = false) {
        return $this->_getTypeScores(self::TYPE_HOTEL, $letter);
    }

    public function getPoolScores($letter = false) {
        return $this->_getTypeScores(self::TYPE_POOL, $letter);
    }

    private function getTanningScores($letter = false) {
        return $this->_getTypeScores(self::TYPE_TANNING, $letter);
    }

    public function getScores($types = self::TYPE_FOOD) {
        if (is_string($types)) {
            $types = explode(',', $types);
        }

        $scores = [];
        foreach ($types as $type) {
            $function = false;

            switch($type) {
                case self::TYPE_COMMUNAL_LIVING:
                    $function = 'getCommunalLivingScores';
                    break;
                case self::TYPE_FOOD:
                    $function = 'getFoodScores';
                    break;
                case self::TYPE_HOTEL:
                    $function = 'getHotelScores';
                    break;
                case self::TYPE_POOL:
                    $function = 'getPoolScores';
                    break;
                case self::TYPE_TANNING:
                    $function = 'getTanningScores';
                    break;
            }

            if ($function) {
                $scores[$type] = [];

                foreach ($this->_getLetters() as $letter) {
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