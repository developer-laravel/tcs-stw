<?php

namespace LeanFrog\SharedDataBundle\Command;

use LeanFrog\SharedDataBundle\Entity\Student;
use LeanFrog\SharedDataBundle\Entity\ProgramSchool;
use LeanFrog\SharedDataBundle\Entity\ProgramSchoolData;
use LeanFrog\SharedDataBundle\Connection\InowAPIConnection;
use LeanFrog\SharedDataBundle\Command\Traits\ImportTraits;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSchoolDataCommand extends ContainerAwareCommand {
    use ImportTraits;

    protected function configure() {

        $this
            ->setName( 'shared:school:import' )
            ->setDescription( 'Import School data from API' )
            ->setHelp( <<<EOF
The <info>%command.name%</info> command imports school data from the iNow database API.

<info>php %command.full_name%</info>

EOF
            );
    }

    protected function execute( InputInterface $input , OutputInterface $output ) {

        ini_set('memory_limit','5120M');

        $env = $this->getContainer()->get( 'kernel' )->getEnvironment();
        var_dump( 'Running School Import in environment: ' . $env );

        $this->entity_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->entity_manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->connection = new InowAPIConnection();
        $this->import_schools_from_students();

        $this->entity_manager->flush();
        var_dump( 'Completed School Import in envinronment: ' . $env );
     }

    public function import_schools_from_students(){

        $delete_schools = $this->entity_manager->createQueryBuilder()
            ->delete('lfSharedDataBundle:ProgramSchool', 'sg')
            ->getQuery();
        $delete_schools->execute();

        $gradelevels = $this->connection->get_response( 'gradelevels' );
        $grade_level_hash = [];
        foreach( $gradelevels as $gradelevel ){
            if( is_numeric( $gradelevel->Name ) ){
                $grade_level_hash[ $gradelevel->Id ] = intval( $gradelevel->Name );
            }
        }

        $schools = $this->connection->get_response( 'schools' );
        foreach( $schools as $school ){

            if( is_object( $school ) ){

                $parent = new ProgramSchool();
                $parent
                    ->setActive( $school->IsActive )
                    ->setName( $school->Name );
                $this->entity_manager->persist( $parent );

                $gradelevels = $this->connection->get_response( 'schools/'.$school->Id.'/gradeLevels' );

                foreach( $gradelevels as $gradelevel ){

                    if( is_object( $gradelevel ) ){
                        $child = new ProgramSchool();
                        $child
                            ->setParent( $parent )
                            ->setActive( $parent->getActive() )
                            ->setName( $parent->getName() )
                            ->setGradeLevel( $grade_level_hash[ $gradelevel->GradeLevelId ] );
                        $this->entity_manager->persist( $child );
                    }
                }
                $this->maybe_flush();
            }
        }
    }
}