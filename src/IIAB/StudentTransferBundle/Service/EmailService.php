<?php
/**
 * Created by PhpStorm.
 * User: michellegivens
 * Date: 2/8/15
 * Time: 6:26 PM
 */

namespace IIAB\StudentTransferBundle\Service;

use IIAB\StudentTransferBundle\Entity\Process;
use IIAB\StudentTransferBundle\Entity\Submission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService {

	/**
	 * @var ContainerInterface
	 */
	private $container;

	function __construct( ContainerInterface $container ) {

		$this->container = $container;
	}

	/**
	 * Sends the confirmation email to the parent's email address.
	 *
	 * @param Submission $submission
	 *
	 * @return bool
	 */
	public function sendConfirmationEmail( Submission $submission ) {
		$email = $submission->getEmail();
		if( isset( $email ) && !empty( $email ) ) {
			$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
					'active' => 1,
					'name' => 'confirmation',
					'type' => 'email'
				) );
		
			switch( $submission->getFormID()->getId() ){

				case 2:
					$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
						'active' => 1,
						'name' => 'transferpersonnel',
						'type' => 'email'
					) );
			
			
					$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) :$this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Confirmation:transferPersonnel.email.twig' );
					break;

				case 5:
					$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
						'active' => 1,
						'name' => 'transfersenior',
						'type' => 'email'
					) );
			
			
					$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) :$this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Confirmation:transferSenior.email.twig' );
					break;

				case 8:
					$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
						'active' => 1,
						'name' => 'transferschoolchoice',
						'type' => 'email'
					) );
			
			
					$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) :$this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Confirmation:transferSchoolChoice.email.twig' );
					break;

				case 9:
					$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
						'active' => 1,
						'name' => 'transfersuccessprep',
						'type' => 'email'
					) );
			
			
					$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) :$this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Confirmation:transferSuccessPrep.email.twig' );
					break;

				case 6:
					switch( $submission->getSubmissionStatus()->getId() ){
						case 12:
							$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
								'active' => 1,
								'name' => 'transferaccountability',
								'type' => 'email'
							) );
			
							$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) :$this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Confirmation:transferAccountabilityActOption4.email.twig' );
							break;
						default:
							$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) :$this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:confirmation.email.twig' );
							break;
					}
					break;
				default:
					$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) :$this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:confirmation.email.twig' );
							break;
			}

			$context = array(
				'email' => $submission->getEmail() ,
				'enrollment' => $submission->getEnrollmentPeriod(),
				'confirmation' => $submission->getConfirmationNumber()
			);
			$fromEmail = $this->container->get('kernel')->getContainer()->getParameter('swiftmailer.sender_address');
			$subject = $template->renderBlock( 'subject' , $context );
			$textBody = $template->renderBlock( 'body_text' , $context );
			$htmlBody = $template->renderBlock( 'body_html' , $context );

			try {
				$message = \Swift_Message::newInstance()
					->setSubject( $subject )
					->setFrom( $fromEmail , 'Tuscaloosa City Schools' )
					->setBcc( 'mypickconfirm@gmail.com' )
					->setTo( $submission->getEmail() );

				( !empty( $htmlBody ) ) ? $message->setBody( $htmlBody , 'text/html' )->addPart( $textBody , 'text/plain' ) : $message->setBody( $textBody );
				$this->container->get( 'mailer' )->send( $message );
			} catch ( \Exception $e ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Sends the awarded email.
	 *
	 * @param Submission $submission
	 *
	 * @return integer
	 */
	public function sendAwardedEmail( Submission $submission ) {

		$email = $submission->getEmail();
		if( !empty( $email ) ) {

			if( $submission->getFormID()->getId() == 1 ) {

				$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
					'active' => 1,
					'name' => 'awarded',
					'type' => 'email'
				) );
				//If no correspondence found load IIABStudentTransferBundle:Email:awarded.email.twig
				$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:awarded.email.twig' );
			} else {
				$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
					'active' => 1,
					'name' => 'awardedNonM2M',
					'type' => 'email'
				) );
				//If no correspondence found load IIABStudentTransferBundle:Email:awarded.email.twig
				$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:awardedNonM2M.email.twig' );
			}

			$enrollment = $submission->getEnrollmentPeriod();
			$lottery = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneBy( array( 'enrollmentPeriod' => $enrollment ) );

			$mailDate = new \DateTime;

			if( $submission->getRoundExpires() == 3 ) {
				$mailDate = $submission->getManualAwardDate();
				$onlineEndTime = new \DateTime( '23:59:59 +10 days ' . $submission->getManualAwardDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( $lottery->getOfflineCloseTime()->format( 'H:i' ) . ' +10 days ' . $submission->getManualAwardDate()->format( 'Y-m-d' ) );
			} else if( $lottery->getLotteryStatus()->getId() == '2' ) {
				$mailDate = $lottery->getMailFirstRoundDate();
				$onlineEndTime = new \DateTime( '23:59:59 +10 days ' . $lottery->getMailFirstRoundDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( $lottery->getOfflineCloseTime()->format( 'H:i' ) . ' +10 days ' . $lottery->getMailFirstRoundDate()->format( 'Y-m-d' ) );
			} else {
				$mailDate = $lottery->getMailSecondRoundDate();
				$onlineEndTime = new \DateTime( '23:59:59 +10 days ' . $lottery->getMailSecondRoundDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( $lottery->getOfflineCloseTime()->format( 'H:i' ) . ' +10 days ' . $lottery->getMailSecondRoundDate()->format( 'Y-m-d' ) );
			}

			$context = array(
				'submission' => $submission ,
				'enrollment' => $submission->getEnrollmentPeriod() ,
				'awardedSchool' => $submission->getAwardedSchoolID() ,
				'acceptOnlineDate' => $onlineEndTime->format( 'm/d/Y' ) ,
				'acceptOfflineDate' => $offlineEndTime->format( 'm/d/Y' ) ,
				'acceptOnlineTime' => $onlineEndTime->format( 'g:i a' ) ,
				'acceptOfflineTime' => $offlineEndTime->format( 'g:i a' ) ,
				'acceptanceURL' => $this->container->get( 'router' )->generate( 'stw_lottery_accept' , array( 'uniqueID' => $submission->getUrl() ) , UrlGeneratorInterface::ABSOLUTE_URL )
			);

			$fromEmail = $this->container->get( 'kernel' )->getContainer()->getParameter( 'swiftmailer.sender_address' );
			$subject = $template->renderBlock( 'subject' , $context );
			$textBody = $template->renderBlock( 'body_text' , $context );
			$htmlBody = $template->renderBlock( 'body_html' , $context );

			try {
				$message = \Swift_Message::newInstance()
					->setSubject( $subject )
					->setFrom( $fromEmail , 'Tuscaloosa City Schools' )
					->setBcc( 'mypickconfirm@gmail.com' )
					->setTo( $submission->getEmail() );

				if( !empty( $htmlBody ) ) {
					$message->setBody( $htmlBody , 'text/html' )
						->addPart( $textBody , 'text/plain' );
				} else {
					$message->setBody( $textBody );
				}

				//Enabled
				return $this->container->get( 'mailer' )->send( $message );
			} catch ( \Exception $e ) {
				return false;
			}
		}
	}

	/**
	 * Send Accepted Email
	 *
	 * @param Submission $submission
	 *
	 * @return int
	 */
	public function sendAcceptedEmail( Submission $submission ) {

		$email = $submission->getEmail();
		if( !empty( $email ) ) {
			$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
					'active' => 1,
					'name' => 'accepted',
					'type' => 'email'
				) );
				//If no correspondence found load IIABStudentTransferBundle:Email:accepted.email.twig
			$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:accepted.email.twig' );

			$lottery = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneBy( array( 'enrollmentPeriod' => $submission->getEnrollmentPeriod() ) );

			$context = array(
				'submission' => $submission ,
				'enrollment' => $submission->getEnrollmentPeriod() ,
				'awardedSchool' => $submission->getAwardedSchoolID() ,
				'registrationNew' => ($lottery != null ) ?  $lottery->getRegistrationNewDate() : null,
				'registrationCurrent' => ($lottery != null) ? $lottery->getRegistrationCurrentDate() : null ,
			);

			$fromEmail = $this->container->get( 'kernel' )->getContainer()->getParameter( 'swiftmailer.sender_address' );
			$subject = $template->renderBlock( 'subject' , $context );
			$textBody = $template->renderBlock( 'body_text' , $context );
			$htmlBody = $template->renderBlock( 'body_html' , $context );

			try {
				$message = \Swift_Message::newInstance()
					->setSubject( $subject )
					->setFrom( $fromEmail , 'Tuscaloosa City Schools' )
					->setBcc( 'mypickconfirm@gmail.com' )
					->setTo( $submission->getEmail() );

				if( !empty( $htmlBody ) ) {
					$message->setBody( $htmlBody , 'text/html' )
						->addPart( $textBody , 'text/plain' );
				} else {
					$message->setBody( $textBody );
				}

				return $this->container->get( 'mailer' )->send( $message );
			} catch ( \Exception $e ) {
				return false;
			}
		}
	}

	/**
	 * Send Declined Email
	 *
	 * @param Submission $submission
	 *
	 * @return int
	 */
	public function sendDeclinedEmail( Submission $submission ) {

		$email = $submission->getEmail();
		if( !empty( $email ) ) {

			$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
				'active' => 1,
				'name' => 'declined',
				'type' => 'email'
			) );
			//If no correspondence found load IIABStudentTransferBundle:Email:awarded.email.twig
			$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:declined.email.twig' );

			$lottery = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneBy( array( 'enrollmentPeriod' => $submission->getEnrollmentPeriod() ) );

			$context = array(
				'submission' => $submission ,
				'enrollment' => $submission->getEnrollmentPeriod() ,
				'awardedSchool' => $submission->getAwardedSchoolID() ,
				'registrationNew' => $lottery->getRegistrationNewDate() ,
				'registrationCurrent' => $lottery->getRegistrationCurrentDate() ,
			);

			$fromEmail = $this->container->get( 'kernel' )->getContainer()->getParameter( 'swiftmailer.sender_address' );
			$subject = $template->renderBlock( 'subject' , $context );
			$textBody = $template->renderBlock( 'body_text' , $context );
			$htmlBody = $template->renderBlock( 'body_html' , $context );

			try {
				$message = \Swift_Message::newInstance()
					->setSubject( $subject )
					->setFrom( $fromEmail , 'Tuscaloosa City Schools' )
					->setBcc( 'mypickconfirm@gmail.com' )
					->setTo( $submission->getEmail() );

				if( !empty( $htmlBody ) ) {
					$message->setBody( $htmlBody , 'text/html' )
						->addPart( $textBody , 'text/plain' );
				} else {
					$message->setBody( $textBody );
				}

				return $this->container->get( 'mailer' )->send( $message );
			} catch ( \Exception $e ) {
				return false;
			}
		}
	}

	/**
	 * Sends the auto declined email.
	 *
	 * @param Submission $submission
	 *
	 * @return bool
	 */
	public function sendAutoDeclinedEmail( Submission $submission ) {
		$email = $submission->getEmail();
		if( isset( $email ) && !empty( $email ) ) {

			$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
				'active' => 1,
				'name' => 'declined',
				'type' => 'email'
			) );
			//If no correspondence found load IIABStudentTransferBundle:Email:awarded.email.twig
			$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:declined.email.twig' );

			$context = array(
				'email' => $submission->getEmail() ,
				'submission' => $submission
			);

			$fromEmail = $this->container->get('kernel')->getContainer()->getParameter('swiftmailer.sender_address');
			$subject = $template->renderBlock( 'subject' , $context );
			$textBody = $template->renderBlock( 'body_text' , $context );
			$htmlBody = $template->renderBlock( 'body_html' , $context );

			try {
				$message = \Swift_Message::newInstance()
					->setSubject( $subject )
					->setFrom( $fromEmail , 'Tuscaloosa City Schools' )
					->setBcc( 'mypickconfirm@gmail.com' )
					->setTo( $submission->getEmail() );

				( !empty( $htmlBody ) ) ? $message->setBody( $htmlBody , 'text/html' )->addPart( $textBody , 'text/plain' ) : $message->setBody( $textBody );
				$this->container->get( 'mailer' )->send( $message );
			} catch ( \Exception $e ) {
				return false;
			}
		}
		return true;
	}

	/**s
	 * Sends the WaitList email.
	 *
	 * @param Submission $submission
	 *
	 * @return integer
	 */
	public function sendWaitListEmail( Submission $submission ) {

		$email = $submission->getEmail();
		if( !empty( $email ) ) {

			$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
				'active' => 1,
				'name' => 'waitingList',
				'type' => 'email'
			) );
			//If no correspondence found load IIABStudentTransferBundle:Email:awarded.email.twig
			$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:waitingList.email.twig' );

			$schools = "";

			$waitList = $submission->getWaitList();
			foreach( $waitList as $entry ) {
				$schools = $entry->getChoiceSchool();
			}

			$context = array(
				'submission' => $submission ,
				'waitListedSchool' => $schools
			);

			$fromEmail = $this->container->get( 'kernel' )->getContainer()->getParameter( 'swiftmailer.sender_address' );
			$subject = $template->renderBlock( 'subject' , $context );
			$textBody = $template->renderBlock( 'body_text' , $context );
			$htmlBody = $template->renderBlock( 'body_html' , $context );

			try {
				$message = \Swift_Message::newInstance()
					->setSubject( $subject )
					->setFrom( $fromEmail , 'Tuscaloosa City Schools' )
					->setBcc( 'mypickconfirm@gmail.com' )
					->setTo( $submission->getEmail() );

				if( !empty( $htmlBody ) ) {
					$message->setBody( $htmlBody , 'text/html' )
						->addPart( $textBody , 'text/plain' );
				} else {
					$message->setBody( $textBody );
				}

				//Enabled
				return $this->container->get( 'mailer' )->send( $message );
			} catch ( \Exception $e ) {
				return false;
			}
		}
	}

	/**s
	 * Sends the Awarded But WaitList for First Choice email.
	 *
	 * @param Submission $submission
	 *
	 * @return integer
	 */
	public function sendAwardedButWaitListEmail( Submission $submission ) {

		$email = $submission->getEmail();
		if( !empty( $email ) ) {

			$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
				'active' => 1,
				'name' => 'awardedButWaitList',
				'type' => 'email'
			) );
			//If no correspondence found load IIABStudentTransferBundle:Email:awarded.email.twig
			$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:awardedButWaitList.email.twig' );

			/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lottery */
			$lottery = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneByEnrollmentPeriod( $submission->getEnrollmentPeriod() );

			if( $lottery->getLotteryStatus()->getId() == '4'  ) {
				$onlineEndTime = new \DateTime( '23:59:59 +10 days ' . $submission->getManualAwardDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( $lottery->getOfflineCloseTime()->format( 'H:i' ) . ' +10 days ' . $submission->getManualAwardDate()->format( 'Y-m-d' ) );
			} else if( $lottery->getLotteryStatus()->getId() == '2' ) {
				$onlineEndTime = new \DateTime( '23:59:59 +10 days ' . $lottery->getMailFirstRoundDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( $lottery->getOfflineCloseTime()->format( 'H:i' ) . ' +10 days ' . $lottery->getMailFirstRoundDate()->format( 'Y-m-d' ) );
			} else {
				$onlineEndTime = new \DateTime( '23:59:59 +10 days ' . $lottery->getMailSecondRoundDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( $lottery->getOfflineCloseTime()->format( 'H:i' ) . ' +10 days ' . $lottery->getMailSecondRoundDate()->format( 'Y-m-d' ) );
			}

			$schools = "";

			$waitList = $submission->getWaitList();
			foreach( $waitList as $entry ) {
				$schools = $entry->getChoiceSchool();
			}

			$context = array(
				'submission' => $submission ,
				'waitListedSchool' => $schools ,
				'acceptOnlineDate' => $onlineEndTime->format( 'm/d/Y' ) ,
				'acceptOnlineTime' => $onlineEndTime->format( 'g:i a' ) ,
				'acceptOfflineDate' => $offlineEndTime->format( 'm/d/Y' ) ,
				'acceptOfflineTime' => $offlineEndTime->format( 'g:i a' ) ,
			);

			$fromEmail = $this->container->get( 'kernel' )->getContainer()->getParameter( 'swiftmailer.sender_address' );
			$subject = $template->renderBlock( 'subject' , $context );
			$textBody = $template->renderBlock( 'body_text' , $context );
			$htmlBody = $template->renderBlock( 'body_html' , $context );

			try {
				$message = \Swift_Message::newInstance()
					->setSubject( $subject )
					->setFrom( $fromEmail , 'Tuscaloosa City Schools' )
					->setBcc( 'mypickconfirm@gmail.com' )
					->setTo( $submission->getEmail() );

				if( !empty( $htmlBody ) ) {
					$message->setBody( $htmlBody , 'text/html' )
						->addPart( $textBody , 'text/plain' );
				} else {
					$message->setBody( $textBody );
				}

				//Enabled
				return $this->container->get( 'mailer' )->send( $message );
			} catch ( \Exception $e ) {
				return false;
			}
		}
	}

	/**s
	 * Sends the Denied email.
	 *
	 * @param Submission $submission
	 *
	 * @return integer
	 */
	public function sendDeniedEmail( Submission $submission ) {

		$email = $submission->getEmail();
		if( !empty( $email ) ) {

			$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
				'active' => 1,
				'name' => 'denied',
				'type' => 'email'
			) );
			//If no correspondence found load IIABStudentTransferBundle:Email:awarded.email.twig
			$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Email:denied.email.twig' );

			$context = array(
				'submission' => $submission ,
				'submissionStatus' => ( $submission->getSubmissionStatus()->getId() == 5 ? 'due to space availability' : 'due to ineligibility' ),
				'enrollment' => $submission->getEnrollmentPeriod(),
				'nextSchoolsYear' => $submission->getEnrollmentPeriod()->getTargetAcademicYear() ,
				'nextYear' => $submission->getEnrollmentPeriod()->getTargetTransferYear() ,
			);

			$fromEmail = $this->container->get( 'kernel' )->getContainer()->getParameter( 'swiftmailer.sender_address' );
			$subject = $template->renderBlock( 'subject' , $context );
			$textBody = $template->renderBlock( 'body_text' , $context );
			$htmlBody = $template->renderBlock( 'body_html' , $context );

			try {
				$message = \Swift_Message::newInstance()
					->setSubject( $subject )
					->setFrom( $fromEmail , 'Tuscaloosa City Schools' )
					->setBcc( 'mypickconfirm@gmail.com' )
					->setTo( $submission->getEmail() );

				if( !empty( $htmlBody ) ) {
					$message->setBody( $htmlBody , 'text/html' )
						->addPart( $textBody , 'text/plain' );
				} else {
					$message->setBody( $textBody );
				}

				//Enabled
				return $this->container->get( 'mailer' )->send( $message );
			} catch ( \Exception $e ) {
				return false;
			}
		}
	}
}