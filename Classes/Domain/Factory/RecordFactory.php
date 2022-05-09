<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory;

/*
 * Copyright notice
 *
 * (c) 2022 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\TcaHandling\RecordIndex;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\MmDatabaseRecord;
use In2code\In2publishCore\Domain\Model\PageTreeRootRecord;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Event\DecideIfRecordShouldBeIgnored;
use In2code\In2publishCore\Service\Configuration\IgnoredFieldsService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

class RecordFactory
{
    protected IgnoredFieldsService $ignoredFieldsService;
    protected RecordIndex $recordIndex;
    protected EventDispatcher $eventDispatcher;

    public function injectIgnoredFieldsService(IgnoredFieldsService $ignoredFieldsService): void
    {
        $this->ignoredFieldsService = $ignoredFieldsService;
    }

    public function injectRecordIndex(RecordIndex $recordIndex): void
    {
        $this->recordIndex = $recordIndex;
    }

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createDatabaseRecord(
        string $table,
        int $id,
        array $localProps,
        array $foreignProps
    ): ?DatabaseRecord {
        $tableIgnoredFields = $this->ignoredFieldsService->getIgnoredFields($table);
        $record = new DatabaseRecord($table, $id, $localProps, $foreignProps, $tableIgnoredFields);
        $this->recordIndex->addRecord($record);

        if ($this->shouldIgnoreRecord($record)) {
            return null;
        }

        return $record;
    }

    public function createMmRecord(
        string $table,
        string $propertyHash,
        array $localProps,
        array $foreignProps
    ): ?MmDatabaseRecord {
        $record = new MmDatabaseRecord($table, $propertyHash, $localProps, $foreignProps);
        $this->recordIndex->addRecord($record);

        if ($this->shouldIgnoreRecord($record)) {
            return null;
        }

        return $record;
    }

    protected function shouldIgnoreRecord(Record $record): bool
    {
        $event = new DecideIfRecordShouldBeIgnored($record);
        $this->eventDispatcher->dispatch($event);
        return $event->shouldBeIgnored();
    }

    public function createPageTreeRootRecord(): PageTreeRootRecord
    {
        $record = new PageTreeRootRecord();
        $this->recordIndex->addRecord($record);
        return $record;
    }
}
