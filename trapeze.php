<?php
declare(strict_types=1);

 
function findSimple(int $a, int $b): array
{
	if ($a < 0 || $b < 0) throw new InvalidArgumentException('Arguments must be positive integers');
	
	if ($b <= $a) throw new InvalidArgumentException('Argument $b must be greater than $a');

	for ($i = $a, $arr = []; $i <= $b; $i++)
	{
		$s = sqrt($i);
		
		for($j = 2; $j <= $s; $j++)
		{
			if ($i % $j < 1)
			{ 
				$s = 0;
				break;
			}
		}	
		if ($s > 0 && $i > 1) array_push($arr, $i);
	}
	return $arr;	
}


function createTrapeze(array $a): array
{	
	if (empty($a)) throw new InvalidArgumentException('The array must not be empty');		
	
	if (count($a) % 3 != 0) throw new InvalidArgumentException('The number of elements in the array must be a multiple of 3');	
	
	$arr = array_chunk($a, 3);

	foreach ($arr as &$v)
	{
		$v = array_combine(['a','b','c'], $v);
		
		if (!is_int($v['a']) || !is_int($v['b']) || !is_int($v['c']) || $v['a'] < 0 || $v['b'] < 0 || $v['c'] < 0)
		{
			throw new InvalidArgumentException('Arguments must be positive integers');
		}			
	}	
	return $arr;	
}

function squareTrapeze(array &$a): void
{
	foreach ($a as &$value) {

		$value['s'] = .5 * $value['c'] * ( $value['a'] + $value['b'] ); 
	}
}

function getSizeForLimit(array $a, int $b): array
{
	$i = 0; 

	foreach ($a as $key => $value) {

		if ($value['s'] <= $b) if ($a[$i]['s'] > $b || $value['s'] > $a[$i]['s']) $i = $key;
	}
	
	return $a[$i];
}

function getMin(array $a): int
{
	return array_reduce($a, function ($c, $i) {return $c < $i ? $c : $i;}, PHP_INT_MAX);
}

function printTrapeze(array $a): string
{
    $table = '<table border="1px" cellspacing="1px" cellpadding="20px"><tr><td>a</td><td>b</td><td>c</td><td>s</td></tr>';
	
	foreach($a as $value) {
        
		$table .= '<tr ' . ($value['s'] % 2 == 0 ? '' : 'bgcolor="#ff0"') . '>';

		foreach($value as $v) {
           
		   $table .= '<td>' . $v . '</td>';
        }
        $table .= '</tr>';
    }
	return $table .= '</table>';
}
