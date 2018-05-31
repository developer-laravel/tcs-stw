<?php

namespace IIAB\StudentTransferBundle\Admin;

use Doctrine\ORM\EntityRepository;
use IIAB\StudentTransferBundle\Entity\WaitList;
use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Entity\SubmissionData;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Validator\ErrorElement;
use Symfony\Component\Intl\DateFormatter\IntlDateFormatter;

class SubmissionAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_submission';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'submission';



	// public function validate( ErrorElement $errorElement , $object ) {

	// 	$status = $object->getSubmissionStatus();
	// 	if( !empty( $status ) && in_array( $object->getSubmissionStatus()->getId(), array(2, 3, 4, 11) ) ) {
 //            if ($object->getAwardedSchoolID() != NULL) {
 //                $firstChoice = $object->getFirstChoice();
 //                $secondChoice = $object->getSecondChoice();
 //                $awardedSchool = $object->getAwardedSchoolID();

 //                if ($awardedSchool != $firstChoice && $awardedSchool != $secondChoice) {
 //                    $errorElement
 //                        ->addViolation('Awarded school must be one of the two schools selected by the student.')
 //                        ->end();
 //                }
 //            } else {
 //                $errorElement
 //                    ->addViolation('You must select an awarded school in order to change the submission status.')
 //                    ->end();
 //            }
 //        }
	// 	if( $object->getSubmissionStatus() != null ) {
	// 		if( $object->getSubmissionStatus()->getId() == 2 ) {
	// 			if( $object->getRoundExpires() == 3 && $object->getManualAwardDate() == '' ) {

	// 				$errorElement
	// 					->addViolation( 'You must select a Manual Award Date.' )
	// 					->end();
	// 			}
	// 		}
	// 	}

	// 	$errorElement
	// 		->with( 'formID' )
	// 		->assertNotNull()
	// 		->end()
	// 		->with( 'enrollmentPeriod' )
	// 		->assertNotNull()
	// 		->end()
	// 		->with( 'firstChoice' )
	// 		->assertNotNull()
	// 		->end();
	// }

	public function getExportFields() {

		return array(
			'Student ID' => 'studentID' ,
			'Confirmation Number' => 'confirmationNumber' ,
			'Enrollment Period' => 'enrollmentPeriod' ,
			'Submission Date/Time' => 'submissionDateTime' ,
			'First Name' => 'firstName' ,
			'Last Name' => 'lastName' ,
			'Date of Birth' => 'dob' ,
			'Address' => 'address' ,
			'City' => 'city' ,
			'Zip' => 'zip' ,
			'Grade' => 'grade' ,
			'Race' => 'race' ,
			'Email Address' => 'email' ,
			'Current School' => 'currentSchool' ,
			'First Choice' => 'firstChoice' ,
			'Second Choice' => 'secondChoice' ,
			'Awarded School' => 'awardedSchoolID' ,
			'Submission Status' => 'submissionStatus' ,
			'Submission was processed' => 'afterLotterySubmissionReportStyle',
			//'Submitter' => 'submitter' ,
			'Zoned School' => 'hsvZonedSchoolsString' ,
			'Initial or Renewal' => 'isRenewal',
			'Additional Submission Details' => 'submissionDataString',

		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getExportFormats() {

		return array(
			'csv'
		);
	}

	protected function configureFormFields( FormMapper $form ) {

		$form
			->with( 'Submission Type' , array( 'class' => 'col-md-12' ) )->end()
			->with( 'Student Info' , array( 'class' => 'col-md-12 activeStudent' ) )->end()
			->with( 'New Student Info' , array( 'class' => 'col-md-12 newStudent hide' ) )->end()
			->with( 'Personnel Transfer Information' , array( 'class' => 'col-md-12 hide personnel' ) )->end()
			->with( 'SPED Transfer Information' , array( 'class' => 'col-md-12 hide sped' ) )->end()
			->with( 'Superintendent Transfer Information' , array( 'class' => 'col-md-12 hide superintendent' ) )->end()
			->with( 'Submission Data' , array( 'class' => 'col-md-12 hide submissionData' ) )->end()
			->with( 'Student Choices' , array( 'class' => 'col-md-6 hide choices' ) )->end()
			->with( 'Awarded Choice' , array( 'class' => 'col-md-6 hide awarded-choice' ) )->end()
			->with( 'Existing Submission' , array( 'class' => 'col-md-12 existing hide' ) )->end();

		$subject = $this->getSubject();
		$status = $subject->getSubmissionStatus() == null ? null : $subject->getSubmissionStatus()->getId();

		//Creating a brand new object, is show the create fields.
		if( $status == null ) {

			$form = $this->handleNewSubmission( $form );

		} else {
			//Handling all forms.

			$submission_data = $subject->getSubmissionData();
			$submission_data_hash = [];
			foreach( $submission_data as $datum ){
				$submission_data_hash[ $datum->getMetaKey() ] = $datum->getMetaValue();
			}
			$this->submission_data_hash = $submission_data_hash;

			$form->with('Student Info')
				->add( 'passing' , 'choice' , array(
					'label' => 'Student Placement' , 'mapped' => false , 'required' => true , 'help' => 'Is the student passing or failing?' , 'choices' => array(
						// 'y' => 'Promoted' ,
						// 'n' => 'Retained' ,
						'y' => 'Next Grade' ,
						'n' => 'Current Grade' ,
						'required' => false,
					)
				) )
				->add( 'afterLotterySubmission' , null , array( 'required' => false , 'disabled' => true , 'label' => 'Submission was processed: ' . $subject->getAfterLotterySubmissionReportStyle() ) )
				->add( 'studentID' , null , array( 'label' => 'Student ID' , 'required' => false , 'help' => 'Enter State ID #, then schools will populate below.' ) )
				->add( 'first_name' , 'text' , array( 'required' => false ) )
				->add( 'last_name' , 'text' , array( 'required' => false ) )
				->add( 'address' , 'text' , array( 'required' => false ) )
				->add( 'city' , 'text' , array( 'required' => false ) )
				->add( 'zip' , 'text' , array( 'required' => false ) )
				->add( 'dob' , 'text' , array( 'label' => 'Date of Birth' ) )
				->add( 'grade' , 'choice' , array(
					'required' => false ,
					'choices' => array_flip([
						'99' => 'PreSchool' ,
						'0' => 'Kindergarten' ,
						'1' => '01' ,
						'2' => '02' ,
						'3' => '03' ,
						'4' => '04' ,
						'5' => '05' ,
						'6' => '06' ,
						'7' => '07' ,
						'8' => '08' ,
						'9' => '09' ,
						'10' => '10' ,
						'11' => '11' ,
						'12' => '12' ,
					])
				))
				->add( 'race' )
				->add( 'currentSchool' , 'text' , array( 'required' => false ) )
				->add( 'hsvZonedSchoolsString' , 'text' , array( 'required' => false , 'label' => 'Zoned School (read only)' , 'disabled' => true , 'attr' => array( 'style' => 'width: 220px;' ) ) );

				$onchange = "
	                var id = this.id;
	                var selection = this.value;
	                var base_id = id.split('proof_of_residency')[0];

                	var date_proven = document.getElementById( base_id +'proof_of_residency_date');

                	if( selection == 1){
                		console.log( 'show' );
                		date_proven.style.display = '';
                	} else {
                		console.log( 'hide');
                		date_proven.style.display = 'none';
                	}
            	";
	    		$onchange = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $onchange));

	        	$form
				->add( 'proof_of_residency', 'choice', [
					'mapped' => false,
					'placeholder' => '',
					'choices' => ['Yes'=>1, 'No'=>0],
					'data' => ( isset( $submission_data_hash['proof_of_residency'] ) ) ? $submission_data_hash['proof_of_residency'] : 0,
					'attr' => ['onchange'=>$onchange],
					'required' => false,
				])
				->add( 'proof_of_residency_date' , 'datetime' , array(
					'label' => false ,
					'data' => ( isset( $submission_data_hash['proof_of_residency_date'] ) ) ? new \DateTime( $submission_data_hash['proof_of_residency_date'] ) : new \DateTime(),
					'format' => IntlDateFormatter::LONG ,
					'mapped' => false,
					'attr' => ( isset( $submission_data_hash['proof_of_residency_date'] ) )
						? ['style' => 'margin-bottom: 10px;']
						: ['style' => 'margin-bottom: 10px; display:none;'],
					'required' => false,
				) );

				if( in_array( $subject->getFormID()->getId(), [2,6,7,8,9] ) ){
					$form
					->add( 'isRenewal', 'choice', [
						'mapped' => false,
						'placeholder' => '',
						'choices' => ['Renewal'=>1, 'Initial'=>0],
						'data' => ( isset( $submission_data_hash['is_renewal'] ) ) ? $submission_data_hash['is_renewal'] : 0,
						'required' => false,
					]);
				}

				$form
				->add( 'primTelephone' , 'text' , array( 'label' => 'Primary Phone' , 'required' => true ) )
				->add( 'secTelephone' , 'text' , array( 'label' => 'Alternate Phone' , 'required' => false ) )
				->add( 'email' , 'text' , array(
					'label' => 'Email Address' ,
					'required' => false,
					'help' => '<button '.
                                    'title="Resend EmailConfirmation "'.
                                    'type="button" class="btn btn-info resend-email" data-email-type="confirmation" data-submission-id="'.$subject->getId().'">'.
                                    '<i class="fa fa-paper-plane"></i> Resend Confirmation<span></span></button>'
				) )
			 	->end();

			if( $status == '2' ) { //Offered
				$form = $this->handleAwardedSubmission( $form , $subject->getFormID()->getId() );
			} else if( $status == '3' ) { //Offered and Accepted
				$form = $this->handleAcceptedSubmission( $form , $subject->getFormID()->getId() );
			} else if( $status == '1' ) { //Active
				$form = $this->handleActiveSubmission( $form , $subject->getFormID()->getId() );
			} else if( $status == '5' ) { //Denied due to Space
				$form = $this->handleDeinedSubmission( $form , $subject->getFormID()->getId() );
			} else if( $status == '4' ) { //Offered and Declined
				$form = $this->handleDeclinedSubmission( $form , $subject->getFormID()->getId() );
			} else {
				$form = $this->handleAllStatusesSubmission( $form , $subject->getFormID()->getId(), $subject->getSubmissionStatus()->getId() );
			}

			//Handle the Personnel Form
			if( $subject->getFormID()->getId() == 2 ) {
				$form = $this->handlePersonnelSubmission( $form, $submission_data_hash );
			}
			//Handle the SPED Form
			if( $subject->getFormID()->getId() == 3 ) {
				$form = $this->handleSPEDSubmission( $form );
			}
			//Handle the Superintendent Form
			if( $subject->getFormID()->getId() == 4 ) {
				$form = $this->handleSUPSubmission( $form );
			}
			//Handle the Success Prep Form
			if( $subject->getFormID()->getId() == 9 ) {
				$form = $this->handleSuccessPrepSubmission( $form );
			}
		}
	}

	/**
	 * @param FormMapper $form
	 *
	 * @return FormMapper
	 */

	private function handleNewSubmission( FormMapper $form ) {

		$form
			->with( 'Submission Type' )
			->add( 'formID' , null , array( 'label' => 'Transfer Type' , 'required' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
			->add( 'enrollmentPeriod' , null , array( 'required' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
			->add( 'enrolled' , 'choice' , array(
				'attr' => array( 'class' => '' ) ,
				'mapped' => false ,
				'choices' => array_flip( array( '0' => 'No' , '1' => 'Yes' ) ) ,
				'placeholder' => '' ,
				'label' => 'Student is already enrolled in TCS Schools?' ,
				'help' => 'Yes, means they would have a Student ID.' ) )
			->end()
			->with( 'Student Info' )
			->add( 'passing' , 'choice' , array(
				'label' => 'Student Placement' , 'mapped' => false , 'required' => true , 'help' => 'Is the student passing or failing?' , 'choices' => array_flip( array(
					'y' => 'Promoted' ,
					'n' => 'Retained' ,
				))
			) )
			->add( 'studentID' , null , array( 'label' => 'Student ID' , 'required' => false , 'help' => 'Enter State ID #, then schools will populate below.' , 'attr' => array( 'class' => 'new-submission' ) ) )
			->add( 'name' , 'text' , array( 'mapped' => false ,  'disabled' => true , 'help' => 'This will populate the student info' , 'required' => false ) )
			->add( 'address' , 'text' , array( 'mapped' => false , 'required' => false , 'disabled' => true ,  'help' => 'This will populate the student info' ) )
			->add( 'afterLottery' , 'choice' , array(
				'label' => 'After Lottery Submission' ,
				'required' => false ,
				'mapped' => false ,
				'choices' => array_flip( array( 1 => 'Yes - Will NOT be included in Lottery' , 0 => 'No - Included in Lottery' )) , 'data' => 0 ) )
			->end()
			->with( 'New Student Info' )
			->add( 'first_name' , 'text' , array( 'mapped' => false , 'required' => false ) )
			->add( 'last_name' , 'text' , array( 'mapped' => false , 'required' => false ) )
			->add( 'street' , 'text' , array( 'mapped' => false , 'required' => false ) )
			->add( 'city' , 'text' , array( 'mapped' => false , 'required' => false ) )
			->add( 'zip' , 'text' , array( 'mapped' => false , 'required' => false ) )
			->add( 'dob' , 'birthday' , array( 'label' => 'Date of Birth' , 'mapped' => false , 'required' => false , 'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ) ) )
			->add( 'grade' , 'choice' , array(
				'mapped' => false ,
				'required' => false ,
				'choices' => array_flip( array(
					'99' => 'PreSchool' ,
					'0' => 'Kindergarten' ,
					'1' => '01' ,
					'2' => '02' ,
					'3' => '03' ,
					'4' => '04' ,
					'5' => '05' ,
					'6' => '06' ,
					'7' => '07' ,
					'8' => '08' ,
					'9' => '09' ,
					'10' => '10' ,
					'11' => '11' ,
					'12' => '12' ,
				))
			) )
			->add( 'race' )
			->add( 'currentSchool' , 'text' , array( 'required' => false ) )
			->add( 'afterLottery_new' , 'choice' , array(
				'attr' => array( 'style' => 'width:auto;' ) ,
				'label' => 'After Lottery Submission' ,
				'required' => true ,
				'mapped' => false ,
				'choices' => array_flip( array( 1 => 'Yes - Will NOT be included in Lottery' , 0 => 'No - Included in Lottery' ) ) ,
				'data' => 0 ) )
			//->add( 'currentSchool' , 'text' , array( 'mapped' => false , 'required' => false ) )
			->end()
			->with('Additional Data')
			->add( 'primTelephone' , 'text' , array( 'label' => 'Primary Phone' , 'required' => true ) )
			->add( 'secTelephone' , 'text' , array( 'label' => 'Alternate Phone' , 'required' => false ) )
			->add( 'email' , 'text' , array( 'label' => 'Email Address' , 'required' => false ) )
			->end()
			->with( 'Personnel Transfer Information' , array( 'class' => 'col-md-12 hide personnel' ) )
			->add( 'employeeID' )
			->add( 'employeeFirstName' )
			->add( 'employeeLastName' )
			->add( 'employeeLocation' )
			->end()
			->with( 'SPED Transfer Information' , array( 'class' => 'col-md-12 hide sped' ) )
			->add( 'SPED-Student-ID' , 'text' , array( 'label' => 'SPED Student ID' , 'mapped' => false , 'required' => false , 'help' => 'Enter State ID #' ) )
			->add( 'SPED-Current-School' , 'text' , array( 'label' => 'SPED Current School' , 'mapped' => false , 'required' => false ) )
			->add( 'SPED-Current-Grade' , 'text' , array( 'label' => 'SPED Current Grade' , 'mapped' => false , 'required' => false ) )
			->end()
			->with( 'Superintendent Transfer Information' , array( 'class' => 'col-md-12 hide superintendent' ) )
			->add( 'Reason' , 'choice' , array(
				'mapped' => false , 'required' => false , 'choices' => array(
					'Safety' => 'Safety' ,
					'Bullying' => 'Bullying' ,
					'Discipline Issue' => 'Discipline Issue' ,
					'Custody' => 'Custody' ,
					'Family Illness' => 'Family Illness' ,
					'Medical' => 'Medical' ,
					'Other' => 'Other'
				)
			) )
			->add( 'Other-Reason' , 'text' , array( 'label' => 'Other Reason' , 'mapped' => false , 'required' => false , 'help' => 'Provide the reason for Other' , 'attr' => array() ) )
			->end()
			->with( 'Student Choices' )
			->add( 'firstChoice' , null , array( 'attr' => array( 'class' => '' ) ) )
			->add( 'secondChoice' , null , array( 'attr' => array( 'class' => '' ) , 'required' => false ) )
			->end()
			->with( 'Existing Submission' )
			->add( 'Submission' , 'hidden' , array( 'required' => false , 'mapped' => false ) )
			->end();
		return $form;
	}

	/**
	 * @param FormMapper $form
	 * @param Integer	 $formID
	 *
	 * @return FormMapper
	 */
	private function handleAwardedSubmission( FormMapper $form , $formID ) {

		if( $formID == 1 ) {
			//Only allow them to set the status to Accepted or Declined
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( 'required' => true , 'query_builder' => function( EntityRepository $er ) { return $er->createQueryBuilder('s')->where('s.id = 2 OR s.id = 3 OR s.id = 4')->orderBy('s.id' , 'ASC'); } ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6' ) )
				->add( 'firstChoice' , null , array(  'disabled' => true ) )
				->add( 'secondChoice' , null , array(  'disabled' => true , 'required' => false ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' ,  'disabled' => true ) )
				->end();
			if( $this->getSubject()->getRoundExpires() == 3 ) {
				$form
					->with( 'Awarded Choice' , array( 'class' => 'col-md-6' ) )
					->add( 'manualAwardDate' , 'date' , array( 'required' => true ) )
					->end();
			}
		} else {
			global $subject;
			$subject = $this->getSubject();
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( 'required' => true ) )//, 'query_builder' => function( EntityRepository $er ) { return $er->createQueryBuilder('s')->where('s.id = 2 OR s.id = 3 OR s.id = 4')->orderBy('s.id' , 'ASC'); } ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6' ) )
				->add( 'firstChoice' , null , array(  'disabled' => false , 'query_builder' => function( EntityRepository $er ) { global $subject; return $er->createQueryBuilder('a')->where('a.enrollmentPeriod = ' . $subject->getEnrollmentPeriod()->getId() )->orderBy('a.id' , 'ASC'); } ) )
				->add( 'secondChoice' , null , array(  'disabled' => false , 'required' => false , 'query_builder' => function( EntityRepository $er ) { global $subject; return $er->createQueryBuilder('a')->where('a.enrollmentPeriod = ' . $subject->getEnrollmentPeriod()->getId() )->orderBy('a.id' , 'ASC'); } ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' ,  'disabled' => false , 'query_builder' => function( EntityRepository $er ) { global $subject; return $er->createQueryBuilder('a')->where('a.enrollmentPeriod = ' . $subject->getEnrollmentPeriod()->getId() )->orderBy('a.id' , 'ASC'); } ) )
				->end();
			if( $this->getSubject()->getRoundExpires() == 3 ) {
				$form
					->with( 'Awarded Choice' , array( 'class' => 'col-md-6' ) )
					->add( 'manualAwardDate' , 'date' , array( 'required' => true ) )
					->end();
			}
		}
		return $form;
	}

	/**
	 * @param FormMapper $form
	 * @param Integer	 $formID
	 *
	 * @return FormMapper
	 */
	private function handleAcceptedSubmission( FormMapper $form , $formID ) {

		if( $formID == 1 ) {
			//Only allow them to set the status to Declined
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( 'required' => false , 'query_builder' => function( EntityRepository $er ) { return $er->createQueryBuilder('s')->where('s.id = 3 OR s.id = 4 OR s.id = 9')->orderBy('s.id' , 'ASC'); }  ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) )
				->add( 'firstChoice' , null , array(  'disabled' => true ) )
				->add( 'secondChoice' , null , array(  'disabled' => true , 'required' => false ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6 awarded-choice' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' ,  'disabled' => true ) )
				->end();
		} else {
			//Only allow them to set the status to Declined
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( 'required' => false ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) )
				->add( 'firstChoice' , null , array(  'disabled' => true ) )
				->add( 'secondChoice' , null , array(  'disabled' => true , 'required' => false ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6 awarded-choice' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' ,  'disabled' => true ) )
				->end();
		}
		return $form;
	}

	private function handleActiveSubmission( FormMapper $form , $formID ) {

		global $subject;
		$subject = $this->getSubject();

		if($formID == 1 ) {
			//Only allow them to set the status to Inactive or Overwritten
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( 'required' => true , 'query_builder' => function( EntityRepository $er ) { return $er->createQueryBuilder('s')->where('s.id = 1 OR s.id = 6 OR s.id = 8 OR s.id = 2 OR s.id = 10')->orderBy('s.status' , 'ASC'); } ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) )
				->add( 'firstChoice' , null , array(  'disabled' => true ) )
				->add( 'secondChoice' , null , array(  'disabled' => true , 'required' => false ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6 hide awarded-choice' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' , 'query_builder' => function( EntityRepository $er ) { global $subject; return $er->createQueryBuilder('a')->where('a.enrollmentPeriod = ' . $subject->getEnrollmentPeriod()->getId() )->orderBy('a.id' , 'ASC'); } ) )
				->add( 'manualAwardDate' , 'date' , array( 'required' => false , 'label' => 'Manual Awarded Mail Date' , 'help' => 'If you are manually awarding, set this to your Mail Date. Used for link expiration.' ) )
				->end();
		} else {
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( 'required' => true ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) );

				if( $formID == 6){
					$transfer_data = null;
					$data = $subject->getSubmissionData();
					foreach ($data as $datum ){
						if( $datum->getMetaKey() == 'transfer_option' ){
							$transfer_data = $datum->getMetaValue();
						}
					}

				$form
					->add( 'transfer_option', 'text', [
						'label' => 'Accountability Act Transfer Option',
						'mapped' => false,
						'attr' => ['readonly' => 'readonly'],
						'data' => $transfer_data,
					]);
				}

				$form
				->add( 'firstChoice' , null , array( 'query_builder' => function( EntityRepository $er ) { global $subject; return $er->createQueryBuilder('a')->where('a.enrollmentPeriod = ' . $subject->getEnrollmentPeriod()->getId() )->orderBy('a.id' , 'ASC'); } ) )
				->add( 'secondChoice' , null , array( 'required' => false , 'query_builder' => function( EntityRepository $er ) { global $subject; return $er->createQueryBuilder('a')->where('a.enrollmentPeriod = ' . $subject->getEnrollmentPeriod()->getId() )->orderBy('a.id' , 'ASC'); } ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6 hide awarded-choice' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' , 'query_builder' => function( EntityRepository $er ) { global $subject; return $er->createQueryBuilder('a')->where('a.enrollmentPeriod = ' . $subject->getEnrollmentPeriod()->getId() )->orderBy('a.id' , 'ASC'); } ) )
				->add( 'manualAwardDate' , 'date' , array( 'required' => false , 'label' => 'Manual Awarded Mail Date' , 'help' => 'If you are manually awarding, set this to your Mail Date. Used for link expiration.' ) )
				->end();
		}
		return $form;
	}

	private function handleDeinedSubmission( FormMapper $form , $formID ) {

		if( $formID == 1 ) {
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				//->add( 'submissionStatus' , null , array(  'disabled' => true ) )
                ->add( 'submissionStatus' , null , array( 'required' => true , 'query_builder' => function( EntityRepository $er ) { return $er->createQueryBuilder('s')->where('s.id = 5 OR s.id = 2')->orderBy('s.status' , 'ASC'); } ) )
                ->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) )
				->add( 'firstChoice' , null , array(  'disabled' => true ) )
				->add( 'secondChoice' , null , array(  'disabled' => true , 'required' => false ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6 awarded-choice hide' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' ,  'disabled' => true , 'required' => false ) )
				->end();
		} else {
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) )
				->add( 'firstChoice' , null , array(  'disabled' => true ) )
				->add( 'secondChoice' , null , array(  'disabled' => true , 'required' => false ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6 awarded-choice hide' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' ,  'disabled' => true , 'required' => false ) )
				->end();
		}
		return $form;
	}

	private function handleDeclinedSubmission( FormMapper $form , $formID ) {

		if( $formID == 1 ) {
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( 'query_builder' => function( EntityRepository $er ) { return $er->createQueryBuilder('s')->where('s.id = 4 OR s.id = 3 OR s.id = 9')->orderBy('s.status' , 'ASC'); } ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) )
				->add( 'firstChoice' , null , array(  'disabled' => true ) )
				->add( 'secondChoice' , null , array(  'disabled' => true , 'required' => false ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6 awarded-choice hide' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' ,  'disabled' => true , 'required' => false ) )
				->end();
		} else {
			$form
				->with( 'Submission Type' )
				->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'disabled' => true , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
				->add( 'enrollmentPeriod' , null , array(  'disabled' => true , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
				->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
				->add( 'submissionStatus' , null , array( ) )
				->end()
				->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) )
				->add( 'firstChoice' , null , array(  'disabled' => true ) )
				->add( 'secondChoice' , null , array(  'disabled' => true , 'required' => false ) )
				->end()
				->with( 'Awarded Choice' , array( 'class' => 'col-md-6 awarded-choice hide' ) )
				->add( 'awardedSchoolID' , null , array( 'label' => 'Awarded School' ,  'disabled' => true , 'required' => false ) )
				->end();
		}
		return $form;
	}

	/**
	 * @param FormMapper $form
	 * @param Integer	 $formID
	 *
	 * @return FormMapper
	 */
	private function handleAllStatusesSubmission( FormMapper $form , $formID, $submissionStatus ) {

		global $subject;
		$subject = $this->getSubject();

		$form
			->with( 'Submission Type' )
			->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'attr' => [ 'readonly' => 'readonly' ] , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
			//->add( 'enrollmentPeriod' , null , array(  'attr' => [ 'readonly' => 'readonly' ] , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
			->add( 'enrollmentPeriod' )
			//->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
            ->add( 'submissionStatus' , null , array(
            	'required' => true ,
            	'query_builder' => function( EntityRepository $er ) use( $submissionStatus ) {

            		$where = ( $submissionStatus == 13 )
            			? 's.id = :submissionStatus OR s.id = 2 OR s.id = 5 OR s.id = 1'
            			: 's.id = :submissionStatus OR s.id = 2 OR s.id = 5';

            		return $er->createQueryBuilder('s')
            		->where($where)
            		->setParameters( array(':submissionStatus' => $submissionStatus ) )
            		->orderBy('s.status' , 'ASC'); } ) )
            ->end()
			->with( 'Student Choices' , array( 'class' => 'col-md-6 choices' ) )
			->add( 'firstChoice' , null , array(  'attr' => [ 'readonly' => 'readonly' ] ) )
			//->add( 'firstChoice', null, [ 'attr' => [ 'readonly' => 'readonly' ] ] )
			//->add( 'secondChoice' , null , array(  'attr' => [ 'readonly' => 'readonly' ] , 'required' => false ) )
			->add( 'secondChoice', null, [ 'required' => false, 'attr' => [ 'readonly' => 'readonly' ] ] )
			->end()
			->with( 'Awarded Choice' , array( 'class' => 'col-md-6 awarded-choice' ) )
			->add( 'awardedSchoolID' , null , array(
				'label' => 'Awarded School' ,
				'query_builder' => function (
					EntityRepository $er ) use ($subject) {
						return $er->createQueryBuilder('a')->where('a.enrollmentPeriod = ' . $subject->getEnrollmentPeriod()->getId() )->orderBy('a.id' , 'ASC');
					} )
			)
			->add( 'manualAwardDate' , 'date' , array( 'required' => false , 'label' => 'Manual Awarded Mail Date' , 'help' => 'If you are manually awarding, set this to your Mail Date. Used for link expiration.' ) )
			->end();

		return $form;
	}

	private function handlePersonnelSubmission( FormMapper $form, $submission_data_hash ) {

		$form
			->with( 'Submission Type' )
			->add( 'formID' , null , array( 'label' => 'Transfer Type' ,  'attr' => [ 'readonly' => 'readonly' ] , 'help' => 'What type of transfer are you entering in?' , 'placeholder' => '' ) )
			//->add( 'enrollmentPeriod' , null , array(  'attr' => [ 'readonly' => 'readonly' ] , 'help' => 'Select the enrollment period this transfer needs to be assigned too.' , 'placeholder' => '' ) )
			->add( 'enrollmentPeriod' )
			->add( 'studentID' , null , array(  'disabled' => false , 'required' => false ) )
			->add( 'submissionStatus' , null , array( 'required' => true ) )
			->end()
			->with( 'Submission Data' , array( 'class' => 'col-md-12 submissionData' ) )
			->add( 'employeeID' , null , array() )
			->add( 'employeeFirstName' , null , array() )
			->add( 'employeeLastName' , null , array() )
			->add( 'employeeLocation' , null , array() )
			->add( 'employeeVerified', 'choice', [
					'mapped' => false,
					'placeholder' => '',
					'choices' => ['Yes'=>1, 'No'=>0],
					'data' => ( isset( $submission_data_hash['employee_verified'] ) ) ? $submission_data_hash['employee_verified'] : null,
					'required' => false,
				])
			->end()
			->with( 'Student Choices' )
			->add( 'firstChoice' , null , array(
				'attr' => ['style' => 'width: auto;'],
			) )
			->add( 'secondChoice' , null , array( 'required' => false) )
			->end();

		return $form;
	}

	/**
	 * @param FormMapper $form
	 *
	 * @return FormMapper
	 */
	private function handleSPEDSubmission( FormMapper $form ) {

		$form
			->with( 'Submission Data' , array( 'class' => 'col-md-12' ) )
			->add( 'submissionData' , 'sonata_type_collection' , array(
				'by_reference' => false ,
				'required' => false ,
				'type_options' => array( 'delete' => false , )
			) , array(
				'edit' => 'inline' ,
				'inline' => 'table' ,
			) )
			->end();

		return $form;
	}

	private function handleSUPSubmission( FormMapper $form ) {

		$form
			->with( 'Submission Data' , array( 'class' => 'col-md-12' ) )
			->add( 'submissionData' , 'sonata_type_collection' , array(
				'by_reference' => false ,
				'required' => false ,
				'type_options' => array( 'delete' => false , )
			) , array(
				'edit' => 'inline' ,
				'inline' => 'table' ,
			) )
			->end();

		return $form;

	}

	/**
	 * @param FormMapper $form
	 *
	 * @return FormMapper
	 */
	private function handleSuccessPrepSubmission( FormMapper $form ) {

		$form
			->with( 'Submission Data' , array( 'class' => 'col-md-12' ) )
			->add( 'grades_repeated', 'text', [
					'mapped' => false,
					'data' => ( isset( $this->submission_data_hash['grades_repeated'] ) ) ? $this->submission_data_hash['grades_repeated'] : null,
			])
			->add( 'referral', 'choice', [
					'mapped' => false,
					'placeholder' => '',
					'choices' =>[
						'Self Interest' => 1,
                        'Parent' => 2,
                        'Principal/Counselor/Teacher Referral' => 3,
                        'Attendance Office' => 4,
					],
					'data' => ( isset( $this->submission_data_hash['referral'] ) ) ? $this->submission_data_hash['referral'] : null,
			])
			->end();

		return $form;
	}

	protected function configureRoutes( RouteCollection $collection ) {

		//$collection->clearExcept( array(  , 'list' , 'show' , 'export' ) );
		$collection->clear();
		$collection
			->add( 'create' )
			->add( 'list' )
			->add( 'edit' )
			->add( 'show' )
			->add( 'export' );
		//$collection->add('create', $this->getRouterIdParameter().'/create');
	}

	/**
	 * @param string $context
	 *
	 * @return \Sonata\AdminBundle\Datagrid\ProxyQueryInterface
	 */
	public function createQuery( $context = 'list' ) {

		$query = parent::createQuery( $context );

		$this->user = $this->getConfigurationPool()->getContainer()->get( 'security.token_storage' )->getToken()->getUser();
		$schools = $this->user->getSchools();

		if(!empty( $schools ) ) {

			$specificSchools = $this->getConfigurationPool()->getContainer()
				->get( 'doctrine' )
				->getRepository( 'IIABStudentTransferBundle:ADM' )
				->findBy([
					'groupID' => $schools
				]);

			$query->orWhere(
				$query->expr()->in( $query->getRootAlias() . '.firstChoice' , ':schools' ),
				$query->expr()->in( $query->getRootAlias() . '.secondChoice' , ':schools' )
			);
			$query->setParameter( 'schools' , $specificSchools );
		}

		$forms = $this->user->getForms();
		if(!empty( $forms ) ) {

			$specificForms = $this->getConfigurationPool()->getContainer()
				->get( 'doctrine' )
				->getRepository( 'IIABStudentTransferBundle:Form' )
				->findBy([
					'id' => $forms
				]);

			$query->orWhere(
				$query->expr()->in( $query->getRootAlias() . '.formID' , ':forms' )
			);
			$query->setParameter( 'forms' , $specificForms );
		}

		return $query;
	}


	protected function configureListFields( ListMapper $list ) {

		$list
			->addIdentifier( 'confirmationNumber' , null , array( 'label' => 'Confirmation #' ) )
			->add( 'submissionDateTime' , null , array( 'label' => 'Submitted' , 'format' => 'm/d/y H:i' ) )
			->add( 'name' )
			->add( 'grade' )
			->add( 'race' )
			->add( 'currentSchool' )
			->add( 'hsvZonedSchoolsString' , null , array( 'label' => 'Zoned School' ) )
			->add( 'studentID' , null , array( 'label' => 'Student ID' ) )
			->add( 'enrollmentPeriod' )
			->add( 'firstChoice' , null , array( 'label' => 'Choice #1' ) )
			->add( 'secondChoice' , null , array( 'label' => 'Choice #2' ) )
			->add( 'formID' , null , array( 'label' => 'Form' ) )
			->add( 'submissionStatus' , null , array( 'label' => 'Status' ) );
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {

		$filter
			->add( 'studentID' )
			->add( 'firstName' )
			->add( 'lastName' )
			->add( 'grade' )
			->add( 'confirmationNumber' )
			->add( 'formID' , null , array( 'label' => 'Form' ) )
			->add( 'submissionStatus' )
			->add( 'enrollmentPeriod' );
	}

	/**
	 * @param \IIAB\StudentTransferBundle\Entity\Submission $object
	 * @return mixed
	 */
	public function preUpdate( $object ) {

		$uniqid = $this->getRequest()->query->get( 'uniqid' );
		$formData = $this->getRequest()->request->get( $uniqid );

		$DM = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();
		$uow = $DM->getUnitOfWork();
		$OriginalEntityData = $uow->getOriginalEntityData( $object );

		$originalStatus = $OriginalEntityData['submissionStatus']->getId();
		$newStatus = $object->getSubmissionStatus()->getId();

		//Going from "Offered and Accepted" to "Offered and Declined"
		if( $originalStatus == 3 && $newStatus == 4 ) {

			$currentEnrollmentChanged = $DM->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findOneBy( array(
				'enrollmentPeriod' => $object->getEnrollmentPeriod() ,
				'groupId' => $object->getAwardedSchoolID()->getGroupID()
			) , array( 'addedDateTime' => 'DESC' ) );

			if( $currentEnrollmentChanged != null ) {
				$race = strtolower( $object->getRace() );
				switch( $race ) {

					case 'black':
					case 'black/african american';
						$currentEnrollmentChanged->setBlack( $currentEnrollmentChanged->getBlack() - 1 );
						break;

					case 'white':
						$currentEnrollmentChanged->setWhite( $currentEnrollmentChanged->getWhite() - 1 );
						break;

					default:
						$currentEnrollmentChanged->setOther( $currentEnrollmentChanged->getOther() - 1 );
						break;
				}
				$DM->persist( $currentEnrollmentChanged );
			}
			$this->getConfigurationPool()->getContainer()->get( 'stw.email' )->sendDeclinedEmail( $object );
		}

		//Going from "Offered and Accepted" to "Denied Due to Ineligibility"
		if( $originalStatus == 3 && $newStatus == 9 ) {

			$currentEnrollmentChanged = $DM->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findOneBy( array(
				'enrollmentPeriod' => $object->getEnrollmentPeriod() ,
				'groupId' => $object->getAwardedSchoolID()->getGroupID()
			) , array( 'addedDateTime' => 'DESC' ) );

			if( $currentEnrollmentChanged != null ) {
				$race = strtolower( $object->getRace() );
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
				$DM->persist( $currentEnrollmentChanged );
			}
		}

		//Going from "Offered and Declined" to "Offered and Accepted"
		if( $originalStatus == 4 && $newStatus == 3 ) {

			$currentEnrollmentChanged = $DM->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findOneBy( array(
				'enrollmentPeriod' => $object->getEnrollmentPeriod() ,
				'groupId' => $object->getAwardedSchoolID()->getGroupID()
			) , array( 'addedDateTime' => 'DESC' ) );

			if( $currentEnrollmentChanged != null ) {
				$race = strtolower( $object->getRace() );
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
				$DM->persist( $currentEnrollmentChanged );
			}
			$this->getConfigurationPool()->getContainer()->get( 'stw.email' )->sendAcceptedEmail( $object );
		}

        //Going from "Active" to "Offered"
		if( $newStatus == 2 ) {
			$object->setRoundExpires( 3 );
			//$object->setManualAwardDate(new \DateTime());

			$currentEnrollmentChanged = $DM->getRepository( 'IIABStudentTransferBundle:CurrentEnrollmentSettings' )->findOneBy( array(
				'enrollmentPeriod' => $object->getEnrollmentPeriod() ,
				'groupId' => $object->getAwardedSchoolID()->getGroupId()
			) , array( 'addedDateTime' => 'DESC' ) );

			$url = $object->getId() . '.' . rand( 10 , 999 );
			$object->setUrl( $url );

			if( $currentEnrollmentChanged != null ) {
				$race = strtolower( $object->getRace() );
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
				$DM->persist( $currentEnrollmentChanged );
			}

			$waitListed = $object->getWaitList();
			foreach( $waitListed as $waitList ) {
				$object->removeWaitList( $waitList );
				$DM->remove( $waitList );
			}
		}

		//Going from "Active" to "Wait Listed"
		if( $originalStatus == 1 && $newStatus == 10 ) {

			if( $object->getFirstChoice() != null ) {
				$waitList = new WaitList();
				$waitList->setChoiceSchool( $object->getFirstChoice() );
				$waitList->setOpenEnrollment( $object->getEnrollmentPeriod() );
				$waitList->setSubmission( $object );
			}
		}

		//Going from "Wait Listed" to "Denied due to Space"
		if( $originalStatus == 10 && $newStatus == 5 ) {

			$waitListed = $object->getWaitList();
			foreach( $waitListed as $waitList ) {
				$object->removeWaitList( $waitList );
				$DM->remove( $waitList );
			}
		}

		if( isset( $newStatus) && $originalStatus != $newStatus ){
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
		}

		$submission_data = $object->getSubmissionData();
		$submission_data_hash = [];
		foreach( $submission_data as $datum ){
			$submission_data_hash[ $datum->getMetaKey() ] = $datum;
		}

		$data_found_proof = ( isset( $submission_data_hash['proof_of_residency'] ) )
			? $submission_data_hash['proof_of_residency']
			: new SubmissionData();

		$data_proven_date = ( isset( $submission_data_hash['proof_of_residency_date'] ) )
			? $submission_data_hash['proof_of_residency_date']
			: new SubmissionData();

		$proven_date = ( isset( $formData['proof_of_residency_date'] ) )
		 	? new \DateTime( implode( '-', $formData['proof_of_residency_date']['date'] )
				.' '. implode( ':', $formData['proof_of_residency_date']['time'] )
				.':00'
			)
			: null;

		if( isset( $formData[ 'employeeVerified' ] ) ){
			$data_found_employee_verified = ( isset( $submission_data_hash['employee_verified'] ) )
			? $submission_data_hash['employee_verified']
			: new SubmissionData();

			$data_found_employee_verified->setMetaKey('employee_verified');
            $data_found_employee_verified->setMetaValue( $formData['employeeVerified']);
            $data_found_employee_verified->setSubmission($object);
            $DM->persist($data_found_employee_verified);
		}

		if( isset( $formData[ 'isRenewal' ] ) ){
			$data_found_is_renewal = ( isset( $submission_data_hash['is_renewal'] ) )
			? $submission_data_hash['is_renewal']
			: new SubmissionData();

			$data_found_is_renewal->setMetaKey('is_renewal');
            $data_found_is_renewal->setMetaValue( $formData['isRenewal']);
            $data_found_is_renewal->setSubmission($object);
            $DM->persist($data_found_is_renewal);
		}

		if( isset( $formData[ 'referral' ] ) ){
			$data_found_referral = ( isset( $submission_data_hash['referral'] ) )
			? $submission_data_hash['referral']
			: new SubmissionData();

			$data_found_referral->setMetaKey('referral');
            $data_found_referral->setMetaValue( $formData['referral']);
            $data_found_referral->setSubmission($object);
            $DM->persist($data_found_referral);
		}

		if( isset( $formData[ 'grades_repeated' ] ) ){
			$data_found_grades_repeated = ( isset( $submission_data_hash['grades_repeated'] ) )
			? $submission_data_hash['grades_repeated']
			: new SubmissionData();

			$data_found_grades_repeated->setMetaKey('grades_repeated');
            $data_found_grades_repeated->setMetaValue( $formData['grades_repeated']);
            $data_found_grades_repeated->setSubmission($object);
            $DM->persist($data_found_grades_repeated);
		}

        if( isset( $formData[ 'proof_of_residency' ] )
            && $formData[ 'proof_of_residency' ]
        ){

            $data_found_proof->setMetaKey('proof_of_residency');
            $data_found_proof->setMetaValue($formData['proof_of_residency']);
            $data_found_proof->setSubmission($object);
            $DM->persist($data_found_proof);

            $data_proven_date->setMetaKey('proof_of_residency_date');
            $data_proven_date->setMetaValue($proven_date->format( 'Y-m-d H:i:s'));
            $data_proven_date->setSubmission($object);
            $DM->persist($data_proven_date);
        } else {
            $data_found_proof->setMetaKey('proof_of_residency');
            $data_found_proof->setMetaValue( (isset($formData['proof_of_residency'])) ? $formData['proof_of_residency'] : null );
            $data_found_proof->setSubmission($object);
            $DM->persist($data_found_proof);

            $data_proven_date->setMetaKey('proof_of_residency_date');
            $data_proven_date->setMetaValue(null);
            $data_proven_date->setSubmission($object);
            $DM->persist($data_proven_date);
        }
	}

	private function recordAudit( $auditCode = 0 , $submission = 0 , $studentID = 0 ) {
		$em = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();
		$user = $this->getConfigurationPool()->getContainer()->get('security.token_storage')->getToken()->getUser();

		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( $_SERVER['REMOTE_ADDR'] );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( $studentID );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( ( $user == 'anon.' ? 0 : $user->getId() ) );

		$em->persist( $audit );
	}

	private function getRaceOptions() {

		return [
			'' => 'Choose an option',
			'American Indian/Alaskan Native' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'American Indian/Alaskan Native' ) ,
			'American Indian/Alaskan Native- Hispanic' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'American Indian/Alaskan Native- Hispanic' ) ,
			'Asian' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'Asian' ) ,
			'Asian- Hispanic' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'Asian- Hispanic' ) ,
			'Black/African American' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'Black/African American' ) ,
			'Black/African American- Hispanic' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'Black/African American- Hispanic' ) ,
			'Multi Race - Two or More Races' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'Multi Race - Two or More Races' ) ,
			'Multi Race - Two or More Races- Hispanic' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'Multi Race - Two or More Races- Hispanic' ) ,
			'Native Hawaiian or Other Pacific Islander- Hispanic' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'Native Hawaiian or Other Pacific Islander- Hispanic' ) ,
			'Pacific Islander' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'Pacific Islander' ) ,
			'White' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'White' ) ,
			'White- Hispanic' => $this->getConfigurationPool()->getContainer()->get('translator')->trans( 'White- Hispanic' ) ,
		];
	}
}