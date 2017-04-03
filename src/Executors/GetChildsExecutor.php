<?php

namespace Zakirullin\Pipedrive\Executors;

class GetChildsExecutor extends Executor
{
    public function execute()
    {
        $pipedriveQuery = $this->getPipedriveQuery();
        $entityType = $pipedriveQuery->getEntityType();
        $condition = $pipedriveQuery->getCondition();
        $id = isset($condition['id']) ? $condition['id'] : null;
        $childEntityType = $pipedriveQuery->getNext()->getEntityType();

        $entities = $pipedriveQuery->getPipedrive()->getChilds($entityType, $id, $childEntityType)->getEntities();
        $pipedriveQuery->getNext()->setEntities($entities);

        $pipedriveQuery->getNext()->filter();

        return static::factory($pipedriveQuery->getNext())->next();
    }
}