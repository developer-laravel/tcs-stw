<?php

namespace IIAB\StudentTransferBundle\Controller;


use IIAB\StudentTransferBundle\Entity\Submission;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sonata\AdminBundle\Controller\CRUDController;
use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Command\CheckMinorityCommand;
use IIAB\StudentTransferBundle\Command\GetAvailableSchoolCommand;

class LotteryCRUDController extends CRUDController {

	public function createAction() {
		//code here
		// the key used to lookup the template
		$templateKey = 'edit';
		$request = $this->get('request_stack')->getCurrentRequest();

		if( false === $this->admin->isGranted( 'CREATE' ) ) {
			throw new AccessDeniedException();
		}

		$object = $this->admin->getNewInstance();

		$this->admin->setSubject( $object );

		/** @var $form \Symfony\Component\Form\Form */
		$form = $this->admin->getForm();
		$form->setData( $object );

		$form->handleRequest( $request );

			$isFormValid = $form->isValid();

			if( $isFormValid && ( !$this->isInPreviewMode() || $this->isPreviewApproved() ) ) {

				if( false === $this->admin->isGranted( 'CREATE' , $object ) ) {
					throw new AccessDeniedException();
				}
				$lotteryStatus = $this->getDoctrine()
					->getRepository('IIABStudentTransferBundle:LotteryStatus')
					->find(1);

				$object->setLotteryStatus( $lotteryStatus );

				$this->admin->create( $object );

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


		$view = $form->createView();

		// set the theme for the current Admin Form
		$this->get( 'twig' )->getExtension( 'form' )->renderer->setTheme( $view , $this->admin->getFormTheme() );

		return $this->render( $this->admin->getTemplate( $templateKey ) , array(
			'action' => 'create' ,
			'form' => $view ,
			'object' => $object ,
		) );
	}
}