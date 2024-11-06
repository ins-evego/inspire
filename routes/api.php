<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('inspire', function (\App\Services\TokenService $tokenService) {
    $body = [
        "modelUri" => "gpt://".config('services.yandex.catalog-id')."/yandexgpt-lite",
        "completionOptions" => [
            "stream" => false,
            "temperature" => 0.6,
            "maxTokens" => "2000",
        ],
        "messages" => [
            [
                "role" => "user",
                "text" => "Чем заняться сегодня? Вдохнови меня одним предложением",
            ],
        ],
    ];

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept'=> 'application/json',
        'x-folder-id' => config('services.yandex.catalog-id'),
        //'Authorization' => 'Bearer '.config('services.yandex.iam-token')
        'Authorization' => 'Api-Key '.config('services.yandex.api_key')
    ])->send('POST', 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion', [
        \GuzzleHttp\RequestOptions::BODY => json_encode($body)
    ]);
    dd($response->body());
});
