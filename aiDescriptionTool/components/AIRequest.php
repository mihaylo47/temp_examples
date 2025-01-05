<?php

namespace YiiApp\modules\org\modules\aiDescriptionTool\components;

use Sp\Sp;
use Yii;

class AIRequest
{
    private ?int $id = null;
    private int $uid;

    private int $gid;
    private int $orgid;
    private string $inputData;
    private string $instructions;
    private string $imageUrl;
    private bool $askSizes;

    private array $request;
    private ?ChatGptResponse $response = null;
    private ChatGptService $aiService;

    private ?\DateTime $created = null;

    public function __construct()
    {
        $this->aiService = new ChatGptService();
    }

    public function setUid(int $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    public function setGid(int $gid): self
    {
        $this->gid = $gid;

        return $this;
    }

    public function setOrgid(int $orgid): self
    {
        $this->orgid = $orgid;

        return $this;
    }

    public function setInputData(string $inputData): self
    {
        $this->inputData = $inputData;

        return $this;
    }

    public function setInstructions(string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function setAskSizes(bool $askSizes): self
    {
        $this->askSizes = $askSizes;

        return $this;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function hasResult(): bool
    {
        return null !== $this->response;
    }

    public function setRequest(array $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function setResponse(?ChatGptResponse $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function setCreated(?\DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getGid(): int
    {
        return $this->gid;
    }

    public function getOrgid(): int
    {
        return $this->orgid;
    }

    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function register(): int
    {
        $answerFields = ['name', 'description', 'properties'];
        if ($this->askSizes) {
            $answerFields = [...$answerFields, 'weight', 'volume', 'dimensions'];
        }
        $this->request = [
            // 'model' => ChatGptService::MODEL_GPT35_TURBO, // 3.5 с картинками не работает!
            'model' => ChatGptService::MODEL_GPT4O,
            'messages' => array_values(array_filter([
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $this->inputData,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $this->imageUrl,
                            ],
                        ],
                    ],
                ],
                ['role' => 'user', 'content' => $this->instructions],
                $this->askSizes
                    ? ['role' => 'user', 'content' => 'Определи приблизительный вес в кг, объем в литрах, размеры товара в упаковке в см  .']
                    : [],
                ['role' => 'system', 'content' => 'Дай ответ в json формате {' . implode(', ', $answerFields) . '}'],
            ])),
            'response_format' => ['type' => 'json_object'],
        ];

        $this->created = new \DateTime();

        Yii::app()->db->commandBuilder->createSqlCommand('INSERT INTO goods_ai_helper_results SET created=:created, gid=:gid, orgid=:orgid, uid=:uid, request=:request, response=:response, response_time=:ms, price=:price')
            ->execute([
                ':created' => $this->created->format('Y-m-d H:i:s'),
                ':gid' => $this->gid,
                ':orgid' => $this->orgid,
                ':uid' => $this->uid,
                ':request' => json_encode($this->request),
                ':response' => '',
                ':ms' => 0,
                ':price' => 0,
            ]);
        $this->id = (int)Yii::app()->db->lastInsertID;

        return $this->id;
    }

    public function call(): bool
    {
        $msStart = microtime(true);
        $this->response = $this->aiService->getChatResponse($this->request);
        $msSpend = (int)((microtime(true) - $msStart) * 1000);

        Sp::logger()->warning('AIGoodsService', [
            'gid' => $this->gid,
            'uid' => $this->uid,
            'query' => $this->inputData . ' ' . $this->instructions,
            'imageUrl' => $this->imageUrl,
            'answer' => $this->response->getContent(),
            'promptTokens' => $this->response->getPromptTokens(),
            'completionTokens' => $this->response->getCompletionTokens(),
        ]);

        Yii::app()->db->commandBuilder->createSqlCommand('UPDATE goods_ai_helper_results SET updated=now(), response=:response, response_time=:ms WHERE id=:id')
            ->execute([
                ':id' => $this->id,
                ':response' => $this->response->toJson(),
                ':ms' => $msSpend,
            ]);

        return true;
    }

    public function setPayed(float $price): self
    {
        Yii::app()->db->commandBuilder->createSqlCommand('UPDATE goods_ai_helper_results SET price=:price WHERE id=:id')
            ->execute([
                ':id' => $this->id,
                ':price' => $price,
            ]);

        return $this;
    }

    public static function getRequestRowById(int $requestId): ?array
    {
        $row = Yii::app()->db->commandBuilder->createSqlCommand('SELECT * FROM goods_ai_helper_results WHERE id=:id')
            ->queryRow(true, [
                ':id' => $requestId,
            ]);

        return $row ?: null;
    }

    public static function getRequestList(int $gid): array
    {
        return Yii::app()->db->commandBuilder->createSqlCommand('SELECT * FROM goods_ai_helper_results WHERE gid=:id')
            ->queryAll(true, [
                ':id' => $gid,
            ]);
    }

    public function getResponseContent(): array
    {
        return $this->hasResult() ? $this->parseContent($this->response->getContent()) : [];
    }

    public function getResponse(): ?ChatGptResponse
    {
        return $this->response;
    }

    private function parseContent(string $string): array
    {
        $arr = ['name' => '', 'weight' => '', 'volume' => '', 'dimensions' => '', 'description' => '', 'properties' => '', ...json_decode($string, true)];
        // надо убедится что внутри не получилось вложенных массивов
        $res = array_map(fn ($item) => $this->arrayToStringRecursive($item), $arr);
        $res['weightRaw'] = $res['weight'];
        $res['volumeRaw'] = $res['volume'];
        $res['dimensionsRaw'] = $res['dimensions'];

        $res['weight'] = $this->findValueInStr($res['weight']);
        $res['volume'] = $this->findValueInStr($res['volume']);

        $delimiter = $this->findDimensionDelimiter($res['dimensions']);
        if ($delimiter) {
            $sizes = explode($delimiter, $res['dimensions']);
            $res['width'] = $this->findValueInStr($sizes[0]);
            $res['height'] = $this->findValueInStr($sizes[1]);
            $res['depth'] = $this->findValueInStr($sizes[2]);
        } else {
            $res['width'] = 0;
            $res['height'] = 0;
            $res['depth'] = 0;
        }

        return $res;
    }

    private function findValueInStr(string $str): float
    {
        $onlyNumbersVal = preg_replace('/[^\d.]/u', ' ', str_replace(',', '.', $str));
        $valStripped = trim(preg_replace('/\s+/u', ' ', $onlyNumbersVal), ' .');
        $numbersList = explode(' ', $valStripped);

        return (float)$numbersList[0];
    }

    private function findDimensionDelimiter(string $str): ?string
    {
        // тут может быть миллион вариаций, надо хотя бы основные разбирать
        foreach (['x', '/', '*', "\n"] as $delimiter) {
            if (str_contains($str, $delimiter) && substr_count($str, $delimiter) === 2) {
                return $delimiter;
            }
        }

        return null;
    }

    private function arrayToStringRecursive(mixed $value): string
    {
        return is_array($value)
            ? implode(" \n", array_map(fn ($item, $key) => (is_numeric($key) ? '' : $key . ': ') . $this->arrayToStringRecursive($item), $value, array_keys($value)))
            : $value . '';
    }

    public static function newRequest(string $inputData, string $instructions, string $imageUrl, int $uid, int $gid, int $orgid, bool $askSizes): self
    {
        $aiRequest = new self();
        $aiRequest
            ->setGid($gid)
            ->setOrgid($orgid)
            ->setUid($uid)
            ->setInputData($inputData)
            ->setInstructions($instructions)
            ->setImageUrl($imageUrl)
            ->setAskSizes($askSizes);

        return $aiRequest;
    }

    public static function loadById(int $requestId): ?self
    {
        $row = self::getRequestRowById($requestId);
        if (!$row) {
            return null;
        }
        $aiRequest = new self();
        $aiRequest
            ->setId($row['id'])
            ->setGid($row['gid'])
            ->setOrgid($row['orgid'])
            ->setCreated(new \DateTime($row['created']))
            ->setUid($row['uid'])
            ->setInputData('')
            ->setInstructions('')
            ->setImageUrl('')
            ->setRequest(json_decode($row['request'], true))
            ->setResponse($row['response'] !== '' ? ChatGptResponse::fromJson($row['response']) : null);

        return $aiRequest;
    }
}
