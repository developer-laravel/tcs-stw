<?php

namespace IIAB\StudentTransferBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindCorrectZonedSchoolCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName( 'iiab:find:current:zoned:school' )
			->setDescription( 'Find the Current Zoned School' )
			->addArgument( 'submissionsID' , InputArgument::REQUIRED , 'What is the submissionID to look up?' )
		;
	}

	public function findCorrectZonedSchool( $zonedSchools = array() , $studentNextGrade = 0 , $enrollment = 0 ) {
		return '';
		$conn = 'mysql:dbname=studenttransfer' .
			';host=127.0.0.1' .
			';port=3306';

		if( $enrollment == 0 ) {
			return '';
		}

		if( ! is_array( $zonedSchools ) ) {
			$zonedSchools = [ $zonedSchools ];
		}

		$zonedSchools = array_map( [ $this , 'formatName' ] , $zonedSchools );

		$dhn = new \PDO( $conn , 'stwwebsite' , 'rA9nr9sqXXYBASQd' );

		if( $studentNextGrade <= 5 && count( $zonedSchools ) == 3 ) { $zonedSchools = array_shift( $zonedSchools ); }
		elseif( $studentNextGrade < 9 && count( $zonedSchools ) == 3 ) { array_pop( $zonedSchools ); }
		elseif( $studentNextGrade > 8 && count( $zonedSchools ) == 3 ) { $zonedSchools = array( array_pop( $zonedSchools ) ); }
		// ADDED AFTER CHANGING FROM SITE TO DB ZONES // DJW

		if( is_array( $zonedSchools ) ) {
			$schools = implode( "','" , array_values( $zonedSchools ) );
		} else {
			$schools = $zonedSchools;
		}

		$selectStatement = $dhn->prepare( "SELECT schoolName FROM stw_adm WHERE enrollmentPeriod = {$enrollment} AND grade = '{$studentNextGrade}' AND UPPER(hsvCityName) IN ('" . $schools . "');" );

		$selectStatement->execute();
		$results = $selectStatement->fetch( \PDO::FETCH_ASSOC );

		if( isset( $results['schoolName'] ) && !empty( $results['schoolName'] ) ) {
			return $results['schoolName'];
		}

		return '';
	}

	private function formatName( $name ) {
		$name = str_replace(' High', '', $name);
		$name = str_replace(' Middle', '', $name);
		$name = str_replace(' Elementary', '', $name);
		$name = str_replace(' School', '', $name);
		$name = str_replace(' P8', '', $name);
		return $name;
	}
}