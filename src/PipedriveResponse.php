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

    const PAGINATE_STEP = 500;

    /**
     * @param Pipedrive $pipedrive
     * @param string $response
     */
    public function __construct($pipedrive, $entityType, $response)
    {
        $this->pipedrive = $pipedrive;
        $this->entityType = $entityType;
        $this->response = json_decode($response);

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
     * @return string
     */
    public function getData()
    {
        if ($this->isComplete($this->response)) {
            return $this->data;
        } else {
            $entities = [];
            $collect = function ($entity) use (&$entities) {
                if ($entity) {
                    $entities[$entity->id] = (object)$this->addShortFields((array)$entity);
                }
            };
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
    public function paginate($callback)
    {
        $start = 0;
        do {
            $terminate = true;

            $params = ['limit' => static::PAGINATE_STEP, 'start' => $start];
            $response = $this->pipedrive->get($entity, 'get', [], $params);

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

    protected function isComplete($response)
    {
        $isPaginationExists = isset($response->additional_data->pagination);
        $isMoreItems = $isPaginationExists && $response->additional_data->pagination->more_items_in_collection;

        return !$isMoreItems;
    }

    /**
     * @param array $entity
     * @return mixed
     */
    protected function addShortFields($entity)
    {
        foreach ($entity as $key => $value) {
            $field = $this->pipedrive->getShortField($this->getEntityQuery()->getType(), $key);
            unset($entity[$key]);
            $entity[$field] = $value;
        }

        return $entity;
    }
}