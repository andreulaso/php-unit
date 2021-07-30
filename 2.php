<?php
declare(strict_types=1);

function convertString(string &$a, string $b): void {

	$a = preg_replace_callback('/'.$b.'/', function ($m) use (&$count) {
		
		$count++; return $count == 2 ? strrev($m[0]) : $m[0];
	
	}, $a, 2);	
}


function mySortForKey(array &$a, string $b): void {

	uksort($a, function($f, $s) use ($b, $a) {

		if (isset($a[$f][$b]))
		{
			if (isset($a[$s][$b]))
			{
				return $a[$f][$b]-$a[$s][$b];
			
			} throw new Exception('Array['.($s).'] не содержит индекс '.$b);			
		
		} throw new Exception('Array['.$f.'}] не содержит индекс '.$b);
   });
}
