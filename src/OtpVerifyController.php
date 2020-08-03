<?php

namespace App;

use App\TestToken\CreateAuthToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;

use App\UserOps;
use Exception;

final class OtpVerifyController
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
        if (!array_key_exists("ph_no", $postData) || !array_key_exists("otp", $postData)) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign vars
        $ph_no  = $request->getParsedBody()['ph_no'];
        $otp    = $request->getParsedBody()['otp'];

        // TODO        
        // sanitize phone number

        // verify otp request        
        $isValid = $this->otpOps->isValidOtp($ph_no, $otp);

        // if invalid return
        if (!$isValid) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // check if id exists
        $id = $this->userOps->getIdByPhoneNo($ph_no);

        // new user
        if ($id == -1) {
            // create user, returns -1 if error
            $id = $this->userOps->createNewUser($ph_no);
            if ($id == -1) {
                return $this->errorReturn($request, $response, "Error Creating User");
            }
            // createAuthToken
            $auth = $this->tokenOps->createAuthToken($id);
            // createRefToken
            $ref = $this->tokenOps->createRefToken($id);
            // make empty cars
            // make empty pending
        } else {
            // CreateAuthToken
            $auth = $this->tokenOps->createAuthToken($id);
            // createRefToken
            $ref = $this->tokenOps->createRefToken($id);
            // chekc cars
            // check pending
        }


        $ret_data = array();
        $ret_data['auth'] = $auth;
        $ret_data['ref'] =  $ref;
        $ret_data['cars'] =  "";
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
