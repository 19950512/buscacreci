<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs;

use DI\Container;

final class Router
{

    public $controller;
    public $action;

    public function __construct(
	    readonly private string $request_uri,
        private Container $container,
	    readonly private string $apiName
    ){

        $uri = explode('/', $this->request_uri);
        $controllerName = explode('?', ($uri[1] ?? 'Index'))[0];
        $controllerName = ucfirst(empty($controllerName) ? 'Index' : $controllerName);

        $action = ($uri[2] ?? 'Index');
        $this->action = ucfirst(empty($action) ? 'Index' : $action);

        $controllerNameSpace = "App\Infraestrutura\APIs\\{$this->apiName}\Controladores\Erros\Erro404Controller";

        $pathController = __DIR__."/{$this->apiName}/Controladores/$controllerName/{$controllerName}Controller.php";

		if(is_file($pathController)){

			$nameSpace =  "App\Infraestrutura\APIs\\{$this->apiName}\Controladores\\$controllerName\\{$controllerName}Controller";

			if(class_exists($nameSpace)){
				$this->controller = new $nameSpace(
					container: $this->container
				);
			}

		}else{

			$this->controller = new $controllerNameSpace(
				container: $this->container
			);
		}


        if(!method_exists($this->controller, $this->action)){

            $controllerNameSpace = "App\Infraestrutura\APIs\\{$this->apiName}\Controladores\Erros\Erro404Controller";
            $this->controller = new $controllerNameSpace(
                container: $this->container
            );
            $this->action = 'Index';
        }

        $this->controller->{$this->action}();
    }
}