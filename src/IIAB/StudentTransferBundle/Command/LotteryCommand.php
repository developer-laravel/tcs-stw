<?php

namespace IIAB\StudentTransferBundle\Command;

use IIAB\StudentTransferBundle\Lottery\Lottery;
use IIAB\StudentTransferBundle\Entity\Audit;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LotteryCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName( 'iiab:lottery:run' )
			->setDescription( 'Run Lottery' )
		;

	}

	protected function execute( InputInterface $input , OutputInterface $output ) {

		ini_set( 'memory_limit' , -1 );
		$today = new \DateTime( date('Y-m-d') );
		//$today = $today->format( 'Y-m-d 00:00:00' );
		/** @var \Doctrine\Common\Persistence\ObjectManager $em */
		$em = $this->getContainer()->get('doctrine')->getManager();

		$lotteryRan = false;

		//Looking for expired URLS first to clean up.
		$lotteryClass = new Lottery();
		$lotteryClass->setContainer( $this->getContainer() );
		$lotteryClass->checkForExpiredURLs( $em );
		unset( $lotteryClass );

		//Find all First Round Lotteries.
		$results = $em->getRepository('IIABStudentTransferBundle:Lottery')->findBy( array(
			'firstRoundDate' => $today
		) );
		//Round one starts the lottery
		$lotteryStatusRunning = $em->getRepository('IIABStudentTransferBundle:LotteryStatus')->find(2);

		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lottery */
		foreach( $results as $lottery ) {
			$lottery->setLotteryStatus( $lotteryStatusRunning );
			$em->flush();

			$lotteryClass = new Lottery();
			$lotteryClass->setContainer( $this->getContainer() );

			//running lottery round one (1)
			$this->recordAudit( 28 );
			$lotteryClass->runLottery( $em , $lottery->getEnrollmentPeriod() , 1 );
			$output->writeln( 'Running Lottery ID:' . $lottery->__toString() . ' -- Round 1');
			$lotteryRan = true;

			unset( $lotteryClass );
		}
		unset( $results, $lottery );

		//Find all Second Round Lotteries
		$results = $em->getRepository('IIABStudentTransferBundle:Lottery')->findBy( array(
			'secondRoundDate' => $today
		) );
		//Round two completes the lottery
		$lotteryStatusCompleted = $em->getRepository('IIABStudentTransferBundle:LotteryStatus')->find(3);

		foreach( $results as $lottery ) {
			$lottery->setLotteryStatus( $lotteryStatusCompleted );
			$em->flush();

			$lotteryClass = new Lottery();

			//running lottery round two (2)
			$this->recordAudit( 29 );
			$lotteryClass->runLottery( $em , $lottery->getEnrollmentPeriod() , 2 );
			$output->writeln( 'Running Lottery ID:' . $lottery->__toString() . ' -- Round 2');
			$lotteryRan = true;

			unset( $lotteryClass );
		}
		unset( $results, $lottery );

		$em->clear();
		$output->writeln( 'Lottery Completed' );

		if( $lotteryRan ) {

			$name = 'Student Support Services (SSS)';
			$emailAddressSetting = $em->getRepository( 'IIABStudentTransferBundle:Settings' )->findOneBy( array(
				'settingName' => 'lottery notification'
			) );


			$message = \Swift_Message::newInstance()
				->setSubject( 'Lottery Ran: ' . date( 'Y/m/d' ) )
				->setFrom( 'choice@tusc.k12.al.us' , 'Student Transfer Website' )
				->setReplyTo( 'choice@tusc.k12.al.us' , 'Student Transfer Website' )
				->setTo( explode( ',' , $emailAddressSetting->getSettingValue() ) )
				->setBody(
					$this->getContainer()->get('templating')->render(
						'IIABStudentTransferBundle:Email:lottery.txt.twig',
						array('name' => $name)
					)
				)
			;
			$this->getContainer()->get('mailer')->send($message);
		}
	}


	/**
	 * @param int $auditCode
	 * @param int $submission
	 *
	 * @return void
	 */
	private function recordAudit( $auditCode = 0 , $submission = 0 ) {

		$em = $this->getContainer()->get('doctrine.orm.entity_manager');

		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( '::1' );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( 0 );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( 0 );

		$em->persist( $audit );
		$em->flush();
		$em->clear();
	}
}