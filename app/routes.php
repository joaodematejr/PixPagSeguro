<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    //Autenticação - Criar uma autenticação;

    $app->post('/autenticacao', function (Request $request, Response $response) {
        /* $curl_log = fopen("./logs/autenticacao.json", 'a+'); */
        $data = [
            'grant_type' => "client_credentials",
        ];
        $header = [
            'Content-Type: application/json; charset=utf-8',
            "cache-control: no-cache",
            'Accept: application/json, text/plain, */*',
        ];
        $ch = curl_init();
        /* $ch = curl_init('https://secure.sandbox.api.pagseguro.com/pix/oauth2'); */
        $ch = curl_init('https://secure.api.pagseguro.com/pix/oauth2');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        /* curl_setopt($ch, CURLOPT_USERPWD, "user:password"); */
        /* curl_setopt($ch, CURLOPT_SSLCERT, "C:\\certs\\pagseguro\\cert.pem"); */
        /* curl_setopt($ch, CURLOPT_SSLKEY, "C:\\certs\\pagseguro\\cert.key"); */

        /* curl_setopt($ch, CURLOPT_VERBOSE, TRUE); */
        //curl_setopt($ch, CURLOPT_STDERR, $logCurl);
        $result = curl_exec($ch);
        //$info = curl_getinfo($ch);
        curl_close($ch);
        $resultArr = [
            "result" => json_decode($result),
        ];
        $response->getBody()->write(json_encode($resultArr));
        $log = [
            "header" => $header,
            "request" => $data,
            "response" => $result,
        ];
        /* fwrite($curl_log, json_encode($log)); */
        //rewind($curl_log);
        /* fclose($curl_log); */
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    function randomTxId($length = 31)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $txId = substr(str_shuffle($chars), 0, $length);
        return $txId;
    }
    //Criar uma cobrança - Criar uma cobrança;
    $app->put('/criarCobranca', function (Request $request, Response $response) {
        $curl_log = fopen("./logs/criarCobranca.json", 'a+');
        $txId = randomTxId(31);
        $resToken = "token_api_pagseguro";
        $header = [
            'Content-Type: application/json; charset=utf-8',
            "cache-control: no-cache",
            'Accept: application/json, text/plain, */*',
            "Authorization: Bearer {$resToken}"
        ];
        $data = [
            "calendario" => [
                "expiracao" => "9600"
            ],
            "devedor" => [
                "cpf" => 'sua_chave_pix',
                "nome" => "nome_received",
            ],
            "valor" => [
                "original" => "1.00",
            ],
            "chave" => 'chave_api_pagseguro',
            "solicitacaoPagador" => "Pagamento",
        ];
        $ch = curl_init();
        /* $ch = curl_init("https://secure.sandbox.api.pagseguro.com/instant-payments/cob/{$txId}"); */
        $ch = curl_init("https://secure.api.pagseguro.com/instant-payments/cob/{$txId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
       /* curl_setopt($ch, CURLOPT_SSLCERT, "C:\\certs\\pagseguro\\cert.pem"); */
        /* curl_setopt($ch, CURLOPT_SSLKEY, "C:\\certs\\pagseguro\\cert.key"); */

        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        //curl_setopt($ch, CURLOPT_STDERR, $curl_log);
        $result = curl_exec($ch);
        //$info = curl_getinfo($ch);
        curl_close($ch);
        $resultArr = [
            "result" => json_decode($result),
        ];
        $response->getBody()->write(json_encode($resultArr));
        $log = [
            "header" => $header,
            "request" => $data,
            "response" => $result,
        ];
        fwrite($curl_log, json_encode($log));
        //rewind($curl_log);
        fclose($curl_log);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    //Consultar uma cobrança - Recuperar uma cobrança
    $app->post('/consultarCobranca', function (Request $request, Response $response) {
        $curl_log = fopen("./logs/consultarCobranca.json", 'a+');
        $txid = "txId";
        $resToken = "token_api_pagseguro";
        $header = [
            'Content-Type: application/json; charset=utf-8',
            "cache-control: no-cache",
            'Accept: application/json, text/plain, */*',
            "Authorization: Bearer {$resToken}"
        ];
        $ch = curl_init();
        /* $ch = curl_init("https://secure.sandbox.api.pagseguro.com/instant-payments/cob/{$txid}?revisao=0"); */
        $ch = curl_init("https://secure.api.pagseguro.com/instant-payments/cob/{$txid}?revisao=0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
       /* curl_setopt($ch, CURLOPT_SSLCERT, "C:\\certs\\pagseguro\\cert.pem"); */
        /* curl_setopt($ch, CURLOPT_SSLKEY, "C:\\certs\\pagseguro\\cert.key"); */
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        //curl_setopt($ch, CURLOPT_STDERR, $curl_log);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $resultArr = [
            "result" => json_decode($result),
        ];
        $log = [
            "header" => $header,
            "response" => $result,
        ];
        fwrite($curl_log, json_encode($log));
        //rewind($curl_log);
        fclose($curl_log);
        $response->getBody()->write(json_encode($resultArr));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    //Simular o pagamento de uma cobrança em sandbox
    $app->post('/simularPagamento', function (Request $request, Response $response) {
        $curl_log = fopen("./logs/pagar.json", 'a+');
        $txid = "id_transacion";
        $data = [
            'status' => "PAID",
            'tx_id' => $txid,
        ];
        $header = [
            "Content-Type: application/json; charset=utf-8",
            "cache-control: no-cache",
            "Authorization: Bearer token_api_pagseguro"
        ];
        $ch = curl_init();
        $ch = curl_init("https://sandbox.api.pagseguro.com/pix/pay/{$txid}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
       /* curl_setopt($ch, CURLOPT_SSLCERT, "C:\\certs\\pagseguro\\cert.pem"); */
        /* curl_setopt($ch, CURLOPT_SSLKEY, "C:\\certs\\pagseguro\\cert.key"); */
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        //curl_setopt($ch, CURLOPT_STDERR, $curl_log);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $resultArr = [
            "result" => json_decode($result),
        ];
        $log = [
            "header" => $header,
            "request" => $data,
            "response" => $result,
        ];
        fwrite($curl_log, json_encode($log));
        //rewind($curl_log);
        fclose($curl_log);
        $response->getBody()->write(json_encode($resultArr));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    //Consultar um PIX
    $app->post('/consultarPix', function (Request $request, Response $response) {
        /* $curl_log = fopen("./logs/ConsultarPIX.json", 'a+'); */
        $endToEndId = "id_transacion";
        $resToken = "token_api_pagseguro";
        $header = [
            'Content-Type: application/json; charset=utf-8',
            "cache-control: no-cache",
            'Accept: application/json, text/plain, */*',
            "Authorization: Bearer {$resToken}"
        ];
        $ch = curl_init();
        /* $ch = curl_init("https://secure.sandbox.api.pagseguro.com/instant-payments/pix/{$endToEndId}"); */
        $ch = curl_init("https://secure.api.pagseguro.com/instant-payments/pix/{$endToEndId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        /* curl_setopt($ch, CURLOPT_SSLCERT, "C:\\certs\\pagseguro\\cert.pem"); */
        /* curl_setopt($ch, CURLOPT_SSLKEY, "C:\\certs\\pagseguro\\cert.key"); */
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        //curl_setopt($ch, CURLOPT_STDERR, $curl_log);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $resultArr = [
            "result" => json_decode($result),
        ];
        $log = [
            "header" => $header,
            "response" => $result,
        ];
        /* fwrite($curl_log, json_encode($log)); */
        //rewind($curl_log);
        /* fclose($curl_log); */
        $response->getBody()->write(json_encode($resultArr));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    //Solicitar uma devolução
    $app->put('/solicitarDevolução', function (Request $request, Response $response) {
        /* $curl_log = fopen("./logs/Realizar uma devolução com erro.json", 'a+'); */
        $resToken = "token_api_pagseguro";
        $e2eid = "id_transacion";
        $id = "chave_pix";
        $header = [
            'Content-Type: application/json; charset=utf-8',
            "cache-control: no-cache",
            'Accept: application/json, text/plain, */*',
            "Authorization: Bearer {$resToken}"
        ];
        $ch = curl_init();
        $data = [
            'valor' => "1.00",
        ];
        /* $ch = curl_init("https://secure.sandbox.api.pagseguro.com/instant-payments/pix/{e2eid}/devolucao/{id}"); */
        $ch = curl_init("https://secure.api.pagseguro.com/instant-payments/pix/{$e2eid}/devolucao/{$id}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        /* curl_setopt($ch, CURLOPT_SSLCERT, "C:\\certs\\pagseguro\\cert.pem"); */
        /* curl_setopt($ch, CURLOPT_SSLKEY, "C:\\certs\\pagseguro\\cert.key"); */
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        //curl_setopt($ch, CURLOPT_STDERR, $curl_log);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $resultArr = [
            "result" => json_decode($result),
        ];
        $log = [
            "header" => $header,
            "request" => $data,
            "response" => $result,
        ];
        /* fwrite($curl_log, json_encode($log)); */
        //rewind($curl_log);
       /*  fclose($curl_log); */
        $response->getBody()->write(json_encode($resultArr));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    //Consultar uma devolução - Consultar recebimento
    $app->get('/consultarDevolucao', function (Request $request, Response $response) {
        $curl_log = fopen("./logs/Consultar recebimento.json", 'a+');
        $endToEndId = "id_transacion";
        $txid = "id";
        $resToken = "token_api_pagseguro";
        $header = [
            'Content-Type: application/json; charset=utf-8',
            "cache-control: no-cache",
            'Accept: application/json, text/plain, */*',
            "Authorization: Bearer {$resToken}"
        ];
        $ch = curl_init();
        $ch = curl_init("https://secure.sandbox.api.pagseguro.com/instant-payments/pix/{$endToEndId}/devolucao/{$txid}");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        /* curl_setopt($ch, CURLOPT_SSLCERT, "C:\\certs\\pagseguro\\cert.pem"); */
        /* curl_setopt($ch, CURLOPT_SSLKEY, "C:\\certs\\pagseguro\\cert.key"); */
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        //curl_setopt($ch, CURLOPT_STDERR, $curl_log);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $resultArr = [
            "result" => json_decode($result),
        ];
        $log = [
            "header" => $header,
            "response" => $result,
        ];
        fwrite($curl_log, json_encode($log));
        //rewind($curl_log);
        fclose($curl_log);
        $response->getBody()->write(json_encode($resultArr));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    function crcChecksum($str)
    {
        function charCodeAt($str, $i)
        {
            return ord(substr($str, $i, 1));
        }

        $crc = 0xFFFF;
        $strlen = strlen($str);
        for ($c = 0; $c < $strlen; $c++) {
            $crc ^= charCodeAt($str, $c) << 8;
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
        }
        $hex = $crc & 0xFFFF;
        $hex = dechex($hex);
        $hex = strtoupper($hex);
        $hex = str_pad($hex, 4, '0', STR_PAD_LEFT);

        return $hex;
    }

    //Gerar QR Code
    //https://pix.nascent.com.br/tools/pix-qr-decoder/ VALIDAR QRCODE
    $app->get('/qrcode', function (Request $request, Response $response) {
        $location = "id_transacion";
        $pix = "000201070503***26830014br.gov.bcb.pix2561api.pagseguro.com/pix/v2/{$location}5204899953039865802BR5921BC 26304";
        $isCrc = crcChecksum($pix);
        $pix .= $isCrc;

        $options = new QROptions([
            'version' => 9, //versao do QRCode
            'eccLevel' => QRCode::ECC_L, //Error Correction Feature Level L
            'outputType' => QRCode::OUTPUT_IMAGE_PNG, //setando o output como PNG
            'imageBase64' => true //evitando que seja gerado a imagem em base64
        ]);

        /* file_put_contents('image.png',(new QRCode($options))->render($data)); */ //salvando a imagem como png
        $response->getBody()->write('<img src="' . (new QRCode($options))->render($pix) . '" />');
        /* $response->getBody()->write($pix); */
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8')->withStatus(200);
    });
};
