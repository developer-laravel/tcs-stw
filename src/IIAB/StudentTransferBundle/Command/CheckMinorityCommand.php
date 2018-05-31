<?php

namespace IIAB\StudentTransferBundle\Command;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use IIAB\StudentTransferBundle\Entity\Student;
use IIAB\StudentTransferBundle\Entity\Audit;

class CheckMinorityCommand extends ContainerAwareCommand {

	protected function configure() {
		$this
			->setName( 'iiab:check:minority' )
			->setDescription( 'Checks the Minority Status of a Student' )
			->addArgument( 'studentID' , InputArgument::REQUIRED , 'Student ID that you want to check it against' );
	}

	protected function execute( InputInterface $input , OutputInterface $output ) {
		$em = $this->getContainer()->get('doctrine')->getManager();
		$inputID = $input->getArgument( 'studentID' );
		$student = $em->getRepository('IIABStudentTransferBundle:Student')->findOneBy( array( 'studentID' => $inputID ) );
		$result = $this->checkMinorityStatus( $student );
		$output->writeln( $result );
	}

	protected function formatName( $name ) {
		$name = str_replace(' High', '', $name);
		$name = str_replace(' Middle', '', $name);
		$name = str_replace(' Elementary', '', $name);
		$name = str_replace(' School', '', $name);
		$name = str_replace(' P8', '', $name);
		return $name;
	}
	/**
	 * Checks the HSV City zoning information and compares it against the current student information.
	 * Then confirms if the student is a Majority or not. If the student is a Majority, then provide the
	 * drop down list with other schools where the student would be an minority.
	 *
	 * @param Student	$student
	 * @param 			$admin
	 * @param			$enrollment
	 * @param			$passing
	 *
	 * @return array|bool if the the student is a minority at their current zoned school.
	 */
	public function checkMinorityStatus( Student $student , $admin = false , $enrollment = '' , $passing = true ) {
		$returnArray = array();

		if( $student->getGrade() != 99 && $student->getGrade()+1 > 12 ) {
			//Student is going into 13th grade! AuditEntry and return false; AuditCode: submission error because student is going into 13th grade
			$this->recordAudit( 26 , 0 , $student->getStudentID() );
			return false;
		}

		if( $passing ) {
			$studentNextGrade = ( $student->getGrade() != 99 ? $student->getGrade() + 1 : 0 );
		} else {
			$studentNextGrade = $student->getGrade();
		}

		$zonedSchools = $this->getContainer()->get('stw.check.address')->checkAddress( array( 'student_status' => 'new' , 'address' => $student->getAddress() , 'zip' => $student->getZip() ) );
		//$getSchools = new GetZonedSchoolsCommand();
		//$getSchools->setContainer( $this->getContainer() );
		//$zonedSchools = $getSchools->getSchools( $student , $this->getContainer()->get( 'request' ) );
		//$getSchools = null;
		//unset( $getSchools );



		if( $zonedSchools == false ) {

			//AuditCode: submission error because HSV City zoning information was not found
			$this->recordAudit( 25 , 0 , $student->getStudentID() );

			//if address error and new student
			if ($student->getStudentID() != ''){
				$this->getContainer()->get( 'request' )->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get( 'translator' )->trans( 'errors.emptySchoolNonTCSstudent' , array() , 'IIABStudentTransferBundle' ) );
			}else {
				$this->getContainer()->get( 'request' )->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get( 'translator' )->trans( 'errors.emptySchool' , array() , 'IIABStudentTransferBundle' ) );
			}

			return false;
		}
		//Set the session variable for the zoned information
		$this->getContainer()->get('request')->getSession()->set( 'stw-formData-zoned' , base64_encode( serialize( $zonedSchools ) ) );
		//Remove HighSchool so the middle/high don't group the same ones. Remove the last index if the array is count of 3.


		//$studentNextGrade = 11;
		if( $studentNextGrade < 9 & count( $zonedSchools) == 3 ) { array_pop( $zonedSchools ); }
		if( $studentNextGrade > 8 & count( $zonedSchools) == 3 ) { $zonedSchools = array( array_pop( $zonedSchools ) ); }
		// ADDED AFTER CHANGING FROM SITE TO DB ZONES // DJW
		$zonedSchools = $this->formatName( $zonedSchools );

		$em = $this->getContainer()->get('doctrine')->getManager();

		//filters ADM data with the HSV City zoned data to get the student's next year zoned school
		$foundNextSchool = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
			->where( 'a.grade = :currentGrade' )
			->andWhere( 'UPPER(a.hsvCityName) IN (:schools)' )
			->andWhere( 'a.enrollmentPeriod = :enrollment' )
			->setParameter(	'currentGrade' , sprintf( '%1$02d' , $studentNextGrade ) )
			->setParameter(	'schools' , array_values( $zonedSchools ) )
			->setParameter( 'enrollment' , abs( $enrollment ) )
			->getQuery();

		$results = $foundNextSchool->getArrayResult();

		if( count( $results ) > 1 ) {
			//There is more than one school found for this next GRADE based on the zoning data.
			//So therefore we have to figure out which ADM record to use.
			$schoolEndGradeArray = array();
			foreach( $results as $index => $schoolFound ) {
				$schoolEndGrade = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
					->select( 'a.hsvCityName, a.grade' )
					->where( 'a.enrollmentPeriod = :enrollment' )
					->andWhere( 'a.schoolName = :schoolName' )
					->andWhere( 'a.grade != 99' )
					->setParameters( array(
						'enrollment' => abs( $enrollment ) ,
						'schoolName' => $schoolFound['schoolName']
					) )
					->orderBy( 'a.grade' , 'DESC' )
					->setMaxResults( 1 )
					->getQuery()
					->getResult();

				$schoolEndGradeArray[$index] = (int) $schoolEndGrade[0]['grade'];
			}

			//If the student next grade is less than both schools end grade.
			if( $studentNextGrade <= $schoolEndGradeArray[0] && $studentNextGrade <= $schoolEndGradeArray[1] ) {
				//then select the lowest end grade to move forward them.
				$results = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
					->where( 'a.grade = :currentGrade' )
					->andWhere( 'UPPER(a.hsvCityName) IN (:schools)' )
					->andWhere( 'a.enrollmentPeriod = :enrollment' )
					->setParameter(	'currentGrade' , sprintf( '%1$02d' , $studentNextGrade ) )
					->setParameter(	'schools' , $zonedSchools['Elementary'] )
					->setParameter( 'enrollment' , abs( $enrollment ) )
					->getQuery()->getArrayResult();
			} else {
				//else you choose the higher end grade.
				$results = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
					->where( 'a.grade = :currentGrade' )
					->andWhere( 'UPPER(a.hsvCityName) IN (:schools)' )
					->andWhere( 'a.enrollmentPeriod = :enrollment' )
					->setParameter(	'currentGrade' , sprintf( '%1$02d' , $studentNextGrade ) )
					->setParameter(	'schools' , $zonedSchools['Middle'] )
					->setParameter( 'enrollment' , abs( $enrollment ) )
					->getQuery()->getArrayResult();
			}
		}

		unset( $foundNextSchool );

		//Schools are empty, meaning there might be a new school this year.
		if( empty( $results ) ) {
			//query the new school data for HSV City zoned school to see if new school for next year
			$foundNextSchool = $em->getRepository( 'IIABStudentTransferBundle:NewSchool' )->createQueryBuilder( 'n' )
				->where( 'UPPER(n.currentSchool) IN (:schools)' )
				->andWhere( 'n.currentGrade = :currentGrade' )
				->andWhere( 'n.enrollmentPeriod = :enrollment' )
				->setParameter(	'schools' ,  array_values( $zonedSchools ) )
				->setParameter(	'currentGrade' , sprintf( '%1$02d' , $studentNextGrade ) )
				->setParameter( 'enrollment' , abs( $enrollment ) )
				->setMaxResults( 1 )
				->getQuery();
			$results = $foundNextSchool->getArrayResult();
			unset( $foundNextSchool );
			if( isset( $results[0] ) ) {
				//query ADM table for the new school
				$foundNextSchool = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
					->where( 'a.grade = :currentGrade' )
					->andWhere( 'a.schoolID = :schoolID' )
					->andWhere( 'a.enrollmentPeriod = :enrollment' )
					->setParameter(	'currentGrade' , $results[0]['newGrade'] )
					->setParameter(	'schoolID' , $results[0]['newSchoolID'] )
					->setParameter( 'enrollment' , abs( $enrollment ) )
					->setMaxResults( 1 )
					->getQuery()
				;
				$results = $foundNextSchool->getArrayResult();

				unset( $foundNextSchool );
			}
		}
		if( !isset( $results[0] ) ) {
			//No schools found in the system. Error out to call SSS. AuditCode: submission error because no schools were found for the student to transfer to
			$this->recordAudit( 22 , 0 , $student->getStudentID() );
			( $admin ) ? $admin->addFlashPublic( 'sonata_flash_error' , $this->getContainer()->get('translator')->trans( 'iiab.admin.errors.emptySchool' , array() , 'IIABStudentTransferBundle' ) )
					   : $this->getContainer()->get('request')->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.noAvailability' , array() , 'IIABStudentTransferBundle' ) );
			return false;
		}
		$results = $results[0];
		//Query the newSchools table with the ID and Grade from the query above to see if there is a new school this year. Then use this as the new base school, Not ADM. Else, then keep ADM school.
		$newSchools = $em->getRepository( 'IIABStudentTransferBundle:NewSchool' )->createQueryBuilder( 'n' )
			->where( 'n.currentSchoolID = :schoolID' )
			->andWhere( 'n.currentGrade = :schoolGrade' )
			->andWhere( 'n.enrollmentPeriod = :enrollment' )
			->setParameters( array(
				'schoolID' => $results['schoolID'],
				'schoolGrade' => $results['grade'],
				'enrollment' => abs( $enrollment ),
			) )
			->getQuery();
		$newSchool = $newSchools->getArrayResult();
		unset( $newSchools );

		//New School found, let use this new school to set use for the new data.
		if( isset( $newSchool[0] ) ) {
			$foundNextSchool = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
				->where( 'a.grade = :currentGrade' )
				->andWhere( 'a.schoolID = :schoolID' )
				->andWhere( 'a.enrollmentPeriod = :enrollment' )
				->setParameter(	'currentGrade' , $newSchool[0]['newGrade'] )
				->setParameter(	'schoolID' , $newSchool[0]['newSchoolID'] )
				->setParameter( 'enrollment' , abs( $enrollment ) )
				->setMaxResults( 1 )
				->getQuery();
			$results = $foundNextSchool->getArrayResult();
			unset( $foundNextSchool );

			$results = $results[0];
		}

		//Is the Student an Non-Expiring M2M Student??
		$nonExpiringStudent = false;
		$openEnrollment = $em->getRepository('IIABStudentTransferBundle:OpenEnrollment')->find( $enrollment );
		$studentIsCurrentlyOnM2MTransfer = $em->getRepository( 'IIABStudentTransferBundle:Expiration' )->findOneBy( array( 'studentID' => $student->getStudentID() , 'openEnrollment' => $openEnrollment ) );
		if( !empty( $studentIsCurrentlyOnM2MTransfer ) && ( !$studentIsCurrentlyOnM2MTransfer->getExpiring() ) ) {

			//Need to Filter out the current Schools so it is not confusing to the Parents or allows them to select ths school.
			$nonExpiringStudent = true;
		}

		//Now look at race to see if Student is Majority in the school returned from query above. If Majority, then query all schools for next years currentGrade where they'll be Minority. Else, return message that they cannot M2M transfer.
		//LEFT JOIN in the stw_slotting table to determine available slots for transfers to eligible schools.
		$majorityFlag = false;

		$foundNextSchools = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
			//->join( 'IIABStudentTransferBundle:Slotting' , 's' )//, 'WITH' , 'a.schoolID=s.schoolID')
			->where( 'a.grade = :currentGrade' )
			//->andWhere( 'a.enrollmentPeriod = s.enrollmentPeriod' )
			->andWhere( 'a.enrollmentPeriod = :enrollment' )
			->andWhere( 'a.black > 0 OR a.white > 0 OR a.other > 0') //Filtering out the Magnet Schools
			//->andWhere( 's.availableSlots > 0' )
			->setParameter(	'currentGrade' , sprintf( '%1$02d' , $studentNextGrade ) )
			->orderBy('a.schoolName' , 'ASC' )
			->setParameter( 'enrollment' , abs( $enrollment ) );

		if( $nonExpiringStudent ) {
			$foundNextSchools->andWhere( 'a.schoolName NOT LIKE :currentSchool' )->andWhere( 'a.hsvCityName NOT LIKE :currentSchool' )->setParameter( 'currentSchool' , $student->getSchool() . '%' );
		}

		$foundNextSchools = $foundNextSchools->getQuery();

		switch( trim( strtolower( $student->getRace() ) ) ) {

			case 'black':
			case 'black/african american':
				$majorityFlag = true;
				if( ( $results['blackPercent'] > 0.50 ) ) {
					foreach( $foundNextSchools->getArrayResult() as $school ) {
						if ( $school['blackPercent'] <  0.50 ) {
							$returnArray[$school['id']] = $school['schoolName'];
						}
					}
				} else {
					// Student is not majority - AuditCode: submission request cancelled because student is not majority race.
					$this->recordAudit( 23 , 0 , $student->getStudentID() );
					( $admin ) ? $admin->addFlashPublic( 'sonata_flash_error' , $this->getContainer()->get('translator')->trans( 'iiab.admin.errors.notMajority' , array() , 'IIABStudentTransferBundle' ) )
							   : $this->getContainer()->get('request')->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.notMajority' , array() , 'IIABStudentTransferBundle' ) );
					$returnArray = false;
				}
				break;
			case "white":
				$majorityFlag = true;
				if( ( $results['whitePercent'] > 0.50 ) ) {
					foreach( $foundNextSchools->getArrayResult()  as $school ) {
						if ( $school['whitePercent'] < 0.50 ) {
							$returnArray[$school['id']] = $school['schoolName'];
						}
					}
				} else {
					// Student is not majority - AuditCode: submission request cancelled because student is not majority race.
					$this->recordAudit( 23 , 0 , $student->getStudentID() );
					( $admin ) ? $admin->addFlashPublic( 'sonata_flash_error' , $this->getContainer()->get('translator')->trans( 'iiab.admin.errors.notMajority' , array() , 'IIABStudentTransferBundle' ) )
						  	   : $this->getContainer()->get('request')->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.notMajority' , array() , 'IIABStudentTransferBundle' ) );
					$returnArray = false;
				}
				break;
			default:
				// Student is not majority - AuditCode: submission request cancelled because student is not majority race.
				$this->recordAudit( 23 , 0 , $student->getStudentID() );
				( $admin ) ? $admin->addFlashPublic( 'sonata_flash_error' , $this->getContainer()->get('translator')->trans( 'iiab.admin.errors.notMajority' , array() , 'IIABStudentTransferBundle' ) )
					: $this->getContainer()->get('request')->getSession()->getFlashBag()->add( 'error' , $this->getContainer()->get('translator')->trans( 'errors.notMajority' , array() , 'IIABStudentTransferBundle' ) );
				$returnArray = false;
				break;
		}
		return $returnArray;
	}

	/**
	 * @param int $auditCode
	 * @param int $submission
	 * @param int $studentID
	 *
	 * @return void
	 */
	private function recordAudit( $auditCode = 0 , $submission = 0 , $studentID = 0 ) {
		$em = $this->getContainer()->get('doctrine.orm.entity_manager');
		$user = $this->getContainer()->get( 'security.context' )->getToken()->getUser();
		$auditCode = $em->getRepository( 'IIABStudentTransferBundle:AuditCode' )->find( $auditCode );
		$audit = new Audit();
		$audit->setAuditCodeID( $auditCode );
		$audit->setIpaddress( '::1' );
		$audit->setSubmissionID( $submission );
		$audit->setStudentID( $studentID );
		$audit->setTimestamp( new \DateTime() );
		$audit->setUserID( ( $user == 'anon.' ? 0 : $user->getId() ) );
		$em->persist( $audit );
		$em->flush();
		$em->clear();
	}
}