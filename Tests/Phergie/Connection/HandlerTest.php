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
 * Unit test suite for Phergie_Connection_Handler.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Connection_HandlerTest extends Phergie_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Connection_Handler
     */
    protected $connections;

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->connections = new Phergie_Connection_Handler;
    }

    /**
     * Tests that the class implements the Countable interface.
     *
     * @return void
     */
    public function testImplementsCountable()
    {
        $this->assertContains(
            'Countable', class_implements(get_class($this->connections))
        );
    }

    /**
     * Tests that the class implements the IteratorAggregate interface.
     *
     * @return void
     */
    public function testImplementsIteratorAggregate()
    {
        $this->assertContains(
            'IteratorAggregate', class_implements(get_class($this->connections))
        );
    }

    /**
     * Tests adding a connection.
     *
     * @return void
     * @depends testImplementsCountable
     * @depends testImplementsIteratorAggregate
     */
    public function testAddConnection()
    {
        $connection = $this->getMockConnection();

        $this->assertEquals(0, count($this->connections));
        $this->connections->addConnection($connection);
        $this->assertEquals(1, count($this->connections));

        foreach ($this->connections as $entry) {
            $this->assertSame($connection, $entry);
        }
    }

    /**
     * Tests removing a connection by specifying the connection instance.
     *
     * @return void
     * @depends testAddConnection
     */
    public function testRemoveConnectionByInstance()
    {
        $connection = $this->getMockConnection();
        $this->connections->addConnection($connection);
        $this->connections->removeConnection($connection);
        $this->assertEquals(0, count($this->connections));
    }

    /**
     * Tests removing a connection by specifying the connection uniqid
     * when the connection is present.
     *
     * @return void
     * @depends testAddConnection
     */
    public function testRemoveConnectionByUniqidWithConnectionPresent()
    {
        $uniqid = uniqid('', TRUE);
        $connection = $this->getMockConnection();
        $connection
            ->expects($this->any())
            ->method('getUniqid')
            ->will($this->returnValue($uniqid));

        $this->connections->addConnection($connection);
        $this->connections->removeConnection($connection->getUniqid());
        $this->assertEquals(0, count($this->connections));
    }

    /**
     * Tests that removing a connection by specifying the connection
     * uniqid when the connection is not present.
     *
     * @return void
     * @depends testAddConnection
     */
    public function testRemoveConnectionByUniqidWithConnectionAbsent()
    {
        $this->connections->removeConnection('foo');
    }

    /**
     * Tests retrieving a list of connections when none have been added.
     *
     * @return void
     */
    public function testGetConnectionsWithNoConnections()
    {
        $this->assertSame(array(), $this->connections->getConnections());
    }

    /**
     * Tests retrieving a single connection by its uniqid.
     *
     * @return void
     */
    public function testGetConnectionsWithSingleConnection()
    {
        $uniqid = uniqid('', TRUE);

        $connection = $this->getMockConnection();
        $connection
            ->expects($this->any())
            ->method('getUniqid')
            ->will($this->returnValue($uniqid));

        $this->connections->addConnection($connection);
        $connections = $this->connections->getConnections($uniqid);
        $this->assertInternalType('array', $connections);
        $this->assertSame(1, count($connections));
        $this->assertArrayHasKey($uniqid, $connections);
        $this->assertSame($connection, $connections[$uniqid]);
    }

    /**
     * Tests retrieving multiple connections by their uniqids.
     *
     * @return void
     */
    public function testGetConnectionsWithMultipleConnections()
    {
        $uniqids = $connections = array();
        $connection = $this->getMockConnection();
        foreach (range(1, 2) as $index) {
            $uniqids[$index] = uniqid('', TRUE);
            $connections[$index] = clone $connection;
            $connections[$index]
                ->expects($this->any())
                ->method('getUniqid')
                ->will($this->returnValue($uniqids[$index]));
            $this->connections->addConnection($connections[$index]);
        }
        $returned = $this->connections->getConnections($uniqids);
        $this->assertInternalType('array', $returned);
        $this->assertEquals(2, count($returned));
        foreach ($uniqids as $index => $uniqid) {
            $this->assertArrayHasKey($uniqid, $returned);
            $this->assertSame($connections[$index], $returned[$uniqid]);
        }
    }
}
