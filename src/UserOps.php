<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class UserOps
{

    private $pdo;
    private $otp_timeout;

    public function __construct(PDO $pdo, ContainerInterface $c)
    {
        $this->pdo = $pdo;
    }


    public function getIdByPhoneNo($ph_no)
    {

        $stmt = $this->pdo->prepare('SELECT user_id FROM users WHERE user_ph_no = :ph_no');
        $stmt->execute([
            'ph_no'     => $ph_no,
        ]);
        $row = $stmt->fetch();

        if ($row) {
            return $row['user_id'];
        } else {
            return -1;
        }
    }

    public function createNewUser($ph_no)
    {

        // curtime
        $curtime = strtotime("now");

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO users(user_ph_no,date_created, last_updated) VALUES (:user_ph_no,:date_created, :last_updated)'
            );
            $stmt->execute([
                'user_ph_no'     => $ph_no,
                'date_created'     => $curtime,
                'last_updated'     => $curtime,
            ]);
            $id = $this->pdo->lastInsertId();
        } catch (\Exception $e) {
            throw $e;
            $id = -1;
        }
        return $id;
    }


    public function updateFirebaseTokenIfNew($id, $fb_token){
        $stmt = $this->pdo->prepare('SELECT firebase_token FROM users WHERE user_id= :user_id');
        $stmt->execute([
            'user_id'     => $id
        ]);
        $row = $stmt->fetch();

        // check if result
        if ($row) {
            // check if null
            if(!is_null($row['firebase_token'])){
                $stored_token = $row['firebase_token'];

                if($stored_token != $fb_token){
                    $this->updateFirebaseToken($id, $fb_token);
                }
            }
            // no token, update it
            else{
                $this->updateFirebaseToken($id, $fb_token);
            }          
        }
    }

    private function updateFirebaseToken($id, $fb_token){
        $stmt = $this->pdo->prepare('UPDATE users SET firebase_token = :fb_token WHERE user_id = :user_id');
        $stmt->execute([
            'user_id'     => $id,
            'fb_token'     => $fb_token
        ]);
    }
}
