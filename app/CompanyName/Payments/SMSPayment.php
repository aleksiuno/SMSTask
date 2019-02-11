<?php
namespace CompanyName\Payments;
/**
 * SMSPayment
 *
 * The class is used to split the required payment between SMS messages. It uses the custom backtracking algorithm.
 * The class accepts the SMS price list with required income and max messages in JSON format. If the calculation
 * is successful then it can return split message prices and income in JSON format, the total income and
 * total price.
 *
 * @author     Darius Aleksiunas
 */

class SMSPayment
{
    /**
     * @var string $smsList decoded SMS associative multidimensional list
     */
    private $smsList;
    /**
     * @var int $reqIncome required income
     */
    private $reqIncome;
    /**
     * @var int $maxMessages maximum messages to be used
     */
    private $maxMessages;
    /**
     * @var array $smsPriceArray the final price list with SMS prices for the client
     */
    private $smsPriceArray = [];
    /**
     * @var array $smsIncomeArray the final income list with SMS prices
     */
    private $smsIncomeArray = [];
    /**
     * @var int $totalIncome the total income (value initialised to 999999999)
     */
    private $totalIncome = 999999999;
    /**
     * @var int $totalPrice the total price for client (value initialised to 999999999)
     */
    private $totalPrice = 999999999;

    /**
     *
     * Constructor
     *
     * Accepts initial data required for the class, automatically starts SMS payment calculation function.
     *
     * @param object $smsJson encoded SMS associative multidimensional array in JSON format
     */
    public function __construct($smsJson = null)
    {
        //if $smsJson equals null, throws an error else decodes SMS array and stores data to instance variables.
        if ($smsJson == null) {
            die('Error: smsJson must not be equal null.\n');
        } else {
            $this->smsJsonDecodeAndStore($smsJson);
        }
        //algorithm needs the array to be in ascending order to work more efficiently.
        $this->sortMsgsAscIncome();
        //starts SMS payment calculation function.
        $this->calcSmsPayment();
        //if calculation finished but SMS income array is empty, throws an error.
        if (count($this->smsIncomeArray) == 0) {
            die("Error: impossible to divide sufficient ammount of messages.\n");
        }
    }

    /**
     *
     * Returns total income
     *
     * @return int
     */
    public function getTotalIncome()
    {
        return $this->totalIncome;
    }

    /**
     *
     * Returns total price for client
     *
     * @return int
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     *
     * Returns array with split SMS prices for required payent in JSON format.
     *
     * @return object
     */
    public function getSmsPriceArrayJSON()
    {
        return json_encode($this->smsPriceArray);
    }

    /**
     *
     * Returns array with split SMS income for required payent in JSON format.
     *
     * @return object
     */
    public function getSmsIncomeArrayJSON()
    {
        return json_encode($this->smsIncomeArray);
    }

    /**
     *
     * Decodes JSON file and stores data to instance variables
     *
     * @param object $smsJson encoded SMS associative multidimensional array in JSON format
     */
    private function smsJsonDecodeAndStore($smsJson)
    {
        if (json_decode($smsJson, true) == null) {
            die('Error: smsJson decoding failed.\n');
        }
        $smsJsonDecoded = json_decode($smsJson, true);
        $this->smsList = $smsJsonDecoded['sms_list'];
        $this->reqIncome = $smsJsonDecoded['required_income'];
        if (array_key_exists("max_messages", $smsJsonDecoded)) {
            $this->maxMessages = $smsJsonDecoded['max_messages'];
        } else {
            $this->maxMessages = -1;
        }
    }

    /**
     *
     * Finds array root key
     *
     * @param string $childKey
     * @param float $childValue
     * @param array $array
     * @return int
     */
    private function findArrayRootKey($childKey, $childValue, $array)
    {
        return array_search($childValue, array_column($array, $childKey));
    }

    /**
     *
     * Sorts instance variable $smsList in ascending orded by "income" column.
     *
     */
    private function sortMsgsAscIncome()
    {
        array_multisort(array_column($this->smsList, "income"), SORT_ASC, $this->smsList);
    }

    /**
     *
     * Converts $smsIncomeArray instance variable to $smsPriceArray instance varieble.
     *
     */
    private function convertIncomeToPriceArray()
    {
        $tempArray = [];
        foreach($this->smsIncomeArray as $item){
            $tempArray[] = $this->smsList[$this->findArrayRootKey('income', $item, $this->smsList)]['price'];
        }
        return $tempArray;
    }

    /**
     *
     * The main class function used for spliting payment between SMS messages with particular price.
     *
     * It uses the custom Brute-FOrce algorithm. The main algorithm idea is to fill stack data structure
     * with largest SMS prices until it reaches or exceeds required income. Then pop large SMS price and fills
     * with smaller SMS prices until reaches or exceeds required income until it fills stack with smallest SMS prices.
     */
    private function calcSmsPayment()
    {
        // Instantiating local variables
        $pernListKey = count($this->smsList) - 1;
        $tempListKey = count($this->smsList) - 1;
        $incomeStack = [];
        $temptotalIncome = 0;
        $tempTotalPrice = 0;
        $popVal = 0;
        // When $pernListKey < 0, calculation is finished
        while ($pernListKey >= 0){
            // When $tempListKey is lesser than 0, pops all smallest SMS values and one before it
            if ($tempListKey < 0) {
                while ($popVal == $this->smsList[0]['income'] && count($incomeStack) > 0) {
                    $popVal = array_pop($incomeStack);
                    $temptotalIncome -= $popVal;
                    $popValRootKey = $this->findArrayRootKey('income', $popVal, $this->smsList);
                    $tempTotalPrice -= $this->smsList[$popValRootKey]['price'];
                }
                //If stack is empty but $pernListKey >= 0 then reduces $pernListKey value by 1
                if (count($incomeStack) == 0) {
                    $pernListKey -= 1;
                    $tempListKey = $pernListKey;
                } else {//
                    $tempArrayKey = $this->findArrayRootKey('income', $popVal, $this->smsList);
                    $tempListKey = $this->smsList[$tempArrayKey]['income'];
                }
            // Check if stack income reached or exceeded required income
            } elseif ($temptotalIncome >= $this->reqIncome){
                // Checks if stack length is lesser or equal to required max length if $maxMessages == -1 it's not
                // determined.
                if (count($incomeStack) <= $this->maxMessages || $this->maxMessages == -1) {
                    /**
                     *
                     * Check if temporary total price is smaller than saved before OR temporary total price is equal
                     * like saved befor AND SMS stack length is smaller than befor.
                     * If statement is true saves the stack, income and price to instance variables as best cases.
                     *
                     */
                    if (
                        $tempTotalPrice < $this->totalPrice ||
                        $tempTotalPrice == $this->totalPrice &&
                        count($incomeStack) <=  count($this->smsIncomeArray)
                    ){
                        $this->totalPrice = $tempTotalPrice;
                        $this->totalIncome = $temptotalIncome;
                        $this->smsIncomeArray = $incomeStack;
                        $this->smsPriceArray = $this->convertIncomeToPriceArray();
                    }
                }
                $popVal = array_pop($incomeStack);
                $temptotalIncome -= $popVal;
                $tempTotalPrice -= $this->smsList[$this->findArrayRootKey('income', $popVal, $this->smsList)]['price'];
                $tempListKey -= 1;
            } else { // Keeps adding income to $incomeStack if income limit is not reached
                $incomeStack[] = $this->smsList[$tempListKey]['income'];
                $temptotalIncome += $this->smsList[$tempListKey]['income'];
                $tempTotalPrice += $this->smsList[$tempListKey]['price'];
            }
        }
    }
}
