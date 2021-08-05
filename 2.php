<?php
declare(strict_types=1);

function convertString(string &$a, string $b): void {

	$a = preg_replace_callback('/'.$b.'/', function ($m) use (&$count) {
		
		$count++; return $count == 2 ? strrev($m[0]) : $m[0];
	
	}, $a, 2);	
}


function mySortForKey(array &$a, string $b): void {

	uksort($a, function($f, $s) use ($a, $b) {

		if (isset($a[$f][$b]))
		{
			if (isset($a[$s][$b]))
			{
				return $a[$f][$b]-$a[$s][$b];
			
			} throw new Exception('Array['.($s).'] не содержит индекс '.$b);			
		
		} throw new Exception('Array['.$f.'}] не содержит индекс '.$b);
	});
}


function importXml(string $a): void {
	
	if (!file_exists($a)) throw new InvalidArgumentException("<pre>Не удалось открыть файл $a.");	 
		
		$xml = simplexml_load_file($a);

		if ($xml === FALSE)	throw new Exception("<pre>Не удалось прочитать файл $a.");	 

		if ($xml->Товар->count() === 0) throw new Exception("<pre>Не найдены товары");
	
			$con = mysqli_connect('localhost', 'root', '140905', 'test_samson');

			if (mysqli_connect_errno($con))} else throw new Exception("<pre>Failed to connect:" . mysqli_connect_error());

				mysqli_set_charset($con, "utf8");

	foreach ($xml->Товар as $product) {

		$code = $product->attributes()->Код;
		$name = $product->attributes()->Название;
		
		if (isset($code, $name)){	

			$sql = "INSERT INTO `a_product`(`product_code`,`product_name`) 
					VALUES ('{$code}', '{$name}')";
			
			if (mysqli_query($con, $sql)){ 						
		
				foreach ($product->Цена as $price) {
					
					$type = $price->attributes()->Тип;
					
					if (isset($type) && !empty((string)$price)){

						$sql = "INSERT INTO `a_price`(`product_code`,`price_type`,`price`) 
								VALUES ('{$code}', '{$type}', '{$price}')";  
						
						if (!mysqli_query($con, $sql)){
							
							echo ("<pre>Не удалось добавить цену $type для товара $name. " . mysqli_error($con));
						}
						
					} else echo("<pre>Не указан тип цены или цена для товара $name.");
				}

				foreach ($product->Свойства->children() as $property) {
					
					$property_type = $property->getName();
					
					$property_unit = $property->attributes()->ЕдИзм;
					
					if (isset($property_type) && !empty((string)$property)){

						$sql = "INSERT INTO `a_property`(`product_code`,`property_type`,`property_unit`,`property_value`) 
								VALUES ('{$code}', '{$property_type}', '{$property_unit}', '{$property}')";  
						
						if (!mysqli_query($con, $sql)){
							
							echo ("<pre>Не удалось добавить свойство $property_type для товара $name." . mysqli_error($con));
						}
						
					} else echo("<pre>Не удалось добавить свойства для товара $name.");	
					
				}		

				foreach ($product->Разделы->children() as $value) {
					
					$category = strval($value);
					
					if (!empty($category)){

						$sql = "SELECT `category_id` FROM `test_samson`.`a_category` 
								WHERE category_name = '{$category}' LIMIT 1"; 	

						$id = mysqli_fetch_array( mysqli_query($con, $sql));

						if ($id){

							$sql = "INSERT INTO `a_product_category`(`product_code`,`category_id`) 
									VALUES ('{$code}', '{$id['category_id']}')";

							if (!mysqli_query($con, $sql)){
								
								echo ("<pre>Не удалось добавить раздел $category для товара $name." . mysqli_error($con));
							}
						
						} else echo ("<pre>Раздела $category не существует." . mysqli_error($con));									

					} else echo("<pre>Не удалось добавить раздел для товара $name.");										

				}	
			
			} else echo("<pre>Не удалось добавить товар $name. " . mysqli_error($con));		
		
		} else echo("<pre>Не указан код или название товара");					
	}		
}
