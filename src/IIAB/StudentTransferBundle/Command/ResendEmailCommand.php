<?php
/**
 * Company: Image In A Box
 * Date: 1/13/15
 * Time: 5:45 PM
 * Copyright: 2015
 */

namespace IIAB\StudentTransferBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResendEmailCommand extends ContainerAwareCommand {

	protected function configure() {

		$this
			->setName( 'stw:send:email' )
			->setDescription( 'Resends a specific email for a submisison' )
			->addArgument( 'submission' , InputArgument::REQUIRED , 'The submission you want to send about.' )
			->addArgument( 'template' , InputArgument::REQUIRED , 'The email you want to send. Confirmation only works.' )
			->addOption( 'nextSchoolYear' , '' , InputOption::VALUE_OPTIONAL , 'Only for Denied Emails')
			->addOption( 'nextYear' , '' , InputOption::VALUE_OPTIONAL , 'Only for Denied Emails');

	}

	protected function execute( InputInterface $input , OutputInterface $output ) {

		$submission = $input->getArgument( 'submission' );
		$templateName = $input->getArgument( 'template' );

		if( empty( $templateName ) || empty( $submission ) ) {
			$output->writeln( 'Missing Required fields. Requires Submission ID and template name' );
			return;
		}

		/** @var \IIAB\StudentTransferBundle\Entity\Submission $submissionObject */
		$submissionObject = $this->getContainer()->get('doctrine')->getRepository('IIABStudentTransferBundle:Submission')->find( $submission );

		if( $submissionObject != null ) {
			$email = $submissionObject->getEmail();
			if( !empty( $email ) )  {

				if( $templateName == 'confirmation' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendConfirmationEmail( $submissionObject );

					$output->writeln( 'Sent Response: ' . $response );

				}

				if( $templateName == 'awarded' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendAwardedEmail( $submissionObject );

					$output->writeln( 'Sent Response: ' . $response );

				}

				if( $templateName == 'awardedbutwaitlisted' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendAwardedButWaitListEmail( $submissionObject );

					$output->writeln( 'Sent Response: ' . $response );

				}

				if( $templateName == 'accepted' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendAcceptedEmail( $submissionObject );

					$output->writeln( 'Sent Response: ' . $response );
				}

				if( $templateName == 'declined' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendDeclinedEmail( $submissionObject );

					$output->writeln( 'Sent Response: ' . $response );
				}

				if( $templateName == 'waitlist' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendWaitListEmail( $submissionObject );
					$output->writeln( 'Sent Response: ' . $response );

				}

				if( $templateName == 'declined-waitlist' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendDeclinedWaitListEmail( $submissionObject );
					$output->writeln( 'Sent Response: ' . $response );

				}

				if( $templateName == 'auto-declined' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendAutoDeclinedEmail( $submissionObject );
					$output->writeln( 'Sent Response: ' . $response );

				}

				if( $templateName == 'denied' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendDeniedEmail( $submissionObject );
					$output->writeln( 'Sent Response: ' . $response );

				}

				if( $templateName == 'next-step' ) {

					$output->writeln( 'Sending ' . $templateName . ' for submission ID: ' . $submissionObject );

					$response = $this->getContainer()->get( 'stw.email' )->sendNextStepEmail( $submissionObject );
					$output->writeln( 'Sent Response: ' . $response );

				}
			} else {
				$output->writeln( 'Submissing does not have an email address on file. Please fix' );
			}
		} else {
			$output->writeln( 'Submission: ' . $submission . ' not found.' );
		}


	}


}