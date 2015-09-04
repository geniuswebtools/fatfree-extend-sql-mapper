<?php

namespace Model\MySQL;

class User extends \Model
{

    public function __construct($table = null, $fields = NULL, $ttl = 60)
    {
        parent::__construct($table, $fields, $ttl);
        $this->beforeinsert(array($this, 'doBeforeInsert'));
    }
    
    public function doBeforeInsert($self, $pkeys)
    {
        $candidate = (object)$this->candidate();
        if(isset($candidate->verify_password)) {
            $verify = $candidate->verify_password;
            $password = $this->get('password');
            if($verify !== $password) {
                throw new \Exception(sprintf(self::E_FIELD_MISMATCH, 'password'));
            }
            if(empty($password)) {
                throw new Exception(sprintf(self::E_FIELD_EMPTY, 'password'));
            }
            $hash = $this->hash($password);
            $this->set('password', $hash);
        }
        if($this->id === null) {
            $this->set('date', date('Y-m-d'));
        }
    }

}
