<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class PaytmOps
{

    private $pdo;
    private $otp_timeout;

    public function __construct(PDO $pdo, ContainerInterface $c)
    {
        $this->pdo = $pdo;
        $this->paytm_mid = $c->get('settings')['paytm_mid'];
        $this->paytm_merchant_key = $c->get('settings')['paytm_merchant_key'];
    }


    public function initiateTransationRequest($amount, $orderId, $user_id)
    {
        // initialize an array
        $paytmParams = array();

        // for UPI
        $myObj = new   \stdClass();
        $myObj->mode = "UPI";
        $myObj->channels = array("UPIPUSH");

        // body parameters
        $paytmParams["body"] = array(
            "requestType"   => "Payment",
            "mid"           => $this->paytm_mid,
            "websiteName"   => "WEBSTAGING",
            "orderId"       => $orderId,
            "callbackUrl"   => "https://merchant.com/callback",
            "txnAmount"     => array(
                "value"     => $amount,
                "currency"  => "INR",
            ),
            // "enablePaymentMode" => array(
            //     $myObj
            // ),
            "userInfo"      => array(
                "custId"    => $user_id,
            ),
        );

        $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), $this->paytm_merchant_key);

        $paytmParams["head"] = array(
            "signature"    => $checksum
        );

        $post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

        /* for Staging */
        //$url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=UaOfbG68292644480647&orderId=ORDERID_987";
        $url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=" . $this->paytm_mid . "&orderId=" . $orderId;

        /* for Production */
        // $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=YOUR_MID_HERE&orderId=ORDERID_98765";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        $response = curl_exec($ch);
        //print_r($response);
        return $response;
    }
}
