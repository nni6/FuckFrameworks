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
  * Класс реализует отправку электронной почты
  */
  class bmMailer extends bmFFObject
  { 
    /**
    * Функция выполняет отправку писем (см)
    * Внимание! В текущей реализации функция отправит только ОДНО письмо! Нужно исправить или поведение функции или имя параметра и документацию.
    * @todo check code & correct!!
    * @param string $subject тема отправляемых писем
    * @param array $messages массив сообщений (email адресата => тело письма)
    * @return boolean результат отправки
    */
    public function send($subject, $messages)
    {               
      if (count($messages) > 0)
      {
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $header = "Content-type: text/plain; charset=utf-8";
        
        foreach ($messages as $email => $message)
        {
          if ($this->validate($email))
          {
            if (mail($email, $subject, $message, $header))
            {
              return true;
            }
            else
            {
              return false;
            }
          }
        }
      }
    }
    
    /**
    * Выполняет проверку адреса электронной почтына корректность
    * 
    * @param string $address адрес электронной почты, подлежащий проверке
    * @return boolean корректность переданного адреса электронной почты
    */
    private function validate($address)
    {
      return (filter_var($address, FILTER_VALIDATE_EMAIL));    
    }
  }

?>