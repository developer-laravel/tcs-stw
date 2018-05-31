<?php

namespace IIAB\StudentTransferBundle\Command;

use Exception;
use PDO;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use IIAB\StudentTransferBundle\Entity\Audit;

class AutoImportCommand extends ContainerAwareCommand {

	private $file;

	/** @var PDO */
	private $pdo;

	protected function configure() {
		$this
			->setName( 'iiab:import:inow' )
			->setDescription( 'Auto Import iNow Data' )
			->addArgument( 'file' , InputArgument::REQUIRED , 'Where is the file you want to import?' )
		;
	}

	/**
	 * @inheritdoc
	 */
	protected function execute( InputInterface $input , OutputInterface $output ) {

		$file = $input->getArgument( 'file' );
		if( empty( $file ) ) {
			throw new \Exception( 'File option is required. Please provide a file to import.' );
		}

		if( !file_exists( $file ) ) {
			throw new \Exception( 'File could not be found. Please provide a file to import. Make sure to use the full path of the file.' );
		}

		ini_set( 'memory_limit' , '1G' );
		set_time_limit( 0 );
		$output->writeln( '<fg=green>Starting loading @ ' . date( 'Y-m-d g:i:s' ) . '</fg=green>' );
		$output->writeln( '<fg=green>Reading: ' . $file . '</fg=green>' );

		$this->file = $file;

		$dbname = $this->getContainer()->getParameter('database_name');
		$dbhost = $this->getContainer()->getParameter('database_host');
		$dbport = $this->getContainer()->getParameter('database_port');
		$dbuser = $this->getContainer()->getParameter('database_user');
		$dbpass = $this->getContainer()->getParameter('database_password');

		$dsn = 'mysql:dbname=' . $dbname . ';host=' . $dbhost . ';port=' . $dbport;

		$this->pdo = new \PDO( $dsn , $dbuser , $dbpass , array( \PDO::MYSQL_ATTR_LOCAL_INFILE => 1 ) );

		$dataSetofStudent = array();
		try {
			$fp = fopen( $file , 'r' );

			// Headrow
			$head = fgetcsv( $fp , 4096 , ',' , '"' , '\\' );

			$studentColumns = array_slice( $head , 0 , 13 );
		} catch( \Exception $e ) {
			throw $e;
		}
		if( filesize( $file ) < 1049000 ) {
			throw new \Exception( 'File is way too small. Stop and check everything' );
		}

		$truncateStatement = $this->pdo->prepare( 'TRUNCATE TABLE `stw_inow`;' );
		$truncateResponse = $truncateStatement->execute();
		if( !$truncateResponse ) {
			var_dump( $truncateStatement->errorInfo() );
			die('check truncate' );
		}
		$truncateStatement->closeCursor();

		$studentIDList = array();
		$changeToMultiRace = array();
		$changeToMultiRaceHispanic = array();
		$multiRace = 'Multi Race - Two or More Races';
		$multiRaceHispanic = 'Multi Race - Two or More Races- Hispanic';
		$total = 1;
		$counter = 0;
		$reset = 1;
		// Rows
		while( $column = fgetcsv( $fp , 4096 , ',' , '"' , '\\' ) ) {
			// This is a great trick, to get an associative row by combining the headrow with the content-rows.

			//Ignoring --- those and last rows that include count.
			if( preg_match( '/----|(\(.+rows affected\))/' , $column[0] ) || $column[0] == '' ) {
				continue;
			}

			$student = array_slice( $column , 0 , 13 );

			try {
				$student = array_combine( $studentColumns , $student );

				$student['IsHispanic'] = (int) $student['IsHispanic'];

				//Fixed Address to be into one column.
				$address = array( $student['StreetNumber'] , $student['AddressLine1'] , $student['AddressLine2'] );
				$address = array_filter( $address );
				$address = trim( implode( ' ' , $address ) );
				$address = preg_replace( '/,/' , '' , $address );

				//remove un need columns
				unset( $student['StudentNumber'] , $student['StreetNumber'] , $student['AddressLine1'] , $student['AddressLine2'] );

				$student['Address'] = $address;

				if( $student['IsHispanic'] == 1 ) {
					$student['Race'] = $student['Race'] . '- Hispanic';
				}
				$student['dateofbirth'] = new \DateTime( $student['dateofbirth'] );
				$student['dateofbirth'] = $student['dateofbirth']->format( 'n/j/Y' );

				$address = null;

				//Only add unique StateID, we only need one copy of them in the database.
				if( !isset( $studentIDList[$column[0]] ) ) {
					$studentIDList[$column[0]] = 1;
					$dataSetofStudent[$column[0]] = $student;
				} else {
					//Need to convert Student over to Multi Race because there is more than one Race entity for this student.
					if( isset( $dataSetofStudent[$student['StateIDNumber']] ) ) {
						//If the races are different, then they are now a Multi Race Student;
						//Multi Race - Two or More Races
						if( $student['Race'] != $dataSetofStudent[$student['StateIDNumber']]['Race'] ) {
							if( $student['IsHispanic'] == 1 ) {
								$dataSetofStudent[$student['StateIDNumber']]['Race'] = $multiRaceHispanic;
							} else {
								$dataSetofStudent[$student['StateIDNumber']]['Race'] = $multiRace;
							}
						}
					} else {
						//They have already been imported. So we just need to update their race after completing the full import.
						if( $student['IsHispanic'] == 1 ) {
							$changeToMultiRaceHispanic[] = $student['StateIDNumber'];
						} else {
							$changeToMultiRace[] = $student['StateIDNumber'];
						}
					}
				}
				$student = null;

			} catch( \Exception $e ) {

				$output->writeln( 'Error 1001 : <pre>' . print_r( $column , true ) . '</pre>' );
				$output->writeln( $e->getMessage() );
				break;
			}

			$counter++;
			$total++;
		}
		if( count( $dataSetofStudent ) > 0 ) {
			$importStatus = $this->importData( $dataSetofStudent );
			if( $importStatus ) {
				$output->writeln( '<fg=green>Successfully import at ' . $total . ' @ ' . date( 'Y-m-d g:i' ) . '</fg=green>' );
				$this->recordAudit( 9 , 0 );
			} else {
				$output->writeln( '<fg=red>Failed import at ' . $total . ' @ ' . date( 'Y-m-d g:i' ) . '</fg=red>' );
				$this->recordAudit( 17 , 0 );
				throw new \Exception( 'Import failed due to error.' );
			}
		} else {
			$this->recordAudit( 17 , 0 );
		}

		$dataSetofStudent = null;

		if( count( $changeToMultiRace ) > 0 ) {
			//Need to Update some Students to MultiRace;
			$changeToMultiRace = array_unique( $changeToMultiRace );
			$output->writeln( '<fg=yellow>Update Race. Updating ' . count( $changeToMultiRace ) . ' rows.</fg=yellow>' );
			$studentRaceStatement = $this->pdo->prepare( 'UPDATE `stw_inow` SET `race` = "' . $multiRace . '" WHERE `studentID` IN (\'' . implode( '\', \'' , $changeToMultiRace ) . '\')' );
			$responseStudentRace = $studentRaceStatement->execute();
			if( !$responseStudentRace ) {
				var_dump( $studentRaceStatement->errorInfo() );
			}
		}
		if( count( $changeToMultiRaceHispanic ) > 0 ) {
			//Need to Update some Students to MultiRace;
			$changeToMultiRaceHispanic = array_unique( $changeToMultiRaceHispanic );
			$output->writeln( '<fg=yellow>Update Race. Updating Hispanic ' . count( $changeToMultiRaceHispanic ) . ' rows.</fg=yellow>' );
			$studentRaceStatementHispanic = $this->pdo->prepare( 'UPDATE `stw_inow` SET `race` = "' . $multiRaceHispanic . '" WHERE `studentID` IN (\'' . implode( '\', \'' , $changeToMultiRaceHispanic ) . '\')' );
			$responseStudentRaceHispanic = $studentRaceStatementHispanic->execute();
			if( !$responseStudentRaceHispanic ) {
				var_dump( $studentRaceStatementHispanic->errorInfo() );
			}
		}

		$output->writeln( '<fg=green>Import Complete. Loaded ' . $total . ' rows. Looped a total of ' . $reset . '</fg=green>' );
		$output->writeln( '<fg=green>Finished loading @ ' . date( 'Y-m-d g:i:s' ) . '</fg=green>' );
	}

	/**
	 * Does the mySQL LOAD DATA call to import the temporary arrays.
	 * Split arrays to keep memory usage low.
	 *
	 * @param array $student
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function importData( array $student ) {

		$fileSystem = new Filesystem();

		$path = str_replace( '\\' , "/" , dirname( $this->file ) );
		$tempStudent = $path . '/temp-student-file.csv';

		$fileSystem->touch( array( $tempStudent ) );

		if( !$fileSystem->exists( array( $tempStudent ) ) ) {
			throw new \Exception('Temporary files were not able to be created. Please check folder permissions.');
		}

		$fpStudent = fopen( $tempStudent , 'w' );

		foreach( $student as $fields ) {
			fputcsv( $fpStudent , $fields , ',' , '"' );
		}

		fclose( $fpStudent );

		try {
			$studentStatement = $this->pdo->prepare( 'LOAD DATA LOCAL INFILE \'' . $tempStudent . '\' INTO TABLE `stw_inow` FIELDS TERMINATED BY \',\' ENCLOSED BY \'"\' ESCAPED BY \'\\\\\' LINES TERMINATED BY \'\n\' (`studentID`, `last_name`, `first_name`, `dob`, `school`, `grade`, `race`, `city`, `zip`, `IsHispanic`, `address`);' );
			$responseStudent = $studentStatement->execute();
			if( !$responseStudent ) {
				var_dump( $studentStatement->errorInfo() );
			}
			$studentStatement->closeCursor();
		} catch( \Exception $e ) {
			throw $e;
		}

		$fpStudent = null;

		$fileSystem->remove( array( $tempStudent ) );

		$studentStatement = null;
		$student = null;

		if( $responseStudent ) {
			return true;
		}
		return false;
	}

	/**
	 * @param int $auditCode
	 * @param int $submission
	 *
	 * @return void
	 */
	private function recordAudit( $auditCode = 0 , $submission = 0 ) {

		$em = $this->getContainer()->get('doctrine.orm.entity_manager');

		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( '::1' );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( 0 );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( 0 );

		$em->persist( $audit );
		$em->flush();
		$em->clear();
	}

}