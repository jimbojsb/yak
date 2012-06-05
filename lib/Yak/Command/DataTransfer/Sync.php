<?php
namespace Yak\Command\DataTransfer;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class Sync extends AbstractDataTransfer
{
    protected function configure()
    {
        $this->setName('sync')
             ->setDescription('syncrhonize data between databases')
             ->addArgument('source_connection', InputArgument::REQUIRED, 'connection to use for data source')
             ->addArgument('destination_connection', InputArgument::REQUIRED, 'connection to write the data to')
             ->addOption('bidirectional', 'b', InputOption::VALUE_NONE, 'sync data both ways (source wins conflicts)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $this->validateTargets();
        $source = $input->getArgument('source_connection');
        $destination = $input->getArgument('destination_connection');

        $sourceConnection = $this->getConnection($source);
        $destinationConnection = $this->getConnection($destination);

        if (!($sourceConnection instanceof \PDO) || !($destinationConnection instanceof \PDO)) {
            throw new \Exception('Unable to get connection information for source or destination');
        }

        $sourceTables = $sourceConnection->query("SHOW TABLES");
        while ($sourceTable = $sourceTables->fetch(\PDO::FETCH_ASSOC)) {
            $sourceTableNames[] = each($sourceTable)[1];
        }

        $destinationTables = $destinationConnection->query("SHOW TABLES");
        while ($destinationTable = $destinationTables->fetch(\PDO::FETCH_ASSOC)) {
            $destinationTableNames[] = each($destinationTable)[1];
        }

        $tablesToTransfer = array_intersect($sourceTableNames, $destinationTableNames);
        $output->writeln('<info>Found ' . count($tablesToTransfer) . ' tables to sync</info>');

        foreach ($tablesToTransfer as $table) {
            $output->write("<info>-- syncing $table...</info>");
            $this->syncTable($sourceConnection, $destinationConnection, $table);
            $output->writeln("<info>done</info>");
        }


    }

    private function syncTable($sourceConnection, $destinationConnection, $sourceTableName, $destinationTableName = null)
    {
        $findPrimaryKey = function($schema) {
            foreach ($schema as $column) {
                if ($column['Key'] == 'PRI') {
                    return $column['Field'];
                }
            }
        };

        $getColumnNames = function($schema) {
            $columns = array();
            foreach ($schema as $column) {
                $columns[] = $column['Field'];
            }
            return $columns;
        };

        $buildColumnList = function($columns) {
            return "`" . implode("`,`", $columns) . "`";
        };

        $transmuteHashRows = function($rows) {
            $output = array();
            foreach ($rows as $row) {
                $output[$row['pk']] = $row['hash'];
            }
            return $output;
        };

        if (!$destinationTableName) {
            $destinationTableName = $sourceTableName;
        }

        $sourceSchema = $sourceConnection->query("DESCRIBE $sourceTableName")->fetchAll(\PDO::FETCH_ASSOC);
        $destinationSchema = $destinationConnection->query("DESCRIBE $destinationTableName")->fetchAll(\PDO::FETCH_ASSOC);
        $schemasMatch = $sourceSchema === $destinationSchema;
        if ($schemasMatch) {
            $primaryKey = $findPrimaryKey($destinationSchema);
            $columns = $getColumnNames($sourceSchema);
            $sourceRowHashQuery = "SELECT $primaryKey AS pk, sha1(CONCAT_WS('!', " . $buildColumnList($columns) . ")) AS hash FROM $sourceTableName";
            $sourceRowHashesResult = $sourceConnection->query($sourceRowHashQuery);
            if ($sourceRowHashesResult) {
                $sourceRowHashes = $transmuteHashRows($sourceRowHashesResult->fetchAll(\PDO::FETCH_ASSOC));
            }
            $destinationRowHashQuery = "SELECT $primaryKey AS pk, sha1(CONCAT_WS('!', " . $buildColumnList($columns) . ")) AS hash FROM $destinationTableName";
            $destinationRowHashesResult = $destinationConnection->query($destinationRowHashQuery);
            if ($destinationRowHashesResult) {
                $destinationRowHashes = $transmuteHashRows($destinationRowHashesResult->fetchAll(\PDO::FETCH_ASSOC));
            }

            if ($sourceRowHashes) {
                $rowsToTransfer = array();
                foreach ($sourceRowHashes as $pk => $hash) {
                    if ($destinationRowHashes[$pk] != $hash) {
                        $rowsToTransfer[] = $pk;
                    }
                }
                var_dump($rowsToTransfer);
            } else {
                return 0;
            }



        } else {
            $this->output->writeln("<error>Cannot sync $sourceTableName due to lack of primary key...</error>");
        }
    }
}