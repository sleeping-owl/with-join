## Package to convert Eloquent BelongsTo subqueries into one query with left join

[![Build Status](https://travis-ci.org/sleeping-owl/with-join.svg?branch=master)](https://travis-ci.org/sleeping-owl/with-join)
[![Latest Stable Version](https://poser.pugx.org/sleeping-owl/with-join/v/stable.svg)](https://packagist.org/packages/sleeping-owl/with-join)
[![License](https://poser.pugx.org/sleeping-owl/with-join/license.svg)](https://packagist.org/packages/sleeping-owl/with-join)
[![Code Climate](https://codeclimate.com/github/sleeping-owl/with-join/badges/gpa.svg)](https://codeclimate.com/github/sleeping-owl/with-join)

### Usage

Mark relation you want to convert into left join using `->references($relations)` method or `Model::includes($relations)` method:

```php
Model::with('other')->references('other')->orderBy('other.title', 'asc')->get();
 # this will make one sql-query with left join of 'other' relation
 # result object will be the same object
 
Model::includes('first', 'second')->where('first.title', '=', 'my title')->get();
 # will be the same as Model::with('first', 'second')->references('first', 'second')->…

Model extends Eloquent
{
	$includes = ['first', 'second'];
}
Model::where('first.title', '=', 'my title')->get();
# result is same as Model::includes but definition is done within the model
# if you use $with and $includes together it will be merged

Model::with('foreign')->orderBy('field', 'asc')->get();
 # this will work with default behaviour (perform 2 sql-queries)
```

### Example

#### New Behaviour

```php
StreetImage::includes('street')->first()
```

will perform the following sql-query:

```sql
select 
	`street`.`<…>` as `__f__street---<…>`, 
	`street_images`.* 
from 
	`street_images` 
left join 
	`streets` as `street` on `street`.`id` = `street_images`.`street_id` 
order by `sort` asc 
limit 1
```

#### Default Behaviour

```php
StreetImage::with('street')->first()
```

will perform the following sql-queries:

```sql
select `street_images`.* from `street_images` order by `sort` asc limit 1
select `streets`.* from `streets` where `streets`.`id` in (?) order by `title` asc
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
	 `street`.`<…>` as `__f__street---<…>`,
	 `type`.`<…>` as `__f__street---__f__type---<…>`,
	 `district`.`<…>` as `__f__street---__f__district---<…>`,
	 `street_images`.* 
from 
	`street_images` 
left join 
	`streets` as `street` on `street`.`id` = `street_images`.`street_id` 
left join 
	`street_types` as `type` on `type`.`id` = `street`.`street_type_id` 
left join 
	`districts` as `district` on `district`.`id` = `street`.`district_id` 
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

## Support Library

You can donate in BTC: 13k36pym383rEmsBSLyWfT3TxCQMN2Lekd

## Copyright and License

Package was written by Sleeping Owl for the Laravel framework and is released under the MIT License. See the LICENSE file for details.
