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
  * @todo documentation
  */
  abstract class bmCustomContentProvider extends bmFFObject
  {
    protected $staticServers = null;
    protected $contentServers = null;
    protected $documentRoot = null;
    protected $contentRoot = null;
    protected $templatePrefixes = null;

    /**
    * Конструктор
    * 
    * @param bmApplication $application экземпляр текущего выполняющегося приложения
    * @param array $parameters массив параметров
    * @return bmCustomContentProvider
    */
    public function __construct($application, &$parameters = array())
    {
      parent::__construct($application, $parameters);
      require_once(projectRoot . 'conf/contentProvider.conf');
    }
    
    /**
    * Собирает CSS в единый файл
    * Функция собирает файлы, указанные в conf/css.conf в результирующий css/global.css
    * @todo documentation
    * @param boolean $tudy
    */
    public function compileCSS($tudy = false)
    {
      require_once(serverRoot . 'conf/css.conf');
      
      $result = '';
      foreach ($toCompile as $css)
      {
        $result .= file_get_contents(documentRoot . 'css/' . $css . '.css') . "\n";
      }
      
      if ($tudy)
      {
        $result = $this->tudyCSS($result);
      }
      
      file_put_contents(documentRoot . 'css/global.css', $result);
    }
    
    /**
    * Возвращает контент-сервер
    * В этой реализации возвращается первый элемент из списка
    */
    public function getContentServer()
    {
      return $this->contentServers[0];
    }
    
    /**
    * Возвращает статический сервер
    * В этой реализации возвращается первый элемент из списока
    */
    public function getStaticServer()
    {
      return $this->staticServers[0];
    }
        
    /**
    * Возвращает путь к корню сайта
    * В этой реализации возвращается первый элемент из списка
    */
    public function getDocumentRoot()
    {
      return $this->documentRoot[0];
    } 
       
    /**
    * Возвращает путь к корню контента сайта
    * В этой реализации возвращается первый элемент из списка
    */
    public function getContentRoot()
    {
      return $this->contentRoot[0];
    }
  }
  
?>
