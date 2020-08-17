<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class CompletedTransactionController
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

        // receive ref token
        $postData = (array)$request->getParsedBody();

        // check post vars
        if (
            (!array_key_exists("qr", $postData)) ||
            (!array_key_exists("liters", $postData)) ||
            (!array_key_exists("rate", $postData)) ||
            (!array_key_exists("shift", $postData)) ||
            (!array_key_exists("attendant_id", $postData))
        ) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign vars
        $qr             = $postData['qr'];
        $liters         = $postData['liters'];
        $rate           = $postData['rate'];
        //$shift          = $postData['shift'];
        $attendant_id   = $postData['attendant_id'];
        $shift 			= ($postData['shift'] == "a") ? 1 : 2;

        // get details from pending transactions
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


        // move from pending to completed table        
        $moveStatus = $this->transactionOps->movePendingToCompleted($qr, $liters, $rate, $shift, $attendant_id);
        if (!$moveStatus['success']) {
            return $this->errorReturn($request, $response, "Transaction Error");
        }

        

        // move successfull return success to android
        $ret_data = array();
        $ret_data['success'] = true;

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
