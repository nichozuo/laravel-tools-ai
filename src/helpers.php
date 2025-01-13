<?php


if (!function_exists('ee')) {
    /**
     * @throws Exception
     */
    function ee(string $message = "", ?int $code = 500): void
    {
        abort($code, $message);
    }
}