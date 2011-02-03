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
 * Unit test suite for Phergie_Plugin_Filter_Simple.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_Filter_SimpleTest extends PHPUnit_Framework_TestCase
{
    /**
     * SimpleFilter instance being tested
     *
     * @var Phergie_Plugin_Filter_Simple
     */
    protected $filter;

    /**
     * List of mock plugin instances
     *
     * @var array
     */
    protected $plugins;

    /**
     * Initializes the filtter instance being tested.
     *
     * @return void
     */
    public function setUp()
    {
        $this->plugins = array();
        foreach (range(0, 4) as $index) {
            $plugin = $this->getMock('Phergie_Plugin_Abstract');
            $plugin
                ->expects($this->any())
                ->method('getName')
                ->will($this->returnValue($index));
            $this->plugins[] = $plugin;
        }

        $this->filter = new Phergie_Plugin_Filter_Simple;
    }

	/**
	 * Returns the names of plugins that will be accepted by the filter
	 *
	 * @return array
	 */
	protected function getFilteredList()
	{
		$filtered = array();
		foreach ($this->plugins as $plugin) {
			if ($this->filter->accept($plugin)) {
				$filtered[] = $plugin->getName();
			}
		}
		return $filtered;
	}

    /**
     * Tests that all plugins are iterated when no filters are applied.
     *
     * @return void
     */
    public function testIteratesAllPluginsWithNoFilters()
    {
        $expected = range(0, 4);
        $actual = $this->getFilteredList();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that appropriate plugins are iterated when plugin name filters
     * are applied.
     *
     * @return void
     */
    public function testIteratesPluginsWithNameFilters()
    {
        // Test acceptance of strings and fluent interface implementation
        $returned = $this->filter->addPluginFilter('0');
        $this->assertSame($this->filter, $returned);

        // Test acceptance of arrays
        $this->filter->addPluginFilter(array('1', '3'));

        // Test application of filters to iteration
        $expected = array('2', '4');
        $actual = $this->getFilteredList();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that appropriate plugins are iterated when method name filters
     * are applied.
     *
     * The same method name is used in all cases here because mocked methods
     * of mock objects do not appear to be detected by method_exists() or
     * ReflectionClass, so filtering by a method defined in the base plugin
     * class seems the easiest way to test that method filtering really
     * works.
     *
     * @return void
     */
    public function testIteratesPluginsWithMethodFilters()
    {
        // Tests acceptance of strings and fluent interface implementation
        $returned = $this->filter->addMethodFilter('getName');
        $this->assertSame($this->filter, $returned);

        // Test acceptance of arrays
        $this->filter->addMethodFilter(array('getName', 'getName'));

        // Test application of filters to iteration
        $expected = array();
        $actual = $this->getFilteredList();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that all plugins are iterated after filters are cleared.
     *
     * @depends testIteratesPluginsWithNameFilters
     * @depends testIteratesPluginsWithMethodFilters
     *
     * @return void
     */
    public function testIteratesPluginsAfterClearingFilters()
    {
        $this->filter->addPluginFilter('0');
        $this->filter->addMethodFilter('method1');
        $this->filter->clearFilters();

        $expected = range(0, 4);
        $actual = $this->getFilteredList();
        $this->assertEquals($expected, $actual);
    }
}
