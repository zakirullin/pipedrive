<?php

namespace Zakirullin\Pipedrive\Executors;

class GetExecutor extends Executor
{
    protected function fetch()
    {
        $query = $this->getQuery();
        $entityType = $query->getEntityType();
        $condition = $query->getCondition();
        $id = isset($condition['id']) ? $condition['id'] : null;

        return $query->getPipedrive()->get($entityType, $id)->getEntities();
    }
}