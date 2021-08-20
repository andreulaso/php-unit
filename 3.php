<?php
namespace Test3;

class newBase
{
	// static private $count = 0;
	private static $count = 0;	
	//static private $arSetName = [];
	private static $arSetName = [];	
	/**
	* @param string $name
	*/
	function __construct(string $name = '0')		 
	//function __construct(int $name = 0)	
	{
		if (empty($name)) {
			//while (array_search(self::$count, self::$arSetName) != false) {
			while (array_search(self::$count, self::$arSetName) !== false) {
				++self::$count;
			}
			$name = self::$count;
		}
		$this->name = $name;
		self::$arSetName[] = $this->name;
	}
	//private $name;
	protected $name;	
	/**
	* @return string
	*/
	public function getName(): string
	{
		return '*' . $this->name  . '*';
	}
	protected $value;
	/**
	* @param mixed $value
	*/
	//public function setValue($value)
	public function setValue($value): newBase	
	{
		$this->value = $value;
		//
		return $this;
	}
	/**
	* @return string
	*/
	//public function getSize()
	public function getSize(): string	
	{
		$size = strlen(serialize($this->value));
		//return strlen($size) + $size;
		return $size;
	}
	public function __sleep()	
	{
		//return ['value'];
		return ['name', 'value'];		
	}
	/**
	* @return string
	*/
	public function getSave(): string
	{
		//$value = serialize($value);
		$value = serialize($this->value);	
		//return $this->name . ':' . sizeof($value) . ':' . $value;
		return $this->name . ':' . self::getSize()  . ':' . $value;		
	}
	/**
	* @return newBase
	*/
	//static public function load(string $value): newBase
	public static function load(string $value): newBase	
	{
		$arValue = explode(':', $value);
		return (new newBase($arValue[0]))
			->setValue(unserialize(substr($value, strlen($arValue[0]) + 1
				//+ strlen($arValue[1]) + 1), $arValue[1]));
				+ strlen($arValue[1]) + 1, $arValue[1])));					
	}
}

class newView extends newBase
{
	private $type = null;
	private $size = 0;
	private $property = null;
	/**
	* @param mixed $value
	*/
	//public function setValue($value)
	public function setValue($value): newView
	{
		parent::setValue($value);
		$this->setType();
		$this->setSize();
		//
		return $this;
	}
	//public function setProperty($value)
	public function setProperty($value): newView
	{
		$this->property = $value;
		return $this;
	}
	//private function setType()
	private function setType(): void
	{
		$this->type = gettype($this->value);
	}
	//private function setSize()
	private function setSize(): void
	{
		//if (is_subclass_of($this->value, "Test3\newView")) {
		if (is_subclass_of($this->value, 'Test3\newBase')) {			
			$this->size = parent::getSize() + 1 + strlen($this->property);
		} elseif ($this->type == 'test') {
			$this->size = parent::getSize();
		} else {
			$this->size = strlen($this->value);
		}
	}
	//
	public function __sleep()
	{
		//return ['property'];
		return ['property', 'value', 'type', 'name', 'size'];		
	}
	/**
	* @return string
	*/
	public function getName(): string
	{
		if (empty($this->name)) {
			//throw new Exception('The object doesn\'t have name');           
			throw new \Exception('The object doesn\'t have name');
		}
		return '"' . $this->name  . '": ';
	}
	/**
	* @return string
	*/
	public function getType(): string
	{
		return ' type ' . $this->type  . ';';	
	}
	/**
	* @return string
	*/
	public function getSize(): string
	{
		return ' size ' . $this->size . ';';
	}
	//public function getInfo()
	public function getInfo(): void
	{
		try {
			echo $this->getName()
			. $this->getType()
			. $this->getSize()
			. "\r\n";
		//} catch (Exception $exc) {				
		} catch (\Exception $exc) {
			echo 'Error: ' . $exc->getMessage();
		}
	}
	/**
	* @return string
	*/
	public function getSave(): string
	{
		//if ($this->type == 'test') {
		//$this->value = $this->value->getSave();
		//}
		//return parent::getSave() . serialize($this->property);		
		return parent::getSave() . ':' . serialize($this->property);
	}
	/**
	* @return newView
	*/
	//static public function load(string $value): newBase
	public static function load(string $value): newView	
	{
		$arValue = explode(':', $value);
		return (new newView($arValue[0]))	
			->setValue(unserialize(substr($value, strlen($arValue[0]) + 1
				//+ strlen($arValue[1]) + 1), $arValue[1]))
				+ strlen($arValue[1]) + 1, $arValue[1])))				
			->setProperty(unserialize(substr($value, strlen($arValue[0]) + 1
				//+ strlen($arValue[1]) + 1 + $arValue[1])))
				+ strlen($arValue[1]) + 1 + $arValue[1] + 1)))				
			;			   
	}
}

function gettype($value): string
{
	if (is_object($value)) {
		$type = get_class($value);
		do {
			//if (strpos($type, "Test3\newBase") !== false) {			
			if (strpos($type, 'Test3\newBase') !== false) {
				return 'test';
			}
		//} while ($type = get_parent_class($type));
		} while ($type == get_parent_class($value));		
	}
	//return gettype($value);  
	return \gettype($value);
}

$obj = new newBase('12345');
$obj->setValue('text');

//$obj2 = new \Test3\newView('O9876');
$obj2 = new newView('09876');
$obj2->setValue($obj);
$obj2->setProperty('field');
$obj2->getInfo();

$save = $obj2->getSave();

$obj3 = newView::load($save);

var_dump($obj2->getSave() == $obj3->getSave());

$obj4 = new newView('44444');
$obj4->setValue($obj2);
$obj4->setProperty('field');

$save2 = $obj4->getSave();

$obj5 = newView::load($save2);

var_dump($obj4->getSave() == $obj5->getSave());

var_dump($obj4);

var_dump($obj5);
