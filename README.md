## Package to convert Eloquent BelongsTo subqueries into one query with left join

### Usage

Mark relation you want to convert into left join using `->references($relations)` method or `Model::includes($relations)` method:

```php
Model::with('other')->references('other')->orderBy('other.title', 'asc')->get();
 # this will make one sql-query with left join of 'other' relation
 # result object will be the same object
 
Model::includes('other', 'another')->where('other.title', '=', 'my title')->get();
 # will be the same as Model::with('other', 'another')->references('other', 'another')->…

Model::with('other')->orderBy('field', 'asc')->get();
 # this will work with default behaviour (perform 2 sql-queries)
```

### Object Structure

You will get the same object if you will use `includes()` method. For example:

```php
StreetImage::includes('street')->first()
```

will return:

```
object(StreetImage) {
	<all street image attributes>
	street: object(Street) {
		<all street attributes>
	}
}
```

Structure will be the same even if you using nested relations:

```php
StreetImage::includes('street.district')->first();
```

will return:

```
object(StreetImage) {
	<all street image attributes>
	street: object(Street) {
		<all street attributes>
		district: object(District) {
			<all district attributes>
		}
	}
}
```

### Nested Relations

```php
StreetImage::includes('street.type', 'street.district')->first();
```

will perform a following sql-query (*<…> will be replaced with all table columns*):

```sql
select
	`streets`.`<…>` as `_foreign_street.<…>`, 
	`street_types`.`<…>` as `_foreign_street._foreign_type.<…>`, 
	`districts`.`<…>` as `_foreign_street._foreign_district.<…>`, 
	`street_images`.* 
from 
	`street_images` 
left join 
	`streets` on `streets`.`id` = `street_images`.`street_id` 
left join 
	`street_types` on `street_types`.`id` = `streets`.`street_type_id` 
left join 
	`districts` on `districts`.`id` = `streets`.`district_id` 
order by `sort` asc
limit 1
```
instead of performing 4 sql-queries by default Eloquent behaviour:

```sql
select `street_images`.* from `street_images` order by `sort` asc limit 1

select `streets`.* from `streets` where `streets`.`id` in (?) order by `title` asc

select * from `street_types` where `street_types`.`id` in (?) order by `title` asc

select * from `districts` where `districts`.`id` in (?) order by `sort` asc
```

### Installation

 1. Require this package in your composer.json and run composer update (or run `composer require sleeping-owl/with-join:1.x` directly):

		"sleeping-owl/with-join": "1.*"

2. Use `\SleepingOwl\WithJoin\WithJoinTrait` trait in every eloquent model you want to use this package features:

	```php
	class StreetImage extends \Eloquent
	{
		use \SleepingOwl\WithJoin\WithJoinTrait;
	}
	```
	
3. That`s all.

## Copyright and License

Admin was written by Sleeping Owl for the Laravel framework and is released under the MIT License. See the LICENSE file for details.
