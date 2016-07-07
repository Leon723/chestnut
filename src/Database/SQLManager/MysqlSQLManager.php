<?php
namespace Chestnut\Database\SQLManager;

use Chestnut\Database\Raw;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class MysqlSQLManager extends SQLManager {

	public function parseInsert($query, $params) {
		$result = 'insert into ' . $this->wrapTable() . ' ';

		$query->addBind('insert', array_values($params));

		return $result . $this->parseInsertParameters(array_keys($params)) . ";";
	}

	public function parseDelete($query) {
		return 'delete from ' . $this->wrapTable() . $this->parseWhere($query->where) . ';';
	}

	public function parseUpdate($query, $params) {
		$result = 'update ' . $this->wrapTable() . ' set';

		foreach ($params as $column => $value) {
			$query->addBind('update', $value);

			$result .= " {$this->wrap($column)} = ?,";
		}

		return rtrim($result, ',')
		. $this->parseWhere($query->where) . ';';
	}

	public function parseSelect($query) {
		$result = $query->isDistinct() ? 'select distinct ' : 'select ';
		return $result
		. $this->parseSelectColumn($query->columns)
		. $this->parseFrom($query->table)
		. $this->parseWhere($query->where)
		. $this->parseGroup($query->group)
		. $this->parseOrder($query->order)
		. $this->parseLimit($query->limit, $query->offset) . ';';
	}

	public function parseInsertParameters($params) {
		$columns = array_map(function ($v) {
			return "`{$v}`";
		}, $params);

		$values = array_map(function () {
			return "?";
		}, $params);

		return "(" . implode($columns, ',') . ")" . ' Values ' . "(" . implode($values, ',') . ")";
	}

	public function parseSelectColumn($columns) {
		$result = [];

		foreach ($columns as $column => $config) {
			if (is_integer($column)) {
				list($column, $config) = [$config, []];
			}

			switch (count($config)) {
			case 1:
				$result[] = $this->wrap($column) . " as `{$config[0]}`";
				break;
			case 2:
				$result[] = "distinct({$this->wrap($column)}) as {$config[0]}";
				break;
			default:
				$result[] = $column instanceof Raw || $column === '*' ? $column : $this->wrap($column);
				break;
			}
		}

		return " " . implode($result, ', ');
	}

	public function parseFrom($from) {
		return " from " . $this->wrapTable() . " ";
	}

	public function parseWhere($wheres) {
		if (is_null($wheres)) {
			return '';
		}

		$result = '';

		foreach ($wheres as $where => $config) {
			$result .= $config['link'] . " ";
			switch ($config['symbol']) {
			case 'between':
				$result .= "{$this->wrap($where)} {$config['symbol']} ? and ? ";
				break;
			case 'in':
				$result .= "find_in_set({$this->wrap($where)}, ?) ";
				break;
			case 'not in':
				$result .= "!find_in_set({$this->wrap($where)}, ?) ";
				break;
			default:
				$result .= "{$this->wrap($where)} {$config['symbol']} ? ";
				break;
			}
		}

		return " where " . ltrim(ltrim($result, ' and'), ' or');
	}

	public function parseGroup($group) {
		if (is_null($group)) {
			return '';
		}

		$groupSQL = ' group by ';

		foreach ($group as $column) {
			$groupSQL .= $this->wrap($column) . ' ';
		}

		return $groupSQL;
	}

	public function parseOrder($order) {
		if (is_null($order)) {
			return '';
		}

		$orderSQL = ' order by ';

		foreach ($order as $column => $sort) {
			if (is_array($sort)) {
				$sort = join(', ' . $this->wrap($column) . ' ', $sort);
			}

			$orderSQL .= "{$this->wrap($column)} $sort,";
		}

		return rtrim($orderSQL, ',');
	}

	public function parseLimit($limit, $offset) {
		if (is_null($limit)) {
			return '';
		}

		return is_null($offset) ? ' limit ' . $limit : ' limit ' . $offset . ',' . $limit;
	}
}
