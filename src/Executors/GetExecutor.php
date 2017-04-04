<?php

namespace Zakirullin\Pipedrive\Executors;

class GetExecutor extends Executor
{
    protected function fetch()
    {
        $type = $this->getQuery()->getType();
        $id = $this->getQuery()->getConditionId();

        return $this->getPipedrive()->get($type, $id)->getEntities();
    }
}