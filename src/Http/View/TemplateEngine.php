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
    $regs = [
      "parseVar"=> '#{{ *\$([\w]+)( *[\+\-\*\/\.\=]?)( *.+ *)?}}#',
      "parseFor"=> '#{{ *for *\$(\w+) *in *\$(\w+) *(as *\$(\w+))? *}}#',
      'parseIf'=> '#{{ *(if) *(\(.+\){1}) *}}|{{ *(elseif) *\(.+\){1} *}}|{{ *(else) *}}#',
      "parseEnd"=> '#{{ *end *}}#'
    ];

    foreach($regs as $type => $reg) {
      $this->content = preg_replace_callback($reg, function($m) use($type){
        return $this->$type($m);
      }, $this->content);
    }
  }

  private function parseVar($m)
  {
    $result = "<?php echo \$$m[1]";

    if(trim($m[2]) !== "" && (! array_key_exists(3, $m) || trim($m[3]) === "")) {
      throw new \RuntimeException("输出公式有误，请检查，运算符号后需要输入内容");
    } elseif(trim($m[2]) !== "" && trim($m[3]) !== "") {
      $result .= trim($m[2]) . ' ' . trim($m[3]);
    }

    $result .= "; ?>";

    return $result;
  }

  private function parseFor($m)
  {
    $result = "";

    if($m[3] === "as \$$m[4]") {
      $result .= "<?php foreach(\$$m[2] as \$$m[4]=> \$$m[1]) { ?>";
    }else {
      $result .= "<?php foreach(\$$m[2] as \$$m[1]) { ?>";
    }

    return $result;
  }

  private function parseIf($m)
  {
    $result = "";

    if($m[1] === 'else') {
      $result .= '<?php else {';
    }
    else {
      $result .=  "<?php $m[1] $m[2] {";
    }

    $result .= " ?>";

    return $result;
  }

  private function parseEnd($m)
  {
    return "<?php } ?>";
  }

}
