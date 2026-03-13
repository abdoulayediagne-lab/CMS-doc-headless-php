<?php


namespace App\Lib\Commands;

use App\Lib\Database\DatabaseConnexion;
use App\Lib\Database\Dsn;


class DropDatabase extends AbstractCommand {
    
    public function execute(): void
    {
        $db = new DatabaseConnexion();
        $dsn = new Dsn();
        $dsn->addHostToDsn()
            ->addPortToDsn()
            ->addDbnameToDsn();
        $db->setConnexion($dsn);
        $db->getConnexion()->exec("DROP DATABASE IF EXISTS {$dsn->getDbName()};");
    }

    public function undo(): void
    {
    }

    public function redo(): void
    {
    }
    
}

?>
