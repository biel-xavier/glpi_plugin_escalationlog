<?php

/**
 * -------------------------------------------------------------------------
 * Escalation Log plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Escalation Log.
 *
 * Tag is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Escalation Log is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Escalation Log. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2014-2023 by Teclib'.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://
 * -------------------------------------------------------------------------
 */

function plugin_escalationlog_install() {
    global $DB;

    $query = "
    CREATE TABLE IF NOT EXISTS `glpi_plugin_escalation_logs` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `tickets_id` INT NOT NULL,
        `slalevels_id` INT NOT NULL,
        `escalated` TINYINT(1) NOT NULL CHECK (escalated IN (0, 1)), 
        `pending_validation` TINYINT(1) NOT NULL CHECK (pending_validation IN (0, 1)), 
        `escalated_at` TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $DB->query($query);


    CronTask::Register(
        'PluginEscalationlogEscalationCron',
        'validateEscalations',
        86400,
        ['mode' => 2, 'allowmode' => 3, 'logs_lifetime' => 30]
    );

    return true;
}

function plugin_escalationlog_uninstall() {
    global $DB;
    $DB->query("DROP TABLE IF EXISTS `glpi_plugin_escalation_logs`;");
    
    CronTask::unregister(
        'PluginEscalationlogEscalationCron',
        'validateEscalations'
    );

    return true;
}

function plugin_escalationlog_item_purge($item) {

    global $DB;

    $getLastEscalationForTicket = $DB->request([
        'FROM' => 'glpi_plugin_escalation_logs',
        'WHERE' => [
            'tickets_id' => $item->fields['tickets_id'],
            'slalevels_id' => $item->fields['slalevels_id']
        ],
        'ORDER' => ['id DESC'],
        'LIMIT' => 1
    ]);

    
    $dataLastEscalation = iterator_to_array($getLastEscalationForTicket, false);


    if(
        empty($dataLastEscalation)
    ) {
        return;
    }

    $DB->update(
        'glpi_plugin_escalation_logs', 
        [
            'pending_validation' => 1
        ],
        [
            'tickets_id' => $dataLastEscalation[0]['tickets_id'],
            'slalevels_id' => $dataLastEscalation[0]['slalevels_id']
        ]
    );
    
}

function plugin_escalationlog_item_add($item) {
    global $DB;

    $verifyTypeSLA = $DB->request([
        'SELECT' => [
            'glpi_slas.id AS sla_id',
            'glpi_slas.type AS sla_type',
            'glpi_slalevels.id AS slalevels_id'
        ],
        'FROM' => 'glpi_slalevels',
        'INNER JOIN' => [
            'glpi_slas' => [
                'ON' => [
                    'glpi_slas' => 'id',
                    'glpi_slalevels' => 'slas_id'
                ]
            ]
        ],
        'WHERE' => [
            'glpi_slalevels.id' => $item->fields['slalevels_id']
        ]
    ]);



    $dataVerifyTypeSLA = iterator_to_array($verifyTypeSLA, false);

    Toolbox::logInFile(
        'php-errors',
        'Verify Type SLA: ' . print_r($dataVerifyTypeSLA, true)
    );
    
    if (empty($dataVerifyTypeSLA) || $dataVerifyTypeSLA[0]['sla_type'] == 1) {
        return;
    }

    $verifyEscalationForTicket = $DB->request([
        'FROM' => 'glpi_plugin_escalation_logs',
        'WHERE' => [
            'tickets_id' => $item->fields['tickets_id'],
            'slalevels_id' => $item->fields['slalevels_id']     
        ],
        'ORDER' => ['id DESC'],
        'LIMIT' => 1
    ]);

    
    $dataVerifyEscalation = iterator_to_array($verifyEscalationForTicket, false);
    
   
    if(!empty($dataVerifyEscalation)) {

        $ticketData = $DB->request([
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                'id' => $dataVerifyEscalation[0]['tickets_id']     
            ]
        ]);
        $ticketDetail = iterator_to_array($ticketData, false);


        if(
            !empty($ticketDetail) &&
            in_array($ticketDetail[0]['status'], [4,5,6]) 
            && $dataVerifyEscalation[0]['pending_validation'] == 1
        ) {
            $DB->update(
                    'glpi_plugin_escalation_logs',
                    [
                        'pending_validation' => 0
                    ],
                    ['id' => $dataVerifyEscalation[0]['id']]
                );
        }

        return;
    }
    
    $DB->insert(
        'glpi_plugin_escalation_logs', 
        [
            'tickets_id'    => $item->fields['tickets_id'],
            'slalevels_id'  => $item->fields['slalevels_id'],
            'pending_validation' => 0,
            'escalated' => 0
        ]
    );
}

function plugin_escalationlog_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '10.0.0', '<')) {
        return false;
    }
    return true;
}



function plugin_escalationlog_getCronTasks() {
    return ['PluginEscalationLogEscalationValidator'];
}