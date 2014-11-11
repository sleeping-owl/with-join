<?php namespace SleepingOwl\WithJoin;

use Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithJoinEloquentBuilder extends Builder
{
	/**
	 * @var array
	 */
	protected $references = [];

	/**
	 * @param array $columns
	 * @return \Illuminate\Database\Eloquent\Model[]
	 */
	public function getModels($columns = ['*'])
	{
		foreach ($this->eagerLoad as $name => $constraints)
		{
			if ($this->isNestedRelation($name) || count($this->nestedRelations($name)) > 0)
			{
				continue;
			}
			$relation = $this->getRelation($name);
			if ($this->isReferencedInQuery($name) && $this->isRelationSupported($relation))
			{
				$this->disableEagerLoad($name);
				$this->addJoinToQuery($name, $relation);
			}
		}
		$this->selectFromQuery($this->model->getTable(), '*');
		return parent::getModels($columns);
	}

	/**
	 * @param $relation
	 * @return bool
	 */
	protected function isNestedRelation($relation)
	{
		return strpos($relation, '.') !== false;
	}

	/**
	 * @param $name
	 * @param BelongsTo $relation
	 */
	protected function addJoinToQuery($name, BelongsTo $relation)
	{
		$foreignTable = $relation->getRelated()->getTable();
		$columns = $this->getColumns($foreignTable);
		$this->query->leftJoin($foreignTable . ' as ' . $name, $name . '.' . $relation->getOtherKey(), '=', $relation->getForeignKey());
		foreach ($columns as $column)
		{
			$this->selectFromQuery($name, $column, '_foreign_' . $name . '.' . $column);
		};
	}

	/**
	 * @param $name
	 * @return bool
	 */
	protected function isReferencedInQuery($name)
	{
		return in_array($name, $this->references);
	}

	/**
	 * @param $relation
	 * @return bool
	 */
	protected function isRelationSupported($relation)
	{
		return $relation instanceof BelongsTo;
	}

	/**
	 * @param $name
	 */
	protected function disableEagerLoad($name)
	{
		unset($this->eagerLoad[$name]);
	}

	/**
	 * @param $table
	 * @param $column
	 * @param null $as
	 */
	protected function selectFromQuery($table, $column, $as = null)
	{
		$string = implode('.', [$table, $column]);
		if ( ! is_null($as))
		{
			$string .= ' as ' . $as;
		}
		$this->query->addSelect($string);
	}

	/**
	 * @param array $relations
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|static
	 */
	public function references($relations)
	{
		if ( ! is_array($relations))
		{
			$relations = func_get_args();
		}
		$this->references = $relations;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getReferences()
	{
		return $this->references;
	}

	/**
	 * @param $table
	 * @return array
	 */
	protected function getColumns($table)
	{
		$cacheKey = '_columns_' . $table;
		if ($columns = Cache::get($cacheKey))
		{
			return $columns;
		}
		$columns = $this->model->getConnection()->getSchemaBuilder()->getColumnListing($table);
		Cache::put($cacheKey, $columns, 1440);
		return $columns;
	}

}