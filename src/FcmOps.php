<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;


final class FcmOps
{
    public function __construct(PDO $pdo, ContainerInterface $c)
    {
        $this->pdo = $pdo;
        $this->fcm_key = $c->get('settings')['fcm_key'];
    }


    // TODO add curl fail responses
    // log errors
    public function sendFcmMessage($id, $message)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        
        $fields = array(
            'registration_ids' => array(
                $id
            ),
            'data' => array(
                "message" => $message
            )
        );
        $fields = json_encode($fields);

        $headers = array(
            'Authorization: key=' . $this->fcm_key,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        $result = curl_exec($ch);
        echo $result;
        curl_close($ch);
    }
}
