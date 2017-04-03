<?php

namespace Zakirullin\Pipedrive;

use Zakirullin\Pipedrive\Exceptions\PipedriveException;
use Zakirullin\Pipedrive\Executors\Executor;

/**
 * @property PipedriveQuery organizations
 * @property PipedriveQuery activities
 * @property PipedriveQuery deals
 * @property PipedriveQuery persons
 * @property PipedriveQuery notes
 * @property PipedriveQuery products
 */
class PipedriveQuery
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
     * @var array
     */
    protected $entities;

    /**
     * @var static
     */
    protected $root;

    /**
     * @var static
     */
    protected $prev;

    /**
     * @var static
     */
    protected $next;

    /**
     * @var int|array
     */
    protected $condition;

    /**
     * @var bool
     */
    protected $exactMatch;

   /**
     * @param Pipedrive $pipedrive
     * @param string $entityType
     * @param PipedriveQuery|null $prev
     */
    public function __construct($pipedrive, $entityType, $prev = null, $root = null)
    {
        $this->setPipedrive($pipedrive);
        $this->setEntityType($entityType);

        if ($prev) {
            $prev->setNext($this);
            $this->setPrev($prev);
            $this->setRoot($root);
        } else {
            $this->setRoot($this);
        }

        return $this;
    }

    public function find($condition, $exactMatch = true)
    {
        $this->setCondition($condition);
        $this->setExactMatch($exactMatch);

        return $this;
    }

    /**
     * @param mixed $entity
     * @return int
     */
    public function create($entity)
    {
        $entity = (array)$entity;

        return $this->getPipedrive()->create($this->getEntityType(), $entity)->getData()->id;
    }

    /**
     * @param mixed $entity
     * @return int
     */
    public function update($entity)
    {
        $entity = (array)$entity;

        if (!isset($entity['id'])) {
            $condition = $this->getCondition();
            if (isset($condition['id'])) {
                $entity['id'] = $condition['id'];
            } else {
                throw new PipedriveException('Cannot update without id.');
            }
        }

        return $this->getPipedrive()->update($this->getEntityType(), $entity)->getData()->id;
    }

    public function all()
    {
        return Executor::factory($this->getRoot())->execute();
    }

    public function one()
    {
        $entities = $this->all();

        return array_shift($entities);
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
     * @return this
     */
    public function setPipedrive($pipedrive)
    {
        $this->pipedrive = $pipedrive;

        return $this;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @param array $entities
     * @return $this
     */
    public function setEntities($entities)
    {
        $this->entities = $entities;

        return $this;
    }

    public function getEntityType()
    {
        return $this->type;
    }

    public function setEntityType($entityType)
    {
        $this->type = $entityType;

        return $this;
    }

    /**
     * @return null|PipedriveQuery
     */
    public function getPrev()
    {
        return $this->prev;
    }

    /**
     * @param null|PipedriveQuery $parent
     * @return $this
     */
    public function setPrev($prev)
    {
        $this->prev = $prev;

        return $this;
    }

    /**
     * @return PipedriveQuery
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @param PipedriveQuery $next
     * @return $this
     */
    public function setNext($next)
    {
        $this->next = $next;

        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return bool
     */
    public function isConditionValid()
    {
        return (is_array($this->getCondition()) && $this->getCondition()) || $this->getCondition();
    }

    public function setCondition($condition)
    {
        if (!is_array($condition)) {
            $condition = ['id' => $condition];
        }

        $this->condition = $condition;

        return $this;
    }

    public function setExactMatch($exactMatch)
    {
        $this->exactMatch = $exactMatch;
    }

    public function isExactMatch()
    {
        return $this->exactMatch;
    }

    /**
     * @param $entityType string
     * @return static
     */
    public function __get($entityType)
    {
        $root = $this->getRoot() ? $this->getRoot() : $this;

        return new static($this->getPipedrive(), $entityType, $this, $root);
    }

    /**
     * @return PipedriveQuery
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param static $root
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @param array $entities
     * @param array|int $condition
     * @return array
     */
    public function filter()
    {
        $filteredEntities = [];
        $condition = $this->getCondition();
        $entities = $this->getEntities();
        if ($condition && is_array($condition)) {
            foreach ($entities as $entity) {
                foreach ($condition as $field => $term) {
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
            }
        } else if ($condition) {
            foreach ($entities as $entity) {
                if ($entity->id == $condition) {
                    $filteredEntities[] = $entity;
                }
            }
        } else {
            $filteredEntities = $entities;
        }

        $this->setEntities($filteredEntities);
    }
}