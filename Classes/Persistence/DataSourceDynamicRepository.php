<?php

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\MissingClassException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class DataSourceDynamicRepository implements DataSourceInterface, ConfigurableInterface
{
    use ConfigurableTrait;

    const LOGICAL_AND = 'and';
    const LOGICAL_OR = 'or';
    const LOGICAL_NOT = 'not';

    const GREATER_THAN = 'greaterthan';
    const LESS_THAN = 'lessthan';

    const GREATER_THAN_OR_EQUALS = 'greaterthanorequals';
    const LESS_THAN_OR_EQUALS = 'lessthanorequals';

    const LIKE = 'like';
    const EQUALS = 'equals';
    const CONTAINS = 'contains';
    const IN = 'in';

    const OPERAND_NOW = 'now';
    const OPERAND_YESTERDAY = 'yesterday';
    const OPERAND_TODAY = 'today';
    const OPERAND_TOMORROW = 'tomorrow';

    /**
     * temporal operands
     */
    const TEMPORAL_OPERANDS = [
        self::OPERAND_NOW, self::OPERAND_YESTERDAY, self::OPERAND_TODAY, self::OPERAND_TOMORROW
    ];

    /**
     * Tells if a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */

    public function isConfigurationValid(array $configuration): bool
    {
        if (!empty($configuration['class'])) {
            return true;
        }
        return false;
    }

    /**
     * Fetches records from a data source.
     *
     * @param array $configuration Source query configuration
     * @return array Array of records or empty array
     */
    public function getRecords(array $configuration)
    {
        $class = $configuration['class'];
        $repository = $this->getRepositoryFromEntityClass($class);


        return $this->fetchResultWithRepository($repository, $configuration);
    }

    /**
     * @param $entityClassName
     * @return Repository object
     * @throws MissingClassException
     */
    private function getRepositoryFromEntityClass($entityClassName)
    {
        $result = $this->findRepositoryByManipulateEntityName($entityClassName);

        if (!$result) {
            throw new MissingClassException('Entity: ' . $entityClassName . 'could not be resolved');
        }

        return $result;
    }

    /**
     * @param $entityClassName
     * @return Repository object
     * @throws MissingClassException
     */
    private function findRepositoryByManipulateEntityName($entityClassName)
    {
        $entityClassName .= 'Repository';
        $entityRepositoryName = str_replace('\\Model\\', '\\Repository\\', $entityClassName);

        /**
         * Note: We use ObjectManager here in order to simplify DI for Repositories
         * fixme: find a solution with
         */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $result = GeneralUtility::makeInstance($entityRepositoryName, $objectManager);
        if (method_exists($result, 'injectPersistenceManager')) {
            $persistenceManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);
            $result->injectPersistenceManager($persistenceManager);
        }

        if (!$result) {
            throw new MissingClassException('Repository: ' . $entityClassName . 'could not be resolved');
        }

        return $result;
    }

    /**
     * @param Repository $repository
     * @param array $config
     *
     * @return QueryResultInterface
     */
    private function fetchResultWithRepository($repository, $config)
    {
        $query = $repository->createQuery();
        $this->configStoragePids($query, $config);
        $this->configLanguageIds($query, $config);

        $constraints = [];
        if (!empty($config['constraints'])) {
            $constraints = $config['constraints'];
        }

        if (!empty($constraints) && is_array($constraints)) {
            // if no logical conjunction is set as first AND ONLY element
            // set and to default
            reset($constraints);
            $firstKey = strtolower(key($constraints));
            if (count($constraints) != 1 ||
                (
                    count($constraints) == 1 &&
                    (
                        $firstKey !== self::LOGICAL_AND &&
                        $firstKey !== self::LOGICAL_OR
                    )
                )
            ) {
                $constraints = [self::LOGICAL_AND => $constraints];
            }
        }


        $constrainsObjects = $this->generateConstrainForQuery($query, $constraints);
        if (count($constrainsObjects) > 0) {
            $query->matching($constrainsObjects[0]);
        }

        // limit and offset
        if (!empty($config['limit'])) {
            $query->setLimit((int)$config['limit']);
        }

        if (!empty($config['offset'])) {
            $query->setOffset((int)$config['offset']);
        }

        return $query->execute();
    }

    /**
     * @param QueryInterface $query
     * @param array $config
     */
    private function configStoragePids($query, $config)
    {
        if (!empty($config['storagePids'])) {
            // remove all space char's and explode the pid string (comma separated)
            $pids = GeneralUtility::intExplode(',', $config['storagePids'], true);
            $querySettings = $query->getQuerySettings();
            if (!empty($pids)) {
                $querySettings->setRespectStoragePage(true);
                $querySettings->setStoragePageIds($pids);
                $query->setQuerySettings($querySettings);

                return;
            }
        }
        $query->getQuerySettings()->setRespectStoragePage(false);
    }

    /**
     * @param QueryInterface $query
     * @param array $config
     */
    private function configLanguageIds($query, $config)
    {
        if (!empty($config['languageId'])) {
            // remove all space char's and explode the pid string (comma separated)
            $langUid = (int)$config['languageId'];
            if (!empty($langUid)) {
                $query->getQuerySettings()->setRespectSysLanguage(false);
                $query->getQuerySettings()->setLanguageUid($langUid);

                return;
            }
        }
        $query->getQuerySettings()->setRespectSysLanguage(true);
    }

    /**
     * @param QueryInterface $query
     * @param $config
     *
     * @return array
     */
    private function generateConstrainForQuery($query, $config, $negative = false)
    {
        $constrains = [];
        foreach ($config as $key => $value) {
            // LOGICAL CONJUNCTION
            if (strtolower($key) === self::LOGICAL_AND) {
                $subConstrains = $this->generateConstrainForQuery($query, $config[$key], $negative);
                if (!empty($subConstrains)) {
                    $constrains[] = $query->logicalAnd($subConstrains);
                }
            } elseif (strtolower($key) === self::LOGICAL_OR) {
                $subConstrains = $this->generateConstrainForQuery($query, $config[$key], $negative);
                if (!empty($subConstrains)) {
                    $constrains[] = $query->logicalOr($subConstrains);
                }
            } elseif (strtolower($key) === self::LOGICAL_NOT) {
                $subConstrains = $this->generateConstrainForQuery($query, $config[$key], !$negative);
                if (!empty($subConstrains)) {
                    $constrains = array_merge($constrains, $subConstrains);
                }
            }

            // LOGICAL CRITERION
            if (strtolower($key) === self::EQUALS) {
                foreach ($value as $propertyName => $operand) {
                    /** @var ComparisonInterface $constrain */
                    $constrain = $query->equals($propertyName, $this->replaceOperandPlaceholder($operand));
                    if ($negative) {
                        $constrain = $query->logicalNot($constrain);
                    }
                    $constrains[] = $constrain;
                }
            } elseif (strtolower($key) === self::LIKE) {
                foreach ($value as $propertyName => $operand) {
                    /** @var ComparisonInterface $constrain */
                    $constrain = $query->like($propertyName, $this->replaceOperandPlaceholder($operand));
                    if ($negative) {
                        $constrain = $query->logicalNot($constrain);
                    }
                    $constrains[] = $constrain;
                }
            } elseif (strtolower($key) === self::CONTAINS) {
                foreach ($value as $propertyName => $operand) {
                    /** @var ComparisonInterface $constrain */
                    $constrain = $query->contains($propertyName, $this->replaceOperandPlaceholder($operand));
                    if ($negative) {
                        $constrain = $query->logicalNot($constrain);
                    }
                    $constrains[] = $constrain;
                }
            } elseif (strtolower($key) === self::IN) {
                foreach ($value as $propertyName => $operand) {
                    $operand = GeneralUtility::intExplode(',', $operand, true);
                    $transformedOperands = [];
                    foreach ($operand as $value) {
                        $transformedOperands[] = $this->replaceOperandPlaceholder($value);
                    }
                    /** @var ComparisonInterface $constrain */
                    $constrain = $query->in($propertyName, $transformedOperands);
                    if ($negative) {
                        $constrain = $query->logicalNot($constrain);
                    }
                    $constrains[] = $constrain;
                }
            } elseif (strtolower($key) === self::GREATER_THAN) {
                foreach ($value as $propertyName => $operand) {
                    /** @var ComparisonInterface $constrain */
                    $constrain = $query->greaterThan($propertyName, $this->replaceOperandPlaceholder($operand));
                    if ($negative) {
                        $constrain = $query->logicalNot($constrain);
                    }
                    $constrains[] = $constrain;
                }
            } elseif (strtolower($key) === self::LESS_THAN) {
                foreach ($value as $propertyName => $operand) {
                    /** @var ComparisonInterface $constrain */
                    $constrain = $query->lessThan($propertyName, $this->replaceOperandPlaceholder($operand));
                    if ($negative) {
                        $constrain = $query->logicalNot($constrain);
                    }
                    $constrains[] = $constrain;
                }
            } elseif (strtolower($key) === self::GREATER_THAN_OR_EQUALS) {
                foreach ($value as $propertyName => $operand) {
                    /** @var ComparisonInterface $constrain */
                    $constrain = $query->greaterThanOrEqual($propertyName, $this->replaceOperandPlaceholder($operand));
                    if ($negative) {
                        $constrain = $query->logicalNot($constrain);
                    }
                    $constrains[] = $constrain;
                }
            } elseif (strtolower($key) === self::LESS_THAN_OR_EQUALS) {
                foreach ($value as $propertyName => $operand) {
                    /** @var ComparisonInterface $constrain */
                    $constrain = $query->lessThanOrEqual($propertyName, $this->replaceOperandPlaceholder($operand));
                    if ($negative) {
                        $constrain = $query->logicalNot($constrain);
                    }
                    $constrains[] = $constrain;
                }
            }
        }

        return $constrains;
    }

    /**
     * @param string $operand
     * @return mixed
     */
    private function replaceOperandPlaceholder($operand)
    {
        $lowerCaseOperand = strtolower($operand);
        if (in_array($lowerCaseOperand, static::TEMPORAL_OPERANDS)) {
            $timeZone = new \DateTimeZone(date_default_timezone_get());
            $dateTime = new \DateTime($lowerCaseOperand, $timeZone);
            $operand = $dateTime->getTimestamp();
        }

        return $operand;
    }
}
