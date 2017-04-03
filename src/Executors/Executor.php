<?php

namespace Zakirullin\Pipedrive\Executors;

use Zakirullin\Pipedrive\PipedriveQuery;

abstract class Executor
{
    /**
     * @var PipedriveQuery
     */
    protected $pipedriveQuery;

    /**
     * @return array
     */
    public abstract function execute();

    /**
     * @param PipedriveQuery $pipedriveQuery
     */
    public function __construct($pipedriveQuery)
    {
        $this->pipedriveQuery = $pipedriveQuery;

        return $this;
    }

    /**
     * @param PipedriveQuery $pipedriveQuery
     * @return static
     */
    public static function factory($pipedriveQuery)
    {
        $condition = $pipedriveQuery->getCondition();
        if ($pipedriveQuery->getPrev()) {
            return new ChainExecutor($pipedriveQuery);
        } else if ($condition) {
            $hasId = is_numeric($condition) || isset($condition['id']);
            if ($hasId && $pipedriveQuery->getNext()) {
                return new GetChildsExecutor($pipedriveQuery);
            } else {
                return new FindExecutor($pipedriveQuery);
            }
        }

        return new GetExecutor($pipedriveQuery);
    }

    /**
     * @return PipedriveQuery
     */
    public function getPipedriveQuery()
    {
        return $this->pipedriveQuery;
    }

    /**
     * @param PipedriveQuery $pipedriveQuery
     * @return $this
     */
    public function setPipedriveQuery($pipedriveQuery)
    {
        $this->pipedriveQuery = $pipedriveQuery;

        return $this;
    }

    /**
     * @return array
     */
    public function next()
    {
        if ($next = $this->getPipedriveQuery()->getNext()) {
            return static::factory($next)->execute();
        } else {
            return $this->getPipedriveQuery()->getEntities();
        }
    }
}