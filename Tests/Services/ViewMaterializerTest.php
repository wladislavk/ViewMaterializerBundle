<?php
namespace VKR\ViewMaterializerBundle\Tests\Services;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use VKR\CustomLoggerBundle\Services\CustomLogger;
use VKR\ViewMaterializerBundle\Services\ViewMaterializer;

class ViewMaterializerTest extends TestCase
{
    /**
     * @var ViewMaterializer
     */
    private $viewMaterializer;

    /**
     * @var string[]
     */
    private $executedQueries;

    /**
     * @var string
     */
    private $loggedError;

    /**
     * @var string[]
     */
    private $definitions;

    private $logFile = 'my_log';

    public function setUp()
    {
        $this->definitions = [
            'mview_first_view' => 'SELECT a FROM table1',
            'mview_second_view' => 'SELECT b FROM table2',
        ];
        $this->executedQueries = [];
    }

    public function testMaterializeViews()
    {
        $customLogger = $this->mockCustomLogger();
        $entityManager = $this->mockEntityManager(true);
        $this->viewMaterializer = new ViewMaterializer(
            $entityManager, $customLogger, $this->definitions, $this->logFile
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
        $customLogger = $this->mockCustomLogger();
        $entityManager = $this->mockEntityManager(false);
        $this->viewMaterializer = new ViewMaterializer(
            $entityManager, $customLogger, $this->definitions, $this->logFile
        );
        $isSuccessful = $this->viewMaterializer->materializeViews();
        $this->assertFalse($isSuccessful);
        $errorMessage =
"Error while executing query DROP TABLE IF EXISTS mview_first_view.
Exception message: Update failed";
        $this->assertEquals(str_replace("\n", ' ', $errorMessage), $this->loggedError);
    }

    private function mockMonolog()
    {
        $logger = $this->createMock(Logger::class);
        $logger->method('addError')
            ->willReturnCallback([$this, 'loggerAddErrorCallback']);
        return $logger;
    }

    private function mockCustomLogger()
    {
        $customLogger = $this->createMock(CustomLogger::class);
        $customLogger->method('setLogger')->willReturn($this->mockMonolog());
        return $customLogger;
    }

    private function mockEntityManager($isSuccessful)
    {
        $entityManager = $this->createMock(EntityManager::class);
        if ($isSuccessful) {
            $entityManager->method('getConnection')
                ->willReturn($this->mockSuccessfulDoctrineConnection());
            return $entityManager;
        }
        $entityManager->method('getConnection')
            ->willReturn($this->mockFailedDoctrineConnection());
        return $entityManager;
    }

    private function mockSuccessfulDoctrineConnection()
    {
        $successfulDoctrineConnection = $this->createMock(Connection::class);
        $successfulDoctrineConnection->method('executeUpdate')
            ->willReturnCallback([$this, 'successfulExecuteUpdateCallback']);
        return $successfulDoctrineConnection;
    }

    private function mockFailedDoctrineConnection()
    {
        $failedDoctrineConnection = $this->createMock(Connection::class);
        $failedDoctrineConnection->method('executeUpdate')
            ->willReturnCallback([$this, 'failedExecuteUpdateCallback']);
        return $failedDoctrineConnection;
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
