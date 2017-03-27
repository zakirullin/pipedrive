<?php

namespace Zakirullin\Pipedrive;

/**
 * @property Entity organizations
 * @property Entity activities
 * @property Entity deals
 * @property Entity persons
 * @property Entity notes
 */
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
     * @var array $shortFields
     */
    protected $shortFields;

    public function __construct($apiToken, $shortFields = [], $apiUrl = 'https://api.pipedrive.com/v1')
    {
        $this->apiToken = $apiToken;
        $this->shortFields = $shortFields;
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
        $url = $this->buildApiUrl($entity, $params, $method);

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
    public function __get($entityType)
    {
        return new Entity($this, $entityType);
    }

    public function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     * @param $apiToken
     * @return $this
     */
    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param string $apiUrl
     * @return $this
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    public function getShortFields()
    {
        return $this->shortFields;
    }

    /**
     * @param Entity $entity
     * @param string $field
     * @return null|string
     */
    public function getShortField($entity, $field)
    {
        return isset($this->shortFields[$entity->getType()][$field]) ? $this->shortFields[$entity->getType()][$field] : null;
    }

    /**
     * @param array $shortFields
     * @return $this
     */
    public function setShortFields($shortFields)
    {
        $this->shortFields = $shortFields;
    }

    /**
     * @param Entity $entity
     * @param array $params
     * @param null|string $method
     * @return string
     */
    protected function buildApiUrl($entity, $params = [], $method = null)
    {
        $url = $this->getApiUrl();

        if (($parent = $entity->getParent()) && $method == 'get') {
            $url .= "/{$parent->getType()}/{$parent->getId()}/{$entity->getType()}";
        } else {
            $url .= "/{$entity->getType()}";
            if ($entity->getId() !== null) {
                $url .= "/{$entity->getId()}";
            }
        }

        $params['api_token'] = $this->apiToken;
        $url .= '?' . http_build_query($params);

        return $url;
    }
}

