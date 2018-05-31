<?php
/**
 * Created by PhpStorm.
 * User: DerrickWales
 * Date: 5/4/15
 * Time: 12:44 PM
 */

namespace IIAB\StudentTransferBundle\Command;

use IIAB\StudentTransferBundle\Entity\Process;
use IIAB\StudentTransferBundle\Entity\LotteryLog;
use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Entity\SubmissionData;
use IIAB\StudentTransferBundle\Lottery\Lottery;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;

class ProcessCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName( 'stw:process' )
			->setDescription( 'Process any command that need to run' )
			->setHelp( <<<EOF
The <info>%command.name%</info> command runs any background processes.

<info>php %command.full_name%</info>

EOF
			);
	}

	protected function execute( InputInterface $input , OutputInterface $output ) {

		$environment = $this->getContainer()->get( 'kernel' )->getEnvironment();
		$output->writeln( 'Running processor in environment: ' . $environment );

		//Looking for expired URLS first to clean up.
		$lotteryClass = new Lottery();
		$lotteryClass->setContainer( $this->getContainer() );
		$lotteryClass->checkForExpiredURLs( $this->getContainer()->get( 'doctrine' )->getManager() );
		unset( $lotteryClass );

		$processes = $this->getContainer()->get( 'doctrine' )->getManager()->getRepository( 'IIABStudentTransferBundle:Process' )->findBy( array(
			'completed' => 0,
			'running' => 0
		) , array( 'addDateTime' => 'ASC' ) );

		foreach( $processes as $process ) {
			$event = strtolower($process->getEvent());
			$process->setRunning(true);
			$this->getContainer()->get('doctrine')->getManager()->flush();
			$output->writeln("\t Running {$event}.");
			switch ($event) {

				case 'email':
					$output->writeln('Running email event.');
					$process = $this->handleEmail($process);
					break;
				case 'pdf':
					$output->writeln('Running PDF event.');
					$process = $this->handlePDF($process);
					break;

				case 'lottery';
					$output->writeln('Running Lottery event.');
					$process = $this->handleLottery($process);
					break;
			}

			$process->setRunning(false);
			$process->setCompleted(true);
			$process->setCompletedDateTime(new \DateTime());
		}
		$this->getContainer()->get( 'doctrine' )->getManager()->flush();
		$output->writeln( 'Completed processor in environment: ' . $environment );
	}

	/**
	 * Handles the Lottery Processes.
	 * @param Process $process
	 * @return Process
	 */
	private function handleLottery( Process $process ) {

		$type = strtolower( $process->getType() );

		$lotteryClass = new Lottery();
		$lotteryClass->setContainer( $this->getContainer() );

		switch( $type ) {

			case 'round-1';
				$lotteryStatusRunning = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:LotteryStatus')->find(2);
				$lotteryEntity = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $process->getOpenEnrollment() );
				$lotteryClass->runLottery( $this->getContainer()->get('doctrine')->getManager() , $process->getOpenEnrollment() , 1 );
				$lotteryEntity->setLotteryStatus( $lotteryStatusRunning );
				break;

			case 'round-2';
				$lotteryStatusCompleted = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:LotteryStatus')->find(3);
				$lotteryEntity =$this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $process->getOpenEnrollment() );
				$lotteryClass->runLottery( $this->getContainer()->get('doctrine')->getManager() , $process->getOpenEnrollment() , 2 );
				$lotteryEntity->setLotteryStatus( $lotteryStatusCompleted );
				break;

			case 'late-lottery';
				$lotteryStatusActive = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:LotteryStatus')->find(1);
				$lotteryStatusCompleted = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:LotteryStatus')->find(4);
				$lotteryLog = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:LotteryLog')->findBy([
					'enrollmentPeriod' => $process->getOpenEnrollment(),
					'lotteryStatus' => $lotteryStatusActive
				]);

				if( $lotteryLog ) {
					$lotteryLog = $lotteryLog[0];
				} else {
					$lotteryLog =  new LotteryLog();

					$lotteryLog->setTimestamp( new \DateTime() );
					$lotteryLog->setEnrollmentPeriod( $process->getOpenEnrollment() );
					$lotteryLog->setLotteryStatus( $lotteryStatusActive );
					$this->getContainer()->get( 'doctrine' )->getManager()->persist( $lotteryLog );
				}
				$lotteryEntity = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $process->getOpenEnrollment() );
				$lotteryClass->runLottery( $this->getContainer()->get('doctrine')->getManager(), $process->getOpenEnrollment(), 3 );
				$lotteryLog->setLotteryStatus( $lotteryStatusCompleted );
				$lotteryEntity->setLotteryStatus( $lotteryStatusCompleted );
				$lotteryEntity->setLastLateLotteryProcess( new \DateTime() );
				break;

			default:
				break;
		}

		$process->setSubmissionsAffected( 0 );

		return $process;
	}

	/**
	 * Handles Emails
	 * @param Process $process
	 * @return Process
	 */
	private function handleEmail( Process $process ) {

		$type = strtolower( $process->getType() );
		$mailer = $this->getContainer()->get( 'stw.email' );

		$searchParameters = [ 'enrollmentPeriod' => $process->getOpenEnrollment() ];

		$formID = $process->getForm();
		if( $formID ){ $searchParameters[ 'formID' ] = $formID; }

		$lottery = $this->getContainer()->get( 'doctrine' )->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $process->getOpenEnrollment() );
		$lotteryStatus = ( $lottery) ? $lottery->getLotteryStatus()->getId() : 0;

		$after_lottery = ( $lotteryStatus > 2 );
		if( $after_lottery ){
			$searchParameters[ 'roundExpires' ]	= 3;
		};

		switch( $type ) {
			case 'awarded':
				$searchParameters[ 'submissionStatus' ] = 2;
				$mailerMethod = 'sendAwardedEmail';
				break;
			case 'awarded-but-wait-listed':
				$searchParameters[ 'submissionStatus' ] = 11;
				$mailerMethod = 'sendAwardedButWaitListEmail';
				break;
			case 'wait-list':
				$searchParameters[ 'submissionStatus' ] = 10;
				$mailerMethod = 'sendWaitListEmail';
				break;
			case 'denied':
				$statusFilter = $process->getSubmissionStatus();
				$searchParameters[ 'submissionStatus' ] = ( $statusFilter ) ? $statusFilter : [ 5 , 9 ];
				$mailerMethod = 'sendDeniedEmail';
				break;
		}

		if( isset( $mailerMethod ) ) {
			$sent_count = 0;
			$meta_key = ucfirst( $type ) .' Email Sent';

			$submissions = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:Submission')->findBy($searchParameters);
			foreach ($submissions as $submission) {
				$email_not_sent = true;
				foreach( $submission->getSubmissionData() as $data) {
					if( $data->getMetaKey() == $meta_key ){
						$email_not_sent = false;
						break;
					}
				}

				if( $email_not_sent ) {
					$mailer->$mailerMethod($submission);
					$sent_count ++;

					$message = new \DateTime();
					$message = $message->format( 'Y-m-d H:i:s' );

					$data = new SubmissionData();
					$data->setMetaKey( $meta_key );
					$data->setMetaValue( $message );
					$data->setSubmission( $submission );

					$this->getContainer()->get('doctrine')->getManager()->persist( $data );
				}
			}
			$submissionsAffected = $sent_count;
			$process->setSubmissionsAffected($submissionsAffected);
		}
		$this->getContainer()->get('doctrine')->getManager()->flush();
		$offeredSubmissions = null;
		$type = null;
		$mailer = null;

		return $process;
	}

	/**
	 * Handles all of the PDF functions.
	 * @param Process $process
	 * @return Process
	 */
	private function handlePDF( Process $process ) {
		$type = strtolower( $process->getType() );

		switch( $type) {
			case 'awarded':
				$fileLocation = $this->getContainer()->get( 'stw.pdf' )->awardedReport( $process );
				break;
			case 'wait-list':
				$fileLocation = $this->getContainer()->get( 'stw.pdf' )->waitListReport( $process );
				break;
			case 'denied':
				$fileLocation = $this->getContainer()->get( 'stw.pdf' )->deniedReport( $process );
				break;
			case 'awarded-but-wait-listed':
				$fileLocation = $this->getContainer()->get('stw.pdf' )->awardedButWaitListReport( $process );
				break;

			default:
				break;
		}
		return $process;
	}
}