<?php

namespace YiiApp\modules\org\modules\aiDescriptionTool\components;

use OpenAI;
use OpenAI\Client;
use Yii;

class ChatGptService
{
    private ?Client $client = null;

    public const MODEL_GPT35_TURBO = 'gpt-3.5-turbo';
    public const MODEL_GPT4_TURBO = 'gpt-4-turbo';
    public const MODEL_GPT4O = 'gpt-4o';
    public const MODEL_GPT4 = 'gpt-4';
    public const MODEL_LIST = [
        self::MODEL_GPT35_TURBO,
        self::MODEL_GPT4_TURBO,
        self::MODEL_GPT4O,
        self::MODEL_GPT4,
    ];

    public function __construct()
    {
        $this->client = $this->getClient(Yii::app()->params['ai']['chatGPT']['apiKey']);
    }

    private function getClient(string $apiKey): Client
    {
        return OpenAI::client($apiKey);
    }

    protected function requestAssemble(array $options): array
    {
        return [
            'model' => self::MODEL_GPT35_TURBO,
            'messages' => [
                ['role' => 'user', 'content' => ''],
            ],
            ...$options,
        ];
    }

    public function getChatResponse(array $options): ChatGptResponse
    {
        return ChatGptResponse::fromCreateResponse($this->client->chat()->create($this->requestAssemble($options)));
    }
}
