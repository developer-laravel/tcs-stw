<?php

namespace IIAB\StudentTransferBundle\Controller;

use IIAB\StudentTransferBundle\Command\CheckMinorityCommand;
use IIAB\StudentTransferBundle\Command\CheckAddressCommand;
use IIAB\StudentTransferBundle\Command\GetAvailableSchoolCommand;
use IIAB\StudentTransferBundle\Command\GetZonedSchoolsCommand;
use IIAB\StudentTransferBundle\Entity\Student;
use IIAB\StudentTransferBundle\Entity\Submission;
use IIAB\StudentTransferBundle\Entity\SubmissionData;
use IIAB\StudentTransferBundle\Entity\SubmissionStatus;
use IIAB\StudentTransferBundle\Entity\Audit;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use IIAB\StudentTransferBundle\Lottery\Lottery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\NotBlank;
use IIAB\StudentTransferBundle\Form\Constraints\ValidAge;
use IIAB\StudentTransferBundle\Form\Constraints\ValidAddress;
use IIAB\StudentTransferBundle\Controller\Traits\TransferControllerTraits;

class TransferAccountabilityActController extends Controller {
    use TransferControllerTraits;

    /**
     * Handles the Education Accountability Act Transfers
     *
     * @param Request $request
     * @param int     $step
     *
     * @Route( "/act/step/{step}" , name="stw_accountability" , requirements={ "step" = "\d+" } )
     * @return \Symfony\Component\HttpFoundation\Response;
     */
    public function AccountabilityActTransferAction( Request $request , $step ){
        $em = $this->getDoctrine()->getManager();
        $textFields = '';
        $formType = '';

        // Custom error messages for non-HTML5, non-JS validation.
        $emptyStudent = new NotBlank();
        $emptyStudent->message = 'Please provide a valid State ID';
        $cannotBeBlank = new NotBlank();
        $cannotBeBlank->message = 'Cannot be blank';

        $lastFormData = $request->getSession()->has( 'stw-formData' );
        if( $lastFormData !== false )
            $lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );

        //Someone is trying to jump ahead!! Redirect back to step 1.
        //If both step is greater than 1 and session data is empty, send back to first step.
        if( $step > 1 && $lastFormData === false ) {
            $request->getSession()->remove( 'stw-formData' );
            $request->getSession()->remove( 'stw-formData-zoned' );
            $request->getSession()->remove( 'stw-other-formID' );
            return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
        }
        $currentSchoolYearText = $em->getRepository('IIABStudentTransferBundle:Settings')->findOneBy( array(
            'settingName' => 'current school year'
        ) );
        $nextSchoolYearText = $em->getRepository('IIABStudentTransferBundle:Settings')->findOneBy( array(
            'settingName' => 'next school year'
        ) );

        $openEnrollment = !empty( $lastFormData['enrollment'] ) ? $lastFormData['enrollment'] : $this->setOpenEnrollmentPeriodForForm( $request );

        $specialEnrollments = $this->getSpecialEnrollmentPeriods( 3 );
        if( is_null( $openEnrollment ) && !empty( $specialEnrollments ) ){
            $openEnrollment = $specialEnrollments[0]->getEnrollmentPeriod();
        }

        if( !empty( $openEnrollment ) && ( !is_object( $openEnrollment ) || get_class( $openEnrollment ) != 'IIAB\StudentTransferBundle\Entity\OpenEnrollment' ) ){
            $openEnrollment = $em->getRepository('IIABStudentTransferBundle:OpenEnrollment')->find( $openEnrollment );
        }

        $message = $em->getRepository('IIABStudentTransferBundle:Settings')->findOneBy( array(
            'settingName' => 'accountability act message'
        ) );

        switch( $step ) {
            default:
            case 1:

                $enrollment = $this->setOpenEnrollmentPeriodForForm( $request );

                if( $enrollment == null ) {
                    if( isset( $lastFormData['enrollment'] ) && !empty( $lastFormData['enrollment'] ) ) {
                        $enrollment = $em->getRepository('IIABStudentTransferBundle:OpenEnrollment')->find( $lastFormData['enrollment'] );
                    } else if( isset( $specialEnrollments ) ) {
                        $enrollment = $specialEnrollments[0]->getEnrollmentPeriod();
                    } else {
                        return $this->redirect( $this->generateUrl( 'stw_index' ) );
                    }
                }

                /** Form isn't active, break out */
                if( ! $enrollment->isFormAvailable( $this->getRouteFormId() ) && $specialEnrollments == NULL ) {
                    return $this->redirect( $this->generateUrl( 'stw_index' ) );
                }

                $form = $this->createFormBuilder()
                    ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step , 'enrollment' => $enrollment->getId() ) ) )
                    ->add( 'last-step' , 'hidden' , array(
                        'attr' => array( 'value' => $step )
                    ) )
                    ->add( 'enrollment' , 'hidden' , array(
                        'attr' => array( 'value' => $enrollment->getId() )
                    ) )
                    ->add( 'studentID' , 'text' , array(
                        'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ),
                        'constraints' => $emptyStudent,
                    ) )
                    ->add( 'dob' , 'birthday' , array(
                        'label' => $this->get('translator')->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) ,
                        //'data' => isset( $lastFormData['dob'] ) ? date( 'Y-m-d' , strtotime( $lastFormData['dob']->date ) ) : null,
                        'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ),
                        'constraints' => $cannotBeBlank,
                    ) )

                    ->add( 'isRenewal' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.isRenewal' , array() , 'IIABStudentTransferBundle' ),
                            'placeholder' => 'Please select option',
                            'choices' => array_flip ( [
                                'initial' => $this->get('translator')->trans( 'forms.initial' , array() , 'IIABStudentTransferBundle' ),
                                'renewal' => $this->get('translator')->trans( 'forms.renewal' , array() , 'IIABStudentTransferBundle' ), ] )
                        ) )

                    ->add( 'primTelephone' , 'text' , array (
                        'label' => $this->get('translator')->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ),
                        'data' => isset( $lastFormData['primTelephone'] ) ? $lastFormData['primTelephone'] : '',
                        'constraints' => $cannotBeBlank,
                    ) )

                    ->add( 'secTelephone' , 'text' , array(
                        'label' => $this->get( 'translator' )->trans( 'forms.secTelephone' , array() , 'IIABStudentTransferBundle' ),
                        'data' => isset( $lastFormData['secTelephone'] ) ? $lastFormData['secTelephone'] : '',
                        'required' => false
                    ) )

                    ->add( 'email', 'repeated', array(
                        'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ),
                        'type' => 'text',
                        'required' => false,
                        'invalid_message' => 'The email addresses must match',
                        'options' => array('attr' => array ( 'value' => isset ( $lastFormData['email'] ) ? $lastFormData['email'] : '' ) ),
                        'first_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) ),
                        'second_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.emailRepeat' , array() , 'IIABStudentTransferBundle' ) )
                    ) )
                    ->add( 'submitAndNext' , 'submit' , array(
                        'label' => $this->get('translator')->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' )
                    ) )
                    ->getForm();

                $form->handleRequest( $request );

                if ( ( $form['primTelephone'] == "" ) || ( $form['primTelephone'] == NULL ) ) {
                    $form->addError( new FormError( 'Primary Telephone cannot be left blank' ) );
                }

                if( $form->isValid() ) {
                    $formData = $form->getData();

                    //Force Upper Case on the studentID
                    $formData['studentID'] = strtoupper( $formData['studentID'] );

                    //Perform looking on Student ID HERE.
                    $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( [
                        'studentID' => $formData['studentID'] ,
                        'dob' => $formData['dob']->format( 'n/j/Y' )
                    ] );

                    if( $student == NULL ) {
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
                    } else {

                        $zonedSchools = $this->container->get('stw.check.address')
                            ->checkAddress( array(
                                'student_status' => 'new' ,
                                'address' => $student->getAddress() ,
                                'zip' => $student->getZip() ) );

                        $schools = [];
                        if( $zonedSchools == false ) {

                            $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'errors.emptySchool' , array() , 'IIABStudentTransferBundle' ) );
                            $form = $this->createFormBuilder()
                                ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                                ->add( 'last-step' , 'hidden' , array(
                                    'attr' => array( 'value' => $step )
                                ) )
                                ->getForm()
                            ;
                        }

                        if( $student->getGrade() > 90
                            || $student->getGrade() < 5
                        ){
                            $maybe_failing_school = $zonedSchools['Elementary'];
                        } else if( $student->getGrade() < 8 ){
                            $maybe_failing_school = $zonedSchools['Middle'];
                        } else if ( $student->getGrade() <= 12 ){
                            $maybe_failing_school = $zonedSchools['High'];
                        }

                        $maybe_failing_school = $em->getRepository( 'IIABStudentTransferBundle:ADM' )
                            ->findOneBy( [ 'hsvCityName' => $maybe_failing_school ] );

                        if( !empty( $maybe_failing_school ) ){
                            $maybe_failing_school = $maybe_failing_school->getGroupID();
                        }

                        $failing_schools = $this->getSchoolsFromSetting( 'Failing and Former Failing Schools', ( $lastFormData['isRenewal'] == 'renewal' ) );

                        if( empty( $maybe_failing_school )
                            || !in_array($maybe_failing_school->getId(), $failing_schools) ){
                            $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noFailingSchool' , array() , 'IIABStudentTransferBundle' ) );
                            return $this->redirect( $this->generateUrl( 'stw_index' ) );
                        }

                        $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );
                        $step++;
                    }

                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                }
                break;

            case 2:
                $lastFormData = $request->getSession()->has( 'stw-formData' );
                if( $lastFormData !== false )
                    $lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );

                //Perform looking on Student ID HERE.
                $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'dob' => $lastFormData['dob']->format( 'n/j/Y' ) ) );
                $formData['primTelephone'] = preg_replace( '/\D+/' , '' , $lastFormData['primTelephone'] );
                $student->setPrimTelephone( $formData['primTelephone'] );
                if ( isset ( $lastFormData['secTelephone'] ) ) {
                    $formData['secTelephone'] = preg_replace( '/\D+/' , '' , $formData['secTelephone'] );
                    $student->setSecTelephone( $formData['secTelephone'] );
                }
                if ( isset ( $lastFormData['email'] ) ) {
                    $student->setEmail( $lastFormData['email'] );
                }

                $form = $this->createFormBuilder()
                    ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                    ->add( 'last-step' , 'hidden' , array(
                        'attr' => array( 'value' => $step )
                    ) )
                    ->add( 'enrollment' , 'hidden' , array(
                        'attr' => array( 'value' => $lastFormData['enrollment'] )
                    ) )
                    ->add( 'wrongInfo' , 'submit' , array(
                        'label' => $this->container->get( 'translator' )->trans( 'forms.wrongInfo' , array() , 'IIABStudentTransferBundle' ),
                        'attr' => array(
                            'class' => 'button button-caution'
                        )
                    ) )
                    ->add( 'correctInfo' , 'submit' , array(
                        'label' => $this->container->get( 'translator' )->trans( 'forms.correctInfo' , array() , 'IIABStudentTransferBundle' ),
                        'attr' => array(
                            'class' => 'button button-action'
                        )
                    ) )
                    ->getForm();

                $form->handleRequest( $request );

                //Do not plus one 99 (PreSchools) because it will be greater than 12
                if( $student->getGrade() != 99 && $student->getGrade()+1 > 12 ) {
                    $form->remove( 'correctInfo' );
                    $form->remove( 'wrongInfo' );
                    $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'errors.graduatingStudent' , array() , 'IIABStudentTransferBundle' ) );
                }

                $textFields = array();
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getStudentID() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.name' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getFirstName() . ' ' . $student->getLastName() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getDob() );

                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getPrimTelephone() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.secTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getSecTelephone() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getEmail() );

                $race = $em->getRepository( 'IIABStudentTransferBundle:Race' )->find( $student->getRace() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.race' , array() , 'IIABStudentTransferBundle' ) , 'value' => $race->getRace() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentGrade' , array( '%current%' => '('.$currentSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $student->getGrade() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getSchool() );
                if( $student->getGrade()+1 <= 12 ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $student->getGrade()+1 );
                }
                if( $student->getGrade() == 99 ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => '00' );
                }
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.address' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getAddress() . '<br />' . $student->getCity() . '<br />' . $student->getZip() );

                if( $form->isValid() ) {
                    $formData = $form->getData();

                    //Perform looking on Student ID HERE.
                    $formData = array_merge( $lastFormData , $formData );

                    if( $form->get( 'wrongInfo' )->isClicked() ) {
                        //User clicked the button because the information is incorrect.
                        $this->recordAudit( 18 , 0 , $student->getStudentID(), $request );
                        return $this->redirect( $this->generateUrl( 'stw_incorrect_info' ) );
                    }

                    if( $student == NULL ) {
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
                    } else {
                        $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );
                        $step++;
                    }
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                }
                break;

            case 3:
                //get last form data
                $lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );

                /**
                 * To make sure the forward and backs are setup correctly.
                 * This will help keep people from jump around on the forms URLs.
                 * AKA. the Cheaters.
                 * Redirect back to step 1 of the form.
                 */
                if( $lastFormData['last-step'] != 2 ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
                }
                //find student in database and find available schools to transfer to for their next year grade
                $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( [
                    'studentID' => $lastFormData['studentID'] ,
                    'dob' => $lastFormData['dob']->format( 'n/j/Y' )
                ] );
                $student->setPrimTelephone( $lastFormData['primTelephone'] );
                $student->setSecTelephone( $lastFormData['secTelephone'] );
                $student->setEmail( $lastFormData['email'] );

                $openEnrollments = $this->getOpenEnrollmentPeriod( $lastFormData['enrollment'] );
                $getAvailableSchoolCommand = new GetAvailableSchoolCommand();
                $getAvailableSchoolCommand->setContainer( $this->container );
                $all_schools = $getAvailableSchoolCommand->getAvailableSchools( $student , false , $openEnrollments->getId() );

                $schools = $this->limitSchoolsBySetting( $all_schools, 'Schools Accepting Acountability Act Transfers', ( $lastFormData['isRenewal'] == 'renewal' ) );
                $schools = array_flip( $schools );

                if( $schools ) {
                    //if schools found, show form
                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->add( 'enrollment' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['enrollment'] )
                        ) )

                        ->add( 'transfer_option', 'choice', [
                            'expanded' => true,
                            'choices' => [
                                //'Remain at '. $student->getSchool() => 1,
                                'Transfer to a comparable school that is not included on the annual list of “failing schools” within the same local school system that has available space and is willing to accept the student.' => 2,
                                //'Transfer to a comparable school that is not included on the annual list of “failing schools” within another Alabama local school system that has available space and is willing to accept the student.' => 3,
                                'Transfer to a qualifying non-public Alabama school that is willing to accept the student.' => 4,
                            ],
                            'constraints' => $cannotBeBlank,
                        ])

                        ->add( 'choice1' , 'choice' , array(
                            'choices' => $schools,
                            'expanded' => false,
                            'placeholder' => 'forms.selectOption',
                            'label' => $this->get( 'translator' )->trans( 'forms.choice1' , array() , 'IIABStudentTransferBundle' ),
                            'constraints' => $cannotBeBlank,
                        ) )
                        ->add( 'proceedOption' , 'submit' , array(
                            'label' => $this->container->get( 'translator' )->trans( 'forms.selectedSchools' , array() , 'IIABStudentTransferBundle' ),
                            'attr' => array(
                                'class' => 'button button-action'
                            )
                        ) )
                        ->getForm();
                } else {
                    // If no schools with available slots are found, call a notice to inform the end-user.
                    $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'errors.noAvailability' , array() , 'IIABStudentTransferBundle' ) );
                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->getForm()
                    ;
                }

                $form->handleRequest( $request );

                if( $form->isValid() ) {
                    $formData = $form->getData();

                    $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'dob' => $lastFormData['dob']->format( 'n/j/Y' ) ) );
                    $openEnrollments = $this->getOpenEnrollmentPeriod( $lastFormData['enrollment'] );
                    if( $openEnrollments && $student != null && empty( $formData['choice3'] ) ) {

                        $submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
                            ->find( 13 );

                        $success_url = 'stw_success';

                        if( $formData['transfer_option'] == 2 ){
                            $firstChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )
                                ->find( $formData['choice1'] );
                        } else {
                            $firstChoice = null;
                            $submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
                                ->find( 12 );

                            $success_url = 'stw_transfer';
                        }

                        $form = $em->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array(
                            'route' => $request->get('_route')
                        ) );

                        $submission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
                            'studentID' => $student->getStudentID(),
                            'formID'    => $form,
                            'enrollmentPeriod'  => $openEnrollments,
                        ) );

                        $lotteryNumber = new Lottery();
                        $lotteryNumber = $lotteryNumber->getLotteryNumber( $this->getDoctrine() );

                        if( $submission == null ) {

                            ///** @var \IIAB\StudentTransferBundle\Entity\AddressBound $addressBound */
                            //$getSchools = $this->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );

                            //$getSchools = new GetZonedSchoolsCommand();
                            //$getSchools->setContainer( $this->container );
                            //$zonedSchools = $getSchools->getSchools( $student , $request );
                            //$getSchools = null;
                            //$request->getSession()->remove( 'error' );

                            $zonedSchools = $this->container->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );
                            if( $zonedSchools == false ) {
                                $zonedSchools = array();
                            }

                            $race = $em->getRepository( 'IIABStudentTransferBundle:Race' )->find( $student->getRace() );

                            $submission = new Submission();
                            $submission->setSubmissionDateTime( new \DateTime() );
                            $submission->setEnrollmentPeriod( $openEnrollments );
                            $submission->setFormID( $form );
                            $submission->setLotteryNumber( $lotteryNumber );
                            $submission->setFirstChoice( $firstChoice );
                            //$submission->setSecondChoice( $firstChoice );
                            // If our Student ID is a temporary ID, empty it so that it is not stored in the DB.
                            ( substr( $student->getStudentID() , 0 , 2 ) == "TS" ) ? $submission->setStudentID( '' ) : $submission->setStudentID( $student->getStudentID() );
                            $submission->setLastName( $student->getLastName() );
                            $submission->setFirstName( $student->getFirstName() );
                            $submission->setDob( $student->getDob() );
                            $submission->setAddress( $student->getAddress() );
                            $submission->setCity( $student->getCity() );
                            $submission->setZip( $student->getZip() );
                            $submission->setPrimTelephone( $student->getPrimTelephone() );
                            $submission->setSecTelephone( $student->getSecTelephone() );
                            $submission->setEmail( $student->getEmail() );
                            $submission->setGrade( $student->getGrade() );
                            $submission->setSubmissionStatus( $submissionsStatus );
                            $submission->setHsvZonedSchools( $zonedSchools );
                            $submission->setCurrentSchool( $student->getSchool() );
                            $submission->setRace( $race );

                            $em->persist( $submission );

                            if( isset( $lastFormData['isRenewal'] ) ){

                                $isRenewal = new SubmissionData();
                                $isRenewal->setMetaKey('is_renewal');
                                $isRenewal->setMetaValue( $lastFormData['isRenewal'] );
                                $isRenewal->setSubmission( $submission );

                                $submission->addSubmissionDatum( $isRenewal );
                                $em->persist( $isRenewal );
                             }

                            $transfer_option = new SubmissionData();
                            $transfer_option->setSubmission( $submission );
                            $transfer_option->setMetaKey( 'transfer_option' );
                            $transfer_option->setMetaValue( $formData['transfer_option'] );
                            $em->persist( $transfer_option );

                            $em->flush();

                            $confirmNumber = sprintf( '%s-%s-%d' , $form->getFormConfirmation() , $openEnrollments->getConfirmationStyle() , $submission->getId() );
                            $submission->setConfirmationNumber( $confirmNumber );

                            $em->persist( $submission );
                            $em->flush();
                            $em->clear();

                            $confirmation = array( 'confirmation' => $confirmNumber );

                            $this->get('stw.email')->sendConfirmationEmail( $submission );

                            $this->recordAudit( 1 , $submission->getId() , $student->getStudentID(), $request );

                            //Erase all other session data and just display the confirmation number.
                            $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $confirmation ) ) );

                            //Send to successful page. ConfirmationNumber is set in the sessions.
                            return $this->redirect( $this->generateUrl( $success_url ) );
                        } else {
                            //Student has already submitted an application.
                            return $this->redirect( $this->generateUrl( 'stw_already_submitted' ) );
                        }
                    }
                }

        }


        return $this->render( '@IIABStudentTransfer/Default/transferAccountability.html.twig' , array( 'form' => $form->createView() , 'step' => ( $step - 1 ) , 'nonFormFields' => $textFields , 'openEnrollment' => $openEnrollment, 'message' => $message ) );

    }
}