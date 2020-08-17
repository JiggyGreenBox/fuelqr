<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class PostVideoCheckController
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

        $ret_bool = false;

        // get token
        $json = $request->getQueryParams();

        // check post vars
        if ((!array_key_exists("t_string", $json)) || (!array_key_exists("t_date", $json))) {
            return $this->errorReturn($request, $response, "Missing Params");
        }

        // assign vars
        $t_string   = $json['t_string'];
        $t_date     = $json['t_date'];

        // check if exists in completed transactions
        if ($this->transactionOps->existsInCompletedTransactions($t_string, $t_date)) {
            $ret_bool = true;
        }


        // move successfull return success to android
        $ret_data = array();
        $ret_data['success'] = $ret_bool;

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
