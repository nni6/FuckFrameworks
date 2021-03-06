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

	/**
  * Базовый класс для кеширующих прослоек дата-объектов
  */
  class bmCustomCache extends bmFFObject
  {
    
    /**
    * Функция возвращает дата объект по идентификатору 
    * При обращении функция пытается "достать" объект из кеша, в случае, если объект отсутствует в кеше
    * функция получает его из БД и помещает в кеш
    * 
    * @param mixed $objectId идентификатор экземпляра объекта
    * @param string $objectName имя дата объекта (имя таблицы)
    * @param string $fields список извлекаемы из БД полей (через запятую)
    */
    public function getObject($objectId, $objectName, $fields)
    {                                             
      $result = $this->application->cacheLink->get($objectName . $objectId); 
      if ($result === false)
      {   
        $sql = "SELECT " . $fields . " FROM `" . $objectName . "` WHERE `id` = '" . $objectId . "' LIMIT 1;";

        $result = $this->application->dataLink->getObject($sql);
        
        if ($result == null)
        {
          $result = false;
          $this->application->errorHandler->add(E_DATA_OBJECT_NOT_EXISTS); 
        } 
        else 
        {
          $this->application->cacheLink->set($objectName . $objectId, $result, BM_CACHE_SHORT_TTL);
        }
      }

      return $result;
    }
    
    /**
    * Функция возвращает коллекцию объектов заданного типа по коллекции идентификаторов
    * Функция изначально пытается получить данные из кеша, после этого обращается к БД
    * 
    * @param mixed $objectIds
    * @param string $objectName имя объекта
    * @param boolean $load
    */
    public function getObjects($objectIds, $objectName, $load = true) // TODO: $load - неиспользуемый параметр
    {
      $objectsFilter = array();
      
      $result = array();
      
      $className = 'bm' . ucfirst($objectName);
      
      foreach ($objectIds as $order => $objectId)
      {
        if ($object = $this->application->cacheLink->get($objectName . $objectId))
        {
          if (is_object($object))
          {
            $object->load = false;
            $result[$order] = new $className($this->application, get_object_vars($object));
          }
        }
        else
        {
          $objectsFilter[$order] = $objectId;
        }
      }

      if (count($objectsFilter) > 0)
      {
        $object = new $className($this->application, array('readonly' => true));
        
        $objectsFilterSQL = "'" . implode("', '", $objectsFilter) . "'";
        $fieldsSQL = $object->fieldsToSQL();
       
        $sql = "SELECT " . $fieldsSQL . " FROM `" . $objectName . "` WHERE `id` IN (" . $objectsFilterSQL . ") ORDER BY FIELD(`identifier`, " . $objectsFilterSQL . ");";
       
        $object = null;
        
        $orders = array_keys($objectsFilter); 
        
        $qObjects = $this->application->dataLink->select($sql);
        
        while ($object = $qObjects->nextObject())
        {                    
          $this->application->cacheLink->set($objectName . $object->identifier, $object, BM_CACHE_SHORT_TTL);  
          $object->load = false;
                                                                                                                          
          foreach ($objectsFilter as $order => $objectId) 
          {
            if ($objectId == $object->identifier)
            {
              $result[$order] = new $className($this->application, get_object_vars($object)); 
            }
          }
        }
        
        $qObjects->free();
      }
      
      return $result;
    }
    
    /**
    * Выполняет sql запрос и загружает объект с идентификатором, полученным в результате выполнения запроса
    * В зависимости от параметра $load, функция или возвращает идентификатор объекта или загруженный объект со всеми полями
    * 
    * @param mixed $sql sql запрос, возвращающий идентификатор загружаемого объекта
    * @param mixed $cacheKey ключ кеша, по которому должен быть сохранен полученный идентификатор объекта
    * @param mixed $objectName имя загружаемого дата объекта
    * @param mixed $errorCode код ошибки, возвращаемый в случае неудачи
    * @param mixed $load флаг, указывающий необходимо ли загружать полученные объекты
    */
    public function getSimpleLink($sql, $cacheKey, $objectName, $errorCode, $load)
    {
      
      $result = $this->application->cacheLink->get($cacheKey);
      
      if ($result === false) {
        $result = $this->application->dataLink->getValue($sql);
        $this->application->cacheLink->set($cacheKey, $result, BM_CACHE_SHORT_TTL);

      }
      
      if ($result)
      {
        $this->application->errorHandler->add(E_SUCCESS);
        
        if ($load)
        {
          $className = 'bm' . ucfirst($objectName);
          $result = new $className($this->application, array('identifier' => $result, 'load' => true));
        }
      }
      else
      {
        $this->application->errorHandler->add($errorCode);
        return null;
      }
      return $result; 
    }    
    
    /**
    * Выполняет sql запрос и загружает объекты с идентификаторами, полученными в результате выполнения запроса
    * В зависимости от параметра $load, функция или возвращает идентификаторы объектов или загруженные объекты со всеми полями
    * 
    * @param string $sql sql запрос, возвращающий идентификаторы загружаемых объектов
    * @param string $cacheKey ключ кеша, по которому должны быть сохранены полученные идентификаторы объектов
    * @param string $objectName имя загружаемого дата объекта
    * @param mixed $errorCode код ошибки, возвращаемый в случае неудачи
    * @param boolean $load флаг, указывающий необходимо ли загружать полученные объекты
    * @param int $limit количество загружаемых объектов
    * @param int $offset смещение, с которого нужно начать загрузку объектов
    */
    public function getSimpleLinks($sql, $cacheKey, $objectName, $errorCode, $load, $limit = 0, $offset = 0)
    {
      
      $result = $this->application->cacheLink->get($cacheKey);

      if ($result === false) {
        $qObjectIds = $this->application->dataLink->select($sql);
        $result = array();
        while ($objectId = $qObjectIds->nextObject()) {
          $result[] = $objectId->identifier;
        }
        $qObjectIds->free();
        $this->application->cacheLink->set($cacheKey, $result, BM_CACHE_SHORT_TTL);
        

      }
    
      if (count($result) > 0)
      {
        if ($offset > 0)
        {           
          $result = array_slice($result, $offset);          
        }          
        
        if ($limit > 0)
        {         
          $result = array_slice($result, 0, $limit);         
        }
        
        $this->application->errorHandler->add(E_SUCCESS);
        
        if ($load)
        {
          $result = $this->getObjects($result, $objectName);
        }
      }
      else
      {
        $this->application->errorHandler->add($errorCode);
        return array();
      }
      return $result; 
    }
    
    /**
    * Функция возвращает TODO
    * 
    * @param mixed $sql запрос, возвращающий идентификаторы загружаемых связанных объектов
    * @param mixed $cacheKey ключ кеша, по которому производится сохранение результатов выборки
    * @param mixed $map структура возвращаемого 
    * @param mixed $errorCode возвращаемый в случае ошибки код неудачи
    * @param mixed $load 
    * @param mixed $limit
    * @param mixed $offset
    * @return array
    */
    protected function getComplexLinks($sql, $cacheKey, $map, $errorCode, $load, $limit = 0, $offset = 0)
    {
      $result = $this->application->cacheLink->get($cacheKey);
      
      $objectArrays = false;
      if ($result === false) {
        
        $result = array();
        $qObjects = $this->application->dataLink->select($sql);
        if ($qObjects->rowCount() > 0)
        {
          $objectArrays = array();
          foreach($map as $propertyName => $type)
          {
            if ($type == BM_VT_OBJECT)
            {
              $objectArrays[$propertyName] = array();
            }
          }
          while ($object = $qObjects->nextObject()) 
          {
            $result[] = $object;
            
            foreach ($objectArrays as $key => $dummy)
            {               
              $objectArrays[$key][] = $object->{$key . 'Id'};
               
            }
          }
        }
   
        $qObjects->free();
        $this->application->cacheLink->set($cacheKey, $result, BM_CACHE_SHORT_TTL);
        $this->application->cacheLink->set($cacheKey . '_objectArrays', $objectArrays, BM_CACHE_SHORT_TTL);
      }
 
      if (count($result) > 0)
      {
        $this->application->errorHandler->add(E_SUCCESS);
        
        $dateTimePropertyNames = array();
        
        /*foreach($map as $propertyName => $type)
        {
          if ($type == BM_VT_DATETIME)
          {
            $dateTimePropertyNames[] = $propertyName;
          }
        }
        
        if (count($dateTimePropertyNames) > 0)
        {
          foreach ($dateTimePropertyNames as $dateTimePropertyName)
          {
            foreach ($result as $key => $value)
            {
              $result[$key]->$dateTimePropertyName = new bmDateTime($result[$key]->$dateTimePropertyName);
            }
          }
        }*/
        
          
        
        if ($load)
        {
          if (!$objectArrays)
          {
            $objectArrays = $this->application->cacheLink->get($cacheKey . '_objectArrays');               
          }
          
          if ($offset > 0)
          {           
            $result = array_slice($result, $offset);          

            foreach ($objectArrays as $key => $dummy)
            {               
              $objectArrays[$key] = array_slice($objectArrays[$key], $offset);
            }
          }          
          
          if ($limit > 0)
          {         
            $result = array_slice($result, 0, $limit);         
            
            foreach ($objectArrays as $key => $dummy)
            {               
              $objectArrays[$key] = array_slice($objectArrays[$key], 0, $limit);
            }
          }
          
          foreach ($objectArrays as $key => $dummy)
          {
            $objectArrays[$key] = $this->getObjects($objectArrays[$key], $key);
          }
          
                                                  
          foreach ($result as $order => $dummy)
          {
            foreach ($objectArrays as $key => $dummy)
            {  
              $result[$order]->$key = $objectArrays[$key][$order];              
            }
          }                
        }
      }
      else
      {
        $this->application->errorHandler->add($errorCode);
        return array();
      } 
                 
      return $result;
    }
    
  }
?>