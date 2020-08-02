<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;

use \Firebase\JWT\JWT;


final class TokenOps
{


    public function __construct(PDO $pdo, ContainerInterface $c)
    {
        $this->pdo = $pdo;
        $this->ref_token_expiry = $c->get('settings')['ref_token_expiry'];
        $this->auth_token_expiry = $c->get('settings')['auth_token_expiry'];
        $this->token_key = $c->get('settings')['token_key'];
    }



    public function createAuthToken($id)
    {
        $curtime = strtotime("now");
        $expiry = strtotime($this->auth_token_expiry);

        $payload = array(
            "iss" => "http://example.org",
            "aud" => "http://example.com",
            "iat" => $curtime,
            "exp" => $expiry,
            "id" => $id
        );

        $jwt = JWT::encode($payload, $this->token_key);
        return $jwt;
    }

    public function createRefToken($id)
    {




        $curtime = strtotime("now");
        $expiry = strtotime($this->ref_token_expiry);

        $payload = array(
            "iss" => "http://example.org",
            "aud" => "http://example.com",
            "iat" => $curtime,
            "exp" => $expiry,
            "id" => $id
        );

        $jwt = JWT::encode($payload, $this->token_key);

        $stmt = $this->pdo->prepare('UPDATE users SET refresh_token=:ref WHERE user_id = :id');
        $stmt->execute([
            'id' => $id,
            'ref' => $jwt
        ]);

        return $jwt;
    }


    public function decodeAuthToken($auth_token)
    {

        try {

            $decoded = (array)JWT::decode($auth_token, $this->token_key, array('HS256'));
            $id = $decoded['id'];            

        } catch (\Exception $e) {
            $id = "invalid";
        }
        return $id;
    }


    public function decodeRefToken($ref_token)
    {

        try {

            $decoded = (array)JWT::decode($ref_token, $this->token_key, array('HS256'));

            $curtime = strtotime("now");
            $exp = $decoded['exp'];
            $id = $decoded['id'];

            // check for collisions
            if(!$this->refIdMatch($id, $ref_token)){
                $id = "invalid";
            }

        } catch (\Exception $e) {
            $id = "invalid";
        }
        return $id;
    }

    private function refIdMatch($id, $ref_token)
    {
        // ALL FALSE SHOULD BE LOGGED

        $stmt = $this->pdo->prepare('SELECT refresh_token FROM users WHERE user_id = :id');
        $stmt->execute([
            'id'     => $id
        ]);
        $row = $stmt->fetch();
        // result found
        if ($row) {
            // token mismatch
            if ($row['refresh_token'] != $ref_token) {
                // should log here
                return false;
            }

            // id and ref match user is valid
            return true;
        }
        // no ref found for user
        return false;
    }
}
