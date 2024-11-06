<?php

namespace App\Services;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Http;
use Jose\Bundle\JoseFramework\DependencyInjection\Source\KeyManagement\JWKSource\JWK;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Symfony\Component\Clock\Clock;

class TokenService {
    public function getIAMToken() :string {
        $jwtToken = config('services.auth.jwt_token');
        $response = Http::post('https://iam.api.cloud.yandex.net/iam/v1/tokens', [
            RequestOptions::HEADERS => [
                'Content-type' => 'application/json',
            ],
            RequestOptions::JSON => ['jwt' => $jwtToken]
        ]);
        dd($response->status(), $response->body());
    }

    public function createJwtToken(): string {
        $keyData = json_decode(file_get_contents(storage_path("authorized_key.json")), true);
        $privateKeyPem = $keyData['private_key'];
        $keyId = $keyData['id'];
        $serviceAccountId = $keyData['service_account_id'];

        // Необходимо удалить заголовок/метаданные из закрытого ключа
        if (strpos($privateKeyPem, "PLEASE DO NOT REMOVE THIS LINE!") === 0) {
            $privateKeyPem = substr($privateKeyPem, strpos($privateKeyPem, "\n") + 1);
        }

        $jwk = JWKFactory::createFromKey(
            $privateKeyPem,
            null,
            [
                'alg' => 'PS256',
                'use' => 'sig',
                'kid' => $keyId,
            ]
        );

        $algorithmManager = new AlgorithmManager([new PS256()]);
        $jwsBuilder = new JWSBuilder($algorithmManager);

        $payload = json_encode([
            'iss' => $serviceAccountId,
            'aud' => "https://iam.api.cloud.yandex.net/iam/v1/tokens",
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600 * 24 * 31 * 12 * 10,
        ]);

        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, ['alg' => 'PS256', 'typ'=>'JWT', 'kid' => $keyId])
            ->build();


        $serializer = new CompactSerializer();
        $token = $serializer->serialize($jws, 0);
        return $token;
    }

    public function storeJwtToken(string $token) {
        file_put_contents(storage_path("jwt_token.txt"), $token);
    }
}
