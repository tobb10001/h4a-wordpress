<?php

declare(strict_types=1);

namespace Tobb10001\H4aWordpress\Util;

use Tobb10001\H4aIntegration\Exceptions\HttpException;
use Tobb10001\H4aIntegration\Util\HttpClientInterface;
use WP_Error;

class WpHttpClient implements HttpClientInterface
{
    public function get(string $url): string
    {
        $response = wp_remote_get($url);
        if ($response instanceof WP_Error) {
            throw new HttpException("Could not retrieve $url due to an error: " . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);

        if ($body instanceof WP_Error) {
            throw new HttpException("COuld not retrieve $url due to an error: " . $body->get_error_message());
        }

        return $body;
    }

    /**
     */
    public function getJson(
        string $url,
        ?bool $associative = true,
        int $depth = 512,
        int $flags = 0
    ): mixed {
        return json_decode($this->get($url), $associative, $depth, $flags);
    }
}
