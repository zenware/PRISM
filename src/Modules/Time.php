<?php
/**
 * PHPInSimMod - Time Module
 * @package PRISM
 * @subpackage Time
 * @copyright the AMX Mod X Development Team & The PRISM Development Team.
*/

namespace PRISM\Modules;

class Time
{
    // Time unit types.
    public const UNIT_SECONDS        = 0;
    public const UNIT_MINUTES        = 1;
    public const UNIT_HOURS        = 2;
    public const UNIT_DAYS            = 3;
    public const UNIT_WEEKS        = 4;

    // The number of seconds that are in each time unit.
    public const SECONDS_IN_MINUTE = 60;
    public const SECONDS_IN_HOUR   = 3600;
    public const SECONDS_IN_DAY    = 86400;
    public const SECONDS_IN_WEEK   = 604800;

    /**
     * @desc: By Brad for AMX Mod X.
     * @param int $unit The number of time units you want translated into verbose text.
     * @param int $type Optional. The type of unit (i.e. seconds, minutes, hours, days, weeks) 
     *                            that you are passing in.
     *                            Defaults to `Time::UNIT_SECONDS`.
     * 
     * @return string Return
    */
    public static function getLength(int $unit, int $type = Time::UNIT_SECONDS): string
    {
        # Ensure the varables are of the correct datatype.
        $unit = (int) $unit;
        $type = (int) $type;

        # If our units happens to equal zero, skip.
        if ($unit == 0) {
            return '0 seconds';
        }

        # Determine the number of each time unit there are.
        $weeks = 0;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        # Handle the various scales of time.
        switch ($type) {
            case Time::UNIT_SECONDS: $seconds = $unit;
                // no break
            case Time::UNIT_MINUTES: $seconds = $unit * Time::SECONDS_IN_MINUTE;
                // no break
            case Time::UNIT_HOURS:   $seconds = $unit * Time::SECONDS_IN_HOUR;
                // no break
            case Time::UNIT_DAYS:    $seconds = $unit * Time::SECONDS_IN_DAY;
                // no break
            case Time::UNIT_WEEKS:   $seconds = $unit * Time::SECONDS_IN_WEEK;
        }

        # How many weeks left?
        $weeks = $seconds / Time::SECONDS_IN_WEEK;
        $seconds -= ($weeks * Time::SECONDS_IN_WEEK);

        # How many days left?
        $days = $seconds / Time::SECONDS_IN_DAY;
        $seconds -= ($days * Time::SECONDS_IN_DAY);

        # How many hours left?
        $hours = $seconds / Time::SECONDS_IN_HOUR;
        $seconds -= ($hours * Time::SECONDS_IN_HOUR);

        # How many minutes left?
        $minutes = $seconds / Time::SECONDS_IN_MINUTE;
        $seconds -= ($minutes * Time::SECONDS_IN_MINUTE);

        # Seconds are the base unit, so it's handled intrinsically

        // Translate the unit counts into verbose text
        $timeElement = array();

        if ($weeks > 0) {
            $timeElement[] = sprintf("%d %s", $weeks, ($weeks == 1) ? "week" : "weeks");
        }
        if ($days > 0) {
            $timeElement[] = sprintf("%d %s", $days, ($days == 1) ? "day" : "days");
        }
        if ($hours > 0) {
            $timeElement[] = sprintf("%d %s", $hours, ($hours == 1) ? "hour" : "hours");
        }
        if ($minutes > 0) {
            $timeElement[] = sprintf("%d %s", $minutes, ($minutes == 1) ? "minute" : "minutes");
        }
        if ($seconds > 0) {
            $timeElement[] = sprintf("%d %s", $seconds, ($seconds == 1) ? "second" : "seconds");
        }

        // Outputs are final result in the correct format.
        switch(count($timeElement)) {
            case 1: return sprintf("%s", $timeElement[0]);
            case 2: return sprintf("%s & %s", $timeElement[0], $timeElement[1]);
            case 3: return sprintf("%s, %s & %s", $timeElement[0], $timeElement[1], $timeElement[2]);
            case 4: return sprintf("%s, %s, %s & %s", $timeElement[0], $timeElement[1], $timeElement[2], $timeElement[3]);
            case 5: return sprintf("%s, %s, %s, %s & %s", $timeElement[0], $timeElement[1], $timeElement[2], $timeElement[3], $timeElement[4]);
        }
    }
}
