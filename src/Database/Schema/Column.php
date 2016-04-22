<?php
namespace Chestnut\Database\Schema;

use Chestnut\Contract\StringAble;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Column implements StringAble {
	protected $column;
	protected $type;
	protected $length;
	protected $nullable;
	protected $primary;
	protected $auto_increment;
	protected $default;

	protected $default_length = [
		'integer' => 11,
		'string' => 255,
		'float' => [11, 2],
	];

	protected $no_length = [
		'timestamp', 'text',
	];

	public function __construct($column, $type, $length = 255, $nullable = false, $default = '', $primary = false, $auto_increment = false) {
		$this->column = $column;

		$this->type($type);
		$this->length = in_array($type, $this->default_length) ? $default_length[$type] : $length;
		$this->nullable($nullable);

		$this->primary = $primary;
		$this->auto_increment = $auto_increment;
	}

	public function type($type) {
		$this->type = $type;

		return $this;
	}

	public function length($length) {
		$this->length = $length;

		return $this;
	}

	public function nullable($nullable = true) {
		$this->nullable = $nullable;

		return $this;
	}

	public function primary($primary = true) {
		$this->primary = $primary;

		return $this;
	}

	public function default($default) {
		$this->default = (string) $default;

		return $this;
	}

	public function auto_increment($auto_increment = true) {
		$this->auto_increment = $auto_increment;

		return $this;
	}

	public function toString() {
		$length = is_array($this->length) ? join(',', $this->length) : $this->length;
		$type = in_array($this->type, $this->no_length) ? "{$this->type}" : "{$this->type}({$length})";
		$nullable = $this->nullable ? '' : 'not null';
		$primary = $this->primary ? 'primary key' : '';
		$auto_increment = $this->auto_increment ? 'auto_increment' : '';
		$default = empty($this->default) ? '' : 'default ' . "{$this->default}";

		return "`{$this->column}` {$type} {$nullable} {$primary} {$auto_increment} {$default}";
	}

	public function __toString() {
		return $this->toString();
	}
}
