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
 * Unit test suite for Phergie_Plugin_Handler.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_HandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Plugin handler instance being tested
     *
     * @var Phergie_Plugin_Handler
     */
    protected $handler;

    /**
     * Mock Phergie_Config instance passed to the plugin handler constructor
     *
     * @var Phergie_Config
     */
    protected $config;

    /**
     * Mock Phergie_Event_Handler instance passed to the plugin handler
     * constructor
     *
     * @var Phergie_Event_Handler
     */
    protected $events;

    /**
     * Mock Phergie_Ui_Abstract instance passed to the plugin handler
     * constructor
     *
     * @var Phergie_Ui_Abstract
     */
    protected $ui;

    /**
     * Returns a mock plugin instance.
     *
     * @param string $name    Optional short name for the mock plugin, defaults
     *        to 'TestPlugin'
     * @param array  $methods Optional list of methods to override
     *
     * @return Phergie_Plugin_Abstract
     */
    protected function getMockPlugin($name = 'TestPlugin', array $methods = array())
    {
        $methods[] = 'getName';
        $plugin = $this->getMock('Phergie_Plugin_Abstract', $methods);
        $plugin
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        return $plugin;
    }

    /**
     * Sets up a new handler instance before each test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->config = $this->getMock(
            'Phergie_Config', array('offsetGet', 'offsetExists')
        );
        $this->events = $this->getMock(
            'Phergie_Event_Handler', array('getIterator')
        );
        $this->ui = $this->getMock(
            'Phergie_Ui_Abstract'
        );
        $this->handler = new Phergie_Plugin_Handler(
            $this->config,
            $this->events,
            $this->ui
        );
    }

    /**
     * Tests iterability of the plugin handler.
     *
     * @return void
     */
    public function testImplementsIteratorAggregate()
    {
        $reflection = new ReflectionObject($this->handler);

        $this->assertTrue(
            $reflection->implementsInterface('IteratorAggregate'),
            'Handler does not implement IteratorAggregate'
        );

        $this->assertInstanceOf(
            'Iterator',
            $this->handler->getIterator(),
            'getIterator() must return an iterator'
        );
    }

    /**
     * Tests that a default iterator is returned if none is explicitly set.
     *
     * @return void
     */
    public function testGetIteratorReturnsDefault()
    {
        $this->assertInstanceOf(
            'Phergie_Plugin_Iterator',
            $this->handler->getIterator()
        );
    }

    /**
     * Tests countability of the plugin handler.
     *
     * @return void
     */
    public function testImplementsCountable()
    {
        $reflection = new ReflectionObject($this->handler);

        $this->assertTrue(
            $reflection->implementsInterface('Countable'),
            'Handler does not implement Countable'
        );

        $this->assertInternalType(
            'int',
            count($this->handler),
            'count() must return an integer'
        );
    }

    /**
     * Tests the plugin handler exposing added plugins as instance
     * properties of the handler via isset().
     *
     * @return void
     */
    public function testImplementsIsset()
    {
        $pluginName = 'TestPlugin';
        $this->assertFalse(isset($this->handler->{$pluginName}));
        $plugin = $this->getMockPlugin($pluginName);
        $this->handler->addPlugin($plugin);
        $this->assertTrue(isset($this->handler->{$pluginName}));
    }

    /**
     * Tests the plugin handler exposing added plugins as instance
     * properties of the handler.
     *
     * @depends testImplementsIsset
     * @return void
     */
    public function testImplementsGet()
    {
        $plugin = $this->getMockPlugin();
        $this->handler->addPlugin($plugin);
        $name = $plugin->getName();
        $getPlugin = $this->handler->getPlugin($name);
        $this->assertTrue(isset($this->handler->$name));
        $get = $this->handler->$name;
        $this->assertSame($getPlugin, $get);
    }

    /**
     * Tests the plugin handler allowing for plugin removal via unset().
     *
     * @depends testImplementsGet
     * @return void
     */
    public function testImplementsUnset()
    {
        $plugin = $this->getMockPlugin();
        $this->handler->addPlugin($plugin);
        unset($this->handler->{$plugin->getName()});
        $this->assertFalse($this->handler->hasPlugin($plugin->getName()));
    }

    /**
     * Tests the plugin handler executing a callback on all contained
     * plugins.
     *
     * @return void
     */
    public function testImplementsCall()
    {
        foreach (range(1, 2) as $index) {
            $plugin = $this->getMockPlugin('TestPlugin' . $index, array('callback'));
            $plugin
                ->expects($this->once())
                ->method('callback');
            $this->handler->addPlugin($plugin);
        }

        $this->assertTrue($this->handler->callback());
    }

    /**
     * Tests a newly instantiated handler not having plugins associated with
     * it.
     *
     * @depends testImplementsCountable
     * @return void
     */
    public function testEmptyHandlerHasNoPlugins()
    {
        $this->assertEquals(0, count($this->handler));
    }

    /**
     * Tests a newly instantiated handler not having autoloading enabled by
     * default.
     *
     * @return void
     */
    public function testGetAutoloadDefaultsToNotAutoload()
    {
        $this->assertFalse($this->handler->getAutoload());
    }

    /**
     * Tests setAutoload().
     *
     * @depends testGetAutoloadDefaultsToNotAutoload
     * @return void
     */
    public function testSetAutoload()
    {
        $this->assertSame(
            $this->handler->setAutoload(true),
            $this->handler,
            'setAutoload() does not provide a fluent interface'
        );

        $this->assertTrue(
            $this->handler->getAutoload(),
            'setAutoload() had no effect on getAutoload()'
        );
    }

    /**
     * Tests addPath() providing a fluent interface.
     *
     * @return void
     */
    public function testAddPathProvidesFluentInterface()
    {
        $handler = $this->handler->addPath(dirname(__FILE__));
        $this->assertSame($this->handler, $handler);
    }

    /**
     * Tests addPath() throwing an exception when it cannot read the
     * directory.
     *
     * @return void
     */
    public function testAddPathThrowsExceptionOnUnreadableDirectory()
    {
        try {
            $this->handler->addPath('/an/unreadable/directory/path');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_DIRECTORY_NOT_READABLE,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised');
    }

    /**
     * Tests adding a path to the plugin handler.
     *
     * @return void
     */
    public function testAddPath()
    {
        $pluginName = 'Mock';

        try {
            $this->handler->addPlugin($pluginName);
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND,
                $e->getCode()
            );
        }

        if (!isset($e)) {
            $this->fail('Plugin loaded, path was already present');
        }

        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        try {
            $this->handler->addPlugin($pluginName);
        } catch(Phergie_Plugin_Exception $e) {
            $this->fail('Added path, plugin still not found');
        }
    }

    /**
     * Tests addPlugin() returning an added plugin instance.
     *
     * @return void
     */
    public function testAddPluginByInstanceReturnsPluginInstance()
    {
        $plugin = $this->getMockPlugin();
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $returnedPlugin = $this->handler->addPlugin($plugin);
        $this->assertSame(
            $returnedPlugin,
            $plugin,
            'addPlugin() does not return the instance passed to it'
        );
    }

    /**
     * Tests adding a plugin to the handler using the plugin's short name.
     *
     * @return void
     */
    public function testAddPluginByShortName()
    {
        $pluginName = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $returnedPlugin = $this->handler->addPlugin($pluginName);
        $this->assertTrue($this->handler->hasPlugin($pluginName));

        $this->assertInstanceOf(
            'Phergie_Plugin_Mock',
            $this->handler->getPlugin($pluginName)
        );

        $this->assertSame(
            $this->handler->getPlugin($pluginName),
            $returnedPlugin,
            'Handler does not contain added plugin'
        );
    }


    /**
     * Tests adding a plugin instance to the handler.
     *
     * @return void
     */
    public function testAddPluginByInstance()
    {
        $plugin = $this->getMockPlugin();
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $returnedPlugin = $this->handler->addPlugin($plugin);
        $this->assertTrue($this->handler->hasPlugin('TestPlugin'));

        $this->assertSame(
            $plugin,
            $returnedPlugin,
            'addPlugin() does not return added plugin instance'
        );

        $this->assertSame(
            $plugin,
            $this->handler->getPlugin('TestPlugin'),
            'getPlugin() does not return added plugin instance'
        );
    }

    /**
     * Tests addPlugin() throwing an exception when the plugin class file
     * can't be found.
     *
     * @return void
     */
    public function testAddPluginThrowsExceptionWhenPluginFileNotFound()
    {
        $this->ui
        	->expects($this->once())
        	->method('onPluginFailure')
        	;
        try {
            $this->handler->addPlugin('TestPlugin');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised');
    }

    /**
     * Recursively removes all files and subdirectories in a directory.
     *
     * @param string $path Directory path
     *
     * @return void
     */
    private function removeDirectory($path)
    {
        if (file_exists($path)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $entry) {
                if ($it->isDot()) {
                    continue;
                }
                if ($entry->isDir()) {
                    rmdir($entry->getPathname());
                } else {
                    unlink($entry->getPathname());
                }
            }
        }
    }

    /**
     * Tests addPlugin() throwing an exception when the plugin class file is
     * found, but does not contain the plugin class as expected.
     *
     * @return void
     */
    public function testAddPluginThrowsExceptionWhenPluginClassNotFound()
    {
        $path = sys_get_temp_dir() . '/Phergie/Plugin';
        $this->removeDirectory(dirname($path));
        mkdir($path, 0777, true);
        touch($path . '/TestPlugin.php');
        $this->handler->addPath($path, 'Phergie_Plugin_');

        $this->ui
        	->expects($this->once())
        	->method('onPluginFailure')
        	;
        try {
            $this->handler->addPlugin('TestPlugin');
        } catch(Phergie_Plugin_Exception $e) {

        }

        if (isset($e)) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND,
                $e->getCode()
            );
        } else {
            $this->fail('An expected exception has not been raised');
        }

        $this->removeDirectory(dirname($path));
    }

    /**
     * Tests addPlugin() throwing an exception when trying to instantiate a
     * class that doesn't extend Phergie_Plugin_Abstract.
     *
     * @return void
     */
    public function testAddPluginThrowsExceptionIfRequestingNonPlugin()
    {
        $this->ui
        	->expects($this->once())
        	->method('onPluginFailure')
        	;
        try {
            $this->handler->addPlugin('Handler');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_INCORRECT_BASE_CLASS,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised');
    }

    /**
     * Tests addPlugin() throwing an exception when trying to instantiate a
     * class that can't be instantiated.
     *
     * @return void
     */
    public function testAddPluginThrowsExceptionIfPluginNotInstantiable()
    {
        $this->ui
        	->expects($this->once())
        	->method('onPluginFailure')
        	;
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');
        try {
            $this->handler->addPlugin('TestNonInstantiablePluginFromFile');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_INSTANTIABLE,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised');
    }

    /**
     * Tests adding a plugin by its short name with arguments passed to the
     * plugin constructor.
     *
     * @return void
     */
    public function testAddPluginShortNamePassesArgsToConstructor()
    {
        $pluginName = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $arguments = array('a', 'b', 'c');
        $plugin = $this->handler->addPlugin($pluginName, $arguments);

        $this->assertAttributeSame(
            $arguments,
            'arguments',
            $plugin,
            'Arguments do not match'
        );
    }

    /**
     * Tests addPlugin() passing Phergie_Config to an instantiated plugin.
     *
     * @return void
     */
    public function testAddPluginPassesConstructorArguments()
    {
        $pluginName = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $plugin = $this->handler->addPlugin($pluginName);

        $this->assertSame(
            $this->config,
            $plugin->getConfig(),
            'Phergie_Config instances do not match'
        );

        $this->assertSame(
            $this->events,
            $plugin->getEventHandler(),
            'Phergie_Event_Handler instances do not match'
        );
    }

    /**
     * Tests addPlugin() calling onLoad() on an instantiated plugin.
     *
     * @return void
     */
    public function testAddPluginCallsOnLoadOnInstantiatedPlugin()
    {
        $plugin = $this->getMockPlugin(null, array('onLoad'));
        $plugin
            ->expects($this->once())
            ->method('onLoad');
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $this->handler->addPlugin($plugin);
    }

    /**
     * Tests addPlugin() returning the same plugin when called twice.
     *
     * @return void
     */
    public function testAddPluginReturnsSamePluginWhenAskedTwice()
    {
        $pluginName = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $plugin1 = $this->handler->addPlugin($pluginName);
        $plugin2 = $this->handler->addPlugin($pluginName);
        $this->assertSame($plugin1, $plugin2);
    }

    /**
     * Tests getPlugin() throwing an exception when trying to get an
     * unloaded plugin with autoload disabled.
     *
     * @depends testGetAutoloadDefaultsToNotAutoload
     * @return void
     */
    public function testExceptionThrownWhenLoadingPluginWithoutAutoload()
    {
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        try {
            $this->handler->getPlugin('Mock');
        } catch (Phergie_Plugin_Exception $expected) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_PLUGIN_NOT_LOADED,
                $expected->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised');
    }

    /**
     * Tests that an added plugin is not added to the plugin handler if an
     * exception occurs while attempting to add it.
     *
     * @return void
     */
    public function testAddPluginFailsIfExceptionOccurs()
    {
        $plugin = $this->getMock(
            'Phergie_Plugin_Abstract',
            array(),
            array(),
            'Phergie_Plugin_ThrowsException'
        );
        $plugin
            ->expects($this->once())
            ->method('onLoad')
            ->will($this->throwException(new Phergie_Plugin_Exception));
        $this->ui
        	->expects($this->once())
        	->method('onPluginFailure')
        	;
        try {
            $this->handler->addPlugin($plugin);
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Plugin_Exception $e) {
            if ($this->handler->hasPlugin('ThrowsException')) {
                $this->fail('Plugin handler added plugin');
            }
        }
    }

    /**
     * Tests addPlugins() with a plugin short name and no plugin constructor
     * arguments.
     *
     * @depends testAddPluginByShortName
     * @depends testAddPluginByInstance
     * @return void
     */
    public function testAddPluginsWithoutArguments()
    {
        $prefix = 'Phergie_Plugin_';
        $this->handler->addPath(dirname(__FILE__), $prefix);

        $plugin = 'Mock';
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $this->handler->addPlugins(array($plugin));
        $returnedPlugin = $this->handler->getPlugin($plugin);
        $this->assertContains(
            get_class($returnedPlugin),
            $prefix . $plugin,
            'Short name plugin not of expected class'
        );
    }

    /**
     * Tests addPlugins() with a plugin short name and plugin constructor
     * arguments.
     *
     * @depends testAddPluginByShortName
     * @depends testAddPluginByInstance
     * @return void
     */
    public function testAddPluginsWithArguments()
    {
        $prefix = 'Phergie_Plugin_';
        $this->handler->addPath(dirname(__FILE__), $prefix);

        $arguments = array(1, 2, 3);
        $plugin = array('Mock', $arguments);
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $this->handler->addPlugins(array($plugin));
        $returnedPlugin = $this->handler->getPlugin('Mock');
        $this->assertEquals(
            $arguments,
            $returnedPlugin->getArguments(),
            'Constructor arguments for instance plugin do not match'
        );
    }

    /**
     * Tests removePlugin() with a plugin instance.
     *
     * @depends testAddPluginByInstance
     * @return void
     */
    public function testRemovePluginByInstance()
    {
        $plugin = $this->getMockPlugin();
        $this->handler->addPlugin($plugin);
        $this->handler->removePlugin($plugin);
        $this->assertFalse(
            $this->handler->hasPlugin($plugin->getName()),
            'Plugin was not removed'
        );
    }

    /**
     * Tests removePlugin() with a plugin short name.
     *
     * @depends testAddPluginByShortName
     * @return void
     */
    public function testRemovePluginByShortName()
    {
        $plugin = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        $this->handler->addPlugin($plugin);
        $this->handler->removePlugin($plugin);
        $this->assertFalse(
            $this->handler->hasPlugin($plugin),
            'Plugin was not removed'
        );
    }

    /**
     * Tests getPlugin() when the plugin is not already loaded and
     * autoloading is enabled.
     *
     * @depends testSetAutoload
     * @return void
     */
    public function testGetPluginWithAutoloadEnabled()
    {
        $this->handler->setAutoload(true);
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;
        $plugin = $this->handler->getPlugin('Mock');
        $this->assertInstanceOf(
            'Phergie_Plugin_Mock',
            $plugin,
            'Retrieved plugin not of expected class'
        );
    }

    /**
     * Tests getPlugins().
     *
     * @depends testGetPluginWithAutoloadEnabled
     * @return void
     */
    public function testGetPlugins()
    {
        $this->ui
        	->expects($this->exactly(2))
        	->method('onPluginLoad')
        	;

        $plugin1 = $this->getMockPlugin('TestPlugin1');
        $this->handler->addPlugin($plugin1);

        $plugin2 = $this->getMockPlugin('TestPlugin2');
        $this->handler->addPlugin($plugin2);

        $expected = array(
            'testplugin1' => $plugin1,
            'testplugin2' => $plugin2,
        );

        $actual = $this->handler->getPlugins();
        $this->assertEquals($expected, $actual);

        $actual = $this->handler->getPlugins(array('testplugin1', 'testplugin2'));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that multiple plugin iterators can be used concurrently.
     *
     * @return void
     */
    public function testUseMultiplePluginIteratorsConcurrently()
    {
        $this->ui
        	->expects($this->exactly(2))
        	->method('onPluginLoad')
        	;

        $plugin1 = $this->getMockPlugin('TestPlugin1');
        $this->handler->addPlugin($plugin1);

        $plugin2 = $this->getMockPlugin('TestPlugin2');
        $this->handler->addPlugin($plugin2);

        $iterator1 = $this->handler->getIterator();
        $iterator1->next();
        $this->assertSame($plugin2, $iterator1->current());

        $iterator2 = $this->handler->getIterator();
        $this->assertSame($plugin1, $iterator2->current());
        $this->assertSame($plugin2, $iterator1->current());

        $iterator3 = $this->handler->getIterator();
        $this->assertSame($plugin1, $iterator3->current());

        $iterator2->next();
        $this->assertSame($plugin2, $iterator2->current());
        $this->assertSame($plugin1, $iterator3->current());
    }

    /**
     * Tests adding plugin paths via configuration.
     *
     * @return void
     */
    public function testAddPluginPathsViaConfiguration()
    {
        $dir = dirname(__FILE__);
        $prefix = 'Phergie_Plugin_';
        $paths = array($dir => $prefix);
        $this->config
            ->expects($this->any())
            ->method('offsetExists')
            ->will($this->returnValue(true));
        $this->config
            ->expects($this->any())
            ->method('offsetGet')
            ->will($this->returnValue($paths));
        $this->ui
        	->expects($this->once())
        	->method('onPluginLoad')
        	;

        // Reinitialize the handler so the configuration change takes effect
        // within the constructor
        $this->handler = new Phergie_Plugin_Handler(
            $this->config,
            $this->events,
            $this->ui
        );

        $this->handler->setAutoload(true);
        $this->handler->getPlugin('Mock');
    }
}
