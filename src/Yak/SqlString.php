<?php
namespace Yak;

class SqlString
{
    protected $rawString;

    public function __construct($sqlString)
    {
        $this->rawString = $sqlString;
    }

    public function getQueries()
    {
        return preg_split('/[.+;][\s]*\n/', $this->rawString, -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function fromFile($file)
    {
        return new self(file_get_contents($file));
    }
}