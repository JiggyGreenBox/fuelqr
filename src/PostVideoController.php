<?php

namespace App;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use PDO;

use Psr\Http\Message\UploadedFileInterface;

final class PostVideoController
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

        // get uploaded files array
        $uploadedFiles = $request->getUploadedFiles();
        // get post data
        $postData = (array)$request->getParsedBody();
        // check post vars
        if (!array_key_exists("trans_date", $postData)) {
            return $this->errorReturn($request, $response, "Access Denied");
        }

        // assign date
        $trans_date  = $request->getParsedBody()['trans_date'];

        // upload dir move to settings
        $directory = dirname(__DIR__) . '/uploads';

        $success_file = false;

        if (empty($uploadedFiles['video'])) {
            return $this->errorReturn($request, $response, "Access Denied");
        } else {
            $file = $uploadedFiles['video'];

            if ($file->getError() === UPLOAD_ERR_OK) {
                $success_file = $this->moveUploadedFile($directory, $file, $trans_date);
            }
        }


        // echo exec('whoami');

        // HTTP response
        $ret_data = array("post_video" => $success_file);
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

    private function moveUploadedFile(string $directory, UploadedFileInterface $uploadedFile, string $trans_date)
    {
        $extension  = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $trans_qr   = pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);
        $filename   = pathinfo($uploadedFile->getClientFilename(), PATHINFO_BASENAME);

        // allow only mp4
        if ($extension == "mp4") {
            // make sure exists in DB
            if ($this->transactionOps->existsInCompletedTransactions($trans_qr, $trans_date)) {
                $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
                return true;
            }
        }

        return false;
    }
}
