<?php

namespace Zakirullin\Pipedrive;
use Zakirullin\Pipedrive\Exceptions\PipedriveException;

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

    // TODO replace with polymorph
    const QUERY_TYPE_GET = 'get';
    const QUERY_TYPE_GET_CHILDS = 'get-childs';
    const QUERY_TYPE_FIND = 'find';
    const QUERY_TYPE_CHAIN = 'chain';

    /**
     * @param Pipedrive $pipedrive
     * @param string $entityType
     * @param PipedriveQuery|null $prev
     */
    public function __construct($pipedrive, $entityType, $prev = null, $root = null)
    {
        $this->setPipedrive($pipedrive);
        $this->setType($entityType);
        $this->setRoot($root);
        if ($prev) {
            $prev->setNext($this);
            $this->setPrev($prev);
        }

        return $this;
    }

    public function find($condition, $exactMatch = true)
    {
        $this->setCondition($condition);
        $this->setExactMatch($exactMatch);

        return $this;
    }

    public function all()
    {
        return $this->getRoot()->execute();
    }

    public function one()
    {

    }

    public function execute()
    {
        if ($this->getType() == static::QUERY_TYPE_CHAIN) {
            return $this->executeChain();
        } else {
            return $this->executeRoot();
        }
    }

    /**
     * @return $this
     */
    protected function executeChain()
    {
        $pipedrive = $this->getPipedrive();
        $parentType = $this->getPrev()->getEntityType();
        $childType = $this->getEntityType();
        foreach ($this->prev()->getEntities() as $parentEntity) {
            $pipedrive->$parentType->find($parentEntity->id)->$childType->walkAll(function($entity) use ($this) {
                $this->entities[$entity->id] = $entity;
            });
        }

        $this->filter();

        if ($next = $this->getNext()) {
            return $next->execute();
        } else {
            return $this->getEntities();
        }
    }

    // TODO replace with ploymorph (?)
    /**
     * @return $this
     * @throws PipedriveException
     */
    protected function executeRoot()
    {
        switch ($this->getType()) {
            case static::QUERY_TYPE_GET: {
                $entityType = $this->getEntityType();
                $id = isset($this->condition['id']) ? $this->condition['id'] : null;
                $this->setEntities($this->getPipedrive()->get($entityType, $id)->getEntities());
                $next = $this->getNext();
            }
            case static::QUERY_TYPE_GET_CHILDS: {
                $entityType = $this->getEntityType();
                $id = $this->getCondition()['id'];
                $childEntityType = $this->getNext()->getEntityType();
                $this->setEntities($this->getPipedrive()->get($entityType, $id, $childEntityType)->getEntities());
                $next = $this->getNext()->getNext();
            }
            case static::QUERY_TYPE_FIND: {
                $next = $this->getNext();
                $field = array_keys($this->condition)[0];
                $term = array_shift($this->condition);
                $this->setEntities($this->getPipedrive()->find($this->entityType, $field, $term));
                $next = $this->getNext();
            }
            default: {
                throw new PipedriveException('Invalid query');
            }
        }

        $this->filter();

        if ($next) {
            return $next->execute();
        } else {
            return $this->getEntities();
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
            $this->condition = ['id' => $condition];
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

        return new static($this->getPipedrive(), $entityType, $this);
    }

    public function __call($method, $params)
    {
        if (isset($params[0])) {
            return (new Entity($this))->$method($params[0]);
        } else {
            return (new Entity($this))->$method();
        }
    }

    /**
     * @return PipedriveQuery
     */
    public function getRoot()
    {
        $entityQuery = $this;
        while (!$entityQuery->getType() == static::QUERY_TYPE_CHAIN) {
            $entityQuery = $this->getPrev();
        }

        return $entityQuery;
    }

    // TODO exception if null
    /**
     * @param PipedriveQuery $entityQuery
     * @return bool
     */
    protected function getType()
    {
        if (($prev = $this->getPrev()) && !$prev->getPrev()) {
            $condition = $prev->getCondition();
            if (isset($condition['id'])) {
               return static::QUERY_TYPE_GET_CHILDS;
            }
        } else if ($this->getEntityType()) {
            if (isset($this->condition['id'])) {
                return static::QUERY_TYPE_GET;
            } else {
                return static::QUERY_TYPE_GET_CHILDS;
            }
        } else {
            return static::QUERY_TYPE_CHAIN;
        }
    }

    /**
     * @param array $entities
     * @param array|int $condition
     * @return array
     */
    protected function filter()
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