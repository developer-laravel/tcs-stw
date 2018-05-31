<?php

namespace IIAB\StudentTransferBundle\Controller;

use IIAB\StudentTransferBundle\Entity\OpenEnrollment;
use IIAB\StudentTransferBundle\Entity\Correspondence;
use IIAB\StudentTransferBundle\Entity\Process;
use IIAB\StudentTransferBundle\Form\CurrentEnrollmentSettingsSelectPeriod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityRepository;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use IIAB\StudentTransferBundle\Form\CurrentEnrollmentSettingsSelectForm;
use IIAB\StudentTransferBundle\Service\CorrespondenceVariablesService;

/**
 * Class EmailFormatController
 * @package IIAB\StudentTransferBundle\Controller
 * @Route("/admin/email-formats/")
 */
class EmailFormatController extends Controller {

	/**
	 * The "accepted" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("accepted/", name="emailformat_accepted2")
	 * @Route("accepted/{period}/", name="emailformat_accepted")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function acceptedAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'accepted' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'accepted' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'accepted',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('accepted');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/accepted.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/accepted/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_accepted' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'accepted','title'=>'Accepted Email' );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

		/**
	 * If an enrollment period ($period) isn't valid or set for the reports page, this action should redirect so as to
	 * force the end user to choose an enrollment period for which to view said reports.
	 *
	 * @Route("select/{source}", name="emailformat_default")
	 * @Route("", name="emailformat_default2")
	 * @param string $source
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfselect.html.twig")
	 * @return array()
	 */
	public function selectAction( $source = 'accepted' ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		$form = $this->createFormBuilder( )
			->add( 'selectPeriod' , 'entity' , [
				'data' => new CurrentEnrollmentSettingsSelectPeriod(),
				'class' => 'IIABStudentTransferBundle:OpenEnrollment' ] )
			->add( 'saveEnrollmentPeriod' , 'submit' , [
				'label' => 'Submit' ,
				'attr' => ['class' => 'btn btn-primary' ] ] )
			->getForm()
			->handleRequest( $request );

		$source = 'emailformat_' . $source;

		if ( $form->isValid( ) ) {
			$data = $form->getData();
			if ( $data['selectPeriod'] ) {
				return $this->redirect( $this->generateUrl( $source , array( 'period' => $data['selectPeriod']->getId() ) ) );
			}
		}
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) ];
	}

		/**
	 * The "confirmation" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("confirmation/", name="emailformat_confirmation2")
	 * @Route("confirmation/{period}/", name="emailformat_confirmation")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function confirmationAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'confirmation' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'confirmation' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'confirmation',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('confirmation');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/confirmation.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/confirmation/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_confirmation' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'confirmation','title'=> 'Confirmation E-Mail' );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

		/**
	 * The "highschool" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("highschool/", name="emailformat_highschool2")
	 * @Route("highschool/{period}/", name="emailformat_highschool")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function highschoolAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'highschool' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'highschool' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'highschool',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('highschool');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/highSchool.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/highschool/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_highschool' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'highschool' ,'title'=> 'High School E-Mail'  );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

	/**
	 * The "placementcompleted" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("placementcompleted/", name="emailformat_placementcompleted2")
	 * @Route("placementcompleted/{period}/", name="emailformat_placementcompleted")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function placementcompletedAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'placementcompleted' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'placementcompleted' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'placementcompleted',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('placementcompleted');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/placementCompleted.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/placementcompleted/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_placementcompleted' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'placementcompleted' ,'title'=> 'Placement Completed E-Mail'  );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}


	/**
	 * The "transferpersonnel" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("transferpersonnel/", name="emailformat_transferpersonnel2")
	 * @Route("transferpersonnel/{period}/", name="emailformat_transferpersonnel")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function transferpersonnelAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transferpersonnel' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transferpersonnel' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'transferpersonnel',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('transferpersonnel');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Confirmation/transferPersonnel.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/transferpersonnel/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_transferpersonnel' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'transferpersonnel' ,'title'=> 'Personel School Transfer E-Mail'  );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

		/**
	 * The "transfersenior" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("transfersenior/", name="emailformat_transfersenior2")
	 * @Route("transfersenior/{period}/", name="emailformat_transfersenior")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function transferseniorAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transfersenior' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transfersenior' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'transfersenior',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('transfersenior');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Confirmation/transferSenior.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/transfersenior/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_transfersenior' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'transfersenior' ,'title'=> 'Senior School Transfer E-Mail'  );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}


		/**
	 * The "transferschoolchoice" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("transferschoolchoice/", name="emailformat_transferschoolchoice2")
	 * @Route("transferschoolchoice/{period}/", name="emailformat_transferschoolchoice")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function transferschoolchoiceAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transferschoolchoice' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transferschoolchoice' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'transferschoolchoice',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('transferschoolchoice');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Confirmation/transferSchoolChoice.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/transferschoolchoice/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_transferschoolchoice' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'transferschoolchoice' ,'title'=> 'School Choice Transfer E-Mail'  );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

		/**
	 * The "transfersuccessprep" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("transfersuccessprep/", name="emailformat_transfersuccessprep2")
	 * @Route("transfersuccessprep/{period}/", name="emailformat_transfersuccessprep")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function transfersuccessprepAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transfersuccessprep' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transfersuccessprep' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'transfersuccessprep',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('transfersuccessprep');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Confirmation/transferSuccessPrep.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/transfersuccessprep/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_transfersuccessprep' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'transfersuccessprep' ,'title'=> 'Schools Success Prep Application Confirmation E-Mail'  );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

		/**
	 * The "transferaccountability" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("transferaccountability/", name="emailformat_transferaccountability2")
	 * @Route("transferaccountability/{period}/", name="emailformat_transferaccountability")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailFormat:emailformat.html.twig")
	 * @return array()
	 */
	public function transferaccountabilityAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transferaccountability' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailformat_default' , array( 'source' => 'transferaccountability' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'transferaccountability',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('transferaccountability');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Confirmation/transferAccountabilityActOption4.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		
		$form = $this->createFormBuilder(null, [ 'attr' => [ ] ] )
			->add( 'emailSubject', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlock['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtml', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlock['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/transferaccountability/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			}

			
			return $this->redirect( $this->generateUrl( 'emailformat_transferaccountability' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'transferaccountability' ,'title'=> 'Accountability Schools Transfer Application Confirmation E-Mail'  );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

}