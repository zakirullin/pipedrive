<?php

namespace Zakirullin\Pipedrive\Executors;

class QueryExecutor extends Executor
{
    protected function fetch()
    {
        $parentType = $this->getQuery()->getPrev()->getType();
        $childType = $this->getQuery()->getType();

        $entities = [];
        foreach ($this->getQuery()->getPrev()->getEntities() as $parentEntity) {
            $childEntities = $this->getPipedrive()->getChilds($parentType, $parentEntity->id, $childType)->getEntities();
            $entities += $childEntities;
        }

        return $entities;
    }
}