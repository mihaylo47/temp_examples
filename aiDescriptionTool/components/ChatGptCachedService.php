<?php

namespace YiiApp\modules\org\modules\aiDescriptionTool\components;

use ChatGptRequest;

class ChatGptCachedService extends ChatGptService
{
    public function getChatResponse(array $options): ChatGptResponse
    {
        $request = $this->requestAssemble($options);
        $cachedRequest = ChatGptRequest::model()->findByRequest($request);

        if ($cachedRequest) {
            return ChatGptResponse::fromJson($cachedRequest->response);
        }

        $response = parent::getChatResponse($options);
        ChatGptRequest::model()->add($request, $response);

        return $response;
    }
}
