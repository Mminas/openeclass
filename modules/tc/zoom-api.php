<?php
use phpDocumentor\Reflection\Types\Integer;
use Mpdf\Tag\THead;
use phpDocumentor\Reflection\Types\This;

require_once 'TcApi.php';
require_once 'paramsTrait.php';


/**
 * @desc This trait includes a function to initialize data classes from associative arrays
 * enforcing an object type specification specified in a variable. It is recommended to use this
 * in __construct() of those classes.
 * @author User
 *
 */
trait ArrayObjectInitable
{
    private function init($data, $typeHints = [])
    {
        $reflect = new ReflectionClass(__CLASS__);
        if (is_array($data)) {
            //echo '<pre>'.__CLASS__." conv array: \n";
            foreach ($data as $key => $val) { //this comes from internal API callers usually: mixed array/object content
                //echo 'key/data: ' . $key . ' ' . var_export($val, true) . "\n";

                //Only store predefined properties in our objects (don't be lazy, fully model the API!)
                if ( !$reflect->hasProperty($key) ) {
                    echo __CLASS__.' uknown property '.$key.' ignored';
                    continue;
                }
                
                //Check if it's in the type hints array, if so, make it an object
                if (array_key_exists($key, $typeHints)) {
                    $classname = $typeHints[$key];
                    $this->{$key} = new $classname($val); //this should logically call it's own init() via this trait, if necessary
                    continue;
                }

                //By default just copy it over
                $this->{$key} = $val;
            }
        }
    }
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *        
 */
class ZoomTrackingField
{

    /**
     *
     * @var string - required
     */
    public $field;

    /**
     *
     * @var string
     */
    public $value;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *         Recurrence object. Use this object only for a meeting with type 8 i.e., a recurring meeting with fixed time.
 *         ONLY IN REQUESTS
 */
class ZoomMeetingRecurrence
{

    /**
     *
     * @var integer Recurrence meeting types:
     *      1 - Daily
     *      2 - Weekly
     *      3 - Monthly
     */
    public $type;

    /**
     *
     * @var integer Define the interval at which the meeting should recur. For instance, if you would like to schedule a meeting
     *      that recurs every two months, you must set the value of this field as 2 and the value of the type parameter as 3.
     *      For a daily meeting, the maximum interval you can set is 90 days. For a weekly meeting the maximum interval that you
     *      can set is of 12 weeks. For a monthly meeting, there is a maximum of 3 months.
     */
    public $repeat_interval;

    /**
     *
     * @var string Use this field only if you're scheduling a recurring meeting of type 2 to state which day(s) of the week your
     *      meeting should repeat.
     *      Note: if you would like the meeting to occur on multiple days of a week, you should provide comma separated
     *      values for this field.
     *      1-7 - Sunday-Saturday
     */
    public $weekly_days;

    /**
     *
     * @var integer Use this field only if you're scheduling a recurring meeting of type 3 to state which day in a month the
     *      meeting should recur. The value ranges from 1 to 31.
     *      For instance, if you would like the meeting to recur on the 23rd of each month, provide 23 as the value of This
     *      field and 1 as the value of the repeat_interval field. Instead, if you would like the meeting to recur every three
     *      months, on 23rd of the months, change the value of the repeat_interval field to 3.
     */
    public $monthly_day;

    /**
     *
     * @var integer Use this field only if you're scheduling a recurring meeting of type 3 to state the week of the month
     *      when the meeting should recur. If you use this field you must also use the monthly_week_day field to
     *      state the day of the week when the meeting should recur.
     *      -1 - Last week of the month
     *      1 - First week of the month
     *      2 - Second week of the month
     *      3 - Third week of the month
     *      4 - Fourth week of the month
     */
    public $monthly_week;

    /**
     *
     * @var integer Use this field only if you're scheduling a recurring meeting of type 3 to state a specific day in a week
     *      when the monthly meeting should recur. To use this field, you must also use the month_week field.
     *      1-7 - Sunday-Saturday
     */
    public $monthly_week_day;

    /**
     *
     * @var integer (default 1, maximum 50)
     *      Select how many times the meeting should recur before it is cancelled. (Cannot be used with end_date_time)
     */
    public $end_times;

    /**
     *
     * @var string Select the final date on which the meeting will recur before it is cancelled. Should be in UTC time, such as
     *      2017-11-25T12:00:00Z. (Cannot be used with end_times)
     */
    public $end_date_time;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *         Occurence object. This object is only returned for Recurring Webinars.
 *         ONLY IN RESPONSES
 */
class ZoomWebinarOccurence
{

    /**
     *
     * @var string Occurrence ID: Unique Identifier that identifies an occurrence of a recurring webinar. [Recurring webinars]
     *      (https://support.zoom.us/hc/en-us/articles/216354763-How-to-Schedule-A-Recurring-Webinar) can have
     *      a maximum of 50 occurrences.
     */
    public $occurrence_id;

    /**
     *
     * @var string format: date-time
     */
    public $start_time;

    /**
     *
     * @var integer
     */
    public $duration;

    /**
     *
     * @var string Occurrence status.
     */
    public $status;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *         ONLY IN RESPONSES
 */
class ZoomDialinNumber
{

    /**
     *
     * @var string - Country code. For example, BR.
     */
    public $country;

    /**
     *
     * @var string - Full name of country. For example, Brazil.
     */
    public $country_name;

    /**
     *
     * @var string - City of the number, if any. For example, Chicago.
     */
    public $city;

    /**
     *
     * @var string - Phone number. For example, +1 2332357613.
     */
    public $number;

    /**
     *
     * @var enum(toll|tollfree) - "Type of number
     */
    public $type;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *         Meeting settings.
 *        
 */
class ZoomMeetingSettings
{
    use ArrayObjectInitable;

    public function __construct($data)
    {
        $this->init($data);
    }

    /**
     *
     * @var boolean - Start video when the host joins the meeting.
     */
    public $host_video;

    /**
     *
     * @var boolean - Start video when participants join the meeting.
     */
    public $participant_video;

    /**
     *
     * @var boolean - Host meeting in China.
     */
    public $cn_meeting;

    /**
     *
     * @var boolean - Host meeting in India. (Default false)
     */
    public $in_meeting;

    /**
     *
     * @var boolean - Allow participants to join the meeting before the host starts the meeting. Only used for scheduled
     *      or recurring meetings. Default false;
     */
    public $join_before_host;

    /**
     *
     * @var boolean - Mute participants upon entry. Default false
     */
    public $mute_upon_entry;

    /**
     *
     * @var boolean - Add watermark when viewing a shared screen.
     */
    public $watermark;

    /**
     *
     * @var boolean Use Personal Meeting ID instead of an automatically generated meeting ID. It can only be used for scheduled
     *      meetings, instant meetings and recurring meetings with no fixed time. Default False
     */
    public $use_pmi;

    /**
     *
     * @var integer 0 - Automatically approve.
     *      1 - Manually approve.
     *      2 - No registration required. (default)
     */
    public $approval_type;

    /**
     *
     * @var integer Registration type. Used for recurring meeting with fixed time only.
     *      1 Attendees register once and can attend any of the occurrences. (default)
     *      2 Attendees need to register for each occurrence to attend.
     *      3 Attendees register once and can choose one or more occurrences to attend.
     */
    public $registration_type;

    /**
     *
     * @var string Determine how participants can join the audio portion of the meeting.
     *      both - Both Telephony and VoIP. (default)
     *      telephony - Telephony only.
     *      voip - VoIP only.
     */
    public $audio;

    /**
     *
     * @var string Automatic recording:
     *      local - Record on local.
     *      cloud - Record on cloud.
     *      none - Disabled. (default)
     */
    public $auto_recording;

    /**
     *
     * @var boolean - Only signed in users can join this meeting.
     */
    public $enforce_login;

    /**
     *
     * @var string - Only signed in users with specified domains can join meetings.
     */
    public $enforce_login_domains;

    /**
     *
     * @var string - Alternative host’s emails or IDs: multiple values separated by a comma.
     */
    public $alternative_hosts;

    /**
     *
     * @var boolean - Close registration after event date. Default false
     */
    public $close_registration;

    /**
     *
     * @var boolean - Enable waiting room. Default:false
     */
    public $waiting_room;

    /**
     *
     * @var string[] - List of global dial-in countries
     */
    public $global_dial_in_countries;

    /**
     *
     * @var ZoomDialinNumber[] Global Dial-in Countries/Regions
     *      ONLY IN RESPONSE
     */
    public $global_dial_in_numbers;

    /**
     *
     * @var string - Contact name for registration
     */
    public $contact_name;

    /**
     *
     * @var string - Contact email for registration
     */
    public $contact_email;

    /**
     *
     * @var boolean Send confirmation email to registrants upon successful registration
     */
    public $registrants_confirmation_email;

    /**
     *
     * @var boolean Send email notifications to registrants about approval, cancellation, denial of the registration.
     *      The value of this field must be set to true in order to use the registrants_confirmation_email field.
     */
    public $registrants_email_notification;

    /**
     *
     * @var boolean - Only authenticated users can join meeting if the value of this field is set to true.
     */
    public $meeting_authentication;

    /**
     *
     * @var string IN REQUESTS:
     *      Specify the authentication type for users to join a meeting withmeeting_authentication setting set to true.
     *      The value of this field can be retrieved from the id field within authentication_options array in the response of Get User Settings API.
     *      IN RESPONSES:
     *      Meeting authentication option id.
     */
    public $authentication_option;

    /**
     *
     * @var string IN REQUESTS:
     *      Meeting authentication domains. This option, allows you to specify the rule so that Zoom users, whose email address contains
     *      a certain domain, can join the meeting. You can either provide multiple domains, using a comma in between and/or use a
     *      wildcard for listing domains.
     *      IN RESPONSES:
     *      If user has configured [\"Sign Into Zoom with Specified Domains\"]
     *      (https://support.zoom.us/hc/en-us/articles/360037117472-Authentication-Profiles-for-Meetings-and-Webinars#h_5c0df2e1-cfd2-469f-bb4a-c77d7c0cca6f)
     *      option, this will list the domains that are authenticated.
     */
    public $authentication_domains;

    /**
     *
     * @var string Authentication name set in the [authentication profile]
     *      (https://support.zoom.us/hc/en-us/articles/360037117472-Authentication-Profiles-for-Meetings-and-Webinars#h_5c0df2e1-cfd2-469f-bb4a-c77d7c0cca6f).
     */
    public $authentication_name;
}

/**
 * ===========================================================================================================================
 *
 * @author User
 *        
 */
class ZoomMeeting
{
    use ArrayObjectInitable;
    
    public function __construct(array $data)
    {
        //echo "\nCONSTRUCT MEETING\n";
        //print_r($data);
        $this->init($data, [
            'settings' => 'ZoomMeetingSettings',
            'tracking_fields' => 'ZoomTrackingField[]',
            'recurrence' => 'ZoomMeetingRecurrence',
        ]);
//         echo '<pre>CONSTRUCTED MEETING:'."\n".var_export($this,true).'</pre>';
//         die();
    }

    /**
     *
     * @var string Unique meeting ID. Each meeting instance will generate its own Meeting UUID. Please double encode your UUID when
     *      using it for API calls if the UUID begins with a '/'or contains '//' in it.
     *      IN RESPONSE ONLY: getmeeting
     */
    public $uuid;

    /**
     *
     * @var string [Meeting ID](https://support.zoom.us/hc/en-us/articles/201362373-What-is-a-Meeting-ID-): Unique identifier of the meeting in
     *      "**long**" format(represented as int64 data type in JSON), also known as the meeting number.
     *      IN RESPONSE ONLY: getmeeting, createmeeting
     */
    public $id;

    /**
     *
     * @var string ID of the user who is set as host of meeting.
     *      IN RESPONSE ONLY: getmeeting
     */
    public $host_id;

    /**
     *
     * @var String Meeting status
     *      "waiting",
     *      "started",
     *      "finished"
     *      ONLY IN RESPONSE: getmeeting
     */
    public $status;

    /**
     *
     * @var string URL to start the meeting. This URL should only be used by the host of the meeting and **should not be shared with anyone
     *      other than the host** of the meeting as anyone with this URL will be able to login to the Zoom Client as the host of the meeting.
     *      IN RESPONSE ONLY: getmeeting, createmeeting
     */
    public $start_url;

    /**
     *
     * @var string URL for participants to join the meeting. This URL should only be shared with users that you would like to invite for the meeting.
     *      ONLY IN RESPONSE: getmeeting, createmeeting
     */
    public $join_url;

    /**
     *
     * @var string H.323/SIP room system password
     *      IN RESPONSE ONLY: createmeeting, getmeeting
     */
    public $h323_password;
    
    /**
     * @var string NOT IN SPEC 20200429 - so private
     */
    private $pstn_password;

    /**
     *
     * @var string Encrypted password for third party endpoints (H323/SIP).
     *      ALSO IN RESPONSE: getmeeting
     */
    public $encrypted_password;

    /**
     *
     * @var string Personal Meeting Id. Only used for scheduled meetings and recurring meetings with no fixed time.
     *      IN RESPONSE ONLY: createmeeting, getmeeting
     */
    public $pmi;

    /**
     *
     * @var ZoomWebinarOccurence[] - Array of occurrence objects.
     *      IN RESPONSE ONLY: createmeeting, getmeeting
     */
    public $occurrences;

    /**
     *
     * @var string Meeting topic.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $topic;

    /**
     *
     * @var integer Meeting Type:
     *      1 - Instant meeting.
     *      2 - Scheduled meeting. (default)
     *      3 - Recurring meeting with no fixed time.
     *      8 - Recurring meeting with fixed time.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $type;

    /**
     *
     * @var string Meeting start time. We support two formats for start_time - local time and GMT.
     *      To set time as GMT the format should be yyyy-MM-ddTHH:mm:ssZ. Example: “2020-03-31T12:02:00Z”
     *      To set time using a specific timezone, use yyyy-MM-ddTHH:mm:ss format and specify the timezone ID in the timezone field OR leave it blank and the timezone set on your Zoom account will be used. You can also set the time as UTC as the timezone field.
     *      The start_time should only be used for scheduled and / or recurring webinars with fixed time.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $start_time;

    /**
     *
     * @var Integer Meeting duration (minutes). Used for scheduled meetings only.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $duration;

    /**
     *
     * @var string Time zone to format start_time. For example, “America/Los_Angeles”. For scheduled meetings only. Please reference our time
     *      zone list for supported time zones and their formats.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $timezone;

    /**
     *
     * @var string - The date and time at which this meeting was created.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     *     
     */
    public $created_at;

    /**
     *
     * @var string Password to join the meeting. Password may only contain the following characters: [a-z A-Z 0-9 @ - _ *]. Max of 10 characters.
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     *      If "Require a password when scheduling new meetings" setting has been **enabled** **and** [locked]
     *      (https://support.zoom.us/hc/en-us/articles/115005269866-Using-Tiered-Settings#locked) for the user, the password field will be
     *      autogenerated in the response even if it is not provided in the API request.
     */
    public $password;

    /**
     *
     * @var string Meeting description. Maxlength 2000
     *      ALSO IN RESPONSE: getmeeting, createmeeting
     */
    public $agenda;

    /**
     *
     * @var ZoomTrackingField[] - Tracking Fields (metadata)
     *      ALSO IN RESPONSE getmeeting, createmeeting
     */
    public $tracking_fields;

    /**
     *
     * @var ZoomMeetingRecurrence ALSO IN RESPONSE getmeeting, createmeeting
     */
    public $recurrence;

    /**
     *
     * @var ZoomMeetingSettings ALSO IN RESPONSE
     *      ALSO IN RESPONSE getmeeting, createmeeting
     */
    public $settings;
}

class Zoom extends TcApi
{

    private static $syntax = [
        'createmeeting' => [
            'request' => [
                'method' => 'POST',
                'url' => 'users/{userId}/meetings',
                'path' => [
                    // The user ID or email address of the user. For user-level apps, pass "me" as the value for user id.
                    'userId:string' // required
                ],
                'body' => 'ZoomMeeting'
            ],
            'response' => [
                '300' => [
                    'description' => 'Invalid enforce_login_domains, separate multiple domains by semicolon.
                        A maximum of {rateLimitNumber} meetings can be created/updated for a single user in one day.'
                ],
                '404' => [
                    'description' => 'User not found - If Error Code: 1001 User {userId} not exist or not belong to this account.'
                ],
                '201' => [
                    'description' => 'Meeting created',
                    'headers' => [
                        'Content-Location:string' // Location of created meeting
                    ],
                    'body' => 'ZoomMeeting'
                ]
            ]
        ],
        'listmeetings' => [
            'request' => [
                'method' => 'GET',
                'url' => 'users/{userId}/meetings',
                'path' => [
                    // The user ID or email address of the user. For user-level apps, pass "me" as the value for user id.
                    'userId:string' // required
                ],
                'query' => [
                    /*
                     * The meeting types: <br>`scheduled` - This includes all valid past meetings (unexpired), live meetings
                     * and upcoming scheduled meetings. It is equivalent to the combined list of \"Previous Meetings\" and
                     * \"Upcoming Meetings\" displayed in the user's [Meetings page](https://zoom.us/meeting) on the Zoom
                     * Web Portal.<br>`live` - All the ongoing meetings.<br>`upcoming` - All upcoming meetings including live meetings.
                     */
                    'type:enum(scheduled,live,upcoming)',
                    
                    /* The number of records returned within a single API call. default:30, max:300 */
                    'page_size:integer',

                    // The current page number of returned records. Default 1
                    'page_number:integer'
                ]
            ],
            'response' => [
                '404' => [
                    'description' => 'User ID not found. Error Code: 1001: User {userId} not exist or not belong to this account.'
                ],
                '200' => [
                    'description' => 'List of meeting objects returned.',
                    'body' => [
                        'page_count:integer',

                        // Default 1
                        'page_number:integer',

                        // default 30, max 300
                        'page_size:integer',
                        'total_records:integer',
                        'meetings:ZoomMeeting[]'
                    ]
                ]
            ]
        ],
        'getmeeting' => [
            'request' => [
                'method' => 'GET',
                'url' => 'meetings/{meetingId}',
                'path' => [
                    'meetingId:string' // required
                ],
                'query' => [
                    'occurence_id:string' // Meeting occurence id
                ]
            ],
            'response' => [
                '400' => [
                    'description' => 'Error Code: 1010: User not found on this account: {accountId}. 
                                    Error Code: 3000: Cannot access webinar info.'
                ],
                '404' => [
                    'description' => 'Meeting not found.
                                    Error Code: 1001: User not exist: {userId}.
                                    Error Code: 3001: Meeting {meetingId} is not found or has expired.'
                ],
                '200' => [
                    'description' => 'Meeting object returned.',
                    'body' => 'ZoomMeeting'
                ]
            ]
        ]
    ];

    private static $_cache = [];

    private $_ApiUrl;

    private $_ApiKey;

    private $_ApiSecret;

    private $_jwt;

    public function __construct($params = [])
    {
        if (is_array($params) && count($params) > 0) {
            if (array_key_exists('server', $params)) {
                $this->_ApiUrl = $params['server']->api_url;
                $this->_ApiSecret = $params['server']->server_key;
            }
            if (array_key_exists('url', $params))
                $this->_ApiUrl = $params['url'];
            if (array_key_exists('key', $params))
                $this->_ApiKey = $params['key'];
            if (array_key_exists('secret', $params)) {
                $x = explode(',', $params['secret']);
                $this->_ApiKey = $x[0];
                $this->_ApiSecret = $x[1];
            }
        }
    }

    private function generateJWT()
    {
        $this->_jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOm51bGwsImlzcyI6InFGQll5a0puUXEyNVVKaHZLV2VrTFEiLCJleHAiOjE1ODg3ODA1MzgsImlhdCI6MTU4ODE3NTcwNX0.IUeZvTZsPEDVF57pqGpO5pOSXZCmEbEagKLenyT4bJ4';
        if ($this->_jwt)
            return;
        if ($this->_ApiUrl && $this->_ApiKey && $this->_ApiSecret) {
            $this->_jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOm51bGwsImlzcyI6InFGQll5a0puUXEyNVVKaHZLV2VrTFEiLCJleHAiOjE1ODg3ODA1MzgsImlhdCI6MTU4ODE3NTcwNX0.IUeZvTZsPEDVF57pqGpO5pOSXZCmEbEagKLenyT4bJ4';
        } else
            die('[' . __METHOD__ . '] Missing info to generate JWT');
    }

    /**
     *
     * @brief Process a syntax fragment with a data fragment
     * @param string $syntax
     * @param mixed $response
     * @return mixed - the data processed per syntax
     * @throws RuntimeException
     */
    private function _processResponse($syntax, $response)
    {
        //echo '<pre>' . __METHOD__ . ': SYNTAX:' . var_export($syntax, true) . ' RESP: ' . var_export($response, true) . '</pre>';
        if (is_array($syntax)) { // array of items
            $items = [];
            foreach ($syntax as $syntaxitem) {
                $x = explode(':', $syntaxitem);
                if (! isset($response->{$x[0]}))
                    continue;
                $typespec = $x[1];
                echo 'Field ' . $x[0] . ' - type:' . $typespec . ' - val in object: ' . var_export($response->{$x[0]}, true) . "\n";
                $items[$x[0]] = $this->_processResponse($syntaxitem, $response->{$x[0]});
            }
            return $items;
        } else { // Single item
            $x = explode(':', $syntax);
            if (count($x) > 1)
                $typespec = $x[1];
            else
                $typespec = $x[0];
            if (in_array($typespec, [
                'number',
                'bool',
                'boolean',
                'boolstr',
                'integer'
            ])) {
                return $response;
            } elseif (substr($typespec, 0, 4) == 'enum') {
                $options = explode('|', substr($typespec, 5, strlen($typespec) - 6));
                if (count($options) < 2)
                    die('Invalid typespec ' . $typespec);
                return $response;
            } elseif (substr($typespec, - 2) == '[]') { // This is an array of things
                $items = [];
                foreach ($response as $data) {
                    $items[] = $this->_processResponse(substr($typespec, 0, - 2), $data);
                }
                return $items; // replace previous data with proper object
            } else {
                // Not a known type, so assume it's a class => try to instantiate
                //echo '**INSTANTIATING ' . $typespec;
                $obj = new $typespec($response);
                return $obj; // replace previous data with proper object
            }
        }
        die('WUT?');
    }

    public function SendApiCall($operation, $params)
    {
        // $jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOm51bGwsImlzcyI6InFGQll5a0puUXEyNVVKaHZLV2VrTFEiLCJleHAiOjE1ODgwNzU0NTUsImlhdCI6MTU4NzQ3MDYzMH0.-zH-b0ZbeLoeN0jNRws212EMI8i89mXFI5Y6uSQW7iY';
        if (! $this->_jwt)
            $this->generateJWT();

        $syntax = self::$syntax[$operation];
        $curl = curl_init();

        $CURLOPT_URL = $syntax['request']['url'];
        foreach ($syntax['request']['path'] as $pathitem) {
            $x = explode(':', $pathitem);
            if (is_array($params))
                $pp = $params[$x[0]];
            elseif (is_object($params))
                $pp = $params->{$x[0]};
            $CURLOPT_URL = str_replace('{' . $x[0] . '}', $pp, $CURLOPT_URL);
        }
        $CURLOPT_URL = $this->_ApiUrl . '/' . $CURLOPT_URL;

        // echo "<pre>Request\n";
        // print_r($syntax['request']);
        // echo $syntax['request']['method'] . ' URL: ' . $CURLOPT_URL . "\n";
        // echo '</pre>';

        if ( array_key_exists('body',$syntax['request'])) {
            $request = $this->_processResponse($syntax['request']['body'], $params);
            $request = json_encode($request);
            $request = preg_replace('/,\s*"[^"]+":null|"[^"]+":null,?/', '', $request);

            // echo '<pre>'."\n\n".var_export($request,true)."\n\n</pre>";
            //  $request = "{\"topic\":\"6666666666666\",\"start_time\":\"2020-04-23T03:55:00\",\"duration\":60,\"timezone\":\"Europe\\\\/Athens\",\"password\":\"ENof6Tr7aq\",\"agenda\":\"Welcome to Teleconference!\",\"settings\":{\"host_video\":0,\"participant_video\":1,\"join_before_host\":1,\"mute_upon_entry\":1,\"use_pmi\":0,\"auto_recording\":\"cloud\",\"enforce_login\":0,\"waiting_room\":1}}";
            //  echo '<pre>'."\n\n".var_export($request,true)."\n\n</pre>";
            //  die();
            if ($syntax['request']['method'] == 'POST') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            }
        }
        curl_setopt_array($curl, array(
            // CURLOPT_URL => $this->_ApiUrl . $params['url'] . '?' . http_build_query($params),
            CURLOPT_URL => $CURLOPT_URL,

            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $syntax['request']['method'],
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $this->_jwt,
                "content-type: application/json"
            )
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        
        // $response = '{"page_count":1,"page_number":1,"page_size":30,"total_records":3,"meetings":[{"uuid":"W5kBJogRSsm9VVRWT70h9A==","id":378790032,"host_id":"KozSavKkS8GnZyINEabHww","topic":"TEC210 Πέμπτη","type":8,"start_time":"2020-05-14T13:00:00Z","duration":120,"timezone":"Europe/Athens","created_at":"2020-04-01T22:01:22Z","join_url":"https://us04web.zoom.us/j/378790032"},{"uuid":"3CkVWN4iSReFYdSET/AC+Q==","id":674827602,"host_id":"KozSavKkS8GnZyINEabHww","topic":"Συνέλευση ΤΤΗΕ 24/3/2020","type":2,"start_time":"2020-03-24T09:00:00Z","duration":60,"timezone":"Europe/Athens","created_at":"2020-03-23T11:34:32Z","join_url":"https://us04web.zoom.us/j/674827602"},{"uuid":"rFuBzSx5QNiQfaEBFRN8oA==","id":73699571476,"host_id":"KozSavKkS8GnZyINEabHww","topic":"Συνέλευση ΤΤΗΕ 27/4/2020","type":2,"start_time":"2020-04-27T08:00:00Z","duration":180,"timezone":"Europe/Athens","created_at":"2020-04-26T15:46:51Z","join_url":"https://us04web.zoom.us/j/73699571476?pwd=ZnNuY1lnRFlRcjFNbWdabjF4anhydz09"}]}';
        //$response = '{"uuid":"9PK7UyCnSj+lQUMG7x4b3A==","id":75811066704,"host_id":"KozSavKkS8GnZyINEabHww","topic":"6666666666666","type":2,"status":"waiting","start_time":"2020-04-23T00:55:00Z","duration":60,"timezone":"Europe/Athens","agenda":"Welcome to Teleconference!","created_at":"2020-04-27T01:05:49Z","start_url":"https://us04web.zoom.us/s/75811066704?zak=eyJ6bV9za20iOiJ6bV9vMm0iLCJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJjbGllbnQiLCJ1aWQiOiJLb3pTYXZLa1M4R25aeUlORWFiSHd3IiwiaXNzIjoid2ViIiwic3R5IjoxMDAsIndjZCI6InVzMDQiLCJjbHQiOjAsInN0ayI6Il9TdzZCZDRocTZpa04zRlk3T09iMzlhMF9KdG55enhfc3ZvZ0dSVnc0MVkuQmdVZ016QkNOa05yYWpCcVRHOTJlSEk1TlRWMVEyOWxVbWRSV0ZscVlUQlRkVkpBTXprMU5UVmxPREV4TkRaaVkyWmhNVFk1WmpSaU4yRXdZVFkyWmpOaVpETTJaR00wTW1aa09EVXpOVEppTldNNFpXWTBPV000T0dJM09UWmpOelJrT1FBTU0wTkNRWFZ2YVZsVE0zTTlBQVIxY3pBMCIsImV4cCI6MTU4Nzk1Njc1MCwiaWF0IjoxNTg3OTQ5NTUwLCJhaWQiOiJCbFJEMXRQN1JPV2paVFc3VGpyTWpnIiwiY2lkIjoiIn0.bBB8bVAWhtsqyit6nxFYTG0RU7FGE5sru_uzHdq4ibc","join_url":"https://us04web.zoom.us/j/75811066704?pwd=a1lvQzBrVXo0SFpzbElPMVVneGtzQT09","password":"ENof6Tr7aq","h323_password":"868374","pstn_password":"868374","encrypted_password":"a1lvQzBrVXo0SFpzbElPMVVneGtzQT09","settings":{"host_video":false,"participant_video":true,"cn_meeting":false,"in_meeting":false,"join_before_host":true,"mute_upon_entry":true,"watermark":false,"use_pmi":false,"approval_type":2,"audio":"both","auto_recording":"none","enforce_login":false,"enforce_login_domains":"","alternative_hosts":"","close_registration":false,"registrants_confirmation_email":true,"waiting_room":true,"registrants_email_notification":true,"meeting_authentication":false}}';
        //$err = null;
        //$code = 201;
        

        if ($err) { // something went wrong with the request
            echo "cURL Error:" . $err;
            echo 'CODE: ' . $code;
            echo 'response: ' . $response;
        } elseif ($response == '') { // empty response
            var_dump($response);
            curl_close($curl);
            throw new \RuntimeException('Curl error: HTTP CODE:' . $code);
        } else {
            // echo $operation . ' - code:' . $code . ':' . $response;
            if (! array_key_exists($code, $syntax['response'])) {
                die('Unknown response ' . $response);
            }
            $response = json_decode($response,true);

//             echo "<pre>Response\n";
//             print_r($syntax['response']);
//             print_r($response);
//             echo '</pre>';
//             die();

            if (array_key_exists('body', $syntax['response'][$code]) || array_key_exists('headers', $syntax['response'][$code])) {
                // TODO: Do headers, too
                return $this->_processResponse($syntax['response'][$code]['body'], $response);
            } else {
                return false; // whoops, nothing to return!
            }
        }
        curl_close($curl);
    }

    /*
     * USAGE:
     * $creationParams = array(
     * 'name' => 'Meeting Name', -- A name for the meeting (or username)
     * 'meetingId' => '1234', -- A unique id for the meeting
     * 'attendeePw' => 'ap', -- Set to 'ap' and use 'ap' to join = no user pass required.
     * 'moderatorPw' => 'mp', -- Set to 'mp' and use 'mp' to join = no user pass required.
     * 'welcomeMsg' => '', -- ''= use default. Change to customize.
     * 'dialNumber' => '', -- The main number to call into. Optional.
     * 'voiceBridge' => '', -- PIN to join voice. Optional.
     * 'webVoice' => '', -- Alphanumeric to join voice. Optional.
     * 'logoutUrl' => '', -- Default in Zoom.properties. Optional.
     * 'maxParticipants' => '-1', -- Optional. -1 = unlimitted. Not supported in BBB. [number]
     * 'record' => 'false', -- New. 'true' will tell BBB to record the meeting.
     * 'duration' => '0', -- Default = 0 which means no set duration in minutes. [number]
     * 'meta_category' => '', -- Use to pass additional info to BBB server. See API docs to enable.
     * );
     */
    public function getCreateMeetingUrl($creationParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function createMeeting($creationParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $creationParams['operation'] = 'createmeeting';
        $creationParams['userId'] = 'me';
        $parms = new ZoomMeeting($creationParams);
        $x = $this->SendApiCall('createmeeting', $parms);
        return $x ? $x : false;
    }

    /*
     * USAGE:
     * $joinParams = array(
     * 'meetingId' => '1234', -- REQUIRED - A unique id for the meeting
     * 'host' => boolean
     * 'password' => 'ap', -- REQUIRED - The attendee or moderator password, depending on what's passed here
     * );
     */
    public function getJoinMeetingURL($joinParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        print_r($joinParams);
        $x = $this->getMeetingInfo(['meetingId'=>$joinParams['meetingId']]);
        
        if ( !$x )
            return false;
        
        if ( $joinParams['host'] )
            return $x->start_url;
        else
            return $x->join_url;
    }

    /*
     * USAGE:
     * $endParams = array (
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public function getEndMeetingURL($endParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function endMeeting($endParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    /*
     * USAGE:
     * $meetingId = '1234' -- REQUIRED - The unique id for the meeting
     */
    public function getIsMeetingRunningUrl($meetingId)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    /**
     *
     * @param string $meetingId
     * @return boolean
     */
    public function isMeetingRunning($meetingId)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $x = $this->getMeetingInfo(['meetingId'=>$meetingId]);
        
        if ( !$x )
            return false;
        
        return $x->status=='started'; //waiting=not started, finished=started and finished
    }

    /*
     * Simply formulate the getMeetings URL
     * We do this in a separate function so we have the option to just get this
     * URL and print it if we want for some reason.
     */
    public function getGetMeetingsUrl()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function getMeetings()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $x = $this->SendApiCall([
            'operation' => 'listmeetings',
            'userId' => 'me',
            'page_size' => 300 // TODO: This is the max allowed, support paging in future
        ]);
        return $x ? $x : false;
        // die('unimplemented');
        return true;
    }

    /*
     * USAGE:
     * $infoParams = array(
     * 'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     * 'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     * );
     */
    public function getMeetingInfoUrl($infoParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function getMeetingInfo($infoParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        
        if ( isset(self::$_cache['meetings'][$infoParams['meetingId']]) ) {
            echo 'CACHED meeting info '.$infoParams['meetingId'];
            return self::$_cache['meetings'][$infoParams['meetingId']];
        }
        
        $x = $this->SendApiCall('getmeeting', [
            'meetingId' => $infoParams['meetingId']
        ]);
        
        //$x is ZoomMeeting
        self::$_cache['meetings'][$x->id] = $x; //store it even if it failed.
        return $x ? $x : false;
    }

    /*
     * $result = array(
     * 'returncode' => $xml->returncode,
     * 'meetingName' => $xml->meetingName,
     * 'meetingId' => $xml->meetingID,
     * 'createTime' => $xml->createTime,
     * 'voiceBridge' => $xml->voiceBridge,
     * 'attendeePw' => $xml->attendeePW,
     * 'moderatorPw' => $xml->moderatorPW,
     * 'running' => $xml->running,
     * 'recording' => $xml->recording,
     * 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded,
     * 'startTime' => $xml->startTime,
     * 'endTime' => $xml->endTime,
     * 'participantCount' => $xml->participantCount,
     * 'maxUsers' => $xml->maxUsers,
     * 'moderatorCount' => $xml->moderatorCount,
     * );
     * // Then interate through attendee results and return them as part of the array:
     * foreach ($xml->attendees->attendee as $a) {
     * $result[] = array(
     * 'userId' => $a->userID,
     * 'fullName' => $a->fullName,
     * 'role' => $a->role
     * );
     * }
     */

    /*
     * USAGE:
     * $recordingParams = array(
     * 'meetingId' => '1234', -- OPTIONAL - comma separate if multiple ids
     * );
     */
    public function getRecordingsUrl($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function getRecordings($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    /*
     * foreach ($xml->recordings->recording as $r) {
     * $result[] = array(
     * 'recordId' => $r->recordID,
     * 'meetingId' => $r->meetingID,
     * 'name' => $r->name,
     * 'published' => $r->published,
     * 'startTime' => $r->startTime,
     * 'endTime' => $r->endTime,
     * 'playbackFormatType' => $r->playback->format->type,
     * 'playbackFormatUrl' => $r->playback->format->url,
     * 'playbackFormatLength' => $r->playback->format->length,
     * 'metadataTitle' => $r->metadata->title,
     * 'metadataSubject' => $r->metadata->subject,
     * 'metadataDescription' => $r->metadata->description,
     * 'metadataCreator' => $r->metadata->creator,
     * 'metadataContributor' => $r->metadata->contributor,
     * 'metadataLanguage' => $r->metadata->language,
     * // Add more here as needed for your app depending on your
     * // use of metadata when creating recordings.
     * );
     * }
     */

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * 'publish' => 'true', -- REQUIRED - boolean: true/false
     * );
     */
    public function getPublishRecordingsUrl($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function publishRecordings($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    /*
     * USAGE:
     * $recordingParams = array(
     * 'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     * );
     */
    public function getDeleteRecordingsUrl($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function deleteRecordings($recordingParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }

    public function clearCaches()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        die('unimplemented');
        return true;
    }
    
    public function generatePassword() {
        $length = 10; //max password length in zoom is 10
        return substr(str_shuffle(implode(array_merge(range(0, 9), range('A', 'Z'), range('a', 'z')))), 0, $length);
    }
    
    public function generateMeetingId() {
        return NULL; //Zoom doesn't allow you to specify meeting IDs
    }
    
    public function getServerUsers(TcServer $server) {
        return 0; //You need dashboard information for this
    }
    
    
}

class TcZoomSession extends TcDbSession
{
    use paramsTrait;

    private $params = [
        'required' => [],
        'optional' => [
            'id:number',
            'join_url',
            'host_id:number',
            'timezone',
            'created_at',
            'type:number',
            'uuid',

            'topic',
            'duration:number',
            'agenda',
            'start_time'
        ]
    ];

    // public $uuid, $id, $host_id, $topic, $duration, $timezone, $join_url, $agenda;

    /**
     *
     * @var format: date-time
     */
    public $start_time;

    /**
     *
     * @var format: date-time
     */
    public $created_at;

    /**
     *
     * @var 1-instant meeting, 2-scheduled meeting, 3-recurring meeting with no fixed time, 8-recurring meeting with fixed time
     */
    public $type;

    function __construct(array $cParams = [])
    {
        parent::__construct($cParams);
        $validparams = $this->_checkParams($this->params, $cParams);
        foreach ($validparams as $n => $v) {
            $this->{$n} = $v;
        }
    }

    /**
     *
     * @brief Disable bbb session (locally)
     * @return bool
     */
    function disable()
    {
        // TODO:attempt to disable on server
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return parent::disable(); // disable locally
    }

    /**
     *
     * @brief enable bbb session (locally)
     * @param int $session_id
     * @return bool
     */
    function enable()
    {
        // TODO:attempt to enable on server
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return parent::enable();
    }

    /**
     *
     * @brief delete bbb sessions (locally)
     * @param int $session_id
     * @return bool
     */
    function delete()
    {
        // TODO:check if it's running and if so, KILL IT NOW
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return parent::delete(); // delete from DB
    }

    /**
     *
     * @brief get number of meeting users
     * @global type $langBBBGetUsersError
     * @global type $langBBBConnectionError
     * @global type $course_code
     * @param string $salt
     * @param string $bbb_url
     * @param string $meeting_id
     * @param string $pw
     * @return int
     */
    function get_meeting_users($pw)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return 0; //NEEDS DASHBOARD ACCESS: Business accounts and up, or implement webhooks
    }

    public function isFull()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return false; //NEEDS DASHBOARD ACCESS: Business accounts and up, or implement webhooks
    }

    /**
     *
     * @brief Join a user to the session
     * @param array $joinParams
     * @return boolean
     */
    public function join_user(array $joinParams)
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $this->getRunningServer();

        $fullname = $this->_requiredParams([
            'username',
            'name'
        ], $joinParams);
        $pw = $this->_requiredParam('pw', $joinParams);
        $uid = $this->_requiredParam('uid', $joinParams);

        if (($this->mod_pw && $pw != $this->mod_pw) || ($this->mod_att && $pw != $this->att_pw))
            return false; // die('Invalid password');

        if ($this->isFull())
            return false;

        $joinParams = array(
            'meetingId' => $this->meeting_id, // REQUIRED - We have to know which meeting to join.
            'fullName' => $fullname, // REQUIRED - The user display name that will show in the BBB meeting.
            'password' => $pw, // REQUIRED - Must match either attendee or moderator pass for meeting.
            'userID' => $uid // OPTIONAL - string
        );

        $bbbApi = new Zoom([
            'server' => $this->server
        ]);
        $uri = $bbbApi->getJoinMeetingURL($joinParams);
        redirect($uri);
        // exit; //FIXME: Probably need to check flow in callers to enforce this there, some plugins may need to continue?
        return true;
    }

    /**
     *
     * @brief Check is this session is known to server (scheduled)
     * @return boolean
     */
    public function IsKnownToServer()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $api = new Zoom([
            'server' => $this->server
        ]);

        if (! $api)
            die('Api creation failed for ' . __METHOD__);
            
        $x = $api->getMeetingInfo([
            'meetingId' => $this->meeting_id
        ]);
        return $x && $x->id;
    }

    /**
     *
     * @brief check if session is running
     * @param string $meeting_id
     * @return boolean
     */
    function IsRunning()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        // First check if it's flagged as running in the database
        if (! parent::IsRunning())
            return false;

        $api = new Zoom([
            'server' => $this->server
        ]);

        if (! $api)
            die('Api creation failed for ' . __METHOD__);
            
        return $api->isMeetingRunning($this->meeting_id);
    }

    /**
     *
     * @brief BBB does not really use the schedule->start flow. Sessions are created/started when people join. Empty sessions are purged quickly.
     * @return boolean
     */
    function create_meeting()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        global $course_code, $langBBBWelcomeMsg;

        // If a maximum limit of simultaneous meeting users has been set, use it
        if (! $this->sessionUsers || $this->sessionUsers <= 0) {
            $users_to_join = $this->sessionUsers;
        } else { // otherwise just count participants
            $users_to_join = $this->usersToBeJoined(); // this is DB-expensive so call before the loop
        }

        $start_date = substr($this->start_date, 0, 10) . 'T' . substr($this->start_date, 11);

        $duration = 0;
        if (($this->start_date != null) and ($this->end_date != null)) {
            $date_start = new DateTime($this->start_date);
            $date_end = new DateTime($this->end_date);
            $hour_duration = $date_end->diff($date_start)->h; // hour
            $min_duration = $date_end->diff($date_start)->i; // minutes
            $duration = $hour_duration * 60 + $min_duration;
        }
        if ($duration == 0) {
            echo __METHOD__ . ' Zero duration meetings not implemented for zoom - defaulting to 1 hour';
            $duration = 60;
        }

        $server = TcServer::LoadById($this->running_at);
        $zoom = new Zoom([
            'server' => $server
        ]);

        $creationParams = array(
            // 'meetingId' => $this->meeting_id, // REQUIRED - given by API on creation, you don't get to chose this with ZOOM
            'timezone' => date_default_timezone_get(),
            'start_time' => $start_date, // FORMAT yyyy-MM-ddTHH:mm:ss (no Z at the end, we're not using GMT)
            'topic' => $this->title,
            'password' => $this->att_pw, // Match this value in getJoinMeetingURL() to join as attendee.
                                          // 'moderatorPw' => $this->mod_pw, // Match this value in getJoinMeetingURL() to join as moderator. -- no moderator password in zoom
            'agenda' => $langBBBWelcomeMsg, // ''= use default. Change to customize.
                                             // 'logoutUrl' => '', // not implemented in ZOOM
                                             // 'maxParticipants' => $this->sessionUsers, // not implemented in ZOOM
            'duration' => $duration, // REQUIRED in zoom
                                      // 'meta_category' => '', // Use to pass additional info to BBB server. See API docs.
            'settings' => new ZoomMeetingSettings([
                'host_video' => false,
                'participant_video' => true,
                'join_before_host' => true,
                'mute_upon_entry' => true,
                'use_pmi' => false,
                'auto_recording' => ($this->record ? 'cloud' : 'none'), // this can have 'local', too but it's a dangerous default, so skip it
                'enforce_login' => false,
                'waiting_room' => true
                // 'contact_name'=>$username
                // 'contact_email'=>$useremail
            ])
        );

        $result = $zoom->createMeeting($creationParams);
        
        if (! $result) {
            return false;
        }
        
        $x = Database::get()->querySingle('UPDATE tc_session SET `meeting_id`=?s, `mod_pw`=NULL, `att_pw`=?s WHERE id=?d',
                $result->id, $result->password,$this->session_id);
        if ( $x === null )
            die('DB SESSION UPDATE FAILED');

        return true;
    }

    /**
     *
     * @global type $course_code
     * @global type $langBBBCreationRoomError
     * @global type $langBBBConnectionError
     * @global type $langBBBConnectionErrorOverload
     * @global type $langBBBWelcomeMsg
     * @param string $title
     * @param string $meeting_id
     * @param string $mod_pw
     * @param string $att_pw
     * @param string $record
     *            'true' or 'false'
     */
    function start_meeting()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        return true; //You don't "START" a meeting remotely, you must join it to do this.
    }

    public function clearCaches()
    {
        echo '[ZOOMAPI] ' . __METHOD__ . '<br>';
        $bbb = new Zoom([
            'server' => $server
        ]);
        $bbb->clearCaches();
    }
}