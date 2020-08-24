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

                if($qr == $row['car_qr']){
                    $ret_array['hasCarQR'] = true;
                }else{
                    $ret_array['hasCarQR'] = false;
                }


                return $ret_array;
            }
        }

        return $ret_array;
    }

    // from pump middleware server    
    public function existsInCompletedTransactions($trans_qr, $trans_date)
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM completed_transactions WHERE date(date) = :trans_date and trans_qr = :trans_qr');
        $stmt->execute([
            'trans_qr' => $trans_qr,
            'trans_date' => $trans_date
        ]);

        $row = $stmt->fetch();

        // match found
        if ($row) {
            return true;
        }
        return false;
    }

    // from pump-op afterStopPhoto
    public function movePendingToCompleted($qr, $liters, $rate, $shift, $attendant_id)
    {

        // ret array
        $ret_array = array();

        $this->pdo->beginTransaction();

        try {
            // fetch from pending
            $stmt = $this->pdo->prepare('SELECT * FROM pending_transactions WHERE :qr IN(car_qr,trans_qr)');
            $stmt->execute([
                'qr'     => $qr
            ]);
            $row = $stmt->fetch();

            // no match
            if ($row) {

                // insert begin
                $stmt = $this->pdo->prepare('INSERT INTO completed_transactions (pump_id, cust_id, car_id, user_id, shift, fuel, amount, rate, liters, trans_qr, date, time_created, video) 
                                        VALUES (:pump_id, :cust_id, :car_id, :user_id, :shift, :fuel, :amount, :rate, :liters, :trans_qr, :mdate, :time_created, :video)');
                $stmt->execute([
                    'pump_id' => 1,
                    'cust_id' => $row['user_id'],
                    'car_id' => $row['car_id'],
                    'user_id' => $attendant_id,
                    'shift' => $shift,
                    'fuel' => $row['fuel_type'],
                    'amount' => $row['amount'],
                    'rate' => $rate,
                    'liters' => $liters,
                    'trans_qr' => $qr,
                    'time_created' => $row['time_created'],
                    'mdate' => date("Y-m-d H:i:s", $row['time_created']),
                    'video' => 'N',
                ]);

                $this->pdo->lastInsertId();

                // after insertion, delete from pending
                $stmt = $this->pdo->prepare('DELETE FROM pending_transactions WHERE time_created = :time_created AND user_id = :user_id');
                $stmt->execute([
                    'time_created' => $row['time_created'],
                    'user_id' => $row['user_id']
                ]);

                $this->pdo->commit();

                $ret_array['success'] = true;
                $ret_array['user_id'] = $row['user_id']; // user for fcm
            } else {
                $ret_array['success'] = false;
            }
        } catch (\Exception $e) {
            $this->pdo->rollback();
            $ret_array['success'] = false;
            throw $e;
        }

        return $ret_array;
    }

    // before video save, using id
    // use trans_qr to fetch id
    // public function getCompletedTransId($trans_qr, $trans_date)
    // {

    //     $ret_id = -1;

    //     $stmt = $this->pdo->prepare('SELECT trans_id FROM completed_transactions WHERE date(date) = :trans_date and cust_qr = :trans_qr');
    //     $stmt->execute([
    //         'trans_qr' => $trans_qr,
    //         'trans_date' => $trans_date
    //     ]);
    //     $row = $stmt->fetch();

    //     // match found
    //     if ($row) {
    //         $ret_id = $row['trans_id'];
    //     }

    //     return $ret_id;
    // }

    public function createPendingTransaction($id, $amount, $fuel_type, $car_id)
    {

        // ret array
        $ret_array = array();

        // find car_qr if exists        
        if ($car_id == "") {
            $car_id = NULL;
            $car_qr = NULL;
            $ret_array['hasCarQR'] = false;
        }
        //
        else {
            // prevent 2 transactions for a single car
            if ($this->pendingTransationsExistForCarId($car_id)) {
                $ret_array['success'] = false;
                $ret_array['message'] = "Pending Transation already exists for Car";
                return $ret_array;
            }
            // 
            else {
                $car_qr = $this->getCarQR($car_id, $id);
                if ($car_id == "") {
                    $ret_array['success'] = false;
                    $ret_array['message'] = "Car ID error";
                    return $ret_array;
                }else{
                    $ret_array['hasCarQR'] = true;
                }
            }
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
            $ret_array['message'] = "DB error";
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

        // check if exists in previous transactions
        if ($this->isDuplicateTransQR($trans_qr)) {
            // check if exists in car qr codes
            if ($this->isDuplicateCarQR($trans_qr)) {
                // recursion
                return $this->getNewTransactionQR();
            }
        }
        // return string
        else {
            return $trans_qr;
        }
    }

    private function isDuplicateTransQR($trans_qr)
    {
        //$stmt = $this->pdo->prepare('SELECT 1 FROM pending_transactions WHERE trans_qr =  :trans_qr');
        $stmt = $this->pdo->prepare('SELECT 1 FROM completed_transactions WHERE trans_qr =  :trans_qr');
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

    private function pendingTransationsExistForCarId($car_id)
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM pending_transactions WHERE car_id =  :car_id');
        $stmt->execute([
            'car_id'     => $car_id
        ]);
        $row = $stmt->fetch();
        // result found
        if ($row) {
            return true;
        }
        return false;
    }

    private function getCarQR($car_id, $user_id)
    {

        $ret = "";

        $stmt = $this->pdo->prepare('SELECT car_qr_code  FROM cars WHERE car_id = :car_id AND car_cust_id = :cust_id AND `status` = "active"');
        $stmt->execute([
            'car_id'     => $car_id,
            'cust_id'     => $user_id
        ]);
        $row = $stmt->fetch();
        // result found
        if ($row) {
            return $row['car_qr_code'];
        }
        return $ret;
    }
}
