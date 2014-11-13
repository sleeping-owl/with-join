<?php namespace SleepingOwl\WithJoin;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

trait WithJoinTrait
{

	/**
	 * @param array $attributes
	 * @return static
	 */
	public function newFromBuilder($attributes = [])
	{
		$attributes = (array) $attributes;
		$prefix = WithJoinEloquentBuilder::$prefix;
		$foreignData = [];
		foreach ($attributes as $key => $value)
		{
			if (strpos($key, $prefix) === 0)
			{
				unset($attributes[$key]);
				$key = substr($key, strlen($prefix));
				$key = str_replace('---', '.', $key);
				Arr::set($foreignData, $key, $value);
			}
		}
		$instance = parent::newFromBuilder($attributes);
		foreach ($foreignData as $relation => $data)
		{
			/** @var BelongsTo $relationInstance */
			$relationInstance = $this->$relation();
			$foreign = $relationInstance->getRelated()->newFromBuilder($data);
			$instance->setRelation($relation, $foreign);
		}
		return $instance;
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
	 * @param  array|string $relations
	 * @return \Illuminate\Database\Eloquent\Builder|WithJoinEloquentBuilder|static
	 */
	public static function with($relations)
	{
		if (is_string($relations)) $relations = func_get_args();
		return parent::with($relations);
	}

	/**
	 * @param  array|string $relations
	 * @return \Illuminate\Database\Eloquent\Builder|WithJoinEloquentBuilder|static
	 */
	public static function includes($relations)
	{
		if (is_string($relations)) $relations = func_get_args();
		$query = parent::with($relations);
		$query->references($relations);
		return $query;
	}

}