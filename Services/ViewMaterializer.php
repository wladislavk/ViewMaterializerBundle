<?php
namespace VKR\ViewMaterializerBundle\Services;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use VKR\CustomLoggerBundle\Services\CustomLogger;

class ViewMaterializer
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CustomLogger
     */
    protected $customLogger;

    /**
     * @var string[]
     */
    protected $definitions;

    /**
     * @var string
     */
    protected $logFile;

    /**
     * @param EntityManager $entityManager
     * @param CustomLogger $customLogger
     * @param string[] $definitions
     * @param string $logFile
     */
    public function __construct(
        EntityManager $entityManager,
        CustomLogger $customLogger,
        array $definitions,
        $logFile
    ) {
        $this->entityManager = $entityManager;
        $this->customLogger = $customLogger;
        $this->definitions = $definitions;
        $this->logFile = $logFile;
    }

    /**
     * @return bool
     */
    public function materializeViews()
    {
        $conn = $this->entityManager->getConnection();
        $logger = $this->customLogger->setLogger($this->logFile);
        foreach ($this->definitions as $viewName => $viewDefinition) {
            $sql = 'DROP TABLE IF EXISTS ' . $viewName;
            try {
                /** @noinspection PhpInternalEntityUsedInspection */
                $conn->executeUpdate($sql);
            } catch (\Exception $e) {
                $this->logError($logger, $sql, $e);
                return false;
            }
            $sql = 'CREATE TABLE ' . $viewName . ' AS ' . $viewDefinition;
            try {
                /** @noinspection PhpInternalEntityUsedInspection */
                $conn->executeUpdate($sql);
            } catch (\Exception $e) {
                $this->logError($logger, $sql, $e);
                return false;
            }
        }
        return true;
    }

    /**
     * @param Logger $logger
     * @param string $sql
     * @param \Exception $e
     */
    protected function logError(Logger $logger, $sql, \Exception $e)
    {
        $logger->addError("Error while executing query $sql. Exception message: {$e->getMessage()}");
    }
}
