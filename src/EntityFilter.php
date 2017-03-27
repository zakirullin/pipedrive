<?php

namespace Zakirullin\Pipedrive;

/**
 * @property EntityFilter organizations
 * @property EntityFilter activities
 * @property EntityFilter deals
 * @property EntityFilter persons
 * @property EntityFilter notes
 */
class EntityFilter
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
     * @var EntityFilter $prev
     */
    protected $prev;

    /**
     * @var EntityFilter $next
     */
    protected $next;

    /**
     * @var int $id
     */
    protected $id;

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
     * @param null|EntityFilter $prev
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
        if (!is_array($condition)) {
            $this->setId($condition);
        }

        return $this;
    }

    public function one()
    {

    }

    public function all()
    {
        return (new Entity($this->pipedrive, $this))->all();
    }

    public function findOne($condition, $exactMatch = true)
    {
    }

    public function findAll($condition, $exactMatch = true)
    {
    }

    public function create($entity)
    {
        return (new Entity())->create($entity);
    }

    public function update($entity)
    {
        return (new Entity())->update($entity);
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
     * @return null|EntityFilter
     */
    public function getPrev()
    {
        return $this->prev;
    }

    /**
     * @param null|EntityFilter $parent
     * @return $this
     */
    public function setPrev($prev)
    {
        $this->prev = $prev;

        return $this;
    }

    /**
     * @return EntityFilter
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @param EntityFilter $next
     * @return $this
     */
    public function setNext($next)
    {
        $this->next = $next;

        return $this;
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

    /**
     * @return EntityFilter
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
        } else if ($this->getType() && $this->getCondition()) {
            return true;
        }

        return false;
    }
}