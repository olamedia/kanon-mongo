<?php

namespace ru\olamedia\kanon\mongo;

/**
 * Lazy reference to model (Model can be saved or retrieved later)
 * 
 * @author olamedia
 */
class reference{
	private $_id = null;
	private $_model = null;
	private $_className = null;
	public function __construct($className = null, $value = null){
		if ($value instanceof model){
			$this->_model = $value;
		}elseif (null != $value){
			$this->_id = $value;
		}
		$this->_className = $className;
	}
	public function isNull(){
		return (null === $this->_id) && (null === $this->_model);
	}
	public function setId($id){
		$this->_id = $id;
		$this->_model = null;
	}
	public function setModel($model){
		$this->_model = $model;
		$this->_id = null;
	}
	public function getId(){
		if (null !== $this->_model && null === $this->_id){
			$this->_model->save();
			$this->_id = $this->_model->getId();
		}
		return $this->_id;
	}
	public function get(){
		return $this->getModel();
	}
	public function getModel(){
		if (null === $this->_model){
			$className = $this->_className;
			if (null === $this->_id){
				$this->_model = new $className();
			}else{
				$this->_model = $className::getById($this->_id);
			}
		}
		return $this->_model;
	}
	// Triggers save() on model
	public function getReference(){
		$collection = collection::forClass($this->_className)->select();
		if (null != $this->_model){
			$this->_model->save();
		}
		$id = $this->getId();
		return \MongoDBRef::create($collection->getName(), $id, (string) $collection->db);
	}
}
