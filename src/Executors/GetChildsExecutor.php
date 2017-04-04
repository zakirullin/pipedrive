<?php

namespace Zakirullin\Pipedrive\Executors;

class GetChildsExecutor extends Executor
{
    protected function fetch()
    {
        $query = $this->getQuery();
        $entityType = $query->getEntityType();
        $condition = $query->getCondition();
        $id = isset($condition['id']) ? $condition['id'] : null;
        $childEntityType = $query->getNext()->getEntityType();

        $entities = $query->getPipedrive()->getChilds($entityType, $id, $childEntityType)->getEntities();

        return $entities;
    }

    protected function getTargetQuery()
    {
        return $this->getQuery()->getNext();
    }
}