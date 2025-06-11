<?php
namespace Core;

class Request {
    public function getUri(): bool|array|int|string|null
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
}