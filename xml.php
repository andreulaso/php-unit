<?php
declare(strict_types=1);

function importXml(string $a): void {
	
	if (!file_exists($a)) throw new InvalidArgumentException("<pre>Не удалось открыть файл $a.");	 
		
	$xml = simplexml_load_file($a);

	if ($xml === FALSE)	throw new Exception("Не удалось прочитать файл $a.");	 

	if ($xml->Товар->count() === 0) throw new Exception("Не найдены товары");
	
	//подключение к базе данных
	$mysqli = new mysqli('localhost', 'root', '140905', 'test_samson');

	if ($mysqli->connect_errno) throw new Exception("Failed to connect:" . $mysqli->connect_error);

	$mysqli->set_charset("utf8");

	$ins_name = $mysqli->prepare("INSERT INTO `a_product`(`product_code`,`product_name`) VALUES (?, ?)");
	
	foreach ($xml->Товар as $product) {

		$code = $product->attributes()->Код;
		$name = $product->attributes()->Название;
		
		if (isset($code, $name)){	

			//вставка кода и названия товара
			$ins_name->bind_param("is", $code, $name);

			if ($ins_name->execute()){ 						
		
				//вставка цены и типа цены товара					
				$ins_price = $mysqli->prepare(	"INSERT INTO `a_price`(`product_code`,`price_type`,`price`) 
													VALUES (?, ?, ?)");  				
				foreach ($product->Цена as $price) {
					
					$type = $price->attributes()->Тип;
					
					if (isset($type) && !empty((string)$price)){

						$ins_price->bind_param("isd", $code, $type, $price);
						
						if (!$ins_price->execute()){
							
							echo ("<pre>Не удалось добавить цену $type для товара $name. " . $mysqli->error);
						}
						
					} else echo("<pre>Не указан тип цены или цена для товара $name.");
				}
				//вставка свойств товара	
				$ins_prop = $mysqli->prepare("INSERT INTO `a_property`(`product_code`,`property_type`,`property_unit`,`property_value`) 
												VALUES (?, ?, ?, ?)"); 				
				
				foreach ($product->Свойства->children() as $property) {
					
					$property_type = $property->getName();
					
					$property_unit = $property->attributes()->ЕдИзм;
					
					if (isset($property_type) && !empty((string)$property)){

						$ins_prop->bind_param("isss", $code, $property_type, $property_unit, $property);
						
						if (!$ins_prop->execute()){
							
							echo ("<pre>Не удалось добавить свойство $property_type для товара $name." . $mysqli->error);
						}
						
					} else echo("<pre>Не удалось добавить свойства для товара $name.");	
					
				}		
				
				//вставка разделов товара	
				$ins_category = $mysqli->prepare("INSERT INTO `a_product_category`(`product_code`,`category_id`) VALUES (?, ?)"); 					

				foreach ($product->Разделы->children() as $value) {
					
					$category = strval($value);
					
					if (!empty($category)){

						//выборка id раздела
						$ins_id = $mysqli->prepare("SELECT `category_id` FROM `a_category` WHERE category_name = ? LIMIT 1"); 
						
						$ins_id->bind_param("s", $category); 

						if ($ins_id->execute()){	
							
							if ($result = $ins_id->get_result()){

								if ($id = $result->fetch_assoc()){

									$ins_category->bind_param("ii", $code, $id['category_id']); 

									if (!$ins_category->execute()){
										
										echo ("<pre>Не удалось добавить раздел $category для товара $name." . $mysqli->error);
									}
							
								} else echo ("<pre>Раздел $category не найден. " . $mysqli->error);	
							
							} else echo ("Не удалось выполнить запрос для раздела $category. " . $mysqli->error);
						
						} else echo ("Не удалось выполнить запрос для раздела $category. " . $mysqli->error);
						
					} else echo("<pre>Не удалось добавить раздел для товара $name.");										

				}	
			
			} else echo("<pre>Не удалось добавить товар $name. " . $mysqli->error);		
		
		} else echo("<pre>Не указан код или название товара");					
	}	
	
	echo("<pre>Импорт файла завершен");		
}


function exportXml(string $a, string $b): void {
	
	if (empty($a) || empty($b)) throw new InvalidArgumentException("One of the arguments is empty");
	
	//подключение к базе данных
	
	$con = mysqli_connect('localhost', 'root', '140905', 'test_samson');

	if (mysqli_connect_errno($con)) throw new Exception("Failed to connect:" . mysqli_connect_error());

	mysqli_set_charset($con, "utf8");
	
	//выборка id раздела
	
	$sql = "SELECT `category_id` FROM `a_category` WHERE category_name = '{$b}' LIMIT 1"; 	

	$result = mysqli_query($con, $sql);

	if ($result){
		
		$id = mysqli_fetch_array($result);
		
		if ($id){	
		
			//выборка всех потомков раздела
			
			$sql = "SELECT * FROM `a_category` c 
					JOIN `a_tree` t ON (c.category_id = t.descendant) 
					WHERE t.ancestor = '{$id['category_id']}';";
	
			$categories = mysqli_query($con, $sql);
			
			$products = $category_names = [];
			
			//выборка всех товаров входящих в разделы			
			
			while ($row = $categories->fetch_assoc()) {
				
				$sql = "SELECT `product_code` FROM `a_product_category` 
						WHERE category_id = '{$row['category_id']}'"; 				

				$product_code = mysqli_query($con, $sql);

				//добавление названий разделов в массив
				$category_names += [$row['category_id']=>$row['category_name']];
				
				//добавление товаров в массив
				if ($product_code) $products = array_merge($products, mysqli_fetch_all($product_code)); 
			}

			$products = array_unique($products, SORT_REGULAR);
			
			if (count($products) > 0){

				$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\" ?><Товары></Товары>");
				
				// добавление товаров в xml
				
				foreach ($products as $product_code) {
					
					$product = $xml->addChild('Товар');					
					$product->addAttribute('Код', $product_code[0]);	

					$childs	=	['Название'=>'`product_name` FROM `a_product`',
								'Цена'=>'`price_type`,`price` FROM `a_price`',
								'Свойства'=>'`property_type`,`property_unit`,`property_value` FROM `a_property`',
								'Разделы'=>'`category_id` FROM `a_product_category`'
								];

					foreach ($childs as $name => $sql) {

						$result = mysqli_query($con, "SELECT ".$sql." WHERE product_code = '{$product_code[0]}'");	
						
						if ($result){	
							
							if ($name == 'Свойства' || $name == 'Разделы') $property = $product->addChild($name);	

							while ($row = $result->fetch_assoc()) {
							
								if ($name == 'Название'){
									
									$product->addAttribute($name, $row['product_name']);
								
								} else if ($name == 'Цена'){
									
									$child = $product->addChild($name, $row['price']);
									
									$child->addAttribute('Тип', $row['price_type']);
								
								} else if ($name == 'Свойства'){
									
									$child = $property->addChild($row['property_type'], $row['property_value']);	
									
									if (!empty($row['property_unit'])){
										
										$child->addAttribute('ЕдИзм', $row['property_unit']);	
									}									
								
								} else if ($name == 'Разделы'){
									
									$property->addChild('Раздел', $category_names[$row['category_id']]);	
								}
							}
						}							
					}
				}
				$dom = dom_import_simplexml($xml)->ownerDocument;				
				$dom->formatOutput = true;				

				if ($dom->save($a)){
					
					echo("<pre>Файл $a успешно создан. {$dom->saveXML()}");
				
				} else echo("<pre>Не удалось создать файл $a.");

			} else echo ("<pre>Не найдено ни одного товара.");
			
		} else echo ("<pre>Раздел $b не найден. " . mysqli_error($con));
	
	} else throw new Exception("Не удалось выполнить запрос. " . mysqli_error($con));			
}



//importXml('import.xml');

//exportXml('export.xml', 'Расходные материалы');

/*РАЗДЕЛЫ
	(1, 'Компьютеры и офисная техника'),
		(2, 'Компьютеры и комплектующие'),
			(9, 'Комплектующие для ПК'),
			(10, 'Системные блоки'),	
			(11, 'Хранение данных и охлаждение');		
		(3, 'Офисная техника и расходные материалы'),		
			(4, 'Принтеры и МФУ'),
				(5, 'Принтеры'),
				(6, 'МФУ'),				
			(7, 'Расходные материалы'),		
				(8, 'Бумага'),
*/
