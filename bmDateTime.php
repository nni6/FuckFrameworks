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
  * Класс, который представляет средства для работы с датой и временем в архитектуре системы. Как правило, используется
  * ДатаОбъектами в качестве обертки к их свойствам, хранящим дату, время или и то, и другое.
  */
  class bmDateTime
  {
    
    private $value;
    private $dateTime;
    		
    /**
    * Конструктор
    * 
    * @param $time дата и время, с которым будет инициализирован объект, в формате unix timestamp или же в формате, 
    *  аналогичном функции strtotime
    */
    public function __construct($time)
    {
    	if(is_int($time))
    	{
    		$time = date(DATE_RFC822, $time);
    	}
      $this->dateTime = new DateTime($time);
    }
		
    public function __sleep()
    {
      $this->value = $this->dateTime->format('Y-m-d H:i:s');
      return array('value');
    }
     
    public function __wakeup()
    {
      $this->dateTime = new DateTime($this->value);   
    }
    
    /**
    * Функция преобразования даты и времени в строку
    * 
    * @return string дата и время в виде 'Y-m-d H:i:s'
    */   
    public function __toString()
    {       
      return $this->dateTime->format('Y-m-d H:i:s');
    }
  
    /**
    * Функция возвращает дату и время текущего объекта
    * 
    * @return string дата и время
    */      
    public function getValue()
    {
      return $this->dateTime;
    }

  }
?>