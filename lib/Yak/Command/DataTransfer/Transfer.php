<?php
namespace Yak\Command\DataTransfer;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class Transfer extends AbstractDataTransfer
{
    protected function configure()
    {
        $this->setName('transfer')
             ->setDescription('transfer data from one database to another')
             ->addArgument('transfer_config', InputArgument::REQUIRED, 'config file that describes how to transfer the data')
             ->addArgument('source_connection', InputArgument::REQUIRED, 'connection to use for data source')
             ->addArgument('destination_connection', InputArgument::REQUIRED, 'connection to write the data to');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source_connection');
        $destination = $input->getArgument('destination_connection');
        $transferConfigFile = $input->getArgument('transfer_config');

        $sourceConnection = $this->getConnection($source);
        $destinationConnection = $this->getConnection($destination);

        if (!($sourceConnection instanceof \PDO) || !($destinationConnection instanceof \PDO)) {
            throw new \Exception('Unable to get connection information for source or destination');
        }

        $maxPacket = $destinationConnection->query("SHOW VARIABLES LIKE '%packet%'")->fetch(\PDO::FETCH_ASSOC);
        $maxPacket = $maxPacket["Value"];

        $destinationConnection->query("SET FOREIGN_KEY_CHECKS=0");

        $transferConfig = include $transferConfigFile;
        if (!is_array($transferConfig)) {
            throw new \Exception('Error loading transfer config file');
        }

        $output->writeln('<info>Found ' . count($transferConfig["tables"]) . ' tables to transfer</info>');
        foreach ($transferConfig["tables"] as $table) {
            $output->writeln('<info>Transferring ' . $table . '</info>');
            $sourceTable = $sourceConnection->query("DESCRIBE $table");
            if ($sourceTable) {
                $sourceTableSchema = array();
                while ($row = $sourceTable->fetch(\PDO::FETCH_ASSOC)) {
                    $sourceTableSchema[] = $row;
                }
            } else {
                $output->writeln('<error>Skipping table: `' . $table . ' - source table does not exist`</error>');
                continue;
            }

            $destinationTable = $destinationConnection->query("DESCRIBE $table");
            if ($destinationTable) {
                $destinationTableSchema = array();
                while ($row = $destinationTable->fetch(\PDO::FETCH_ASSOC)) {
                    $destinationTableSchema[] = $row;
                }
            } else {
                $output->writeln('<error>Skipping table: `' . $table . ' - destination table does not exist`</error>');
                continue;
            }

            $columnsToTransfer = array();
            foreach ($sourceTableSchema as $srcColumn) {
                $foundColumn = false;
                foreach ($destinationTableSchema as $destinationColumn) {
                    if ($srcColumn["Field"] === $destinationColumn["Field"]) {
                        if ($srcColumn["Type"] !== $destinationColumn["Type"]) {
                            $output->writeln('<comment>--- Definition mismatch: `' . $srcColumn['Field'] . '` - source is ' . $srcColumn['Type'] . ' and destination is ' . $destinationColumn['Type'] . '. Continuing anyway...</comment>');
                        }
                        $columnsToTransfer[] = $srcColumn;
                        $foundColumn = true;
                        break;
                    }
                }
                if (!$foundColumn) {
                    $output->writeln('<comment>--- Skipping `' . $srcColumn['Field'] . '`: column does not exist in destination</comment>');
                }
            }

            $destinationConnection->query("TRUNCATE TABLE $table");
            $destinationConnection->query("ALTER TABLE $table DISABLE KEYS");


            $cols = array();
            foreach ($columnsToTransfer as $col) {
                $cols[] = "`" . $col["Field"] . "`";
            }
            $insertSqlBase = "INSERT INTO $table (" . implode(",", $cols) . ") VALUES ";
            $insertSql = $insertSqlBase;
            $selectSql = "SELECT " . implode(",", $cols) . " FROM $table";
            $rows = $sourceConnection->query($selectSql);
            $output->writeln('<comment>--- Transferring ' . $rows->rowCount() . ' rows</comment>');
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                foreach ($row as $key => &$val) {
                   if ($val === null) {
                       $val = 'NULL';
                   } else {
                       $val = "'" . addslashes($val) . "'";
                   }
                }
                $vals = array_values($row);
                $rowData = "(" . implode(",", $vals) . ")";
                if (strlen($insertSql) + strlen($rowData) + 1 < $maxPacket) {
                    $insertSql .= $rowData . ",";
                } else {
                    $insertSql = substr($insertSql, 0, strlen($insertSql) - 1);
                    $destinationConnection->query($insertSql);
                    $insertSql = $insertSqlBase;
                    $insertSql .= $rowData . ",";
                }
            }
            if ($insertSql != $insertSqlBase) {
                $insertSql = substr($insertSql, 0, strlen($insertSql) - 1);
                $destinationConnection->query($insertSql);
            }
            $destinationConnection->query("ALTER TABLE $table ENABLE KEYS");
        }
        $destinationConnection->query("SET FOREIGN_KEY_CHECKS=1");
    }
}