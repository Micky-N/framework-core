<?php

namespace MkyCore\Console\Install;

use MkyCore\Console\Create\Create;

class Notification extends Create
{
    public function process(): bool|string
    {
        $migrationAR = false;
        $migrationModel = file_get_contents(dirname(__DIR__) . '/models/install/notification/migration.model');
        $databasePath = $this->app->get('path:base') . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        if (!is_dir($databasePath)) {
            mkdir($databasePath, '0777', true);
        }
        if (!glob($databasePath . DIRECTORY_SEPARATOR . '*create_notifications_table.php')) {
            $migrationFile = $databasePath . DIRECTORY_SEPARATOR . time() . '_create_notifications_table.php';
            file_put_contents($migrationFile, $migrationModel);
        } else {
            $migrationAR = true;
        }
        if ($migrationAR) {
            echo $this->getColoredString('notifications migration file already exists', 'red', 'bold');
        } else {
            echo $this->getColoredString('notifications migration file created successfully', 'green', 'bold') . "\n";
            echo '> run ' . $this->getColoredString('php mky migration:run', 'yellow') . ' to migrate the Notifications table';
        }
        return true;
    }
}