<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

$additionalColumns = [
    'podigee_thumbnail' => [
        'exclude' => true,
        'label' => 'LLL:EXT:podigee/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.podigee_thumbnail',
        'config' => [
            'type' => 'link',
            'allowedTypes' => ['url'],
            'readOnly' => true,
            'size' => 40,
        ],
        'displayCond' => 'USER:Ayacoo\\Podigee\\Tca\\DisplayCond\\IsPodigee->match',
    ],
];

ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $additionalColumns);
ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    '--div--;LLL:EXT:podigee/Resources/Private/Language/locallang_db.xlf:tab.podigee, podigee_thumbnail'
);
