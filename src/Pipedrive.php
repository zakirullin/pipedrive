<?php

namespace Zakirullin\Pipedrive;

/**
 * @property EntityFilter organizations
 * @property EntityFilter activities
 * @property EntityFilter deals
 * @property EntityFilter persons
 * @property EntityFilter notes
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

    // TODO Checking for parents
    public function sendRequest(EntityFilter $entity, $method, $data = [], $params = [])
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

    public function walkAll(EntityFilter $entity, $callback)
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
        return new EntityFilter($this, $entityType);
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
     * @param EntityFilter $entity
     * @param array $params
     * @param null|string $method
     * @return string
     */
    protected function buildApiUrl(EntityFilter $entityFilter, $params = [], $method = null)
    {
        $url = $this->getApiUrl();

        $condition = $entityFilter->getCondition();
        if (is_array($condition)) {
            if ($condition) {
                // BuildSearchQuery
                $url .= "/searchResults/field";
                $params['term'] = array_values($condition)[0];
                $params['field_type'] = $entityFilter->buildSearchField();
                $params['field_key'] = $this->getField($entityFilter, array_keys($condition)[0]);
                $params['return_item_ids'] = 1;
                $params['exact_match'] = (int)$entityFilter->isExactMatch();
            } else {
                throw new \Exception('Condition is empty!');
            }
        } else {
            if (($prev = $entityFilter->getPrev()) && $prev->getId() && $method == 'get') {
                // BuilRelationQuery
                $url .= "/{$prev->getType()}/{$prev->getId()}/{$entityFilter->getType()}";
            } else {
                // BuildQuery
                $url .= "/{$entityFilter->getType()}";
                if ($entityFilter->getId() !== null) {
                    $url .= "/{$entityFilter->getId()}";
                }
            }
        }

        $params['api_token'] = $this->apiToken;
        $url .= '?' . http_build_query($params);

        return $url;
    }
}
