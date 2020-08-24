<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class TestPayments
{

    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
        PaytmChecksum $paytmChecksum        
    ) {
        $this->pdo = $pdo;
        $this->otp_timeout = $c->get('settings')['otp_timeout'];      
        $this->paytmChecksum = $paytmChecksum;
    }


    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        /* initialize an array */
        $paytmParams = array();

        /* body parameters */
        $paytmParams["body"] = array(

            /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
            "mid" => "UaOfbG68292644480647",

            /* Enter your order id which needs to be check status for */
            "orderId" => "ORDERID_987",
        );

        $checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), "xGh5gDzSXq71HK7B");


        // HTTP response        
        $ret_data = array();
        $ret_data['paytm'] =  $checksum;

        $response->getBody()->write((string)json_encode($ret_data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }


    private function errorReturn(
        ServerRequestInterface $request,
        ResponseInterface $response,
        $message
    ) {
        $err_data = array("message" => $message);

        // HTTP response
        $response->getBody()->write(json_encode($err_data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(409);
    }
}
