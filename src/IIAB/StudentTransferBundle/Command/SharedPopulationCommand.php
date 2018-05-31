<?php

namespace IIAB\StudentTransferBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use LeanFrog\SharedDataBundle\Entity\Population;
use LeanFrog\SharedDataBundle\Service\SharedPopulationService;
use IIAB\StudentTransferBundle\Entity\CurrentEnrollmentSettings;

class SharedPopulationCommand extends ContainerAwareCommand {

    protected function configure() {

        $this
            ->setName( 'stw:shared:population:sync' )
            ->setDescription( 'Updates the Shared Populatin Table' )
            ->setHelp( <<<EOF
The <info>%command.name%</info>

EOF
            );
    }

    private $shared_manager = null;
    private $transfer_manager = null;

    protected function execute( InputInterface $input , OutputInterface $output ) {

        $em = $this->getContainer()->get( 'doctrine' );

        $this->shared_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->transfer_manager = $this->getContainer()->get( 'doctrine' )->getManager();

        $last_shared_update = $this->shared_manager
            ->getRepository('lfSharedDataBundle:Population')
            ->findOneBy(
                [],
                ['id' => 'DESC']
            );

        //$this->updateSharedFromTransfer( $last_shared_update );

        $last_population = $this->transfer_manager
                    ->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')
                    ->findAll();

        $last_transfer_update = $this->transfer_manager
            ->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')
            ->findOneBy(
                [],
                ['id' => 'DESC']
            );

        var_dump('START');
        $this->updateTransferFromShared( $last_transfer_update );
    }

    private function getSharedSchoolFromTransfer( $school ){
        $shared_school_data = $this->shared_manager
            ->getRepository( 'lfSharedDataBundle:ProgramSchoolData' )
            ->findOneBy([
                'metaKey' => 'stw_adm_school',
                'metaValue' => $school->getId()
            ]);

        if( empty($shared_school_data ) ){

            $shared_school = new \LeanFrog\SharedDataBundle\Entity\ProgramSchool();
            $shared_school
                ->setName( $school->getSchoolName() )
                ->setGradeLevel( intval( $school->getGrade() ) );

            $shared_school_data = new \LeanFrog\SharedDataBundle\Entity\ProgramSchoolData();
            $shared_school_data
                ->setProgramSchool( $shared_school )
                ->setMetaKey('stw_adm_school')
                ->setMetaValue( $school->getId() );
            $shared_school->addAdditionalDatum( $shared_school_data );

            $this->shared_manager->persist( $shared_school );
            $this->shared_manager->persist( $shared_school_data );
            $this->shared_manager->flush();
        }

        $school = $shared_school_data->getProgramSchool();

        return $school;
    }

    private function getSharedSchoolFromCurrent( $school_name, $grade_level ){

        $school = $this->shared_manager
            ->getRepository( 'lfSharedDataBundle:ProgramSchool' )
            ->findOneBy([
                'name' => $school_name,
                'gradeLevel' => $grade_level
            ]);

        if( empty($school ) ){

            return false;
        }

        return $school;
    }

    private function getTransferSchoolFromShared( $school ){

        $transfer = $this->transfer_manager
            ->getRepository( 'IIABStudentTransferBundle:ADM' )
            ->findOneBy([
                'schoolName' => $school->getName(),
                'grade' => sprintf('%02d', $school->getGradeLevel() )
            ], [
                'id' => 'DESC'
            ]);

        if( $transfer == null ){
            return false;
        }
        return $transfer;
    }

    private function getSharedAcademicYearFromOpenEnrollment( $openEnrollment ){

        if( !$year_key ){
            $year_key = intval( $openEnrollment->getEndignDate()->format('Y') );
            $year_key = $year_key .'-'. ($year_key+1);
        }

        $academicYear = $this->shared_manager
            ->getRepository( 'lfSharedDataBundle:AcademicYear')
            ->findBy([
                'name' => $year_key
            ]);

        if( $academicYear == null ){
            $endDate = clone $openEnrollment->getBeginningDate();
            $endDate->modify( '+364 days' );
            $academicYear = new AcademicYear();
            $academicYear
                ->setName( $year_key )
                ->setStartDate( $openEnrollment->getBeginningDate() )
                ->setEndDate( $endDate );
            $this->shared_manager->persist( $academicYear );
            $this->shared_manager->flush();
        }

        return ( is_array( $academicYear ) ) ? $academicYear[0] : $academicYear;
    }

    private function updateSharedFromTransfer( $last_shared_update ){
        $this->updateSharedFromTransferOffers( $last_shared_update );
        $this->updateSharedFromTransferDeclines( $last_shared_update );
    }

    private function updateSharedFromTransferOffers( $last_shared_update ){

        $transfer_shared_school_hash = [];

        $new_offers = $this->transfer_manager
            ->getRepository( 'IIABStudentTransferBundle:Audit' )
            ->createQueryBuilder( 'a' )
            ->where( 'a.auditCodeID = 2');

        if( $last_shared_update != null ){
            $new_offers
                ->andWhere( "a.timestamp > :lastUpdate")
                ->setParameter( 'lastUpdate', $last_shared_update->getUpdateDateTime() );
            }

        $new_offers = $new_offers
            ->orderBy( 'a.id', 'ASC' )
            ->getQuery()
            ->getResult();

        $update_hash = [];
        $submission_ids = [];

        foreach( $new_offers as $offer ){
            $submission_ids[] = $offer->getSubmissionID();

            $date_key = $offer->getTimeStamp()->format( 'Y-m-d H:i:s' );
            $update_hash[ $offer->getSubmissionID() ] = $date_key;
        }

        $submissions = $this->transfer_manager
            ->getRepository( 'IIABStudentTransferBundle:Submission' )
            ->findBy([
                'id' => $submission_ids,
            ]);

        $school_hash = [];
        $offer_counts = [];
        foreach( $submissions as $submission ){
            $race = $this->getRaceValue( $submission );
            $school = $this->getSharedSchoolFromTransfer( $submission->getAwardedSchoolID() );

            if( empty( $offer_counts[ $school->getId() ][ $race ] ) ){
                $offer_counts
                    [ $school->getId() ]
                    [ $update_hash[$submission->getId()] ]
                    [ $race ] = [
                        'count' => 0,
                        'openEnrollment' => $submission->getEnrollmentPeriod()
                    ];
            }

            $offer_counts
                [ $school->getId() ]
                [ $update_hash[ $submission->getId() ] ]
                [ $race ]['count'] += 1;
            $school_hash[ $school->getId() ] = $school;
        }

        $population_service = new SharedPopulationService( $this->getContainer()->get( 'doctrine' ) );

        foreach( $offer_counts as $school_id => $dates ){
            foreach( $dates as $date => $counts ){
                foreach( $counts as $race => $count ){

                    $update_time = new \DateTime( $date );

                    $race_name = $population_service->getRaceName( $race );

                    $new_population = new Population();
                    $new_population
                        ->setProgramSchool( $school )
                        ->setUpdateType( 'stw offer' )
                        ->setUpdateDateTime( $update_time )
                        ->setTrackingColumn( 'Race' )
                        ->setTrackingValue( $race )
                        ->setCount( $count('count') )
                        ->setAcademicYear( $this->getSharedAcademicYearFromOpenEnrollment( $count['openEnrollment'] ) );

                    $population_hash[ $school->getId() ]['Race'][$race_name]->setCount( $new_population );

                    $this->shared_manager->persist( $new_population );
                }
            }
        }
    }

    private function updateSharedFromTransferDeclines( $last_shared_update ){

        $transfer_shared_school_hash = [];

        $new_declines = $this->transfer_manager
            ->getRepository( 'IIABStudentTransferBundle:Audit' )
            ->createQueryBuilder( 'a' )
            ->where( 'a.auditCodeID = 4');

        if( $last_shared_update != null ){
            $new_declines
                ->andWhere( "a.timestamp > :lastUpdate")
                ->setParameter( 'lastUpdate', $last_shared_update->getUpdateDateTime() );
            }

        $new_declines = $new_declines
            ->orderBy( 'a.id', 'ASC' )
            ->getQuery()
            ->getResult();

        $update_hash = [];
        $submission_ids = [];

        foreach( $new_declines as $decline ){
            $submission_ids[] = $decline->getSubmissionID();

            $date_key = $decline->getTimeStamp()->format( 'Y-m-d H:i:s' );
            $update_hash[ $decline->getSubmissionID() ] = $date_key;
        }

        $submissions = $this->transfer_manager
            ->getRepository( 'IIABStudentTransferBundle:Submission' )
            ->findBy([
                'id' => $submission_ids,
            ]);

        $school_hash = [];
        $decline_counts = [];
        foreach( $submissions as $submission ){
            $race = $this->getRaceValue( $submission );
            $school = $this->getSharedSchoolFromTransfer( $submission->getAwardedSchoolID() );

            if( empty( $decline_counts[ $school->getId() ][ $race ] ) ){
                $decline_counts
                    [ $school->getId() ]
                    [ $update_hash[$submission->getId()] ]
                    [ $race ] = [
                        'count' => 0,
                        'openEnrollment' => $submission->getEnrollmentPeriod()
                    ];
            }

            $decline_counts
                [ $school->getId() ]
                [ $update_hash[ $submission->getId() ] ]
                [ $race ]['count'] += 1;
            $school_hash[ $school->getId() ] = $school;
        }

        foreach( $decline_counts as $school_id => $dates ){
            foreach( $dates as $date => $counts ){
                foreach( $counts as $race => $count ){

                    $update_time = new \DateTime( $date );

                    $race_name = $population_service->getRaceName( $race );

                    $new_population = new Population();
                    $new_population
                        ->setProgramSchool( $school )
                        ->setUpdateType( 'stw decline' )
                        ->setUpdateDateTime( $update_time )
                        ->setTrackingColumn( 'Race' )
                        ->setTrackingValue( $race )
                        ->setCount( $count['count'] )
                        ->setAcademicYear( $this->getSharedAcademicYearFromOpenEnrollment( $count['openEnrollment'] ) );

                    $this->shared_manager->persist( $new_population );
                }
            }
        }

    }

    private function updateTransferFromShared( $last_transfer_update ){

        $populations_to_apply = $this->shared_manager
            ->getRepository( 'lfSharedDataBundle:Population' )
            ->createQueryBuilder( 'p' )
            ->where( 'p.updateDateTime > :last_update' )
            ->andWhere( "p.trackingColumn = 'Race'")
            ->setParameter( 'last_update' , $last_transfer_update->getAddedDateTime() )
            ->andWhere( "p.updateType IN ('mpw offer', 'mpw decline')" )
            ->orderBy( 'p.id', 'ASC' )
            ->getQuery()
            ->getResult();


        $transfer_shared_school_hash = [];
        foreach( $populations_to_apply as $population ){
            if( empty( $transfer_shared_school_hash[ $population->getProgramSchool()->getId() ] ) ){

                $transfer_shared_school_hash[ $population->getProgramSchool()->getId() ] =
                    $this->getTransferSchoolFromShared( $population->getProgramSchool() );
            }

            $transfer_school = $transfer_shared_school_hash[ $population->getProgramSchool()->getId() ];

            if( $transfer_school ){

                $last_population = $this->transfer_manager
                    ->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')
                    ->findOneBy([
                        'groupId' => $transfer_school->getGroupID()
                    ],[
                        'addedDateTime' => 'DESC'
                    ]);

                $new_population = new CurrentEnrollmentSettings();
                $new_population
                    ->setGroupId( $transfer_school->getGroupID() );
                $new_population
                    ->setAddedDateTime( $population->getUpdateDateTime() );
                $new_population
                    ->setMaxCapacity( $last_population->getMaxCapacity() );
                $new_population
                    ->setBlack( $last_population->getBlack() );
                $new_population
                    ->setWhite( $last_population->getWhite() );
                $new_population
                    ->setOther( $last_population->getOther() );
                $new_population
                    ->setEnrollmentPeriod( $transfer_school->getEnrollmentPeriod() );

                switch( $population->getTrackingValue() ){
                    case 'black':
                    case 3:
                        $new_population->setBlack( $last_population->getBlack() + $population->getCount() );
                        break;
                    case 'white':
                    case 7:
                        $new_population->setWhite( $last_population->getWhite() + $population->getCount() );
                        break;

                    case 'other':
                    case 'none':
                    default:
                        $new_population->setOther( $last_population->getOther() + $population->getCount() );
                        break;

                }

                $this->transfer_manager->persist( $new_population );
            }
        }
        $this->transfer_manager->flush();
    }

    public function getRaceValue( $submission ){
        $race = $submission->getRace();

        if( $race->getReportAsOther() ){
            $race = 'other';
        } else if( $race->getReportAsNoAnswer() ){
            $race = 'none';
        } else {
            $race = $race->getId();
        }
        return $race;
    }
}