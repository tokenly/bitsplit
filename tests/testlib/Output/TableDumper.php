<?php

use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;


class TableDumper
{
    public static function dumpToTable($headers, $rows, $output = null)
    {
        $output = $output ?? new BufferedOutput();

        $table = new Table($output);

        if ($rows instanceof Arrayable) {
            $rows = $rows->toArray();
        }

        $tableStyle = 'default';
        $table->setHeaders((array) $headers)->setRows($rows)->setStyle($tableStyle);

        $table->render();

        return $output->fetch();
    }


}
