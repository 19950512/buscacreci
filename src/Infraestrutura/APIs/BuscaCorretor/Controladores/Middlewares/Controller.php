<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares;

use DI\Container;

abstract class Controller
{

    public $method;

    public function __construct(
        private Container $container
    ){

        $this->method = $_SERVER['REQUEST_METHOD'] ?? '';

        if(is_array($_POST) and count($_POST) == 0){
            $json = file_get_contents('php://input');
            $_POST = json_decode(json_decode(json_encode($json), true), true);
        }

	    header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET');
		header('Access-Control-Allow-Headers: Content-Type');
    }

    public function response(array $data)
    {
		header('Content-Type: application/json');
	    header('Server: github.com/19950512');
	    header('X-Powered-By: 19950512');

        if(isset($data['statusCode']) and is_numeric($data['statusCode'])){
            header("HTTP/1.0 {$data['statusCode']} {$data['statusMessage']}");
            unset($data['statusCode']);
			if(isset($data['statusMessage'])){
				unset($data['statusMessage']);
			}
        }

        echo json_encode($data['data'] ?? $data);
    }
}
