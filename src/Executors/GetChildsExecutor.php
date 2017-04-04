<?php

namespace Zakirullin\Pipedrive\Executors;

class GetChildsExecutor extends Executor
{
    protected function fetch()
    {
        $parentType = $this->getQuery()->getType();
        $id = $this->getQuery()->getConditionId();
        $childType = $this->getQuery()->getNext()->getType();

        return $this->getPipedrive()->getChilds($parentType, $id, $childType)->getEntities();
    }

    protected function getRootQuery()
    {
        return $this->getQuery()->getNext();
    }
}