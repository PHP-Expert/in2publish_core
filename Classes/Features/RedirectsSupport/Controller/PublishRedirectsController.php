<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Controller;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Controller\ActionController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PublishRedirectsController extends ActionController
{
    public function indexAction(array $pagination = []): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $query = $connection->getQueryBuilderForTable('sys_redirect');
        $query->select('*')
              ->from('sys_redirect', 'r')
              ->leftJoin('r', 'tx_in2publishcore_pages_redirects_mm', 'mm', 'r.uid = mm.redirect_uid')
              ->where($query->expr()->isNull('mm.redirect_uid'));
        $this->view->assign('query', $query);
        $this->view->assign('pagination', $pagination);
    }
}
