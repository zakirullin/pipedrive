<?php

namespace Zakirullin\Pipedrive\Executors;

class ChainExecutor extends Executor
{
    protected function fetch()
    {
        $query = $this->getQuery();
        $parentType = $query->getPrev()->getEntityType();
        $childType = $query->getEntityType();

        $entities = [];
        foreach ($query->getPrev()->getEntities() as $parentEntity) {
            $childEntities = $query->getPipedrive()->getChilds($parentType, $parentEntity->id, $childType)->getEntities();
            $entities += $childEntities;
        }

        return $entities;
    }
}