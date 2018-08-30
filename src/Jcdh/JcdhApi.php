<?php
namespace Encounting\Jcdh;

use Encounting\Jcdh\Enums\JcdhEventTargets;
use Encounting\Jcdh\Enums\JcdhOutputs;
use Encounting\Jcdh\Enums\JcdhTypes;
use Encounting\Jcdh\Enums\JcdhUrls;
use Encounting\Jcdh\Models\JcdhCommunalLiving;
use Encounting\Jcdh\Models\JcdhDeduction;
use Encounting\Jcdh\Models\JcdhFood;
use Encounting\Jcdh\Models\JcdhHotel;
use Encounting\Jcdh\Models\JcdhPool;
use Encounting\Jcdh\Models\JcdhTanning;
use Encounting\Jcdh\Models\JcdhType;
use simple_html_dom_node;
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

    /**
     * Build the URL for requesting a specific type
     *
     * @param JcdhTypes $type
     * @param string    $letter
     * @return bool|string
     */
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

    /**
     * Convert html to JcdhCommunalLiving
     *
     * @param simple_html_dom_node[] $tds
     *
     * @return bool|JcdhCommunalLiving
     */
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

    /**
     * Convert html to JcdhFood
     *
     * @param simple_html_dom_node[] $tds
     *
     * @return bool|JcdhFood
     */
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

    /**
     * Convert html to JcdhHotel
     *
     * @param simple_html_dom_node[] $tds
     *
     * @return bool|JcdhHotel
     */
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

    /**
     * Convert html to JcdhPool
     *
     * @param simple_html_dom_node[] $tds
     *
     * @return bool|JcdhPool
     */
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

    /**
     * Convert html to JcdhTanning
     *
     * @param simple_html_dom_node[] $tds
     *
     * @return bool|JcdhTanning
     */
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

    /**
     * Get the value of input by input id
     *
     * @param simple_html_dom_node $html
     * @param string               $id
     *
     * @return bool|mixed
     */
    private function _findById($html, $id) {
        $returnVar = false;

        $results = $html->find('input#'.$id);
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

    /**
     * Get the event target from html
     *
     * @param simple_html_dom_node $html
     * @param JcdhTypes            $type
     *
     * @return bool|mixed|string
     */
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

    /**
     * Get the event validation from html
     *
     * @param simple_html_dom_node $html
     * @param JcdhTypes            $type
     *
     * @return bool|mixed
     */
    private function _getEventValidation($html, $type) {
        $eventValidation = $this->_findById($html, '__EVENTVALIDATION');

        if ($eventValidation === false) {
            error_log('Unable to find the event validation for: '.$type);
        }

        return $eventValidation;
    }

    /**
     * Get the latitude and longitude from an address
     *
     * @param string $address
     *
     * @return mixed
     */
    private function _getLatLng($address) {
        $prepAddr = str_replace(' ', '+', $address);
        $geocode = file_get_contents(JcdhUrls::GOOGLE_GEOCODE.'?address=' . $prepAddr . '&sensor=false&key=');
        $output = json_decode($geocode);

        return $output->results[0]->geometry->location;
    }

    /**
     * Get an array of all alpha characters
     *
     * @return string[]
     */
    private function _getLetters() {
        return str_split($this->_LETTERS);
    }

    /**
     * Get the number of available pages from html
     *
     * @param simple_html_dom_node $html
     * @param JcdhTypes            $type
     *
     * @return int
     */
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

    /**
     * Generic function for retrieving scores
     *
     * @param JcdhTypes   $type
     * @param string|bool $letter
     *
     * @return JcdhType[]
     */
    private function _getTypeScores($type, $letter = false) {
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

    /**
     * Get the view state from the html
     *
     * @param simple_html_dom_node $html
     * @param JcdhTypes            $type
     *
     * @return bool|mixed
     */
    private function _getViewState($html, $type) {
        $viewState = $this->_findById($html, '__VIEWSTATE');

        if ($viewState === false) {
            error_log('Unable to find the view state for: '.$type);
        }

        return $viewState;
    }

    /**
     * Get the view state generator from html
     *
     * @param simple_html_dom_node $html
     * @param JcdhTypes            $type
     *
     * @return bool|mixed
     */
    private function _getViewStateGenerator($html, $type) {
        $viewStateGenerator = $this->_findById($html, '__VIEWSTATEGENERATOR');

        if ($viewStateGenerator === false) {
            error_log('Unable to find the view state generator for: '.$type);
        }

        return $viewStateGenerator;
    }

    /**
     * Convert the results to the expected output
     *
     * @param mixed $results
     *
     * @return mixed|string
     */
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

    /**
     * Split the name from the address
     *
     * @param string $string
     *
     * @return array[]|false|string[]
     */
    private function _splitNameAddress($string) {
        return preg_split('/\n|\r\n?/', $string);
    }

    /**
     * Get an array of food scores, all of the letter passed in or the most recent if $letter is false
     *
     * @param string|bool $letter
     *
     * @return JcdhCommunalLiving[]|mixed|string
     */
    public function getCommunalLivingScores($letter = false) {
        return $this->_getTypeScores(JcdhTypes::COMMUNAL_LIVING, $letter);
    }

    /**
     * Get an array of food scores, all of the letter passed in or the most recent if $letter is false
     *
     * @param string|bool $letter
     *
     * @return JcdhFood[]|mixed|string
     */
    public function getFoodScores($letter = false) {
        return $this->_getTypeScores(JcdhTypes::FOOD, $letter);
    }

    /**
     * Get an array of hotel scores, all of the letter passed in or the most recent if $letter is false
     *
     * @param string|bool $letter
     *
     * @return JcdhHotel[]|mixed|string
     */
    public function getHotelScores($letter = false) {
        return $this->_getTypeScores(JcdhTypes::HOTEL, $letter);
    }

    /**
     * Get an array of pool scores, all of the letter passed in or the most recent if $letter is false
     *
     * @param string|bool $letter
     *
     * @return JcdhPool[]|mixed|string
     */
    public function getPoolScores($letter = false) {
        return $this->_getTypeScores(JcdhTypes::POOL, $letter);
    }

    /**
     * Get an array of tanning scores, all of the letter passed in or the most recent if $letter is false
     *
     * @param string|bool $letter
     *
     * @return JcdhTanning[]|mixed|string
     */
    public function getTanningScores($letter = false) {
        return $this->_getTypeScores(JcdhTypes::TANNING, $letter);
    }

    /**
     * Get the scores for a range any children of JcdhType
     *
     * @param string|JcdhTypes $types
     * @param string|bool      $letters
     *
     * @return JcdhType|mixed|string
     */
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
                } else if (is_string($letters)) {
                    $letters = str_split($letters);
                }

                foreach ($letters as $letter) {
                    $moreScores = $this->$function($letter);

                    $scores[$type] = array_merge($scores[$type], $moreScores);
                }
            }
        }

        return $this->_processResults($scores);
    }

    /**
     * Get an item's deductions based on the the date, if the date is null then
     * it will retrieve the most recent
     *
     * @param JcdhType    $item
     * @param string|null $date
     *
     * @return mixed
     */
    public function getDeductions($item, $date = null) {
        switch (get_class($item)) {
            case JcdhFood::class:
                /** @var JcdhFood $item */
                $item->deductions = $this->getFoodDeductions($item->permit_no, $date);
                break;
            case JcdhHotel::class:
                /** @var JcdhHotel $item */
                $item->deductions = $this->getHotelDeductions($item->establishment_number, $date);
                break;
            default:
                break;
        }

        return $item;
    }

    /**
     * Get a food deductions based on the permitNo and the date, if the date is null then
     * it will retrieve the most recent
     *
     * @param string      $permitNo
     * @param string|null $date
     *
     * @return mixed
     */
    public function getFoodDeductions($permitNo, $date = null) {
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

                    preg_match('/[0-9]{1}-[0-9]+\.[0-9]+(\([A-Z]\))*\s/', $notes,$complianceNumber);
                    if ($complianceNumber && is_array($complianceNumber) && count($complianceNumber) > 0) {
                        $complianceNumber = $complianceNumber[0];

                        $deduction->compliance_number = trim($complianceNumber);
                        $notes = str_replace($deduction->compliance_number, '', $notes);
                    }

                    $deduction->notes = [];

                    $notes = explode('.', $notes);
                    foreach ($notes as $i => $note) {
                        $note = trim($note);

                        if (strlen($note) > 0) {
                            if ($i === 0) {
                                $deduction->compliance_details = $note;
                            } else {
                                $deduction->notes[] = $note;
                            }
                        }
                    }

                    $deductions[] = $deduction;
                }
            }
        } else {
            error_log('Unable to find the deduction table.');
        }

        return $deductions;
    }

    /**
     * Get a hotel's deductions based on the establishment number and the date, if the date is null then
     * it will retrieve the most recent
     *
     * @param string      $establishmentNumber
     * @param string|null $date
     *
     * @return mixed
     */
    public function getHotelDeductions($establishmentNumber, $date = null) {
        return [];
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

// TODO: Add key manager for Google Geocode
// TODO: Include lat, long it geocode key is provided
// TODO: Get old results too when available
// TODO: Get deductions based on the date