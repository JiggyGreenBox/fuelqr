<?php

namespace App;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Psr7\Factory\ResponseFactory;
use PDO;

final class AuthCheck
{


    private $pdo;


    public function __construct(PDO $pdo, TokenOps $tokenOps)
    {
        $this->pdo = $pdo;
        $this->tokenOps = $tokenOps;
    }

    public function __invoke(
        Request $request,
        RequestHandler $handler
    ): Response {

        // get token
        $json = $request->getQueryParams();

        // check post vars
        if (!array_key_exists("token", $json)) {
            return $this->authCheckError("Missing Auth Token");
        }

        // assign
        try {
            $auth = $json['token'];
        } catch (\Exception $e) {
            return $this->authCheckError("Missing Auth Token");
        }

        // check if blank
        if ($auth == "") {
            return $this->authCheckError("Missing Auth Token");
        }

        // verify auth token
        $id = $this->tokenOps->decodeAuthToken($auth);
        if($id == "invalid"){
            return $this->authCheckError("Invalid Auth Token");
        }

        // pass it on further
        $request = $request->withAttribute('user_id', $id);


        $response = $handler->handle($request);
        // $existingContent = (string) $response->getBody();

        // $responseFactory = new ResponseFactory();
        // $response = $responseFactory->createResponse();
        // $response->getBody()->write($auth . $existingContent);

        return $response;
    }

    public function authCheckError($message)
    {

        $err_data = array("message" => $message);

        // HTTP response
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();
        $response->getBody()->write(json_encode($err_data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(409);
    }
}
