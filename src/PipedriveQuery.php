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
        return $this->getRoot()->execute();
    }

    public function one()
    {
        $entities = $this->getRoot()->execute();

        return array_shift($entities);
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
            $entities = &$this->entities;
            $pipedrive->$parentType->find($parentEntity->id)->$childType->walkAll(function($entity) use (&$entities) {
                $entities[$entity->id] = $entity;
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
                $entities = $this->getPipedrive()->get($entityType, $id)->getEntities();
                $this->setEntities($entities);
                $next = $this->getNext();
                break;
            }
            case static::QUERY_TYPE_GET_CHILDS: {
                $entityType = $this->getEntityType();
                $id = $this->getCondition()['id'];
                $childEntityType = $this->getNext()->getEntityType();
                $entities = $this->getPipedrive()->getChilds($entityType, $id, $childEntityType)->getEntities();
                $this->getNext()->setEntities($entities);
                $this->getNext()->filter();
                $next = $this->getNext()->getNext();
                break;
            }
            case static::QUERY_TYPE_FIND: {
                $field = array_keys($this->condition)[0];
                $term = array_shift($this->condition);
                $entities = $this->getPipedrive()->find($this->getEntityType(), $field, $term)->getEntities();
                $this->setEntities($entities);
                $this->filter();
                $next = $this->getNext();
                break;
            }
            default: {
                throw new PipedriveException('Invalid query');
            }
        }


        if ($next) {
            return $next->execute();
        } else {
            return $entities;
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

    // TODO exception if null
    /**
     * @param PipedriveQuery $entityQuery
     * @return bool
     */
    protected function getType()
    {
        if (!$this->getPrev()) {
            if (isset($this->condition['id'])) {
                if (($next = $this->getNext())) {
                    return static::QUERY_TYPE_GET_CHILDS;
                } else {
                    return static::QUERY_TYPE_GET;
                }
            }

            return static::QUERY_TYPE_FIND;
        }

        return static::QUERY_TYPE_CHAIN;
    }
}