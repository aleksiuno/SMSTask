<?php
//Uses the composer autoload functionality.
use CompanyName\Payments\SMSPayment as SMSPayment;
require_once 'vendor/autoload.php';

//Gets imput from the console and stores to the $input variable.
$input = file_get_contents($argv[1]);
//Creates new instane of SMSPayment object.
$smsPayment = new SMSPayment($input);
//Outputs stream with JSON object
fwrite(STDOUT, $smsPayment->getSmsPriceArrayJSON());
echo "\n";
