<?php
/**
 * Phergie
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie
 * @package   Phergie_Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Emply class used to test Phergie_Plugin_Abstract
 */
class Phergie_Plugin_Empty extends Phergie_Plugin_Abstract { }

/**
 * Unit test suite for Phergie_Plugin_Abstract.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_AbstractTest extends Phergie_Plugin_TestCase
{
	/**
	 * Name of the class of the plugin we're testing
	 *
	 * @var string
	 */
	protected $pluginClass = 'Phergie_Plugin_Empty';

	/**
	 * Tests we can get the connection object
	 *
	 * @return void
	 */
	public function testGetConnection()
	{
		$this->assertSame($this->getMockConnection(), $this->plugin->getConnection());
	}

	/**
	 * Tests we can set the connection object
	 *
	 * @return void
	 * @depends testGetConnection
	 */
	public function testSetConnection()
	{
		$connection = $this->getMock('Phergie_Connection');
		$this->assertSame($this->plugin, $this->plugin->setConnection($connection));
		$this->assertSame($connection, $this->plugin->getConnection());
		// restore
		$this->plugin->setConnection($this->getMockConnection());
		$this->assertSame($this->getMockConnection(), $this->plugin->getConnection());
	}

	/**
	 * Tests we can use getConfig() to get config
	 *
	 * @return void
	 */
	public function testGetConfigFound()
	{
		$this->setConfig('empty.nb', 23);
		$this->assertEquals(23, $this->plugin->getConfig('empty.nb'));
	}

	/**
	 * Tests we can use getConfig() with plugin auto-added as prefix
	 *
	 * @return void
	 * @depends testGetConfigFound
	 */
	public function testGetConfigFoundWithAutoPrefix()
	{
		$this->setConfig('empty.nb', 42);
		$this->assertEquals(42, $this->plugin->getConfig('nb'));
	}

	/**
	 * Tests we can use getConfig() to get main values (w/ dot as prefix)
	 *
	 * @return void
	 * @depends testGetConfigFound
	 */
	public function testGetConfigFoundByPassAutoPrefix()
	{
		$this->setConfig('empty.nb', 42);
		$this->setConfig('nb', 108);
		$this->assertEquals(108, $this->plugin->getConfig('.nb'));
	}

	/**
	 * Tests we can use getConfig() and get default value
	 *
	 * @return void
	 * @depends testGetConfigFound
	 */
	public function testgetConfigNotFoundReturnsDefault()
	{
		$this->assertEquals(4, $this->plugin->getConfig('empty.fake', 4));
		$this->assertEquals(4, $this->plugin->getConfig('fake', 4));
	}

	/**
	 * Tests we can use getConfig() to get the config object
	 *
	 * @return void
	 */
	public function testGetConfigObject()
	{
		$this->assertSame($this->config, $this->plugin->getConfig());
	}

	/**
	 * Tests we can set the config object
	 *
	 * @return void
	 * @depends testGetConfigObject
	 */
	public function testSetConfigObject()
	{
		$config = $this->getMock('Phergie_Config');
		$this->assertSame($this->plugin, $this->plugin->setConfig($config));
		$this->assertSame($config, $this->plugin->getConfig());
		// restore
		$this->plugin->setConfig($this->config);
		$this->assertSame($this->config, $this->plugin->getConfig());
	}

	/**
	 * Tests getConfig() does call config->getSetting() w/ the active connection
	 *
	 * @return void
	 * @depends testSetConfigObject
	 * @depends testSetConnection
	 */
	public function testGetConfigUsesActiveConnection()
	{
		$connection = $this->getMockConnection();
		$config = $this->getMock('Phergie_Config', array('getSetting'));
		$config
			->expects($this->once())
			->method('getSetting')
			->with($this->equalTo('empty.name'), $this->equalTo($connection))
			->will($this->returnValue(15))
			;
		$this->plugin->setConfig($config);
		$this->plugin->setConnection($connection);
		$this->assertEquals(15, $this->plugin->getConfig('name'));
		$this->plugin->setConfig($this->config);
	}
}
