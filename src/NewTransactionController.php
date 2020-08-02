<?php

namespace App;

use App\TestToken\CreateAuthToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;

use App\UserOps;
use App\TokenOps;
use App\TransactionOps;
use Exception;

final class NewTransactionController
{


    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
        UserOps $userOps,
        OtpVerify $otpVerify,
        TokenOps $tokenOps,
        TransactionOps $transactionOps
    ) {
        $this->pdo = $pdo;
        $this->otp_timeout = $c->get('settings')['otp_timeout'];
        $this->userOps = $userOps;
        $this->otpOps = $otpVerify;
        $this->tokenOps = $tokenOps;
        $this->transactionOps = $transactionOps;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        // get user id
        $id = $request->getAttribute('user_id');


        // receive number
        $postData = (array)$request->getParsedBody();

        // check post vars
        if (
            !array_key_exists("amount", $postData)
            || !array_key_exists("fuel_type", $postData)
            || !array_key_exists("car_id", $postData)
        ) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign vars
        $amount  = $request->getParsedBody()['amount'];
        $fuel_type    = $request->getParsedBody()['fuel_type'];
        $car_id = $request->getParsedBody()['car_id'];

        $ret = $this->transactionOps->createPendingTransaction($id, $amount, $fuel_type, $car_id);

        if (!$ret['success']) {
            return $this->errorReturn($request, $response, "Transaction Error");
        }


        // return success
        $ret_data = array();
        $ret_data['pending_transaction'] =  "success";
        $ret_data['trans_qr'] = $ret['trans_qr'];
        $ret_data['hasCarQR'] = $ret['hasCarQR'];

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
