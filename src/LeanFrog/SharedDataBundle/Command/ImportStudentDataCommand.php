<?php

namespace LeanFrog\SharedDataBundle\Command;

use LeanFrog\SharedDataBundle\Entity\Student;
use LeanFrog\SharedDataBundle\Connection\InowAPIConnection;
use LeanFrog\SharedDataBundle\Command\Traits\ImportTraits;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStudentDataCommand extends ContainerAwareCommand {
    use ImportTraits;

    private $entity_manager;
    private $connection;
    private $year;

    protected function configure() {

        $this
            ->setName( 'shared:student:import' )
            ->setDescription( 'Import Student data from API' )
            ->setHelp( <<<EOF
The <info>%command.name%</info> command imports student data from the iNow database API.

<info>php %command.full_name%</info>

EOF
            );
    }

    protected function execute( InputInterface $input , OutputInterface $output ) {

        ini_set('memory_limit','2048M');

        $env = $this->getContainer()->get( 'kernel' )->getEnvironment();
        var_dump( 'Running Student Import in environment: ' . $env );

        $this->entity_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->entity_manager->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->connection = new InowAPIConnection();

        $this->import_students_from_inow();

        $this->entity_manager->flush();
        var_dump( 'Completed Student Import in envinronment: ' . $env );
    }


    public function import_students_from_inow(){

        $state_hash = [];
        $states = $this->connection->get_response( 'states' );

        foreach( $states as $state ){
            $state_hash[ $state->Id ] = $state->Code;
        }

        $race_hash = array();
        $ethnicities = $this->connection->get_response( 'ethnicities' );
        foreach( $ethnicities as $ethnicity ){
            $race_hash[ $ethnicity->Id ] = $ethnicity->Name;
        }

        // Get all student ethnicities
        $student_race_hash = array();
        $ethnicities = $this->connection->get_response( 'ethnicities_for_persons' );
        foreach( $ethnicities as $ethnicity ){
            if( $ethnicity->IsPrimary ) {
                $student_race_hash[$ethnicity->PersonId] = $race_hash[ $ethnicity->EthnicityId ];
            }
        }

        // Get all student addresses
        $student_address_hash = array();
        $addresses = $this->connection->get_response( 'addresses_for_persons' );
        foreach( $addresses as $address ){

            if( $address->IsPhysical ) {
                $student_address_hash[ $address->PersonId ] = [
                    'address' => implode( ' ', [ $address->AddressLine1, $address->AddressLine2 ] ),
                    'city' => $address->City,
                    'state' => ( isset( $state_hash[ $address->StateId ] ) ) ? $state_hash[ $address->StateId ] : 'AL',
                    'zip' => $address->PostalCode
                ];
            }
        }

        // Get all student email addresses

        $state_id_email_hash = $this->maybe_import_emails_from_file();
        if( !$state_id_email_hash ){
            $student_email_hash = array();
            $emails = $this->connection->get_response( 'email_for_persons' );
            foreach( $emails as $email ){

                if( $email->IsPrimary ) {
                    $student_email_hash[ $email->PersonId ] = $email->EmailAddress;
                }
            }
        }

        // Get all schools
        $schools_hash = array();
        $schools = $this->connection->get_response( 'schools' );
        foreach( $schools as $school ){
            $schools_hash[ $school->Id ] = $school->Name;
        }

        // Get all Grade Levels
        $grade_level_hash = array();
        $grade_levels = $this->connection->get_response( 'gradelevels' );
        foreach( $grade_levels as $grade_level ){
            $grade_level_hash[ $grade_level->Id ] = intval( $grade_level->Name );
        }

        // Get all Genders
        $gender_hash = array();
        $genders = $this->connection->get_response( 'genders' );
        foreach( $genders as $gender ){
            $gender_hash[ $gender->Id ] = $gender->Name;
        }

        // Get all Academic Sessions
        $sessions_hash = [
            'current' => [],
            'late' => []
        ];
        $acad_sessions = $this->connection->get_response( 'acadsessions' );


        foreach($acad_sessions as $acad_session){

            if( $acad_session->AcadYear == $this->getYear() ){
                $sessions_hash['current'][ $acad_session->Id ] = $acad_session;
            } else if( $acad_session->AcadYear == $this->getYear() - 1  ){
                $sessions_hash['last'][ $acad_session->Id ] = $acad_session;
            }
        }

        uasort( $sessions_hash['current'], function ($a, $b)
        {
            if ($a->StartDate == $b->StartDate) {
                return 0;
            }
            return ($a->StartDate < $b->StartDate) ? 1 : -1;
        });

        uasort( $sessions_hash['last'], function ($a, $b)
        {
            if ($a->StartDate == $b->StartDate) {
                return 0;
            }
            return ($a->StartDate < $b->StartDate) ? 1 : -1;
        });

        $new = 0;
        $student_objects = array();
        $duplicates = [];

        $enrollment_statuses = [
            'Enrolled',
            'Registered'
        ];

        foreach ($sessions_hash as $sessions) {

            foreach( $enrollment_statuses as $status ) {

                foreach ($sessions as $session_id => $acad_session) {

                    $students = $this->connection->get_response( 'students_by_session_and_status', [$session_id, $status] );

                    if (is_object($students)) {
                        $students = array();
                    };

                    $find_students = [];
                    foreach ($students as $index => $student) {

                        if (!is_object($student)) {
                            unset($students[$index]);
                        } else {
                            $find_students[] = $student->StateIdNumber;
                        }
                    }

                    $found_students = $this->entity_manager->getRepository('lfSharedDataBundle:Student')->findBy(['stateID' => $find_students]);
                    $found_students_hash = [];

                    foreach ($found_students as $found_student) {
                        $found_students_hash[$found_student->getStateID()] = $found_student;
                    }
                    unset($found_students);

                    foreach ($students as $student) {

                        if (!isset($student_objects[$student->StateIdNumber])) {

                            if (empty($found_students_hash[$student->StateIdNumber])) {
                                $student_object = new Student();
                                $student_object->setStateID($student->StateIdNumber);
                                $new++;
                            } else {
                                $student_object = $found_students_hash[$student->StateIdNumber];
                            }

                            $birthday = new \DateTime(str_replace('T', ' ', $student->DateOfBirth));
                            $race = isset($student_race_hash[$student->Id]) ? $student_race_hash[$student->Id] : '';
                            $address = (isset($student_address_hash[$student->Id])) ? $student_address_hash[$student->Id]['address'] : '';
                            $city = (isset($student_address_hash[$student->Id])) ? $student_address_hash[$student->Id]['city'] : '';
                            $state = (isset($student_address_hash[$student->Id])) ? $student_address_hash[$student->Id]['state'] : 'AL';
                            $zip = (isset($student_address_hash[$student->Id])) ? $student_address_hash[$student->Id]['zip'] : '';
                            $currentSchool = (isset($schools_hash[$acad_session->SchoolId])) ? $schools_hash[$acad_session->SchoolId] : '';
                            $nextSchool = (isset($schools_hash[$student->NextYearSchoolId])) ? $schools_hash[$student->NextYearSchoolId] : '';
                            $grade_level = (isset($grade_level_hash[$student->GradeLevelId])) ? $grade_level_hash[$student->GradeLevelId] : '';
                            $gender = isset($gender_hash[$student->GenderId]) ? $gender_hash[$student->GenderId] : '';

                            if( $state_id_email_hash ){
                                $email = (isset($state_id_email_hash[$student->StateIdNumber])) ? $state_id_email_hash[$student->StateIdNumber] : '';
                            } else {
                                $email = (isset($student_email_hash[$student->Id])) ? $student_email_hash[$student->Id] : '';
                            }

                            $student_object->setFirstName($student->FirstName);
                            $student_object->setLastName($student->LastName);
                            $student_object->setRace($race);
                            $student_object->setBirthday($birthday);
                            $student_object->setAddress($address);
                            $student_object->setCity($city);
                            $student_object->setState($state);
                            $student_object->setZip($zip);
                            $student_object->setCurrentSchool($currentSchool);
                            $student_object->setNextSchool($nextSchool);
                            $student_object->setGradeLevel($grade_level);
                            $student_object->setIsDistrictStudent(false);
                            $student_object->setIsHispanic($student->IsHispanic);
                            $student_object->setGender($gender);
                            $student_object->setEmail($email);
                            $student_object->setDborId( $student->Id );

                            $this->entity_manager->persist($student_object);
                            $this->maybe_flush();
                            $objects_entry = [
                                'student' => $student_object,
                                'sessions' => [],
                            ];
                            $objects_entry['sessions'][] = $acad_session;
                            $student_objects[$student->StateIdNumber] = $objects_entry;
                        } else {
                            $is_duplicate = false;
                            foreach ($student_objects[$student->StateIdNumber]['sessions'] as $previous_session) {
                                if ($acad_session->StartDate >= $previous_session->StartDate) {
                                    $is_duplicate = true;
                                }
                            }

                            if ($is_duplicate) {
                                $student_objects[$student->StateIdNumber]['sessions'][] = $acad_session;
                                if (!in_array($student->StateIdNumber, $duplicates)) {

                                    $duplicates[] = $student->StateIdNumber;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function maybe_import_emails_from_file(){

        $email_file = ( defined( 'MYPICK_CONFIG' ) && !empty( MYPICK_CONFIG['student_email_file'] ) ) ? MYPICK_CONFIG['student_email_file'] : false;

        $state_id_email_hash = [];

        if( $email_file ){

            if( !file_exists( $email_file ) ) {
                throw new \Exception( 'File could not be found. Please provide a file to import. Make sure to use the full path of the file.' );
            }

            try {
                $fp = fopen( $email_file , 'r' );

                // Headrow
                $head = fgetcsv( $fp , 4096 , ',' , '"' , '\\' );
                $studentColumns = array_merge( array_slice( $head , 0 , 13 ) , array_slice( $head , -1 ) );
                $gradeColumns = array_merge( array_slice( $head , 0 , 1 ) , array_slice( $head , -8 , 7 ) );
            } catch( \Exception $e ) {
                throw $e;
            }


            while( $column = fgetcsv( $fp , 4096 , ',' , '"' , '\\' ) ) {
                if( !empty( $column[5] ) ){
                    $state_id_email_hash[ $column[0] ] = $column[5];
                }
            }
        }

        return $state_id_email_hash;
    }
}