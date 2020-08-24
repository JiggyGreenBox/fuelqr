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

    // from add car
    public function getIdByTransQR($trans_qr)
    {
        // $stmt = $this->pdo->prepare('SELECT cust_id FROM pending_transactions WHERE trans_qr = :trans_qr');
        $stmt = $this->pdo->prepare('SELECT cust_id FROM completed_transactions WHERE trans_qr = :trans_qr');
        $stmt->execute([
            'trans_qr'     => $trans_qr,
        ]);
        $row = $stmt->fetch();

        if ($row) {
            return $row['cust_id'];
        } else {
            return -1;
        }
    }

    // from add car
    // could move to qr ops
    public function getQRCodeStatus($car_qr)
    {

        $ret_array = array();

        $stmt = $this->pdo->prepare('SELECT * FROM codes WHERE qr_code = :car_qr');
        $stmt->execute([
            'car_qr'     => $car_qr,
        ]);
        $row = $stmt->fetch();

        if (!$row) {
            // no qr found
            // qr is invalid
            $ret_array['exists'] = false;
            return $ret_array;
        } else {

            // qr found
            $ret_array['exists'] = true;

            if ($row['status'] == "active") {
                $ret_array['assigned'] = true;
            } else {
                $ret_array['assigned'] = false;
            }

            return $ret_array;
        }
    }

    // from add car
    public function insertNewCar($id, $car_qr, $car_plate_no, $car_fuel_type)
    {
        $ret = false;

        $this->pdo->beginTransaction();

        $curtime = date("Y-m-d h:i:s", strtotime("now"));

        try {

            // insert into cars
            $stmt = $this->pdo->prepare('INSERT INTO cars(car_no_plate, car_fuel_type, car_cust_id, car_qr_code, status, date_created, last_updated) 
                                            VALUES (:car_no_plate, :car_fuel_type, :car_cust_id, :car_qr_code, :status, :date_created, :last_updated)');

            $stmt->execute([
                'car_no_plate'  => $car_plate_no,
                'car_fuel_type' => $car_fuel_type,
                'car_cust_id'   => $id,
                'car_qr_code'   => $car_qr,
                'status'        => "active",
                'date_created'  => $curtime,
                'last_updated'  => $curtime
            ]);

            // update codes
            $stmt = $this->pdo->prepare('UPDATE codes SET status="active" WHERE qr_code = :qr_code');
            $stmt->execute([
                'qr_code'     => $car_qr,
            ]);


            $this->pdo->commit();
            $ret = true;
        } catch (\Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }

        return $ret;
    }

    // from car and pending controller
    public function getCarsByID($id)
    {

        $ret_array = array();

        $stmt = $this->pdo->prepare('SELECT car_id, car_no_plate, car_fuel_type FROM cars WHERE car_cust_id = :id and status = "active"');
        // $stmt = $this->pdo->prepare('SELECT car_id, car_no_plate, car_fuel_type FROM cars WHERE car_cust_id = :id and status = "active" LIMIT 1');
        $stmt->execute([
            'id'     => $id
        ]);

        $rows = $stmt->fetchAll();

        if ($rows) {

            foreach ($rows as $row) {
                array_push($ret_array, $row);
            }
        }

        return $ret_array;
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


    public function updateFirebaseTokenIfNew($id, $fb_token)
    {
        $stmt = $this->pdo->prepare('SELECT firebase_token FROM users WHERE user_id= :user_id');
        $stmt->execute([
            'user_id'     => $id
        ]);
        $row = $stmt->fetch();

        // check if result
        if ($row) {
            // check if null
            if (!is_null($row['firebase_token'])) {
                $stored_token = $row['firebase_token'];

                if ($stored_token != $fb_token) {
                    $this->updateFirebaseToken($id, $fb_token);
                }
            }
            // no token, update it
            else {
                $this->updateFirebaseToken($id, $fb_token);
            }
        }
    }

    private function updateFirebaseToken($id, $fb_token)
    {
        $stmt = $this->pdo->prepare('UPDATE users SET firebase_token = :fb_token WHERE user_id = :user_id');
        $stmt->execute([
            'user_id'     => $id,
            'fb_token'     => $fb_token
        ]);
    }

    public function getFcmID($id)
    {
        $stmt = $this->pdo->prepare('SELECT firebase_token FROM users WHERE user_id = :user_id');
        $stmt->execute([
            'user_id'     => $id
        ]);
        $row = $stmt->fetch();

        if ($row) {
            return $row['firebase_token'];
        } else {
            return -1;
        }
    }
}
