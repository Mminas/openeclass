<?php

class TcServer
{

    private $data = false;

    // this is an associative array
    public function __construct(StdClass $data)
    {
        if ($data)
            $this->data = $data;
    }

    public static function LoadById($id)
    {
        $r = Database::get()->querySingle("SELECT * FROM tc_servers WHERE id = ?d", $id);
        return $r ? new TcServer($r) : false;
    }

    public static function LoadOneByTypes($types, $enabledOnly = false)
    {
        if ($enabledOnly)
            $enabledOnly = " AND enabled='true' ";

        if (! is_array($types)) {
            $types = array(
                $types
            );
        }
        array_walk($types,function(&$value) { $value = '"'.$value.'"'; });
        $types = implode(',',$types);
        //TODO: FIX Database to support IN() - probably by supporting arrays as values to bind
        $r = Database::get()->querySingle("SELECT * FROM tc_servers WHERE `type` IN ($types)" . $enabledOnly . " ORDER BY weight ASC");
        return $r ? new TcServer($r) : false;
    }

    public static function LoadOneByCourse($course_id)
    {
        $r = Database::get()->querySingle("SELECT id FROM course_external_server WHERE course_id=?d", $course_id);
        if ($r)
            return self::LoadById($r);
        return false;
    }

    public static function LoadAllByTypes($types, $enabledOnly = false)
    {
        if ($enabledOnly)
            $enabledOnly = " AND enabled='true' ";

        if (! is_array($types)) {
            $types = array(
                $types
            );
        }
        array_walk($types,function(&$value) { $value = '"'.$value.'"'; });
        $types = implode(',',$types);
        //TODO: FIX Database to support IN() - probably by supporting arrays as values to bind
        
        $r = Database::get()->queryArray("SELECT * FROM tc_servers WHERE `type` IN ($types)" . $enabledOnly . "ORDER BY weight ASC");
        $s = [];
        if ($r) {
            foreach ($r as $rr) {
                $s[] = new TcServer($rr);
            }
        }
        return $s;
    }

    public static function LoadAll($enabledOnly = false)
    {
        if ($enabledOnly)
            $enabledOnly = " WHERE enabled='true' ";

        $r = Database::get()->queryArray("SELECT * FROM tc_servers" . $enabledOnly . " ORDER BY weight ASC");
        $s = [];
        if ($r) {
            foreach ($r as $rr) {
                $s[] = new TcServer($rr);
            }
        }
        return $s;
    }

    public function recording()
    {
        return $this->data && $this->enabled_recordings;
    }

    public function enabled()
    {
        return $this->data && $this->enabled;
    }

    public function __get($name)
    {
        if (! $this->data)
            return false;

        // Convert to actual booleans
        if ($name == 'enabled') {
            return $this->data->enabled == 'true';
        } elseif ($name == 'enable_recordings') {
            return $this->data->enable_recordings == 'true';
        } elseif ($name == 'all_courses')
            return $this->data->all_courses == '1';

        if (isset($this->data->$name))
            return $this->data->$name;

        return false;
    }

    /**
     *
     * @brief check if bbb server is available
     * @param int $users_to_join
     *            -- The number of participants to join
     * @return boolean
     */
    function available($users_to_join)
    {
        if (! $this->enabled)
            return false;

        $max_rooms = $this->max_rooms;
        $max_users = $this->max_users;

        $bbb = new BigBlueButton([
            'server' => $this->data
        ]);

        $meetings = $bbb->getMeetings();
        if (! $meetings)
            return false;

        $connected_users = 0;
        $active_rooms = 0;

        // echo 'MEETING XML:<br>';
        // print_r($meetings);
        if (array_key_exists('meetings', $meetings)) {
            foreach ($meetings['meetings'] as $meeting) {
                // echo 'MEETING<br><br>';
                // print_r($meeting);
                $mid = $meeting['meetingID'];
                if ($mid != null) {
                    $active_rooms ++;
                    $connected_users += $meeting['participantCount'];
                }
            }
        }

        // good cases:
        // max_users = 0 && max_rooms = 0 - UNLIMITED
        // active_rooms < max_rooms && active_users < max_users
        // active_rooms < max_rooms && max_users = 0 (UNLIMITED)
        // active_users < max_users && max_rooms = 0 (UNLIMITED)
        return (($max_rooms == 0 && $max_users == 0) || (($max_users >= ($users_to_join + $connected_users)) and $active_rooms <= $max_rooms) || ($active_rooms <= $max_rooms and $max_users == 0) || (($max_users >= ($users_to_join + $connected_users)) && $max_rooms == 0));
    }

    public function get_connected_users()
    {
        return 'NOT IMPLEMENTED';
    }
}