<?php

class Foo extends \Illuminate\Database\Eloquent\Model
{
	protected $fillable = [
		'title'
	];
}

class Baz extends \Illuminate\Database\Eloquent\Model
{
	protected $fillable = [
		'title'
	];
}

class Bar extends \Illuminate\Database\Eloquent\Model
{
	use \SleepingOwl\WithJoin\WithJoinTrait;

	protected $fillable = [
		'title',
		'foo_id',
		'baz_id'
	];

	public function foo()
	{
		return $this->belongsTo('Foo');
	}

	public function baz()
	{
		return $this->belongsTo('Baz');
	}

}

class Bom extends \Illuminate\Database\Eloquent\Model
{
	use \SleepingOwl\WithJoin\WithJoinTrait;

	protected $fillable = [
		'title',
		'bar_id'
	];

	public function bar()
	{
		return $this->belongsTo('Bar');
	}

}

class Qux extends Bom
{
	use \SleepingOwl\WithJoin\WithJoinTrait;
	protected $table = 'boms';
	protected $includes = ['bar'];
}

class WithJoinTest extends TestCase
{

	/** @test */
	public function it_doesnt_break_default_behaviour()
	{
		$bar = Bar::with('foo')->find(1);
		$this->assertEquals('First Foo First Baz Bar', $bar->title);
		$this->assertEquals('First Foo', $bar->foo->title);

		$this->assertQuery('select "bars".* from "bars" where "bars"."id" = ? limit 1');
		$this->assertQuery('select * from "foos" where "foos"."id" in (?)');

		$this->assertQueryCount(2);
	}

	/** @test */
	public function it_replaces_subqueries_with_left_join_using_references()
	{
		$bar = Bar::with('foo')->references('foo')->find(1);
		$this->assertEquals('First Foo First Baz Bar', $bar->title);
		$this->assertEquals('First Foo', $bar->foo->title);

		$this->assertQuery('select "foo"."id" as "__f__foo---id", "foo"."title" as "__f__foo---title", "foo"."created_at" as "__f__foo---created_at", "foo"."updated_at" as "__f__foo---updated_at", "bars".* from "bars" left join "foos" as "foo" on "foo"."id" = "bars"."foo_id" where "bars"."id" = ? limit 1');

		$this->assertQueryCount(1);
	}

	/** @test */
	public function it_replaces_subqueries_with_left_join_using_includes()
	{
		$bar = Bar::includes('foo')->find(1);
		$this->assertEquals('First Foo First Baz Bar', $bar->title);
		$this->assertEquals('First Foo', $bar->foo->title);

		$this->assertQuery('select "foo"."id" as "__f__foo---id", "foo"."title" as "__f__foo---title", "foo"."created_at" as "__f__foo---created_at", "foo"."updated_at" as "__f__foo---updated_at", "bars".* from "bars" left join "foos" as "foo" on "foo"."id" = "bars"."foo_id" where "bars"."id" = ? limit 1');

		$this->assertQueryCount(1);
	}

	/** @test */
	public function it_joins_table_by_relation_name_as_alias()
	{
		$bar = Bar::includes('foo')->where('foo.id', '=', 1)->first();
		$this->assertEquals('First Foo First Baz Bar', $bar->title);
		$this->assertEquals('First Foo', $bar->foo->title);

		$this->assertQuery('select "foo"."id" as "__f__foo---id", "foo"."title" as "__f__foo---title", "foo"."created_at" as "__f__foo---created_at", "foo"."updated_at" as "__f__foo---updated_at", "bars".* from "bars" left join "foos" as "foo" on "foo"."id" = "bars"."foo_id" where "foo"."id" = ? limit 1');

		$this->assertQueryCount(1);
	}

	/** @test */
	public function it_support_multiple_flat_joins()
	{
		$bar = Bar::includes('foo', 'baz')->where('foo.id', '=', 1)->where('baz.id', '=', 2)->first();
		$this->assertEquals('First Foo Second Baz Bar', $bar->title);
		$this->assertEquals('First Foo', $bar->foo->title);
		$this->assertEquals('Second Baz', $bar->baz->title);

		$this->assertQuery('select "foo"."id" as "__f__foo---id", "foo"."title" as "__f__foo---title", "foo"."created_at" as "__f__foo---created_at", "foo"."updated_at" as "__f__foo---updated_at", "baz"."id" as "__f__baz---id", "baz"."title" as "__f__baz---title", "baz"."created_at" as "__f__baz---created_at", "baz"."updated_at" as "__f__baz---updated_at", "bars".* from "bars" left join "foos" as "foo" on "foo"."id" = "bars"."foo_id" left join "bazs" as "baz" on "baz"."id" = "bars"."baz_id" where "foo"."id" = ? and "baz"."id" = ? limit 1');

		$this->assertQueryCount(1);
	}

	/** @test */
	public function it_can_combine_with_and_joins()
	{
		$bar = Bar::with('foo', 'baz')->references('baz')->where('baz.id', '=', 2)->first();
		$this->assertEquals('First Foo Second Baz Bar', $bar->title);
		$this->assertEquals('First Foo', $bar->foo->title);
		$this->assertEquals('Second Baz', $bar->baz->title);

		$this->assertQuery('select "baz"."id" as "__f__baz---id", "baz"."title" as "__f__baz---title", "baz"."created_at" as "__f__baz---created_at", "baz"."updated_at" as "__f__baz---updated_at", "bars".* from "bars" left join "bazs" as "baz" on "baz"."id" = "bars"."baz_id" where "baz"."id" = ? limit 1');
		$this->assertQuery('select * from "foos" where "foos"."id" in (?)');

		$this->assertQueryCount(2);
	}

	/** @test */
	public function it_supports_nested_relations()
	{
		$bom = Bom::includes('bar.foo')->where('foo.id', '=', 1)->first();
		$this->assertEquals('First Bar Bom', $bom->title);
		$this->assertEquals('First Foo First Baz Bar', $bom->bar->title);
		$this->assertEquals('First Foo', $bom->bar->foo->title);

		$this->assertQuery('select "bar"."id" as "__f__bar---id", "bar"."title" as "__f__bar---title", "bar"."foo_id" as "__f__bar---foo_id", "bar"."created_at" as "__f__bar---created_at", "bar"."updated_at" as "__f__bar---updated_at", "bar"."baz_id" as "__f__bar---baz_id", "foo"."id" as "__f__bar---__f__foo---id", "foo"."title" as "__f__bar---__f__foo---title", "foo"."created_at" as "__f__bar---__f__foo---created_at", "foo"."updated_at" as "__f__bar---__f__foo---updated_at", "boms".* from "boms" left join "bars" as "bar" on "bar"."id" = "boms"."bar_id" left join "foos" as "foo" on "foo"."id" = "bar"."foo_id" where "foo"."id" = ? limit 1');

		$this->assertQueryCount(1);
	}

	/** @test */
	public function it_stores_related_model_in_relations_field()
	{
		$bom = Bom::includes('bar')->where('bar.id', '=', 1)->first();
		$this->assertArrayNotHasKey('bar', $bom->attributesToArray());
		$this->assertInstanceOf('Bar', $bom->getRelation('bar'));
	}

	/** @test */
	public function it_uses_includes_to_set_references()
	{
		$qux = Qux::where('bar.id', '=', 1)->first();

		$this->assertEquals('First Bar Bom', $qux->title);
		$this->assertEquals('First Foo First Baz Bar', $qux->bar->title);

		$this->assertQuery('select "bar"."id" as "__f__bar---id", "bar"."title" as "__f__bar---title", "bar"."foo_id" as "__f__bar---foo_id", "bar"."created_at" as "__f__bar---created_at", "bar"."updated_at" as "__f__bar---updated_at", "bar"."baz_id" as "__f__bar---baz_id", "boms".* from "boms" left join "bars" as "bar" on "bar"."id" = "boms"."bar_id" where "bar"."id" = ? limit 1');

		$this->assertQueryCount(1);
	}

}
 