<?php

namespace IIAB\StudentTransferBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindSubmittedUserCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName( 'iiab:find:submitter' )
			->setDescription( 'Find the Submitted User' )
			->addArgument( 'submissionsID' , InputArgument::REQUIRED , 'What is the submissionID to look up?' )
		;
	}

	public function findSubmittedUser( $submissionID = 0, $studentID = 0 ) {

		$conn = 'mysql:dbname=studenttransfer' .
			';host=127.0.0.1' .
			';port=3306';

		$dhn = new \PDO( $conn , 'stwwebsite' , 'rA9nr9sqXXYBASQd' );

		$selectStatement = $dhn->prepare( "SELECT auditID, userID, fos_user_user.firstname, fos_user_user.lastname FROM stw_audit LEFT JOIN fos_user_user ON fos_user_user.id = stw_audit.userID WHERE submissionID = :submissionID AND auditCodeID = 1 LIMIT 1" );

		$selectStatement->bindParam( ':submissionID' , $submissionID );
		//$selectStatement->bindParam( ':studentID' , $studentID );

		$selectStatement->execute();
		$results = $selectStatement->fetch( \PDO::FETCH_ASSOC );

		if( isset( $results['firstname'] ) && !empty( $results['firstname'] ) && isset( $results['lastname'] ) && !empty( $results['lastname'] ) ) {
			return $results['firstname'] . ' ' . $results['lastname'];
		}

		return $results['userID'];
	}
}