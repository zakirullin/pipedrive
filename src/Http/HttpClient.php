<?php

namespace Zakirullin\Pipedrive\Http;

use Zakirullin\Pipedrive\Interfaces\HttpClient as HttpClientInterface;
use Zakirullin\Pipedrive\Exceptions\Exception;

class HttpClient implements HttpClientInterface
{
    public function __construct()
    {
        return $this;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $body
     * @return mixed
     * @throws \Exception
     */
    public function json($url, $method = 'GET', $body = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $method = strtoupper($method);
        if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? http_build_query($body) : $body);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $response = curl_exec($ch);
        $code = (string)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!$code || $code[0] != '2') {
            $error = curl_error($ch);
            $body = json_encode($body);

            throw new Exception("HTTP $method to $url failed with: HTTP $code $error, body: $body, response: $response");
        }

        curl_close($ch);

        return json_decode($response);
    }
}