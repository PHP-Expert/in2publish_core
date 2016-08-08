<?php

call_user_func(
    function ($extKey) {
        /**
         * Manually load Spy YAML parser
         */
        if (!class_exists('\Spyc')) {
            $file = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
                $extKey,
                'Resources/Private/Libraries/Spyc/Spyc.php'
            );
            require_once($file);
        }

        // Enable caching
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = array();
    },
    $_EXTKEY
);
