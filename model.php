<?php

abstract class Model extends \DB\SQL\Mapper
{

    private
        $candidate;

    const
        BCRYPT_COST = 10,
        CANDIDATE_PREFIX = 'dbCandidate',
        E_FIELD_MISMATCH = 'The %s does not match.',
        E_FIELD_EMPTY = 'The %s cannot be empty.',
        E_FIELD_INVALID = '%s is not valid.',
        EX_FAILED= '%s failed.',
        EX_NO_SAVE = 'This model cannot save records.';
    
    public function __construct($table = null, $fields = NULL, $ttl = 60)
    {
        $class = get_called_class();
        $isMySQL = preg_match('/mysql/i', $class);
        if($table === null) {
            $temp = explode('\\', $class);
            $table = strtolower(end($temp));
        }
        $fw = $this->fw();
        $db = (($isMySQL) ? $fw->get('MySQLDB') : $fw->get('SQLiteDB'));
        parent::__construct($db, $table, $fields, $ttl);
    }

    /**
     * You cannot override the save method.  If you need to disable the save
     * method on a model class, use the beforeinsert() trigger and throw an
     * exception and return the EX_NO_SAVE constant.
     * 
     * @param array $struct
     * @return \DB\SQL\Mapper object
     */
    final public function save(array $struct = null)
    {
        if($struct !== null) {
            $this->candidate = $struct;
            $this->reset();
            $fw = $this->fw();
            $pkey = $this->pkey();
            if(isset($this->candidate[$pkey])) {
                $id = $this->candidate[$pkey];
                unset($this->candidate[$pkey]);
                $this->load(array('`' . $pkey . '`=?', $id));
            }
            $candidate = self::CANDIDATE_PREFIX . $this->source;
            $fw->set($candidate, $this->candidate);
            $this->copyfrom($candidate);
            $fw->clear($candidate);
        }
        return parent::save();
    }

    /**
     * Returns the name of the primary key field in the database table from the 
     * schema stored in the mapper.
     * 
     * @return string
     */
    protected function pkey()
    {
        foreach((array)$this->fields as $field => $meta) {
            if($meta['pkey'] !== true) {
                continue;
            }

            return $field;
        }
    }

    /**
     * The candidate returns the raw $struct that was passed to the save() 
     * method.  The candidate can be used in the mapper event triggers for 
     * comparison, and rules based on the individual model.
     * 
     * @return null|array
     */
    protected function candidate()
    {
        return $this->candidate;
    }
    
    protected function isEmail($email)
    {
        $audit = $this->audit();
        return $audit->email($email, false);
    }

    /**
     * Provides access to the Bcrypt class to hash a string.
     * 
     * @param string $pw
     * @param string $salt
     * @param integer $cost
     * @return string
     */
    protected function hash($pw, $salt = null, $cost = self::BCRYPT_COST)
    {
        $crypt = $this->crypt();
        return $crypt->hash($pw, $salt, $cost);
    }

    /**
     * Provides access to the Bcrypt class to verify a hash.
     * 
     * @param string $pw
     * @param string $hash
     * @return bool
     */
    protected function verify($pw, $hash)
    {
        $crypt = $this->crypt();
        return $crypt->verify($pw, $hash);
    }

    /**
     * 
     * @param string $hash
     * @param integer $cost
     * @return bool
     */
    protected function needsRehash($hash, $cost = self::BCRYPT_COST)
    {
        $crypt = $this->crypt();
        return $crypt->needs_rehash($hash, $cost);
    }
    
    /**
     * Provides access to the Fatfree framework. Data relevant to the model 
     * should be passed to the save() method, and is stored in the private 
     * $candidate member. You can access the $candidate member through the 
     * protected candidate() method.
     * 
     * @return type
     */
    protected function fw()
    {
        return \Base::instance();
    }

    /**
     * This method should not be called directly. Models that need to hash or 
     * verify hashes should use the aliased methods hash() and verify().
     * 
     * @return object
     */
    private function crypt()
    {
        return \Bcrypt::instance();
    }
    
    private function audit()
    {
        return \Audit::instance();
    }

}
