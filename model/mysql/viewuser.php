<?php

namespace Model\MySQL;

class ViewUser extends \User
{

    public function __construct($table = null, $fields = NULL, $ttl = 60)
    {
        parent::__construct($table, $fields, $ttl);
        $this->beforeinsert(array($this, 'doBeforeInsert'));
    }
    
    public function doBeforeInsert($self, $pkeys)
    {
        throw new \Exception(self::EX_NO_SAVE);
    }

}
