<?php
namespace VKR\ViewMaterializerBundle\Tests\Services;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use VKR\CustomLoggerBundle\Services\CustomLogger;
use VKR\ViewMaterializerBundle\Services\ViewMaterializer;

class ViewMaterializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ViewMaterializer
     */
    protected $viewMaterializer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $successfulDoctrineConnection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $failedDoctrineConnection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customLogger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var string[]
     */
    protected $executedQueries;

    /**
     * @var string
     */
    protected $loggedError;

    /**
     * @var string[]
     */
    protected $definitions;

    protected $logFile = 'my_log';

    public function setUp()
    {
        $this->mockMonolog();
        $this->mockCustomLogger();
        $this->definitions = [
            'mview_first_view' => 'SELECT a FROM table1',
            'mview_second_view' => 'SELECT b FROM table2',
        ];
        $this->executedQueries = [];
    }

    public function testMaterializeViews()
    {
        $this->mockSuccessfulDoctrineConnection();
        $this->mockEntityManager(true);
        $this->viewMaterializer = new ViewMaterializer(
            $this->entityManager, $this->customLogger, $this->definitions, $this->logFile
        );
        $isSuccessful = $this->viewMaterializer->materializeViews();
        $this->assertTrue($isSuccessful);
        $this->assertEquals(4, sizeof($this->executedQueries));
        $expectedDropQuery = 'DROP TABLE IF EXISTS mview_first_view';
        $expectedCreateQuery = 'CREATE TABLE mview_first_view AS SELECT a FROM table1';
        $this->assertEquals($expectedDropQuery, $this->executedQueries[0]);
        $this->assertEquals($expectedCreateQuery, $this->executedQueries[1]);
    }

    public function testMaterializeViewsWithError()
    {
        $this->mockFailedDoctrineConnection();
        $this->mockEntityManager(false);
        $this->viewMaterializer = new ViewMaterializer(
            $this->entityManager, $this->customLogger, $this->definitions, $this->logFile
        );
        $isSuccessful = $this->viewMaterializer->materializeViews();
        $this->assertFalse($isSuccessful);
        $errorMessage =
"Error while executing query DROP TABLE IF EXISTS mview_first_view.
Exception message: Update failed";
        $this->assertEquals(str_replace("\n", ' ', $errorMessage), $this->loggedError);
    }

    protected function mockMonolog()
    {
        $this->logger = $this
            ->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger->expects($this->any())
            ->method('addError')
            ->will($this->returnCallback([$this, 'loggerAddErrorCallback']));
    }

    protected function mockCustomLogger()
    {
        $this->customLogger = $this
            ->getMockBuilder(CustomLogger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customLogger->expects($this->any())
            ->method('setLogger')
            ->will($this->returnValue($this->logger));
    }

    protected function mockEntityManager($isSuccessful)
    {
        $this->entityManager = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        if ($isSuccessful) {
            $this->entityManager->expects($this->any())
                ->method('getConnection')
                ->will($this->returnValue($this->successfulDoctrineConnection));
            return;
        }
        $this->entityManager->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->failedDoctrineConnection));
    }

    protected function mockSuccessfulDoctrineConnection()
    {
        $this->successfulDoctrineConnection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->successfulDoctrineConnection->expects($this->any())
            ->method('executeUpdate')
            ->will($this->returnCallback([$this, 'successfulExecuteUpdateCallback']));
    }

    protected function mockFailedDoctrineConnection()
    {
        $this->failedDoctrineConnection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->failedDoctrineConnection->expects($this->any())
            ->method('executeUpdate')
            ->will($this->returnCallback([$this, 'failedExecuteUpdateCallback']));
    }

    public function successfulExecuteUpdateCallback($sql)
    {
        $this->executedQueries[] = $sql;
    }

    public function failedExecuteUpdateCallback($sql)
    {
        throw new \Exception('Update failed');
    }

    public function loggerAddErrorCallback($message)
    {
        $this->loggedError = $message;
    }
}
