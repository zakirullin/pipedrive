<?php

namespace Zakirullin\Pipedrive;

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

    /**
     * @var array $ids
     */
    protected $idFields = [
        'organizations' => 'org_id',
    ];

    const WALK_STEP = 500;

    // TODO add version and scheme
    public function __construct($apiToken, $fields = [], $apiUrl = 'https://api.pipedrive.com/v1')
    {
        $this->apiToken = $apiToken;
        $this->shortFields = $fields;
        $this->apiUrl = $apiUrl;

        return $this;
    }


    public function process($entityQuery, $method, $data = [])
    {
        return $this->processResponse($this->sendRequest($entityQuery, $method, $data));
    }

    // TODO Checking for parents
    public function sendRequest(EntityQuery $entity, $method, $data = [], $params = [])
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

    public function walkAll(EntityQuery $entity, $callback)
    {
        $start = 0;
        do {
            $terminate = true;

            $params = ['limit' => static::WALK_STEP, 'start' => $start];
            $response = $this->sendRequest($entity, 'get', [], $params);
            $isPaginationExists = isset($response->additional_data->pagination);
            $isMoreItems = $isPaginationExists && $response->additional_data->pagination->more_items_in_collection;
            if ($response->success == 'true') {
                if ($isMoreItems) {
                    $start = $response->additional_data->pagination->next_start;
                }

                if (is_array($response->data)) {
                    foreach ($response->data as $data) {
                        $terminate = $callback($data);
                        if ($terminate) {
                            break;
                        }
                    }
                } else {
                    $terminate = $callback($response->data);
                }
            }

        } while (!$terminate && $isMoreItems);
    }

    // TODO excetpion
    public function __get($entityType)
    {
        return new EntityQuery($this, $entityType);
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
     * @param EntityQuery $entity
     * @param array $params
     * @param null|string $method
     * @return string
     */
    public function buildApiUrl(EntityQuery $entityQuery, $params = [], $method = null)
    {
        $url = $this->getApiUrl();

        $condition = $entityQuery->getCondition();
        if (is_array($condition)) {
            if ($condition) {
                $url .= "/searchResults/field";
                $params['term'] = array_values($condition)[0];
                $params['field_type'] = mb_strtolower(trim($this->buildSearchField($entityQuery->getType())));
                $params['field_key'] = $this->getLongField($entityQuery->getType(), array_keys($condition)[0]);
                $params['return_item_ids'] = 1;
                $params['exact_match'] = (int)$entityQuery->isExactMatch();
            } else {
                throw new \Exception('Condition is empty!');
            }
        } else {
            if (($prev = $entityQuery->getPrev()) && $prev->getId() && $method == 'get') {
                $url .= "/{$prev->getType()}/{$prev->getId()}/{$entityQuery->getType()}";
            } else {
                $url .= "/{$entityQuery->getType()}";
                if ($entityQuery->getId() !== null) {
                    $url .= "/{$entityQuery->getId()}";
                }
            }
        }

        $params['api_token'] = $this->apiToken;
        $url .= '?' . http_build_query($params);

        return $url;
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
                return $fields[$key];
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
            return $this->buildIdField($type);
        }
    }

    protected function buildIdField($type)
    {
        return $this->getSingularType($type) . '_id';
    }

    protected function buildSearchField($type)
    {
        return $this->getSingularType($type) . 'Field';
    }

    protected function getSingularType($type)
    {
        return substr($type, 0, -1);
    }
}
