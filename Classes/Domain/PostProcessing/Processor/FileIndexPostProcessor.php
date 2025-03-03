<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\PostProcessing\Processor;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Domain\Factory\FileIndexFactory;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Utility\StorageDriverExtractor;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReflectionException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_chunk;

class FileIndexPostProcessor implements PostProcessor, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ResourceFactory */
    protected $resourceFactory;

    public function __construct(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * @param RecordInterface[] $records
     * @throws ReflectionException
     */
    public function postProcess(array $records): void
    {
        /** @var RecordInterface[][] $sortedRecords */
        $sortedRecords = [];
        /** @var array<ResourceStorage> $storages */
        $storages = [];
        $skipStorages = [];
        foreach ($records as $record) {
            if (null === $uid = $record->getLocalProperty('storage')) {
                $uid = $record->getForeignProperty('storage');
            }
            if (isset($skipStorages[$uid])) {
                $skipStorages[$uid][] = $record->getTableName() . '[' . $record->getIdentifier() . ']';
                continue;
            } elseif (!isset($storages[$uid])) {
                try {
                    $storages[$uid] = $this->resourceFactory->getStorageObject($uid);
                } catch (InvalidArgumentException $exception) {
                    $skipStorages[$uid] = [];
                    $skipStorages[$uid][] = $record->getTableName() . '[' . $record->getIdentifier() . ']';
                    $this->logger->critical(
                        'Could not fetch storage for file, skipping file and any further request to get storage',
                        [
                            'record_table' => $record->getTableName(),
                            'record_uid' => $record->getIdentifier(),
                            'storage_uid' => $uid,
                        ]
                    );
                    continue;
                }
            }
            $sortedRecords[$uid][] = $record;
        }

        if (!empty($skipStorages)) {
            $logData = [];
            foreach ($skipStorages as $storageUid => $skippedFiles) {
                $logData[$storageUid]['storage'] = $storageUid;
                $logData[$storageUid]['files'] = $skippedFiles;
            }
            $this->logger->info('Statistics of skipped files per unavailable storage', $logData);
        }

        $this->prefetchForeignInformationFiles($storages, $sortedRecords);

        foreach ($sortedRecords as $storageIndex => $recordArray) {
            $fileIndexFactory = GeneralUtility::makeInstance(
                FileIndexFactory::class,
                StorageDriverExtractor::getLocalDriver($storages[$storageIndex]),
                StorageDriverExtractor::getForeignDriver($storages[$storageIndex])
            );
            foreach ($recordArray as $record) {
                if ($record->hasLocalProperty('identifier')) {
                    $localIdentifier = $record->getLocalProperty('identifier');
                } else {
                    $localIdentifier = $record->getForeignProperty('identifier');
                }
                if ($record->hasForeignProperty('identifier')) {
                    $foreignIdentifier = $record->getForeignProperty('identifier');
                } else {
                    $foreignIdentifier = $record->getLocalProperty('identifier');
                }
                $fileIndexFactory->updateFileIndexInfo($record, $localIdentifier, $foreignIdentifier);
                $record->addAdditionalProperty('isAuthoritative', true);
            }
        }
    }

    /**
     * @param array<ResourceStorage> $storages
     * @param RecordInterface[][] $sortedRecords
     */
    protected function prefetchForeignInformationFiles(array $storages, array $sortedRecords): void
    {
        $foreignIdentifiers = [];
        foreach ($sortedRecords as $storageIndex => $recordArray) {
            foreach ($recordArray as $record) {
                if ($record->hasForeignProperty('identifier')) {
                    $foreignIdentifier = $record->getForeignProperty('identifier');
                } else {
                    $foreignIdentifier = $record->getLocalProperty('identifier');
                }
                $foreignIdentifiers[$storageIndex][] = $foreignIdentifier;
            }
        }
        foreach ($foreignIdentifiers as $storageIndex => $identifierArray) {
            foreach (array_chunk($identifierArray, 500) as $fragmentedArray) {
                StorageDriverExtractor::getForeignDriver($storages[$storageIndex])
                                      ->batchPrefetchFiles($fragmentedArray);
            }
        }
    }
}
