<?php

namespace ru\olamedia\kanon\mongo;

class connection{
	protected static $_instances = [];
	protected static $_classInstances = [];
	protected static $_classCollectionName = [];
	private $_instanceName = '';
	private $_config = [];
	private $_client = null;
	private $_database = null;
	private function __construct($instanceName){
		$this->_instanceName = $instanceName;
	}
	public static function getInstance($instanceName = 'default'){
		if (!isset(self::$_instances[$instanceName])){
			self::$_instances[$instanceName] = new self($instanceName);
		}
		return self::$_instances[$instanceName];
	}
	// Lazy connection
	public function connect(array $config = []){
		$this->_config = $config;
		return $this;
	}
	public static function getCollectionNameByClassName($className){
		if (isset(self::$_classCollectionName[$className])){
			return self::$_classCollectionName[$className];
		}
		return null;
	}
	public static function forClass($className){
		if (!isset(self::$_classInstances[$className])){
			return null;
		}
		return self::$_classInstances[$className];
	}
	public function registerModel($className, $collectionName){
		self::$_classInstances[$className] = $this; // register own instance
		self::$_classCollectionName[$className] = $collectionName;
		typeManager::registerModel($className);
		return $this;
	}
	/**
	 * @return \MongoClient
	 * @throws \Exception
	 */
	private function _getClient(){
		if (null == $this->_client){
			$config = $this->_config['connection'];
			$config = \array_merge(array(
					'hostnames' => 'localhost:27017' 
			), $config);
			/* Add Username & Password to server string */
			if (isset($config['username']) && isset($config['password'])){
				$config['hostnames'] = $config['username'] . ':' . $config['password'] . '@' . $config['hostnames'] . '/' . $config['database'];
			}
			/* Add required 'mongodb://' prefix */
			if (\strpos($config['hostnames'], 'mongodb://') !== 0){
				$config['hostnames'] = 'mongodb://' . $config['hostnames'];
			}
			if (!\class_exists('\MongoClient')){ // PECL mongoclient >= 1.3.0
				throw new \Exception('Required PECL mongoclient >= 1.3.0');
			}
			$this->_client = new \MongoClient($config['hostnames'], [
					'connect' => false 
			]);
			try{
				$this->_client->connect();
			}catch (\MongoConnectionException $e){
				throw new \Exception('Unable to connect to MongoDB server at ' . $config['hostnames']);
			}
			if (!isset($config['database'])){
				throw new \Exception('No database specified in MongoDB Config');
			}
			$this->_database = $this->_client->selectDB($config['database']);
		}
		return $this->_client;
	}
	/**
	 * @return \MongoCollection
	 * @param string $name
	 */
	public function getCollection($name){
		return $this->getDatabase()->selectCollection($name);
	}
	/**
	 * @return \MongoDB
	 */
	private function getDatabase(){
		if (null == $this->_database){
			$this->_getClient();
		}
		return $this->_database;
	}
	/**
	 * @return boolean
	 */
	public function isConnected(){
		return null !== $this->_client && $this->_client->connected;
	}
}
