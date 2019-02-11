<?php

use CompanyName\Payments\SMSPayment as SMSPayment;
require_once 'vendor/autoload.php';

class SmsPaymentTest extends \PHPUnit_Framework_TestCase
{
    public function testIfSmsPaymentClassGetsRightPrice(){
        $smsPayment = new SMSPayment("../../input.json");
        $this->assertEquals($SMSPayment->getTotalPrice(), 11.5)
    }
    public function testIfSmsPaymentClassGetsRightIncome(){
        $smsPayment = new SMSPayment("../../input.json");
        $this->assertEquals($SMSPayment->getTotalIncome(), 11.02)
    }
    public function testIfSmsPaymentClassReturnsRightArray(){
        $smsPayment = new SMSPayment("../../input.json");
        $array =[0.5, 2, 3, 3];
        $this->assertEquals($SMSPayment->getSmsPriceArrayJSON(), $array)
    }
}
