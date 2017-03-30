<?php

namespace Zakirullin\Pipedrive\Tests\Http;

use Zakirullin\Pipedrive\Interfaces\HttpClient;

class FakeHttpClient implements HttpClient
{
    /**
     * @var array $data
     */
    protected $data;

    const URL_TYPE_GET = 'get';
    const URL_TYPE_GET_CHILDS = 'get-childs';
    const URL_TYPE_SEARCH = 'search';

    const PATH_PART_ACTION_INDEX = 1;
    const PATH_PART_ID_INDEX = 2;
    const PATH_PART_CHILD_TYPE_INDEX = 3;

    const ACTION_SEARCH = 'searchResults';

    /**
     * @param array $db
     */
    public function __construct(&$db)
    {
        $this->db = &$db;
    }

    public function json($url, $method = 'GET', $body = '')
    {
        switch ($method) {
            case 'get':
                $data = $this->processGet($url);
                break;
            case 'post':
                $data = $this->processPost($url, $body);
                break;
            case 'put':
                $data = $this->processPut($url, $body);
                break;
        }


        return (object)(['success' => true, 'data' => $data]);
    }

    protected function processGet($url)
    {
        $urlType = $this->getUrlType($url);
        switch ($urlType) {
            case static::URL_TYPE_GET:
                $entities = $this->get($url);
                break;
            case static::URL_TYPE_GET_CHILDS:
                $entities = $this->getChilds($url);
                break;
            case static::URL_TYPE_SEARCH:
                $entities = $this->search($url);
                break;
        }

        return $entities;
    }

    protected function processPost($url, $entity)
    {
        $entity = (object)$entity;
        $type = $this->getAction($url);
        $entity->id = count($this->db[$type]) + 1;
        $this->db[$type][$entity->id] = $entity;

        return $entity;
    }

    protected function processPut($url, $entity)
    {
        $entity = (object)$entity;
        $id = $this->getId($url);
        $entity->id = $id;
        $type = $this->getAction($url);
        $this->db[$type][$id] = $entity;

        return $entity;
    }

    protected function get($url)
    {
        if ($this->hasId($url)) {
            return [$this->db[$this->getAction($url)][$this->getId($url)]];
        } else {
            return $this->db[$this->getAction($url)];
        }
    }

    protected function getChilds($url)
    {
        $childType = $this->getChildType($url);
        return $this->db[$this->getAction($url)][$this->getId($url)]->$childType;
    }

    protected function search($url)
    {
        $term = $this->getUrlSearchTerm($url);
        $field = $this->getUrlSearchField($url);
        $type = $this->getUrlSearchType($url);

        $filteredEntities = [];
        foreach ($this->db[$type] as $entity) {
            // TODO add exactly match
            $value = $entity->$field;
            if (is_array($value)) {
                foreach ($value as $object) {
                    if (is_object($object) && isset($object->value)) {
                        if ($object->value == $term) {
                            $filteredEntities[$entity->id] = $entity;
                            break;
                        }
                    }
                }
            } else {
                if ($value == $term) {
                    $filteredEntities[$entity->id] = $entity;
                }
            }
        }

        return $filteredEntities;
    }

    protected function getUrlType($url)
    {
        if ($this->getAction($url) == static::ACTION_SEARCH) {
            return static::URL_TYPE_SEARCH;
        } else if ($this->hasChildType($url)) {
            return static::URL_TYPE_GET_CHILDS;
        } else {
            return static::URL_TYPE_GET;
        }

    }

    protected function hasId($url)
    {
        $parts = $this->getUrlParts($url);

        return isset($parts[static::PATH_PART_ID_INDEX]) && is_numeric($parts[static::PATH_PART_ID_INDEX]);
    }

    protected function getId($url)
    {
        return $this->getUrlParts($url)[static::PATH_PART_ID_INDEX];
    }

    protected function getAction($url)
    {
        return $this->getUrlParts($url)[static::PATH_PART_ACTION_INDEX];
    }

    protected function hasChildType($url)
    {
        $parts = $this->getUrlParts($url);

        return isset($parts[static::PATH_PART_CHILD_TYPE_INDEX]);
    }

    protected function getChildType($url)
    {
        return $this->getUrlParts($url)[static::PATH_PART_CHILD_TYPE_INDEX];
    }

    protected function getUrlParts($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $parts = preg_split('/\//', $path, -1, PREG_SPLIT_NO_EMPTY);

        return $parts;
    }

    protected function getUrlParams($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);

        return $params;
    }

    protected function getUrlSearchType($url)
    {
        $fieldType = $this->getUrlParams($url)['field_type'];
        switch ($fieldType) {
            case 'dealField':
                return 'deals';
            case 'organizationField':
                return 'organizations';
            case 'personField':
                return 'persons';
            case 'productField':
                return 'products';
        }
    }

    protected function getUrlSearchField($url)
    {
        return $this->getUrlParams($url)['field_key'];
    }

    protected function getUrlSearchTerm($url)
    {
        return $this->getUrlParams($url)['term'];
    }
}