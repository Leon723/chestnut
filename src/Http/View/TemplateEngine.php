<?php namespace Cheatnut\Http\View;

class TemplateEngine
{
  protected $content;

  public function __construct($path)
  {
    $this->content = file_get_contents($path);
  }

  public function make()
  {
    $this->parse();

    return $this->content;
  }

  public function parse()
  {
    $this->parseVar();
  }

  public function parseVar()
  {
    $pattern = '#{{ *\$([\w]+) *}}#';

    $this->content = preg_replace($pattern, '<?php echo \$${1}; ?>', $this->content);
  }

}
