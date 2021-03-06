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
  * Базовый класс для реализации страниц
  */
  abstract class bmHTMLPage extends bmPage
  {
    
    public $title = '';
    public $docType = '<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
';
    
    private $scripts = array();
    private $CSS = array();
    private $meta = array();
    private $RSSLinks = array();
    
    protected $content = '';
    protected $metaData = '';
    protected $clientTemplates = '';
    protected $pageTemplate = '';
    
    /**
    * Генерация страницы
    * Добавляет в страницу meta, js скрипты, css, rss
    * @return возвращает doctype страницы
    */
    public function generate() {
      
      $this->metaData = implode("\n", $this->meta) . "\n" . implode("\n", $this->scripts) . "\n" . implode("\n", $this->CSS) . "\n" . implode("\n", $this->RSSLinks) . "\n";
      
      return $this->docType;
      
    }
    
    /**
    * Добавляет в страницу данные
    * //TODO change!!
    * @param mixed $dataType тип данных
    * @param mixed $source url источника
    * @param mixed $path добавляемый в страницу код
    */
    public function addHTMLMetaDatum($dataType, $source, $path)
    {
      if (!array_key_exists($source, $this->$dataType)) {
        $this->{$dataType}[$source] = $path;
      }
    }
    
    /**
    * Отрисовывает в страницу переданные шаблоны
    * 
    * @param mixed $templateNames имя шаблона или массив имен шаблонов
    */
    public function getClientTemplates($templateNames)
    {
      if (!is_array($templateNames))
      {
        $templateNames = array($templateNames);
      }
      
      $templates = '';
      $templateSet = $this->application->getTemplate('global/div_templateSet');
      $templateClient = $this->application->getTemplate('global/div_template');
      foreach ($templateNames as $key => $templateName)
      {
        $currentTemplate = $this->application->getClientTemplate($templateName);
        $templateName = substr($templateName, strrpos($templateName, '/') + 1);
        eval('$templates .= "' . $templateClient . '";');
      }
      eval('$this->clientTemplates = "' . $templateSet . '";');
    }

    /**
    * Добавляет к странице инклюд внешнего js скрипта
    * 
    * @param string $source url скрипта
    */
    public function addScript($source)
    {
      $src = mb_strpos($source, 'http') === 0 ? $source : $this->application->contentProvider->getStaticServer() . '/scripts/' . $source . '.js';
      $this->addHTMLMetaDatum('scripts', $source, '<script type="text/javascript" src="' . $src . '"></script>');
    }
    
    /**
    * Добавляет к странице инклюды внешних js скриптов
    * 
    * @param mixed $scripts url (или массив url) скрипта
    */
    public function addScripts($scripts)
    {
      if (!is_array($scripts))
      {
        $scripts = array($scripts);
      }
      
      foreach ($scripts as $source)
      {
        $this->addScript($source);
      }
    }
    
    /**
    * Добавляет к странице инклюд внешнего css файла
    * 
    * @param string $source url css
    */
    public function addCSS($source)
    {
      $this->addHTMLMetaDatum('CSS', $source, '<link rel="stylesheet" type="text/css" href="' . $this->application->contentProvider->getStaticServer() . '/styles/' . $source . '.css" />');
    }
    
    /**
    * Добавляет к страницы инклюды внешних css файлов
    * 
    * @param mixed $CSSs url (или массив url) css
    */
    public function addCSSs($CSSs)
    {
      if (!is_array($CSSs))
      {
        $CSSs = array($CSSs);
      }
      
      foreach ($CSSs as $source)
      {
        $this->addCSS($source);
      }
    }
    
    /**
    * Добавляет к странице поле meta
    * 
    * @param mixed $name имя поля
    * @param mixed $content значение
    */
    public function addMeta($name, $content)
    {
      $this->addHTMLMetaDatum('meta', $name, '<meta http-equiv="' . $name . '" content="' . $content . '" />');
    }
    
    /**
    * Добавляет к странице поля meta
    * 
    * @param array $meta массив полей (имя => значение)
    */
    public function addMetas($meta)
    {
      foreach ($meta as $name => $content)
      {
        $this->addMeta($name, $content);
      }
    }
  
    /**
    * Добавляет к документу ссылку на rss поток
    * 
    * @param string $source url rss потоку
    * @param string $title отображаемое имя потока
    */
    public function addRSSLink($source, $title)
    {
      $this->addHTMLMetaDatum('RSSLinks', $source, '<link href="' . $source .'" title="' . $title . '" type="application/rss+xml" rel="alternate"/>');
    }
    
    /**
    * Добавляет к документу ссылки на rss потоки
    * 
    * @param array $links массив данных rss потоков (url потока => отображаемое имя потока )
    */
    public function addRSSLinks($links)
    {
      if (!is_array($links))
      {
        $links = array($links);
      }
      
      foreach ($links as $source => $title)
      {
        $this->addRSSLink($source, $title);
      }
    }
  }
?>