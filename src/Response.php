<?php

namespace Zakirullin\Pipedrive;

class Response
{
    /**
     * @var Pipedrive
     */
    protected $pipedrive;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var object
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var bool
     */
    protected $isSuccess = false;

    const ENTITIES_PER_PAGE = 500;

    /**
     * @param Pipedrive $pipedrive
     * @param string $type
     * @param object $response
     */
    public function __construct($pipedrive, $type, $response)
    {
        $this->pipedrive = $pipedrive;
        $this->entityType = $type;
        $this->response = $response;

        if ($this->response->success == 'true') {
            $this->data = $response->data;
            $this->isSuccess = true;
        }
    }

    /**
     * @return Pipedrive
     */
    public function getPipedrive()
    {
        return $this->pipedrive;
    }

    /**
     * @param Pipedrive $pipedrive
     * @return $this
     */
    public function setPipedrive($pipedrive)
    {
        $this->pipedrive = $pipedrive;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->entityType;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->entityType = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        $entities = [];
        if ($this->isComplete()) {
            $entities = $this->data;
            if (!is_array($entities)) {
                $entities = [$entities->id => $entities];
            }
        } else {
            $collect = function ($entity) use (&$entities) {
                if ($entity) {
                    $entities[$entity->id] = $entity;
                }
            };
            $this->walkAll($collect);
        }

        foreach ($entities as &$entity) {
            $entity = $this->addShortFields($entity);
        }

        return $entities;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return is_array($this->data) ? $this->data[0] : $this->data;
    }

    /**
     * @return object|null
     */
    public function getAdditionalData()
    {
        if (isset($this->response->additional_data)) {
            return $this->response->additional_data;
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isSuccess;
    }

    /**
     * @param callable $callback
     * @return bool
     */
    public function walkAll($callback)
    {
        $response = $this;
        do {
            $terminate = $response->walk($callback);
            $isComplete = $response->isComplete();
            if (!$isComplete) {
                $response = $this->getPipedrive()->get($this->entityType, null, [
                    'start' => $response->getAdditionalData()->pagination->next_start,
                    'limit' => static::ENTITIES_PER_PAGE
                ]);
            }
        } while (!$terminate || !$isComplete);

        return $terminate;
    }

    public function walk($callback)
    {
        $terminate = false;
        if (is_array($this->data)) {
            foreach ($this->data as $data) {
                $terminate = $callback($data);
                if ($terminate) {
                    break;
                }
            }
        }

        return $terminate;
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        $isPaginationExists = isset($this->getAdditionalData()->pagination);
        $isMoreItems = $isPaginationExists && $this->getAdditionalData()->pagination->more_items_in_collection;

        return !$isMoreItems;
    }

    /**
     * @param object $entity
     * @return mixed
     */
    protected function addShortFields($entity)
    {
        $entity = (array)$entity;
        foreach ($entity as $key => $value) {
            $field = $this->getPipedrive()->getFieldByHash($this->getType(), $key);
            unset($entity[$key]);
            $entity[$field] = $value;
        }

        return (object)$entity;
    }
}