<?php
/**
 * Company: Image In A Box
 * Date: 3/10/15
 * Time: 2:40 PM
 * Copyright: 2015
 */

namespace IIAB\StudentTransferBundle\Service;

use IIAB\StudentTransferBundle\Entity\OpenEnrollment;
use IIAB\StudentTransferBundle\Entity\Process;
use Symfony\Component\DependencyInjection\ContainerInterface;

require_once( __DIR__ . '/../Library/mpdf/mpdf.php' );

class GeneratePDFService {

	/**
	 * @var ContainerInterface
	 */
	private $container;

	function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Generates and saves the awarded report.
	 * @param Process $process
	 * @return string
	 */
	public function awardedReport( Process $process ) {
        $openEnrollment = $process->getOpenEnrollment();
		$em = $this->container->get( 'doctrine' );

		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lottery */
		$lottery = $em->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		$mailDate = new \DateTime;
		if( $lottery != null ) {
			if( $lottery->getLotteryStatus()->getId() == '2' ) {
				$mailDate = $lottery->getMailFirstRoundDate();

				$window_length = $lottery->getFirstRoundAcceptanceWindow();
				$window_length = ( $window_length != null && $window_length )
					? $window_length : 11;

				$onlineEndTime = new \DateTime( '11:59:59 +'.$window_length.' days ' . $mailDate->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( '11:59:59 +'.$window_length.' days ' . $mailDate->format( 'Y-m-d' ) );
			} else if( $lottery->getLotteryStatus()->getId() == '3'
				|| $lottery->getLotteryStatus()->getId() == '3'
			){
				$mailDate = $lottery->getMailSecondRoundDate();

				$window_length = $lottery->getSecondRoundAcceptanceWindow();
				$window_length = ( $window_length != null && $window_length )
					? $window_length : 11;

				$onlineEndTime = new \DateTime( '11:59:59 +'.$window_length.' days ' . $lottery->getMailFirstRoundDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( '11:59:59 +'.$window_length.' days ' . $lottery->getMailSecondRoundDate()->format( 'Y-m-d' ) );
			}
		}
		$searchParameters = [
			'submissionStatus' => 2, // submissionStatus(2) = "Offered"
			'enrollmentPeriod' => $openEnrollment
		];
		$formID = $process->getForm();
		if( $formID ){ $searchParameters[ 'formID' ] = $formID; }
		$awardedSubmissions = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findBy( $searchParameters );

		$mpdf = new \mPDF( '' , 'Letter' , 0 , '' , 10 , 10 , 10 , 10 );

		if( $openEnrollment != null ) {
			$title = $openEnrollment . ' - Awarded Report';
		} else {
			$title = 'All Enrollment Periods - Awarded Report';
		}

		$total = count( $awardedSubmissions );

		$mpdf->WriteHTML( '<html><style type="text/css">body,td,th,p {font-family:sans-serif;font-style: normal;font-weight: normal; font-size: 12px;color: #000000;} p { font-family: serif; } .push { padding: 0 45pt; } </style><body>' );
		if( $total > 0 ) {

			foreach( $awardedSubmissions as $submission ) {

				if( $submission->getFormID()->getId() == 1 ) {

					$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
						'active' => 1,
						'name' => 'awarded',
						'type' => 'letter'
					) );
					$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Report:awardedLetter.html.twig' );
				} else {
					$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
						'active' => 1,
						'name' => 'awardedNonM2M',
						'type' => 'letter'
					) );
					$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Report:awardedLetterNonM2M.html.twig' );
				}

				if( $submission->getRoundExpires() == 3 ) {
					$mailDate = $submission->getManualAwardDate();
					if( $mailDate == null ){
						$mailDate = new \DateTime();
						$submission->setManualAwardDate( $mailDate );
						$em->getManager()->persist($submission);
					}

					$days = ( $submission->getFormID()->getAcceptanceWindow() != null && $submission->getFormID()->getAcceptanceWindow() > 0 )
					? $submission->getFormID()->getAcceptanceWindow() : 10;

					$onlineEndTime = new \DateTime( '11:59:59 +'.$days.' days ' . $mailDate->format( 'Y-m-d' ) );
					$offlineEndTime = new \DateTime( '11:59:59 +'.$days.' days ' . $mailDate->format( 'Y-m-d' ) );

					$mpdf->WriteHTML( $template->render( [ 'reportDate' => $mailDate , 'submission' => $submission , 'acceptOnlineDate' => $onlineEndTime->format( 'm/d/Y' ) , 'acceptOnlineTime' => $onlineEndTime->format( 'g:i a' ) , 'acceptOfflineDate' => $offlineEndTime->format( 'm/d/Y' ) , 'acceptOfflineTime' => $offlineEndTime->format( 'g:i a' ) ] ) );
					$total--;
					if( $total > 0 ) {
						$mpdf->WriteHTML( '<pagebreak></pagebreak>' );
					}
				} else {

					$mpdf->WriteHTML($template->render(['reportDate' => $mailDate, 'submission' => $submission, 'acceptOnlineDate' => $onlineEndTime->format('m/d/Y'), 'acceptOnlineTime' => $onlineEndTime->format('g:i a'), 'acceptOfflineDate' => $offlineEndTime->format('m/d/Y'), 'acceptOfflineTime' => $offlineEndTime->format('g:i a')]));
					$total--;
					if ($total > 0) {
						$mpdf->WriteHTML('<pagebreak></pagebreak>');
					}
				}
			}
		} else {
			$mpdf->WriteHTML( '<p>No Offer letters found.</p>' );
		}
		$mpdf->WriteHTML( '</body></html>' );
		$mpdf->mirrorMargins = false;
		$mpdf->SetTitle( $title );
		$mpdf->SetDisplayMode( 'fullpage' , 'two' );
		$name = date( 'Y-m-d-H-i' ) . '-Awarded-Report.pdf';
		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/awarded/' . $openEnrollment->getId() . '/';

		if( !file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$fileLocation = $rootDIR . $name;

		$pdfContent = null;
		$mpdf->Output( $fileLocation , 'F' );
		$mpdf = null;

		$fileLocation = '/reports/awarded/' . $openEnrollment->getId() . '/' . $name;

		return $fileLocation;
	}

	/**
	 * Generates and saves the Waitlist Report.
	 * @param Process $process
	 *
	 * @return string
	 */
	public function waitListReport( Process $process ) {

        $openEnrollment = $process->getOpenEnrollment();

		$em = $this->container->get( 'doctrine' );

		$searchParameters = [
			'submissionStatus' => 10 , // submissionStatus(10) = "Wait List"
			'enrollmentPeriod' => $openEnrollment
		];
		$formID = $process->getForm();
		if( $formID ){ $searchParameters[ 'formID' ] = $formID; }
		$waitlistedSubmissions = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findBy( $searchParameters , null );

		$mpdf = new \mPDF( '' , 'Letter' , 0 , '' , 10 , 10 , 10 , 10 );

		$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'waitingList',
			'type' => 'letter'
		) );
		$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Report:waitListLetter.html.twig' );

		if( $openEnrollment != null ) {
			$title = $openEnrollment . ' - Waiting List Letter';
		} else {
			$title = 'All Enrollment Periods - Waiting List Letter';
		}

		$total = count( $waitlistedSubmissions );

		$mpdf->WriteHTML( '<html><style type="text/css">body,td,th,p {font-family:sans-serif;font-style: normal;font-weight: normal; font-size: 12px;color: #000000;} p { font-family: serif; } .push { padding: 0 45pt; }</style><body>' );
		if( $total > 0 ) {

			foreach( $waitlistedSubmissions as $submission ) {
				$schools = "";
				$waitList = $submission->getWaitList();
				foreach( $waitList as $entry ) {
					$schools = $entry->getChoiceSchool();
				}

				$mpdf->WriteHTML( $template->render( [
					'submission' => $submission ,
					'waitListedSchool' => $schools ,
					'reportDate' => $openEnrollment->getMailDate()
				] ) );

				$total--;
				if( $total > 0 ) {
					$mpdf->WriteHTML( '<pagebreak></pagebreak>' );
				}
			}
		} else {
			$mpdf->WriteHTML( '<p>No Wait List letters found.</p>' );
		}
		$mpdf->WriteHTML( '</body></html>' );

		$name = date( 'Y-m-d-H-i' ) . '-Wait-List-Report.pdf';

		$mpdf->mirrorMargins = false;
		$mpdf->SetTitle( $title );
		$mpdf->SetDisplayMode( 'fullpage' , 'two' );

		$rootDIR = $this->container->get('kernel')->getRootDir() . '/../web/reports/wait-list/' . $openEnrollment->getId() . '/';

		if( !file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$fileLocation = $rootDIR . $name;

		$pdfContent = null;
		$mpdf->Output( $fileLocation , 'F' );
		$mpdf = null;

		$fileLocation = '/reports/wait-list/' . $openEnrollment->getId() . '/' . $name;

		return $fileLocation;
	}

	/**
	 * Generates and saves the Awarded BUT Waitlist Report.
	 * @param Process $process
	 *
	 * @return string
	 */
	public function awardedButWaitListReport( Process $process ) {

		$openEnrollment = $process->getOpenEnrollment();
		$em = $this->container->get( 'doctrine' );

		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lottery */
		$lottery = $em->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		$mailDate = new \DateTime;

		if( $lottery != null ) {
			if ($lottery->getLotteryStatus()->getId() == '2') {

				$window_length = $lottery->getFirstRoundAcceptanceWindow();
				$window_length = ( $window_length != null && $window_length )
					? $window_length : 11;

				$onlineEndTime = new \DateTime( '11:59:59 +'.$window_length.' days ' . $lottery->getMailFirstRoundDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( '11:59:59 +'.$window_length.' days ' . $lottery->getMailFirstRoundDate()->format( 'Y-m-d' ) );

			} else {
				$window_length = $lottery->getSecondRoundAcceptanceWindow();
				$window_length = ( $window_length != null && $window_length )
					? $window_length : 11;

				$onlineEndTime = new \DateTime( '11:59:59 +'.$window_length.' days ' . $lottery->getMailSecondRoundDate()->format( 'Y-m-d' ) );
				$offlineEndTime = new \DateTime( '11:59:59 +'.$window_length.' days ' . $lottery->getMailSecondRoundDate()->format( 'Y-m-d' ) );
			}
		}

		$em = $this->container->get( 'doctrine' );

		$searchParameters = [
			'enrollmentPeriod' => $openEnrollment,
			'submissionStatus' => 11 // submissionStatus(10) = "Offered and Wait List"
		];
		$formID = $process->getForm();
		if( $formID ){ $searchParameters[ 'formID' ] = $formID; }
		$waitlistedSubmissions = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findBy( $searchParameters , null );

		$mpdf = new \mPDF( '' , 'Letter' , 0 , '' , 10 , 10 , 10 , 10 );
		$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'awardedButWaitList',
			'type' => 'letter'
		) );
		$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Report:awardedButWaitListLetter.html.twig' );

		if( $openEnrollment != null ) {
			$title = $openEnrollment . ' - Awarded but Waiting List Letter';
		} else {
			$title = 'All Enrollment Periods - Awarded but Waiting List Letter';
		}

		$total = count( $waitlistedSubmissions );

		$mpdf->WriteHTML( '<html><style type="text/css">body,td,th,p {font-family:sans-serif;font-style: normal;font-weight: normal; font-size: 12px;color: #000000;} p { font-family: serif; } .push { padding: 0 45pt; }</style><body>' );
		if( $total > 0 ) {

			foreach( $waitlistedSubmissions as $submission ) {
				if( $submission->getRoundExpires() == 3 ) {
					$mailDate = $submission->getManualAwardDate();

					$days = ( $submission->getFormID()->getAcceptanceWindow() != null && $submission->getFormID()->getAcceptanceWindow() > 0 )
					? $submission->getFormID()->getAcceptanceWindow() : 10;

					$onlineEndTime = new \DateTime( '11:59:59 +'.$days.' days ' . $submission->getManualAwardDate()->format( 'Y-m-d' ) );
					$offlineEndTime = new \DateTime( '11:59:59 +'.$days.' days ' . $submission->getManualAwardDate()->format( 'Y-m-d' ) );
				}

				$schools = "";

				$waitList = $submission->getWaitList();
				foreach( $waitList as $entry ) {
					$schools = $entry->getChoiceSchool();
				}

				$mpdf->WriteHTML( $template->render( [
					'submission' => $submission ,
					'waitListedSchool' => $schools ,
					'acceptOnlineDate' => $onlineEndTime->format( 'm/d/Y' ) ,
					'acceptOnlineTime' => $onlineEndTime->format( 'g:i a' ) ,
					'acceptOfflineDate' => $offlineEndTime->format( 'm/d/Y' ) ,
					'acceptOfflineTime' => $offlineEndTime->format( 'g:i a' ) ,
					'reportDate' => $mailDate
				] ) );

				$total--;
				if( $total > 0 ) {
					$mpdf->WriteHTML( '<pagebreak></pagebreak>' );
				}
			}
		} else {
			$mpdf->WriteHTML( '<p>No Awarded But Wait List letters found.</p>' );
		}
		$mpdf->WriteHTML( '</body></html>' );

		$name = date( 'Y-m-d-H-i' ) . '-Awarded-But-Wait-List-Report.pdf';

		$mpdf->mirrorMargins = false;
		$mpdf->SetTitle( $title );
		$mpdf->SetDisplayMode( 'fullpage' , 'two' );

		$rootDIR = $this->container->get('kernel')->getRootDir() . '/../web/reports/awarded-but-wait-list/' . $openEnrollment->getId() . '/';

		if( !file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$fileLocation = $rootDIR . $name;

		$pdfContent = null;
		$mpdf->Output( $fileLocation , 'F' );
		$mpdf = null;

		$fileLocation = '/reports/wait-list/' . $openEnrollment->getId() . '/' . $name;

		return $fileLocation;
	}

	/**
	 * Generates and saves the Denied Report.
	 * @param Process $process
	 *
	 * @return string
	 */
	public function deniedReport( Process $process ) {

        $openEnrollment = $process->getOpenEnrollment();

		$em = $this->container->get( 'doctrine' );

		$searchParameters = [ 'enrollmentPeriod' => $openEnrollment ];
		$statusFilter = $process->getSubmissionStatus();
		$searchParameters[ 'submissionStatus' ] = ( $statusFilter ) ? $statusFilter : [ 5 , 9 ]; // submissionStatus(5) = "Denied due to Space" OR "Denied due to ineligibility"
		$formID = $process->getForm();
		if( $formID ){ $searchParameters[ 'formID' ] = $formID; }
		$deniedSubmissions = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findBy( $searchParameters , null );

		$mpdf = new \mPDF( '' , 'Letter' , 0 , '' , 10 , 10 , 10 , 10 );
		$correspondence = $this->container->get( 'doctrine' )->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'denied',
			'type' => 'letter'
		) );
		$template = ($correspondence) ? $this->container->get( 'twig' )->createTemplate($correspondence->getTemplate()) : $this->container->get( 'twig' )->loadTemplate( 'IIABStudentTransferBundle:Report:deniedLetter.html.twig' );

		if( $openEnrollment != null ) {
			$title = $openEnrollment . ' - Denied Letter';
		} else {
			$title = 'All Enrollment Periods - Denied Letter';
		}

		$total = count( $deniedSubmissions );

		$mpdf->WriteHTML( '<html><style type="text/css">body,td,th,p {font-family:sans-serif;font-style: normal;font-weight: normal; font-size: 12px;color: #000000;} p { font-family: serif; } .push { padding: 0 45pt; }</style><body>' );
		if( $total > 0 ) {

			foreach( $deniedSubmissions as $submission ) {

				$mpdf->WriteHTML( $template->render( [
					'submission' => $submission ,
					'submissionStatus' => ( $submission->getSubmissionStatus()->getId() == 5 ? 'due to space availability' : 'due to ineligibility' ),
					'enrollment' => $submission->getEnrollmentPeriod(),
					'nextSchoolsYear' => $openEnrollment->getTargetAcademicYear() ,
					'nextYear' => $openEnrollment->getTargetTransferYear() ,
					'reportDate' => $openEnrollment->getMailDate() ,
				] ) );

				$total--;

				if( $total > 0 ) {
					$mpdf->WriteHTML( '<pagebreak></pagebreak>' );
				}
			}
		} else {
			$mpdf->WriteHTML( '<p>No Denied letters found.</p>' );
		}
		$mpdf->WriteHTML( '</body></html>' );

		$name = date( 'Y-m-d-H-i' ) . '-Denied-List-Report.pdf';

		$mpdf->mirrorMargins = false;
		$mpdf->SetTitle( $title );
		$mpdf->SetDisplayMode( 'fullpage' , 'two' );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/denied/' . $openEnrollment->getId() . '/';

		if( !file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$fileLocation = $rootDIR . $name;

		$pdfContent = null;
		$mpdf->Output( $fileLocation , 'F' );
		$mpdf = null;

		$fileLocation = '/reports/denied/' . $openEnrollment->getId() . '/' . $name;

		return $fileLocation;
	}
}