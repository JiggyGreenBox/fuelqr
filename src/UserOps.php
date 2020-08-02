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
}
