<?php

declare(strict_types=1);

namespace App\Infraestrutura\APIs\BuscaCorretor\Controladores\Login;

use Exception;
use DI\Container;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use App\Infraestrutura\APIs\BuscaCorretor\Controladores\Middlewares\Controller;

final class LoginController extends Controller
{

    public function __construct(
	    private Container $container
    ){}

    public function index()
    {
		$jwt = $_POST['credential'] ?? null;
		
		if (!$jwt) {
			http_response_code(400);
			echo json_encode(['error' => 'Token não encontrado']);
			exit;
		}
		
		// Separa o JWT em partes
		$parts = explode('.', $jwt);
		
		if (count($parts) !== 3) {
			http_response_code(400);
			echo json_encode(['error' => 'Token inválido']);
			exit;
		}
		
		// Decodifica o payload
		$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
		
		// 1. Baixa as chaves públicas do Google
		$keys_json = file_get_contents('https://www.googleapis.com/oauth2/v3/certs');
		$keys = JWK::parseKeySet(json_decode($keys_json, true));

		try {
			// 2. Decodifica e valida a assinatura
			$decoded = JWT::decode($jwt, $keys);

			dd($decoded);
			$payload = (array) $decoded;

			// 3. Salva os dados na sessão
			$_SESSION['user'] = [
				'email' => $payload['email'],
				'name' => $payload['name'],
				'sub' => $payload['sub']
			];

			// 4. Redireciona
			header("Location: /painel.php");
			exit;

		} catch (Exception $e) {
			http_response_code(401);
			echo json_encode(['error' => 'Token inválido', 'message' => $e->getMessage()]);
		}
    }
}

