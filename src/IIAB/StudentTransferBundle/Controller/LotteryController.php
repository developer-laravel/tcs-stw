<?php

namespace IIAB\StudentTransferBundle\Controller;

use Exporter\Handler;
use Exporter\Source\ArraySourceIterator;
use Exporter\Writer\CsvWriter;
use Exporter\Writer\XlsWriter;
use Exporter\Writer\XmlExcelWriter;
use IIAB\StudentTransferBundle\Entity\CurrentEnrollmentSettings;
use IIAB\StudentTransferBundle\Entity\Process;
use IIAB\StudentTransferBundle\Entity\Submission;
use IIAB\StudentTransferBundle\Form\CurrentEnrollmentSettingsForm;
use IIAB\StudentTransferBundle\Lottery\Lottery;
use IIAB\StudentTransferBundle\Entity\LotteryLog;
use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Entity\SlottingReport;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;

class LotteryController extends Controller {

	/**
	 * @param Request $request
	 * @param String $uniqueID
	 *
	 * @Route("/awarded/{uniqueID}" , name="stw_lottery_accept" )
	 * @Template()
	 *
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function lotteryAcceptAction( Request $request , $uniqueID  ) {
		$textFields = array();

		if( !empty( $uniqueID ) ) {

			$submission = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->findOneBy( array(
				'url' => $uniqueID,
			) );
			$nextSchoolYearText = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Settings')->findOneBy( array(
				'settingName' => 'next school year'
			) );


			if( $submission != null ) {

				$awardedSchool = ( $submission->getAwardedSchoolID()->getId() == $submission->getFirstChoice()->getId() ? $submission->getFirstChoice()->getSchoolName() : $submission->getSecondChoice()->getSchoolName() );
				$awardedGrade =  ( $submission->getAwardedSchoolID()->getId() == $submission->getFirstChoice()->getId() ? $submission->getFirstChoice()->getGrade() : $submission->getSecondChoice()->getGrade() );

				$textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ) , 'value' => $submission->getStudentID() );
				$textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.name' , array() , 'IIABStudentTransferBundle' ) , 'value' => $submission->getFirstName() . ' ' . $submission->getLastName() );
				$textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) , 'value' => $submission->getDob() );
				if( $submission->getSubmissionStatus()->getId() == 11 ) {
					$waitList = '';
					foreach( $submission->getWaitList() as $waitListed ) {
						$waitList = $waitListed->getChoiceSchool()->getSchoolName();
					}
					$textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'awarded.waitList' , array() , 'IIABStudentTransferBundle' ) , 'value' => $waitList );
				}
				$textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'awarded.awardedSchool' , array() , 'IIABStudentTransferBundle' ) , 'value' => $awardedSchool );
				$textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $awardedGrade );

				//If the submissionStatus is correctly at "Offered" OR "Offered but Wait Listed"
				if( $submission->getSubmissionStatus()->getId() == 2 || $submission->getSubmissionStatus()->getId() == 11 ) {
					$form = $this->createFormBuilder()
						->add( 'hidden_input' , 'hidden' )
						->add( 'decline' , 'submit' , array(
							'label' => ( $submission->getSubmissionStatus()->getId() == 11 ? $this->container->get( 'translator' )->trans( 'awarded.acceptWaitList' , array() , 'IIABStudentTransferBundle' ) : $this->container->get( 'translator' )->trans( 'awarded.decline' , array() , 'IIABStudentTransferBundle' ) ),
							'attr' => array(
								'class' => 'button' . ( $submission->getSubmissionStatus()->getId() == 11 ? ' waitlist button-action' : ' button-caution' )
							)
						) )
						->add( 'accept' , 'submit' , array(
							'label' => ( $submission->getSubmissionStatus()->getId() == 11 ? $this->container->get( 'translator' )->trans( 'awarded.acceptSecond' , array() , 'IIABStudentTransferBundle' ) : $this->container->get( 'translator' )->trans( 'awarded.accept' , array() , 'IIABStudentTransferBundle' ) ),
							'attr' => array(
								'class' => 'button button-action'
							)
						) )
					;
					if( $submission->getSubmissionStatus()->getId() == 11 ) {
						$form->add( 'decline_both' , 'submit' , array(
							'label' => $this->container->get( 'translator' )->trans( 'awarded.decline_both' , array() , 'IIABStudentTransferBundle' ),
							'attr' => array(
								'class' => 'button button-caution'
							)
						) );
					}
					$form = $form->getForm();
				} else {
					$form = $this->createFormBuilder()
						->add( 'hidden_input' , 'hidden' )
						->getForm();
					$textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'awarded.status' , array() , 'IIABStudentTransferBundle' ) , 'value' => $this->get( 'translator' )->trans( 'awarded.statusChanged' , array( '%status%' => $submission->getSubmissionStatus()->getStatus() ) , 'IIABStudentTransferBundle' ) );
				}
			} else {
				$form = $this->createFormBuilder()
					->add( 'hidden_input' , 'hidden' )
					->getForm();
				$textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'awarded.error' , array() , 'IIABStudentTransferBundle' ) , 'value' => $this->get( 'translator' )->trans( 'awarded.notfound' , array() , 'IIABStudentTransferBundle' ) );

			}
		} else {
			//Unique Code not found.
			$form = $this->createFormBuilder()
				->add( 'hidden_input' , 'hidden' )
				->getForm();

		}

		$form->handleRequest( $request );

		if( $form->isValid() ) {
			if( $submission->getSubmissionStatus()->getId() == 11 ) {
				if( $form->get( 'decline_both' )->isClicked() ) {
					//User clicked the button to decline Wait List and Second Choice Awarded Slot.

					$statusEntity = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )->find( 4 ); //offered and declined

					$this->get('stw.email')->sendDeclinedEmail( $submission );

					//Remove the Wait Listed Choice
					$waitListed = $submission->getWaitList();
					foreach( $waitListed as $waitList ) {
						$submission->removeWaitList( $waitList );
						$this->getDoctrine()->getManager()->remove( $waitList );
					}

					$currentEnrollmentChanged = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')->findOneBy( array(
						'enrollmentPeriod' => $submission->getEnrollmentPeriod(),
						'groupId' => $submission->getAwardedSchoolID()->getGroupID()
					) , array( 'addedDateTime' => 'DESC' ) );

					$race = trim( strtolower( $submission->getRace() ) );
					switch( $race ) {

						case 'black':
						case 'black/african american':
							$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
							break;

						case 'other':
							$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
							break;

						case 'white':
							$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
							break;
					}
					$this->getDoctrine()->getManager()->persist( $currentEnrollmentChanged );

					$submission->setSubmissionStatus( $statusEntity );
					//submission status changed from awarded to declined
					$this->recordAudit( 5 , $submission->getId() , $submission->getStudentID() );

					$submissionReporting = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->find( $submission->getId() );
					//Used to run the reporting so we can build the specific URL.
					$reporting = new SlottingReport();
					$reporting->setEnrollmentPeriod( $submissionReporting->getEnrollmentPeriod() );
					$reporting->setRound( $submissionReporting->getRoundExpires() );
					$reporting->setSchoolID( $submissionReporting->getAwardedSchoolID() );
					$reporting->setStatus( $statusEntity->getStatus() );
					$this->getDoctrine()->getManager()->persist( $reporting );

					$this->getDoctrine()->getManager()->flush();

					return $this->redirect( $this->generateUrl( 'stw_declined' ) );
				}
			}
			if( $form->get( 'decline' )->isClicked() ) {
				//User clicked the button to decline the submission.

				if( $submission->getSubmissionStatus()->getId() == 11 ) {
					$statusEntity = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )->find( 10 ); //wait listed
					$this->get('stw.email')->sendWaitListEmail( $submission );
				} else {
					$statusEntity = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )->find( 4 ); //offered and declined
					$this->get('stw.email')->sendDeclinedEmail( $submission );
				}

				$currentEnrollmentChanged = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')->findOneBy( array(
					'enrollmentPeriod' => $submission->getEnrollmentPeriod(),
					'groupId' => $submission->getAwardedSchoolID()->getGroupID()
				) , array( 'addedDateTime' => 'DESC' ) );

				$race = trim( strtolower( $submission->getRace() ) );
				switch( $race ) {

					case 'black':
					case 'black/african american':
						$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
						break;

					case 'other':
						$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
						break;

					case 'white':
						$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
						break;

				}
				$this->getDoctrine()->getManager()->persist( $currentEnrollmentChanged );

				$submission->setSubmissionStatus( $statusEntity );
				//submission status changed from awarded to declined
				$this->recordAudit( 5 , $submission->getId() , $submission->getStudentID() );

				$submissionReporting = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->find( $submission->getId() );
				//Used to run the reporting so we can build the specific URL.
				$reporting = new SlottingReport();
				$reporting->setEnrollmentPeriod( $submissionReporting->getEnrollmentPeriod() );
				$reporting->setRound( $submissionReporting->getRoundExpires() );
				$reporting->setSchoolID( $submissionReporting->getAwardedSchoolID() );
				$reporting->setStatus( $statusEntity->getStatus() );
				$this->getDoctrine()->getManager()->persist( $reporting );

				$this->getDoctrine()->getManager()->flush();

				$this->get( 'session' )->set( 'submissionID' , $submission->getId() );
				if( $statusEntity->getId() == 10 ) {
					return $this->redirect( $this->generateUrl( 'stw_choice_waitlist' ) );
				}
				return $this->redirect( $this->generateUrl( 'stw_declined' ) );
			}
			if( $form->get('accept')->isClicked() ) {
				//User clicked the button to decline the submission.

				$statusEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:SubmissionStatus')->find(3); //offered and accepted

				if( $submission->getAwardedSchoolID()->getId() == $submission->getFirstChoice()->getId() ) {
					$grade		= $submission->getFirstChoice()->getGrade();
					$schoolID	= $submission->getFirstChoice()->getSchoolID();
				} else {
					$grade		= $submission->getSecondChoice()->getGrade();
					$schoolID	= $submission->getSecondChoice()->getSchoolID();
				}

				$waitListed = $submission->getWaitList();
				foreach( $waitListed as $waitList ) {
					$submission->removeWaitList( $waitList );
					$this->getDoctrine()->getManager()->remove( $waitList );
				}

				$submission->setSubmissionStatus( $statusEntity );
				//submission status changed from awarded to accepted
				$this->recordAudit( 4 , $submission->getId() , $submission->getStudentID() );

				//Assign the control number.
				//Control Number is {Form Confirmation Style}-{Enrollment Period Confirmation Style}-{SubmissionID}
				$submission->setControlNumber( $submission->getFormID()->getFormConfirmation() . '-' . $submission->getEnrollmentPeriod()->getConfirmationStyle() . '-' . $submission->getId() );

				$submissionReporting = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->find( $submission->getId() );
				//Used to run the reporting so we can build the specific URL.
				$reporting = new SlottingReport();
				$reporting->setEnrollmentPeriod( $submissionReporting->getEnrollmentPeriod() );
				$reporting->setRound( $submissionReporting->getRoundExpires() );
				$reporting->setSchoolID( $submissionReporting->getAwardedSchoolID() );
				$reporting->setStatus( $statusEntity->getStatus() );

				$this->getDoctrine()->getManager()->persist( $reporting );

				$this->get('stw.email')->sendAcceptedEmail( $submission );
				$this->get( 'session' )->set( 'submissionID' , $submission->getId() );
				$this->getDoctrine()->getManager()->flush();
				return $this->redirect( $this->generateUrl( 'stw_accepted' ) );
			}
		}

		return $this->render( '@IIABStudentTransfer/Lottery/awarded.html.twig' , array( 'nonFormFields' => $textFields , 'form' => $form->createView() , 'submission' => $submission ) );
	}

	/**
	 * @param Request $request
	 *
	 * @Route("/admin/run-lottery/export" , name="stw_admin_run_lottery_export" )
	 * @Template( "IIABStudentTransferBundle:Report:report.html.twig")
	 *
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function lotteryExportAction( Request $request ) {
		$admin_pool = $this->get('sonata.admin.pool');

		$content = $this->get('kernel')->getRootDir() . '/../web/uploads/submission-export.csv';

		if( file_exists( $content ) )
			unlink( $content );

		$form = $this->createFormBuilder()
			->setAction( $this->generateUrl( 'stw_admin_run_lottery_export' ) )
			->setMethod( 'post' )
			->add( 'enrollmentPeriod' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:OpenEnrollment',
				'placeholder'	=> 'Select an Enrollment Period'
			) )
			->add( 'status' , 'entity' , array(
				'class'	=> 'IIABStudentTransferBundle:SubmissionStatus',
				'placeholder'	=> 'Select a Status'
			) )
			->add( 'submit' , 'submit' )
			->getForm();
		;
		$form->handleRequest( $request );

		if( $request->getMethod() == 'POST' ) {
			if( $form->isValid() ) {
				$data = $form->getData();
				$source = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->createQueryBuilder('s')
					->where( 's.enrollmentPeriod = :enrollment' )
					->andWhere( 's.submissionStatus = :status' )
					->setParameters( array(
						'enrollment'	=> $data['enrollmentPeriod']->getId(),
						'status'		=> $data['status']->getId()
					) )
					->getQuery()
					->getResult()
				;
				$excelArray = array();
				$index = 0;
				/**
				 * @var \IIAB\STudentTransferBundle\Entity\Submission $submission
				 */
				foreach( $source as $submission ) {
					if( $submission->getGrade() == 99 ) {
						$grade = sprintf( '%1$02d' , 0 );
					} else {
						$grade = sprintf( '%1$02d' , abs( $submission->getGrade() ) + 1 );
					}
					$awardedSchoolName = '';
					if( $submission->getAwardedSchoolID() != null ) {
						/*if( $submission->getAwardedSchoolID()->getId() == $submission->getFirstChoice()->getId() ) {
							$awardedSchoolName = $submission->getFirstChoice()->__toString();
						}
						if( $submission->getAwardedSchoolID()->getId() == $submission->getSecondChoice()->getId() ) {
							$awardedSchoolName = $submission->getSecondChoice()->__toString();
						}*/
						$awardedSchoolName = $submission->getAwardedSchoolID()->__toString();
					}

					$excelArray[$index] = array(
						'Confirmation Number'	=> $submission->getConfirmationNumber(),
						'Student ID'			=> $submission->getStudentID(),
						'First Name'			=> $submission->getFirstName(),
						'Last Name'				=> $submission->getLastName(),
						'Date of Birth'			=> $submission->getDob(),
						'Address'				=> $submission->getAddress(),
						'City'					=> $submission->getCity(),
						'Zip'					=> $submission->getZip(),
						'Status'				=> $submission->getSubmissionStatus()->__toString(),
						'Grade'					=> $grade,
						'Race'					=> $submission->getRace(),
						'Current School'  		=> $submission->getCurrentSchool(),
						'HSV Zoned Schools'		=> $submission->getHsvZonedSchoolsString(),
						'Employee ID'			=> $submission->getEmployeeID(),
						'Employee First Name'	=> $submission->getEmployeeFirstName(),
						'Employee Last Name'	=> $submission->getEmployeeLastName(),
						'Employee Location'		=> $submission->getEmployeeLocation(),
						'Awarded School'		=> $awardedSchoolName,
					);
					if( $data['status']->getId() == 2 ) {

						$lottery = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneBy( array(
							'enrollmentPeriod' => $submission->getEnrollmentPeriod(),
						) );

						if( $submission->getRoundExpires() == 1 ) {
							$urlExpires = new \DateTime( $lottery->getMailFirstRoundDate()->format( 'm/d/Y 23:59:59' ) );
							$urlExpires->modify( '+10 days' );
						}
						if( $submission->getRoundExpires() == 2 ) {
                            $urlExpires = new \DateTime( $lottery->getMailSecondRoundDate()->format( 'm/d/Y 23:59:59' ) );
                            $urlExpires->modify( '+10 days' );
                        }

                        if( $submission->getRoundExpires() == 3 ) {
                            $urlExpires = new \DateTime( $submission->getManualAwardDate()->format( 'm/d/Y 23:59:59' ) );
                            $urlExpires->modify( '+10 days' );
                        }


						$excelArray[$index]['Acceptance URL']	= $this->generateUrl( 'stw_lottery_accept' , array( 'uniqueID' => $submission->getUrl() ) , true );
						$excelArray[$index]['Link Expiration']	= $urlExpires->format( 'm/d/Y 23:59:59' );
					}
					$index++;
				}

				$source = new ArraySourceIterator( $excelArray );

				$writer = new CsvWriter( $content );

				Handler::create( $source , $writer )->export();

				$content = file_get_contents( $content );

				$response = new Response();

				$response->headers->set('Content-Type', 'text/csv');
				$response->headers->set('Content-Disposition', 'attachment;filename="submission-export.csv"');

				$response->setContent($content);
				unlink( $this->get('kernel')->getRootDir() . '/../web/uploads/submission-export.csv' );
				return $response;
			}
		}
		return array(
			'admin_pool' => $admin_pool,
			'form' => $form->createView()
		);
	}


	/**
	 * Display the Accepted submission
	 *
	 * @param Request $request
	 *
	 * @Route( "/accepted" , name="stw_accepted")
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function acceptAction( Request $request ) {

		/**
		 * Always clear the formData in the session on the index page. Making sure to have clean session.
		 */
		$lastFormData = $request->getSession()->has( 'submissionID' );

		$submission = new Submission();
		if( $lastFormData !== false ) {
			$submissionID = $request->getSession()->get('submissionID');
			$submission = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->find( $submissionID );
			//empty all sessions data to keep data secure.
			$request->getSession()->remove( 'submissionID' );
		}

		return $this->render( 'IIABStudentTransferBundle:Lottery:accepted.html.twig' , array( 'submission' => $submission ) );
	}


	/**
	 * @Route("/admin/process/lottery/{period}/", name="process_lottery")
	 * @Template("@IIABStudentTransfer/Lottery/process.html.twig")
	 *
	 * @param integer $period
	 * @param Request $request
	 * @return array()
	 * @throws \Exception
	 */
	public function processLotteryAction( Request $request , $period ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );

		$openEnrollment = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->find( $period );

		if( $openEnrollment == null ) {
			return $this->redirect( $this->generateUrl( 'process_lottery_select' ) );
		}

		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lottery */
		$lottery = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneByEnrollmentPeriod( $openEnrollment );
		if( $lottery == null ) {
			return $this->redirect( $this->generateUrl( 'process_lottery_select' ) );
		}

		$alreadyAcceptedOrDeclinedSubmissions = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )->createQueryBuilder( 's' )
			->where( 's.enrollmentPeriod = :enrollment' )
			->andWhere( 's.submissionStatus IN (3,4)' )
			->andWhere( 's.formID = 6')
			->setParameter( 'enrollment' , $openEnrollment )
			->getQuery()
			->getResult()
		;
		$alreadyAcceptedOrDeclinedSubmissionsCount = count( $alreadyAcceptedOrDeclinedSubmissions );
		$alreadyAcceptedOrDeclinedSubmissions = null;

		$alreadyAwardedSubmissions = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )->createQueryBuilder( 's' )
			->where( 's.enrollmentPeriod = :enrollment' )
			->andWhere( 's.submissionStatus IN (2)' )
			->andWhere( 's.formID = 6')
			->andWhere( 's.roundExpires IN ( 1,2 )')
			->setParameter( 'enrollment' , $openEnrollment )
			->getQuery()
			->getResult()
		;
		$alreadyAwardedSubmissionsCount = count( $alreadyAwardedSubmissions );
		$alreadyAwardedSubmissions = null;

		$form = $this->createFormBuilder();

		if( $lottery->getLotteryStatus()->getId() == 1 || ( $lottery->getLotteryStatus()->getId() == 2 && $alreadyAcceptedOrDeclinedSubmissionsCount == 0 ) ) {
			$form->add( 'processRound1' , 'submit' , [ 'label' => 'Process Lottery Round 1' , 'attr' => [ 'class' => 'btn btn-primary' , 'onclick' => 'return confirm("Are you sure you want to Process Round 1?\n\nPlease double check your Processing Date.");' ] ] );
			$form->add( 'round1ProcessingDate' , 'date' , [ 'label' => 'Round 1 Mail Date' , 'attr' => [ 'class' => 'sonata-medium-date' ] , 'data' => $lottery->getMailFirstRoundDate() ] );

			$form->add( 'processRound2' , 'submit' , [ 'label' => 'Process Lottery Round 2' , 'attr' => [ 'class' => 'btn disabled' , 'onclick' => 'return false;' , 'style' => 'text-decoration: line-through;' ] , 'disabled' => true ] );
			$form->add( 'round2ProcessingDate' , 'date' , [ 'label' => 'Round 2 Mail Date' , 'attr' => [ 'class' => 'sonata-medium-date' ] , 'data' => $lottery->getMailSecondRoundDate() , 'disabled' => true ] );
		} elseif( $lottery->getLotteryStatus()->getId() == 2 && $alreadyAwardedSubmissionsCount == 0 ) {
			$form->add( 'processRound1' , 'submit' , [ 'label' => 'Process Lottery Round 1' , 'attr' => [ 'class' => 'btn disabled' , 'onclick' => 'return false;' , 'style' => 'text-decoration: line-through;' ] , 'disabled' => true ] );
			$form->add( 'round1ProcessingDate' , 'date' , [ 'label' => 'Round 1 Mail Date' , 'attr' => [ 'class' => 'sonata-medium-date' ] , 'data' => $lottery->getMailFirstRoundDate() , 'disabled' => true ] );

			$form->add( 'processRound2' , 'submit' , [ 'label' => 'Process Lottery Round 2' , 'attr' => [ 'class' => 'btn btn-primary' , 'onclick' => 'return confirm("Are you sure you want to Process Round 2?\n\nPlease double check your Processing Date.");' ] ] );
			$form->add( 'round2ProcessingDate' , 'date' , [ 'label' => 'Round 2 Mail Date' , 'attr' => [ 'class' => 'sonata-medium-date' ] , 'data' => $lottery->getMailSecondRoundDate() ] );
		} else {
			$form->add( 'processRound1' , 'submit' , [ 'attr' => [ 'style' => 'display: none;' , 'disabled' => 'disabled' , 'onclick' => 'return false;' ] ] );
			$form->add( 'processRound2' , 'submit' , [ 'attr' => [ 'style' => 'display: none;' , 'disabled' => 'disabled' , 'onclick' => 'return false;' ] ] );
		}

		$form->add( 'offlineCloseTime' , 'time' , [ 'label' => 'Offline Acceptance Close Time' , 'data' => $lottery->getOfflineCloseTime() , 'attr' => [ 'class' => 'sonata-medium-date' ] ] );

		$form->add( 'processPDF' , 'submit' , [ 'label' => 'Generate PDFs Letters' , 'attr' => [ 'class' => 'btn btn-info' ] ] );

		$form = $form->getForm();

		$form->handleRequest( $request );

		if( $form->isValid() ) {

			$data = $form->getData();

			$lottery->setOfflineCloseTime( $data['offlineCloseTime'] );

			if( $form->get( 'processRound1' )->isClicked() ) {

				if( ( $lottery->getLotteryStatus()->getId() == 2 || $lottery->getLotteryStatus()->getId() == 3 ) && $alreadyAwardedSubmissionsCount > 0 && $alreadyAcceptedOrDeclinedSubmissionsCount == 0 ) {
					//Reset the submissions and enrollment numbers back to the original processing state.
					$em = $this->get('doctrine.orm.entity_manager');

					//Round 1 Reset
					if( $lottery->getLotteryStatus()->getId() == 2 ) {
						$lastDateTime = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:CurrentEnrollmentSettings')->createQueryBuilder('current')->select('current.addedDateTime')->where('current.enrollmentPeriod = :enrollment')->setParameter('enrollment',$openEnrollment)->orderBy('current.addedDateTime','DESC')->distinct(true)->getQuery()->getResult();
						//$lastDateTime = $this->get('doctrine')->getConnection()->query('SELECT DISTINCT(c.`addedDateTime`) FROM `stw_current_enrollment_settings` c WHERE c.`enrollmentPeriod` = ' . $openEnrollment->getId() . ' ORDER BY c.`addedDateTime` DESC LIMIT 0,1');

						if( count( $lastDateTime ) > 1 ) {
							//Has to have atleat two entries to make sure we do not delete the base numbers. Meaning the Lottery has Ran atleast ONCE.
							$this->get( 'doctrine' )->getConnection()->query( 'DELETE FROM `stw_current_enrollment_settings` WHERE `addedDateTime` = "' . $lastDateTime[0]['addedDateTime']->format('Y-m-d H:i:s') . '";' .
								'DELETE FROM `WaitList` WHERE `openEnrollment` = ' . $openEnrollment->getId() . '; ' .
								'UPDATE `stw_submission` SET `submissionstatus` = 1, `url` = NULL, `roundExpires` = NULL, `awardedSchoolID` = NULL WHERE `submissionstatus` IN (2,7,11) AND `enrollmentPeriod` = ' . $openEnrollment->getId() . ';' );
						}
					}
					if( $lottery->getLotteryStatus()->getId() == 3 ) {
						throw new \Exception( 'trying to reprocess Round 1 after Round 2 has already been completed.' );
					}
				}

				$process = new Process();
				$process->setAddDateTime( new \DateTime() );
				$process->setEvent( 'lottery' );
				$process->setType( 'round-1' );
				$process->setOpenEnrollment( $openEnrollment );
				$lottery->setMailFirstRoundDate( $data['round1ProcessingDate'] );
				$lottery->setLastFirstRoundProcess( new \DateTime() );
				$this->getDoctrine()->getManager()->persist( $process );
			}
			if( $form->get( 'processRound2' )->isClicked() ) {

				$process = new Process();
				$process->setAddDateTime( new \DateTime() );
				$process->setEvent( 'lottery' );
				$process->setType( 'round-2' );
				$process->setOpenEnrollment( $openEnrollment );
				$lottery->setMailSecondRoundDate( $data['round2ProcessingDate'] );
				$lottery->setLastSecondRoundProcess( new \DateTime() );
				$this->getDoctrine()->getManager()->persist( $process );
			}
			if( $form->get( 'processPDF' )->isClicked() ) {
				$process1 = new Process();
				$process1->setAddDateTime( new \DateTime() );
				$process1->setEvent('PDF');
				$process1->setType('awarded');
				$process1->setOpenEnrollment( $openEnrollment );
				$this->getDoctrine()->getManager()->persist( $process1 );

				$process2 = new Process();
				$process2->setAddDateTime( new \DateTime() );
				$process2->setEvent('PDF');
				$process2->setType('wait-list');
				$process2->setOpenEnrollment( $openEnrollment );
				$this->getDoctrine()->getManager()->persist( $process2 );

				$process4 = new Process();
				$process4->setAddDateTime( new \DateTime() );
				$process4->setEvent( 'PDF' );
				$process4->setType( 'awarded-but-wait-listed' );
				$process4->setOpenEnrollment( $openEnrollment );
				$this->getDoctrine()->getManager()->persist( $process4 );
			}

			$this->getDoctrine()->getManager()->flush();

			return $this->redirect( $this->generateUrl( 'process_lottery' , array( 'period' => $openEnrollment->getId() ) ) );
		}

		return [ 'admin_pool' => $admin_pool , 'lottery' => $lottery , 'openEnrollment' => $openEnrollment , 'form' => $form->createView() ];
	}

	/**
	 * @Route("/admin/process/form/", name="process_form_select")
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfselect.html.twig")
	 *
	 * @param Request $request
	 * @return array()
	 */
	public function processFormSelectAction( Request $request ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		$forms = $this->getDoctrine()
			->getRepository( 'IIABStudentTransferBundle:Form' )
			->findAll();

		$form_choices = [];
		foreach( $forms as $form ){
			if( $form->getId() != 6 ){
				$form_choices[ $form->getId() ] = $form;
			}
		}

		$form = $this->createFormBuilder( )
			->add( 'selectPeriod' , 'entity' , [
				'data' => new CurrentEnrollmentSettingsForm(),
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ] )
			->add( 'selectForm' , 'entity' , [
				'class' => 'IIABStudentTransferBundle:Form',
				'choices' => $form_choices,
			] )
			->add( 'saveEnrollmentPeriod' , 'submit' , [
				'label' => 'Submit' ,
				'attr' => ['class' => 'btn btn-primary' ] ] )
			->getForm()
			->handleRequest( $request );

		$source = 'process_form';

		if ( $form->isValid( ) ) {
			$data = $form->getData();

			if ( $data['selectPeriod'] ) {

				return $this->redirect(
					$this->generateUrl(
						$source,
						[
							'period' => $data['selectPeriod']->getId(),
							'form' => $data ['selectForm']->getId(),
						]
					)
				);
			}
		}
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) ];
	}


	/**
	 * @Route("/admin/process/form/{period}/{form}/", name="process_form")
	 * @Template("@IIABStudentTransfer/Lottery/process_form.html.twig")
	 *
	 * @param integer $period
	 * @param integer $form
	 * @param Request $request
	 * @return array()
	 * @throws \Exception
	 */
	public function processFormAction( Request $request , $period, $form ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );

		$selected_form = $this->getDoctrine()
			->getRepository( 'IIABStudentTransferBundle:Form' )
			->find($form);

		$selected_period = $this->getDoctrine()
			->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )
			->find($period);

		$active = $this->getDoctrine()
			->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
			->find(1);

		$submissions = $this->getDoctrine()
			->getRepository( 'IIABStudentTransferBundle:Submission' )
			->findBy([
				'formID' => $selected_form,
				'enrollmentPeriod' => $selected_period,
				'submissionStatus' => $active,
			]);

		$form = $this->createFormBuilder( )
			->add( 'processForm' , 'submit' , [
				'label' => 'Award All Submissions' ,
				'attr' => ['class' => 'btn btn-primary' ] ] )
			->getForm()
			->handleRequest( $request );

		if ( $form->isValid( ) ) {

			foreach( $submissions as $submissionObject ){

				$status = $this->getDoctrine()
					->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
					->find( 2 );

				$url = $submissionObject->getId() . '.' . rand( 10 , 999 );
				$submissionObject->setUrl( $url );
				$submissionObject->setAwardedSchoolID( $submissionObject->getFirstChoice() );
				$submissionObject->setRoundExpires( 3 );
				$submissionObject->setmanualAwardDate( new \DateTime() );
				$submissionObject->setSubmissionStatus( $status );

				//Used to run the reporting so we can build the specific URL.
				$reporting = new SlottingReport();
				$reporting->setEnrollmentPeriod( $submissionObject->getEnrollmentPeriod() );
				$reporting->setRound( 3 );
				$reporting->setSchoolID( $submissionObject->getFirstChoice() );
				$reporting->setStatus( $status );
				$reporting->setCurrentSchool( $submissionObject->getCurrentSchool() );

				$currentEnrollmentChanged = $this->getDoctrine()
					->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )
					->findOneBy( array(
						'enrollmentPeriod' => $selected_period ,
						'groupId' => $submissionObject->getAwardedSchoolID()->getGroupID()
					), array( 'addedDateTime' => 'DESC' ) );

				if( $currentEnrollmentChanged != null ) {
					$race = strtolower( $submissionObject->getRace() );
					switch( $race ) {

					case 'black':
					case 'black/african american';
						$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() + 1 );
						break;

					case 'white':
						$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() + 1 );
						break;

					default:
						$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() + 1 );
						break;
					}
				}

				$auditCode = $this->getDoctrine()->getManager()
					->getRepository( 'IIABStudentTransferBundle:AuditCode' )
					->find( 2 );

				$audit = new Audit();
				$audit->setAuditCodeID( $auditCode );
				$audit->setIpaddress( '::1' );
				$audit->setSubmissionID( $submissionObject->getId() );
				$audit->setStudentID( $submissionObject->getStudentId() );
				$audit->setTimestamp( new \DateTime() );
				$audit->setUserID( 0 );

				$waitListed = $submissionObject->getWaitList();
				foreach( $waitListed as $waitList ) {
					$submissionObject->removeWaitList( $waitList );
					$this->getDoctrine()->getManager()->remove( $waitList );
				}

				$this->getDoctrine()->getManager()->persist( $audit );
				$this->getDoctrine()->getManager()->persist( $reporting );
				$this->getDoctrine()->getManager()->persist( $submissionObject );
				$this->getDoctrine()->getManager()->persist( $currentEnrollmentChanged );
				$this->getDoctrine()->getManager()->flush();
			}

			return $this->redirect(
					$this->generateUrl(
						'process_form_select'
					)
				);

		}

		return [
			'admin_pool' => $admin_pool ,
			'form' => $form->createView( ),
			'openEnrollment' => $selected_period,
			'selected_form' => $selected_form,
			'submissions' => $submissions,
		];
	}


	/**
	 * @Route("/admin/lottery/dashboard/{period}/", name="lottery_dashboard")
	 * @Template("@IIABStudentTransfer/Lottery/dashboard.html.twig")
	 *
	 * @param Request $request
	 * @param         $period
	 * @return array()
	 *
	 */
	public function lotteryDashboardAction( Request $request , $period ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );

		$openEnrollment = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->find( $period );

		if( $openEnrollment == null ) {
			return $this->redirect( $this->generateUrl( 'lottery_dashboard_select' ) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lottery */
		$lottery = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneByEnrollmentPeriod( $openEnrollment );

		$getGroups = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:SchoolGroup')->getEnrollmentSchools( $openEnrollment );

		//Get all the school Groups and latest Current Enrollment DATA.
		$groupArray = [];
		$currentEnrollmentDateTime = new \DateTime();
		/** @var \IIAB\StudentTransferBundle\Entity\SchoolGroup $group */
		foreach ( $getGroups as $group ) {

			$groupKey = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findOneBy( [ 'groupId' => $group , 'enrollmentPeriod' => $openEnrollment ] , [ 'addedDateTime' => 'DESC' ] );

			if ( $groupKey == null ) {
				$groupKey = new CurrentEnrollmentSettings();
				$groupKey->setGroupId( $group );
				$groupKey->setAddedDateTime( $currentEnrollmentDateTime );
				$groupKey->setEnrollmentPeriod( $openEnrollment );

				$this->getDoctrine()->getManager()->persist( $groupKey );
				$this->getDoctrine()->getManager()->flush();
			}
			if( $groupKey->getMaxCapacity() > 0 ) {
				$groupArray[$group->getId()] = $groupKey;
			}
		}

		//Get all the Current Wait Listed People for each Groups.
		$waitListArray = [];
		/** @var \IIAB\StudentTransferBundle\Entity\SchoolGroup $group */
		foreach ( $getGroups as $group ) {

			$waitListed = 0;
			$waitListed = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:WaitList')->findBySchoolGroup( $group , $openEnrollment );
			$waitListed = count( $waitListed );

			$waitListArray[$group->getId()] = $waitListed;
			$waitListed = null;

		}

		return [ 'admin_pool' => $admin_pool , 'lottery' => $lottery , 'openEnrollment' => $openEnrollment , 'currentEnrollment' => $groupArray , 'waitList' => $waitListArray ];
	}

	/**
	 * @Route("/admin/lottery/dashboard/", name="lottery_dashboard_select")
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfselect.html.twig")
	 *
	 * @return array()
	 */
	public function lotteryDashboardSelectAction() {

		$admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		$form = $this->createFormBuilder( )
			->add( 'selectPeriod' , 'entity' , [
				'data' => new CurrentEnrollmentSettingsForm(),
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ] )
			->add( 'saveEnrollmentPeriod' , 'submit' , [
				'label' => 'Submit' ,
				'attr' => ['class' => 'btn btn-primary' ] ] )
			->getForm()
			->handleRequest( $request );

		$source = 'lottery_dashboard';

		if ( $form->isValid( ) ) {
			$data = $form->getData();
			if ( $data['selectPeriod'] ) {
				return $this->redirect( $this->generateUrl( $source , array( 'period' => $data['selectPeriod']->getId() ) ) );
			}
		}
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) ];
	}

	/**
	 * @Route("/admin/process/lottery/", name="process_lottery_select")
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfselect.html.twig")
	 *
	 * @param Request $request
	 * @return array()
	 */
	public function processLotterySelectAction( Request $request ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		$form = $this->createFormBuilder( )
			->add( 'selectPeriod' , 'entity' , [
				'data' => new CurrentEnrollmentSettingsForm(),
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ] )
			->add( 'saveEnrollmentPeriod' , 'submit' , [
				'label' => 'Submit' ,
				'attr' => ['class' => 'btn btn-primary' ] ] )
			->getForm()
			->handleRequest( $request );

		$source = 'process_lottery';

		if ( $form->isValid( ) ) {
			$data = $form->getData();

			if ( $data['selectPeriod'] ) {

				$lottery = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneByEnrollmentPeriod( $data['selectPeriod'] );
				if( $lottery == null ){
					$form->get('selectPeriod')->addError(new FormError('No Lottery exists for this Period.  Please create a lottery and try again.'));
				} else {
					return $this->redirect( $this->generateUrl( $source , array( 'period' => $data['selectPeriod']->getId() ) ) );
				}
			}
		}
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) ];
	}


	/**
	 * @Route("/admin/process/late-lottery/", name="process_late_lottery_select")
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfselect.html.twig")
	 *
	 * @param Request $request
	 * @return array()
	 */
	public function processLateLotterySelectAction( Request $request ){

		$admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		$form = $this->createFormBuilder( )
			->add( 'selectPeriod' , 'entity' , [
				'data' => new CurrentEnrollmentSettingsForm(),
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ] )
			->add( 'saveEnrollmentPeriod' , 'submit' , [
				'label' => 'Submit' ,
				'attr' => ['class' => 'btn btn-primary' ] ] )
			->getForm()
			->handleRequest( $request );

		$source = 'process_late_lottery';

		if ( $form->isValid( ) ) {
			$data = $form->getData();

			if ( $data['selectPeriod'] ) {
				$lottery = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneByEnrollmentPeriod( $data['selectPeriod'] );
				if( $lottery == null || !in_array( $lottery->getLotteryStatus()->getId(), [ 3,4 ] ) ) {
					$form->get('selectPeriod')->addError(new FormError('The Lottery for this Period has not completed.  Please complete the lottery processing and try again.'));
				} else {
					return $this->redirect( $this->generateUrl( $source , array( 'period' => $data['selectPeriod']->getId() ) ) );
				}
			}
		}
		return [ 'admin_pool' => $admin_pool, 'form' => $form->createView( ) ];
	}


	/**
	 * @Route("/admin/process/late-submissions/{period}/", name="process_late_lottery")
	 * @Template("@IIABStudentTransfer/Lottery/processLateLottery.html.twig")
	 *
	 * @param integer $period
	 * @param Request $request
	 * @return array()
	 * @throws \Exception
	 */
	public function processLateLottery( Request $request , $period ) {
		$admin_pool = $this->get( 'sonata.admin.pool' );
		$openEnrollment = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->find( $period );

		if( $openEnrollment == null ) {
			return $this->redirect( $this->generateUrl( 'process_late_lottery_select' ) );
		}

		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lottery */
		$lottery = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Lottery' )->findOneByEnrollmentPeriod( $openEnrollment );
		if( $lottery == null || !in_array( $lottery->getLotteryStatus()->getId(), [ 3, 4 ] ) ) {
			return $this->redirect( $this->generateUrl( 'process_late_lottery_select' ) );
		}

		$submissionCount = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')
			->createQueryBuilder('s')

			->select( 'SUM( CASE WHEN (s.submissionStatus = 1 AND s.formID = 1) THEN 1 ELSE 0 END ) AS active')
			->addSelect( 'SUM( CASE WHEN ( (s.submissionStatus = 7 OR s.submissionStatus = 10) AND s.formID = 1) THEN 1 ELSE 0 END ) AS waitlist')
			->addSelect( 'SUM( CASE WHEN (s.submissionStatus = 2 AND s.formID = 1) THEN 1 ELSE 0 END ) AS m2m_offered')
			->addSelect( 'SUM( CASE WHEN (s.submissionStatus = 11 AND s.formID = 1) THEN 1 ELSE 0 END ) AS m2m_offered_and_waitlist')
			//todo uncomment when adding "Process Personnel & SPED" buttons
			//->addSelect( 'SUM( CASE WHEN (s.submissionStatus = 1 AND s.formID = 2) THEN 1 ELSE 0 END ) AS personnel')
			//->addSelect( 'SUM( CASE WHEN (s.submissionStatus = 1 AND s.formID = 3) THEN 1 ELSE 0 END ) AS sped')
			->where('s.enrollmentPeriod = :enrollment')
			->andWhere('s.submissionStatus IN (:submissionStatus)')
			->andWhere('s.formID IN (:form_type)')
			->setParameters( array(
				'enrollment'		=> $openEnrollment->getId(),
				'submissionStatus'	=> [ 1, 2, 7, 10, 11 ], // Only Active (1), Offered (2), Offered and Waitlisted (11) Non-Awarded (7) AND Wait Listed (10)
				'form_type'			=> [ 1, 2, 3 ], // M2M, Personnel Transfer and Special Education Sibling Transfer
			) )
			->getQuery()
			->getResult()[0];

		$current_date = new \dateTime();
		$form = $this->createFormBuilder()
			->add( 'processLateLottery' , 'submit' , [
				'label' => 'Process Late Lottery' ,
				'attr' => ['class' => 'btn btn-primary' ] ] )
			/*
			->add( 'mailDate' , 'date' , [
				'label' => 'Mail Date' ,
				'attr' => [ 'class' => 'sonata-medium-date' ] ,
				'data' => $current_date ] )
			->add( 'offlineCloseTime' , 'time' , [
				'label' => 'Offline Acceptance Close Time' ,
				'data' => $lottery->getOfflineCloseTime() ,
				'attr' => [ 'class' => 'sonata-medium-date' ] ] )
			*/
			->add( 'processPDF' , 'submit' , [
				'label' => 'Generate PDFs Letters' ,
				'attr' => [ 'class' => 'btn btn-info' ] ] );

		$form = $form->getForm()->handleRequest( $request );

		if( $form->isValid() ) {

			$data = $form->getData();
			$em = $this->getDoctrine()->getManager();

			if ($form->get('processLateLottery')->isClicked()) {

				$lotteryStatus = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:LotteryStatus')->find(1);

				$lottery_log = new LotteryLog();
				$lottery_log->setTimeStamp( new \DateTime() );
				$lottery_log->setEnrollmentPeriod( $openEnrollment );
				$lottery_log->setLotteryStatus( $lotteryStatus );
				$em->persist( $lottery_log );

				$process = new Process();
				$process->setAddDateTime(new \DateTime());
				$process->setEvent('lottery');
				$process->setType('late-lottery');
				$process->setOpenEnrollment($lottery->getEnrollmentPeriod());

				$em->persist( $process );
			}
			if ($form->get('processPDF')->isClicked()) {
				$process1 = new Process();
				$process1->setAddDateTime(new \DateTime());
				$process1->setEvent('PDF');
				$process1->setType('awarded');
				$process1->setOpenEnrollment($openEnrollment);
				$em->persist( $process1 );
			}

			$em->flush();
		}

		return [
			'admin_pool' => $admin_pool,
			'openEnrollment' => $openEnrollment,
			'lottery' => $lottery,
			'submissionCount' => $submissionCount,
			'process' => ( $lottery->getLotteryStatus()->getId() == 4 ),
			'form' => $form->createView( )
		];

	}

	/**
	 * Display the Wait List submission
	 *
	 * @param Request $request
	 *
	 * @Route( "/waitlist" , name="stw_choice_waitlist")
	 * @Template("IIABStudentTransferBundle:Lottery:waitList.html.twig")
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function waitListAction( Request $request ) {

		/**
		 * Always clear the formData in the session on the index page. Making sure to have clean session.
		 */

		/** @var \IIAB\StudentTransferBundle\Entity\Submission $submission */
		$submissionID = $request->getSession()->get( 'submissionID' );

		$submission = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->find( $submissionID );
		$request->getSession()->clear();

		$waitList = '';
		foreach( $submission->getWaitList() as $waitListed ) {
			$waitList = $waitListed->getChoiceSchool()->getSchoolName();
		}

		//Get the openEnrollmentPeriod and see if it is less than 72 hours before it closes.

		return array( 'waitList' => $waitList );
	}

	/**
	 * Display the Declined submission
	 *
	 * @param Request $request
	 *
	 * @Route( "/declined" , name="stw_declined")
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function declinedAction( Request $request ) {

		/**
		 * Always clear the formData in the session on the index page. Making sure to have clean session.
		 */
		$lastFormData = $request->getSession()->has( 'submissionID' );

		$submission = new Submission();
		if( $lastFormData !== false ) {
			$submissionID = $request->getSession()->get('submissionID');
			$submission = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Submission')->find( $submissionID );
			//empty all sessions data to keep data secure.
			$request->getSession()->remove( 'submissionID' );
		}

		//Get the openEnrollmentPeriod and see if it is less than 72 hours before it closes.

		return $this->render( 'IIABStudentTransferBundle:Lottery:declined.html.twig' , array( 'submission' => $submission ) );
	}

	/**
	 * @param int $auditCode
	 * @param int $studentID
	 * @param int $submission
	 */
	private function recordAudit( $auditCode = 0 , $submission = 0 , $studentID = 0 ) {
		$em = $this->getDoctrine()->getManager();

		$user = $this->getUser();
		//$user = $this->get( 'security.context' )->getToken()->getUser();

		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

		$request = $this->get('request_stack')->getCurrentRequest();

		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( $request->getClientIp() );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( $studentID );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( ( $user == 'anon.' || $user == null ) ? 0 : $user->getId() );
		$em->persist( $audit );
		$em->flush();
		$em->clear();
	}

	/**
	 * @Template("IIABStudentTransferBundle:Lottery:status.html.twig")
	 *
	 * @return array
	 */
	public function processingStatusAction() {

		$processes = $this->getDoctrine()->getManager()->getRepository( 'IIABStudentTransferBundle:Process' )->findlastFiveMinuteProcess();

		return array( 'processes' => $processes );
	}

	/**
	 * @Route("ajax/process/update.json", name="iiab_magnet_program_process_updater")
	 *
	 * @return array
	 */
	public function processStatusAction() {

		$request = $this->get('request_stack')->getCurrentRequest();
		$id = $request->get('id' , 0 );
		if( $id == 0 || empty( $id ) ) {
			return array();
		}

		$process = $this->getDoctrine()->getManager()->getRepository( 'IIABStudentTransferBundle:Process' )->find( $id );

		if( $process == null ) {
			return array();
		}

		$message = '<span aria-hidden="true" class="glyphicon glyphicon-ok"></span> <strong>' . ucwords( $process->getEvent() ) . ' ' . ucwords( $process->getType() ) . '</strong>';
		if( $process->getRunning() == 1 ) {
			$message .= ' is currently running. Please wait.';
		} elseif( $process->getCompleted() == 1 ) {
			$message .= ' this task has completed at ' . $process->getCompletedDateTime()->format( 'm/d/y h:ia' ) . sprintf( '. <a style="text-decoration: underline;" onclick="location.reload(); return false;" href="#">%s</a>.' , 'Click here to reload this window' );
		} else {
			$message .= ' is currently running. Please wait..';
		}

		$responseProcess = array(
			'completed' => $process->getCompleted() ,
			'message' => $message ,
		);

		return new JsonResponse( $responseProcess );
	}
}