<?php

class TcServer {
    private $data = false; //this is an associative array
    
    public function LoadById($id,$enabledOnly=false) {
        if ( $enabledOnly )
            $enabledOnly = " AND enabled='true'";
            $this->data=Database::get()->querySingle("SELECT * FROM tc_servers WHERE id = ?d".$enabledOnly, $this->id);
        return $this->data?$this:false;
    }

    public function LoadByType($type,$enabledOnly=false) {
        if ( $enabledOnly )
            $enabledOnly = " AND enabled='true' ";
        
            $this->data = Database::get()->querySingle("SELECT * FROM tc_servers WHERE `type` = ?s".$enabledOnly.
                "ORDER BY weight ASC",$type);
            return $this->data?$this:false;
    }

    public function recording() {
        return $this->data && $this->data['enabled_recordings'] === 'true';
    }
    
    public function __get($name) {
        if ( !$this->data )
            return false;
        if ( isset($this->data->$name) )
            return $this->data->$name;
        return false;
    }
    
}