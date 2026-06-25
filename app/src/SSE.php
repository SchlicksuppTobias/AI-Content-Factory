<?php

namespace src;
/**
 * SSE – Server-Sent Events helper.
 * Call SSE::send() to push a JSON event to the browser.
 */
class SSE
{
    public static function init(): void
    {
        // Disable output buffering as much as possible
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no'); // nginx

        set_time_limit(0);
        ignore_user_abort(false);
    }

    /** Send a data event. */
    public static function send(array $data): void
    {
        echo 'data: ' . json_encode($data) . "\n\n";
        flush();
    }

    /** Shorthand: log a terminal line */
    public static function log(string $msg, string $cls = 'info'): void
    {
        self::send(['type' => 'log', 'msg' => $msg, 'cls' => $cls]);
    }

    public static function done(): void
    {
        self::send(['type' => 'done']);
    }

    public static function error(string $msg): void
    {
        self::send(['type' => 'error', 'msg' => $msg]);
    }
}
