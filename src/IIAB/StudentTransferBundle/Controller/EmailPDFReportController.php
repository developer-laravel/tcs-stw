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
 * Class EmailPDFReportController
 * @package IIAB\StudentTransferBundle\Controller
 * @Route("/admin/email-pdf-reports/")
 */
class EmailPDFReportController extends Controller {

	/**
	 * The "awarded" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("awarded/", name="emailpdfreport_awarded2")
	 * @Route("awarded/{period}/", name="emailpdfreport_awarded")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfawarded.html.twig")
	 * @return array()
	 */
	public function awardedAction( $period = 0 ) {

        $admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

            return $this->redirect( $this->generateUrl( 'emailpdfreport_default' , array( 'source' => 'awarded' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

        if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailpdfreport_default' , array( 'source' => 'awarded' )) );
		}
		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		$dynamicVariables = CorrespondenceVariablesService::getDynamicVariables();

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'awarded',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('awarded');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/awarded.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		// Setup Email Templates
		$emailCorrespondenceNonM2M = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'awardedNonM2M',
			'type' => 'email'
		) );

		if($emailCorrespondenceNonM2M == null) {
			$emailCorrespondenceNonM2M = new Correspondence();
			$emailCorrespondenceNonM2M->setName('awardedNonM2M');
			$emailCorrespondenceNonM2M->setType('email');
			$emailCorrespondenceNonM2M->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/awardedNonM2M.email.twig'));
			$emailCorrespondenceNonM2M->setActive(1);
			$emailCorrespondenceNonM2M->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlockNonM2M = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondenceNonM2M->getTemplate());

		// Setup PDF Letter Templates
		$letterCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'awarded',
			'type' => 'letter'
		) );

		if($letterCorrespondence == null) {
			$letterCorrespondence = new Correspondence();
			$letterCorrespondence->setName('awarded');
			$letterCorrespondence->setType('letter');
			$letterCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Report/awardedLetter.html.twig'));
			$letterCorrespondence->setActive(1);
			$letterCorrespondence->setLastUpdateDateTime(new \DateTime());
		}

		$letterCorrespondenceNonM2M = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'awardedNonM2M',
			'type' => 'letter'
		) );

		if($letterCorrespondenceNonM2M == null) {
			$letterCorrespondenceNonM2M = new Correspondence();
			$letterCorrespondenceNonM2M->setName('awardedNonM2M');
			$letterCorrespondenceNonM2M->setType('letter');
			$letterCorrespondenceNonM2M->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Report/awardedLetterNonM2M.html.twig'));
			$letterCorrespondenceNonM2M->setActive(1);
			$letterCorrespondenceNonM2M->setLastUpdateDateTime(new \DateTime());
		}

		$form = $this->createFormBuilder(null, [ 'attr' => [ 'data-dynamic' => json_encode( $dynamicVariables ) ] ] )
			->add( 'selectForm_letter' , 'entity' , [
					'data' => new CurrentEnrollmentSettingsSelectForm(),
					'class' => 'IIABStudentTransferBundle:Form',
					'required' => false,
					'placeholder' => 'All Forms',
					'label' => 'Form Type'
				]
			)
			->add( 'selectForm_email' , 'entity' , [
					'data' => new CurrentEnrollmentSettingsSelectForm(),
					'class' => 'IIABStudentTransferBundle:Form',
					'required' => false,
					'placeholder' => 'All Forms',
					'label' => 'Form Type'
				]
			)

			// ->add( 'letterTemplate', CKEditorType::class, array(
			// 	'label' => 'M2M Letter',
			// 	'data' => $letterCorrespondence->getTemplate(),
			// ))
			->add( 'letterTemplateNonM2M', CKEditorType::class, array(
				'label' => 'Letter',
				'data' => $letterCorrespondenceNonM2M->getTemplate(),
			))
			->add( 'saveLetterChanges' , 'submit' , array(
				'label' => 'Save PDF Letter Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )

			// ->add( 'emailSubject', CKEditorType::class, array(
			// 	'label' => 'M2M Email Subject',
			// 	'data' => $emailBlock['subject'],
			// 	'attr' => array('class' => 'plain-text single-line')
			// ))
			// ->add( 'emailBodyHtml', CKEditorType::class, array(
			// 	'label' => 'M2M Email Body',
			// 	'data' => $emailBlock['body_html'],
			// ))

			->add( 'emailSubjectNonM2M', CKEditorType::class, array(
				'label' => 'Email Subject',
				'data' => $emailBlockNonM2M['subject'],
				'attr' => array('class' => 'plain-text single-line')
			))
			->add( 'emailBodyHtmlNonM2M', CKEditorType::class, array(
				'label' => 'Email Body',
				'data' => $emailBlockNonM2M['body_html'],
			))

			->add( 'saveEmailChanges' , 'submit' , array(
				'label' => 'Save Email Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )

			->add( 'sendEmailsNow' , 'submit' , array(
				'label' => 'Send Awarded Emails Now'
			) )
			->add( 'sendPDFs' , 'submit' , array(
				'label' => 'Generate PDFs' ,
				'attr' => array( 'class' => 'btn btn-info' )
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/awarded/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( )
				||  $form->get( 'sendEmailsNow' )->isClicked( ) ) {

				if( isset( $data['emailSubject'] ) ){
					$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
					$emailCorrespondence->setTemplate($emailTemplate);
					$this->getDoctrine()->getManager()->persist($emailCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$emailTemplateNonM2M = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubjectNonM2M'], 'body_html' => $data['emailBodyHtmlNonM2M']]);
				$emailCorrespondenceNonM2M->setTemplate($emailTemplateNonM2M);
				$this->getDoctrine()->getManager()->persist($emailCorrespondenceNonM2M);
				$this->getDoctrine()->getManager()->flush();

			} else if(  $form->get( 'saveLetterChanges' )->isClicked( )
				||  $form->get( 'sendPDFs' )->isClicked( ) ) {

				if( isset( $data['letterTemplate'] ) ){
					$letterCorrespondence->setTemplate($data['letterTemplate']);
					$this->getDoctrine()->getManager()->persist($letterCorrespondence);
					$this->getDoctrine()->getManager()->flush();
				}

				$letterCorrespondenceNonM2M->setTemplate($data['letterTemplateNonM2M']);
				$this->getDoctrine()->getManager()->persist($letterCorrespondenceNonM2M);
				$this->getDoctrine()->getManager()->flush();
			}

			if ( $form->get( 'sendEmailsNow' )->isClicked( ) ) {

				/* This segment of code handles what needs to be done when the "Send Awarded Emails Now" button is clicked. */
				$selectForm = $data['selectForm_email'];
				if ( $selectForm ) {
					$selectForm = $this->getDoctrine()
						->getRepository( 'IIABStudentTransferBundle:Form' )
						->find( $selectForm );
					$process->setForm( $selectForm );
				}

				$process->setEvent( 'email' );
				$process->setType( 'awarded' );
				$process->setOpenEnrollment( $openEnrollment );

				$this->getDoctrine()->getManager()->persist( $process );
				$this->getDoctrine()->getManager()->flush();

			} elseif ( $form->get( 'sendPDFs' )->isClicked( ) ) {

				/* This segment of code handles what needs to be done when the Save Changes button is clicked. */
				$selectForm = $data[ 'selectForm_letter' ];
				if( $selectForm ){
					$selectForm = $this->getDoctrine()
						->getRepository( 'IIABStudentTransferBundle:Form' )
						->find( $selectForm );
					$process->setForm( $selectForm );
				}

				$process->setEvent( 'pdf' );
				$process->setType( 'awarded' );

				$process->setOpenEnrollment( $openEnrollment );
				$this->getDoctrine()->getManager()->persist( $process );
				$this->getDoctrine()->getManager()->flush();
			}
			return $this->redirect( $this->generateUrl( 'emailpdfreport_awarded' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'awarded' );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

	/**
	 * The "awarded" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Offered".
	 *
	 * @Route("awarded-but-waitlisted/", name="emailpdfreport_awardedwaitlisted2")
	 * @Route("awarded-but-waitlisted/{period}/", name="emailpdfreport_awardedwaitlisted")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfawardedButWaitListed.html.twig")
	 * @return array()
	 */
	public function awardedButWaitListedAction( $period = 0 ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {

			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */

			return $this->redirect( $this->generateUrl( 'emailpdfreport_default' , array( 'source' => 'awardedwaitlisted' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

		if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

			return $this->redirect( $this->generateUrl( 'emailpdfreport_default' , array( 'source' => 'awardedwaitlisted' )) );
		}

		/** @var \IIAB\StudentTransferBundle\Entity\Lottery $lotteryEntity */
		$lotteryEntity = $this->getDoctrine()->getRepository('IIABStudentTransferBundle:Lottery')->findOneByEnrollmentPeriod( $openEnrollment );

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'awardedButWaitList',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('awardedButWaitList');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/awardedButWaitList.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		// Setup PDF Letter Templates
		$letterCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'awardedButWaitList',
			'type' => 'letter'
		) );

		if($letterCorrespondence == null) {
			$letterCorrespondence = new Correspondence();
			$letterCorrespondence->setName('awardedButWaitList');
			$letterCorrespondence->setType('letter');
			$letterCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Report/awardedButWaitListLetter.html.twig'));
			$letterCorrespondence->setActive(1);
			$letterCorrespondence->setLastUpdateDateTime(new \DateTime());
		}

		$form = $this->createFormBuilder( )
			->add( 'selectForm_letter' , 'entity' , [
					'data' => new CurrentEnrollmentSettingsSelectForm(),
					'class' => 'IIABStudentTransferBundle:Form',
					'required' => false,
					'placeholder' => 'All Forms',
					'label' => 'Form Type'
				]
			)
			->add( 'selectForm_email' , 'entity' , [
					'data' => new CurrentEnrollmentSettingsSelectForm(),
					'class' => 'IIABStudentTransferBundle:Form',
					'required' => false,
					'placeholder' => 'All Forms',
					'label' => 'Form Type'
				]
			)

			->add( 'letterTemplate', CKEditorType::class, array(
				'label' => 'Letter',
				'data' => $letterCorrespondence->getTemplate(),
			))

			->add( 'saveLetterChanges' , 'submit' , array(
				'label' => 'Save PDF Letter Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )

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

			->add( 'sendEmailsNow' , 'submit' , array(
				'label' => 'Send Awarded But Wait Listed Emails Now'
			) )
			->add( 'sendPDFs' , 'submit' , array(
				'label' => 'Generate PDFs' ,
				'attr' => array( 'class' => 'btn btn-info' )
			) )
		;

		$form = $form->getForm();

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/awarded-but-wait-list/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();

		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( ) ||  $form->get( 'sendEmailsNow' )->isClicked( ) ) {

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			} else if(  $form->get( 'saveLetterChanges' )->isClicked( ) ||  $form->get( 'sendPDFs' )->isClicked( ) ) {

				$letterCorrespondence->setTemplate($data['letterTemplate']);
				$this->getDoctrine()->getManager()->persist($letterCorrespondence);
				$this->getDoctrine()->getManager()->flush();
			}


			if ( $form->get( 'sendEmailsNow' )->isClicked( ) ) {

				/* This segment of code handles what needs to be done when the "Send Awarded Emails Now" button is clicked. */

				$selectForm = $data[ 'selectForm_email' ];
				if( $selectForm ){
					$selectForm = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array ( 'id' => $selectForm ) );
					$process->setForm( $selectForm );
				}

				$process->setEvent( 'email' );
				$process->setType( 'awarded-but-wait-listed' );
				$process->setOpenEnrollment( $openEnrollment );

				$this->getDoctrine()->getManager()->persist( $process );
				$this->getDoctrine()->getManager()->flush();

			} elseif ( $form->get( 'sendPDFs' )->isClicked( ) ) {

				/* This segment of code handles what needs to be done when the Save Changes button is clicked. */

				$selectForm = $data[ 'selectForm_letter' ];
				if( $selectForm ){
					$selectForm = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array ( 'id' => $selectForm ) );
					$process->setForm( $selectForm );
				}

				$process->setEvent( 'pdf' );
				$process->setType( 'awarded-but-wait-listed' );
				$process->setOpenEnrollment( $openEnrollment );
				$this->getDoctrine()->getManager()->persist( $process );
				$this->getDoctrine()->getManager()->flush();
			}
			return $this->redirect( $this->generateUrl( 'emailpdfreport_awardedwaitlisted' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'awarded-but-wait-list' );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

	/**
	 * The "wait listed" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Wait List".
	 *
	 * @Route("waitlisted/", name="emailpdfreport_waitlisted2")
	 * @Route("waitlisted/{period}/", name="emailpdfreport_waitlisted")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfwaitlisted.html.twig")
	 * @return array()
     */
	public function waitlistedAction( $period = 0 ) {
		$admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {
			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */
			return $this->redirect( $this->generateUrl( 'emailpdfreport_default' , array( 'source' => 'waitlisted' ) ) );
		}

		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];

		if ( $openEnrollment == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

			return $this->redirect( $this->generateUrl( 'emailpdfreport_default' , array( 'source' => 'waitlisted' ) ) );
		}

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'waitingList',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('waitingList');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/waitingList.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		// Setup PDF Letter Templates
		$letterCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'waitingList',
			'type' => 'letter'
		) );

		if($letterCorrespondence == null) {
			$letterCorrespondence = new Correspondence();
			$letterCorrespondence->setName('waitingList');
			$letterCorrespondence->setType('letter');
			$letterCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Report/waitListLetter.html.twig'));
			$letterCorrespondence->setActive(1);
			$letterCorrespondence->setLastUpdateDateTime(new \DateTime());
		}

		$form = $this->createFormBuilder( )
			->add( 'selectForm_letter' , 'entity' , [
					'data' => new CurrentEnrollmentSettingsSelectForm(),
					'class' => 'IIABStudentTransferBundle:Form',
					'required' => false,
					'placeholder' => 'All Forms',
					'label' => 'Form Type'
				]
			)
			->add( 'selectForm_email' , 'entity' , [
					'data' => new CurrentEnrollmentSettingsSelectForm(),
					'class' => 'IIABStudentTransferBundle:Form',
					'required' => false,
					'placeholder' => 'All Forms',
					'label' => 'Form Type'
				]
			)

			->add( 'letterTemplate', CKEditorType::class, array(
				'label' => 'Letter',
				'data' => $letterCorrespondence->getTemplate(),
			))

			->add( 'saveLetterChanges' , 'submit' , array(
				'label' => 'Save PDF Letter Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )

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

			->add( 'sendEmailsNow' , 'submit' , array(
				'label' => 'Send Wait Listed Emails Now'
			) )
			->add( 'sendPDFs' , 'submit' , array(
				'label' => 'Generate PDFs' ,
				'attr' => array( 'class' => 'btn btn-info' )
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/wait-list/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();
		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( ) ||  $form->get( 'sendEmailsNow' )->isClicked( ) ) {

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			} else if(  $form->get( 'saveLetterChanges' )->isClicked( ) ||  $form->get( 'sendPDFs' )->isClicked( ) ) {

				$letterCorrespondence->setTemplate($data['letterTemplate']);
				$this->getDoctrine()->getManager()->persist($letterCorrespondence);
				$this->getDoctrine()->getManager()->flush();
			}

			if ( $form->get( 'sendEmailsNow' )->isClicked( ) ) {

				/* This segment of code handles what needs to be done when the "Send Awarded Emails Now" button is clicked. */

				$selectForm = $data[ 'selectForm_email' ];
				if( $selectForm ){
					$selectForm = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array ( 'id' => $selectForm ) );
					$process->setForm( $selectForm );
				}

				$process->setEvent( 'email' );
				$process->setType( 'wait-list' );
				$process->setOpenEnrollment( $openEnrollment );

				$this->getDoctrine()->getManager()->persist( $process );
				$this->getDoctrine()->getManager()->flush();

			} elseif ( $form->get( 'sendPDFs' )->isClicked( ) ) {

				/* This segment of code handles what needs to be done when the Save Changes button is clicked. */

				$selectForm = $data[ 'selectForm_letter' ];
				if( $selectForm ){
					$selectForm = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array ( 'id' => $selectForm ) );
					$process->setForm( $selectForm );
				}

				$process->setEvent( 'pdf' );
				$process->setType( 'wait-list' );
				$process->setOpenEnrollment( $openEnrollment );
				$this->getDoctrine()->getManager()->persist( $process );
				$this->getDoctrine()->getManager()->flush();
			}
			return $this->redirect( $this->generateUrl( 'emailpdfreport_waitlisted' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'wait-list' );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

	/**
	 * The "denied" view for the email and pdf reports page. If the period parameter ($period) is not set, it should redirect
	 * the end user to another page to explicitly choose a period for which to view a report. Generates PDF letters for all
	 * submissions with the status "Denied".
	 *
	 * @Route("denied/", name="emailpdfreport_denied2")
	 * @Route("denied/{period}/", name="emailpdfreport_denied")
	 *
	 * @param int $period
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfdenied.html.twig")
	 * @return array()
	 */
	public function deniedAction( $period = 0 ) {
		$admin_pool = $this->get( 'sonata.admin.pool' );
		$request = $this->get('request_stack')->getCurrentRequest();

		if ( $period == 0 ) {
			/* Redirect to enrollment period selection, passing as parameter the action type so we can return here afterwards */
			return $this->redirect( $this->generateUrl( 'emailpdfreport_default' , array( 'source' => 'denied' ) ) );
		}
		$getEnrollmentInfo = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:OpenEnrollment' )->findBy( array ( 'id' => $period ) );
		$openEnrollment = $getEnrollmentInfo[0];
		if ( $getEnrollmentInfo == null ) {

			/* In the event the end user attempts to select an invalid enrollment period (one that doesn't exist) redirect to selection */
			/* Passing the parameter denoting the source action so as to return here afterwards. */

            return $this->redirect( $this->generateUrl( 'emailpdfreport_default' , array( 'source' => 'denied' )) );
		}

		// Setup Email Template
		$emailCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'denied',
			'type' => 'email'
		) );

		if($emailCorrespondence == null) {
			$emailCorrespondence = new Correspondence();
			$emailCorrespondence->setName('denied');
			$emailCorrespondence->setType('email');
			$emailCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Email/denied.email.twig'));
			$emailCorrespondence->setActive(1);
			$emailCorrespondence->setLastUpdateDateTime(new \DateTime());
		}
		$emailBlock = CorrespondenceVariablesService::divideEmailBlocks($emailCorrespondence->getTemplate());

		// Setup PDF Letter Templates
		$letterCorrespondence = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Correspondence' )->findOneBy( array(
			'active' => 1,
			'name' => 'denied',
			'type' => 'letter'
		) );

		if($letterCorrespondence == null) {
			$letterCorrespondence = new Correspondence();
			$letterCorrespondence->setName('denied');
			$letterCorrespondence->setType('letter');
			$letterCorrespondence->setTemplate(file_get_contents($this->container->get('kernel')->getRootDir() . '/../src/IIAB/StudentTransferBundle/Resources/views/Report/deniedLetter.html.twig'));
			$letterCorrespondence->setActive(1);
			$letterCorrespondence->setLastUpdateDateTime(new \DateTime());
		}

		$form = $this->createFormBuilder( )
			->add( 'nextMailDate' , 'date' , array(
				'label' => 'Denied Letter Mail Date',
				'data' => $openEnrollment->getMailDate()
			) )
			->add( 'nextAcademicYear' , 'text' , array(
                'label' => 'Next Academic School Year (YYYY/YYYY)',
				'data' => $openEnrollment->getTargetAcademicYear()
            ) )
			->add( 'nextTransferYear' , 'text' , array(
                'label' => 'Next Year When Transfer Applications Will Be Accepted (YYYY)',
				'data' => $openEnrollment->getTargetTransferYear()
            ) )
			->add( 'sendEmailsNow' , 'submit' , array(
				'label' => 'Send Denied Emails Now'
			) )
			->add( 'submissionStatus_email' , 'entity' , [
					'class' => 'IIABStudentTransferBundle:SubmissionStatus',
					'query_builder' => function( EntityRepository $er ) {
						return $er->createQueryBuilder( 'ss' )
							->where('ss.id IN (5, 9) ')
							->orderBy( 'ss.id' , 'ASC' );
					},
					'required' => false,
					'placeholder' => 'All Denied Statuses',
					'label' => 'Submission Status'
				]
			)
			->add( 'submissionStatus_letter' , 'entity' , [
					'class' => 'IIABStudentTransferBundle:SubmissionStatus',
					'query_builder' => function( EntityRepository $er ) {
						return $er->createQueryBuilder( 'ss' )
							->where('ss.id IN (5, 9) ')
							->orderBy( 'ss.id' , 'ASC' );
					},
					'required' => false,
					'placeholder' => 'All Denied Statuses',
					'label' => 'Submission Status'
				]
			)
			->add( 'selectForm_letter' , 'entity' , [
					'data' => new CurrentEnrollmentSettingsSelectForm(),
					'class' => 'IIABStudentTransferBundle:Form',
					'required' => false,
					'placeholder' => 'All Forms',
					'label' => 'Form Type'
				]
			)
			->add( 'selectForm_email' , 'entity' , [
					'data' => new CurrentEnrollmentSettingsSelectForm(),
					'class' => 'IIABStudentTransferBundle:Form',
					'required' => false,
					'placeholder' => 'All Forms',
					'label' => 'Form Type'
				]
			)

			->add( 'letterTemplate', CKEditorType::class, array(
				'label' => 'Letter',
				'data' => $letterCorrespondence->getTemplate(),
			))

			->add( 'saveLetterChanges' , 'submit' , array(
				'label' => 'Save PDF Letter Template Changes' ,
				'attr' => array( 'class' => 'btn btn-info' ) ,
			) )

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

			->add( 'saveChanges1' , 'submit' , array(
				'label' => 'Save Changes' ,
				'attr' => array( 'class' => 'btn btn-info' )
			) )
			->add( 'sendPDFs' , 'submit' , array(
				'label' => 'Generate PDFs' ,
				'attr' => array( 'class' => 'btn btn-info' )
			) )
		;

		$form = $form->getForm( );

		$form->handleRequest( $request );

		$rootDIR = $this->container->get( 'kernel' )->getRootDir() . '/../web/reports/denied/' . $period . '/';
		if ( ! file_exists( $rootDIR ) ) {
			mkdir( $rootDIR , 0755 , true );
		}

		$lastGeneratedFiles = array_diff( scandir( $rootDIR ) , array( '..' , '.' , '.DS_Store' ) );
		rsort( $lastGeneratedFiles );
		$lastGeneratedFiles = array_slice( $lastGeneratedFiles , 0 , 5 );

		$process = new Process();
		if ( $form->isValid( ) ) {
			$data = $form->getData( );

			if(  $form->get( 'saveEmailChanges' )->isClicked( ) ||  $form->get( 'sendEmailsNow' )->isClicked( ) ) {

				$emailTemplate = CorrespondenceVariablesService::combineEmailBlocks(['subject' => $data['emailSubject'], 'body_html' => $data['emailBodyHtml']]);
				$emailCorrespondence->setTemplate($emailTemplate);
				$this->getDoctrine()->getManager()->persist($emailCorrespondence);
				$this->getDoctrine()->getManager()->flush();

			} else if(  $form->get( 'saveLetterChanges' )->isClicked( ) ||  $form->get( 'sendPDFs' )->isClicked( ) ) {

				$letterCorrespondence->setTemplate($data['letterTemplate']);
				$this->getDoctrine()->getManager()->persist($letterCorrespondence);
				$this->getDoctrine()->getManager()->flush();
			}

			if ( $form->get( 'sendEmailsNow' )->isClicked( ) ) {

				/* This segment of code handles what needs to be done when the "Send Awarded Emails Now" button is clicked. */

				$selectForm = $data[ 'selectForm_email' ];
				if( $selectForm ){
					$selectForm = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array ( 'id' => $selectForm ) );
					$process->setForm( $selectForm );
				}

				$submissionStatus = $data[ 'submissionStatus_email' ];
				if( $submissionStatus ){
					$submissionStatus = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )->findOneBy( array ( 'id' => $submissionStatus ) );
					$process->setSubmissionStatus( $submissionStatus );
				}

				$process->setEvent( 'email' );
				$process->setType( 'denied' );
				$process->setOpenEnrollment( $openEnrollment );
				$this->getDoctrine()->getManager()->persist( $process );
				$this->getDoctrine()->getManager()->flush();

			} elseif ( $form->get( 'sendPDFs' )->isClicked( ) ) {

				/* This segment of code handles what needs to be done when the Save Changes button is clicked. */

				$selectForm = $data[ 'selectForm_letter' ];
				if( $selectForm ){
					$selectForm = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Form' )->findOneBy( array ( 'id' => $selectForm ) );
					$process->setForm( $selectForm );
				}

				$submissionStatus = $data[ 'submissionStatus_letter' ];
				if( $submissionStatus ){
					$submissionStatus = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:SubmissionStatus' )->findOneBy( array ( 'id' => $submissionStatus ) );
					$process->setSubmissionStatus( $submissionStatus );
				}

				$process->setEvent( 'pdf' );
				$process->setType( 'denied' );
				$process->setOpenEnrollment( $openEnrollment );
				$openEnrollment->setMailDate( $data['nextMailDate'] );
				$openEnrollment->setTargetAcademicYear( $data['nextAcademicYear'] );
				$openEnrollment->setTargetTransferYear( $data['nextTransferYear'] );
				$this->getDoctrine()->getManager()->persist( $process );
				$this->getDoctrine()->getManager()->flush();
			} elseif( $form->get('saveChanges1')->isClicked() ) {
				$openEnrollment->setMailDate( $data['nextMailDate'] );
				$openEnrollment->setTargetAcademicYear( $data['nextAcademicYear'] );
				$openEnrollment->setTargetTransferYear( $data['nextTransferYear'] );
				$this->getDoctrine()->getManager()->flush();
			}
			return $this->redirect( $this->generateUrl( 'emailpdfreport_denied' , array( 'period' => $period )) );
		}
		$info = array( 'period' => $period , 'type' => 'denied' );
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) , 'files' => $lastGeneratedFiles , 'process' => $process , 'info' => $info ];
	}

	/**
	 * If an enrollment period ($period) isn't valid or set for the reports page, this action should redirect so as to
	 * force the end user to choose an enrollment period for which to view said reports.
	 *
	 * @Route("select/{source}", name="emailpdfreport_default")
	 * @Route("", name="emailpdfreport_default2")
	 * @param string $source
	 * @Template("IIABStudentTransferBundle:EmailPDF:emailpdfselect.html.twig")
	 * @return array()
	 */
	public function selectAction( $source = 'awarded' ) {

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

		$source = 'emailpdfreport_' . $source;

		if ( $form->isValid( ) ) {
			$data = $form->getData();
			if ( $data['selectPeriod'] ) {
				return $this->redirect( $this->generateUrl( $source , array( 'period' => $data['selectPeriod']->getId() ) ) );
			}
		}
		return [ 'admin_pool' => $admin_pool , 'form' => $form->createView( ) ];
	}
}