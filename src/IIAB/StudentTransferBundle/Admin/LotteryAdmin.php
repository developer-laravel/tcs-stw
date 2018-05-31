<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class LotteryAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_lottery';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'lottery';


	protected function configureFormFields( FormMapper $form ) {

		$form
			->with( 'Run Dates' , array( 'class' => 'col-md-12' ) )
			->end()
			->with( 'Mailed Dates' , array( 'class' => 'col-md-12' ) )
			->end()
		;

		$form
			->with( 'Run Dates' )
			->add('enrollmentPeriod')
			/*->add('firstRoundDate' , 'datetime' , array(
				'label' => 'First Round Lottery Date'
			) )
			->add('secondRoundDate' , 'datetime' , array(
				'label' => 'Second Round Lottery Date'
			) )*/
			->end()
			->with( 'Mailed Dates' )
			->add('mailFirstRoundDate' , 'date' , array(
				'label' => 'MAILED: First Round Lottery Date'
			) )
			->add( 'firstRoundAcceptanceWindow', 'integer', [
				'label' => 'First Round Acceptance Window: # of Days after mail date`',
			])
			->add('mailSecondRoundDate' , 'date' , array(
				'label' => 'MAILED: Second Round Lottery Date',

			) )
			->add( 'secondRoundAcceptanceWindow', 'integer', [
				'label' => 'Second Round Acceptance Window: # of Days after mail date',
			])
			->end()
		;
	}

	protected function configureRoutes( RouteCollection $collection ) {

		//$collection->clearExcept( array( 'list' , 'show' , 'export' ) );
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->addIdentifier('id')
			->add('enrollmentPeriod')
			//->add('firstRoundDate' , null , array( 'label' => 'First Round Run' ) )
			->add('mailFirstRoundDate' , null , array( 'label' => 'First Round Mail' ) )
			//->add('secondRoundDate' , null , array( 'label' => 'Second Round Run' ) )
			->add('mailSecondRoundDate' , null , array( 'label' => 'Second Round Mail' ) )
			->add('lotteryStatus', null, [ 'label' => 'Status'] )
		;
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add('enrollmentPeriod')
			->add('lotteryStatus')
		;
	}
}