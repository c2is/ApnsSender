<?php

namespace C2iS\ApnsSender\Utils;

/**
 * Class Sleep
 *
 * @package C2iS\ApnsSender\Utils
 */
class Sleep
{
    /**
     * Sleeps for the given amount of milliseconds
     *
     * @param $ms
     */
    public static function millisecond($ms)
    {
        if (($sec = (int)($ms / 1000)) > 0) {
            $ms = $ms % 1000;
        }

        time_nanosleep($sec, $ms * 1000000);
    }
}
