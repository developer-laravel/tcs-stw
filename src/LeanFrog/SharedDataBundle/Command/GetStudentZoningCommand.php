<?php

namespace LeanFrog\SharedDataBundle\Command;

use LeanFrog\SharedDataBundle\Entity\Student;
use LeanFrog\SharedDataBundle\Connection\ZoningAPIConnection;
use LeanFrog\SharedDataBundle\Command\Traits\ImportTraits;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetStudentZoningCommand extends ContainerAwareCommand {
    use ImportTraits;

    private $entity_manager;

    protected function configure() {

        $this
            ->setName( 'shared:student:zoning' )
            ->setDescription( 'Gets the zoned school for Students from API' )
            ->setHelp( <<<EOF
The <info>%command.name%</info> command gets the zoned school for students from zoning API.

<info>php %command.full_name%</info>

EOF
            );
    }

    protected function execute( InputInterface $input , OutputInterface $output ) {

        ini_set('memory_limit','2048M');

        $env = $this->getContainer()->get( 'kernel' )->getEnvironment();
        var_dump( 'Running Student Zoning in environment: ' . $env );

        $this->entity_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->entity_manager->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->connection = new ZoningAPIConnection();

        $this->get_zoning_for_students();

        $this->entity_manager->flush();
        var_dump( 'Completed Student Zoning in envinronment: ' . $env );
    }

public function get_zoning_for_students(){

        $students = $this->entity_manager->getRepository('lfSharedDataBundle:Student')
            ->findAll();

        $address_checked_hash = [];
        $api_calls = 0;
        foreach( $students as $student ){

            if( $student->getZonedHigh() == null ){
                echo "\n\r". $student->getId();
                $address = strtoupper( trim( $student->getAddress() ) );

                //remove apartment numbers
                $address = explode( ' APT ', $address)[0];
                $address = explode( ' UNIT ', $address)[0];
                $address = explode( ' LOT ', $address)[0];

                if( isset( $address_checked_hash[ $address ] ) ){
                    $address_bound = $address_checked_hash[ $address ];
                } else {
                    $api_calls ++;
                    $address_bound = $this->connection->getZonedSchools( $address , $student->getZip() );
                    $address_checked_hash[ $address ] = $address_bound;
                }

                if( $address_bound ){
                    $student
                        ->setZonedElementary( $address_bound->getESBND() )
                        ->setZonedMiddle( $address_bound->getMSBND() )
                        ->setZonedHigh( $address_bound->getHSBND() );
                    $this->entity_manager->persist( $student );
                    $this->entity_manager->flush();
                    //$this->maybe_flush();
                }

            }
        }
        var_dump( '# of Calls '. $api_calls );
    }
}