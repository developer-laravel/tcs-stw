<?php
/**
 * Created by PhpStorm.
 * User: michellegivens
 * Date: 12/26/14
 * Time: 2:10 PM
 */

namespace IIAB\StudentTransferBundle\Command;

use IIAB\StudentTransferBundle\Service\CheckAddressService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckAddressCommand extends ContainerAwareCommand {

	protected function configure() {

		$this
			->setName( 'stw:check:address' )
			->setDescription( 'Checks an address against the AddressBounds' )
			->addArgument( 'address' , InputArgument::REQUIRED , 'Address to check against' )
			->addArgument( 'zip' , InputArgument::REQUIRED , 'Zip to check against' );
	}

	protected function execute( InputInterface $input , OutputInterface $output ) {

		$address = $input->getArgument( 'address' );
		$zip = $input->getArgument( 'zip' );

		$studentArray = array(
			'address' => $address,
			'zip' => $zip,
			'student_status' => 'new'
		);

		$response = $this->getContainer()->get('stw.check.address')->checkAddress( $studentArray );
		if( empty( $response ) ) {
			$response = 'No address found';
		}

		$output->writeln( "Response: " . $response );

	}

}