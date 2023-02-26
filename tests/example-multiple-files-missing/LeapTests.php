
<?php

use Helpers;

class Leap {
    public static function isLeapYear($year) {
        return Helpers::mod($year, 4) === 0;
    }
}