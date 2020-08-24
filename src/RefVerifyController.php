<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class RefVerifyController
{

    public function __construct(
        PDO $pdo,
        ContainerInterface $c,
        UserOps $userOps,
        TokenOps $tokenOps
    ) {
        $this->pdo = $pdo;
        $this->otp_timeout = $c->get('settings')['otp_timeout'];
        $this->userOps = $userOps;
        $this->tokenOps = $tokenOps;
    }


    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {


        // receive ref token
        $postData = (array)$request->getParsedBody();

        // check post vars
        if (!array_key_exists("ref", $postData) || !array_key_exists("fb_token", $postData)) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign vars
        $ref_token  = $request->getParsedBody()['ref'];
        $fb_token  = $request->getParsedBody()['fb_token'];

        // decode ref token
        $id = $this->tokenOps->decodeRefToken($ref_token);

        // if invalid return
        if ($id == "invalid") {
            return $this->errorReturn($request, $response, "Token Invalid");
        } else {
            $auth = $this->tokenOps->createAuthToken($id);

            $this->userOps->updateFirebaseTokenIfNew($id, $fb_token);
        }


        // get cars 
        $cars = $this->userOps->getCarsByID($id);

        // HTTP response        
        $ret_data = array();
        $ret_data['auth'] = $auth;
        $ret_data['cars'] =  $cars;
        $ret_data['pending'] =  "";

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
