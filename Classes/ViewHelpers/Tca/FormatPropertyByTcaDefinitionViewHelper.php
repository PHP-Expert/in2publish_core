<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Tca;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function nl2br;
use function strftime;
use function trim;

class FormatPropertyByTcaDefinitionViewHelper extends AbstractViewHelper
{
    protected $tableConfiguration = [];

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('fieldName', 'string', 'The field name to get the localized label from', true);
        $this->registerArgument('tableName', 'string', 'The table name in which the field can be found', true);
    }

    /**
     * Get formatted output by TCA definition
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function render(): string
    {
        $fieldName = $this->arguments['fieldName'];
        $tableName = $this->arguments['tableName'];

        $this->tableConfiguration = $GLOBALS['TCA'][$tableName]['columns'][$fieldName];
        $value = trim($this->renderChildren());

        if (empty($this->tableConfiguration['config']['type'])) {
            return $value;
        }

        switch ($this->tableConfiguration['config']['type']) {
            case 'input':
                $this->changeValueForTypeInput($value);
                break;
            case 'text':
                $this->changeValueForTypeText($value);
                break;
            default:
        }

        $this->setValueToNoValue($value);

        return $value;
    }

    protected function changeValueForTypeInput(&$value): void
    {
        $eval = $this->tableConfiguration['config']['eval'] ?? null;
        if (null === $eval) {
            return;
        }

        if (
            GeneralUtility::inList($eval, 'datetime')
            || GeneralUtility::inList($eval, 'date')
        ) {
            if ($value !== '0') {
                $value = strftime('%d.%m.%Y', (int)$value);
            }
        }
        if (GeneralUtility::inList($eval, 'password')) {
            $value = '*******';
        }
    }

    protected function changeValueForTypeText(&$value): void
    {
        $value = nl2br($value);
    }

    protected function setValueToNoValue(&$value): void
    {
        if (empty($value)) {
            $value = '-';
        }
    }
}
