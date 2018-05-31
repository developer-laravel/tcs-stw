<?php

namespace LeanFrog\SharedDataBundle\Command;

use LeanFrog\SharedDataBundle\Entity\StudentGrade;
use LeanFrog\SharedDataBundle\Entity\StudentData;
use LeanFrog\SharedDataBundle\Connection\InowAPIConnection;
use LeanFrog\SharedDataBundle\Command\Traits\ImportTraits;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStudentConductCommand extends ContainerAwareCommand {
    use ImportTraits;

    private $year;

    protected function configure() {

        $this
            ->setName( 'shared:student:conduct:import' )
            ->setDescription( 'Import Student Conduct from API' )
            ->setHelp( <<<EOF
The <info>%command.name%</info> command imports student conduct from the iNow database API.

<info>php %command.full_name%</info>

EOF
            );
    }

    protected function execute( InputInterface $input , OutputInterface $output ) {

        ini_set('memory_limit','5120M');

        $env = $this->getContainer()->get( 'kernel' )->getEnvironment();
        var_dump( 'Running Student Conduct Import at: ' . date(' H:i ') );

        $this->entity_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->entity_manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->connection = new InowAPIConnection();
        $this->import_conduct_from_inow();

        $this->entity_manager->flush();
        var_dump( 'Completed Student Conduct Import at: ' . date(' H:i ') );
    }

    public function import_conduct_from_inow(){

        $delete_odrs = $this->entity_manager->createQueryBuilder()
            ->delete('lfSharedDataBundle:StudentData', 'sd')
            ->where("sd.metaKey like 'odr_%'")
            ->getQuery();
        $delete_odrs->execute();

        $students = $this->entity_manager->getRepository('lfSharedDataBundle:Student')->findAll();
        $students_hash = [];
        foreach( $students as $student ){
            $students_hash[ $student->getDborId() ] = $student;
        }
        unset( $students );
        var_dump( 'student hash done' );

        //Incidents (not student relationship)
        $incidents = $this->connection->get_response( 'incidents' );
        $incident_hash = [];
        foreach( $incidents as $incident ){
            $incident_hash[ $incident->Id ] = $incident;
        }
        unset( $incidents );
        var_dump( 'incident hash done' );

        // what they did
        $infractions = $this->connection->get_response( 'infractions' );
        $infraction_hash = [];
        foreach( $infractions as $infraction ){
            $infraction_hash[ $infraction->Id ] = $infraction;
        }
        unset( $infractions );
        var_dump( 'infraction hash done' );

        //What was done
        $disciplinary_actions = $this->connection->get_response( 'disciplinaryactions' );
        $disciplinary_actions_hash = [];
        foreach( $disciplinary_actions as $disciplinary_action ){
            $disciplinary_actions_hash[ $disciplinary_action->Id ] = $disciplinary_action;
        }
        unset( $disciplinary_actions );
        var_dump( 'disciplinary actions hash done');

        //**** Hold onto this in case they want to add staff info to odr import
        // $staff_hash = [];
        // $staff = $this->connection->get_response( 'staff' );
        // foreach( $staff as $staff_member ){
        //     $staff_hash[ $staff_member->Id ] = [
        //         'staffer' => $staff_member,
        //         'position' => '',
        //         'schools' => []
        //     ];
        // }

        // $school_staff_hash = [];
        // $schools = $this->connection->get_response( 'schools' );
        // foreach( $schools as $school ){
        //     $classifications = $this->connection->get_response( 'staff_positions_by_school', [$school->Id] );

        //     foreach( $classifications as $staffer ){

        //         $staff_hash[$staffer->StaffId]['position'] =
        //             $this->connection->get_staff_position_from_id( $staffer->StaffClassificationId );
        //         $staff_hash[$staffer->StaffId]['schools'][$school->Id] =
        //             $this->connection->get_staff_position_from_id( $staffer->StaffClassificationId );
        //     }
        // }
        // unset( $staff );
        // unset( $schools );
        // var_dump( 'staff hash done');
        //**** Hold onto this in case they want to add staff info to odr import

        $odrs = [];

        $academic_sessions = $this->connection->get_response( 'acadsessions' );
        //Description of Event
        foreach( $academic_sessions as $academic_session ){
            if( is_object( $academic_session ) ){
                $occurances = $this->connection->get_response( $academic_session->Id .'/students/disciplinaryOccurrences' );

                if( is_array( $occurances ) ){
                    foreach( $occurances as $occurance ){

                        if ( isset( $students_hash[ $occurance->StudentId ] )
                        ){
                            $description = str_replace('T', ' ', $occurance->DateTime)."\n\r";
                            if( isset( $occurance->IncidentId )
                                && isset( $incident_hash[ $occurance->IncidentId ] )
                            ){
                                $description .= 'Incident: '. $incident_hash[ $occurance->IncidentId ]->Description ."\n\r";
                            }

                            foreach( $occurance->Infractions as $infraction ){
                                if( isset( $infraction_hash[ $infraction->InfractionId ] ) ) {
                                    $description .= 'Infraction: '. $infraction_hash[ $infraction->InfractionId ]->Name ."\n\r"
                                        .$infraction->Description."\n\r";
                                } else {
                                    $description .= 'Infraction: '. $infraction->Description ."\n\r";
                                }
                            }

                            foreach( $occurance->DisciplinaryActions as $action ){

                                if( isset( $disciplinary_actions_hash[ $action->DisciplinaryActionId ] ) ) {
                                    $description .= 'Action: '. $disciplinary_actions_hash[ $action->DisciplinaryActionId ]->Name ."\n\r". $action->Note ."\n\r";
                                } else {
                                    $description .= 'Action: '. $action->Note ."\n\r";
                                }
                            }
                            if( empty( $odrs[$occurance->StudentId] ) ){
                                $odrs[$occurance->StudentId] = [];
                            }

                            if( $description ){
                                $odrs[$occurance->StudentId][] = [
                                    'date' => $occurance->DateTime,
                                    'description' => $description
                                ];
                            }

                            // Maybe add staff info
                            //if( isset( $staff_hash[ $occurance->AdministratorId ] ) ){

                            // $position = '';
                            // if( isset( $staff_hash[ $occurance->AdministratorId ]['schools'][ $academic_session->SchoolId ] ) ){
                            //     $position = $staff_hash[ $occurance->AdministratorId ]['schools'][ $academic_session->SchoolId ];
                            // } else {
                            //     $position = $staff_hash[ $occurance->AdministratorId ]['position'];
                            // }
                        }

                    }
                }
            }
        }
        unset( $academic_sessions );
        unset( $occurances );

        foreach( $odrs as $student_id => $student_odrs ){

            if( count( $student_odrs ) > 1 ){
                usort( $student_odrs, function($a,$b){
                    if ($a['date'] == $b['date']) {
                        return 0;
                    }
                    return ($a['date'] > $b['date']) ? -1 : 1;
                });

                $student_odrs = array_slice($student_odrs, 0, 3);
            }

            $student = $students_hash[ $student_id ];

            foreach( $student_odrs as $index => $odr ){
                $odr_count = $index + 1;
                $student_data = new StudentData();
                $student_data->setStudent( $student );
                $student_data->setMetaKey( 'odr_'. $odr_count );
                $student_data->setMetaValue( $odr['description'] );
                $student->addAdditionalDatum( $student_data );
                $this->entity_manager->persist( $student );
                $this->entity_manager->persist( $student_data );

                $this->maybe_flush();
            }
        }
    }
}