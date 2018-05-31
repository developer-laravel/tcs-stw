<?php

namespace LeanFrog\SharedDataBundle\Command;

use LeanFrog\SharedDataBundle\Entity\Student;
use LeanFrog\SharedDataBundle\Entity\StudentData;
use LeanFrog\SharedDataBundle\Entity\Teacher;
use LeanFrog\SharedDataBundle\Command\Traits\ImportTraits;
use LeanFrog\SharedDataBundle\Connection\InowAPIConnection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportTeacherDataCommand extends ContainerAwareCommand {
    use ImportTraits;

    private $year;

    protected function configure() {

        $this
            ->setName( 'shared:teacher:import' )
            ->setDescription( 'Import Teacher data from API' )
            ->setHelp( <<<EOF
The <info>%command.name%</info> command imports teacher data from the iNow database API.

<info>php %command.full_name%</info>

EOF
            );
    }

    protected function execute( InputInterface $input , OutputInterface $output ) {

        ini_set('memory_limit','5120M');

        $env = $this->getContainer()->get( 'kernel' )->getEnvironment();
        var_dump( 'Running Teacher Import in environment: ' . $env );

        $this->entity_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->entity_manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->connection = new InowAPIConnection();
        $this->import_teachers_from_inow();

        $this->entity_manager->flush();
        var_dump( 'Completed Teacher Import in envinronment: ' . $env );
     }

    public function import_teachers_from_inow(){

        $delete_teachers = $this->entity_manager->createQueryBuilder()
            ->delete('lfSharedDataBundle:StudentData', 'sd')
            ->where("sd.metaKey like '%_teacher%'")
            ->getQuery();
        $delete_teachers->execute();

        $delete_admins = $this->entity_manager->createQueryBuilder()
            ->delete('lfSharedDataBundle:StudentData', 'sd')
            ->where("sd.metaKey like 'admin_%'")
            ->getQuery();
        $delete_admins->execute();

        $delete_counselors = $this->entity_manager->createQueryBuilder()
            ->delete('lfSharedDataBundle:StudentData', 'sd')
            ->where("sd.metaKey like 'counselor_%'")
            ->getQuery();
        $delete_counselors->execute();

        $active_teachers = [];

        $staff_hash = [];
        $staff = $this->connection->get_response( 'staff' );
        foreach( $staff as $staff_member ){
            $staff_hash[ $staff_member->Id ] = $staff_member;
        }

        $teacher_email_hash = array();
        $emails = $this->connection->get_response( 'email_for_persons' );
        foreach( $emails as $email ){

            if( $email->IsPrimary ) {
                $teacher_email_hash[ $email->PersonId ] = $email->EmailAddress;
            }
        }
        var_dump( 'teachers done');

        $school_admin_hash = [];
        $school_counselors_hash = [];
        $schools = $this->connection->get_response( 'schools' );
        foreach( $schools as $school ){
            $classifications = $this->connection->get_response( 'staff_positions_by_school', [$school->Id] );

            foreach( $classifications as $staffer ){

                if( isset( $teacher_email_hash[ $staffer->StaffId ] ) ){

                    $staff_name = ( isset( $staff_hash[$staffer->StaffId] ) )
                        ? $staff_hash[$staffer->StaffId]->FirstName .' '. $staff_hash[$staffer->StaffId]->LastName
                        : '';

                    if( $this->connection->get_staff_position_from_id( $staffer->StaffClassificationId ) == 'counselor'){

                        if( empty( $school_counselors_hash[ $school->Id ] ) ){
                            $school_counselors_hash[ $school->Id ] = [];
                        }

                        if( !$school_counselors_hash[ $school->Id ] ) {
                            $school_counselors_hash[ $school->Id ][] = [
                                'email' => $teacher_email_hash[ $staffer->StaffId ],
                                'name' => $staff_name
                            ];
                        }
                        if( !in_array($staffer->StaffId, $active_teachers ) ){
                            $active_teachers[] = $staffer->StaffId;
                        }

                    } else if(  $this->connection->get_staff_position_from_id( $staffer->StaffClassificationId ) == 'admin'
                                && $staffer->IsPrimary
                    ){
                        $school_admin_hash[ $school->Id ] = [
                            'email' => $teacher_email_hash[ $staffer->StaffId ],
                            'name' => $staff_name
                        ];

                        if( !in_array($staffer->StaffId, $active_teachers ) ){
                            $active_teachers[] = $staffer->StaffId;
                        }
                    }
                }
            }
        }
        var_dump( 'admins done');

        $sessions_hash = [];
        $acad_sessions = $this->connection->get_response( 'acadsessions' );
        foreach($acad_sessions as $acad_session){
            if( is_object( $acad_session ) ){

                if( $acad_session->AcadYear == $this->getYear() ){
                    $sessions_hash[ $acad_session->Id ] = $acad_session;
                }
            }
        }
        var_dump( 'sessions done');
        $students = $this->entity_manager->getRepository('lfSharedDataBundle:Student')->findAll();

        $students_hash = [];
        foreach( $students as $student ){
            $students_hash[ $student->getDborId() ] = $student;
        }

        $homeroom_teachers_hash = [];
        $student_homeroom_teacher_hash = [];
        foreach( $sessions_hash as $session ){
            if( is_object( $session ) ){

                $homerooms = $this->connection->get_response( 'homerooms_by_session', [ $session->Id ] );

                foreach( $homerooms as $homeroom ){

                    if( is_object( $homeroom ) && isset( $teacher_email_hash[ $homeroom->TeacherId ] ) ){
                        $teacher = $staff_hash[ $homeroom->TeacherId ];
                        $homeroom_teachers_hash[ $homeroom->Id ] = [
                            'email' => $teacher_email_hash[ $homeroom->TeacherId ],
                            'name' => $teacher->FirstName .' '. $teacher->LastName
                        ];

                        if( !in_array($homeroom->TeacherId, $active_teachers ) ){
                            $active_teachers[] = $homeroom->TeacherId;
                        }
                    }
                }

                $student_homerooms = $this->connection->get_response( 'student_homerooms_by_session', [ $session->Id ] );

                foreach( $student_homerooms as $student_homeroom ){

                    if( is_object( $student_homeroom ) ){
                        $student_homeroom_teacher_hash[ $student_homeroom->StudentId ] = $student_homeroom->HomeroomId;
                    }
                }
            }
        }
        var_dump( 'homerooms done' );

        $sections_hash = [];
        $section_year_hash = [];
        $section_teachers = [];
        foreach( $sessions_hash as $session ){
            if( is_object( $session ) ){

                $sections = $this->connection->get_response( 'sections_by_session', [ $session->Id ] );
                foreach( $sections as $section ){
                    if( is_object( $section ) && !empty( $section->PrimaryTeacherId ) ){

                        $courseType = $this->connection->get_course_type_from_section( $section );
                        $sections_hash[ $section->Id ] = $section;
                        $section_year_hash[ $section->Id ] = $session->AcadYear;

                        $section_teachers[ $section->PrimaryTeacherId ] = $staff_hash[ $section->PrimaryTeacherId ];
                    }
                }
            }
        }
        var_dump( 'sections done' );

        $student_section_hash = [];
        $student_admin_hash = [];
        $student_counselors_hash = [];
        foreach( $sessions_hash as $session ){
            $schedule = $this->connection->get_response( 'schedules_by_session', [ $session->Id ] );
            foreach( $schedule as $scheduled_section ){
                if( is_object( $scheduled_section )
                    && isset( $sections_hash[ $scheduled_section->SectionId ] )
                    && isset( $students_hash[ $scheduled_section->StudentId ] )
                ){

                    $section = $sections_hash[ $scheduled_section->SectionId ];

                    $student = $students_hash[ $scheduled_section->StudentId ];

                    $courseType = $this->connection->get_course_type_from_section( $section );

                    if( empty( $student_section_hash[$student->getId()][ $courseType ] )
                            || $session->AcadYear > $section_year_hash[ $section->Id ]
                    ){
                        $student_section_hash[$student->getDborId()][ $courseType ] = $section;
                        $student_admin_hash[$student->getDborId()] = ( isset( $school_admin_hash[ $session->SchoolId ] ) ) ? $school_admin_hash[ $session->SchoolId ] : [];
                        $student_counselors_hash[$student->getDborId()] = ( isset($school_counselors_hash[ $session->SchoolId ] ) ) ? $school_counselors_hash[ $session->SchoolId ] : [];
                    }
                }
            }
        }
        var_dump( 'student section done' );

        foreach( $student_section_hash as $student_id => $courses ){
            foreach( $courses as $courseType => $section ){

                if( $courseType
                    && in_array($courseType, ['reading', 'english', 'math', 'science', 'social studies'] )
                ){

                    $student = $students_hash[ $student_id ];
                    $student_data = new StudentData();
                    $student_data->setStudent( $student );
                    $student_data->setMetaKey( $courseType .'_teacher_email' );
                    $student_data->setMetaValue( $teacher_email_hash[ $section->PrimaryTeacherId ] );
                    $student->addAdditionalDatum( $student_data );
                    $this->entity_manager->persist( $student_data );
                    $this->maybe_flush();

                    $teacher = $staff_hash[ $section->PrimaryTeacherId ];
                    $student_data = new StudentData();
                    $student_data->setStudent( $student );
                    $student_data->setMetaKey( $courseType .'_teacher_name' );
                    $student_data->setMetaValue( $teacher->FirstName .' '. $teacher->LastName );
                    $student->addAdditionalDatum( $student_data );
                    $this->entity_manager->persist( $student_data );
                    $this->maybe_flush();

                    if( !in_array($section->PrimaryTeacherId, $active_teachers ) ){
                        $active_teachers[] = $section->PrimaryTeacherId;
                    }


                }
            }
        }
        unset( $student_section_hash );
        var_dump( 'teachers flushed');

        foreach( $student_admin_hash as $student_id => $admin ){

            $student = $students_hash[ $student_id ];
            $student_data = new StudentData();
            $student_data->setStudent( $student );
            $student_data->setMetaKey( 'admin_email' );
            $student_data->setMetaValue( $admin['email'] );
            $student->addAdditionalDatum( $student_data );
            $this->entity_manager->persist( $student_data );
            $this->maybe_flush();

            $student = $students_hash[ $student_id ];
            $student_data = new StudentData();
            $student_data->setStudent( $student );
            $student_data->setMetaKey( 'admin_name' );
            $student_data->setMetaValue( $admin['name'] );
            $student->addAdditionalDatum( $student_data );
            $this->entity_manager->persist( $student_data );
            $this->maybe_flush();
        }
        unset( $student_admin_hash );
        var_dump( 'admins flushed');

        foreach( $student_counselors_hash as $student_id => $counselors ){

            foreach( $counselors as $counselor ){
                $student = $students_hash[ $student_id ];
                $student_data = new StudentData();
                $student_data->setStudent( $student );
                $student_data->setMetaKey( 'counselor_email' );
                $student_data->setMetaValue( $counselor['email'] );
                $student->addAdditionalDatum( $student_data );
                $this->entity_manager->persist( $student_data );
                $this->maybe_flush();

                $student = $students_hash[ $student_id ];
                $student_data = new StudentData();
                $student_data->setStudent( $student );
                $student_data->setMetaKey( 'counselor_name' );
                $student_data->setMetaValue( $counselor['name'] );
                $student->addAdditionalDatum( $student_data );
                $this->entity_manager->persist( $student_data );
                $this->maybe_flush();
            }
        }
        unset( $student_counselors_hash );
        var_dump('Counselors Flushed');

        foreach( $student_homeroom_teacher_hash as $student_id => $homeroom ){

            if( isset( $homeroom_teachers_hash[ $homeroom ] )
                && isset( $students_hash[ $student_id ] )
            ){
                $homeroom_teacher = $homeroom_teachers_hash[ $homeroom ];

                $student = $students_hash[ $student_id ];
                $student_data = new StudentData();
                $student_data->setStudent( $student );
                $student_data->setMetaKey( 'homeroom_teacher_email' );
                $student_data->setMetaValue( $homeroom_teacher['email'] );
                $student->addAdditionalDatum( $student_data );
                $this->entity_manager->persist( $student_data );
                $this->maybe_flush();

                $student = $students_hash[ $student_id ];
                $student_data = new StudentData();
                $student_data->setStudent( $student );
                $student_data->setMetaKey( 'homeroom_teacher_name' );
                $student_data->setMetaValue( $homeroom_teacher['name'] );
                $student->addAdditionalDatum( $student_data );
                $this->entity_manager->persist( $student_data );
                $this->maybe_flush();
            }
        }
        unset( $student_homeroom_teacher_hash );
        var_dump( 'homerooms flushed');

        // foreach( $active_teachers as $active_teacher ){

        //     if( isset( $staff_hash[ $active_teacher ] ) ){
        //         $staffer = $staff_hash[ $active_teacher ];

        //         $teacher = new Teacher();
        //         $teacher->setFirstName( $staffer->FirstName );
        //         $teacher->setLastName( $staffer->LastName );
        //         $teacher->setEmail( $teacher_email_hash[ $active_teacher ] );
        //         $teacher->setPassword( 'TEST' );
        //         $this->entity_manager->persist( $teacher );

        //         $this->maybe_flush();
        //     }
        // }
        // var_dump( 'teacher records flushed' );
    }
}