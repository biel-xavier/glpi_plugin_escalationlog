<?php

/**
 * -------------------------------------------------------------------------
 * Tag plugin for GLPI
 * -------------------------------------------------------------------------
 */

use Glpi\Plugin\Hooks;

define('PLUGIN_ESCALATIONLOG_VERSION', '1.0.0');

// Minimal GLPI version, inclusive
define("PLUGIN_ESCALATIONLOG_MIN_GLPI", "10.0.11");
// Maximum GLPI version, exclusive
define("PLUGIN_ESCALATIONLOG_MAX_GLPI", "10.0.99");

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_escalationlog()
{
    /**
     * @var array $PLUGIN_HOOKS
     * @var array $UNINSTALL_TYPES
     * @var array $CFG_GLPI
     */
    global $PLUGIN_HOOKS, $UNINSTALL_TYPES, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['escalationlog'] = true;
    

    if (Plugin::isPluginActive("escalationlog")) {

        $PLUGIN_HOOKS[Hooks::ITEM_ADD]['escalationlog'] = [
            SlaLevel_Ticket::class  => 'plugin_escalationlog_item_add'
        ];

        $PLUGIN_HOOKS[Hooks::ITEM_PURGE]['escalationlog'] = [
            SlaLevel_Ticket::class  => 'plugin_escalationlog_item_purge'
        ];

        $PLUGIN_HOOKS['show_item_stats']['escalationlog']    = [
           'Ticket' => 'plugin_escalationlog_displayTabContent'
        ];


    }   
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_escalationlog()
{
    return [
        'name'       => __('Escalation Log'),
        'version'        => PLUGIN_ESCALATIONLOG_VERSION,
        'author'         => '<a href="">Gabriel Xavier\'</a>',
        'homepage'       => '',
        'license'        => '<a href="' . Plugin::getWebDir('tag') . '/LICENSE" target="_blank">GPLv3+</a>',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_ESCALATIONLOG_MIN_GLPI,
                'max' => PLUGIN_ESCALATIONLOG_MAX_GLPI,
                'dev' => true, //Required to allow 9.2-dev
            ],
        ],
    ];
}
