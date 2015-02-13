<?php namespace SleepingOwl\WithJoin;

use Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class WithJoinEloquentBuilder extends Builder
{

	/**
	 * @var string
	 */
	public static $prefix = '__f__';

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
			if ($this->isNestedRelation($name))
			{
				continue;
			}
			$relation = $this->getRelation($name);
			if ($this->isReferencedInQuery($name) && $this->isRelationSupported($relation))
			{
				$this->disableEagerLoad($name);
				$this->addJoinToQuery($name, $this->model->getTable(), $relation);
				$this->addNestedRelations($name, $relation);

				$query = $relation->getQuery()->getQuery();
				$wheres = $query->wheres;
				$bindings = $query->getBindings();
				$this->mergeWheres($wheres, $bindings);
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
	 * @param Relation $relation
	 */
	protected function addNestedRelations($name, Relation $relation)
	{
		$nestedRelations = $this->nestedRelations($name);
		if (count($nestedRelations) <= 0) return;

		$class = $relation->getRelated();
		foreach ($nestedRelations as $nestedName => $nestedConstraints)
		{
			$relation = $class->$nestedName();
			$this->addJoinToQuery($nestedName, $name, $relation, $name . '---' . static::$prefix);
		}
	}

	/**
	 * @param $joinTableAlias
	 * @param $currentTableAlias
	 * @param BelongsTo|Relation $relation
	 * @param string $columnsPrefix
	 */
	protected function addJoinToQuery($joinTableAlias, $currentTableAlias, Relation $relation, $columnsPrefix = '')
	{
		$joinTableName = $relation->getRelated()->getTable();

		$joinTable = implode(' as ', [
			$joinTableName,
			$joinTableAlias
		]);
		$joinLeftCondition = implode('.', [
			$joinTableAlias,
			$relation->getOtherKey()
		]);
		$joinRightCondition = implode('.', [
			$currentTableAlias,
			$relation->getForeignKey()
		]);

		$this->query->leftJoin($joinTable, $joinLeftCondition, '=', $joinRightCondition);

		$columns = $this->getColumns($joinTableName);
		$prefix = static::$prefix . $columnsPrefix . $joinTableAlias . '---';
		foreach ($columns as $column)
		{
			$this->selectFromQuery($joinTableAlias, $column, $prefix . $column);
		}
	}

	/**
	 * @param $name
	 * @return bool
	 */
	protected function isReferencedInQuery($name)
	{
		if (in_array($name, $this->references))
		{
			return true;
		}
		foreach ($this->references as $reference)
		{
			if (strpos($reference, $name . '.') === 0)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $relation
	 * @return bool
	 */
	public function isRelationSupported($relation)
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
		$string = implode('.', [
			$table,
			$column
		]);
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

	/**
	 * Find a model by its primary key.
	 *
	 * @param  mixed $id
	 * @param  array $columns
	 * @return \Illuminate\Database\Eloquent\Model|static|null
	 */
	public function find($id, $columns = ['*'])
	{
		if (is_array($id))
		{
			return $this->findMany($id, $columns);
		}

		$this->query->where($this->model->getQualifiedKeyName(), '=', $id);

		return $this->first($columns);
	}

	/**
	 * @param array $relations
	 * @return \Illuminate\Database\Query\Builder
	 */
	public function with($relations)
	{
		//if passing the relations as arguments, pass on to eloquents with
		if (is_string($relations)) $relations = func_get_args();
		
		$includes = null;
		try
		{
			$includes = $this->getModel()->getIncludes();
		} catch (\BadMethodCallException $e)
		{
		}
		if (is_array($includes))
		{
			$relations = array_merge($relations, $includes);
			$this->references(array_keys($this->parseRelations($relations)));
		}

		parent::with($relations);

		return $this;
	}

}
