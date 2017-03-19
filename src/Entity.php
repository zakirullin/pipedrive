<?php

namespace Zakirullin\Pipedrive;

class Entity
{

    /**
     * @var Pipedrive $pipedrive
     */
    protected $pipedrive;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var Entity $parent
     */
    protected $parent;

    const WALK_LIMIT = 500;

    public function __construct($pipedrive, $type, $id = null, $parent = null)
    {
        $this->pipedrive = $pipedrive;
        $this->type = $type;
        $this->id = $id;
        $this->parent = $parent;

        return $this;
    }


    public function find($params)
    {

    }

    public function get()
    {
        return $this->pipedrive->sendRequest($this, 'get');
    }

    public function getAll()
    {
        $entities = [];
        $collect = function($entity) use ($entities) {
            $entities[] = $entity;
        };
        $this->walkAll($collect);

        return $entities;
    }

    public function walkAll($callback)
    {
        $start = 0;
        do {
            $params = ['limit' => static::WALK_LIMIT, 'start' => $start];
            $response = $this->pipedrive->sendRequest($this, 'get', [], $params, false);
            if ($response->success == 'true') {
                if (isset($response->additional_data->pagination->next_start)) {
                    $start = $response->additional_data->pagination->next_start;
                }
                if ($stop = $callback($response->data)) {
                    return false;
                }
            } else {
                return false;
            }
        } while ($response->additional_data->pagination->more_items_in_collection);

        return true;
    }

    public function create($entity)
    {
        $entity = (array)$entity;

        return $this->pipedrive->sendRequest($this, 'post', $entity);
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

        return $this->pipedrive->sendRequest($this, 'put', $entity);
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

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function __call($type, $params)
    {
        return new static($this->pipedrive, $type, isset($params[0]) ? $params[0] : null, $this);
    }
}
