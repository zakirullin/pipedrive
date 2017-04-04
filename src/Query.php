<?php

namespace Zakirullin\Pipedrive;

use Zakirullin\Pipedrive\Exceptions\Exception;
use Zakirullin\Pipedrive\Executors\Executor;

/**
 * @property Query $organizations
 * @property Query $activities
 * @property Query $deals
 * @property Query $persons
 * @property Query $notes
 * @property Query $products
 * @property Query $users
 */
class Query
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
     * @var array
     */
    protected $entities;

    /**
     * @var self
     */
    protected $root;

    /**
     * @var self
     */
    protected $prev;

    /**
     * @var self
     */
    protected $next;

    /**
     * @var array
     */
    protected $condition;

    /**
     * @var bool
     */
    protected $exactMatch;

    /**
     * @param Pipedrive $pipedrive
     * @param string $type
     * @param self $prev
     * @param self $root
     */
    public function __construct($pipedrive, $type, $prev = null, $root = null)
    {
        $this->setPipedrive($pipedrive);
        $this->setType($type);

        if ($prev) {
            $prev->setNext($this);
            $this->setPrev($prev);
            $this->setRoot($root);
        } else {
            $this->setRoot($this);
        }

        return $this;
    }

    /**
     * @param array|int $condition
     * @param bool $exactMatch
     * @return $this
     */
    public function find($condition, $exactMatch = true)
    {
        $this->setCondition($condition);
        $this->setExactMatch($exactMatch);

        return $this;
    }

    /**
     * @param int $condition
     */
    public function findOne($condition)
    {
        $this->setCondition($condition);

        return $this->one();
    }

    /**
     * @param array $condition
     */
    public function findAll($condition)
    {
        $this->setCondition($condition);

        return $this->all();
    }

    /**
     * @param mixed $entity
     * @return int
     */
    public function create($entity)
    {
        $entity = (array)$entity;

        return $this->getPipedrive()->create($this->getType(), $entity)->getEntity()->id;
    }

    /**
     * @param mixed $entity
     * @return int
     * @throws Exception
     */
    public function update($entity)
    {
        $entity = (array)$entity;

        if (!isset($entity['id'])) {
            $condition = $this->getCondition();
            if (isset($condition['id'])) {
                $entity['id'] = $condition['id'];
            } else {
                throw new Exception('Cannot update without id.');
            }
        }

        return $this->getPipedrive()->update($this->getType(), $entity)->getEntity()->id;
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
     * @return $this
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

    /**
     * @return string
     */
    public function getType()
    {
        return $this->entityType;
    }

    public function setType($type)
    {
        $this->entityType = $type;

        return $this;
    }

    /**
     * @return null|Query
     */
    public function getPrev()
    {
        return $this->prev;
    }

    /**
     * @param self|null $prev
     * @return $this
     */
    public function setPrev($prev)
    {
        $this->prev = $prev;

        return $this;
    }

    /**
     * @return Query
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @param Query $next
     * @return $this
     */
    public function setNext($next)
    {
        $this->next = $next;

        return $this;
    }

    /**
     * @return array
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return int|null
     */
    public function getConditionId()
    {
        $condition = $this->getCondition();

        return isset($condition['id']) ? $condition['id'] : null;
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
     * @param string $type
     * @return static
     */
    public function __get($type)
    {
        $root = $this->getRoot() ? $this->getRoot() : $this;

        return new static($this->getPipedrive(), $type, $this, $root);
    }

    /**
     * @return Query
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param self $root
     * @return $this
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    public function filter()
    {
        $filteredEntities = [];
        $condition = $this->getCondition();
        if ($condition && is_array($condition)) {
            foreach ($this->getEntities() as $entity) {
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
            foreach ($this->getEntities() as $entity) {
                if ($entity->id == $condition) {
                    $filteredEntities[] = $entity;
                }
            }
        } else {
            $filteredEntities = $this->getEntities();
        }

        $this->setEntities($filteredEntities);
    }
}