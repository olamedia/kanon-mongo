<?php

namespace ru\olamedia\kanon\mongo;

class typeManager{
  private static $_registered = [];
	private static $_types = [];
	private static $_defaults = [];
	private static function stripNamespace($className){
		$path = \explode('\\', $className);
		return \end($path);
	}
	private static function getModelTypes($className){
		$class = new \ReflectionClass($className);
		$types = [self::stripNamespace($className)];
		while ($parent = $class->getParentClass()) {
			$types[] = self::stripNamespace($parent->getName());
			$class = $parent;
		}
		return $types;

	}
	public static function registerModel($className){
		//static::$_properties
		
	}
	private static function _registerModel($className){
		if (!isset(self::$_registered[$className])){
			self::$_registered[$className] = true;
			$class = new \ReflectionClass($className);
			$prop = $class->getProperty('_properties');
			$prop->setAccessible(true);
			$props = $prop->getValue();
			$prop->setAccessible(false);
			self::setDefault($className, '_type', self::getModelTypes($className));
			self::setAll($className, $props);
		}
	}
	public static function has($className){
		self::_registerModel($className);
		return isset(self::$_types[$className]);
	}
	public static function getAll($className){
		self::_registerModel($className);
		if (isset(self::$_types[$className])){
			return self::$_types[$className];
		}
		return null;
	}
	public static function get($className, $attributeName){
		self::_registerModel($className);
		if (isset(self::$_types[$className]) && isset(self::$_types[$className][$attributeName])){
			return self::$_types[$className][$attributeName];
		}
		return null;
	}
	public static function setAll($className, $types){
		self::_registerModel($className);
		self::$_types[$className] = $types;
		foreach ($types as $k => $type){
			if (isset($type['default'])){
				self::$_defaults[$className][$k] = $type['default'];
			}
		}
	}
	public static function set($className, $attributeName, $type){
		self::_registerModel($className);
		self::$_types[$className][$attributeName] = $type;
	}
	public static function setDefault($className, $attributeName, $default){
		self::_registerModel($className);
		self::$_defaults[$className][$attributeName] = $default;
	}
	public static function getDefaults($className){
		self::_registerModel($className);
		return self::$_defaults[$className];
	}
}
