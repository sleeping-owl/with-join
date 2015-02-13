<?php

use Illuminate\Database\Capsule\Manager as Capsule;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Capsule
	 */
	protected $capsule;

	protected function setUp()
	{
		parent::setUp();
		$this->createConnection();
		$this->fillWithData();
		$this->capsule->getConnection()->flushQueryLog();
	}

	protected function tearDown()
	{
		//var_dump($this->capsule->getConnection()->getQueryLog());
		parent::tearDown();
		$this->eraseData();
	}

	protected function fillWithData()
	{
		$foos = [
			'First Foo',
			'Second Foo',
			'Third Foo'
		];
		foreach ($foos as $foo)
		{
			Foo::create([
				'title' => $foo
			]);
		}

		$bazs = [
			'First Baz',
			'Second Baz',
			'Third Baz'
		];
		foreach ($bazs as $baz)
		{
			Baz::create([
				'title' => $baz
			]);
		}

		$bars = [
			[
				'foo_id' => 1,
				'baz_id' => 1,
				'title'  => 'First Foo First Baz Bar'
			],
			[
				'foo_id' => 1,
				'baz_id' => 2,
				'title'  => 'First Foo Second Baz Bar'
			]
		];
		foreach ($bars as $bar)
		{
			Bar::create($bar);
		}

		$boms = [
			[
				'bar_id' => 1,
				'title'  => 'First Bar Bom'
			],
			[
				'bar_id' => 2,
				'title'  => 'Second Bar Bom'
			]
		];
		foreach ($boms as $bom)
		{
			Bom::create($bom);
		}

	}

	protected function eraseData()
	{
		Foo::truncate();
		Bar::truncate();
		Baz::truncate();
		Bom::truncate();
	}

	protected function createConnection()
	{
		$this->capsule = new Capsule;

		$this->capsule->addConnection([
			'driver'   => 'sqlite',
			'database' => __DIR__ . '/db/testing.sqlite',
			'prefix'   => '',
		]);

		$this->capsule->setFetchMode(PDO::FETCH_CLASS);

		$this->capsule->setAsGlobal();

		$this->capsule->bootEloquent();
		$this->capsule->getConnection()->enableQueryLog();
	}

	protected function assertQuery($query)
	{
		$log = $this->capsule->getConnection()->getQueryLog();

		foreach ($log as $logEntry)
		{
			if ($logEntry['query'] === $query)
			{
				$this->assertTrue(true);
				return;
			}
		}
		$this->assertTrue(false, 'Query [' . $query . '] wasn`t called');
	}

	protected function assertQueryCount($expectedCount)
	{
		$log = $this->capsule->getConnection()->getQueryLog();
		$this->assertEquals($expectedCount, count($log), 'Expected ' . $expectedCount . ' queries, got ' . count($log));
	}

}

class Cache
{

	public static function get($key)
	{
		$filepath = __DIR__ . '/db/columns.dat';
		$cache = [];
		if (file_exists($filepath))
		{
			$cache = unserialize(file_get_contents($filepath));
		}
		if (isset($cache[$key]))
		{
			return $cache[$key];
		}
		return null;
	}

	public static function put($key, $data)
	{
		$filepath = __DIR__ . '/db/columns.dat';
		$cache = [];
		if (file_exists($filepath))
		{
			$cache = unserialize(file_get_contents($filepath));
		}
		$cache[$key] = $data;
		file_put_contents($filepath, serialize($cache));
	}
}