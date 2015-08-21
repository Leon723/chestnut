<?php namespace Chestnut\Http\View;

class TemplateEngine
{
  protected $layout;
  protected $view;
  protected $path;
  protected $rootDir;
  protected $section;

  public function __construct($path)
  {
    $this->path = $path;
    $this->rootDir = \Chestnut\Core\Registry::get('config')->get('root') . "../app/views/";
  }

  public function make()
  {
    $this->_getView();
    $this->_getLayout();

    return $this->_parse();
  }

  private function _getView()
  {
    $this->view = file_get_contents($this->path);

    preg_match_all("#<@section:(.*)>([\w\W]*?)<@\/section>#", $this->view, $m);

    foreach($m[1] as $key=> $section) {
      $this->_setSection($section, $m[2][$key]);
    }
  }

  private function _setSection($section, $value)
  {
    if($this->section === null) {
      $this->section = [];
    }

    $this->section[$section] = $value;
  }

  private function _getSection($section)
  {
    return $this->section[$section];
  }

  private function _getLayout()
  {
    preg_match("#<@layout:([\w\.]+)>#",$this->view, $m);

    $layoutPath = count($m) ? join("/", explode(".", $m[1])) . ".php" : false;

    if($layoutPath && file_exists($this->rootDir . $layoutPath)) {
      $layout = file_get_contents($this->rootDir . $layoutPath);

      $this->layout = $layout;
    }
  }

  private function _parse()
  {
    $regs = [
      "_parseFor"=> '#<@for:(\S*)\s*in\s*(\S*)>#',
      "_parseIf"=> '#<@(if(?=\:)|elseif(?=\:)|else)(?:\:?(.*))>#',
      "_parseVar"=> '#<@(\S*(?=\:))(?:\:(.*?|\[.*\]))>#',
      "_parseEnd"=> '#<@/\S*>#'
    ];

    if($this->layout) {
      $content = preg_replace_callback("#<@section:(.*)>#", function($m){
        return $this->_getSection($m[1]);
      }, $this->layout);
    } else {
      $content = $this->view;
    }

    foreach($regs as $type => $reg) {
      $content = preg_replace_callback($reg, function($m) use($type){
        return $this->$type($m);
      }, $content);
    }

    return $content;
  }

  private function _parseVar($m)
  {
    if(preg_match("#\[\S*\]#", $m[2])) {
      return "<?php echo \$$m[1]$m[2]; ?>";
    }elseif($m[1] === ""){
      if(preg_match("#(\"|\')\S*(\"|\')#", $m[2])) {
        $m[2] = "$m[2]";
      } elseif(! (int) $m[2] && "" . (int) $m[2] !== $m[2]) {
        $m[2] = "\$$m[2]";
      }
      return "<?php echo $m[2]; ?>";
    } else {
      return "<?php echo \$$m[1]->$m[2]; ?>";
    }
  }

  private function _parseFor($m)
  {
    $item = explode(",", $m[1]);
    $parent = $m[2];

    return "<?php foreach(\$$parent as \$" . join("=> $", $item) . ") { ?>";
  }

  private function _parseIf($m)
  {
    switch($m[1]) {
      case "if":
        return "<?php $m[1]($m[2]) { ?>";
        break;
      case "elseif":
        return "<?php } $m[1]($m[2]) { ?>";
        break;
      case "else":
        return "<?php } $m[1] { ?>";
        break;
    }
  }

  private function _parseEnd($m)
  {
    return "<?php } ?>";
  }

}
