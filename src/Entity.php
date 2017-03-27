<?php

namespace Zakirullin\Pipedrive;

/**
 * @property Entity organizations
 * @property Entity activities
 * @property Entity deals
 * @property Entity persons
 * @property Entity notes
 */
class Entity
{
    /**
     * @var array $ids
     */
    protected static $idFields = [
        'organizations' => 'org_id',
    ];

    /**
     * @var Pipedrive $pipedrive
     */
    protected $pipedrive;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var Entity $parent
     */
    protected $parent;

    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var int|array $condition
     */
    protected $condition;

    const WALK_STEP = 500;

    public function __construct($pipedrive, $type, $parent = null)
    {
        $this->pipedrive = $pipedrive;
        $this->type = $type;
        $this->parent = $parent;

        return $this;
    }

    public function find($condition)
    {
        $this->condition = $condition;

        if (is_int($condition)) {
            $this->id = $condition;
        }

        return $this;
    }

    public function one()
    {
        return $this->pipedrive->process($this, 'get');
    }

    public function all()
    {
        $entities = [];
        $collect = function($entity) use (&$entities) {
            $entities[] = $entity;
        };
        $this->walkAll($collect);

        return $entities;
    }

    public function findOne($id)
    {
        $this->id = $id;

        return $this->one();
    }

    public function findAll()
    {

    }

    public function walkAll($callback)
    {
        $start = 0;
        do {
            $terminate = true;

            $params = ['limit' => static::WALK_STEP, 'start' => $start];
            $response = $this->pipedrive->sendRequest($this, 'get', [], $params);
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

    public function create($entity)
    {
        $entity = (array)$entity;
        $entity = $this->addShortFields($entity);

        $parent = $this->getParent();
        while ($parent) {
            $entity[$parent->getIdField()] = $parent->getId();
            $parent = $parent->getParent();
        }

        return $this->pipedrive->process($this, 'post', $entity)->id;
    }

    // TODO exceptions
    public function update($entity)
    {
        $entity = (array)$entity;

        if ($this->getId() == null) {
            if (isset($entity['id'])) {
                $this->setId($entity['id']);
            } else {
                throw new \Exception('Unable to update entity without id');
            }
        }

        return $this->pipedrive->process($this, 'put', $entity);
    }

    public function delete($entity)
    {

    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    public function getType()
    {
        return $this->type;
    }


    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null|Entity
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get related entity
     *
     * @param $type string
     * @return static
     */
    public function __get($type)
    {
        return new static($this->pipedrive, $type, $this);
    }

    protected function getIdField()
    {
        if (isset(static::$idFields[$this->getType()])) {
            return static::$idFields[$this->getType()];
        } else {
            return $this->buildIdField();
        }
    }

    protected function buildIdField()
    {
        return $this->getSingularType() . '_id';
    }

    protected function buildSearchField()
    {
        return $this->getSingularType() . 'Field';
    }

    protected function getSingularType()
    {
        return substr($this->type, 0, -1);
    }

    protected function addShortFields($entity)
    {
        $isObject = is_object($entity);
        $entity = (array)$entity;
        foreach ($entity as $key => $value) {
            if ($field = $this->pipedrive->getShortField($this, $key)) {
                $entity[$field] = $value;
            }
        }

        if ($isObject) {
            $entity = (object)$entity;
        }

        return $entity;
    }
}
