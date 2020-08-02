<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class PumpScanController
{

    private $pdo;
    private $otp_timeout;

    public function __construct(PDO $pdo, ContainerInterface $c, TransactionOps $transactionOps)
    {
        $this->pdo = $pdo;
        $this->transactionOps = $transactionOps;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {


        // get token
        $json = $request->getQueryParams();

        // check get vars
        if (!array_key_exists("qr", $json)) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign vars
        $qr = $json['qr'];

        // get all results into array
        $payment_result = $this->transactionOps->getPendingTransDetails($qr);

        // check if exists
        if (!$payment_result['exists']) {
            return $this->errorReturn($request, $response, "QR Invalid");
        }

        // check payment status
        // check if payment is completed
        if ($payment_result['payment_status'] == "pending") {
            return $this->errorReturn($request, $response, "Payment Pending");
        }


        // payment successfull return amount, fuel_type
        $ret_data = array();
        $ret_data['amount'] =  $payment_result['amount'];
        $ret_data['fuel_type'] =  $payment_result['fuel_type'];


        // HTTP response        
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
