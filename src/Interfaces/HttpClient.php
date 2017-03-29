<?php

namespace Zakirullin\Pipedrive\Interfaces;

interface HttpClient
{
    /**
     * @param string $url
     * @param string $method
     * @param array|string $data
     * @return mixed
     */
    public function json($url, $method, $data);
}