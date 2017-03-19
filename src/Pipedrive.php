<?php

namespace Zakirullin\Pipedrive;

class Pipedrive
{
    /**
     * @var string $apiToken
     */
    protected $apiToken;

    /**
     * @var string $apiUrl
     */
    protected $apiUrl;

    /**
     * @var array $fields
     */
    protected $fields;

    public function __construct($apiToken, $fields = [], $apiUrl = 'https://api.pipedrive.com/v1/')
    {
        $this->apiToken = $apiToken;
        $this->fields = $fields;
        $this->apiUrl = $apiUrl;

        return $this;
    }

    // TODO Checking for parents
    public function sendRequest($entity, $method, $data = [], $params = [], $processResponse = true)
    {
        $url = $this->buildApiUrl($entity, $params);
        $response = HHttp::doJson($method, $url, $data);

        if ($processResponse) {
            return $this->processResponse($response);
        } else {
            return $response;
        }
    }

    // TODO add exception
    public function processResponse($response)
    {
        if ($response->success == 'true') {
            return $response->data;
        } else {
            return false;
        }
    }

    // TODO excetpion
    public function __call($entityType, $params)
    {
        return new Entity($this, $entityType, (isset($params[0])) ? $params[0] : null);
    }

    public function getApiToken()
    {
        return $this->apiToken;
    }

    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    protected function buildApiUrl($entity, $params = [])
    {
        $url = $this->apiUrl . $entity->getType();
        if ($entity->getId() !== null) {
            $url .= "/{$entity->getId()}";
        }

        $params['api_token'] = $this->apiToken;
        $url .= '?' . http_build_query($params);

//        if ($relatedEntityName !== null) {
//            $url .= '/' . $relatedEntityName;
//        }

//        $params['api_token'] = Yii::app()->params['pipedrive']['apiKey'];
//        $url = HUrl::addParams($url, $params);

        return $url;
    }
}

