<?php

namespace ru\olamedia\kanon\mongo;

class collection{
	private static $_instances = [];
	private static $_names = [];
	private $_connection = null;
	private $_collection = null;
	private $_name = '';
	private function __construct($connection, $name){
		$this->_connection = $connection;
		$this->_name = $name;
	}
	public function getConnection(){
		return $this->_connection;
	}
	/**
	 * collection::registerModel('myModel', 'default', 'mymodels');
	 * 
	 * @param string $className
	 * @param string $connectionName
	 * @param string $collectionName
	 */
	public static function registerModel($className, $connectionName, $collectionName){
		connection::registerModel($className, $connectionName);
		self::$_names[$className] = $collectionName;
	}
	public static function forClass($className){
		if (!isset(self::$_instances[$className])){
			$collectionName = connection::getCollectionNameByClassName($className);
			if (null === $collectionName){
				// FIXME
				return null;
			}
			self::$_instances[$className] = new self(connection::forClass($className), $collectionName);
		}
		return self::$_instances[$className];
	}
	/**
	 *
	 * @return \MongoCollection
	 */
	public function select(){
		if (null === $this->_collection){
			$this->_collection = $this->getConnection()->getCollection($this->_name);
		}
		return $this->_collection;
	}
	/**
	 *
	 * @param array $criteria
	 * @param array $fields
	 * @return \ru\olamedia\kanon\mongo\result
	 */
	public function find(array $criteria = [], array $fields = []){
		$cursor = $this->select()->find($criteria, $fields);
		return new result($className, $cursor);
	}
	/**
	 *
	 * @param array $criteria
	 * @param array $fields
	 * @return \ru\olamedia\kanon\mongo\model
	 */
	public function findOne(array $criteria = [], array $fields = []){
		$result = $this->select()->findOne($criteria, $fields);
		return new $className($result);
	}
	public function remove(array $criteria, $options = array()){
		return $this->select()->remove($criteria, $options);
	}
	public function insert(array $data, $options = array()){
		// The _id is available if a wrapping function does not trigger copy-on-write
		$result = $this->select()->insert($data, $options);
	}
	public function update(array $criteria, array $data, $options = array()){
		$result = $this->select()->update($criteria, $data, $options);
		return $result;
	}
	
	
	public function createReference(\MongoId $id){
		return $this->select()->createDBRef(['_id'=>$id]);
//		return \MongoDBRef::create($this->getName(),$id,);
	}
}
