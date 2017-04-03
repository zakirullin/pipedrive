<?php

namespace Zakirullin\Pipedrive;

use Zakirullin\Pipedrive\Interfaces\HttpClient as HttpClientInterface;
use Zakirullin\Pipedrive\Http\HttpClient;

/**
 * @property EntityQuery organizations
 * @property EntityQuery activities
 * @property EntityQuery deals
 * @property EntityQuery persons
 * @property EntityQuery notes
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
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * All other id fields can be geted using {getSingularType()}_id
     *
     * @var array
     */
    protected $idFields = [
        'organizations' => 'org_id',
        'activities' => 'activity_id',
    ];

    /**
     * Pipedrive constructor.
     * @param string $apiToken
     * @param array $fields
     * @param HttpClientInterface $httpClient
     * @param string $host
     * @param string $version
     */
    public function __construct($token, $fields = [], $httpClient = null, $host = 'https://api.pipedrive.com', $version = 'v1')
    {
        $this->token = $token;
        $this->fields = $fields;

        if (!$httpClient) {
            $httpClient = new HttpClient();
        }
        $this->httpClient = $httpClient;

        $this->host = $host;
        $this->version = $version;

        return $this;
    }


    public function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     * @param string $apiToken
     * @return $this
     */
    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;

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
     * @param array $fields
     * @return $this
     */
    public function setFields($fields)
    {
        $this->setFields = $fields;

        return $this;
    }

    public function getIdFields()
    {
        return $this->idFields;
    }

    public function setIdFields($idFields)
    {
        $this->$idFields = $idFields;

        return $this;
    }

    /**
     * @param string $type
     * @param int|null $id
     * @return PipedriveResponse
     */
    public function get($type, $id = null)
    {
        $url .= "/{$entityQuery->getType()}";
        if ($entityQuery->getId() !== null) {
            $url .= "/{$entityQuery->getId()}";
        }
    }

    /**
     * @param string $type
     * @param int $id
     * @param string $childType
     * @return PipedriveResponse
     */
    public function getChilds($type, $id, $childType)
    {
        $url .= "/{$prev->getType()}/{$prev->getId()}/{$entityQuery->getType()}";
    }

    /**
     * @param string $type
     * @param string $field
     * @param string $needle
     * @param bool $isExact
     * @return PipedriveResponse
     */
    public function search($type, $field, $needle, $isExact = true)
    {
        $url .= "/searchResults/field";
        $params['term'] = trim(mb_strtolower(current(array_values($condition))));
        $params['field_type'] = $this->getSearchField($entityQuery->getType());
        $params['field_key'] = $this->getLongField($entityQuery->getType(), array_keys($condition)[0]);
        $params['return_item_ids'] = 1;
        $params['exact_match'] = (int)$entityQuery->isExactMatch();
    }

    /**
     * @param string $type
     * @param array $data
     */
    public function create($type, $data)
    {

    }

    /**
     * @param string $type
     * @param string $data
     */
    public function update($type, $data)
    {

    }

    /**
     * @param string action
     * @param array $params
     * @return string
     */
    public function getApiUrl($action, $params = [])
    {
        $url = "{$this->host}/{$this->version}/{$action}";

        $params['api_token'] = $this->apiToken;
        $url .= '?' . http_get_query($params);

        return $url;
    }

    // TODO excetpion
    public function __get($entityType)
    {
        return new EntityQuery($this, $entityType);
    }

    /**
     * @param string $entityQuery
     * @param string $field
     * @return null|string
     */
    public function getLongField($type, $field)
    {
        return isset($this->fields[$type][$field]) ? $this->fields[$type][$field] : $field;
    }

    public function getShortField($type, $field)
    {
        $fields = $this->getFields();
        if (isset($fields[$type])) {
            $key = array_search($field, $fields[$type]);
            if ($key !== false) {
                return $key;
            }
        }

        return $field;
    }

    public function getIdField($type)
    {
        $idFields = $this->getIdFields();
        if (isset($idFields[$type])) {
            return $idFields[$type];
        } else {
            return $this->getIdField($type);
        }
    }

    protected function getIdField($type)
    {
        return $this->getSingularType($type) . '_id';
    }

    protected function getSearchField($type)
    {
        return $this->getSingularType($type) . 'Field';
    }

    protected function getSingularType($type)
    {
        return substr($type, 0, -1);
    }
}
