<?php

namespace IIAB\StudentTransferBundle\Controller;

use Doctrine\ORM\Query\Expr\OrderBy;
use IIAB\StudentTransferBundle\Entity\Student;
use IIAB\StudentTransferBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sonata\AdminBundle\Controller\CRUDController;
use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Entity\SubmissionData;
use IIAB\StudentTransferBundle\Command\CheckMinorityCommand;
use IIAB\StudentTransferBundle\Command\GetAvailableSchoolCommand;
use Symfony\Component\HttpFoundation\Response;
use IIAB\StudentTransferBundle\Lottery\Lottery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class SubmissionCRUDController extends CRUDController {

	public function customeditAction($id = null)
	{
		// the key used to lookup the template
		$templateKey = 'edit';

		$id = $this->get('request_stack')->getCurrentRequest()->get($this->admin->getIdParameter());
		$object = $this->admin->getObject($id);

		if (!$object) {
			throw new NotFoundHttpException(sprintf('unable to find the object with id : %s', $id));
		}

		if (false === $this->admin->isGranted('EDIT', $object)) {
			throw new AccessDeniedException();
		}

		$this->admin->setSubject($object);

		/** @var $form \Symfony\Component\Form\Form */
		$form = $this->admin->getForm();
		$form->setData($object);

		if ($this->getRestMethod() == 'POST') {
			$form->submit($this->get('request_stack')->getCurrentRequest());

			$isFormValid = $form->isValid();

			// persist if the form was valid and if in preview mode the preview was approved
			if ($isFormValid && (!$this->isInPreviewMode() || $this->isPreviewApproved())) {
				$this->admin->update($object);

				$newStatus = $object->getSubmissionStatus()->getId();
				switch( $newStatus ) :
					case '1': //Active
					default:
						$this->recordAudit( 1 , $object->getId() , $object->getStudentID() );
						break;
					case '2': //Offered
						$this->recordAudit( 2 , $object->getId() , $object->getStudentID() );
						break;
					case '3': //Offered and Accepted
						$this->recordAudit( 4 , $object->getId() , $object->getStudentID() );
						break;
					case '4': //Offered and Declined
						$this->recordAudit( 5 , $object->getId() , $object->getStudentID() );
						break;
					case '5': //Denied due to Space
						$this->recordAudit( 6 , $object->getId() , $object->getStudentID() );
						break;
					case '6': //overwritten
						$this->recordAudit( 27 , $object->getId() , $object->getStudentID() );
						break;
					case '7': //non-awarded
						$this->recordAudit( 3 , $object->getId() , $object->getStudentID() );
						break;
					case '8': //inactive
						$this->recordAudit( 30 , $object->getId() , $object->getStudentID() );
						break;
				endswitch;

				if ($this->isXmlHttpRequest()) {
					return $this->renderJson(array(
						'result'    => 'ok',
						'objectId'  => $this->admin->getNormalizedIdentifier($object)
					));
				}

				$this->addFlash('sonata_flash_success', $this->admin->trans('flash_edit_success', array('%name%' => $this->admin->toString($object)), 'SonataAdminBundle'));

				// redirect to edit mode

				return $this->redirectTo($object);
			}

			// show an error message if the form failed validation
			if (!$isFormValid) {
				if (!$this->isXmlHttpRequest()) {
					$this->addFlash('sonata_flash_error', $this->admin->trans('flash_edit_error', array('%name%' => $this->admin->toString($object)), 'SonataAdminBundle'));
				}
			} elseif ($this->isPreviewRequested()) {
				// enable the preview template if the form was valid and preview was requested
				$templateKey = 'preview';
				$this->admin->getShow();
			}
		}

		$view = $form->createView();

		// set the theme for the current Admin Form
		$this->get('twig')->getExtension('form')->renderer->setTheme($view, $this->admin->getFormTheme());

		return $this->render($this->admin->getTemplate($templateKey), array(
			'action' => 'edit',
			'form'   => $view,
			'object' => $object,
		));
	}

	public function createAction() {

		//code here
		// the key used to lookup the template
		$templateKey = 'edit';

		if( false === $this->admin->isGranted( 'CREATE' ) ) {
			throw new AccessDeniedException();
		}

		/** @var \IIAB\StudentTransferBundle\Entity\Submission $object */
		$object = $this->admin->getNewInstance();

		$this->admin->setSubject( $object );

		/** @var $form \Symfony\Component\Form\Form */
		$form = $this->admin->getForm();
		$form->setData( $object );
		$request = $this->get('request_stack')->getCurrentRequest();
		//$form->handleRequest( $request );

		if( $this->getRestMethod() == 'POST' ) {
		 	$form->handleRequest( $request );

			$isFormValid = $form->isValid();

			$uniqID = ( isset( $_REQUEST['uniqid'] ) ? $_REQUEST['uniqid'] : '' );

			if( !empty( $uniqID ) ) {
				$formData = $_REQUEST[$uniqID];

				if( $formData['enrolled'] == 0 ) {
					//This means the student is a new the HSV City.
					// && !empty($formData['race'])
					if( !empty($formData['first_name']) && !empty($formData['last_name']) && !empty($formData['street']) && !empty($formData['city']) && !empty($formData['zip']) && !empty($formData['dob']) && !empty($formData['grade']) ) {
						$isFormValid = true;

						$race = $this->getDoctrine()->getManager()
							->getRepository( 'IIABStudentTransferBundle:Race' )
							->find($formData['race']);

						$form = $this->getDoctrine()->getManager()
							->getRepository( 'IIABStudentTransferBundle:Form' )
							->find($formData['formID']);
						$now = new \DateTime();

						//$object->setStudentID( 'TS' . strtotime( 'now' ) );
						$object->setLastName( $formData['last_name'] );
						$object->setFirstName( $formData['first_name'] );
						$object->setDob( $formData['dob']['month'] . '/' . $formData['dob']['day'] . '/' . $formData['dob']['year'] );
						$object->setAddress( $formData['street'] );
						$object->setCity( $formData['city'] );
						$object->setZip( $formData['zip'] );
						$object->setGrade( $formData['grade'] );
						$object->setRace( $race );
						$object->setCurrentSchool( $formData['currentSchool'] );
						$object->setFormID( $form );
						$object->setSubmissionDateTime( $now );
					}
					$object->setAfterLotterySubmission( $formData['afterLottery_new'] );
				} else {
					$object->setAfterLotterySubmission( $formData['afterLottery'] );
				}

				if( $formData['enrolled'] == 1 ) {
					//This means the student should be within the HSV City.
					//Don't change any form validation.
				}
			}

			// persist if the form was valid and if in preview mode the preview was approved
			if( $isFormValid && ( !$this->isInPreviewMode() || $this->isPreviewApproved() ) ) {

				if( false === $this->admin->isGranted( 'CREATE' , $object ) ) {
					throw new AccessDeniedException();
				}

				$createAndDeactivateButton = $this->get('request_stack')->getCurrentRequest()->get('btn_create_and_deactivate');

				if( $object->getFormID()->getRoute() == 'stw_m2m' ) {
					return $this->handleAdminM2MTransfer( $object , $createAndDeactivateButton );
				}

				if( $object->getFormID()->getRoute() == ''
					|| $object->getFormID()->getRoute() == 'stw_accountability'
					|| $object->getFormID()->getRoute() == 'stw_senior'
					|| $object->getFormID()->getRoute() == 'stw_iep'
					|| $object->getFormID()->getRoute() == 'stw_sct'
					|| $object->getFormID()->getRoute() == 'stw_success_prep'
				) {
					return $this->handleAdminOtherTransfer( $object , $createAndDeactivateButton );
				}

				if( $object->getFormID()->getRoute() == 'stw_personnel' ) {
					return $this->handleAdminPersonnelTransfer( $object , $createAndDeactivateButton );
				}

				if( $object->getFormID()->getRoute() == 'stw_sped' ) {
					return $this->handleAdminSPEDTransfer( $object , $createAndDeactivateButton );
				}

				if( $object->getFormID()->getRoute() == 'stw_other' ) {
					return $this->handleAdminOtherTransfer( $object , $createAndDeactivateButton );
				}

				/*
				$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.notMajority' , array() , 'IIABStudentTransferBundle' ) );

				return $this->render( $this->admin->getTemplate( $templateKey ) , array(
					'action' => 'create' ,
					'form' => $form->createView() ,
					'object' => $object ,
				) );
				*/

				if( $this->isXmlHttpRequest() ) {
					return $this->renderJson( array(
						'result' => 'ok' ,
						'objectId' => $this->admin->getNormalizedIdentifier( $object )
					) );
				}

				$this->addFlash( 'sonata_flash_success' , $this->admin->trans( 'flash_create_success' , array( '%name%' => $this->admin->toString( $object ) ) , 'SonataAdminBundle' ) );

				// redirect to edit mode
				return $this->redirectTo( $object );
			}

			// show an error message if the form failed validation
			if( !$isFormValid ) {
				if( !$this->isXmlHttpRequest() ) {
					$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'flash_create_error' , array( '%name%' => $this->admin->toString( $object ) ) , 'SonataAdminBundle' ) );
				}
			} elseif( $this->isPreviewRequested() ) {
				// pick the preview template if the form was valid and preview was requested
				$templateKey = 'preview';
				$this->admin->getShow();
			}
		}

		$view = $form->createView();

		// set the theme for the current Admin Form
		$this->get( 'twig' )->getExtension( 'form' )->renderer->setTheme( $view , $this->admin->getFormTheme() );

		return $this->render( $this->admin->getTemplate( $templateKey ) , array(
			'action' => 'create' ,
			'form' => $view ,
			'object' => $object ,
		) );
	}

	private function handleAdminPersonnelTransfer( Submission $object , $createAndDeactivateButton ) {

		$em = $this->getDoctrine()->getManager();

		$lotteryNumber = new Lottery();
		$lotteryNumber = $lotteryNumber->getLotteryNumber( $this->getDoctrine() );

		$submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
			->find( 1 );

		if( $object->getFirstName() == '' ) {
			$student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $object->getStudentID() ) );

			if( $student == null ) {
				$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
				// redirect to edit mode
				return $this->redirectTo( $object );
			}

			/*
			 * TODO: Check to see if the student ID has already been awarded a slot and used it.
			 * Before allowing the new submissions.
			 */
			$submission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
				'studentID' => $student->getStudentID(),
				'submissionStatus' => $submissionsStatus,
				'enrollmentPeriod' => $object->getEnrollmentPeriod(),
			) );

		} else {
			$student = new Student();
			$student->setFirstName( $object->getFirstName() );
			$student->setLastName( $object->getLastName() );
			$student->setDob( $object->getDob() );
			$student->setAddress( $object->getAddress() );
			$student->setCity( $object->getCity() );
			$student->setZip( $object->getZip() );
			$student->setGrade( $object->getGrade() );
			$student->setRace( $object->getRace() );
			$student->setStudentID( $object->getStudentID() );
			$student->setSchool( $object->getCurrentSchool( false ) );
			$submission = null;
		}

		//If admin selected Created and Deactivate
		if( null !== $createAndDeactivateButton && $submission != null ) {
			//Admin user selected, create and deactivate old submission.
			if( $submission->getEnrollmentPeriod() == $object->getEnrollmentPeriod() ) {
				//It is the same openEnrollment Period and the submission is active. Need to change it!

				$submissionsDeactivate = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
					->find( 6 );

				$submission->setSubmissionStatus( $submissionsDeactivate );
				$em->persist( $submission );

				$this->recordAudit( 27 , $submission->getId() , $object->getStudentID() );
				$submission = null;
			}
		}

		if( $student != 'newStudent' ) {
			$getSchoolsCommand = new GetAvailableSchoolCommand();
			$getSchoolsCommand->setContainer( $this->container );
			$schools = $getSchoolsCommand->getAvailableSchools( $student , true , $object->getEnrollmentPeriod()->getId() );

			if( $schools == false) {
				return $this->redirectTo( $object );
			}

			$zonedSchools = $this->container->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );
			$object->setHsvZonedSchools( $zonedSchools );
			$getSchools = null;
		} else {
			$object->setHsvZonedSchools( array() );
		}

		if( $submission == null ) {

			$submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
				->find( 1 );

			$firstChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $object->getFirstChoice()->getId() );
			if( $object->getSecondChoice() != null ) {
				$secondChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $object->getSecondChoice()->getId() );
			} else {
				$secondChoice = null;
			}
			$form = $em->getRepository( 'IIABStudentTransferBundle:Form' )->find( $object->getFormID()->getId() );
			$enrollmentPeriod = $em->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->find( $object->getEnrollmentPeriod()->getId() );

			$object->setFirstChoice( $firstChoice );
			if( $secondChoice != null ) {
				$object->setSecondChoice( $secondChoice );
			}
			$object->setFormID( $form );
			$object->setEnrollmentPeriod( $enrollmentPeriod );

			$object->setSubmissionDateTime( new \DateTime() );
			$object->setLotteryNumber( $lotteryNumber );
			if( $student != 'newStudent' ) {

				$race = $this->getDoctrine()->getManager()
					->getRepository( 'IIABStudentTransferBundle:Race' )
					->find($student->getRace());

				$object->setLastName( $student->getLastName() );
				$object->setFirstName( $student->getFirstName() );
				$object->setDob( $student->getDob() );
				$object->setAddress( $student->getAddress() );
				$object->setCity( $student->getCity() );
				$object->setZip( $student->getZip() );
				$object->setGrade( $student->getGrade() );
				$object->setRace( $race );
				$object->setCurrentSchool( $student->getSchool() );
			}
			$object->setSubmissionStatus( $submissionsStatus );

			$this->admin->create( $object );

			$confirmNumber = sprintf( '%s-%s-%d' , $object->getFormID()->getFormConfirmation() , $object->getEnrollmentPeriod()->getConfirmationStyle() , $object->getId() );
			$object->setConfirmationNumber( $confirmNumber );
			$this->admin->update( $object );

			$this->recordAudit( 1 , $object->getId() , $object->getStudentID() );

			if( $this->isXmlHttpRequest() ) {
				return $this->renderJson( array(
					'result' => 'ok' ,
					'objectId' => $this->admin->getNormalizedIdentifier( $object )
				) );
			}

			$this->addFlash( 'sonata_flash_success' , $this->admin->trans( 'flash_create_success' , array( '%name%' => $object->getConfirmationNumber() ) , 'SonataAdminBundle' ) );

			// redirect to edit mode
			if( null !== $createAndDeactivateButton)
				return new RedirectResponse( $this->admin->generateUrl('list') );

			return $this->redirectTo( $object );

		}

		$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.alreadySubmitted' , array( '%name%' => $object->getConfirmationNumber() ) , 'IIABStudentTransferBundle' ) );

		return new RedirectResponse( $this->admin->generateUrl('create') );

		//return $this->redirectTo( $object );

	}

	private function handleAdminSPEDTransfer( Submission $object , $createAndDeactivateButton ) {

		$uniqID = ( isset( $_REQUEST['uniqid'] ) ? $_REQUEST['uniqid'] : '' );
		$formData = $_REQUEST[$uniqID];

		$em = $this->getDoctrine()->getManager();

		$lotteryNumber = new Lottery();
		$lotteryNumber = $lotteryNumber->getLotteryNumber( $this->getDoctrine() );

		$submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
			->find( 1 );

		if( $object->getFirstName() == '' ) {
			$student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $object->getStudentID() ) );

			if( $student == null ) {
				$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
				// redirect to edit mode
				return $this->redirectTo( $object );
			}

			/*
			 * TODO: Check to see if the student ID has already been awarded a slot and used it.
			 * Before allowing the new submissions.
			 */
			$submission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
				'studentID' => $student->getStudentID(),
				'submissionStatus' => $submissionsStatus,
				'enrollmentPeriod' => $object->getEnrollmentPeriod(),
			) );

		} else {
			$student = new Student();
			$student->setFirstName( $object->getFirstName() );
			$student->setLastName( $object->getLastName() );
			$student->setDob( $object->getDob() );
			$student->setAddress( $object->getAddress() );
			$student->setCity( $object->getCity() );
			$student->setZip( $object->getZip() );
			$student->setGrade( $object->getGrade() );
			$student->setRace( $object->getRace() );
			$student->setStudentID( $object->getStudentID() );
			$student->setSchool( $object->getCurrentSchool( false ) );
			$submission = null;
		}

		//If admin selected Created and Deactivate
		if( null !== $createAndDeactivateButton && $submission != null ) {
			//Admin user selected, create and deactivate old submission.
			if( $submission->getEnrollmentPeriod() == $object->getEnrollmentPeriod() ) {
				//It is the same openEnrollment Period and the submission is active. Need to change it!

				$submissionsDeactivate = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
					->find( 6 );

				$submission->setSubmissionStatus( $submissionsDeactivate );
				$em->persist( $submission );

				$this->recordAudit( 27 , $submission->getId() , $object->getStudentID() );
				$submission = null;
			}
		}

		if( $student != 'newStudent' ) {
			$getSchoolsCommand = new GetAvailableSchoolCommand();
			$getSchoolsCommand->setContainer( $this->container );
			$schools = $getSchoolsCommand->getAvailableSchools( $student , true , $object->getEnrollmentPeriod()->getId() );

			if( $schools == false) {
				return $this->redirectTo( $object );
			}

			$zonedSchools = $this->container->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );
			$object->setHsvZonedSchools( $zonedSchools );
			$getSchools = null;
		} else {
			$object->setHsvZonedSchools( array() );
		}

		if( $submission == null ) {

			$submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
				->find( 1 );

			$firstChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $object->getFirstChoice()->getId() );
			if( $object->getSecondChoice() != null ) {
				$secondChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $object->getSecondChoice()->getId() );
			} else {
				$secondChoice = null;
			}
			$form = $em->getRepository( 'IIABStudentTransferBundle:Form' )->find( $object->getFormID()->getId() );
			$enrollmentPeriod = $em->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->find( $object->getEnrollmentPeriod()->getId() );

			$object->setFirstChoice( $firstChoice );
			if( $secondChoice != null ) {
				$object->setSecondChoice( $secondChoice );
			}
			$object->setFormID( $form );
			$object->setEnrollmentPeriod( $enrollmentPeriod );

			$object->setSubmissionDateTime( new \DateTime() );
			$object->setLotteryNumber( $lotteryNumber );
			if( $student != 'newStudent' ) {

				$race = $this->getDoctrine()->getManager()
					->getRepository( 'IIABStudentTransferBundle:Race' )
					->find($student->getRace());

				$object->setLastName( $student->getLastName() );
				$object->setFirstName( $student->getFirstName() );
				$object->setDob( $student->getDob() );
				$object->setAddress( $student->getAddress() );
				$object->setCity( $student->getCity() );
				$object->setZip( $student->getZip() );
				$object->setGrade( $student->getGrade() );
				$object->setRace( $race );
				$object->setCurrentSchool( $student->getSchool() );
			}
			$object->setSubmissionStatus( $submissionsStatus );

			$this->admin->create( $object );

			$confirmNumber = sprintf( '%s-%s-%d' , $object->getFormID()->getFormConfirmation() , $object->getEnrollmentPeriod()->getConfirmationStyle() , $object->getId() );
			$object->setConfirmationNumber( $confirmNumber );
			$this->admin->update( $object );

			$this->recordAudit( 1 , $object->getId() , $object->getStudentID() );

			//Add in handling sibling information here like employee for personnel transfers
			//Need to create a new submission to make sure the EM is update to date with the same object.
			$newSubmissionObject = $em->getRepository('IIABStudentTransferBundle:Submission')->find( $object->getId() );

			if( isset( $formData['SPED-Student-ID'] ) && !empty( $formData['SPED-Student-ID'] ) ) {
				$submissionData1 = new SubmissionData();
				$submissionData1->setMetaKey('SPED Student ID');
				$submissionData1->setMetaValue( $formData['SPED-Student-ID'] );
				$submissionData1->setSubmission( $newSubmissionObject );
				$em->persist( $submissionData1 );
			}

			if( isset( $formData['SPED-Current-School']  ) && !empty( $formData['SPED-Current-School']  ) ) {
				$submissionData2 = new SubmissionData();
				$submissionData2->setMetaKey('SPED Current School');
				$submissionData2->setMetaValue( $formData['SPED-Current-School'] );
				$submissionData2->setSubmission( $newSubmissionObject );
				$em->persist( $submissionData2 );
			}

			if( isset( $formData['SPED-Current-Grade'] ) && !empty( $formData['SPED-Current-Grade'] ) ) {
				$submissionData3 = new SubmissionData();
				$submissionData3->setMetaKey('SPED Current Grade');
				$submissionData3->setMetaValue( $formData['SPED-Current-Grade'] );
				$submissionData3->setSubmission( $newSubmissionObject );
				$em->persist( $submissionData3 );
			}

			if( $formData['enrolled'] == 1 )
				$studentStatus = 'HCSStudent';
			else
				$studentStatus = 'nonHCSStudent';

			$submissionData4 = new SubmissionData();
			$submissionData4->setMetaKey('Sibling Status');
			$submissionData4->setMetaValue( $studentStatus );
			$submissionData4->setSubmission( $newSubmissionObject );
			$em->persist( $submissionData4 );

			$em->flush();

			if( $this->isXmlHttpRequest() ) {
				return $this->renderJson( array(
					'result' => 'ok' ,
					'objectId' => $this->admin->getNormalizedIdentifier( $object )
				) );
			}

			$this->addFlash( 'sonata_flash_success' , $this->admin->trans( 'flash_create_success' , array( '%name%' => $object->getConfirmationNumber() ) , 'SonataAdminBundle' ) );

			// redirect to edit mode
			if( null !== $createAndDeactivateButton)
				return new RedirectResponse( $this->admin->generateUrl('list') );

			return $this->redirectTo( $object );

		}

		$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.alreadySubmitted' , array( '%name%' => $object->getConfirmationNumber() ) , 'IIABStudentTransferBundle' ) );

		return new RedirectResponse( $this->admin->generateUrl('create') );

		//return $this->redirectTo( $object );

	}

	private function handleAdminOtherTransfer( Submission $object , $createAndDeactivateButton ) {

		$em = $this->getDoctrine()->getManager();

		$uniqID = ( isset( $_REQUEST['uniqid'] ) ? $_REQUEST['uniqid'] : '' );
		$formData = $_REQUEST[$uniqID];

		$lotteryNumber = new Lottery();
		$lotteryNumber = $lotteryNumber->getLotteryNumber( $this->getDoctrine() );

		$submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
			->find( 1 );

		if( $object->getFirstName() == '' ) {
			$student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $object->getStudentID() ) );

			if( $student == null ) {
				$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
				// redirect to edit mode
				return $this->redirectTo( $object );
			}

			/*
			 * TODO: Check to see if the student ID has already been awarded a slot and used it.
			 * Before allowing the new submissions.
			 */
			$submission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
				'studentID' => $student->getStudentID(),
				'submissionStatus' => $submissionsStatus,
				'enrollmentPeriod' => $object->getEnrollmentPeriod(),
			) );

		} else {
			$student = new Student();
			$student->setFirstName( $object->getFirstName() );
			$student->setLastName( $object->getLastName() );
			$student->setDob( $object->getDob() );
			$student->setAddress( $object->getAddress() );
			$student->setCity( $object->getCity() );
			$student->setZip( $object->getZip() );
			$student->setGrade( $object->getGrade() );
			$student->setRace( $object->getRace() );
			$student->setStudentID( $object->getStudentID() );
			$student->setSchool( $object->getCurrentSchool( false ) );
			$submission = null;
		}

		//If admin selected Created and Deactivate
		if( null !== $createAndDeactivateButton && $submission != null ) {
			//Admin user selected, create and deactivate old submission.
			if( $submission->getEnrollmentPeriod() == $object->getEnrollmentPeriod() ) {
				//It is the same openEnrollment Period and the submission is active. Need to change it!

				$submissionsDeactivate = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
					->find( 6 );

				$submission->setSubmissionStatus( $submissionsDeactivate );
				$em->persist( $submission );

				$this->recordAudit( 27 , $submission->getId() , $object->getStudentID() );
				$submission = null;
			}
		}

		if( $student != 'newStudent' ) {
			$getSchoolsCommand = new GetAvailableSchoolCommand();
			$getSchoolsCommand->setContainer( $this->container );
			$schools = $getSchoolsCommand->getAvailableSchools( $student , true , $object->getEnrollmentPeriod()->getId() );

			if( $schools == false) {
				return $this->redirectTo( $object );
			}

			$zonedSchools = $this->container->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );
			$object->setHsvZonedSchools( $zonedSchools );
			$getSchools = null;
		} else {
			$object->setHsvZonedSchools( array() );
		}

		if( $submission == null ) {

			$submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
				->find( 1 );

			$firstChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $object->getFirstChoice()->getId() );
			if( $object->getSecondChoice() != null ) {
				$secondChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $object->getSecondChoice()->getId() );
			} else {
				$secondChoice = null;
			}
			$form = $em->getRepository( 'IIABStudentTransferBundle:Form' )->find( $object->getFormID()->getId() );
			$enrollmentPeriod = $em->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->find( $object->getEnrollmentPeriod()->getId() );

			$object->setFirstChoice( $firstChoice );
			if( $secondChoice != null ) {
				$object->setSecondChoice( $secondChoice );
			}
			$object->setFormID( $form );
			$object->setEnrollmentPeriod( $enrollmentPeriod );

			$object->setSubmissionDateTime( new \DateTime() );
			$object->setLotteryNumber( $lotteryNumber );
			if( $student != 'newStudent' ) {

				$race = $this->getDoctrine()->getManager()
					->getRepository( 'IIABStudentTransferBundle:Race' )
					->find($student->getRace());

				$object->setLastName( $student->getLastName() );
				$object->setFirstName( $student->getFirstName() );
				$object->setDob( $student->getDob() );
				$object->setAddress( $student->getAddress() );
				$object->setCity( $student->getCity() );
				$object->setZip( $student->getZip() );
				$object->setGrade( $student->getGrade() );
				$object->setRace( $race );
				$object->setCurrentSchool( $student->getSchool() );
			}
			$object->setSubmissionStatus( $submissionsStatus );

			$this->admin->create( $object );

			$confirmNumber = sprintf( '%s-%s-%d' , $object->getFormID()->getFormConfirmation() , $object->getEnrollmentPeriod()->getConfirmationStyle() , $object->getId() );
			$object->setConfirmationNumber( $confirmNumber );
			$this->admin->update( $object );

			$this->recordAudit( 1 , $object->getId() , $object->getStudentID() );

			//Added in the Submission Data for extra details.
			if( isset( $formData['Reason'] ) && !empty( $formData['Reason'] ) ) {
				$newSubmissionObject = $em->getRepository('IIABStudentTransferBundle:Submission')->find( $object->getId() );
				$submissionData1 = new SubmissionData();
				$submissionData1->setMetaKey('Transfer Reason');
				$submissionData1->setMetaValue( $formData['Reason'] );
				$submissionData1->setSubmission( $newSubmissionObject );
				$em->persist( $submissionData1 );
				$em->flush();
			}
			if( isset( $formData['Other-Reason'] ) && !empty( $formData['Other-Reason'] ) ) {
				$newSubmissionObject = $em->getRepository('IIABStudentTransferBundle:Submission')->find( $object->getId() );
				$submissionData2 = new SubmissionData();
				$submissionData2->setMetaKey('Other Reason');
				$submissionData2->setMetaValue( $formData['Other-Reason'] );
				$submissionData2->setSubmission( $newSubmissionObject );
				$em->persist( $submissionData2 );
				$em->flush();
			}

			if( $this->isXmlHttpRequest() ) {
				return $this->renderJson( array(
					'result' => 'ok' ,
					'objectId' => $this->admin->getNormalizedIdentifier( $object )
				) );
			}

			$this->addFlash( 'sonata_flash_success' , $this->admin->trans( 'flash_create_success' , array( '%name%' => $object->getConfirmationNumber() ) , 'SonataAdminBundle' ) );

			// redirect to edit mode
			if( null !== $createAndDeactivateButton)
				return new RedirectResponse( $this->admin->generateUrl('list') );

			return $this->redirectTo( $object );

		}

		$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.alreadySubmitted' , array( '%name%' => $object->getConfirmationNumber() ) , 'IIABStudentTransferBundle' ) );

		return new RedirectResponse( $this->admin->generateUrl('create') );

		//return $this->redirectTo( $object );

	}

	private function handleAdminM2MTransfer( Submission $object , $createAndDeactivateButton ) {

		$em = $this->getDoctrine()->getManager();

		$lotteryNumber = new Lottery();
		$lotteryNumber = $lotteryNumber->getLotteryNumber( $this->getDoctrine() );

		$submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
			->find( 1 );

		if( $object->getFirstName() == '' ) {
			$student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $object->getStudentID() ) );

			if( $student == null ) {
				$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
				// redirect to edit mode
				return $this->redirectTo( $object );
			}

			/*
			 * TODO: Check to see if the student ID has already been awarded a slot and used it.
			 * Before allowing the new submissions.
			 */
			$submission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
				'studentID' => $student->getStudentID(),
				'submissionStatus' => $submissionsStatus,
				'enrollmentPeriod' => $object->getEnrollmentPeriod(),
			) );

		} else {
			$student = new Student();
			$student->setFirstName( $object->getFirstName() );
			$student->setLastName( $object->getLastName() );
			$student->setDob( $object->getDob() );
			$student->setAddress( $object->getAddress() );
			$student->setCity( $object->getCity() );
			$student->setZip( $object->getZip() );
			$student->setGrade( $object->getGrade() );
			$student->setRace( $object->getRace() );
			$student->setStudentID( $object->getStudentID() );
			$student->setSchool( $object->getCurrentSchool( false ) );
			$submission = null;
		}

		//If admin selected Created and Deactivate
		if( null !== $createAndDeactivateButton && $submission != null ) {
			//Admin user selected, create and deactivate old submission.
			if( $submission->getEnrollmentPeriod() == $object->getEnrollmentPeriod() ) {
				//It is the same openEnrollment Period and the submission is active. Need to change it!

				$submissionsDeactivate = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
					->find( 6 );

				$submission->setSubmissionStatus( $submissionsDeactivate );
				$em->persist( $submission );

				$this->recordAudit( 27 , $submission->getId() , $object->getStudentID() );
				$submission = null;
			}
		}

		if( $student != 'newStudent' ) {
			$checkMinorityCommand = new CheckMinorityCommand();
			$checkMinorityCommand->setContainer( $this->container );
			$uniqID = $this->get('request_stack')->getCurrentRequest()->query->get( 'uniqid' , 0 );
			$passing = true;
			$postArray = $this->get('request_stack')->getCurrentRequest()->request->get( $uniqID , array() );
			if( isset( $postArray['passing'] ) ) {
				if( $this->get('request_stack')->getCurrentRequest()->request->get( $uniqID )['passing'] == 'n' ) {
					$passing = false;
				}
			}
			$schools = $checkMinorityCommand->checkMinorityStatus( $student , $this , $object->getEnrollmentPeriod()->getId() , $passing );

			$zonedSchools = unserialize( base64_decode( $this->get('request_stack')->getCurrentRequest()->getSession()->get( 'stw-formData-zoned' ) ) );

			if( $schools == false) {
				return $this->redirect( $this->admin->generateUrl('create') );
			}
		} else {
			$zonedSchools = array();
		}

		if( $submission == null ) {

			$submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
				->find( 1 );

			$firstChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $object->getFirstChoice()->getId() );
			if( $object->getSecondChoice() != null ) {
				$secondChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->find( $object->getSecondChoice()->getId() );
			} else {
				$secondChoice = null;
			}
			$form = $em->getRepository( 'IIABStudentTransferBundle:Form' )->find( $object->getFormID()->getId() );
			$enrollmentPeriod = $em->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->find( $object->getEnrollmentPeriod()->getId() );

			$object->setFirstChoice( $firstChoice );
			if( $secondChoice != null ) {
				$object->setSecondChoice( $secondChoice );
			}
			$object->setFormID( $form );
			$object->setEnrollmentPeriod( $enrollmentPeriod );

			$object->setSubmissionDateTime( new \DateTime() );
			$object->setLotteryNumber( $lotteryNumber );
			if( $student != 'newStudent' ) {

				$race = $this->getDoctrine()->getManager()
					->getRepository( 'IIABStudentTransferBundle:Race' )
					->find($student->getRace());

				$object->setLastName( $student->getLastName() );
				$object->setFirstName( $student->getFirstName() );
				$object->setDob( $student->getDob() );
				$object->setAddress( $student->getAddress() );
				$object->setCity( $student->getCity() );
				$object->setZip( $student->getZip() );
				$object->setGrade( $student->getGrade() );
				$object->setRace( $race );
				$object->setCurrentSchool( $student->getSchool() );
			}
			$object->setSubmissionStatus( $submissionsStatus );
			$object->setHsvZonedSchools( $zonedSchools );

			$this->admin->create( $object );

			$confirmNumber = sprintf( '%s-%s-%d' , $object->getFormID()->getFormConfirmation() , $object->getEnrollmentPeriod()->getConfirmationStyle() , $object->getId() );
			$object->setConfirmationNumber( $confirmNumber );
			$this->admin->update( $object );

			$this->recordAudit( 1 , $object->getId() , $object->getStudentID() );

			$this->get('stw.email')->sendConfirmationEmail( $object );

			if( $this->isXmlHttpRequest() ) {
				return $this->renderJson( array(
					'result' => 'ok' ,
					'objectId' => $this->admin->getNormalizedIdentifier( $object )
				) );
			}

			$this->addFlash( 'sonata_flash_success' , $this->admin->trans( 'flash_create_success' , array( '%name%' => $object->getConfirmationNumber() ) , 'SonataAdminBundle' ) );

			// redirect to edit mode
			if( null !== $createAndDeactivateButton)
				return new RedirectResponse( $this->admin->generateUrl('list') );

			return $this->redirectTo( $object );
		}

		$this->addFlash( 'sonata_flash_error' , $this->admin->trans( 'iiab.admin.errors.alreadySubmitted' , array( '%name%' => $object->getConfirmationNumber() ) , 'IIABStudentTransferBundle' ) );

		if( null !== $createAndDeactivateButton)
			return new RedirectResponse( $this->admin->generateUrl('create') );

		return $this->redirectTo( $object );
	}

	public function addFlashPublic( $type , $message ) {
		$this->addFlash( $type , $message );
	}

	/**
	 * @param int $auditCode
	 * @param int $studentID
	 * @param int $submission
	 */
	private function recordAudit( $auditCode = 0 , $submission = 0 , $studentID = 0 ) {
		$em = $this->getDoctrine()->getManager();
		//$user = $this->get( 'security.context' )->getToken()->getUser();
		$user = $this->getUser();

		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( $this->get('request_stack')->getCurrentRequest()->getClientIp() );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( $studentID );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( ( $user == 'anon.' ? 0 : $user->getId() ) );

		$em->persist( $audit );
		$em->flush();
		$em->clear();
	}

	/**
	 * @param $studentID
	 *
	 * @Route( "/admin/iiab/submission/filter-schools/{studentID}" , name="ajax_filter_schools" , requirements={ "studentID" = ".+" } , options={ "expose" = true } )
	 * @return Response
	 */
	public function filterSchoolsBasedOnStudentID( $studentID ) {

		$em = $this->getDoctrine()->getManager();

		$enrollment = $this->get('request_stack')->getCurrentRequest()->get( 'enrollment' );
		$passing = $this->get('request_stack')->getCurrentRequest()->get( 'passing' );

		$student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array(
			'studentID' => $studentID
		) );

		if( $student == null ) {
			return new Response( json_encode( array( 'error' => $this->container->get('translator')->trans( 'iiab.admin.errors.noStudentFoundAjax' , array() , 'IIABStudentTransferBundle' ) ) ) , 200 );
		}

		$getAvailableSchools = new GetAvailableSchoolCommand();
		$getAvailableSchools->setContainer( $this->container );
		$schools = $getAvailableSchools->getAvailableSchools( $student , true , $enrollment , $passing );

		if( empty( $schools ) ) {
			return new Response( json_encode( array( 'error' => $this->container->get('translator')->trans( 'iiab.admin.errors.noSchoolsFoundAjax' , array() , 'IIABStudentTransferBundle' ) ) ) , 200 );
		}

		$studentNextGrade = ( $student->getGrade() != 99 ? $student->getGrade()+1 : 0 );
		if( $passing == 'n' ) {
			if( $studentNextGrade > 0 )
				$studentNextGrade--;
		}

		$formatedSchools = array();
		foreach( $schools as $id => $school ) {
			$formatedSchools[] = array(
				'id'	=> $id,
				'text'	=> $school,
			);
		}

		$submissions = $this->existingSubmission( $student , $enrollment );
		$foundSubmissions = array();
		if( $submissions != null ) {
			foreach( $submissions as $submission ) {
				$foundSubmissions[] = array(
					'confirmationNumber' => $submission->getConfirmationNumber(),
					'dateTime' => $submission->getSubmissionDateTime()->format( 'm/d/y H:i' ),
					'firstChoice' => $submission->getFirstChoice()->getSchoolName(),
					'secondChoice' => ( $submission->getSecondChoice() != null ? $submission->getSecondChoice()->getSchoolName() : '' ),
					'status' => ucwords( $submission->getSubmissionStatus()->getStatus() ),
				);
			}
		}
		$zonedSchools = $this->container->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );

		$race = $this->getDoctrine()->getManager()
					->getRepository( 'IIABStudentTransferBundle:Race' )
					->find($student->getRace());


		$response = array(
			'success'	=> 1,
			'schools'	=> $formatedSchools,
			'name'		=> $student->getLastName() . ', ' . $student->getFirstName(),
			'address'	=> $student->getAddress(),
			'dob'		=> $student->getDob(),
			'grade' 	=> $student->getGrade(),
			'nextGrade' => $studentNextGrade,
			'currentSchool' => $student->getSchool(),
			'zonedSchools' => $zonedSchools,
			'race'		=> $race->getRace(),
			'submission'=> $foundSubmissions,
		);

		return new Response( json_encode( $response ) , 200 );
	}

	/**
	 *
	 * @Route( "/admin/iiab/submission/filter-schools-non-student/" , name="ajax_filter_schools_nonstudent" , options={ "expose" = true } )
	 * @return Response
	 */
	public function filterSchoolsBasedOnNonStudent() {

		$em = $this->getDoctrine()->getManager();

		$enrollment = $this->get('request_stack')->getCurrentRequest()->get( 'enrollment' );

		$schools = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
			->where( 'a.enrollmentPeriod = :enrollment' )
			->setParameter(	'enrollment' , abs( $enrollment ) )
			->addOrderBy( new OrderBy( 'a.schoolName' , 'ASC' ) )
			->addOrderBy( new OrderBy( 'a.grade' , 'ASC' ) )
			->getQuery()
			->getArrayResult()
		;

		$formatedSchools = array();
		foreach( $schools as $id => $school ) {
			$formatedSchools[] = array(
				'id'	=> $school['id'],
				'text'	=> $school['hsvCityName'] . ' - Grade:' . $school['grade'],
			);
		}

		$response = array(
			'success'	=> 1,
			'schools'	=> $formatedSchools,
		);

		return new Response( json_encode( $response ) , 200 );
	}

	private function existingSubmission( $student , $enrollment ) {

		$em = $this->getDoctrine();

		/*$openEnrollments = $em->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->createQueryBuilder( 'o' )
			->setMaxResults( 1 )
			->where( 'o.beginningDate <= :testDate')
			->andWhere( 'o.endingDate >= :testDate' )
			->setParameter( 'testDate' , date( 'Y-m-d H:i:s' ) )
			->setMaxResults( 1 )
			->getQuery()
			->getResult()
		;
		if( $openEnrollments != null && !empty( $openEnrollments ) )
			$openEnrollments = $openEnrollments[0];
		else
			$openEnrollments = null;
		*/

		$submissions = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findBy( array(
			'studentID' => $student->getStudentID(),
			'enrollmentPeriod' => $enrollment
		) );

		return $submissions;

	}
}