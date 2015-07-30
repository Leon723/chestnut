<?php
  namespace Cheatnut\Http;
  
  abstract class Controller {
    protected $application;
    
    public function __construct($application) {
      $this->application = $application;
    }
    
    public function view($fileName, $data = []) {
      return $this->application->view->setFileName($fileName)->data($data);
    }
    
    // public function __destruct() {
    //   return $this->application->view->display();
    // }
  }