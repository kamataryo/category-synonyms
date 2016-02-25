<?php
/**
 * Ginq: `LINQ to Object` inspired DSL for PHP
 * Copyright 2013, Atsushi Kanehara <akanehara@gmail.com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP Version 5.3 or later
 *
 * @author     Atsushi Kanehara <akanehara@gmail.com>
 * @copyright  Copyright 2013, Atsushi Kanehara <akanehara@gmail.com>
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @package    Ginq
 */

namespace Ginq\Core;

class Comparer
{
    /**
     * @var Comparer
     */
    static private $inst;

    /**
     * @return Comparer
     */
    static final public function getDefault() {
        if (is_null(self::$inst)) {
            self::$inst = new Comparer();
        }
        return self::$inst;
    }

    /**
     * @param mixed      $v0 - left value
     * @param mixed      $v1 - right value
     * @param mixed|null $k0 - left key
     * @param mixed|null $k1 - right key
     * @return int
     */
    public function compare($v0, $v1, $k0 = null, $k1 = null)
    {
        if (is_string($v0) && is_string($v1)) {
            return strcmp($v0, $v1);
        }
        if (is_numeric($v0) && is_numeric($v1)) {
            if ($v0 == $v1) return 0;
            return ($v0 < $v1) ? -1 : 1;
        }
        return 0;
    }
}

