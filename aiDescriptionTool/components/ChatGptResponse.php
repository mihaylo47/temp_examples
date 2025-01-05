<?php

namespace YiiApp\modules\org\modules\aiDescriptionTool\components;

use OpenAI\Responses\Chat\CreateResponse;

readonly class ChatGptResponse
{
    private string $content;
    private int $promptTokens;
    private int $completionTokens;
    private string $model;

    public function __construct(private string $rawJson)
    {
        $array = json_decode($rawJson);
        if (
            !is_object($array)
            || !isset($array->model)
            || !isset($array->choices[0]->message->content)
            || !isset($array->usage->promptTokens)
            || !isset($array->usage->completionTokens)
        ) {
            throw new \LogicException('Wrong json format for ChatGptResponse');
        }

        $this->content = $array->choices[0]->message->content;
        $this->promptTokens = $array->usage->promptTokens;
        $this->completionTokens = $array->usage->completionTokens;
        $this->model = $array->model;
    }

    public static function fromCreateResponse(CreateResponse $response): self
    {
        return new self(json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public static function fromJson(string $json): self
    {
        return new self($json);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function toJson(int $jsonEncodeFlags = 0): string
    {
        return $this->rawJson;
    }
}
