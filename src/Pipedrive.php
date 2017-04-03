<?php

namespace Zakirullin\Pipedrive;

use Zakirullin\Pipedrive\Interfaces\HttpClient as HttpClientInterface;
use Zakirullin\Pipedrive\Http\HttpClient;

/**
 * @property PipedriveQuery $organizations
 * @property PipedriveQuery $activities
 * @property PipedriveQuery $deals
 * @property PipedriveQuery $persons
 * @property PipedriveQuery $notes
 * @property PipedriveQuery $products
 */
class Pipedrive
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var array
     */
    protected $fields;

    /**
     * All other id fields can be builded using {getSingularType()}_id
     *
     * @var array
     */
    protected $idFields = [
        'organizations' => 'org_id',
        'activities' => 'activity_id',
    ];

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @param string $token
     * @param array $fields
     * @param HttpClientInterface $httpClient
     * @param string $host
     * @param string $version
     */
    public function __construct($token, $fields = [], $httpClient = null, $host = 'https://api.pipedrive.com', $version = 'v1')
    {
        $this->token = $token;
        $this->fields = $fields;
        $this->httpClient = $httpClient ? $httpClient : new HttpClient();
        $this->host = $host;
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $entityQuery
     * @param string $field
     * @return null|string
     */
    public function getFieldHash($entityType, $field)
    {
        return isset($this->fields[$entityType][$field]) ? $this->fields[$entityType][$field] : $field;
    }

    /**
     * @param string $entityType
     * @param string $field
     * @return string
     */
    public function getFieldByHash($entityType, $hash)
    {
        $fields = $this->getFields();
        if (isset($fields[$entityType])) {
            $key = array_search($hash, $fields[$entityType]);
            if ($key !== false) {
                return $key;
            }
        }

        return $hash;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setFields($fields)
    {
        $this->setFields = $fields;

        return $this;
    }

    /**
     * @return array
     */
    public function getIdFields()
    {
        return $this->idFields;
    }

    /**
     * @param string $entityType
     * @return string
     */
    public function getIdField($entityType)
    {
        $idFields = $this->getIdFields();
        if (isset($idFields[$entityType])) {
            return $idFields[$entityType];
        } else {
            return $this->getSingularType($entityType) . '_id';
        }
    }

    public function setIdFields($idFields)
    {
        $this->$idFields = $idFields;

        return $this;
    }

    /**
     * @return HttpClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param HttpClientInterface $httpClient
     * @return $this
     */
    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @param string $entityType
     * @param int|null $id
     * @return PipedriveResponse
     */
    public function get($entityType, $id = null, $params = [])
    {
        $action = $entityType;
        if ($id !== null) {
            $action .= "/$id";
        }
        $url = $this->getApiUrl($action, $params);

        $response = $this->getHttpClient()->json($url);

        return new PipedriveResponse($this, $entityType, $response);
    }

    /**
     * @param string $entityType
     * @param int $id
     * @param string $childType
     * @return PipedriveResponse
     */
    public function getChilds($entityType, $id, $childType)
    {
        $action = "$entityType/$id/$childType";
        $url = $this->getApiUrl($action);

        $response = $this->getHttpClient()->json($url);

        return new PipedriveResponse($this, $childType, $response);
    }

    /**
     * @param string $entityType
     * @param string $field
     * @param string $needle
     * @param bool $isExact
     * @return PipedriveResponse
     */
    public function find($entityType, $field, $term, $isExact = true)
    {
        $action = "searchResults/field";
        $params['term'] = trim(mb_strtolower($term));
        $params['field_type'] = $this->getSearchField($entityType);
        $params['field_key'] = $this->getFieldHash($entityType, $field);
        $params['return_item_ids'] = 1;
        $params['exact_match'] = $isExact;
        $url = $this->getApiUrl($action, $params);

        $response = $this->getHttpClient()->json($url);

        return new PipedriveResponse($this, $entityType, $response);
    }

    /**
     * @param string $entityType
     * @param array $data
     */
    public function create($entityType, $entity)
    {
        $action = "$entityType";
        $url = $this->getApiUrl($action);

        $response = $this->getHttpClient()->json($url, 'post', $this->addHashFields($entityType, $entity));

        return new PipedriveResponse($this, $entityType, $response);
    }

    /**
     * @param string $entityType
     * @param string $data
     */
    public function update($entityType, $entity)
    {
        $action = "{$entityType}/{$entity['id']}";
        $url = $this->getApiUrl($action);

        $response = $this->getHttpClient()->json($url, 'put', $this->addHashFields($entityType, $entity));

        return new PipedriveResponse($this, $entityType, $response);
    }

    /**
     * @param string $action
     * @param array $params
     * @return string
     */
    public function getApiUrl($action, $params = [])
    {
        $url = "{$this->host}/{$this->version}/{$action}";

        $params['api_token'] = $this->getToken();
        $url .= '?' . http_build_query($params);

        return $url;
    }

    // TODO excetpion
    public function __get($entityType)
    {
        return new PipedriveQuery($this, $entityType);
    }

    /**
     * @param string $entityType
     * @return string
     */
    public function getSearchField($entityType)
    {
        return $this->getSingularType($entityType) . 'Field';
    }

    /**
     * @param string $entityType
     * @return string
     */
    protected function getSingularType($entityType)
    {
        return substr($entityType, 0, -1);
    }

    protected function addHashFields($entityType, $entity)
    {
        foreach ($entity as $key => $value) {
            $field = $this->getFieldHash($entityType, $key);
            unset($entity[$key]);
            $entity[$field] = $value;
        }

        return $entity;
    }
}
