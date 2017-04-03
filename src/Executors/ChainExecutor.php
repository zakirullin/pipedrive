<?php

namespace Zakirullin\Pipedrive\Executors;

class ChainExecutor extends Executor
{
    public function execute()
    {
        $pipedriveQuery = $this->getPipedriveQuery();
        $parentType = $pipedriveQuery->getPrev()->getEntityType();
        $childType = $pipedriveQuery->getEntityType();

        $entities = [];
        foreach ($pipedriveQuery->getPrev()->getEntities() as $parentEntity) {
            $childEntities = $pipedriveQuery->getPipedrive()->$parentType->find($parentEntity->id)->$childType->all();
            $entities = array_merge($entities, $childEntities);
        }
        $pipedriveQuery->setEntities($entities);

        $pipedriveQuery->filter();

        return $this->next();
    }
}