<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class OtpVerify
{

    private $pdo;
    private $otp_timeout;
    /**
     * Constructor.
     *
     * @param PDO $connection The database connection
     */
    public function __construct(PDO $pdo, ContainerInterface $c)
    {
        $this->pdo = $pdo;
        $this->otp_timeout = $c->get('settings')['otp_timeout'];
    }



    public function isValidOtp($ph_no, $otp)
    {
        $curtime = strtotime("now");
        $stmt = $this->pdo->prepare('SELECT * FROM otp_request WHERE otp_req_ph_no = :ph_no AND otp_req_otp = :otp');
        $stmt->execute([
            'ph_no'     => $ph_no,
            'otp'     => $otp
        ]);
        $row = $stmt->fetch();
        if ($row) {
            $stored_timestamp = $row['otp_req_timestamp'];
            if (($curtime - $stored_timestamp) < $this->otp_timeout) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
