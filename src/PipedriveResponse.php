<?php

namespace Zakirullin\Pipedrive;

class PipedriveResponse
{
    /**
     * @var Pipedrive
     */
    protected $pipedrive;

    /**
     * @var string
     */
    protected $response;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $isSuccess = false;

    const PAGINATE_STEP = 500;

    /**
     * @param string $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->isSuccess;
    }

    public function paginate(EntityQuery $entity, $callback)
    {
        $start = 0;
        do {
            $terminate = true;

            $params = ['limit' => static::WALK_STEP, 'start' => $start];
            $response = $this->sendRequest($entity, 'get', [], $params);
            $isPaginationExists = isset($response->additional_data->pagination);
            $isMoreItems = $isPaginationExists && $response->additional_data->pagination->more_items_in_collection;
            if ($response->success == 'true') {
                if ($isMoreItems) {
                    $start = $response->additional_data->pagination->next_start;
                }

                if (is_array($response->data)) {
                    foreach ($response->data as $data) {
                        $terminate = $callback($data);
                        if ($terminate) {
                            break;
                        }
                    }
                } else {
                    $terminate = $callback($response->data);
                }
            }

        } while (!$terminate && $isMoreItems);
    }
}