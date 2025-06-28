<?php

class PluginEscalationlogEscalationCron extends CommonDBTM {
       /**
    * The right name for this class
    *
    * @var string
    */
    public static $rightname = 'plugin_glpi_escalationlog';

    public static function getTypeName($nb = 0) {
        return __('validateEscalation', 'escalationlog');
    }

    public static function canRun() {
        return true;
    }

    public static function cronvalidateEscalations(CronTask $task) {
        return self::runCron($task);
    }

    public static function runCron($task) {
        global $DB;

        $query = "
            SELECT el.id, el.tickets_id, t.status
            FROM glpi_plugin_escalation_logs el
            JOIN glpi_tickets t ON t.id = el.tickets_id
            WHERE el.pending_validation = 1
        ";

        foreach ($DB->request($query) as $row) {
            $escalated = !in_array($row['status'], [4, 5, 6]) ? 1 : 0;

            if($escalated == 1) {
                $DB->update(
                    'glpi_plugin_escalation_logs',
                    [
                        'escalated'         => $escalated,
                        'pending_validation' => 0,
                        'escalated_at' => date('Y-m-d H:i:s')
                    ],
                    ['id' => $row['id']]
                );
            } else {
                $DB->update(
                    'glpi_plugin_escalation_logs',
                    [
                        'escalated'         => $escalated,
                        'pending_validation' => 0
                    ],
                    ['id' => $row['id']]
                );
            }

        }

        return true;
    }

    public static function plugin_escalationlog_cronInfo($name) {
        switch ($name) {
            case 'PluginEscalationlogEscalationCron':
                return ['description' => __('Valida se o escalonamento é real', 'escalationlog')];
        }
        return [];
    }
}