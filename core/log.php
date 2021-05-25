<?php

class log
{
    function __construct(array $data)
    {
        if (!file_exists(__DIR__ . '/../log/')) {
            mkdir(__DIR__ . '/../log/', 0777, true);
        }
        file_put_contents(
            __DIR__ . '/../log/log_' . date("Y-m-d") . '.txt',
            "\r\n\r\n### " . date("Y-m-d H:i:s") . " ###\r\n\r\n" . var_export($data, true),
            FILE_APPEND
        );
    }
}