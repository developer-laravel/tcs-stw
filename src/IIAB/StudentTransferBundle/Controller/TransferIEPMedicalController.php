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

class TransferIEPMedicalController extends Controller {
    use TransferControllerTraits;

    /**
     * Handles the Medical Transfers
     *
     * @param Request $request
     * @param int     $step
     *
     * @Route( "/iep/step/{step}" , name="stw_iep" , requirements={ "step" = "\d+" } )
     * @return \Symfony\Component\HttpFoundation\Response;
     */
    public function IEPMedicalTransferAction( Request $request , $step ){
        $em = $this->getDoctrine()->getManager();
        $textFields = '';
        $formType = '';

        // Custom error messages for non-HTML5, non-JS validation.
        $emptyStudent = new NotBlank();
        $emptyStudent->message = 'Please provide a valid State ID';
        $cannotBeBlank = new NotBlank();
        $cannotBeBlank->message = 'Cannot be blank';

        $lastFormData = $request->getSession()->has( 'stw-formData' );
        if( $lastFormData !== false ) {
            $lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );
        }
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
            'settingName' => 'iep med'
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
                    ->add( 'studentStatus' , 'choice' , array(
                        'label' => $this->get('translator')->trans( 'forms.status' , array() , 'IIABStudentTransferBundle' ),
                        'choices' => array_flip ( [
                            'currentStudent' => $this->get('translator')->trans( 'forms.currentStudent' , array() , 'IIABStudentTransferBundle' ),
                            'currentKindergarten' => $this->get('translator')->trans( 'forms.currentKindergarten' , array() , 'IIABStudentTransferBundle' ),
                            'newStudent' => $this->get('translator')->trans( 'forms.newStudent' , array() , 'IIABStudentTransferBundle' ) ] )
                    ) )
                    ->add( 'submitAndNext' , 'submit' , array(
                        'label' => $this->get('translator')->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' )
                    ) )
                    ->getForm();
                $form->handleRequest( $request );

                if( $form->isValid() ) {

                    $formData = $form->getData();

                    $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );

                    $step++;
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                }
                break;

            case 2:

                /**
                 * No Session Form data, means they just landed on the website without submitting a form.
                 * Redirect back to step 1 of the form.
                 */
                if( $lastFormData === false ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( 'stw_index' , array( 'step' => 1 ) ) );
                }

                if( $lastFormData['studentStatus'] == 'currentStudent' ) {

                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->add( 'enrollment' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['enrollment'] )
                        ) )
                        ->add( 'studentStatus' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['studentStatus'] )
                        ) )
                        ->add( 'studentID' , 'text' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ),
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'dob' , 'birthday' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ),
                            'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ),
                            'constraints' => $cannotBeBlank
                        ) )

                        ->add( 'isRenewal' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.isRenewal' , array() , 'IIABStudentTransferBundle' ),
                            'placeholder' => 'Please select option',
                            'choices' => array_flip ( [
                                'initial' => $this->get('translator')->trans( 'forms.initial' , array() , 'IIABStudentTransferBundle' ),
                                'renewal' => $this->get('translator')->trans( 'forms.renewal' , array() , 'IIABStudentTransferBundle' ), ] )
                        ) )

                        ->add( 'iepOrMedical' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.iepOrMedical' , array() , 'IIABStudentTransferBundle' ),
                            'placeholder' => 'Please select option',
                            'choices' => array_flip ( [
                                'initial' => $this->get('translator')->trans( 'forms.iep' , array() , 'IIABStudentTransferBundle' ),
                                'renewal' => $this->get('translator')->trans( 'forms.medical' , array() , 'IIABStudentTransferBundle' ), ] )
                        ) )

                        ->add( 'primTelephone' , 'text' , array (
                            'label' => $this->get('translator')->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ),
                            'data' => isset( $lastFormData['primTelephone'] ) ? $lastFormData['primTelephone'] : '',
                            'constraints' => $cannotBeBlank
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
                            'first_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) ),
                            'second_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.emailRepeat' , array() , 'IIABStudentTransferBundle' ) )
                        ) )
                        ->add( 'submitAndNext' , 'submit' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' ),
                            'attr' => array(
                                'class' => 'button button-action'
                            )
                        ) )
                        ->getForm();
                } else {

                    $grades = array( '99' => 'Pre-K' , '00' => 'K');
                    foreach( range( 1 , 12 ) as $grade ) {
                        $newGrade = sprintf( '%1$02d' , $grade );
                        $grades[$newGrade] = $newGrade;
                    }


                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->add( 'studentStatus' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['studentStatus'] )
                        ) )
                        ->add( 'enrollment' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['enrollment'] )
                        ) )
                        ->add( 'studentFirstName' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.studentFirstName' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['studentFirstName'] ) ? $lastFormData['studentFirstName'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'studentLastName' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.studentLastName' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['studentLastName'] ) ? $lastFormData['studentLastName'] : '',
                            'constraints' => $cannotBeBlank
                        ) )

                        ->add( 'iepOrMedical' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.iepOrMedical' , array() , 'IIABStudentTransferBundle' ),
                            'placeholder' => 'Please select option',
                            'choices' => array_flip ( [
                                'iep' => $this->get('translator')->trans( 'forms.iep' , array() , 'IIABStudentTransferBundle' ),
                                'medical' => $this->get('translator')->trans( 'forms.medical' , array() , 'IIABStudentTransferBundle' ), ] )
                        ) )

                        ->add( 'address' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.address' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['address'] ) ? $lastFormData['address'] : '',
                            'constraints' => array(
                                new NotBlank(),
                                new ValidAddress()
                            )
                        ) )
                        ->add( 'city' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.city' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['city'] ) ? $lastFormData['city'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'state' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.state' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['state'] ) ? $lastFormData['state'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'zip' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.zip' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['zip'] ) ? $lastFormData['zip'] : '',
                            'constraints' => $cannotBeBlank
                        ) )

                        ->add( 'primTelephone' , 'text' , array (
                            'label' => $this->get('translator')->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ),
                            'data' => isset( $lastFormData['primTelephone'] ) ? $lastFormData['primTelephone'] : '',
                            'constraints' => $cannotBeBlank
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
                            'first_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) ),
                            'second_options' => array ( 'label' => $this->get( 'translator' )->trans( 'forms.emailRepeat' , array() , 'IIABStudentTransferBundle' ) )
                        ) )

                        ->add( 'dob' , 'birthday' , array(
                            'label' => $this->get('translator')->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['dob'] ) ? new \DateTime( $lastFormData['dob']->date ) : null,
                            'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ),
                            'constraints' => array(
                                new ValidAge()
                            )
                        ) )
                        ->add( 'race' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.race' , array() , 'IIABStudentTransferBundle' ) ,
                            'choices' => $this->getRaceOptions() ,
                            'data' => isset( $lastFormData['race'] ) ? $lastFormData['race'] : '',
                            'constraints' => $cannotBeBlank
                        ) )

                        ->add( 'currentSchool' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['currentSchool'] ) ? $lastFormData['currentSchool'] : ''
                        ) )

                        ->add( 'currentGrade' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.currentGrade' , array() , 'IIABStudentTransferBundle' ) ,
                            'choices' => $grades,
                            'data' => isset( $lastFormData['currentGrade'] ) ? $lastFormData['currentGrade'] : '',
                        ) )
                        ->add( 'confirmStatus' , 'checkbox' , array(
                            'label' => 'form.field.confirm_status',
                            'required' => true,
                            'label_attr' => ['style' => 'width: auto; display: inline;'],
                            'constraints' => array(
                                new NotBlank()
                            ),
                        ) )

                        ->add( 'submitAndNext' , 'submit' , array(
                            'label' => $this->get( 'translator' )->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' ),
                            'attr' => array(
                                'class' => 'button button-action'
                            )
                        ) )
                        ->getForm()
                    ;
                }

                $form->handleRequest( $request );

                if( $form->isValid() ) {
                    $formData = $form->getData();

                    if( isset( $formData['confirmStatus']) && $formData['confirmStatus'] != 1 ){
                        return $this->redirect( $this->generateUrl( 'stw_incorrect_info' ) );
                    }

                    if( isset( $formData['studentID'] ) ) {
                        //Force Upper Case on the studentID
                        $formData['studentID'] = strtoupper( $formData['studentID'] );
                        $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $formData['studentID'] , 'dob' => $formData['dob']->format( 'n/j/Y' ) ) );
                        if( $student != NULL ) {
                            $formData['primTelephone'] = preg_replace( '/\D+/' , '' , $formData['primTelephone'] );
                            $student->setPrimTelephone( $formData['primTelephone'] );
                            //var_dump($student);die;
                            if( isset ( $formData['secTelephone'] ) ) {
                                $formData['secTelephone'] = preg_replace( '/\D+/' , '' , $formData['secTelephone'] );
                                $student->setSecTelephone( $formData['secTelephone'] );
                            }
                            if( isset ( $formData['email'] ) ) {
                                $student->setEmail( $formData['email'] );
                            }
                        }
                    } else {
                        //Create a new student record because the personnel form entering in is not found.
                        $student = new Student();
                        $student->setAddress( $formData['address'] );
                        $student->setCity( $formData['city'] );
                        $student->setDob( $formData['dob']->format( 'n/j/Y' ) );
                        $student->setFirstName( $formData['studentFirstName'] );
                        $student->setLastName( $formData['studentLastName'] );
                        $student->setGrade( $formData['currentGrade'] );
                        if ( $formData['currentSchool'] != NULL) {
                            $student->setSchool( $formData['currentSchool'] );
                        }
                        $student->setRace( $formData['race'] );

                        // Temporary ID is required to traverse steps without losing session data.
                        $student->setStudentID( 'TS' . strtotime( 'now' ) );
                        $student->setZip( $formData['zip'] );

                        $formData['primTelephone'] = preg_replace( '/\D+/' , '' , $formData['primTelephone'] );
                        $student->setPrimTelephone( $formData['primTelephone'] );
                        if ( isset ( $formData['secTelephone'] ) ) {
                            $formData['secTelephone'] = preg_replace( '/\D+/' , '' , $formData['secTelephone'] );
                            $student->setSecTelephone( $formData['secTelephone'] );
                        }
                        if ( isset ( $formData['email'] ) )
                            $student->setEmail( $formData['email'] );
                        $em->persist( $student );
                        $em->flush();
                        $formData['studentID'] = $student->getStudentID();
                    }


                    if( $student == NULL ) {
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
                    } elseif ( $student->getGrade() != 99 && $student->getSchool() == NULL ) {
                        // If the student is not in position to enter Kindergarten, require that a current school be set.
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get( 'translator' )->trans( 'errors.noCurrentSchool' , array() , 'IIABStudentTransferBundle' ) );
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
                if( $lastFormData['last-step'] != 2 && $lastFormData['last-step'] != 3 ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
                }

                //lookup student in database
                $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'dob' => $lastFormData['dob']->format( 'n/j/Y' ) ) );
                $form = $this->createFormBuilder()
                    ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                    ->add( 'last-step' , 'hidden' , array(
                        'attr' => array( 'value' => $step )
                    ) )
                    ->add( 'enrollment' , 'hidden' , array(
                        'attr' => array( 'value' => $lastFormData['enrollment'] )
                    ) )
                    ->add( 'correctInfo' , 'submit' , array(
                        'label' => $this->container->get( 'translator' )->trans( 'forms.correctInfo' , array() , 'IIABStudentTransferBundle' ),
                        'attr' => array(
                            'class' => 'button button-action'
                        )
                    ) );


                if( $lastFormData ['studentStatus'] == 'currentStudent' ) {
                    $form->add( 'wrongInfo' , 'submit' , array(
                        'label' => $this->container->get( 'translator' )->trans( 'forms.wrongInfo' , array() , 'IIABStudentTransferBundle' ) ,
                        'attr' => array(
                            'class' => 'button button-caution'
                        )
                    ) );
                }
                $form = $form->getForm();
                $form->handleRequest( $request );

                //Do not plus one 99 (PreSchools) because it will be greater than 12
                if( $student->getGrade() != 99 && $student->getGrade()+1 > 12 ) {
                    $form->remove( 'correctInfo' );
                    $form->remove( 'wrongInfo' );
                    $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'errors.graduatingStudent' , array() , 'IIABStudentTransferBundle' ) );
                }

                $textFields = array();
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $lastFormData['primTelephone'] );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.secTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $lastFormData['secTelephone'] );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) , 'value' => $lastFormData['email'] );

                if ( $lastFormData['studentStatus'] == 'currentStudent'){
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getStudentID() );

                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.isRenewal' , array() , 'IIABStudentTransferBundle' ) , 'value' => $this->get('translator')->trans( 'forms.'.$lastFormData['isRenewal'] , array() , 'IIABStudentTransferBundle' ) );

                } else {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) , 'value' => $lastFormData['currentSchool'] );
                }
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.name' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getFirstName() . ' ' . $student->getLastName() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getDob() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentGrade' , array( '%current%' => '('.$currentSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $student->getGrade() );
                if( $student->getGrade()+1 <= 12 ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $student->getGrade()+1 );
                }
                if( $student->getGrade() == 99 ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => '00' );
                }
                if ( $student->getSchool() != NULL ) {
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getSchool() );
                }
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.address' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getAddress() . '<br />' . $student->getCity() . '<br />' . $student->getZip() );

                if( $form->isValid() ) {
                    $formData = $form->getData();

                    $formData = array_merge( $lastFormData , $formData );

                    if( $lastFormData ['studentStatus'] == 'currentStudent' ) {
                        if( $form->get( 'wrongInfo' )->isClicked() ) {
                            //User clicked the button because the information is incorrect.
                            $this->recordAudit( 18 , 0 , $student->getStudentID(), $request );
                            return $this->redirect( $this->generateUrl( 'stw_incorrect_info' ) );
                        }
                    }
                    if( $student == NULL ) {
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
                    } else {
                        $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );
                        $step++;
                    }

                    $studentIsDuplicateSubmission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( [
                        'firstName' => $student->getFirstName() ,
                        'lastName' => $student->getLastName() ,
                        'dob' => $formData['dob']->format( 'n/j/Y' ) ,
                        'grade' => $student->getGrade() ,
                        'formID' => 2,
                        'enrollmentPeriod' => $formData['enrollment']
                    ] );

                    if($studentIsDuplicateSubmission){
                        return $this->redirect( $this->generateUrl( 'stw_already_submitted' ) );
                    } else {
                        return $this->redirect($this->generateUrl($request->get('_route'), array('step' => $step)));
                    }
                }
                //}
                break;

            case 4:
                //get last form data
                $lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );

                /**
                 * To make sure the forward and backs are setup correctly.
                 * This will help keep people from jump around on the forms URLs.
                 * AKA. the Cheaters.
                 * Redirect back to step 1 of the form.
                 */
                if( $lastFormData['last-step'] != 3 ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
                }
                //find student in database and find available schools to transfer to for their next year grade
                $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'dob' => $lastFormData['dob']->format( 'n/j/Y' ) ) );
                $student->setPrimTelephone( $lastFormData['primTelephone'] );
                $student->setSecTelephone( $lastFormData['secTelephone'] );
                $student->setEmail( $lastFormData['email'] );

                $openEnrollments = $this->getOpenEnrollmentPeriod( $lastFormData['enrollment'] );
                $getAvailableSchoolCommand = new GetAvailableSchoolCommand();
                $getAvailableSchoolCommand->setContainer( $this->container );

                $zonedSchools = $this->container->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );
                if( $zonedSchools == false ) {
                    $schools = [];
                } else {
                    $schools = $getAvailableSchoolCommand->getAvailableSchools( $student , false , $openEnrollments->getId() );
                }

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
                        ->add( 'choice1' , 'choice' , array(
                            'choices' => $schools,
                            'expanded' => false,
                            'empty_data' => $this->get( 'translator' )->trans( 'forms.selectOption' , array() , 'IIABStudentTransferBundle' ),
                            'label' => $this->get( 'translator' )->trans( 'forms.choice1' , array() , 'IIABStudentTransferBundle' ),
                            'constraints' => $cannotBeBlank
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
                            ->find( 1 );

                        $firstChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )
                            ->find( $formData['choice1'] );

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

                            $race = $em->getRepository( 'IIABStudentTransferBundle:Race' )->findOneBy( array(
                                'race' => $student->getRace(),
                            ) );

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

                            if( isset( $lastFormData['isRenewal'] ) ){

                                $isRenewal = new SubmissionData();
                                $isRenewal->setMetaKey('is_renewal');
                                $isRenewal->setMetaValue( $lastFormData['isRenewal'] );
                                $isRenewal->setSubmission( $submission );

                                $submission->addSubmissionDatum( $isRenewal );
                                $em->persist( $isRenewal );
                            }

                            if( isset( $lastFormData['iepOrMedical'] ) ){

                                $iepOrMedical = new SubmissionData();
                                $iepOrMedical->setMetaKey('is_iep_medical');
                                $iepOrMedical->setMetaValue( $lastFormData['iepOrMedical'] );
                                $iepOrMedical->setSubmission( $submission );

                                $submission->addSubmissionDatum( $iepOrMedical );
                                $em->persist( $iepOrMedical );
                            }

                            $em->persist( $submission );
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
                            return $this->redirect( $this->generateUrl( 'stw_success' ) );
                        } else {
                            //Student has already submitted an application.
                            return $this->redirect( $this->generateUrl( 'stw_already_submitted' ) );
                        }
                    }
                }
                break;
        }


        return $this->render( '@IIABStudentTransfer/Default/transferIEP.html.twig' , array(
            'form' => $form->createView() ,
            'step' => ( $step - 1 ) ,
            'nonFormFields' => $textFields ,
            'openEnrollment' => $openEnrollment,
            'message' => $message
        ) );
    }
}