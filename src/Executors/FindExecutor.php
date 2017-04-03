<?php

namespace Zakirullin\Pipedrive\Executors;

class FindExecutor extends Executor
{
    public function execute()
    {
        $pipedriveQuery = $this->getPipedriveQuery();
        $condition = $pipedriveQuery->getCondition();
        $field = array_keys($condition)[0];
        $term = array_shift($condition);
        $pipedriveQuery->setCondition($condition);

        $entities = $pipedriveQuery->getPipedrive()->find($pipedriveQuery->getEntityType(), $field, $term)->getEntities();
        $pipedriveQuery->setEntities($entities);

        $pipedriveQuery->filter();

        return  $this->next();
    }
}