<?php
namespace Yak\Command\DataTransfer;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class ColumnTransfer extends AbstractDataTransfer
{
    protected function configure()
    {
        $this->setName('transfer-column')
             ->setDescription('transfer columns from one database to another in the same table name')
             ->addArgument('transfer_config', InputArgument::REQUIRED, 'config file that describes how to transfer the data')
             ->addArgument('source_connection', InputArgument::REQUIRED, 'connection to use for data source')
             ->addArgument('destination_connection', InputArgument::REQUIRED, 'connection to write the data to');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateTargets();
        $source = $input->getArgument('source_connection');
        $destination = $input->getArgument('destination_connection');
        $transferConfigFile = $input->getArgument('transfer_config');

        $sourceConnection = $this->getConnection($source);
        $destinationConnection = $this->getConnection($destination);

        if (!($sourceConnection instanceof \PDO) || !($destinationConnection instanceof \PDO)) {
            throw new \Exception('Unable to get connection information for source or destination');
        }

        $destinationConnection->query("SET FOREIGN_KEY_CHECKS=0");

        $transferConfig = include $transferConfigFile;
        if (!is_array($transferConfig)) {
            throw new \Exception('Error loading transfer config file');
        }

        foreach ($transferConfig as $table => $spec) {

            $primaryKey = $spec["key"];
            $columns = $spec["columns"];
            $constraints = $spec["constraints"];

            $output->writeln("<info>Getting " . implode (",", $columns) . " from $table</info>");

            $sourceSql = "SELECT $primaryKey, ";
            $sourceSql .= implode(",", $columns);
            $sourceSql .= " FROM $table";
            if ($constraints) {
                $sourceSql .= " WHERE ";
                $constraintClauses = [];
                foreach ($constraints as $column => $value) {
                    if (is_numeric($value)) {
                        $constraintClauses[] = "$column=" . $value;
                    } else if (is_null($value)) {
                        $constraintClauses[] = "$column=NULL";
                    } else {
                        $quotedVal = $destinationConnection->quote($value);
                        $constraintClauses[] = "$column=$quotedVal";
                    }
                }
                $sourceSql .= implode(" AND ", $constraintClauses);
            }

            $sourceData = $sourceConnection->query($sourceSql);
            foreach ($sourceData->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $destSql = "UPDATE $table
                            SET ";
                $columnUpdates = [];
                foreach ($columns as $column) {
                    if (is_numeric($row[$column])) {
                        $columnUpdates[] = "$column=" . $row[$column];
                    } else if (is_null($row[$column])) {
                        $columnUpdates[] = "$column=NULL";
                    } else {
                        $quotedVal = $destinationConnection->quote($row[$column]);
                        $columnUpdates[] = "$column=$quotedVal";
                    }

                }
                $destSql .= implode(",", $columnUpdates);
                $destSql .= " WHERE $primaryKey=" . $row[$primaryKey];
                $destinationConnection->query($destSql);
                $output->writeln("Updated $table." . $row[$primaryKey] . " " . implode(",", $columnUpdates));
            }
        }
        $destinationConnection->query("SET FOREIGN_KEY_CHECKS=1");
    }
}