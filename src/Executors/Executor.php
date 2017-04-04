<?php

namespace Zakirullin\Pipedrive\Executors;

use Zakirullin\Pipedrive\Query;
use Zakirullin\Pipedrive\Pipedrive;

abstract class Executor
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Pipedrive
     */
    protected $pipedrive;

    /**
     * @param Query $query
     */
    public function __construct($query)
    {
        $this->setQuery($query);
        $this->setPipedrive($query->getPipedrive());

        return $this;
    }

    /**
     * @param Query $query
     * @return static
     */
    public static function factory($query)
    {
        $condition = $query->getCondition();
        if ($query->getPrev()) {
            return new ChainExecutor($query);
        } else if ($condition) {
            $hasId = is_numeric($condition) || isset($condition['id']);
            if ($hasId && $query->getNext()) {
                return new GetChildsExecutor($query);
            } else {
                return new FindExecutor($query);
            }
        }

        return new GetExecutor($query);
    }

    /**
     * @return array
     */
    public function execute()
    {
        $query = $this->getTargetQuery();
        $query->setEntities($this->fetch());
        $query->filter();

        return $this->next($query);
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param Query $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

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
    protected abstract function fetch();

    /**
     * @return Query|null
     */
    protected function getTargetQuery()
    {
        return $this->getQuery();
    }

    /**
     * @param Query $query
     * @return array
     */
    protected function next($query)
    {
        if ($next = $query->getNext()) {
            return static::factory($next)->execute();
        } else {
            return $query->getEntities();
        }
    }
}