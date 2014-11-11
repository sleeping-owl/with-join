<?php namespace SleepingOwl\WithJoin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait WithJoinTrait {

	/**
	 * @param array $attributes
	 * @return static
	 */
	public function newFromBuilder($attributes = [])
	{
		$prefix = '_foreign_';
		$foreignData = [];
		foreach ($attributes as $key => $value)
		{
			if (strpos($key, $prefix) === 0)
			{
				unset($attributes->$key);
				$key = substr($key, strlen($prefix));
				list($table, $key) = explode('.', $key, 2);
				if ( ! isset($foreignData[$table]))
				{
					$foreignData[$table] = [];
				}
				$foreignData[$table][$key] = $value;
			}
		}
		foreach ($foreignData as $relation => $data)
		{
			/** @var BelongsTo $relationInstance */
			$relationInstance = $this->$relation();
			$foreign = $relationInstance->getRelated()->newFromBuilder($data);
			$attributes->$relation = $foreign;
		}
		return parent::newFromBuilder($attributes);
	}

	/**
	 * @param  \Illuminate\Database\Query\Builder $query
	 * @return WithJoinEloquentBuilder
	 */
	public function newEloquentBuilder($query)
	{
		return new WithJoinEloquentBuilder($query);
	}

	/**
	 * Being querying a model with eager loading.
	 *
	 * @param  array|string  $relations
	 * @return \Illuminate\Database\Eloquent\Builder|WithJoinEloquentBuilder|static
	 */
	public static function with($relations)
	{
		if (is_string($relations)) $relations = func_get_args();
		return parent::with($relations);
	}

}