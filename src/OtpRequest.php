<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class OtpRequest
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

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        // receive number
        $postData = (array)$request->getParsedBody();
        $ph_no = $postData['ph_no'];

        // sanitize phone number here
        if (strlen($ph_no) < 10) {

            $err_data = array("message" => "Invalid phone number.");

            // HTTP response
            $response->getBody()->write(json_encode($err_data));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(409);
        }

        // generate otp
        // change to TOTP implementation
        // will require change of DB structure too
        $otp = rand(1000, 9999);

        // curtime time
        $curtime = time();

        //---------------------------
        // add to database
        //---------------------------

        // select from db where phno
        $stmt = $this->pdo->prepare('SELECT otp_req_timestamp FROM otp_request WHERE otp_req_ph_no = :ph_no');
        $stmt->execute([
            'ph_no'     => $ph_no,
        ]);
        $row = $stmt->fetch();

        $send_msg = false;


        if (!$row) {

            // if not data insert phno, otp, storetime

            $stmt = $this->pdo->prepare('INSERT INTO otp_request (otp_req_ph_no, otp_req_otp, otp_req_timestamp) VALUES (:ph_no, :otp, :curtime)');
            $stmt->execute([
                'ph_no'     => $ph_no,
                'otp'       => $otp,
                'curtime'   => $curtime,
            ]);

            // send msg
            $send_msg = true;
        } else {
            
            $stored_timestamp = $row['otp_req_timestamp'];
            // else check if curtime - storetime > interval
            if (($curtime - $stored_timestamp) > $this->otp_timeout) {
                // update new otp
                // set new stored_time
                $stmt = $this->pdo->prepare('UPDATE otp_request SET otp_req_otp=:otp, otp_req_timestamp = :curtime WHERE otp_req_ph_no = :ph_no');
                $stmt->execute([
                    'ph_no'     => $ph_no,
                    'otp'       => $otp,
                    'curtime'   => $curtime,
                ]);

                // send msg
                $send_msg = true;
            } else {

                // else possible spam
                // no message
                $err_data = array("message" => "Too many requests.");

                // HTTP response
                $response->getBody()->write(json_encode($err_data));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(409);
            }

            // send msg call

        }



        // HTTP response
        $otp_data = array("otp" => $otp);
        $response->getBody()->write((string)json_encode($otp_data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }
}
