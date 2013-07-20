<?php

namespace ru\olamedia\kanon\mongo;

class result implements \Iterator{
	private $_className = null;
	private $_cursor = null;
	private $_asArray = false;
	public function __construct($className,\MongoCursor $cursor){
		$this->_className = $className;
		$this->_cursor = $cursor;
	}
	/**
	 *
	 * @return \MongoCursor
	 */
	public function getCursor(){
		return $this->_cursor;
	}
	/**
	 *
	 * @param boolean $asArray
	 * @return \ru\olamedia\kanon\mongo\result
	 */
	public function asArray($asArray = true){
		$this->_asArray = $asArray;
		return $this;
	}
	public function count($foundOnly = false){
		return $this->_cursor->count($foundOnly);
	}
	/**
	 *
	 * @param string $fieldName
	 * @return \ru\olamedia\kanon\mongo\result
	 */
	public function asc($fieldName){
		$this->_cursor->sort([
				$fieldName => 1 
		]);
		return $this;
	}
	/**
	 *
	 * @param string $fieldName
	 * @return \ru\olamedia\kanon\mongo\result
	 */
	public function desc($fieldName){
		$this->_cursor->sort([
				$fieldName => -1 
		]);
		return $this;
	}
	public function sort(array $fields){
		$this->_cursor->sort($fields);
		return $this;
	}
	public function skip($num){
		$this->_cursor->skip($num);
		return $this;
	}
	public function limit($num){
		$this->_cursor->limit($num);
		return $this;
	}
	private function _pack($data = []){
		if (null === $data){
			return null;
		}
		if ($this->_asArray){
			return $data;
		}
		$className = $this->_className;
		return new $className($data);
	}
	// Iterator
	public function rewind(){
		$this->_cursor->rewind();
	}
	public function current(){
		return $this->_pack($this->_cursor->current());
	}
	/**
	 *
	 * @return string
	 */
	public function key(){
		return $this->_cursor->key();
	}
	public function next(){
		return $this->_pack($this->_cursor->next());
	}
	public function valid(){
		return $this->_cursor->valid();
	}
}
