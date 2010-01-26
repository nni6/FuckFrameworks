<?php
  /*
  * Copyright (c) 2009, "The Blind Mice Studio"
  * All rights reserved.
  * 
  * Redistribution and use in source and binary forms, with or without
  * modification, are permitted provided that the following conditions are met:
  * - Redistributions of source code must retain the above copyright
  *   notice, this list of conditions and the following disclaimer.
  * - Redistributions in binary form must reproduce the above copyright
  *   notice, this list of conditions and the following disclaimer in the
  *   documentation and/or other materials provided with the distribution.
  * - Neither the name of the "The Blind Mice Studio" nor the
  *   names of its contributors may be used to endorse or promote products
  *   derived from this software without specific prior written permission.

  * THIS SOFTWARE IS PROVIDED BY "The Blind Mice Studio" ''AS IS'' AND ANY
  * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  * DISCLAIMED. IN NO EVENT SHALL "The Blind Mice Studio" BE LIABLE FOR ANY
  * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
  * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
  * 
  */


	abstract class bmDataObject extends bmFFObject implements IDataObject {
		
		public $map = array();
		public $objectName = '';
		public $dirty = array();
		public $dirtyQueue = array();
		protected $readonly = false;
		public $updateCount = 0;
		public $runningCheckDirty = false;
		
		public function __construct(bmApplication $application, $parameters = array())
		{
			
      
      $this->events = array('save', 'load', 'delete', 'propertyChange');
      
      foreach($this->map as $propertyName => $property)
			{
				$this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $property['defaultValue']);
			}
      
			parent::__construct($application, $parameters);
			
			foreach($this->map as $propertyName => $property)
			{
				if (array_key_exists($propertyName, $parameters)) 
				{
					if (!property_exists($this, $propertyName))
					{
            
            $this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $parameters[$propertyName]);
					}
				}
			}
      
			if (array_key_exists('identifier', $parameters) && ($parameters['identifier'] !== 0 && $parameters['identifier'] != ''))
			{           
				if (!array_key_exists('load', $parameters) || $parameters['load'] != false)
				{
					
          $this->load();
				}                                                                 
			}
      else
      {
        $this->dirty['store'] = true;
      }

		}
		
	 protected function formatProperty($propertyName, $dataType, $value)
	 {
			switch ($dataType)
			{
				case BM_VT_DATETIME:       
					$result = new bmDateTime($value);
				break;
				
				case BM_VT_INTEGER:
					$result = intval($value);
				break;
				
				case BM_VT_FLOAT:
					$result = floatval($value);
				break;
				
				default:
					$result = $value;            
				break;
			}
			return $result;
	 } 
		
		public function __destruct()
		{ 
			$this->invalidate();
			
			$this->checkDirty();
		}
		
		protected function checkDirty()
		{  
			if (!$this->runningCheckDirty)
			{
				$this->runningCheckDirty = true;
				
				if (!$this->readonly && $this->updateCount == 0)
				{ 
					$this->dirty = array_merge($this->dirty, $this->dirtyQueue);
					$this->dirtyQueue = array();
					
					while (count($this->dirty) !== 0)
					{
						$actions = array();
						if (array_key_exists('store', $this->dirty) && $this->dirty['store'])
						{
							$this->store();
							unset($this->dirty['store']);
						}
						foreach($this->dirty as $method => $flag)
						{ 
							if ($flag)
							{ 
								$this->$method(); 
							}
						}
						
						if (count($this->dirtyQueue) > 0)
						{
							$this->dirty = $this->dirtyQueue;
							$this->dirtyQueue = array();            
						}
						else
						{
							$this->dirty = array();
						}
					}
				} 
				
				$this->runningCheckDirty = false;
			}
		}
		
		public function makeDirty($method)
		{
			$this->dirtyQueue[$method] = true;
		}
		
		public function __get($propertyName)
		{ 
      $this->checkDirty();
			$result = parent::__get($propertyName);
			if (array_key_exists($propertyName, $this->map))
			{
				switch ($this->map[$propertyName]['dataType'])
				{
					case BM_VT_DATETIME:
            return $result;
					break;
					default:
						return $result;
					break;
				}
			}
			
			
			
			if (isset($result))
			{
				return $result;
			}
		}
		
		public function __set($propertyName, $value)
		{
			if (array_key_exists($propertyName, $this->map))
			{
				if ($this->map[$propertyName]['dataType'] == BM_VT_DATETIME)
				{
					$value = new bmDateTime($value);
				}
				if ((string)$this->properties[$propertyName] != (string)$value)
        {
          $this->triggerEvent('propertyChange', array('identifier' => $this->properties['identifier'], 'propertyName' => $propertyName, 'oldValue' => $this->properties[$propertyName], 'newValue' => $value));
          $this->properties[$propertyName] = $value;
          $this->dirty['store'] = true;
        }
				
			}
		}
		
		public function getObjectIdByField($fieldName, $value)
		{
			$sql = "SELECT `id` AS `identifier` FROM `" . $this->objectName . "` WHERE `" . $fieldName . "` = '" . $value . "';";
			$result = $this->application->dataLink->getValue($sql);
			return ($result != null) ? $result : 0;
		}
		
		public function load()
		{
      $objectName = mb_convert_case($this->objectName, MB_CASE_TITLE);
			if ($this->properties['identifier'])
			{        
				$cache = $this->application->{$this->objectName . 'Cache'}->getObject($this->properties['identifier'], $this->objectName, $this->fieldsToSQL());
			}
			else
			{
				$cache = false;
			}
      
			
			if ($cache != false) 
			{
				foreach ($this->map as $propertyName => $property) 
				{                                                                                                                          
					$this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $cache->$propertyName);   
				}
				$this->application->errorHandler->add(E_SUCCESS);    
				$this->dirty['store'] = false; 
        $this->triggerEvent('load', array('identifier' => $this->properties['identifier'])); 
			}
			else
			{
				$this->application->errorHandler->add(E_DATA_OBJECT_NOT_EXISTS);    
			} 
		}
		
		public function fieldsToSQL()
		{
			
			$fields = array();
			
			foreach ($this->map as $propertyName => $property)
			{
				$fields[] =  '`' . $property['fieldName'] . '` AS `' . $propertyName . '`';
			}
				
			$fields = "" . implode(',', $fields) . "";
			
			return $fields;
			
		}
		
		public function store() 
		{
      $saveIdentifier = $this->properties['identifier'];
			$dataLink = $this->application->dataLink;
			if ( ($this->properties['identifier'] === 0) || ($this->properties['identifier'] == '') ) 
			{
				$this->properties['identifier'] = 'NULL';
			}
			
			$cacheObject = new stdClass();
			
			$fields = array();
			
			foreach ($this->map as $propertyName => $property) 
			{     
				$propertyValue = $this->properties[$propertyName];
				$cacheObject->$propertyName = $propertyValue;  
				
				$value = $property['defaultValue'];
				if ($propertyValue !== 'NULL')
				{
					switch ($property['dataType']) 
					{
						case BM_VT_STRING:
							$value = "'" . $dataLink->formatInput($propertyValue) . "'";
						break;
						case BM_VT_INTEGER:
							$value = intval($propertyValue);
						break;
						case BM_VT_FLOAT:
							$value = floatval($propertyValue);
						break;
						case BM_VT_DATETIME:
							$value = "'" . (string)$propertyValue . "'";
						break;
					}
				}
				else
				{
					$value = 'NULL';
				}
				$fields[] = '`' . $property['fieldName'] . '` = ' . $value;

				
			}
			
			$fields = implode(',', $fields);
			
		
			$sql = "INSERT INTO `" . $this->objectName . "` SET " . $fields . " ON DUPLICATE KEY UPDATE " . $fields . ";";
			
			$objectId = $this->application->dataLink->query($sql);
			if (($objectId = $this->application->dataLink->insertId()) != 0)
			{
				$this->properties['identifier'] = $objectId;
        $cacheObject->identifier = $objectId;
			}
			else 
			{
				$this->properties['identifier'] = $saveIdentifier;
        $cacheObject->identifier = $saveIdentifier;
			}

			$this->application->cacheLink->set($this->objectName . $this->properties['identifier'], $cacheObject, BM_CACHE_SHORT_TTL);
		}
		
		public function save() 
		{ 
      $this->checkDirty();
      $this->triggerEvent('save', array('identifier' => $this->properties['identifier']));
		}
		
		public function delete()
		{
			$this->readonly = true;
      $this->triggerEvent('delete', array('identifier' => $this->properties['identifier']));
		}
		
		public function beginUpdate()
		{
			++$this->updateCount;
		}
		
		public function endUpdate()
		{
			$this->updateCount = $this->updateCount == 0 ? 0 : --$this->updateCount;
		}
		
		public function invalidate()
		{
			$this->updateCount = 0;
		}
    
    protected function itemExists($key, $propertyName, &$collection)
    {
      $result = false;
      foreach ($collection as $item)
      {
        if ($item->$propertyName == $key)
        {
          $result = true;
          break;
        }
      }
      return $result;
    }
		
	}
?>