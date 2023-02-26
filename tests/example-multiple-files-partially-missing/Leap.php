<?php

use Helpers;

class Leap
{
    function isLeapYear($year)
    {
        return Helpers::mod($year, 4) === 0;
    }
}