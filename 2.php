<?php
declare(strict_types=1);

function convertString(string &$a, string $b): void {

	$a = preg_replace_callback('/'.$b.'/', function ($m) use (&$count) {$count++; return $count == 2 ? strrev($m[0]) : $m[0];}, $a, 2);	
}