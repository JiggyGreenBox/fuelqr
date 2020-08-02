<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PDO;

use \Firebase\JWT\JWT;

final class TestToken
{
    // private $userCreator;

    private $pdo;

    /**
     * Constructor.
     *
     * @param PDO $connection The database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // // Collect input from the HTTP request
        // $data = (array)$request->getParsedBody();

        // // Invoke the Domain with inputs and retain the result
        // $userId = $this->userCreator->createUser($data);

        // Transform the result into the JSON representation
        $result = [
            'user_id' => 123,
        ];

        $user_id = $args['id'];

        $key = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9";
        $payload = array(
            "iss" => "http://example.org",
            "aud" => "http://example.com",
            "iat" => 1356999524,
            "nbf" => 1357000000,
            "id" => 123
        );

        /**
         * IMPORTANT:
         * You must specify supported algorithms for your application. See
         * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
         * for a list of spec-compliant algorithms.
         */
        $jwt = JWT::encode($payload, $key);
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        // print_r($decoded);
        $data = $this->createRefreshToken($user_id);

        // Build the HTTP response
        $response->getBody()->write((string)json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    // on sign-up
    // on log-in
    // issue token
    // remove old token
    private function createRefreshToken($id)
    {

        // check if exists
        $stmt = $this->pdo->prepare('SELECT refresh_token FROM users WHERE user_id = :user_id');
        $stmt->execute([
            'user_id'     => $id,
        ]);

        $row = $stmt->fetch();
        $ret_array = array();

        // if data, user logged out or changed device
        if ($row) {
            // update old tokens
            $ref_token = $this->constructRefreshToken($id);
            $auth_token = $this->constructAuthToken($id);
            array_push($ret_array, array('ref_token' => $ref_token));
            array_push($ret_array, array('auth_token' => $auth_token));

            $stmt = $this->pdo->prepare('UPDATE users SET refresh_token=:refresh_token WHERE user_id=:user_id');
            $stmt->execute([
                'user_id'     => $id,
            ]);
        }
        // if data, user logged out or changed device 
        else {
            // must be user before reaching here
            // error   
            array_push($ret_array,"jiggy");            
        }

        return $ret_array;
    }

    private function constructRefreshToken($id)
    {

        $curtime = strtotime("now");
        $expiry = strtotime("+1 year");

        $key = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9";
        $payload = array(
            "iss" => "http://example.org",
            "aud" => "http://example.com",
            "iat" => $curtime,
            "exp" => $expiry,
            "id" => $id
        );

        $jwt = JWT::encode($payload, $key);
        return $jwt;
    }

    private function constructAuthToken($id)
    {

        $curtime = strtotime("now");
        $expiry = strtotime("+1 hour");

        $key = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9";
        $payload = array(
            "iss" => "http://example.org",
            "aud" => "http://example.com",
            "iat" => $curtime,
            "exp" => $expiry,
            "id" => $id
        );

        $jwt = JWT::encode($payload, $key);
        return $jwt;
    }
}
