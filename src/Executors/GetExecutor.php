<?php

namespace Zakirullin\Pipedrive\Executors;

class GetExecutor extends Executor
{
    public function execute()
    {
        $pipedriveQuery = $this->getPipedriveQuery();
        $entityType = $pipedriveQuery->getEntityType();
        $condition = $pipedriveQuery->getCondition();
        $id = isset($condition['id']) ? $condition['id'] : null;

        $pipedriveQuery->setEntities($pipedriveQuery->getPipedrive()->get($entityType, $id)->getEntities());

        return $this->next();
    }
}