<?php

namespace App;

use App\TestToken\CreateAuthToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;

use App\UserOps;
use Exception;

final class CarAndPendingController
{


    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
        UserOps $userOps,
        OtpVerify $otpVerify,
        TokenOps $tokenOps
    ) {
        $this->pdo = $pdo;
        $this->otp_timeout = $c->get('settings')['otp_timeout'];
        $this->userOps = $userOps;
        $this->otpOps = $otpVerify;
        $this->tokenOps = $tokenOps;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {



        $cars = array();
        $car = array("id" => 1, "plate" => "MH04CT1374");
        array_push($cars, $car);

        $ret_data = array();
        $ret_data['cars'] =  $cars;
        $ret_data['pending'] =  "";



        // HTTP response
        $otp_data = array("otp" => "working");
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
