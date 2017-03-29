<?php

namespace Zakirullin\Pipedrive;

/**
 * @property EntityQuery organizations
 * @property EntityQuery activities
 * @property EntityQuery deals
 * @property EntityQuery persons
 * @property EntityQuery notes
 * @method array create
 * @method array update
 * @method array all
 * @method array findAll
 * @method Entity|null one
 * @method Entity|null findOne
 * @method forEach
 */
class EntityQuery
{
    /**
     * @var Pipedrive $entity
     */
    protected $pipedrive;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var EntityQuery $prev
     */
    protected $prev;

    /**
     * @var EntityQuery $next
     */
    protected $next;

    /**
     * @var int|array $condition
     */
    protected $condition;

    /**
     * @var bool $exactMatch
     */
    protected $exactMatch;

    /**
     * EntityFilter constructor.
     * @param Pipedrive $pipedrive
     * @param $type
     * @param null|EntityQuery $prev
     */
    public function __construct($pipedrive, $type, $prev = null)
    {
        $this->setPipedrive($pipedrive);
        $this->setType($type);
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
     * @return null|EntityQuery
     */
    public function getPrev()
    {
        return $this->prev;
    }

    /**
     * @param null|EntityQuery $parent
     * @return $this
     */
    public function setPrev($prev)
    {
        $this->prev = $prev;

        return $this;
    }

    /**
     * @return EntityQuery
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @param EntityQuery $next
     * @return $this
     */
    public function setNext($next)
    {
        $this->next = $next;

        return $this;
    }

    public function getId()
    {
        return is_array($this->getCondition()) ? null : $this->getCondition();
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
     * Chain filtering
     *
     * @param $type string
     * @return static
     */
    public function __get($type)
    {
        return new static($this->getPipedrive(), $type, $this);
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
     * @return EntityQuery
     */
    public function getRoot()
    {
        $entityFilter = $this;
        while (!$entityFilter->isRoot()) {
            $entityFilter = $this->getPrev();
        }

        return $entityFilter;
    }

    // TODO exception if null
    /**
     * @param EntitFilter $entityFilter
     * @return bool
     */
    protected function isRoot()
    {
        if (($prev = $this->getPrev()) && !$prev->getPrev()) {
            return (bool)$prev->getId();
        } else if ($this->getType()) {
            return true;
        }

        return false;
    }
}