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
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Handles on-demand loading of, iteration over, and access to plugins.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_Handler implements IteratorAggregate, Countable
{
    /**
     * Current list of plugin instances
     *
     * @var array
     */
    protected $plugins;

	/**
	 * List of plugin names per connection, to know if a plugin is active/enabled on a given connection
	 *
	 * @var array		array('all' => array('plugins' => PLUGINS..)), 'uniqid1' => array('all' => TRUE|FALSE, 'plugins' => array(PLUGINS..)), 'uniqid2'...)
	 */
	protected $in_connection = array('all' => array('plugins' => array()));

    /**
     * Paths in which to search for plugin class files
     *
     * @var array
     */
    protected $paths;

    /**
     * Flag indicating whether plugin classes should be instantiated on
     * demand if they are requested but no instance currently exists
     *
     * @var bool
     */
    protected $autoload;

    /**
     * Phergie_Config instance that should be passed in to any plugin
     * instantiated within the handler
     *
     * @var Phergie_Config
     */
    protected $config;

    /**
     * Phergie_Event_Handler instance that should be passed in to any plugin
     * instantiated within the handler
     *
     * @var Phergie_Event_Handler
     */
    protected $events;

    /**
     * Array of current filters to be used by our plugins iterator
     *
     * @var array
     */
    protected $filters = array();

	/**
	 * Array of plugins active on the current connection (used to create the iterator)
	 *
	 * @var array
	 */
	protected $active_plugins;

	/**
	 * Currently active connection (i.e. last set, through $this->setConnection)
	 * Will be used to tell the iterator which plugins to filter out by default (those not active for that connection)
	 *
	 * @var Phergie_Connection
	 */
	protected $connection;

    /**
     * Phergie_Ui_Abstract instance to let know of events (e.g. plugin loaded)
     *
     * @var Phergie_Ui_Abstract
     */
    protected $ui;

    /**
     * Constructor to initialize class properties and add the path for core
     * plugins.
     *
     * @param Phergie_Config $config                    configuration to pass
     *        to any instantiated plugin
     * @param Phergie_Event_Handler $events             event handler to pass
     *        to any instantiated plugin
     * @param Phergie_Ui_Abstract $ui                   ui interface, to let
     *        know of events (e.g. plugin loaded...)
     *
     * @return void
     */
    public function __construct(
        Phergie_Config $config,
        Phergie_Event_Handler $events,
        Phergie_Ui_Abstract $ui
    ) {
        $this->config = $config;
        $this->events = $events;
        $this->ui = $ui;

        $this->plugins = array();
        $this->paths = array();
        $this->autoload = false;

        if (!empty($config['plugins.paths'])) {
            foreach ($config['plugins.paths'] as $dir => $prefix) {
                $this->addPath($dir, $prefix);
            }
        }

        $this->addPath(dirname(__FILE__), 'Phergie_Plugin_');
    }


    /**
     * Adds a path to search for plugin class files. Paths are searched in
     * the reverse order in which they are added.
     *
     * @param string $path   Filesystem directory path
     * @param string $prefix Optional class name prefix corresponding to the
     *        path
     *
     * @return Phergie_Plugin_Handler Provides a fluent interface
     * @throws Phergie_Plugin_Exception
     */
    public function addPath($path, $prefix = '')
    {
        if (!is_readable($path)) {
            throw new Phergie_Plugin_Exception(
                'Path "' . $path . '" does not reference a readable directory',
                Phergie_Plugin_Exception::ERR_DIRECTORY_NOT_READABLE
            );
        }

        $this->paths[] = array(
            'path' => rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            'prefix' => $prefix
        );

        return $this;
    }

    /**
     * Returns metadata corresponding to a specified plugin.
     *
     * @param string $plugin Short name of the plugin class
     *
     * @throws Phergie_Plugin_Exception Class file can't be found
     *
     * @return array|boolean Associative array containing the path to the
     *         class file and its containing directory as well as the full
     *         class name
     */
    public function getPluginInfo($plugin)
    {
        foreach (array_reverse($this->paths) as $path) {
            $file = $path['path'] . $plugin . '.php';
            if (file_exists($file)) {
                $path = array(
                    'dir' => $path['path'],
                    'file' => $file,
                    'class' => $path['prefix'] . $plugin,
                );
                return $path;
            }
        }

        // If the class can't be found, display an error
        throw new Phergie_Plugin_Exception(
            'Class file for plugin "' . $plugin . '" cannot be found',
            Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND
        );
    }

	/**
	 * Returns whether or not the specified plugin is active in the specified connection
	 *
	 * @param string             $plugin			name of the plugin
	 * @param Phergie_Connection $connection		connection
	 *
	 * @return bool
	 */
	public function isPluginInConnection($plugin, Phergie_Connection $connection = NULL)
	{
		$plugin = strtolower($plugin);
		// if we're searching for a plugin in a connection w/out a definition, it means it uses the "global" plugins
		if (!isset($connection) || !isset($this->in_connection[$uniqid = $connection->getUniqid()]))
		{
			return FALSE !== array_search($plugin, $this->in_connection['all']['plugins']);
		}
		$found = (FALSE !== array_search($plugin, $this->in_connection[$uniqid]['plugins']));
		if (!$found && $this->in_connection[$uniqid]['all'])
		{
			$found = (FALSE !== array_search($plugin, $this->in_connection['all']['plugins']));
		}
		return $found;
	}
	
	/**
	 * Returns whether or not the specified plugin is active in the active connection (i.e. last one set through $this->setConnection)
	 * IMPORTANT: if there is no currently active connection ($this->connection === NULL) then we return TRUE !!
	 * This is because this is called by the iterator to know whether or not the plugin is active in the current
	 * connection, and if not skip it. But if there are no active connection, we want all plugins to be iterated,
	 * not just the ones declared in the global ('all') setting
	 *
	 * @param string $plugin			name of the plugin
	 *
	 * @return bool
	 */
	public function isPluginInActiveConnection($plugin)
	{
		if (isset($this->connection)) {
			return $this->isPluginInConnection($plugin, $this->connection);
		}
		return TRUE;
	}

	/**
	 * Adds a plugin "assignement" to one or more connections
	 *
	 * @param string                   $plugin		name of the plugin
	 * @param Phergie_Connection|array $connections	(array of) connection(s) or their uniqids
	 * @param bool                     $inc_all		whether or not this connection should also include the "global" plugins
	 *
	 * @return void
	 */
	public function addPluginConnection($plugin, $connections = NULL, $inc_all = NULL)
	{
		if (!isset($connections))
		{
			$this->in_connection['all']['plugins'][] = $plugin;
			$this->in_connection['all']['plugins'] = array_unique($this->in_connection['all']['plugins']);
			return;
		}
		
		if (!is_array($connections))
		{
			$connections = array($connections);
		}
		foreach($connections as $connection)
		{
			if ($connection instanceof Phergie_Connection)
			{
				$uniqid = $connection->getUniqid();
			}
			else
			{
				$uniqid = (string) $connection;
			}
			
			if (!isset($this->in_connection[$uniqid]))
			{
				$this->in_connection[$uniqid] = array('all' => FALSE, 'plugins' => array());
			}
			
			if (isset($inc_all))
			{
				$this->in_connection[$uniqid]['all'] = (bool) $inc_all;
			}
			$this->in_connection[$uniqid]['plugins'][] = $plugin;
			$this->in_connection[$uniqid]['plugins'] = array_unique($this->in_connection[$uniqid]['plugins']);
		}
	}

    /**
     * Adds a plugin instance to the handler.
     *
     * @param string|Phergie_Plugin_Abstract $plugin Short name of the
     *        plugin class or a plugin object
     * @param array                          $args   Optional array of
     *        arguments to pass to the plugin constructor if a short name is
     *        passed for $plugin
     * @param Phergie_Connection|array		$connection	host(s) the plugin is active
     * @param bool         					$inc_all	whether or not this connection should also include the "global" plugins
     *
     * @throws Phergie_Plugin_Exception
     * @return Phergie_Plugin_Abstract             New plugin instance
     */
    public function addPlugin($plugin, array $args = null, $connection = NULL, $inc_all = NULL)
    {
    	try {
	        // If a short plugin name is specified...
	        if (is_string($plugin)) {
	            $index = strtolower($plugin);
	            if (isset($this->plugins[$index])) {
	            	$this->addPluginConnection($index, $connection, $inc_all);
	                return $this->plugins[$index];
	            }
	
	            // Attempt to locate and load the class
	            $info = $this->getPluginInfo($plugin);
	            $file = $info['file'];
	            $class = $info['class'];
	            include_once $file;
	            if (!class_exists($class, false)) {
	                throw new Phergie_Plugin_Exception(
	                    'File "' . $file . '" does not contain class "' . $class . '"',
	                    Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND
	                );
	            }
	
	            // Check to ensure the class is a plugin class
	            if (!is_subclass_of($class, 'Phergie_Plugin_Abstract')) {
	                $msg
	                    = 'Class for plugin "' . $plugin .
	                    '" does not extend Phergie_Plugin_Abstract';
	                throw new Phergie_Plugin_Exception(
	                    $msg,
	                    Phergie_Plugin_Exception::ERR_INCORRECT_BASE_CLASS
	                );
	            }
	
	            // Check to ensure the class can be instantiated
	            $reflection = new ReflectionClass($class);
	            if (!$reflection->isInstantiable()) {
	                throw new Phergie_Plugin_Exception(
	                    'Class for plugin "' . $plugin . '" cannot be instantiated',
	                    Phergie_Plugin_Exception::ERR_CLASS_NOT_INSTANTIABLE
	                );
	            }
	
	            // If the class is found, instantiate it
	            if (!empty($args)) {
	                $instance = $reflection->newInstanceArgs($args);
	            } else {
	                $instance = new $class;
	            }
	
	        } elseif ($plugin instanceof Phergie_Plugin_Abstract) {
	            // If a plugin instance is specified...
	
	            // Add the plugin instance to the list of plugins
	            $index = strtolower($plugin->getName());
	            $instance = $plugin;
	        }
	
			$this->addPluginConnection($index, $connection, $inc_all);
			
	        // Configure and initialize the instance
	        $instance->setPluginHandler($this);
	        $instance->setConfig($this->config);
	        $instance->setEventHandler($this->events);
	        $instance->onLoad();
	
	        // Store the instance
	        $this->plugins[$index] = $instance;
        } catch (Phergie_Plugin_Exception $e) {
	        $this->ui->onPluginFailure($plugin, $e->getMessage());
	        throw $e;
	    }

        $this->ui->onPluginLoad($instance->getName());
        return $instance;
    }

    /**
     * Adds multiple plugin instances to the handler.
     *
     * @param array $plugins List of elements where each is of the form
     *        'ShortPluginName' or array('ShortPluginName', array($arg1,
     *        ..., $argN))
     *
     * @return Phergie_Plugin_Handler Provides a fluent interface
     */
    public function addPlugins(array $plugins)
    {
        foreach ($plugins as $plugin) {
            if (is_array($plugin)) {
                $this->addPlugin($plugin[0], $plugin[1]);
            } else {
                $this->addPlugin($plugin);
            }
        }

        return $this;
    }

	/**
	 * Removes a plugin "assignement" to all its connections
	 *
	 * @param string       $plugin		name of the plugin
	 *
	 * @return void
	 */
	protected function _removePlugin($plugin)
	{
		foreach ($this->in_connection as $uniqid => $data)
		{
			if (FALSE !== ($k = array_search($plugin, $this->in_connection[$uniqid]['plugins'])))
			{
				unset($this->in_connection[$uniqid]['plugins'][$k]);
			}
		}
	}

    /**
     * Removes a plugin instance from the handler.
     *
     * @param string|Phergie_Plugin_Abstract $plugin Short name of the
     *        plugin class or a plugin object
     *
     * @return Phergie_Plugin_Handler Provides a fluent interface
     */
    public function removePlugin($plugin)
    {
        if ($plugin instanceof Phergie_Plugin_Abstract) {
            $plugin = $plugin->getName();
        }
        $plugin = strtolower($plugin);

        unset($this->plugins[$plugin]);
        $this->_removePlugin($plugin);

        return $this;
    }

    /**
     * Returns the corresponding instance for a specified plugin, loading it
     * if it is not already loaded and autoloading is enabled.
     *
     * @param string $name Short name of the plugin class
     *
     * @return Phergie_Plugin_Abstract Plugin instance
     */
    public function getPlugin($name)
    {
        // If the plugin is loaded, return the instance
        $lower = strtolower($name);
        if (isset($this->plugins[$lower])) {
            return $this->plugins[$lower];
        }

        // If autoloading is disabled, display an error
        if (!$this->autoload) {
            $msg
                = 'Plugin "' . $name . '" has been requested, ' .
                'is not loaded, and autoload is disabled';
            throw new Phergie_Plugin_Exception(
                $msg,
                Phergie_Plugin_Exception::ERR_PLUGIN_NOT_LOADED
            );
        }

        // If autoloading is enabled, attempt to load the plugin
        $plugin = $this->addPlugin($name);

        // Return the added plugin
        return $plugin;
    }

    /**
     * Returns the corresponding instances for multiple specified plugins,
     * loading them if they are not already loaded and autoloading is
     * enabled.
     *
     * @param array $names Optional list of short names of the plugin
     *        classes to which the returned plugin list will be limited,
     *        defaults to all presently loaded plugins
     *
     * @return array Associative array mapping lowercased plugin class short
     *         names to corresponding plugin instances
     */
    public function getPlugins(array $names = array())
    {
        if (empty($names)) {
            return $this->plugins;
        }

        $plugins = array();
        foreach ($names as $name) {
            $plugins[strtolower($name)] = $this->getPlugin($name);
        }
        return $plugins;
    }

    /**
     * Returns whether or not at least one instance of a specified plugin
     * class is loaded.
     *
     * @param string $name Short name of the plugin class
     *
     * @return bool TRUE if an instance exists, FALSE otherwise
     */
    public function hasPlugin($name)
    {
        return isset($this->plugins[strtolower($name)]);
    }

    /**
     * Sets a flag used to determine whether plugins should be loaded
     * automatically if they have not been explicitly loaded.
     *
     * @param bool $flag TRUE to have plugins autoload (default), FALSE
     *        otherwise
     *
     * @return Phergie_Plugin_Handler Provides a fluent interface.
     */
    public function setAutoload($flag = true)
    {
        $this->autoload = $flag;

        return $this;
    }

    /**
     * Returns the value of a flag used to determine whether plugins should
     * be loaded automatically if they have not been explicitly loaded.
     *
     * @return bool TRUE if autoloading is enabled, FALSE otherwise
     */
    public function getAutoload()
    {
        return $this->autoload;
    }

    /**
     * Allows plugin instances to be accessed as properties of the handler.
     *
     * @param string $name Short name of the plugin
     *
     * @return Phergie_Plugin_Abstract Requested plugin instance
     */
    public function __get($name)
    {
        return $this->getPlugin($name);
    }

    /**
     * Allows plugin instances to be detected as properties of the handler.
     *
     * @param string $name Short name of the plugin
     *
     * @return bool TRUE if the plugin is loaded, FALSE otherwise
     */
    public function __isset($name)
    {
        return $this->hasPlugin($name);
    }

    /**
     * Allows plugin instances to be removed as properties of handler.
     *
     * @param string $name Short name of the plugin
     *
     * @return void
     */
    public function __unset($name)
    {
        $this->removePlugin($name);
    }

    /**
     * Returns the iterator for all currently loaded plugin instances in
     * active connection.
     *
     * @return Phergie_Plugin_Iterator
     */
    public function getIterator()
    {
        if (!isset($this->active_plugins)) {
	        // filter out plugins not in active connection
	        $this->active_plugins = array();
	        foreach ($this->plugins as $plugin) {
	        	if ($this->isPluginInActiveConnection($plugin->getName())) {
	        		$this->active_plugins[] = $plugin;
	        	}
	        }
	    }
        return new Phergie_Plugin_Iterator(new ArrayIterator($this->active_plugins), $this->filters);
    }

	/**
	 * Adds the specified filter, to be used by the plugins iterator
	 * The filter will be used by every iterator from getIterator() as long as
	 * the current connection stays the same (i.e. on setConnection() the
	 * list of filters is reset automatically)
	 *
	 * @param Phergie_Plugin_Filter_Abstract $filter	filter object
	 *
	 * @return Phergie_Plugin_Handler					Provides a fluent API
	 */
	public function addFilter(Phergie_Plugin_Filter_Abstract $filter)
	{
		$this->filters[] = $filter;
		return $this;
	}

    /**
     * Proxies method calls to all plugins containing the called method.
     *
     * @param string $name Name of the method called
     * @param array  $args Arguments passed in the method call
     *
     * @return void
     */
    public function __call($name, array $args)
    {
        // if the currently active connection is being set, we remove the old one,
        // so that the iterator we get will list all plugins -- we want all plugins
        // to be set to the new connection, so that we can know whether or not
        // the plugin is active in the currently active connection, in case
        // plugin foo (not active) was triggered by plugin bar (active), to ensure
        // whatever event plugin foo might generate will be set to a connection where
        // it (foo) is active...
        if ('setConnection' == $name) {
        	// this will have the iterator be based on all plugins
        	$this->connection = NULL;
        	if (isset($this->active_plugins)) {
        		unset($this->active_plugins);
        	}
        	// reset the filters
        	$this->filters = array();
        }
        foreach ($this->getIterator() as $plugin) {
            call_user_func_array(array($plugin, $name), $args);
        }
        // and, we take note of the connection so we can now filter out plugins
        // not active on the connection before we create the iterator
        if ('setConnection' == $name) {
        	$this->connection = $args[0];
        	// again we need to do this now that the connection is known, to
        	// create a new iterator and not re-used the one loaded with all
        	// plugins
        	unset($this->active_plugins);
        }
        return true;
    }

    /**
     * Returns the number of plugins contained within the handler.
     *
     * @return int Plugin count
     */
    public function count()
    {
        return count($this->plugins);
    }
}
