<?php

namespace ru\olamedia\kanon\mongo;

class model{
	// Ready for database data array (original)
	protected $_data = [];
	// Ready for database data array (modified)
	protected $_dirty = [];
	protected $_references = [];
	public function __construct(array $data = []){
		$defaults = typeManager::getDefaults(\get_called_class());
		if (\is_array($defaults)){
			$data = \array_replace($defaults, $data);
		}
		$exists = isset($data['_id']) && (null !== $data['_id']);
		if ($exists){
			$this->_data = $exists?$data:[];
			$this->_dirty = $exists?[]:$data;
		}else{
			foreach ($data as $k => $v){
				$this->$k = $v;
			}
		}
	}
	// @final
	public function exists(){
		return isset($this->_data['_id']) && (null !== $this->_data['_id']);
	}
	public function update($data = []){
		foreach ($data as $k => $v){
			$this->$k = $v;
		}
	}
	private static function _getPropertyType($name){
		return typeManager::get(\get_called_class(), $name);
	}
	private static function stripNamespace($className){
		$path = \explode('\\', $className);
		return \end($path);
	}
	public function hasTypes(){
		return typeManager::has(\get_called_class());
	}
	public function getTypes(){
		return typeManager::getAll(\get_called_class());
	}
	public function getId(){
		if (isset($this->_data['_id'])){
			return new \MongoId($this->_data['_id']);
		}
		return null;
	}
	public static function getById($id){
		if ($id && \strlen($id) == 24){
			$id = new \MongoId($id);
		}else{
			return null;
		}
		$className = \get_called_class();
		return $className::findOne([
				"_id" => $id 
		]);
	}
	public static function id($id){
		if ($id && \strlen($id) == 24){
			$id = new \MongoId($id);
		}else{
			return null;
		}
		$className = \get_called_class();
		return $className::findOne([
				"_id" => $id 
		]);
	}
	// @final
	public static function select(array $criteria = [], array $fields = []){
		$className = \get_called_class();
		if (typeManager::has($className)){
			$criteria['_type'] = self::stripNamespace($className);
		}
		return collection::forClass($className)->find($criteria, $fields);
	}
	// @final
	public static function find(array $criteria = [], array $fields = []){
		$className = \get_called_class();
		if (typeManager::has($className)){
			$criteria['_type'] = self::stripNamespace($className);
		}
		$collection = collection::forClass($className)->select();
		return new result($className, $collection->find($criteria, $fields));
	}
	// @final
	public static function findOne(array $criteria = [], array $fields = []){
		$className = \get_called_class();
		if (typeManager::has($className)){
			$criteria['_type'] = self::stripNamespace($className);
		}
		$collection = collection::forClass($className)->select();
		$result = $collection->findOne($criteria, $fields);
		return new $className($result);
	}
	// @final
	public static function one(array $criteria = [], array $fields = []){
		$className = \get_called_class();
		if (typeManager::has($className)){
			$criteria['_type'] = self::stripNamespace($className);
		}
		$collection = collection::forClass($className)->select();
		$result = $collection->findOne($criteria, $fields);
		return new $className($result);
	}
	// @final
	public function delete(array $options = []){
		$this->preDelete();
		if ($this->exists()){
			$deleted = collection::forClass(\get_called_class())->remove(array(
					"_id" => $this->getId() 
			), $options);
			if ($deleted){
				unset($this->_data['$id']);
			}
		}
		$this->postDelete();
		return !$this->exists();
	}
	// @final
	public function preDelete(){
	}
	// @final
	public function postDelete(){
	}
	// @final
	final public function save(array $options = []){
		$this->_packReferences();
		if ($this->exists() && empty($this->_dirty)) return true;
		$this->preSave();
		$collection = collection::forClass(\get_called_class());
		if ($this->exists()){
			$this->preUpdate();
			$success = $collection->update(array(
					'_id' => $this->getId()
			), array(
					'$set' => $this->_dirty
			), $options);
			$this->_dirty = [];
			$this->postUpdate();
		}else{
			$this->preInsert();
			//var_dump($this);
			$collection->select()->insert($this->_data, $options);
			//$this->_data['_id'] = (string) $this->_data['_id'];
			//var_dump($this);
			$this->_dirty = [];
			$this->postInsert();
		}
		$this->postSave();
		return $success;
	}
	// @final
	public function preSave(){
	}
	// @final
	public function postSave(){
	}
	// @final
	public function preUpdate(){
	}
	// @final
	public function postUpdate(){
	}
	// @final
	public function preInsert(){
	}
	// @final
	public function postInsert(){
	}
	private function _getData($name){
		return isset($this->_data[$name])?$this->_data[$name]:null;
	}
	// References
	private function _packReferences(){
		foreach ($this->_references as $name => $ref){
			// getReference triggers save() on the underlying model
			$this->_dirty[$name] = $ref->getReference();
		}
	}
	public function makeRef(){
		$className =\get_called_class();
		//$ref = \MongoDBRef::create($this->collectionName(), $this->getId(), $this->dbName());
		return collection::forClass($className)->select()->createDBRef($this->_data);//->createReference($this->getId());
	}
	private function _makeReference($model){
		$model = get_called_class();
		$ref = \MongoDBRef::create($this->collectionName(), $this->getId(), $this->dbName());
		return $ref;
	}
	private function _loadReference($name, $type){
		if (!isset($this->_references[$name])){
			$this->_references[$name] = null;
			$model = $type['model'];
			$referenceType = $type['type'];
			if (\class_exists($model)){
				$value = $this->_getData($name);
				if ($referenceType == 'reference'){
					if (\MongoDBRef::isRef($value)){
						$this->_references[$name] = new reference($model,$value['$id']);
					}else{
						$this->_references[$name] = new reference($model,null);
					}
				}elseif ($referenceType == 'references'){
					$this->_references[$name] = new referenceSet($model);
					if (!empty($value)){
						foreach ($value as $item){
							$this->_references[$name][] = $item['$id'];
						}
					}
				}
			}
		}
		if (is_object($this->_references[$name])){
			if ($this->_references[$name] instanceof reference){
				return $this->_references[$name]->get();
			}
		}
		return $this->_references[$name];
	}
	private function _getReferenceInternal($name){
		if (!isset($this->_references[$name])){
			$this->_references[$name] = new reference(\get_called_class(), null);
			$value = $this->_getData($name);
			if (\MongoDBRef::isRef($value)){
				$this->_references[$name]->setId($value['$id']);
			}
		}
		return $this->_references[$name];
	}
	private function _getReferencesInternal($name){
		if (!isset($this->_references[$name])){
			$type = self::_getPropertyType($name);
			$model = $type['model'];
			$this->_references[$name] = new referenceSet($model);
			$value = $this->_getData($name);
			if (!empty($value)){
				foreach ($value as $item){
					$this->_references[$name][] = $item['$id'];
				}
			}
		}
		return $this->_references[$name];
	}
	private function _setReference($name, $value, $type){
		$model = $type['model'];
		$referenceType = $type['type'];
		if ($referenceType == 'reference'){
			$reference = $this->_getReferenceInternal($name);
			if ($value instanceof $model || $value == null){
				$reference->setModel($value);
				$this->_dirty[$name] = $reference;
			}
		}elseif ($referenceType == 'references'){
			$references = $this->_getReferencesInternal($name);
			if (is_array($value)){
				foreach ($value as $item){
					if ($item instanceof $model){
						$references[] = $item;
					}
				}
			}elseif (null === $value){
				$references->clear();
				$this->_dirty[$name] = $references;
			}
		}
	}
	// Convert value to MongoDB representation
	private function _internalValue($name, $value, $type = null){
		if ('_id' === $name){
			// \MongoId
			if (!($value instanceof \MongoId)){
				return new \MongoId($value);
			}else{
				return $value;
			}
		}
		if (null != $type){
			$valueType = $type['type'];
			if ('string' == $valueType){
				$value = \strval($value);
			}elseif ('boolean' == $valueType || 'bool' == $valueType){
				$value = !!($value);
			}elseif ('integer' == $valueType || 'int' == $valueType){
				$value = \intval($value);
			}elseif ('float' == $valueType || 'double' == $valueType){
				$value = \doubleval($value);
			}elseif ('timestamp' == $valueType){
				// MongoTimestamp is used by sharding. 
				// If you're not looking to write sharding tools, what you probably want is MongoDate.
				if (!($value instanceof \MongoTimestamp)){
					$value = new \MongoTimestamp($value);
				}
			}elseif ('datetime' == $valueType || 'time' == $valueType || 'date' == $valueType){
				if (!($value instanceof \MongoDate)){
					$value = new \MongoDate($value); // FIXME parse float into usec
				}
			}elseif (\is_array($value)){
				// check if is ref
				if (\MongoDBRef::isRef($value)){
					// which class??
				}
			}
		}
		$value = $this->processValue($name, $value);
		return $value;
	}
	public function processValue($name, $value){
		return $value;
	}
	// Magic methods
	public function __get($name){
		$type = typeManager::get(\get_called_class(), $name);
		if (null != $type && isset($type['type']) && ($type['type'] == 'reference' || $type['type'] == 'references')){
			return $this->_loadReference($name, $type);
		}else if (\array_key_exists($name, $this->_data)){
			return $this->_internalValue($name, $this->_data[$name], $type);
		}
		return null;
	}
	public function __set($name, $value){
		$type = typeManager::get(\get_called_class(), $name);
		if (null != $type && isset($type['type']) && ($type['type'] == 'reference' || $type['type'] == 'references')){
			$value = $this->_setReference($name, $value, $type);
		}
		$value = $this->_internalValue($name, $value, $type);
		if (isset($this->_data[$name]) && $this->_dirty[$name] === $value){
		}else{
			$this->_data[$name] = $value;
			$this->_dirty[$name] = $value;
		}
	}
}
