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

class TransferSPEDController extends Controller {
    use TransferControllerTraits;

    /**
     * Handles the Special Education Sibling Transfers
     *
     * @param Request $request
     * @param int     $step
     *
     * @Route( "/sped/step/{step}" , name="stw_sped" , requirements={ "step" = "\d+" } )
     * @return \Symfony\Component\HttpFoundation\Response;
     */
    public function spedTransferAction( Request $request , $step ) {
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
            'settingName' => 'spec ed sibling'
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
                        'data' => isset( $lastFormData['dob'] ) ? date( 'Y-m-d' , strtotime( $lastFormData['dob']->date ) ) : '',
                        'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ),
                        'constraints' => $cannotBeBlank,
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
                    $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $formData['studentID'] , 'dob' => $formData['dob']->format( 'n/j/Y' ) ) );

                    if( $student == NULL ) {
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
                    } else {
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
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.lastName' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.city' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );
                //$textFields[] = array( 'label' => $this->container->get( 'translator' )->trans( 'forms.zip' , array() , 'IIABStudentTransferBundle' ) , 'value' =>  );


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

                /**
                 * No Session Form data, means they just landed on the website without submitting a form.
                 * Redirect back to step 1 of the form.
                 */
                if( $lastFormData === false ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
                }

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

                $form = $this->createFormBuilder()
                    ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                    ->add( 'last-step' , 'hidden' , array(
                        'attr' => array( 'value' => $step )
                    ) )
                    ->add( 'enrollment' , 'hidden' , array(
                        'attr' => array( 'value' => $lastFormData['enrollment'] )
                    ) )
                    ->add( 'siblingStatus' , 'choice' , array(
                        'label' => $this->get('translator')->trans( 'sped.siblingstatus' , array() , 'IIABStudentTransferBundle' ),
                        'choices' => array (
                            'currentStudent'                => $this->get('translator')->trans( 'forms.hsvStudent' , array() , 'IIABStudentTransferBundle' ),
                            'currentKindergartenStudent'    => $this->get('translator')->trans( 'forms.hsvKindergarten' , array() , 'IIABStudentTransferBundle' ),
                            'newStudent'                => $this->get('translator')->trans( 'forms.nonHsvStudent' , array() , 'IIABStudentTransferBundle' ) )
                    ) )
                    ->add( 'submitAndNext' , 'submit' , array(
                        'label' => $this->get('translator')->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' )
                    ) )
                    ->getform();

                $form->handleRequest( $request );

                if( $form->isValid() ) {

                    $formData = $form->getData();
                    $formData = array_merge( $lastFormData , $formData );

                    $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );

                    $step++;
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                }
                break;

            case 4:
                /**
                 * No Session Form data, means they just landed on the website without submitting a form.
                 * Redirect back to step 1 of the form.
                 */
                if( $lastFormData === false ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
                }

                $lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );

                /**
                 * To make sure the forward and backs are setup correctly.
                 * This will help keep people from jump around on the forms URLs.
                 * AKA. the Cheaters.
                 * Redirect back to step 1 of the form.
                 */
                if( $lastFormData['last-step'] != 3 && $lastFormData['last-step'] != 4 ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
                }

                if ( $lastFormData ['siblingStatus'] != 'currentStudent') {
                    $grades = array( '99' => 'Pre-K' );
                    foreach( range( 0 , 12 ) as $grade ) {
                        $newGrade = sprintf( '%1$02d' , $grade );
                        $grades[$newGrade] = $newGrade;
                    }
                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->add( 'enrollment' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['enrollment'] )
                        ) )
                        ->add( 'siblingFirstName' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'sped.siblingFirstName' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['siblingFirstName'] ) ? $lastFormData['siblingFirstName'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'siblingLastName' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'sped.siblingLastName' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['studentLastName'] ) ? $lastFormData['siblingLastName'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'siblingdob' , 'birthday' , array(
                            'label' => $this->get('translator')->trans( 'sped.siblingdob' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['siblingdob'] ) ? date( 'Y-m-d' , strtotime( $lastFormData['siblingdob']->date ) ) : '',
                            'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ),
                            'constraints' => array(
                                new ValidAge()
                            )
                        ) )
                        ->add( 'siblingRace' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'sped.siblingrace' , array() , 'IIABStudentTransferBundle' ),
                            'choices' => $this->getRaceOptions() ,
                        ) )
                        ->add( 'address' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.address' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['siblingAddress'] ) ? $lastFormData['siblingAddress'] : '',
                            'constraints' => array(
                                new ValidAddress()
                            )
                        ) )
                        ->add( 'city' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.city' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['siblingCity'] ) ? $lastFormData['siblingCity'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'zip' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.zip' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['siblingZip'] ) ? $lastFormData['siblingZip'] : '',
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'currentSchool' , 'text' , array(
                            'label' => $this->get('translator')->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['currentSchool'] ) ? $lastFormData['currentSchool'] : ''
                        ) )
                        ->add( 'currentGrade' , 'choice' , array(
                            'label' => $this->get('translator')->trans( 'forms.currentGrade' , array() , 'IIABStudentTransferBundle' ) ,
                            'choices' => $grades,
                            'data' => isset( $lastFormData['currentGrade'] ) ? $lastFormData['currentGrade'] : ''
                        ) )
                        ->add( 'submitAndNext' , 'submit' , array(
                            'label' => $this->get('translator')->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' )
                        ) )
                        ->getForm();
                } else {
                    //This means they are a currentStudent
                    $form = $this->createFormBuilder()
                        ->setAction( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) )
                        ->add( 'last-step' , 'hidden' , array(
                            'attr' => array( 'value' => $step )
                        ) )
                        ->add( 'enrollment' , 'hidden' , array(
                            'attr' => array( 'value' => $lastFormData['enrollment'] )
                        ) )
                        ->add( 'currentSchool' , 'hidden' , array(
                            'attr' => array( 'value' => '' ),
                        ) )
                        ->add( 'siblingID' , 'text' , array(
                            'label' => $this->get( 'translator' )->trans( 'sped.siblingID' , array() , 'IIABStudentTransferBundle' ),
                            'constraints' => $emptyStudent
                        ) )
                        ->add( 'siblingdob' , 'birthday' , array(
                            'label' => $this->get('translator')->trans( 'sped.siblingdob' , array() , 'IIABStudentTransferBundle' ) ,
                            'data' => isset( $lastFormData['siblingdob'] ) ? date( 'Y-m-d' , strtotime( $lastFormData['siblingdob']->date ) ) : '',
                            'years' => range( date( 'Y' , strtotime( '-20 years' ) ) , date( 'Y' ) ),
                            'constraints' => $cannotBeBlank
                        ) )
                        ->add( 'submitAndNext' , 'submit' , array(
                            'label' => $this->get('translator')->trans( 'forms.submitNext' , array() , 'IIABStudentTransferBundle' )
                        ) )
                        ->getForm();
                }

                $form->handleRequest( $request );

                if( $form->isValid() ) {
                    $formData = $form->getData();

                    $formData = array_merge( $lastFormData , $formData );

                    if( isset( $formData['siblingID'] ) ) {
                        $formData['siblingID'] = strtoupper( $formData['siblingID'] );
                        $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $formData['siblingID'] , 'dob' => $formData['siblingdob']->format( 'n/j/Y' ) ) );

                    } else {
                        //Create a new student record because the personnel form entering in is not found.
                        $formData['primTelephone'] = preg_replace( '/\D+/' , '' , $formData['primTelephone'] );
                        $formData['secTelephone'] = preg_replace( '/\D+/' , '' , $formData['secTelephone'] );

                        $student = new Student();
                        $student->setAddress( $formData['address'] );
                        $student->setCity( $formData['city'] );
                        $student->setDob( $formData['siblingdob']->format( 'n/j/Y' ) );
                        $student->setFirstName( $formData['siblingFirstName'] );
                        $student->setLastName( $formData['siblingLastName'] );
                        $student->setPrimTelephone( $formData['primTelephone'] );
                        $student->setSecTelephone( $formData['secTelephone'] );
                        $student->setEmail( $formData['email'] );
                        $student->setGrade( $formData['currentGrade'] );
                        if ( $formData['currentSchool'] != NULL ) {
                            $student->setSchool( $formData['currentSchool'] );
                        }
                        $student->setRace( $formData['siblingRace'] );
                        $student->setStudentID( 'TS' . strtotime( 'now' ) );
                        $student->setZip( $formData['zip'] );

                        $em->persist( $student );
                        $em->flush();

                        $formData['siblingID'] = $student->getStudentID();
                    }

                    $studentIsDuplicateSubmission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( [
                        'firstName' => $student->getFirstName() ,
                        'lastName' => $student->getLastName() ,
                        'dob' => $formData['siblingdob']->format( 'n/j/Y' ) ,
                        'grade' => $student->getGrade() ,
                        'enrollmentPeriod' => $formData['enrollment']
                    ] );

                    if($studentIsDuplicateSubmission){
                        return $this->redirect( $this->generateUrl( 'stw_already_submitted' ) );
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

            case 5:
                //get last form data

                $lastFormData = unserialize( base64_decode( $request->getSession()->get( 'stw-formData' ) ) );

                /**
                 * To make sure the forward and backs are setup correctly.
                 * This will help keep people from jump around on the forms URLs.
                 * AKA. the Cheaters.
                 * Redirect back to step 1 of the form.
                 */
                if( $lastFormData['last-step'] != 4 ) {
                    $request->getSession()->getFlashBag()->add( 'error' , 'You cannot skip to another page. Please start over and submit the form again.' );
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
                }

                //lookup student and sibling in database
                $student = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['studentID'] , 'dob' => $lastFormData['dob']->format( 'n/j/Y' ) ) );
                $sibling = $em->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array( 'studentID' => $lastFormData['siblingID'] , 'dob' => $lastFormData['siblingdob']->format( 'n/j/Y' ) ) );

                $lastFormData['primTelephone'] = preg_replace( '/\D+/' , '' , $lastFormData['primTelephone'] );
                $student->setPrimTelephone( $lastFormData['primTelephone'] );
                if ( isset ( $lastFormData['secTelephone'] ) ) {
                    $lastFormData['secTelephone'] = preg_replace( '/\D+/' , '' , $lastFormData['secTelephone'] );
                    $student->setSecTelephone( $lastFormData['secTelephone'] );
                }
                if ( isset ( $lastFormData['email'] ) ) {
                    $student->setEmail( $lastFormData['email'] );
                }

                $grade = ( ( $sibling->getGrade()+1 <= 12 ) ? $sibling->getGrade()+1 : '00' );

                $openEnrollments = $this->getOpenEnrollmentPeriod( $lastFormData['enrollment'] );
                if( $specialEnrollments && $openEnrollments == NULL ){
                    $openEnrollments = $specialEnrollments[0]->getEnrollmentPeriod();
                }

                //Check to make sure that siblings grade + 1 is available at student->getschool
                $siblingNewSchool = $em->getRepository('IIABStudentTransferBundle:ADM')->createQueryBuilder( 'a' )
                    ->leftJoin( 'a.groupID' , 'g' )
                    ->where( 'a.grade = :grade' )
                    ->andWhere( 'a.enrollmentPeriod = :enrollment' )
                    ->andWhere( 'g.spedAccess = 1' ) //Only display SPED only Schools.
                    ->setParameters( array(
                        'grade'         => $grade,
                        'enrollment'    => $openEnrollments->getId(),
                    ) )
                    ->orderBy('a.hsvCityName','ASC')
                    ->getQuery()
                    ->getArrayResult()
                ;
                //If the above query is empty, then we need to break out.
                if( empty( $siblingNewSchool ) ) {
                    $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'sped.siblingTransferNotFound' , array() , 'IIABStudentTransferBundle' ) );
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => 1 ) ) );
                }
                $schools = array();
                foreach( $siblingNewSchool as $adm ) {
                    $schools[$adm['id']] = $adm['schoolName'];
                }
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
                        'label' => $this->get( 'translator' )->trans( 'forms.selectOption' , array() , 'IIABStudentTransferBundle' ),
                        'constraints' => $cannotBeBlank
                    ) )
                    ->add( 'correctInfo' , 'submit' , array(
                        'label' => $this->container->get( 'translator' )->trans( 'forms.correctInfo' , array() , 'IIABStudentTransferBundle' ),
                        'attr' => array(
                            'class' => 'button button-action'
                        )
                    ) );

                if( $lastFormData ['siblingStatus'] == 'currentStudent' ) {
                    $form->add( 'wrongInfo' , 'submit' , array(
                        'label' => $this->container->get( 'translator' )->trans( 'forms.wrongInfo' , array() , 'IIABStudentTransferBundle' ) ,
                        'attr' => array(
                            'class' => 'button button-caution'
                        )
                    ) );
                }

                $form = $form->getForm();

                //Do not plus one 99 (PreSchools) because it will be greater than 12
                if( $student->getGrade() != 99 && $student->getGrade()+1 > 12 ) {
                    $form->remove( 'correctInfo' );
                    $form->remove( 'wrongInfo' );
                    $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'errors.graduatingStudent' , array() , 'IIABStudentTransferBundle' ) );
                }
                if( $sibling->getGrade() != 99 && $sibling->getGrade()+1 > 12 ) {
                    $form->remove( 'correctInfo' );
                    $form->remove( 'wrongInfo' );
                    $request->getSession()->getFlashBag()->add( 'notice' , $this->get('translator')->trans( 'errors.graduatingStudent' , array() , 'IIABStudentTransferBundle' ) );
                }

                $textFields = array();
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'sped.siblingFirstName' , array() , 'IIABStudentTransferBundle' ) , 'value' => $sibling->getFirstName() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'sped.siblingLastName' , array() , 'IIABStudentTransferBundle' ) , 'value' => $sibling->getLastName() );

                if ( $lastFormData['siblingStatus'] == 'currentStudent')
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.studentID' , array() , 'IIABStudentTransferBundle' ) , 'value' => $sibling->getStudentID() );
                else
                    $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentSchool' , array() , 'IIABStudentTransferBundle' ) , 'value' => $lastFormData['currentSchool'] );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.name' , array() , 'IIABStudentTransferBundle' ) , 'value' => $sibling->getFirstName() . ' ' . $student->getLastName() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.dob' , array() , 'IIABStudentTransferBundle' ) , 'value' => $sibling->getDob() );

                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.primTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getPrimTelephone() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.secTelephone' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getSecTelephone () );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.email' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getEmail() );

                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.currentGrade' , array( '%current%' => '('.$currentSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $sibling->getGrade() );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.nextGrade' , array( '%next%' => '('.$nextSchoolYearText->getSettingValue().')' ) , 'IIABStudentTransferBundle' ) , 'value' => $grade );
                $textFields[] = array( 'label' => $this->get( 'translator' )->trans( 'forms.address' , array() , 'IIABStudentTransferBundle' ) , 'value' => $student->getAddress() . '<br />' . $student->getCity() . '<br />' . $student->getZip() );

                $form->handleRequest( $request );
                if( $form->isValid() ) {
                    $formData = $form->getData();

                    $formData = array_merge( $lastFormData , $formData );

                    if( $lastFormData ['siblingStatus'] == 'currentStudent' ) {
                        if( $form->get( 'wrongInfo' )->isClicked() ) {
                            //User clicked the button because the information is incorrect.
                            $this->recordAudit( 18 , 0 , $sibling->getStudentID(), $request );
                            return $this->redirect( $this->generateUrl( 'stw_incorrect_info' ) );
                        }
                    }

                    $siblingNewSchool = $em->getRepository('IIABStudentTransferBundle:ADM')->find( $formData['choice1'] );

                    if( $student == NULL || $sibling == NULL || $siblingNewSchool == NULL ) {
                        $request->getSession()->getFlashBag()->add( 'error' , $this->get('translator')->trans( 'errors.noStudentFound' , array() , 'IIABStudentTransferBundle' ) );
                    } else {

                        if( $openEnrollments && $student->getSchool() != NULL ) {

                            $submissionsStatus = $em->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )
                                ->find( 1 );

                            $firstChoice = $em->getRepository( 'IIABStudentTransferBundle:ADM' )
                                ->find( $siblingNewSchool->getId() );

                            $form = $em->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array(
                                'route' => $request->get('_route')
                            ) );

                            $submission = $em->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
                                'studentID' => $sibling->getStudentID(),
                                'formID'    => $form,
                                'enrollmentPeriod'  => $openEnrollments,
                            ) );

                            $lotteryNumber = new Lottery();
                            $lotteryNumber = $lotteryNumber->getLotteryNumber( $this->getDoctrine() );

                            if( $submission == null ) {

                                $getSchools = new GetZonedSchoolsCommand();
                                $getSchools->setContainer( $this->container );

                                // Use Address Bounds table for zoning
                                $zonedSchools = $this->container->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );

                                // Use HSV City Zoning Website for zoning
                                //$zonedSchools = $getSchools->getSchools( $student , $request );
                                //$getSchools = null;
                                //$request->getSession()->remove( 'error' );
                                //$discard = $request->getSession()->getFlashBag()->get( 'error' );

                                $race = $em->getRepository( 'IIABStudentTransferBundle:Race' )->findOneBy( array(
                                    'race' => $student->getRace(),
                                ) );

                                $submission = new Submission();
                                $submission->setSubmissionDateTime( new \DateTime() );
                                $submission->setEnrollmentPeriod( $openEnrollments );
                                $submission->setFormID( $form );
                                $submission->setLotteryNumber( $lotteryNumber );
                                $submission->setFirstChoice( $firstChoice );
                                $submission->setSecondChoice( $firstChoice );

                                // If our Student ID is a temporary ID, empty it so that it is not stored in the DB.
                                ( substr( $student->getStudentID() , 0 , 2 ) == "TS" ) ? $submission->setStudentID( '' ) : $submission->setStudentID( $student->getStudentID() );

                                $submission->setLastName( $sibling->getLastName() );
                                $submission->setFirstName( $sibling->getFirstName() );
                                $submission->setDob( $sibling->getDob() );
                                $submission->setPrimTelephone( $sibling->getPrimTelephone() );
                                $submission->setSecTelephone( $sibling->getSecTelephone() );
                                $submission->setEmail( $sibling->getEmail() );
                                $submission->setAddress( $sibling->getAddress() );
                                $submission->setCity( $sibling->getCity() );
                                $submission->setZip( $sibling->getZip() );
                                $submission->setGrade( $sibling->getGrade() );
                                $submission->setSubmissionStatus( $submissionsStatus );
                                $submission->setHsvZonedSchools( $zonedSchools );
                                $submission->setCurrentSchool( $sibling->getSchool() );
                                $submission->setRace( $race );

                                $em->persist( $submission );
                                $em->flush();

                                $confirmNumber = sprintf( '%s-%s-%d' , $form->getFormConfirmation() , $openEnrollments->getConfirmationStyle() , $submission->getId() );
                                $submission->setConfirmationNumber( $confirmNumber );

                                $em->persist( $submission );
                                $em->flush();

                                //Add in handling sibling information here like employee for personnel transfers
                                $submissionData1 = new SubmissionData();
                                $submissionData1->setMetaKey('SPED Student ID');
                                $submissionData1->setMetaValue( $student->getStudentID() );
                                $submissionData1->setSubmission( $submission );
                                $em->persist( $submissionData1 );

                                $submissionData2 = new SubmissionData();
                                $submissionData2->setMetaKey('SPED Current School');
                                $submissionData2->setMetaValue( $student->getSchool() );
                                $submissionData2->setSubmission( $submission );
                                $em->persist( $submissionData2 );

                                $submissionData3 = new SubmissionData();
                                $submissionData3->setMetaKey('SPED Current Grade');
                                $submissionData3->setMetaValue( $student->getGrade() );
                                $submissionData3->setSubmission( $submission );
                                $em->persist( $submissionData3 );

                                $submissionData4 = new SubmissionData();
                                $submissionData4->setMetaKey('Sibling Status');
                                $submissionData4->setMetaValue( $lastFormData['siblingStatus'] );
                                $submissionData4->setSubmission( $submission );
                                $em->persist( $submissionData4 );

                                $em->flush();
                                $em->clear();
                                /*$submission->setEmployeeID( $lastFormData['employeeID'] );
                                $submission->setEmployeeFirstName( $lastFormData['employeeFirstName'] );
                                $submission->setEmployeeLastName( $lastFormData['employeeLastName'] );
                                $submission->setEmployeeLocation( $lastFormData['employeeLocation'] );*/

                                $confirmation = array( 'confirmation' => $confirmNumber );

                                $this->recordAudit( 1 , $submission->getId() , $sibling->getStudentID(), $request );

                                //Erase all other session data and just display the confirmation number.
                                $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $confirmation ) ) );

                                //Send to successful page. ConfirmationNumber is set in the sessions.
                                return $this->redirect( $this->generateUrl( 'stw_success' ) );
                            } else {
                                //Student has already submitted an application.
                                return $this->redirect( $this->generateUrl( 'stw_already_submitted' ) );
                            }
                        }


                        $request->getSession()->set( 'stw-formData' , base64_encode( serialize( $formData ) ) );
                        $step++;
                    }
                    return $this->redirect( $this->generateUrl( $request->get('_route') , array( 'step' => $step ) ) );
                }
                break;
        }


        return $this->render( '@IIABStudentTransfer/Default/transferSPED.html.twig' , array( 'form' => $form->createView() , 'step' => ( $step - 1 ) , 'nonFormFields' => $textFields , 'openEnrollment' => $openEnrollment, 'message' => $message ) );

    }
}