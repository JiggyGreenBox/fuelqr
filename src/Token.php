<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PDO;

use \Firebase\JWT\JWT;

final class Token
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

        $key = "example_key";
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

        // Build the HTTP response
        $response->getBody()->write((string)json_encode(strtotime("+3 hours")));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }
}
