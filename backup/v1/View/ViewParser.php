<?php
  namespace Cheatnut\View;
  
  class ViewParser {
    protected $content;
    protected $sensitive;
    
    public function __construct($path, $sensitive = false) {
      $this->content = file_get_contents($path);
      $this->sensitive = $sensitive;
    }
    
    public function make() {
      $this->parse();
      
      return $this->content;
    }
    
    public function parse() {
      $this->parseVar();
    }
    
    public function parseVar() {
      $pattern = '#{{ *\$([\w]+) *}}#';
      
      $this->content = preg_replace($pattern, '<?php echo \$${1}; ?>', $this->content);
    }
  }