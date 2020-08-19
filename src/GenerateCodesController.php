<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class GenerateCodesController
{

    private $pdo;
    private $otp_timeout;

    public function __construct(PDO $pdo, ContainerInterface $c, TransactionOps $transactionOps)
    {
        $this->pdo = $pdo;
        $this->transactionOps = $transactionOps;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        // receive post vars
        $postData = (array)$request->getParsedBody();

        // check post vars
        if (!array_key_exists("phno1", $postData) || !array_key_exists("phno2", $postData)) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign vars
        $phno1   = $request->getParsedBody()['phno1'];
        $phno2   = $request->getParsedBody()['phno2'];        

        if(($phno1 == "8411815106") && ($phno2=="9762230207")){
            $this->bulkCarQR(1000);
        }


        // payment successfull return amount, fuel_type
        $ret_data = array();
        $ret_data['success'] =  true;        


        // HTTP response        
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



    private function getNewCarQR()
    {
        // generate a random string
        $length = 15;
        $car_qr = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, $length);

        // check if exists
        if ($this->isDuplicateCarQR($car_qr)) {
            // recursion
            return $this->getNewCarQR();
        }
        // return string
        else {
            return $car_qr;
        }
    }


    private function isDuplicateCarQR($car_qr)
    {
        //$stmt = $this->pdo->prepare('SELECT 1 FROM pending_transactions WHERE trans_qr =  :trans_qr');
        $stmt = $this->pdo->prepare('SELECT 1 FROM codes WHERE qr_code =  :car_qr');
        $stmt->execute([
            'car_qr'     => $car_qr
        ]);
        $row = $stmt->fetch();
        // result found
        if ($row) {
            return true;
        }
        return false;
    }


    private function bulkCarQR($num){

        // TODO add transaction commit and rollback


        try {
            for($i=0; $i<$num; $i++) {

                $car_qr = $this->getNewCarQR();

                $stmt = $this->pdo->prepare('INSERT INTO `codes`(`qr_code`,`code_type`) VALUES (:qr_code, :code_type)');
                $stmt->execute([
                    'qr_code'     => $car_qr,
                    'code_type'   => "online"
                ]);
            }
        } catch (\Exception $e) {                        
            throw $e;
        }
    }
}
