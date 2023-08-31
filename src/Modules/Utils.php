<?php

declare(strict_types=1);

namespace PRISM\Modules;

define('RAND_ASCII', 1);
define('RAND_ALPHA', 2);
define('RAND_NUMERIC', 4);
define('RAND_HEX', 8);
define('RAND_BINARY', 16);

class Utils
{
    public const ANSI_FGCOLOR = array(
        "black"         => "0;30",
        "dark_gray"     => "1;30",
        "red"           => "0;31",
        "light_red"     => "1;31",
        "green"         => "0;32",
        "light_green"   => "1;32",
        "brown"         => "0;33",
        "yellow"        => "1;33",
        "blue"          => "0;34",
        "light_blue"    => "1;34",
        "purple"        => "0;35",
        "light_purple"  => "1;35",
        "cyan"          => "0;36",
        "light_cyan"    => "1;36",
        "light_gray"    => "0;37",
        "white"         => "1;37"
        );

    public const ANSI_BGCOLOR = array(
        "black"         => "40",
        "red"           => "41",
        "green"         => "42",
        "yellow"        => "43",
        "blue"          => "44",
        "magenta"       => "45",
        "cyan"          => "46",
        "light_gray"    => "47"
        );

    /**
     * Write colored output to the console.
     *
     * This function requires a terminal emulator which supports ANSI escape sequences.
     * Most term emulators you can find today will have this support.
     *
     * However, I will leave the legacy recommendations here.
     *
     * Windows: You need to download ansicon first; http://adoxa.altervista.org/ansicon/
     * first run ansicon -l on the command line.. Then the dosbox console switches to ANSI supported mode.
     *
     * Linux: Use an ANSI supported terminal like for example SecureCRT
     *
     * Example Usage:
     *
     * ```
     * console("this is my exampletext", true, "light_green", "black");
     * ```
     *
     * @param string $line The line to output to the console.
     * @param boolean $EOL
     * @param key-of<Utils::ANSI_FGCOLOR> $fgcolor
     * @param key-of<Utils::ANSI_BGCOLOR> $bgcolor
     * @return void
     */
    public static function console(string $line, $EOL = true, string $fgcolor = "light_gray", string $bgcolor = "black")
    {

        echo "\033[" . Utils::ANSI_FGCOLOR[$fgcolor] . "m\033[" . Utils::ANSI_BGCOLOR[$bgcolor] . "m" . $line . "\033[0m" . (($EOL) ? PHP_EOL : '');
    }

    /**
     * Gathers up a list of file paths inside a given directory.
     *
     * The only actual use-case I've found is to gather a list of plugin files.
     *
     * @param string $path Directory path
     * @param boolean $recursive
     * @param string $ext Optional. File extension. Defaults to `NULL`.
     *
     * TODO: Doesn't seem like it should be able to return "just a string" but PHPStan seems convinced.
     *
     * @return mixed # string[]|boolean|null Returns a list of file paths. If there's an error, returnse `FALSE`.
     */
    public static function get_dir_structure(string $path, bool $recursive = true, string $ext = null): mixed
    {
        $return = null;
        if (!is_dir($path)) {
            trigger_error('$path is not a directory!', E_USER_WARNING);
            return false;
        }
        if ($handle = opendir($path)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($path . $item)) {
                        if ($recursive) {
                            $return[$item] = Utils::get_dir_structure($path . $item . '/', $recursive, $ext);
                        } else {
                            $return[$item] = array();
                        }
                    } else {
                        if ($ext != null && strrpos($item, $ext) !== false) {
                            $return[] = $item;
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $return;
    }

    // check if path1 is part of path2 (ie. if path1 is a base path of path2)
    /**
     * Tests if one directory is inside another.
     *
     * @param string $path1 Base Path
     * @param string $path2 Sub-Path
     * @return boolean
     */
    public static function isDirInDir(string $path1, string $path2): bool
    {
        $p1 = explode('/', $path1);
        $p2 = explode('/', $path2);

        foreach ($p1 as $index => $part) {
            if ($part === '') {
                continue;
            }
            if (!isset($p2[$index]) || $part != $p2[$index]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Find the location of the PHP executable.
     *
     * Originally used some logic that scanned directories, and had some differences
     * between Windows, Linux, BSD. Now Uses the PHP_BINARY constant. As long as we're
     * not running the code as a module this value will exist.
     *
     * Side-effect behavior has been preserved. If you are on windows, this function
     * will write a message to the console.
     *
     * @param boolean $windows Whether the current operating system is Windows. Could be determined by `php_uname('s')`.
     * @return string
     */
    public static function findPHPLocation(bool $windows = false)
    {
        $phpLocation = '';

        if ($windows) {
            Utils::console('Trying to find the location of php.exe');
            $phpLocation = PHP_BINARY;
        } else {
            $phpLocation = PHP_BINARY;
        }

        return $phpLocation;
    }

    /**
     * Validates whether a PHP file can be parsed.
     *
     * TODO: This needs to be removed as soon as possible.
     * Or if it absolutely needs to exist, then it needs to work on ASTs.
     * Executing random code to check if it can be parsed is not acceptable.
     *
     * @param string $file Name of a file to validate.
     * @return mixed[]
     */
    public static function validatePHPFile(string $file): array
    {
        // Validate script
        $fileContents = file_get_contents($file);

        if ($fileContents) {
            if (!eval('return true;'.preg_replace(array('/^<\?(php)?/', '/\?>$/'), '', $fileContents))) {
                return array(false, array('Errors parsing '.$file));
            }
        }

        return array(true, array());
    }

    /**
     * Converts and ASCII representation of flags into an Integer representation.
     *
     * @param string $flagsString
     * @return int|false -- Return false or bitwise
     */
    public static function flagsToInteger(string $flagsString = ''): int|false
    {
        # We don't have anything to parse.
        if ($flagsString == '') {
            return false;
        }

        $flagsBitwise = 0;
        for ($chrPointer = 0, $strLen = strlen($flagsString); $chrPointer < $strLen; ++$chrPointer) {
            # Convert this charater to it's ASCII int value.
            $char = ord($flagsString[$chrPointer]);

            # We only want a (ASCII = 97) through z (ASCII 122), nothing else.
            if ($char < 97 || $char > 122) {
                continue;
            }

            # Check we have already set that flag, if so skip it!
            if ($flagsBitwise & (1 << ($char - 97))) {
                continue;
            }

            # Add the value to our $flagBitwise intager.
            $flagsBitwise += (1 << ($char - 97));
        }
        return $flagsBitwise;
    }

    /**
     * Convert Bitwise OR'd flags into ASCII string.
     *
     * @param integer $flagsBitwise
     * @return string
     */
    public static function flagsToString($flagsBitwise = 0): string
    {
        $flagsString = '';
        if ($flagsBitwise == 0) {
            return $flagsString;
        }

        # This makes sure we only handle the flags we know by unsetting any unknown bits.
        $flagsBitwise = $flagsBitwise & ADMIN_ALL;

        # Converts bits to the char forms.
        for ($i = 0; $i < 26; ++$i) {
            $flagsString .= ($flagsBitwise & (1 << $i)) ? chr($i + 97) : null;
        }

        return $flagsString;
    }

    /**
     * Generates a random string of a specified length and charset.
     *
     * @param integer $len Length of string to create.
     * @param integer $type Bitwise OR of charsets.
     * @return string A freshly generated random string.
     */
    public static function createRandomString(int $len, int $type = RAND_ASCII): string
    {
        $out = '';
        for ($a = 0; $a < $len; $a++) {
            if ($type & RAND_ALPHA) {
                $out .= rand(0, 1) ? chr(rand(65, 90)) : chr(rand(97, 122));
            } elseif ($type & RAND_NUMERIC) {
                $out .= chr(rand(48, 57));
            } elseif ($type & RAND_HEX) {
                $out .= sprintf('%02x', rand(0, 255));
            } elseif ($type & RAND_BINARY) {
                $out .= chr(rand(0, 255));
            } else {
                $out .= chr(rand(32, 127));
            }
        }
        return $out;
    }

    /**
     * Undocumented function
     *
     * @param string $string
     * @param non-empty-string $delimiter
     * @return string
     */
    public static function ucwordsByChar(string $string, string $delimiter): string
    {
        $out = '';
        foreach (explode($delimiter, $string) as $k => $v) {
            if ($k > 0) {
                $out .= $delimiter;
            }
            $out .= ucfirst($v);
        }
        return $out;
    }

    /**
     * Undocumented function
     *
     * @param string $ip
     * @return string|false
     */
    public static function getIP(&$ip): string|FALSE
    {
        if (Utils::verifyIP($ip)) {
            return $ip;
        } else {
            $tmp_ip = @gethostbyname($ip);
            if (Utils::verifyIP($tmp_ip)) {
                return $tmp_ip;
            }
        }

        return false;
    }

    /**
     * Undocumented function
     *
     * @param string $ip
     * @return mixed
     */
    public static function verifyIP(&$ip): mixed
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * Undocumented function
     *
     * @param integer $int Lap time or Split Time in milliseconds.
     * @param integer $fraction
     * @return string
     */
    public static function timeToString(int $int, int $fraction = 1000): string
    {
        $seconds = floor($int / $fraction);
        $fractions = $int - floor($seconds * $fraction);
        $seconds -= ($hours = floor($seconds / 3600)) * 3600;
        $seconds -= ($minutes = floor($seconds / 60)) * 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d.%0'.(strlen((string) $fraction) - 1).'d', $hours, $minutes, $seconds, $fractions);
        } else {
            return sprintf('%d:%02d.%0'.(strlen((string) $fraction) - 1).'d', $minutes, $seconds, $fractions);
        }
    }

    /**
     * Undocumented function
     *
     * @param integer $time Lap time or Split Time in milliseconds.
     * @param integer $fraction
     *
     * @return array<string>|string|null The same return type as `preg_replace`.
     */
    public static function timeToStr(int $time, $fraction = 1000): array|string|null
    {
        return preg_replace('/^(0+:)+/', '', Utils::timeToString($time, $fraction));
    }

    /**
     * Generates a function which compares the specified property between two objects.
     *
     * This is intended to be supplied to a sorting function.
     *
     * @param string $key This is most likely an array key.
     * @return callable(mixed, mixed): integer
     */
    public static function sortByKey(string $key): callable
    {
        return function (mixed $left, mixed $right) use ($key): int {
            if ($left[$key] == $right[$key]) {
                return 0;
            } else {
                return ($left[$key] < $right[$key]) ? -1 : 1;
            }
        };
    }

    /**
     * Generates a function which compares the specified property between two objects.
     *
     * This is intended to be supplied to a sorting function.
     *
     * @param string $property
     * @return callable(mixed, mixed): integer
     */
    public static function sortByProperty($property): callable
    {
        return function ($left, $right) use ($property) {
            if ($left->$property == $right->$property) {
                return 0;
            } else {
                return ($left->$property < $right->$property) ? -1 : 1;
            }
        };
    }

    /**
     * Undocumented function
     *
     * This seems like an edge case as typically you call `new Msg2Lfs();`
     * but if it does something special then I need to preserve the behavior
     * and ignore anny errors.
     *
     * @return \PRISM\Modules\Msg2Lfs
     */
    public static function Msg2Lfs()
    {
        return new Msg2Lfs();
    }
}
