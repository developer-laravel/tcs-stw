<?php

namespace IIAB\StudentTransferBundle\Controller;

use Exception;
use IIAB\StudentTransferBundle\Entity\ADM;
use IIAB\StudentTransferBundle\Entity\Audit;
use IIAB\StudentTransferBundle\Entity\Expiration;
use IIAB\StudentTransferBundle\Entity\NewSchool;
use IIAB\StudentTransferBundle\Entity\OpenEnrollment;
use IIAB\StudentTransferBundle\Entity\SchoolGroup;
use IIAB\StudentTransferBundle\Entity\Slotting;
use IIAB\StudentTransferBundle\Entity\Submission;
use PHPExcel;
use PHPExcel_IOFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ImportController extends Controller {

	/**
	 * The default action to handle the form for form to import data.
	 *
	 * @param Request $request
	 *
	 * @Route("/admin/import" , name="stw_admin_import" )
	 * @Template()
	 *
	 * @return \Symfony\Component\HttpFoundation\Response;
	 */
	public function importAction( Request $request ) {

		$admin_pool = $this->get( 'sonata.admin.pool' );

		$form = $this->createFormBuilder()
			->add( 'import-type' , 'choice' , array(
				'label' => $this->get( 'translator' )->trans( 'import.form.type' , array() , 'IIABStudentTransferBundle' ) ,
				'choices' => array(
					'adm' => $this->get( 'translator' )->trans( 'import.form.adm' , array() , 'IIABStudentTransferBundle' ) ,
					//'inow' => $this->get( 'translator' )->trans( 'import.form.inow' , array() , 'IIABStudentTransferBundle' ) ,
					'new-schools' => $this->get( 'translator' )->trans( 'import.form.newschool' , array() , 'IIABStudentTransferBundle' ) ,
					'slotting' => $this->get( 'translator' )->trans( 'import.form.slotting' , array() , 'IIABStudentTransferBundle' ) ,
					'address' => 'Address bounds data file' ,
					'expiring' => 'Current M2M Student File'
				) ,
				'placeholder' => $this->get( 'translator' )->trans( 'import.form.emptyType' , array() , 'IIABStudentTransferBundle' ) ,
			) )
			->add( 'open-enrollment' , 'entity' , array(
				'class' => 'IIAB\StudentTransferBundle\Entity\OpenEnrollment' ,
				'property' => 'year' ,
				'label' => $this->get( 'translator' )->trans( 'import.form.openenrollment' , array() , 'IIABStudentTransferBundle' ) ,
				'placeholder' => $this->get( 'translator' )->trans( 'import.form.emptyEnrollment' , array() , 'IIABStudentTransferBundle' )
			) )
			->add( 'import-file' , 'file' , array(
				'label' => $this->get( 'translator' )->trans( 'import.form.file' , array() , 'IIABStudentTransferBundle' )
			) )
			->add( 'import-submit' , 'submit' , array(
				'label' => $this->get( 'translator' )->trans( 'import.form.submit' , array() , 'IIABStudentTransferBundle' )
			) )
			->getForm();

		$form->handleRequest( $request );

		if( $form->isValid() ) {


			$formData = $form->getData();

			/** @var \Symfony\Component\HttpFoundation\File\UploadedFile $objFile */
			$objFile = $formData['import-file'];
			$newFile = 'imported-file.' . $objFile->guessClientExtension();

			$objFile->move( $this->get( 'kernel' )->getRootDir() . '/../web/uploads/' , $newFile );

			$newFile = $this->get( 'kernel' )->getRootDir() . '/../web/uploads/' . $newFile;

			if( $formData['import-type'] != 'address' ) {

				try {
					$inputFileType = PHPExcel_IOFactory::identify( $newFile );
					$objReader = PHPExcel_IOFactory::createReader( $inputFileType );
					$objPHPExcel = $objReader->load( $newFile );
					unset( $objReader , $inputFileType );
				} catch( Exception $e ) {
					die( 'Error loading file : ' . $objFile->getClientOriginalName() . ' Error:' . $e->getMessage() ); //"' . pathinfo($inputFileType, PATHINFO_BASENAME) . '"
				}
			}

			switch( $formData['import-type'] ) {
				case 'adm':
					$message = $this->importADM( $objPHPExcel , $formData['open-enrollment'] );
					break;
				case 'inow':
					$message = $this->importINowData( $objPHPExcel );
					break;
				case 'new-schools':
					$message = $this->importNewSchoolsData( $objPHPExcel , $formData['open-enrollment'] );
					break;
				case 'employee':
					$message = $this->importEmployeeFeederData( $objPHPExcel , $formData['open-enrollment'] );
					break;
				case 'slotting':
					$message = $this->importSlottingData( $objPHPExcel , $formData['open-enrollment'] );
					break;
				case 'address':
					$message = $this->importAddressData( $newFile );
					break;
				case 'expiring':
					$message = $this->importExpiringData( $objPHPExcel , $formData['open-enrollment'] );
					break;
				default:
					$message = $this->get( 'translator' )->trans( 'import.form.noFile' , array() , 'IIABStudentTransferBundle' );
					break;
			}
			unlink( $newFile );

			return array(
				'admin_pool' => $admin_pool ,
				'form' => $form->createView() ,
				'message' => $message

			);
			//return $this->render( '@IIABStudentTransfer/Import/import.html.twig' , array( 'form' => $form->createView() , 'message' => $message ) );

		}
		return array(
			'admin_pool' => $admin_pool ,
			'form' => $form->createView()
		);
		//return $this->render( '@IIABStudentTransfer/Import/import.html.twig' , array( 'form' => $form->createView() ) );
	}

	/**
	 * This function is used to handle the importing of the ADM Data.
	 *
	 * @param PHPExcel       $excelFile
	 * @param OpenEnrollment $enrollment
	 *
	 * @return string
	 */
	private function importADM( PHPExcel $excelFile , OpenEnrollment $enrollment ) {

		$submission = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
			'enrollmentPeriod' => $enrollment
		) );

		if( $submission ) {
			return $this->get( 'translator' )->trans( 'import.form.admErrorSubmission' , array() , 'IIABStudentTransferBundle' );
		}

		//Since there are no submissions for this open enrollment. Lets remove all ADM data for this openEnrollment Period.
		$qb = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:ADM' );
		$qb->createQueryBuilder( 'a' )
			->delete()
			->where( 'a.enrollmentPeriod = :id' )
			->setParameter( 'id' , $enrollment->getId() )
			->getQuery()
			->getResult();

		//convert
		$arrayOfExcelData = $excelFile->getActiveSheet()->toArray( null , true , false , false );
		unset( $excelFile , $qb , $submission );

		$em = $this->getDoctrine()->getManager();

		//echo '<pre>' . print_r( $arrayOfExcelData , true ) . '</pre>';

		//check to see if the file contains the ADM data fields
		if( strpos( $arrayOfExcelData[0][0] , 'SchNum' ) !== false &&
			strpos( $arrayOfExcelData[0][1] , 'SchName' ) !== false &&
			strpos( $arrayOfExcelData[0][2] , 'Black' ) !== false &&
			strpos( $arrayOfExcelData[0][3] , 'White' ) !== false &&
			strpos( $arrayOfExcelData[0][4] , 'Other' ) !== false &&
			strpos( $arrayOfExcelData[0][5] , 'Total' ) !== false &&
			strpos( $arrayOfExcelData[0][6] , 'B%' ) !== false &&
			strpos( $arrayOfExcelData[0][7] , 'W%' ) !== false &&
			strpos( $arrayOfExcelData[0][8] , 'O%' ) !== false &&
			strpos( $arrayOfExcelData[0][9] , 'Current School on City Zoning (MUST BE EXACTLY THE SAME)' ) !== false &&
			strpos( $arrayOfExcelData[0][10] , 'Next Year Starting Grade' ) !== false &&
			strpos( $arrayOfExcelData[0][11] , 'Next Year Ending Grade' ) !== false
		) {

			//Excel file has correct columns, now loop through the data and put data in database
			$firstRowFlag = true;
			foreach( $arrayOfExcelData as $row ) {
				//make sure to skip the first row
				if( strpos( $row[1] , 'TOTAL' ) !== false ) {
					break;
				} //we have reached the end of the ADM data break out of for loop
				if( !$firstRowFlag ) {

					$row = array_map( 'trim' , $row );

					$startGrade = $row[10];
					$endGrade = $row[11];

					$schoolGroup = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:SchoolGroup' )->createQueryBuilder( 'school_group' )
						->where( 'UPPER(school_group.name) LIKE :name' )
						->setParameter( 'name' , strtoupper( $row[1] ) . '%' )
						->setMaxResults( 1 )
						->getQuery()
						->getResult();

					if( $schoolGroup == null ) {
						$schoolGroup = new SchoolGroup();
						$schoolGroup->setName( ucwords( strtolower( $row[1] ) ) );
						$em->persist( $schoolGroup );
					} else {
						$schoolGroup = $schoolGroup[0];
					}

					$school = trim( preg_replace( '/(\bElementary\b)|(\bHigh\b)|(\bMiddle\b)|(\bSchool\b)/i' , '' , $row[9] ) );

					if( $startGrade != Null && $endGrade != Null ) {
						//start/ending grades aren't empty. Lets loop over them.
						if( strtoupper( $startGrade ) == 'K' ) {
							$startGrade = 0;
						}
						if( strtoupper( $startGrade ) == 'P' ) {
							$startGrade = -1;
						}
						for( $x = $startGrade; $x <= $endGrade; $x++ ) {
							$adm = new ADM();
							if( $x == -1 ) {
								$adm->setSchoolID( preg_replace( '/[^0-9]/' , '' , $row[0] ) );
								$adm->setSchoolName( $row[1] );
								$adm->setBlack( ( empty( $row[2] ) ? 0 : $row[2] ) );
								$adm->setWhite( ( empty( $row[3] ) ? 0 : $row[3] ) );
								$adm->setOther( ( empty( $row[4] ) ? 0 : $row[4] ) );
								$adm->setTotal( ( empty( $row[5] ) ? 0 : $row[5] ) );
								$adm->setBlackPercent( ( empty( $row[6] ) ? 0 : $row[6] ) );
								$adm->setWhitePercent( ( empty( $row[7] ) ? 0 : $row[7] ) );
								$adm->setOtherPercent( ( empty( $row[8] ) ? 0 : $row[8] ) );
								$adm->setHsvCityName( $school );
								$adm->setGrade( '99' );
								$adm->setEnrollmentPeriod( $enrollment );
								$adm->setGroupID( $schoolGroup );
							} else {
								$adm->setSchoolID( preg_replace( '/[^0-9]/' , '' , $row[0] ) );
								$adm->setSchoolName( $row[1] );
								$adm->setBlack( ( empty( $row[2] ) ? 0 : $row[2] ) );
								$adm->setWhite( ( empty( $row[3] ) ? 0 : $row[3] ) );
								$adm->setOther( ( empty( $row[4] ) ? 0 : $row[4] ) );
								$adm->setTotal( ( empty( $row[5] ) ? 0 : $row[5] ) );
								$adm->setBlackPercent( ( empty( $row[6] ) ? 0 : $row[6] ) );
								$adm->setWhitePercent( ( empty( $row[7] ) ? 0 : $row[7] ) );
								$adm->setOtherPercent( ( empty( $row[8] ) ? 0 : $row[8] ) );
								$adm->setHsvCityName( $school );
								$adm->setGrade( sprintf( '%1$02d' , $x ) );
								$adm->setEnrollmentPeriod( $enrollment );
								$adm->setGroupID( $schoolGroup );
							}
							$em->persist( $adm );
							unset( $adm );
						}
					}

				} else {
					//skipped first row
					$firstRowFlag = false;
				}

			}
			$em->flush();
			$em->clear();

			$this->recordAudit( 8 , 0 );

			unset( $arrayOfExcelData , $firstRowFlag , $audit );
			return $this->get( 'translator' )->trans( 'import.form.success' , array( 'type' => $this->get( 'translator' )->trans( 'import.form.adm' , array() , 'IIABStudentTransferBundle' ) ) , 'IIABStudentTransferBundle' );
		}

		$this->recordAudit( 14 , 0 );

		return $this->get( 'translator' )->trans( 'import.form.admError' , array() , 'IIABStudentTransferBundle' );
	}

	/**
	 * @param int $auditCode
	 * @param int $submission
	 *
	 * @return void
	 */
	private function recordAudit( $auditCode = 0 , $submission = 0 ) {

		$em = $this->getDoctrine()->getManager();
		$user = $this->get( 'security.context' )->getToken()->getUser();

		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );

		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( $this->get( 'request' )->getClientIp() );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( 0 );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( $user->getId() );

		$em->persist( $audit );
		$em->flush();
		$em->clear();
	}


	private function importExpiringData( PHPExcel $excelFile , OpenEnrollment $enrollment ) {

		$arrayOfExcelData = $excelFile->getActiveSheet()->toArray( null , true , false , false );
		unset( $excelFile );


		$dbname = $this->container->getParameter( 'database_name' );
		$dbhost = $this->container->getParameter( 'database_host' );
		$dbport = $this->container->getParameter( 'database_port' );
		$dbuser = $this->container->getParameter( 'database_user' );
		$dbpass = $this->container->getParameter( 'database_password' );

		$dsn = 'mysql:dbname=' . $dbname . ';host=' . $dbhost . ';port=' . $dbport;

		$pdo = new \PDO( $dsn , $dbuser , $dbpass , array() );

		//check to see if the file contains the new School data fields
		if( strpos( $arrayOfExcelData[0][0] , 'studentID' ) !== false &&
			strpos( $arrayOfExcelData[0][1] , 'studentFirstName' ) !== false &&
			strpos( $arrayOfExcelData[0][2] , 'studentLastName' ) !== false &&
			strpos( $arrayOfExcelData[0][3] , 'expiring' ) !== false &&
			strpos( $arrayOfExcelData[0][4] , 'feederSchool' ) !== false
		) {

			$deleteStatement = $pdo->prepare( 'DELETE FROM `stw_expiring` WHERE `openEnrollment_id` = ' . $enrollment->getId() . ';' );
			$deleteResponse = $deleteStatement->execute();
			if( !$deleteResponse ) {
				return 'Could not empty out existing expiring table. Try again or contact your IT administrator';
			}
			$deleteStatement->closeCursor();
			$deleteStatement = null;

			$em = $this->getDoctrine()->getManager();

			foreach( $arrayOfExcelData as $key => $row ) {
				if( $key == 0 ) {
					continue;
				}

				if( empty( $row[0] ) || empty( $row[1] ) || empty( $row[2] ) ) {
					continue;
				}
				$feederSchool = ( isset( $row[4] ) ) ? $row[4] : '';

				$newExpiring = new Expiration();
				$newExpiring->setOpenEnrollment( $enrollment );
				$newExpiring->setStudentID( $row[0] );
				$newExpiring->setFirstName( $row[1] );
				$newExpiring->setLastName( $row[2] );
				$newExpiring->setExpiring( $row[3] );
				$newExpiring->setFeederSchool($feederSchool );

				$em->persist( $newExpiring );
			}
			$em->flush();
			$em->clear();

			$enrollment = null;
			$arrayOfExcelData = null;

			return 'Successful updated the expiring data';
		}

		$enrollment = null;
		$arrayOfExcelData = null;

		return 'Columns are incorrect. They should be studentID, studentFirstName, studentLastName, expiring, feederSchool';
	}

	/**
	 * Imports the address file to allow the Address Bounds to be UPDATED Manually.
	 *
	 * @param $currentFile
	 *
	 * @return string
	 */
	private function importAddressData( $currentFile ) {

		$path_parts = pathinfo( $currentFile );

		if( $path_parts['extension'] != 'csv' ) {
			return 'Error! File must be a CSV file. Please correct and re-upload.';
		}

		$columns = '';
		$handle = fopen( $currentFile , "r" );
		if( $handle ) {
			$columns = trim( fgets( $handle , 4096 ) );
		}
		fclose( $handle );

		if( !empty( $columns ) ) {
			$columns = preg_split( '/,/' , $columns );
		}


		$dbname = $this->container->getParameter( 'database_name' );
		$dbhost = $this->container->getParameter( 'database_host' );
		$dbport = $this->container->getParameter( 'database_port' );
		$dbuser = $this->container->getParameter( 'database_user' );
		$dbpass = $this->container->getParameter( 'database_password' );

		$dsn = 'mysql:dbname=' . $dbname . ';host=' . $dbhost . ';port=' . $dbport;

		$pdo = new \PDO( $dsn , $dbuser , $dbpass , array( \PDO::MYSQL_ATTR_LOCAL_INFILE => 1 ) );

		$truncateStatement = $pdo->prepare( 'TRUNCATE TABLE `AddressBound`;' );
		$truncateResponse = $truncateStatement->execute();
		if( !$truncateResponse ) {
			return 'Could not empty out Addresss table. Try again or contact your IT administrator';
		}
		$truncateStatement->closeCursor();

		try {
			$studentStatement = $pdo->prepare( 'LOAD DATA LOCAL INFILE \'' . $currentFile . '\' INTO TABLE `AddressBound` FIELDS TERMINATED BY \',\' ENCLOSED BY \'"\' ESCAPED BY \'\\\\\' LINES TERMINATED BY \'\n\' IGNORE 1 LINES (`' . implode( '`, `' , $columns ) . '`);' );
			$responseStudent = $studentStatement->execute();
			if( !$responseStudent ) {
				return 'Could not load address file. Please ensure your file complete.';
			}
			$studentStatement->closeCursor();
		} catch( \Exception $e ) {
			return 'There was an error. Please ensure your file is in CSV format.';
		}

		return 'Successfully updated Address bounds data.';
	}

	/**
	 * This function is used to handle the importing of the iNow Data.
	 *
	 * @param PHPExcel $excelFile
	 *
	 * @return string
	 */
	private function importINowData( PHPExcel $excelFile ) {

		//convert
		$arrayOfExcelData = $excelFile->getActiveSheet()->toArray( null , true , false , false );
		$arrayOfExcelData = array_chunk( $arrayOfExcelData , 1000 , false );
		unset( $excelFile );

		$conn = 'mysql:dbname=' . $this->container->getParameter( 'database_name' ) .
			';host=' . $this->container->getParameter( 'database_host' ) .
			';port=' . $this->container->getParameter( 'database_port' );

		$dhn = new \PDO( $conn , $this->container->getParameter( 'database_user' ) , $this->container->getParameter( 'database_password' ) );

		$selectStatement = $dhn->prepare( "SELECT id from stw_inow WHERE studentID = :studentID LIMIT 1" );
		$insertStatement = $dhn->prepare( "INSERT INTO stw_inow (first_name, last_name, studentID, race, address, city, zip, school, dob, grade) VALUES (:firstName, :lastName, :studentID, :race, :address, :city, :zip, :school, :dob, :grade);" );
		$updateStatement = $dhn->prepare( "UPDATE stw_inow SET first_name = :firstName, last_name = :lastName, studentID = :studentID, race = :race, address = :address, city = :city, zip = :zip, school = :school, dob = :dob, grade = :grade WHERE stw_inow.id = :ID;" );

		$ID = '';
		$firstName = '';
		$lastName = '';
		$race = '';
		$address = '';
		$city = '';
		$zip = '';
		$school = '';
		$dob = '';
		$grade = '';

		$updateStatement->bindParam( ':ID' , $ID );
		$updateStatement->bindParam( ':firstName' , $firstName );
		$updateStatement->bindParam( ':lastName' , $lastName );
		$updateStatement->bindParam( ':studentID' , $studentID );
		$updateStatement->bindParam( ':race' , $race );
		$updateStatement->bindParam( ':address' , $address );
		$updateStatement->bindParam( ':city' , $city );
		$updateStatement->bindParam( ':zip' , $zip );
		$updateStatement->bindParam( ':school' , $school );
		$updateStatement->bindParam( ':dob' , $dob );
		$updateStatement->bindParam( ':grade' , $grade );

		$count = 1;
		$firstRowFlag = true;
		$index = 0;
		$studentIDs = array();

		foreach( $arrayOfExcelData as &$arrChunk ) {

			//check to see if the file contains the ADM data fields
			if( strpos( $arrayOfExcelData[0][0][0] , 'StateIDNumber' ) !== false &&
				strpos( $arrayOfExcelData[0][0][1] , 'LastName' ) !== false &&
				strpos( $arrayOfExcelData[0][0][2] , 'FirstName' ) !== false &&
				strpos( $arrayOfExcelData[0][0][3] , 'dateofbirth' ) !== false &&
				strpos( $arrayOfExcelData[0][0][4] , 'SchoolName' ) !== false &&
				strpos( $arrayOfExcelData[0][0][5] , 'Gradelevel' ) !== false &&
				strpos( $arrayOfExcelData[0][0][6] , 'Race' ) !== false &&
				strpos( $arrayOfExcelData[0][0][7] , 'StreetNumber' ) !== false &&
				strpos( $arrayOfExcelData[0][0][8] , 'AddressLine1' ) !== false &&
				strpos( $arrayOfExcelData[0][0][9] , 'AddressLine2' ) !== false &&
				strpos( $arrayOfExcelData[0][0][10] , 'City' ) !== false &&
				strpos( $arrayOfExcelData[0][0][11] , 'PostalCode' ) !== false
			) {


				//echo '<pre>Correct ADM file, now importing into database!</pre>';
				//Excel file has correct columns, now loop through the data and put data in database
				foreach( $arrChunk as $row ) {
					//$em = $this->getDoctrine()->getManager();

					if( !$firstRowFlag ) {

						if( strpos( $row[0] , '---' ) !== false &&
							strpos( $row[1] , '---' ) !== false &&
							strpos( $row[2] , '---' ) !== false &&
							strpos( $row[3] , '---' ) !== false &&
							strpos( $row[4] , '---' ) !== false &&
							strpos( $row[5] , '---' ) !== false &&
							strpos( $row[6] , '---' ) !== false &&
							strpos( $row[7] , '---' ) !== false &&
							strpos( $row[8] , '---' ) !== false &&
							strpos( $row[9] , '---' ) !== false &&
							strpos( $row[10] , '---' ) !== false &&
							strpos( $row[11] , '---' ) !== false
						) {
							continue;
						}

						//$emLookup = ;
						//$doc = $this->getDoctrine();

						//no column data in the file.
						//Still need to make sure each row has all 10 columns the script needs. if not do not import the student.
						//Require a race, if empty, more than likely the columns aren't correct.
						//if( $row[4] == 'NULL' ) {
						//	$row[4] = ''; //Index 4 is text NULL, lets blank it out.
						//	array_splice( $row , 5 , 0 , '' ); //Since we add the blank index.
						//	array_pop( $row ); //We need to pop off the 11 items.
						//}
						//$address = $row[4]; //. $row[5]; //Join the two columns for the address.
						//$addressArray = preg_split( $regex , $address , -1 , PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

						$address = trim( trim( $row[7] ) . ' ' . trim( $row[8] ) . ' ' . trim( $row[9] ) );
						if( empty( $address ) ) {
							$address = '';
						}

						$row[10] = trim( $row[10] );
						$row[11] = trim( $row[11] );

						$city = ( empty( $row[10] ) ? '' : $row[10] );
						$zip = ( empty( $row[11] ) ? '' : $row[11] );

						//Remove all other words we don't need: High, Middle, Elem, and School. Replaces with ''
						$school = trim( preg_replace( '/(Elementary)|(High)|(Middle)|(School)/i' , '' , $row[4] ) );

						//This checks to see the student Records already exists in the database.
						//$student = $doc->getRepository( 'IIABStudentTransferBundle:Student' )->findOneBy( array(
						//	'studentID' => $row[0]
						//) );

						$studentID = $row[0];
						$selectStatement->bindParam( ':studentID' , $studentID );
						$selectStatement->execute();
						$results = $selectStatement->fetch( \PDO::FETCH_ASSOC );

						$firstName = $row[2];
						$lastName = $row[1];
						$race = $row[6];
						$dob = date( 'n/j/Y' , strtotime( $row[3] ) );
						$grade = $row[5];


						if( isset( $results['id'] ) ) {
							$ID = $results['id'];
							$updateStatement->bindParam( ':ID' , $ID );
							$updateStatement->bindParam( ':firstName' , $firstName );
							$updateStatement->bindParam( ':lastName' , $lastName );
							$updateStatement->bindParam( ':studentID' , $studentID );
							$updateStatement->bindParam( ':race' , $race );
							$updateStatement->bindParam( ':address' , $address );
							$updateStatement->bindParam( ':city' , $city );
							$updateStatement->bindParam( ':zip' , $zip );
							$updateStatement->bindParam( ':school' , $school );
							$updateStatement->bindParam( ':dob' , $dob );
							$updateStatement->bindParam( ':grade' , $grade );
							//$updateStatement->debugDumpParams();
							$updateStatement->execute();
						} else {
							$ID = 0;
							$insertStatement->bindParam( ':firstName' , $firstName );
							$insertStatement->bindParam( ':lastName' , $lastName );
							$insertStatement->bindParam( ':studentID' , $studentID );
							$insertStatement->bindParam( ':race' , $race );
							$insertStatement->bindParam( ':address' , $address );
							$insertStatement->bindParam( ':city' , $city );
							$insertStatement->bindParam( ':zip' , $zip );
							$insertStatement->bindParam( ':school' , $school );
							$insertStatement->bindParam( ':dob' , $dob );
							$insertStatement->bindParam( ':grade' , $grade );
							//$insertStatement->debugDumpParams();
							$insertStatement->execute();
						}
						unset( $ID , $results , $firstName , $lastName , $race , $dob , $grade , $address , $city , $zip , $school ); // , $emLookup );
						$count++;

					} else {
						//skipped first row
						$firstRowFlag = false;
					}
				}
			}
			$index++;
			unset( $arrChunk[$index] );
		}

		unset( $arrayOfExcelData , $studentIDs );

		if( $count > 0 ) {
			$this->recordAudit( 9 , 0 );

			return $this->get( 'translator' )->trans( 'import.form.success' , array( 'type' => $this->get( 'translator' )->trans( 'import.form.inow' , array() , 'IIABStudentTransferBundle' ) ) , 'IIABStudentTransferBundle' );
		} else {

			$this->recordAudit( 17 , 0 );

			return $this->get( 'translator' )->trans( 'import.form.inowError' , array() , 'IIABStudentTransferBundle' );
		}
	}

	/**
	 * This function is used to handle the importing of the New School Data.
	 *
	 * @param PHPExcel       $excelFile
	 * @param OpenEnrollment $enrollment
	 *
	 * @return string
	 */
	private function importNewSchoolsData( PHPExcel $excelFile , OpenEnrollment $enrollment ) {

		//convert


		$submission = $this->getDoctrine()->getRepository( 'IIABStudentTransferBundle:Submission' )->findOneBy( array(
			'enrollmentPeriod' => $enrollment
		) );

		if( $submission ) {
			return $this->get( 'translator' )->trans( 'import.form.admErrorSubmission' , array() , 'IIABStudentTransferBundle' );
		}

		//TODO: Send audit about who imported new school data.

		$arrayOfExcelData = $excelFile->getActiveSheet()->toArray( null , true , false , false );
		unset( $excelFile );

		$em = $this->getDoctrine()->getManager();

		//check to see if the file contains the new School data fields
		if( strpos( $arrayOfExcelData[0][0] , 'Current School Year School Name' ) !== false &&
			strpos( $arrayOfExcelData[0][1] , 'Current School Year  School ID' ) !== false &&
			strpos( $arrayOfExcelData[0][2] , 'Grade number' ) !== false &&
			strpos( $arrayOfExcelData[0][3] , 'Next School Year School Name' ) !== false &&
			strpos( $arrayOfExcelData[0][4] , 'Next School Year  School ID' ) !== false &&
			strpos( $arrayOfExcelData[0][5] , 'Grade Number' ) !== false
		) {

			//Excel file has correct columns, now loop through the data and put data in database
			$firstRowFlag = true;
			foreach( $arrayOfExcelData as $row ) {
				//make sure to skip the first row
				if( $row[0] == null ) {
					break;
				} //we have reached the end of the new schools data break out of for loop
				if( !$firstRowFlag ) {
					$row = array_map( 'trim' , $row );
					/*
					echo '<pre>Current School Year School Name: ' . print_r( $row[0] , true ) . '</pre>';
					echo '<pre>Current School Year  School ID: ' . print_r( (int) $row[1] , true ) . '</pre>';
					echo '<pre>Grade number: ' . print_r(  , true ) . '</pre>';
					echo '<pre>Next School Year School Name: ' . print_r( $row[3] , true ) . '</pre>';
					echo '<pre>Next School Year  School ID: ' . print_r( (int) $row[4] , true ) . '</pre>';
					echo '<pre>Grade Number: ' . print_r(   , true ) . '</pre>';
					*/

					$newSchool = new NewSchool();
					$newSchool->setCurrentSchool( $row[0] );
					$newSchool->setCurrentSchoolID( (int)$row[1] );
					$newSchool->setCurrentGrade( sprintf( '%1$02d' , (int)$row[2] ) );
					$newSchool->setNewSchool( $row[3] );
					$newSchool->setNewSchoolID( (int)$row[4] );
					$newSchool->setNewGrade( sprintf( '%1$02d' , (int)$row[5] ) );
					$newSchool->setEnrollmentPeriod( $enrollment );

					$em->persist( $newSchool );

				} else {
					//skipped first row
					$firstRowFlag = false;
				}
			}

			$em->flush();
			$em->clear();

			$this->recordAudit( 10 , 0 );

			return $this->get( 'translator' )->trans( 'import.form.success' , array( 'type' => $this->get( 'translator' )->trans( 'import.form.newschool' , array() , 'IIABStudentTransferBundle' ) ) , 'IIABStudentTransferBundle' );
		}
		//TODO: Edit error message for this audit code. The error should be because the new school information file was incorrect
		$this->recordAudit( 15 , 0 );

		return $this->get( 'translator' )->trans( 'import.form.newschoolError' , array() , 'IIABStudentTransferBundle' );
	}

	/**
	 * This function is used to handle the importing of the Employee Feeder Data.
	 */
	private function importEmployeeFeederData( PHPExcel $excelFile ) {

		echo '<pre>Importing Employee Feeder file!</pre>';
		return;
	}

	/**
	 * This function is used to handle the importing of the Slotting Data.
	 */
	private function importSlottingData( PHPExcel $excelFile , OpenEnrollment $enrollment ) {

		//echo '<pre>Importing Slotting Data file!</pre>';

		$arrayOfExcelData = $excelFile->getActiveSheet()->toArray( null , true , false , false );
		unset( $excelFile );

		$em = $this->getDoctrine()->getManager();

		//check to see if the file contains the slotting data fields
		if( strpos( $arrayOfExcelData[0][0] , 'School Name' ) !== false &&
			strpos( $arrayOfExcelData[0][1] , 'School ID' ) !== false &&
			strpos( $arrayOfExcelData[0][2] , 'Grade' ) !== false &&
			strpos( $arrayOfExcelData[0][3] , 'Available Slots' ) !== false &&
			strpos( $arrayOfExcelData[0][4] , 'Priority for Transfers? (yes - 1, no - 0)' ) !== false
		) {

			//Excel file has correct columns, now loop through the data and put data in database
			$firstRowFlag = true;

			foreach( $arrayOfExcelData as $row ) {
				//make sure to skip the first row
				if( $row[0] == null ) {
					break;
				} //we have reached the end of the slotting data break out of for loop
				if( !$firstRowFlag ) {
					$row = array_map( 'trim' , $row );

					if( strtoupper( $row[2] ) == 'P' ) {
						$row[2] = 99;
					}

					if( strtoupper( $row[2] ) == 'K' ) {
						$row[2] = 0;
					}

					$slotting = new Slotting();
					$slotting->setSchoolID( (int)$row[1] );
					$slotting->setGrade( sprintf( '%1$02d' , (int)$row[2] ) );
					$slotting->setAvailableSlots( (int)$row[3] );
					$slotting->setPriority( (int)$row[4] );
					$slotting->setEnrollmentPeriod( $enrollment );

					$em->persist( $slotting );

				} else {
					//skipped first row
					$firstRowFlag = false;
				}
			}

			$em->flush();
			$em->clear();

			$this->recordAudit( 11 , 0 );

			return $this->get( 'translator' )->trans( 'import.form.success' , array( 'type' => $this->get( 'translator' )->trans( 'import.form.slotting' , array() , 'IIABStudentTransferBundle' ) ) , 'IIABStudentTransferBundle' );
		}

		//TODO: Edit error message for this audit code. The error should be because the slotting information file was incorrect.
		$this->recordAudit( 16 , 0 );

		return $this->get( 'translator' )->trans( 'import.form.slottingError' , array() , 'IIABStudentTransferBundle' );
		return;
	}

}
