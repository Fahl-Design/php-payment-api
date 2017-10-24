<?php

namespace Heidelpay\Tests\PhpApi\Unit\PaymentMethods;

use Heidelpay\PhpApi\Response;
use Codeception\TestCase\Test;
use Heidelpay\PhpApi\PaymentMethods\Przelewy24PaymentMethod as Przelewy24;

/**
 * Przelewy24 Test
 *
 * Connection tests can fail due to network issues and scheduled down times.
 * This does not have to mean that your integration is broken. Please verify the given debug information
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/heidelpay-php-api/
 *
 * @author  Ronja Wann
 *
 * @package  Heidelpay
 * @subpackage PhpApi
 * @category UnitTest
 */
class Przelewy24PaymentMethodTest extends Test
{
    /**
     * @var array authentication parameter for heidelpay api
     */
    protected static $authentication = array(
        '31HA07BC8142C5A171745D00AD63D182', //SecuritySender
        '31ha07bc8142c5a171744e5aef11ffd3', //UserLogin
        '93167DE7',                         //UserPassword
        '31HA07BC811BAF9BED1129D1160BF318', //TransactionChannel
        true                                //Sandbox mode
    );

    /**
     * @var array customer address
     */
    protected static $customerDetails = array(
        'Heidel',                   //NameGiven
        'Berger-Payment',           //NameFamily
        'DevHeidelpay',             //NameCompany
        '1234',                     //IdentificationShopperId
        'Vagerowstr. 18',           //AddressStreet
        'DE-BW',                    //AddressState
        '69115',                    //AddressZip
        'Heidelberg',               //AddressCity
        'PL',                       //AddressCountry
        'development@heidelpay.de'  //Customer
    );

    /**
     * Transaction currency
     *
     * @var string currency
     */
    protected $currency = 'PLN';

    /**
     * Secret
     *
     * The secret will be used to generate a hash using
     * transaction id + secret. This hash can be used to
     * verify the the payment response. Can be used for
     * brute force protection.
     *
     * @var string secret
     */
    protected $secret = 'Heidelpay-PhpApi';

    /**
     * PaymentObject
     *
     * @var Przelewy24
     */
    protected $paymentObject;

    /**
     * Constructor used to set timezone to utc
     */
    public function __construct()
    {
        date_default_timezone_set('UTC');

        parent::__construct();
    }

    /**
     * Set up function will create a Przelewy24 object for each test case
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    // @codingStandardsIgnoreStart
    public function _before()
    {
        // @codingStandardsIgnoreEnd
        $Przelewy24 = new Przelewy24();
        $Przelewy24->getRequest()->authentification(...self::$authentication);
        $Przelewy24->getRequest()->customerAddress(...self::$customerDetails);
        $Przelewy24->_dryRun = true;

        $this->paymentObject = $Przelewy24;
    }

    /**
     * Get current called method, without namespace
     *
     * @param string $method
     *
     * @return string class and method
     */
    public function getMethod($method)
    {
        return substr(strrchr($method, '\\'), 1);
    }

    /**
     * Test case for a single Przelewy24 authorize
     *
     * @return string payment reference id for the Przelewy24 authorize transaction
     * @group connectionTest
     */
    public function testAuthorize()
    {
        $timestamp = $this->getMethod(__METHOD__) . ' ' . date('Y-m-d H:i:s');
        $this->paymentObject->getRequest()->basketData($timestamp, 23.12, $this->currency, $this->secret);
        $this->paymentObject->getRequest()->async('DE', 'https://dev.heidelpay.de');

        $this->paymentObject->authorize();

        /* prepare request and send it to payment api */
        $request = $this->paymentObject->getRequest()->convertToArray();
        /** @var Response $response */
        list(, $response) = $this->paymentObject->getRequest()->send($this->paymentObject->getPaymentUrl(), $request);

        $this->assertTrue($response->isSuccess(), 'Transaction failed : ' . print_r($response, 1));
        $this->assertFalse($response->isError(), 'authorize failed : ' . print_r($response->getError(), 1));

        return (string)$response->getPaymentReferenceId();
    }
}
