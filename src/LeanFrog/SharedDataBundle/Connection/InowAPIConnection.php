<?php

namespace LeanFrog\SharedDataBundle\Connection;

class InowAPIConnection {

    /**
     * URLs for Huntsville Zoning API endpoints
     *
     * @var array
     */
    private $end_points = [

        'states' => 'states',
        'ethnicities' => 'ethnicities',
        'ethnicities_for_persons' => 'persons/ethnicities',
        'addresses_for_persons' => 'persons/addresses',
        'email_for_persons' => 'persons/emailaddresses',
        'schools' => 'schools',
        'gradelevels' => 'gradelevels',
        'genders' => 'genders',
        'academic_sessions' => 'acadsessions',
        'students_by_session_and_status' => '%s/students?status=%s',
        'staff' => 'staff',
        'schedules_by_session' => '%s/students/schedule',
        'sections_by_session' => '%s/sections',
        'staff_positions_by_school' => 'schools/%s/staffClassifications',
        'homerooms_by_session' => '%s/homerooms',
        'student_homerooms_by_session' => '%s/students/homerooms'
    ];

    /**
     * @param $endpoint
     */
    public function get_response($endpoint, $parameters = null){

        $endpoint = ( isset( $this->end_points[ $endpoint ] ) ) ? $this->end_points[ $endpoint ] : $endpoint;

        if( strpos($endpoint, '%') !== false ){
            $endpoint = vsprintf( $endpoint, $parameters );
        }

        $url = MYPICK_CONFIG['inow_api']['url'] . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0.1');  // mPDF 5.7.4
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'ApplicationKey: leanfrog B/1F8Y/ToQlRufi/0DgoaKLOBcrd3PpT+wFJL6Sdwy2Z8vZP6GamF7KDmU2nb+Cn/ayElMuxwrWreWae06oNhrCE29gnEizIdFuS3bICs3eFOe7bnRsVyPbPE+4CmOc9QzI5pTbUv9aH/7TrSVVSYcL5WaLzeEwnl2+hlj9c2dw=',
        ));
        curl_setopt ( $ch , CURLOPT_RETURNTRANSFER , 1 );
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); //timeout after 30 seconds
        curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
        curl_setopt( $ch, CURLOPT_USERPWD, MYPICK_CONFIG['inow_api']['username'].":". MYPICK_CONFIG['inow_api']['password'] );
        $data = curl_exec($ch);
        curl_close($ch);

        if( !$data ) {
            //var_dump( $url );
            return [];
        }

        $decoded_data = json_decode($data);

        if( json_last_error() != JSON_ERROR_NONE ){

            var_dump( 'JSON error: ' . json_last_error() );
            return false;
        }
        return $decoded_data;
    }

    public function get_course_type_from_section( $section ){

        if( !is_object( $section ) ){
            var_dump( $section ); die;
        }

        switch( $section->CourseTypeId ){
            case 1:
                $course_type = 'business';
                break;
            case 2:
                $course_type = 'elective';
                break;
            case 3:
                if( strpos( strtolower( $section->ShortName ), 'read') !== false ){
                    $course_type = 'reading';
                } else {
                    $course_type = 'english';
                }
                break;
            case 4:
                $course_type = 'art';
                break;
            case 5:
                $course_type = 'foreign language';
                break;
            case 6:
                $course_type = 'health';
                break;
            case 7:
                $course_type = 'math';
                break;
            case 8:
                $course_type = '??' . $section->CourseTypeId . $section->ShortName;
                break;
            case 9:
                $course_type = 'physical education';
                break;
            case 10:
                $course_type = 'science';
                break;
            case 11:
                $course_type = 'social studies';
                break;
            default:
                $course_type = '??' . $section->CourseTypeId . $section->ShortName;
                break;
        }
        return $course_type;
    }

    public function get_staff_position_from_id( $position_id ){
        switch( $position_id ){
            case 2: //'Administrator'
            case 11: //'School Administrator';
                $position = 'admin';
                break;
            case 4: //'Counselor'
                $position = 'counselor';
                break;
            case 62:
                $position = 'Administrative Leave';
                break;
            case 16:
                $position = 'Aide';
                break;
            case 19:
                $position = 'Athletic Director';
                break;
            case 12:
                $position = 'Bookkeeper';
                break;
            case 68:
                $position = 'Bus Aide';
            break;
            case 8:
                $position = 'Bus Driver';
            break;
            case 31:
                $position = 'Certified Unlic Med Asst';
                break;
            case 63:
                $position = 'Chalkable Group';
                break;
            case 15:
                $position = 'CNP';
                break;
            case 55:
                $position = 'CNP Assistant';
                break;
            case 58:
                $position = 'CO Office Personnel';
                break;
            case 52:
                $position = 'Coach-Career';
                break;
            case 47:
                $position = 'Coach-Graduation';
                break;
            case 71:
                $position = 'Coach-Instruct Technology';
                break;
            case 53:
                $position = 'Coach-Instructional';
                break;
            case 21:
                $position = 'Coach-Intervention';
                break;
            case 44:
                $position = 'Coach-Math';
                break;
            case 43:
                $position = 'Coach-Reading';
                break;
            case 65:
                $position = 'Contract';
                break;
            case 45:
                $position = 'Curriculum Specialist';
                break;
            case 56:
                $position = 'Custodian-Maintenance';
                break;
            case 13:
                $position = 'Dean of Students';
                break;
            case 64:
                $position = 'Disable - Leaving';
                break;
            case 29:
                $position = 'District School Nurse';
                break;
            case 70:
                $position = 'District Service Maint CT';
                break;
            case 22:
                $position = 'District Users';
                break;
            case 32:
                $position = 'Health Data Personnel';
                break;
            case 28:
                $position = 'IHealth Admin';
                break;
            case 17:
                $position = 'Inactive';
                break;
            case 60:
                $position = 'Interpreter';
                break;
            case 35:
                $position = 'Intervention Administrator';
                break;
            case 38:
                $position = 'ISS In School Suspension';
                break;
            case 61:
                $position = 'Leave Of Absence';
                break;
            case 14:
                $position = 'Librarian';
                break;
            case 54:
                $position = 'New - Disabled';
                break;
            case 7:
                $position = 'Nurse';
                break;
            case 20:
                $position = 'Office Assistant';
                break;
            case 5:
                $position = 'Other';
                break;
            case 36:
                $position = 'PE Teacher Coach for Fitness';
                break;
            case 72:
                $position = 'Psychologist';
                break;
            case 24:
                $position = 'Receptionist';
                break;
            case 10:
                $position = 'Registrar';
                break;
            case 39:
                $position = 'RTI Chair';
                break;
            case 9:
                $position = 'Secretary';
                break;
            case 57:
                $position = 'Security Monitor';
                break;
            case 23:
                $position = 'SETS Staff';
                break;
            case 51:
                $position = 'Social Worker';
                break;
            case 33:
                $position = 'Special Logins';
                break;
            case 59:
                $position = 'SRO';
                break;
            case 69:
                $position = 'Substitute';
                break;
            case 50:
                $position = 'Substitute Counselor';
                break;
            case 40:
                $position = 'Substitute Receptionist';
                break;
            case 30:
                $position = 'Substitute School Nurse';
                break;
            case 41:
                $position = 'Substitute Secretary';
                break;
            case 18:
                $position = 'Substitute Teacher';
                break;
            case 42:
                $position = 'Summer School Facilitators';
                break;
            case 3:
                $position = 'Support';
                break;
            case 27:
                $position = 'TCT Teacher';
                break;
            case 1:
                $position = 'Teacher';
                break;
            case 49:
                $position = 'Teacher STARS';
                break;
            case 48:
                $position = 'Teacher Success Prep';
                break;
            case 37:
                $position = 'Tech Coordinator';
                break;
            case 67:
                $position = 'Tech Staff';
                break;
            case 73:
                $position = 'Transportation Office';
                break;
            case 46:
                $position = 'Turnaround Administrator';
                break;
            default:
                $position = 'other';
                break;

        }
        return $position;
    }
}
