<?php
  namespace Cheatnut\Controllers;
  
  class Controller {
    public function __destruct(){
      view()->display();
    }
  }