<?php

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Psr7\Factory\ResponseFactory;

use App\TestStatic\Up;

return function (App $app) {


    // TODO
    $app->post('/otp_request', \App\OtpRequest::class);

    $app->post('/otp_verify', \App\OtpVerifyController::class);

    $app->post('/ref_verify', \App\RefVerifyController::class);


    // pump auth to be created and added here
    $app->get('/pump_scan', \App\PumpScanController::class);

    // add auth here
    $app->get('/cars_pending', \App\CarAndPendingController::class);
    

    // group has AuthCheck attached to each request
    $app->group('', function (Group $group) {

        $group->post('/new_transaction', \App\NewTransactionController::class);

        $group->get('/ad', function (Request $request, Response $response) {

            $id = $request->getAttribute('user_id');

            $response->getBody()->write($id);
            return $response;
        });
    })->add(\App\AuthCheck::class);

};
