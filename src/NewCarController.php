<?php

namespace App;

use App\TestToken\CreateAuthToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;

use App\UserOps;
use Exception;

final class NewCarController
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


        // receive number
        $postData = (array)$request->getParsedBody();

        // check post vars
        if (
            !array_key_exists("trans_qr", $postData) ||
            !array_key_exists("car_plate_no", $postData) ||
            !array_key_exists("car_qr", $postData) ||
            !array_key_exists("car_fuel_type", $postData)
        ) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign vars
        $trans_qr       = $request->getParsedBody()['trans_qr'];
        $car_plate_no   = $request->getParsedBody()['car_plate_no'];
        $car_qr         = $request->getParsedBody()['car_qr'];
        $car_fuel_type  = $request->getParsedBody()['car_fuel_type'];

        // some validation
        if ($car_plate_no == "") {
            return $this->errorReturn($request, $response, "Blank values not allowed");
        }

        if (($car_fuel_type != "petrol") && ($car_fuel_type != "diesel")) {
            return $this->errorReturn($request, $response, "Invalid values");
        }



        // verify user id from transqr
        $id = $this->userOps->getIdByTransQR($trans_qr);
        // if invalid return
        if ($id == -1) {
            return $this->errorReturn($request, $response, "Invalid Trans QR");
        }
        // id is valid
        else {
            // check if car qr exists in database
            // check if car qr already assigned
            $qr_status = $this->userOps->getQRCodeStatus($car_qr);

            if (!$qr_status['exists']) {
                return $this->errorReturn($request, $response, "Invalid Car QR");
            }

            if ($qr_status['assigned']) {
                return $this->errorReturn($request, $response, "Car QR already assigned");
            }



            // insert new car
            $ret = $this->userOps->insertNewCar($id, $car_qr, $car_plate_no, $car_fuel_type);
            if (!$ret) {
                return $this->errorReturn($request, $response, "Car not assigned");
            }
        }


        // HTTP response
        $otp_data = array("success" => true);
        $response->getBody()->write((string)json_encode($otp_data));
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
