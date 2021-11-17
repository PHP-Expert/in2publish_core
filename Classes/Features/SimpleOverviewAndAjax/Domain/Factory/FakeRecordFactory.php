<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Factory;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Repository\TableCacheRepository;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff;
use function array_merge;
use function strnatcmp;
use function strpos;
use function uasort;

class FakeRecordFactory
{
    public const PAGE_TABLE_NAME = 'pages';

    protected TableCacheRepository $tableCacheRepository;

    protected TcaService $tcaService;

    protected array $config;

    protected array $metaDataBlackList = [];

    public function __construct(
        TableCacheRepository $tableCacheRepository,
        TcaService $tcaService,
        ConfigContainer $configContainer
    ) {
        $this->tableCacheRepository = $tableCacheRepository;
        $this->tcaService = $tcaService;
        $this->config = $configContainer->get();
    }

    /**
     * Build a record tree with a minimum information (try to keep queries reduced)
     */
    public function buildFromStartPage(int $identifier): Record
    {
        $record = $this->getSingleFakeRecordFromPageIdentifier($identifier);
        $this->addRelatedRecords($record);
        return $record;
    }

    /**
     * Add related records and respect level depth
     */
    protected function addRelatedRecords(Record $record, int $currentDepth = 0): void
    {
        $currentDepth++;
        if ($currentDepth < $this->config['factory']['maximumPageRecursion']) {
            foreach ($this->getChildrenPages($record->getIdentifier()) as $pageIdentifier) {
                if ($this->shouldSkipChildrenPage($pageIdentifier)) {
                    $subRecord = $this->getSingleFakeRecordFromPageIdentifier($pageIdentifier);
                    $this->addRelatedRecords($subRecord, $currentDepth);
                    $record->addRelatedRecordRaw($subRecord);
                }
            }
        }
    }

    /** @SuppressWarnings(PHPMD.StaticAccess) */
    protected function getSingleFakeRecordFromPageIdentifier(int $identifier): Record
    {
        $propertiesLocal = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $identifier);
        $propertiesForeign = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $identifier, 'foreign');
        $record = GeneralUtility::makeInstance(
            Record::class,
            'pages',
            $propertiesLocal,
            $propertiesForeign,
            (array)$this->tcaService->getConfigurationArrayForTable('pages'),
            []
        );
        $this->guessState($record);
        return $record;
    }

    /**
     * Try to get state for given record
     */
    protected function guessState(Record $record): void
    {
        if (0 === $record->getIdentifier()) {
            return;
        }

        $localProperties = $record->getLocalProperties();
        $foreignProperties = $record->getForeignProperties();

        if ([] === $localProperties && [] !== $foreignProperties) {
            $record->setState(RecordInterface::RECORD_STATE_DELETED);
        } elseif ($this->pageIsNew($record)) {
            $record->setState(RecordInterface::RECORD_STATE_ADDED);
        } elseif ($this->pageIsDeletedOnLocalOnly($record->getIdentifier())) {
            $record->setState(RecordInterface::RECORD_STATE_DELETED);
        } elseif ($this->pageHasMoved($record->getIdentifier())) {
            $record->setState(RecordInterface::RECORD_STATE_MOVED);
        } elseif ($this->pageHasChanged($record->getIdentifier()) || $this->pageContentRecordsHasChanged($record)) {
            $record->setState(RecordInterface::RECORD_STATE_CHANGED);
        }
    }

    protected function pageIsNew(Record $record): bool
    {
        $propertiesLocal = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $record->getIdentifier());
        $propertiesForeign = $this->tableCacheRepository->findByUid(
            static::PAGE_TABLE_NAME,
            $record->getIdentifier(),
            'foreign'
        );
        return !empty($propertiesLocal) && empty($propertiesForeign);
    }

    /**
     * Get all page identifiers from sub pages
     *
     * @param int $identifier
     *
     * @return array<int>
     */
    protected function getChildrenPages(int $identifier): array
    {
        $rows = $this->tableCacheRepository->findByPid(static::PAGE_TABLE_NAME, $identifier);
        $rows = $this->sortRowsBySorting($rows);
        $pageIdentifiers = [];
        foreach ($rows as $row) {
            $pageIdentifiers[] = (int)$row['uid'];
        }
        return $pageIdentifiers;
    }

    /**
     * Check if record is deleted and respect delete field from TCA
     */
    protected function isRecordDeleted(
        int $pageIdentifier,
        string $databaseName,
        string $tableName = self::PAGE_TABLE_NAME
    ): bool {
        $tcaTable = $this->tcaService->getConfigurationArrayForTable($tableName);
        if (!empty($tcaTable['ctrl']['delete'])) {
            $properties = $this->tableCacheRepository->findByUid($tableName, $pageIdentifier, $databaseName);
            return $properties[$tcaTable['ctrl']['delete']] === 1;
        }
        return false;
    }

    /**
     * Compare sorting of a page on both sides. Check if it's different
     */
    protected function pageHasMoved(int $pageIdentifier): bool
    {
        $propertiesLocal = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $pageIdentifier);
        $propertiesForeign = $this->tableCacheRepository->findByUid(
            static::PAGE_TABLE_NAME,
            $pageIdentifier,
            'foreign'
        );
        return $propertiesLocal['sorting'] !== $propertiesForeign['sorting']
               || $propertiesLocal['pid'] !== $propertiesForeign['pid'];
    }

    /**
     * Check if this page should be related or not
     */
    protected function shouldSkipChildrenPage(int $pageIdentifier): bool
    {
        return !$this->isRecordDeletedOnBothInstances($pageIdentifier, static::PAGE_TABLE_NAME)
               && !$this->isRecordDeletedOnLocalAndNonExistingOnForeign($pageIdentifier);
    }

    /**
     * Check if page is deleted on local only
     */
    protected function pageIsDeletedOnLocalOnly(int $pageIdentifier): bool
    {
        $deletedLocal = $this->isRecordDeleted($pageIdentifier, 'local');
        if ($deletedLocal) {
            $deletedForeign = $this->isRecordDeleted($pageIdentifier, 'foreign');
            return $deletedForeign === false;
        }
        return false;
    }

    /**
     * Compare rows of a page on both sides. Check if it's different
     */
    protected function pageHasChanged(int $pageIdentifier): bool
    {
        $propertiesLocal = $this->tableCacheRepository->findByUid(static::PAGE_TABLE_NAME, $pageIdentifier);
        $propertiesForeign = $this->tableCacheRepository->findByUid(
            static::PAGE_TABLE_NAME,
            $pageIdentifier,
            'foreign'
        );
        $propertiesLocal = $this->removeIgnoreFieldsFromArray($propertiesLocal, 'pages');
        $propertiesForeign = $this->removeIgnoreFieldsFromArray($propertiesForeign, 'pages');
        $changes = array_diff($propertiesLocal, $propertiesForeign);
        return !empty($changes);
    }

    /**
     * Compare rows of any records on a page. Check if they are different
     */
    protected function pageContentRecordsHasChanged(Record $record): bool
    {
        $tables = $this->tcaService->getAllTableNamesWithPidAndUidField(
            array_merge($this->config['excludeRelatedTables'], ['pages'])
        );
        foreach ($tables as $table) {
            $propertiesLocal = $this->tableCacheRepository->findByPid($table, $record->getIdentifier());
            $propertiesForeign = $this->tableCacheRepository->findByPid($table, $record->getIdentifier(), 'foreign');
            if ($this->areDifferentArrays($propertiesLocal, $propertiesForeign, $table)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if multidimensional array with records is different between instances
     */
    protected function areDifferentArrays(array $arrayLocal, array $arrayForeign, string $table): bool
    {
        $newLocal = $newForeign = [];

        // remove sys file entries from local extensions and their sys_file_metadata records
        if ('sys_file' === $table) {
            foreach ($arrayLocal as $index => $localSysFile) {
                if (!isset($arrayForeign[$index]) && 0 === strpos($localSysFile['identifier'], '/typo3conf/ext/')) {
                    $this->metaDataBlackList[$index] = $index;
                    unset($arrayLocal[$index]);
                }
            }
        } elseif ('sys_file_metadata' === $table) {
            foreach ($arrayLocal as $index => $localSysFileMeta) {
                if (isset($this->metaDataBlackList[$localSysFileMeta['file']])) {
                    unset($arrayLocal[$index]);
                }
            }
        }

        foreach ($arrayLocal as $subLocal) {
            $subLocal = $this->removeIgnoreFieldsFromArray($subLocal, $table);
            if (
                !$this->isRecordDeletedOnLocalAndNonExistingOnForeign($subLocal['uid'], $table)
                && !$this->isRecordDeletedOnBothInstances($subLocal['uid'], $table)
            ) {
                $newLocal[] = $subLocal;
            }
        }
        foreach ($arrayForeign as $subForeign) {
            $subForeign = $this->removeIgnoreFieldsFromArray($subForeign, $table);
            if (!$this->isRecordDeletedOnBothInstances($subForeign['uid'], $table)) {
                $newForeign[] = $subForeign;
            }
        }
        return $newForeign !== $newLocal;
    }

    /**
     * Sort rows array by sorting field
     */
    protected function sortRowsBySorting(array $rows): array
    {
        uasort(
            $rows,
            static function ($row1, $row2) {
                return strnatcmp((string)$row1['sorting'], (string)$row2['sorting']);
            }
        );
        return $rows;
    }

    /**
     * Respect configuration ignoreFieldsForDifferenceView.[table] and remove these fields
     */
    protected function removeIgnoreFieldsFromArray(array $properties, string $table): array
    {
        if (!empty($this->config['ignoreFieldsForDifferenceView'][$table])) {
            $ignoreFields = $this->config['ignoreFieldsForDifferenceView'][$table];
            $properties = ArrayUtility::removeFromArrayByKey($properties, $ignoreFields);
        }
        return $properties;
    }

    /**
     * Check if record was not generated and at once deleted on local (so it's not existing on foreign)
     */
    protected function isRecordDeletedOnLocalAndNonExistingOnForeign(
        int $identifier,
        string $tableName = self::PAGE_TABLE_NAME
    ): bool {
        if ($this->isRecordDeleted($identifier, 'local', $tableName)) {
            $properties = $this->tableCacheRepository->findByUid($tableName, $identifier, 'foreign');
            if (empty($properties)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if record is deleted on both instances
     */
    protected function isRecordDeletedOnBothInstances(int $identifier, string $tableName): bool
    {
        return $this->isRecordDeleted($identifier, 'local', $tableName)
               && $this->isRecordDeleted($identifier, 'foreign', $tableName);
    }
}
