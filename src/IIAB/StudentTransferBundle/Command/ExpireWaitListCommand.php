<?php
/**
 * Created by PhpStorm.
 * User: michellegivens
 * Date: 12/26/14
 * Time: 2:10 PM
 */

namespace IIAB\StudentTransferBundle\Command;

use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Service\CheckAddressService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireWaitListCommand extends ContainerAwareCommand {

	protected function configure() {

		$this
			->setName( 'stw:expire:waitlist' )
			->setDescription( 'Checks an address against the AddressBounds' )
			->addArgument( 'enrollment' , InputArgument::REQUIRED , 'Enrollment to Expire' );
	}

	protected function execute( InputInterface $input , OutputInterface $output ) {

		$enrollment = $input->getArgument( 'enrollment' );

		$DM = $this->getContainer()->get('doctrine')->getManager();

		if( $enrollment == 'auto' ){
			$now = new \DateTime();

			$enrollment = $DM->getRepository('IIABStudentTransferBundle:OpenEnrollment')->findBy( [
				'waitListExpireDate' => $now
			]);
		}

		$submissions = $DM->getRepository('IIABStudentTransferBundle:Submission')->findBy( [
			'enrollmentPeriod' => $enrollment ,
			'submissionStatus' => 10
		] );
		$total = count( $submissions );

		if( $total > 0 ) {

			$deniedDueToSpace = $DM->getRepository('IIABStudentTransferBundle:SubmissionStatus')->find( 5 ); //Denied Due to Space

			foreach( $submissions as $submission ) {

				$submission->setAwardedSchoolID( null );
				$waitListed = $submission->getWaitList();
				foreach( $waitListed as $waitList ) {
					$submission->removeWaitList( $waitList );
					$DM->remove( $waitList );
				}

				$submission->setSubmissionStatus( $deniedDueToSpace );

				$auditCode = $DM->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( 34 ); //Submission status changed from wait listed to denied due to space

				$audit = new Audit();
				$audit->setAuditCodeID( $auditCode );
				$audit->setIpaddress( '::1' );
				$audit->setSubmissionID( $submission->getId() );
				$audit->setStudentID( $submission->getStudentID() );
				$audit->setTimestamp( new \DateTime() );
				$audit->setUserID( 0 );

				$DM->persist( $audit );
			}

			$DM->flush();
		}

		$output->writeln( "Expired Total Wait Listed Entry: " . $total );

	}

}