<?php

abstract class TcApi {
    
    /*
     USAGE:
     $creationParams = array(
     'name' => 'Meeting Name', -- A name for the meeting (or username)
     'meetingId' => '1234', -- A unique id for the meeting
     'attendeePw' => 'ap', -- Set to 'ap' and use 'ap' to join = no user pass required.
     'moderatorPw' => 'mp', -- Set to 'mp' and use 'mp' to join = no user pass required.
     'welcomeMsg' => '', -- ''= use default. Change to customize.
     'dialNumber' => '', -- The main number to call into. Optional.
     'voiceBridge' => '', -- PIN to join voice. Optional.
     'webVoice' => '', -- Alphanumeric to join voice. Optional.
     'logoutUrl' => '', -- Default in bigbluebutton.properties. Optional.
     'maxParticipants' => '-1', -- Optional. -1 = unlimitted. Not supported in BBB. [number]
     'record' => 'false', -- New. 'true' will tell BBB to record the meeting.
     'duration' => '0', -- Default = 0 which means no set duration in minutes. [number]
     'meta_category' => '', -- Use to pass additional info to BBB server. See API docs to enable.
     );
     */
    public abstract function getCreateMeetingUrl($creationParams);
    public abstract function createMeeting($creationParams);
    
    /*
     USAGE:
     $joinParams = array(
     'meetingId' => '1234', -- REQUIRED - A unique id for the meeting
     'username' => 'Jane Doe', -- REQUIRED - The name that will display for the user in the meeting
     'password' => 'ap', -- REQUIRED - The attendee or moderator password, depending on what's passed here
     'createTime' => '', -- OPTIONAL - string. Leave blank ('') unless you set this correctly.
     'userID' => '', -- OPTIONAL - string
     'webVoiceConf' => '' -- OPTIONAL - string
     );
     */
    public abstract function getJoinMeetingURL($joinParams);
    
    /* USAGE:
     $endParams = array (
     'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     );
     */
    public abstract function getEndMeetingURL($endParams);
    public abstract function endMeeting($endParams);
    
    /* USAGE:
     $meetingId = '1234' -- REQUIRED - The unique id for the meeting
     */
    public abstract function getIsMeetingRunningUrl($meetingId);
    public abstract function isMeetingRunning($meetingId);
    
    /* Simply formulate the getMeetings URL
     We do this in a separate function so we have the option to just get this
     URL and print it if we want for some reason.
     */
    public abstract function getGetMeetingsUrl();
    public abstract function getMeetings();
    
    /* USAGE:
     $infoParams = array(
     'meetingId' => '1234', -- REQUIRED - The unique id for the meeting
     'password' => 'mp' -- REQUIRED - The moderator password for the meeting
     );
     */
    public abstract function getMeetingInfoUrl($infoParams);
    public abstract function getMeetingInfo($infoParams);
    /*$result = array(
        'returncode' => $xml->returncode,
        'meetingName' => $xml->meetingName,
        'meetingId' => $xml->meetingID,
        'createTime' => $xml->createTime,
        'voiceBridge' => $xml->voiceBridge,
        'attendeePw' => $xml->attendeePW,
        'moderatorPw' => $xml->moderatorPW,
        'running' => $xml->running,
        'recording' => $xml->recording,
        'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded,
        'startTime' => $xml->startTime,
        'endTime' => $xml->endTime,
        'participantCount' => $xml->participantCount,
        'maxUsers' => $xml->maxUsers,
        'moderatorCount' => $xml->moderatorCount,
    );
    // Then interate through attendee results and return them as part of the array:
    foreach ($xml->attendees->attendee as $a) {
        $result[] = array(
            'userId' => $a->userID,
            'fullName' => $a->fullName,
            'role' => $a->role
        );
    }*/
    
    /* USAGE:
     $recordingParams = array(
     'meetingId' => '1234', -- OPTIONAL - comma separate if multiple ids
     );
     */
    public abstract function getRecordingsUrl($recordingParams);
    public abstract function getRecordings($recordingParams);
    /*foreach ($xml->recordings->recording as $r) {
        $result[] = array(
            'recordId' => $r->recordID,
            'meetingId' => $r->meetingID,
            'name' => $r->name,
            'published' => $r->published,
            'startTime' => $r->startTime,
            'endTime' => $r->endTime,
            'playbackFormatType' => $r->playback->format->type,
            'playbackFormatUrl' => $r->playback->format->url,
            'playbackFormatLength' => $r->playback->format->length,
            'metadataTitle' => $r->metadata->title,
            'metadataSubject' => $r->metadata->subject,
            'metadataDescription' => $r->metadata->description,
            'metadataCreator' => $r->metadata->creator,
            'metadataContributor' => $r->metadata->contributor,
            'metadataLanguage' => $r->metadata->language,
            // Add more here as needed for your app depending on your
            // use of metadata when creating recordings.
        );
    }*/

        
    /* USAGE:
     $recordingParams = array(
     'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     'publish' => 'true', -- REQUIRED - boolean: true/false
     );
    */
    public abstract function getPublishRecordingsUrl($recordingParams);
    public abstract function publishRecordings($recordingParams);
    
    /* USAGE:
     $recordingParams = array(
     'recordId' => '1234', -- REQUIRED - comma separate if multiple ids
     );
     */
    public abstract function getDeleteRecordingsUrl($recordingParams);
    public abstract function deleteRecordings($recordingParams);
    
    //returns meetings info in meetings[]
    public abstract function getMeetingsInfo();
}