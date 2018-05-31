<?php

namespace IIAB\StudentTransferBundle\Admin;

//use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Finder\Finder;

class OpenEnrollmentAdmin extends AbstractAdmin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_openEnrollment';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'open-enrollment';

	protected function configureFormFields( FormMapper $form ) {

		$context = $this->getPersistentParameter('context');

		$files = array(
			'' => 'No file'
		);
		if( ! file_exists( 'uploads/pdfs' ) ) {
			mkdir('uploads/pdfs');
		}
		$finder = new Finder();
		$finder->files()->in('uploads/pdfs');

		foreach ($finder as $file) {
			$files['/uploads/pdfs/' . $file->getRelativePathname() ] = $file->getRelativePathname();
		}

		$form
			->with( 'Enrollment Settings' , array( 'class' => 'col-md-12' ) )->end()
			->with( 'Lottery Acceptance Window' , array( 'class' => 'col-md-6' ) )->end()
			->with( 'After Lottery Acceptance Window' , array( 'class' => 'col-md-6' ) )->end()
			->with( 'Kindergarten Cut Off Settings' , array( 'class' => 'col-md-6' ) )->end()
			->with( 'First Grade Cut Off Settings' , array( 'class' => 'col-md-6' ) )->end()
			->with( 'Documents Settings' , array( 'class' => 'col-md-12' ) )->end();

		$form
			->with( 'Enrollment Settings' )
			->add( 'year' )
			->add( 'confirmationStyle' )
			->add( 'waitListExpireDate' , null , [ 'help' => 'The day the Wait List should Expire after all manual awarded as been completed.' ] )
			->end()
			->with( 'Lottery Acceptance Window' )
			->add( 'beginningDate' )
			->add( 'endingDate' )
			->end()
			->with( 'After Lottery Acceptance Window' )
			->add( 'afterLotteryBeginningDate' )
			->add( 'afterLotteryEndingDate' )
			->end()

			->with( 'Kindergarten Cut Off Settings' )
			->add('kindergartenDateCutOff', null, [
				'label' => 'Kindergarten Birthday Cut Off',
				'required' => true ,
				'years' => range(Date('Y') - 7, Date('Y')),
				'help' => 'After this date, submissions applying for Kindergarten will not be accepted.'
			])
			->end()
			->with( 'First Grade Cut Off Settings' )
			->add('firstGradeDateCutOff', null, [
				'label' => 'First Grade Birthday Cut Off',
				'required' => true ,
				'years' => range(Date('Y') - 8, Date('Y')),
				'help' => 'After this date, submissions applying for First Grade will not be accepted.'
			])
			->end()

			->with( 'Documents Settings' )
			->add( 'file', 'file', array( 'required' => false , 'label' => 'Add new File to use' , 'help' => 'Make sure the file name is unique. Add new file here for them to show up in the list. If you option is on "No file", then the text will not show on the front facing pages.' ) )
			// ->add( 'm2mPDFInfo' , 'choice' , array( 'required' => false , 'label' => 'M2M Info PDF' , 'choices' => $files ) )
			// ->add( 'm2mPDFInfoES' , 'choice' , array( 'required' => false , 'label' => 'M2M Info PDF Spanish' , 'choices' => $files ) )
			// ->add( 'm2mPDFFAQs' , 'choice' , array( 'required' => false , 'label' => 'M2M FAQs PDF' , 'choices' => $files ) )
			// ->add( 'm2mPDFFAQsES' , 'choice' , array( 'required' => false , 'label' => 'M2M FAQs PDF Spanish' , 'choices' => $files ) )
			->add( 'personnelPDF' , 'choice' , array( 'required' => false , 'label' => 'Personnel PDF' , 'choices' => $files ) )
			->add( 'personnelPDFEs' , 'choice' , array( 'required' => false , 'label' => 'Personnel PDF Spanish' , 'choices' => $files ) )
			->add( 'infoPDF' , 'choice' , array( 'required' => false , 'label' => 'Information PDF' , 'choices' => $files ) )
			->add( 'infoPDFEs' , 'choice' , array( 'required' => false , 'label' => 'Information PDF Spanish' , 'choices' => $files ) )
			->add( 'spedPDFInfo' , 'choice' , array( 'required' => false , 'label' => 'SPED Info PDF' , 'choices' => $files ) )
			->add( 'spedPDFInfoEs' , 'choice' , array( 'required' => false , 'label' => 'SPED Info PDF Spanish' , 'choices' => $files ) )
			->add( 'schoolChoicePDFInfo' , 'choice' , array( 'required' => false , 'label' => 'School Choice Info PDF' , 'choices' => $files ) )
			->add( 'schoolChoicePDFInfoEs' , 'choice' , array( 'required' => false , 'label' => 'School Choice Info PDF Spanish' , 'choices' => $files ) )
			->end()
			->with( 'Available Forms' )
			->add('forms', 'sonata_type_collection', array(
				'by_reference' => false,
				'label' => 'Front-end Available Forms',
				'required' => false,
				'help' => 'This does not affect the administration area. Only the front-end.',
				// 'cascade_validation' => true,
				'type_options' => array('delete' => false)
			), array(
				'edit' => 'inline',
				'inline' => 'table',
				'sortable' => 'position',
				'admin_code' => 'sonata.admin.openenrollment.hasform',
				'link_parameters' => array('context' => $context),
			))
			->end();
	}

	protected function configureListFields( ListMapper $list ) {

		$list
			->addIdentifier( 'year' )
			->add( 'confirmationStyle' )
			->add( 'beginningDate' )
			->add( 'endingDate' )
			->add( 'afterLotteryBeginningDate' )
			->add( 'afterLotteryEndingDate' );
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {

		$filter
			->add( 'year' )
			->add( 'confirmationStyle' )
			->add( 'beginningDate' )
			->add( 'endingDate' );
	}

	public function prePersist($openEnrollment) {
		$this->saveFile($openEnrollment);
	}

	public function preUpdate($openEnrollment) {
		$this->saveFile($openEnrollment);
	}

	public function saveFile($openEnrollment) {
		$basepath = $this->getRequest()->getBasePath();
		$openEnrollment->upload($basepath);
	}

}