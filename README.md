## Package to convert BelongsTo subqueries into one query with left join

### Usage

```php
\Model::with('other')->references('other')->orderBy('other.title', 'asc')->get();
 # this will make one sql-query with left join of 'other' relation. result object will be the same.

\Model::with('other')->orderBy('field', 'asc')->get();
 # this will work with default behaviour (perform 2 sql-queries)
```

### Installation

1. Use `\SleepingOwl\WithJoin\WithJoinTrait` trait in your eloquent model:

	```php
	class StreetImage extends \Eloquent
	{
		use \SleepingOwl\WithJoin\WithJoinTrait;
	}
	```
	
2. That`s all.

### Todo

- Nested relations support
- Other relation types support

## Copyright and License

Admin was written by Sleeping Owl for the Laravel framework and is released under the MIT License. See the LICENSE file for details.
