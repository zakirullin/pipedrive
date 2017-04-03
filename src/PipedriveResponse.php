<?php

namespace Zakirullin\Pipedrive;

class PipedriveResponse
{
    /**
     * @var Pipedrive
     */
    protected $pipedrive;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var string
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
     * @param string $response
     */
    public function __construct($pipedrive, $entityType, $response)
    {
        $this->pipedrive = $pipedrive;
        $this->entityType = $entityType;
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
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @param string $entityType
     * @return $this
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;

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
        if ($this->isComplete($this->response)) {
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
    public function getData()
    {
        return $this->data;
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
     * @param object $response
     * @return bool
     */
    public function isComplete()
    {
        $isPaginationExists = isset($this->getAdditionalData()->pagination);
        $isMoreItems = $isPaginationExists && $this->getAdditionalData()->pagination->more_items_in_collection;

        return !$isMoreItems;
    }

    /**
     * @return $this
     */
    protected function nextPage()
    {
        $start = $this->response->additional_data->pagination->next_start;
        $params = ['limit' => static::PAGINATE_STEP, 'start' => $start];

        return $this->getPipedrive()->get($this->getEntityType(), null, $params);
    }

    /**
     * @param object $entity
     * @return mixed
     */
    protected function addShortFields($entity)
    {
        $entity = (array)$entity;
        foreach ($entity as $key => $value) {
            $field = $this->getPipedrive()->getFieldByHash($this->getEntityType(), $key);
            unset($entity[$key]);
            $entity[$field] = $value;
        }

        return (object)$entity;
    }
}