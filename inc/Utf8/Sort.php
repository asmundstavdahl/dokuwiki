<?php

namespace dokuwiki\Utf8;

/**
 * DokuWiki sort functions
 *
 * When "intl" extension is available, all sorts are done using a collator.
 * Otherwise, primitive PHP functions are called.
 *
 * The collator is created using the locale given in $conf['lang'].
 * It always uses case insensitive "natural" ordering in its collation.
 * The fallback solution uses the primitive PHP functions that return almost the same results
 * when the input is text with only [A-Za-z0-9] characters.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Moisés Braga Ribeiro <moisesbr@gmail.com>
 */
class Sort
{
    /** @var \Collator[] language specific collators, usually only one */
    protected static $collator;

    /**
     * Initialization of a collator using $conf['lang'] as the locale.
     * The initialization is done only once, except when $reload is set to true.
     * The collation takes "natural ordering" into account, that is, "page 2" is before "page 10".
     *
     * @param bool $reload Usually false; true forces collator re-creation
     * @return \Collator Returns a configured collator or null if the collator cannot be created.
     *
     * @author Moisés Braga Ribeiro <moisesbr@gmail.com>
     */
    protected static function getCollator($reload = false)
    {
        global $conf;
        $lc = $conf['lang'];

        // check if intl extension is available
        if (!class_exists('\Collator')) {
            return null;
        }

        // load collator if not available yet
        if ($reload || !isset(self::$collator[$lc])) {
            $collator = \Collator::create($lc);
            if (!isset($collator)) return null;
            $collator->setAttribute(\Collator::NUMERIC_COLLATION, \Collator::ON);
            self::$collator[$lc] = $collator;
        }

        return self::$collator[$lc];
    }

    /**
     * Drop-in replacement for strcmp(), strcasecmp(), strnatcmp() and strnatcasecmp().
     * It uses a collator-based comparison, or strnatcasecmp() as a fallback.
     *
     * @param string $str1 The first string.
     * @param string $str2 The second string.
     * @return int Returns < 0 if $str1 is less than $str2; > 0 if $str1 is greater than $str2, and 0 if they are equal.
     *
     * @author Moisés Braga Ribeiro <moisesbr@gmail.com>
     */
    public static function strcmp($str1, $str2)
    {
        $collator = self::getCollator();
        if (isset($collator)) {
            return $collator->compare($str1, $str2);
        } else {
            return strnatcasecmp($str1, $str2);
        }
    }

    /**
     * Drop-in replacement for sort().
     * It uses a collator-based sort, or sort() with flags SORT_NATURAL and SORT_FLAG_CASE as a fallback.
     *
     * @param array $array The input array.
     * @return bool Returns true on success or false on failure.
     *
     * @author Moisés Braga Ribeiro <moisesbr@gmail.com>
     */
    public static function sort(&$array)
    {
        $collator = self::getCollator();
        if (isset($collator)) {
            return $collator->sort($array);
        } else {
            return sort($array, SORT_NATURAL | SORT_FLAG_CASE);
        }
    }

    /**
     * Drop-in replacement for ksort().
     * It uses a collator-based sort, or ksort() with flags SORT_NATURAL and SORT_FLAG_CASE as a fallback.
     *
     * @param array $array The input array.
     * @return bool Returns true on success or false on failure.
     *
     * @author Moisés Braga Ribeiro <moisesbr@gmail.com>
     */
    public static function ksort(&$array)
    {
        $collator = self::getCollator();
        if (isset($collator)) {
            return uksort($array, array($collator, 'compare'));
        } else {
            return ksort($array, SORT_NATURAL | SORT_FLAG_CASE);
        }
    }

    /**
     * Drop-in replacement for asort(), natsort() and natcasesort().
     * It uses a collator-based sort, or asort() with flags SORT_NATURAL and SORT_FLAG_CASE as a fallback.
     *
     * @param array $array The input array.
     * @return bool Returns true on success or false on failure.
     *
     * @author Moisés Braga Ribeiro <moisesbr@gmail.com>
     */
    public static function asort(&$array)
    {
        $collator = self::getCollator();
        if (isset($collator)) {
            return $collator->asort($array);
        } else {
            return asort($array, SORT_NATURAL | SORT_FLAG_CASE);
        }
    }

    /**
     * Drop-in replacement for asort(), natsort() and natcasesort() when the parameter is an array of filenames.
     * Filenames may not be equal to page names, depending on the setting in $conf['fnencode'],
     * so the correct behavior is to sort page names and reflect this sorting in the filename array.
     *
     * @param array $array The input array.
     * @return bool Returns true on success or false on failure.
     *
     * @author Moisés Braga Ribeiro <moisesbr@gmail.com>
     */
    public static function asortFN(&$array)
    {
        $collator = self::getCollator();
        return uasort($array, function ($fn1, $fn2) use ($collator) {
            if (isset($collator)) {
                return $collator->compare(utf8_decodeFN($fn1), utf8_decodeFN($fn2));
            } else {
                return strnatcasecmp(utf8_decodeFN($fn1), utf8_decodeFN($fn2));
            }
        });
    }

}