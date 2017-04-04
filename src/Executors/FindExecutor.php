<?php

namespace Zakirullin\Pipedrive\Executors;

class FindExecutor extends Executor
{
    protected function fetch()
    {
        $query = $this->getQuery();
        $condition = $query->getCondition();
        $field = array_keys($condition)[0];
        $term = array_shift($condition);
        $query->setCondition($condition);

        $entities = $query->getPipedrive()->find($query->getEntityType(), $field, $term)->getEntities();

        return $entities;
    }
}