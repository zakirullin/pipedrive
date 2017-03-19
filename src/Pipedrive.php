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

    public function process($entity, $method, $data = [], $params = [])
    {
        return $this->processResponse($this->sendRequest($entity, $method, $data, $params));
    }

    // TODO Checking for parents
    public function sendRequest($entity, $method, $data = [], $params = [])
    {
        $url = $this->buildApiUrl($entity, $params);

        return HHttp::doJson($method, $url, $data);
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
        $url = $this->getApiUrl();

        if ($parent = $entity->getParent()) {
            $url .= "{$parent->getType()}/{$parent->getId()}/{$entity->getType()}";
        } else {
            $url .= "/{$entity->getId()}";
        }

        $params['api_token'] = $this->apiToken;
        $url .= '?' . http_build_query($params);


//        $params['api_token'] = Yii::app()->params['pipedrive']['apiKey'];
//        $url = HUrl::addParams($url, $params);

        return $url;
    }
}

