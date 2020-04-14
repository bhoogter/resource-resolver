<?php

function choose($n, $a, $b = "", $c = "", $d = "", $e = "")
{
    if (strval($n) == "1") return strval($a);
    if (strval($n) == "2") return strval($b);
    if (strval($n) == "3") return strval($c);
    if (strval($n) == "4") return strval($d);
    if (strval($n) == "5") return strval($e);
}

function first($a, $b = "", $c = "", $d = "", $e = "")
{
    if (strval($a) != "") return strval($a);
    if (strval($b) != "") return strval($b);
    if (strval($c) != "") return strval($c);
    if (strval($d) != "") return strval($d);
    if (strval($e) != "") return strval($e);
    return "";
}

function first_index($a, $b, $c = "", $d = "", $e = "")
{
    if (strval($a) != "") return "1";
    if (strval($b) != "") return "2";
    if (strval($c) != "") return "3";
    if (strval($d) != "") return "4";
    if (strval($e) != "") return "5";
    return "0";
}

function has_first($a, $b = "", $c = "", $d = "", $e = "")
{
    return first($a, $b, $c, $d, $e) != "";
}
