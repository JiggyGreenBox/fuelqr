<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class TransactionOps
{

    private $pdo;
    private $otp_timeout;

    public function __construct(PDO $pdo, ContainerInterface $c)
    {
        $this->pdo = $pdo;
    }

    // from pump scan action
    public function getPendingTransDetails($qr)
    {
        // init ret array
        $ret_array = array();


        // query DB
        $stmt = $this->pdo->prepare('SELECT * FROM pending_transactions WHERE :qr IN(car_qr,trans_qr)');
        $stmt->execute([
            'qr'     => $qr
        ]);
        $row = $stmt->fetch();

        // no match
        if (!$row) {
            $ret_array['exists'] = false;
            return $ret_array;
        }
        // match found 
        else {

            // return status pending
            if ($row['payment_status'] == "pending") {
                $ret_array['exists'] = true;
                $ret_array['payment_status'] = "pending";
                return $ret_array;
            }

            // return status success
            // return amount fuel type
            elseif ($row['payment_status'] == "success") {
                $ret_array['exists'] = true;
                $ret_array['payment_status'] = "success";
                $ret_array['amount'] = $row['amount'];
                $ret_array['fuel_type'] = $row['fuel_type'];
                return $ret_array;
            }
        }

        return $ret_array;
    }



    public function createPendingTransaction($id, $amount, $fuel_type, $car_id)
    {

        // ret array
        $ret_array = array();

        // find car_qr if exists
        // TODO prevent 2 transactions on a single car
        if ($car_id == "") {
            $car_id = NULL;
            $car_qr = NULL;
            $ret_array['hasCarQR'] = false;
        }
        // $car_qr = "";

        // payment status
        $payment_status = "success";

        // create trans qr
        $trans_qr = $this->getNewTransactionQR();

        // curtime
        $curtime = strtotime("now");

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO pending_transactions (user_id, car_id, car_qr, trans_qr, amount, fuel_type, payment_status, time_created)
                VALUES (:user_id, :car_id, :car_qr, :trans_qr, :amount, :fuel_type, :payment_status, :time_created)'
            );
            $stmt->execute([
                'user_id'     => $id,
                'car_id'     => (int)$car_id,
                'car_qr'     => $car_qr,
                'trans_qr'     => $trans_qr,
                'amount'     => $amount,
                'fuel_type'     => $fuel_type,
                'payment_status'     => $payment_status,
                'time_created'     => $curtime
            ]);
        } catch (\Exception $e) {

            // throw $e;
            $ret_array['success'] = false;
            return $ret_array;
        }

        $ret_array['success'] = true;
        $ret_array['trans_qr'] = $trans_qr;
        return $ret_array;
    }


    private function getNewTransactionQR()
    {
        // generate a random string
        $length = 10;
        $trans_qr = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 1, $length);

        // check if exists
        if ($this->isDuplicateTransQR($trans_qr)) {
            // recursion
            return $this->getNewTransactionQR();
        }
        // return string
        else {
            return $trans_qr;
        }
    }

    private function isDuplicateTransQR($trans_qr)
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM pending_transactions WHERE trans_qr =  :trans_qr');
        $stmt->execute([
            'trans_qr'     => $trans_qr
        ]);
        $row = $stmt->fetch();
        // result found
        if ($row) {
            return true;
        }
        return false;
    }
}
