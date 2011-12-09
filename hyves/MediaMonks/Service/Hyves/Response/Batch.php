<?php

class MediaMonks_Service_Hyves_Response_Batch implements SeekableIterator, Countable, ArrayAccess
{
	protected $_body = null;
	protected $_info = null;
	
	protected $_pointer = 0;
	protected $_count = 0;
	protected $_responses = array();
	
	protected $_paginated = false;
	
	public function __construct($format, $data)
	{
		$response = MediaMonks_Service_Hyves_Response::toArray($format, $data);
		
		if(!empty($response['error_code'])) {
			throw new MediaMonks_Service_Hyves_Response_Batch_Exception($response['error_message'], $response['error_code']);
		}

		$this->_info = $response['info'];
		unset($response['info']);
		
		$this->_body = $response;
		
		// loop trough each response and build a collection of responses
		$this->_responses = array();
		foreach($this->_body['request'] as $response) {
			$this->_responses[] = new MediaMonks_Service_Hyves_Response($format, $response[key($response)], key($response));
		}
		
		$this->_count = count($this->_responses);
	}
	
	public function getBody()
	{
		return $this->_body;
	}
	
	public function getRaw()
	{
		return $this->_raw;
	}
	
	public function getInfo()
	{
		return $this->_info;
	}

    public function __sleep()
    {
        return array('_body', '_info', '_data', '_pointer', '_count', '_responses', '_paginated');
    }

    public function __wakeup()
    {
    }
    
    public function rewind()
    {
        $this->_pointer = 0;
        return $this;
    }
    
    public function current()
    {
        if ($this->valid() === false) {
            return null;
        }

        // return the row object
        return $this->_responses[$this->_pointer];
    }
    
    public function key()
    {
        return $this->_pointer;
    }
    
    public function next()
    {
        ++$this->_pointer;
    }
    
    public function valid()
    {
        return $this->_pointer < $this->_count;
    }
    
    public function count()
    {
        return $this->_count;
    }
    
    public function seek($position)
    {
        $position = (int) $position;
        if ($position < 0 || $position >= $this->_count) {
            throw new MediaMonks_Service_Hyves_Response_Batch_Exception("Illegal index $position");
        }
        $this->_pointer = $position;
        return $this;
    }

    public function offsetExists($offset)
    {
        return isset($this->_responses[(int)$offset]);
    }

    public function offsetGet($offset)
    {
        $this->_pointer = (int)$offset;
        return $this->current();
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    	unset($this->container[$offset]);
    }
}