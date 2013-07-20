<?php

namespace ru\olamedia\kanon\mongo;

/**
 * Collection of references
 * For lazy loading/saving purposes
 * 
 * @author olamedia
 */
class referenceSet implements \ArrayAccess, \Iterator, \Countable{
  private $_set = [];
	private $_className = null;
	public function __construct($className){
		$this->_className = $className;
	}
	public function clear(){
		$this->_set = [];
	}
	// Countable
	public function count(){
		return count($this->_set);
	}
	// Iterator
	public function rewind(){
		reset($this->_set);
	}
	public function current(){
		$value = current($this->_set);
		if (false === $value){
			return false;
		}
		return $value->get();
	}
	public function key(){
		return key($this->_set);
	}
	public function next(){
		$value = next($this->_set);
		if (false === $value){
			return false;
		}
		if (!is_object($value)){
			var_dump($value);
		}
		return $value->get();
	}
	public function valid(){
		$key = key($this->_set);
		return null !== $key && false !== $key;
	}
	// ArrayAccess
	public function offsetExists($offset){
		return \array_key_exists($offset, $this->_set);
	}
	public function offsetUnset($offset){
		unset($this->_set[$offset]);
	}
	public function offsetGet($offset){
		if (\array_key_exists($offset, $this->_set)){
			return $this->_set[$offset]->get();
		}
		return null;
	}
	public function offsetSet($offset, $value){
		if (null === $offset){
			$this->_set[] = new reference($this->_className, $value);
		}else{
			$this->_set[$offset] = new reference($this->_className, $value);
		}
	}
	public function getReference(){
		$a = [];
		foreach ($this->_set as $ref){
			$a[] = $ref->getReference();
		}
		return $a;
	}
}
