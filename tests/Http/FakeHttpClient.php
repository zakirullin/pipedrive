<?php

namespace Zakirullin\Pipedrive\Tests\Http;

use Zakirullin\Pipedrive\Interfaces\HttpClient;

class FakeHttpClient implements HttpClient
{
    public function json($url, $method = 'GET', $body = '')
    {

    }
}