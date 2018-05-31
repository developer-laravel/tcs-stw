<?php

namespace IIAB\StudentTransferBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use IIAB\StudentTransferBundle\Entity\Student;
use IIAB\StudentTransferBundle\Entity\Audit;

class GetAvailableSchoolCommand extends ContainerAwareCommand {

	protected function configure() {

		$this
			->setName( 'iiab:check:avaiable' )
			->setDescription( 'Gets the available schools for a Student' )
			->addArgument( 'studentID' , InputArgument::REQUIRED , 'Student ID that you want to check it against' )
		;
	}

	protected function execute( InputInterface $input , OutputInterface $output ) {

		$em = $this->getContainer()->get('doctrine')->getManager();
		$inputID = $input->getArgument( 'studentID' );

		$student = $em->getRepository('IIABStudentTransferBundle:Student')->findOneBy( array(
			'studentID' => $inputID
		) );

		$result = $this->getAvailableSchools( $student );

		$output->writeln( $result );
	}

	/**
	 * Checks the HSV City zoning information and compares it against the current student information.
	 * Then confirms if the student is a Majority or not. If the student is a Majority, then provide the
	 * drop down list with other schools where the student would be an minority.
	 *
	 * @param Student 	$student
	 * @param 			$admin
	 * @param 			$enrollment
	 * @param 			$passing
	 *
	 * @return array|bool if the the student is a minority at their current zoned school.
	 */
	public function getAvailableSchools( Student $student , $admin = false , $enrollment = '', $passing = 'y' ) {
		$returnArray = array();

		// Assign correct Next Grade
		// If student is PreK set to 0
		// If student is passing add 1 to current grade
		$studentNextGrade = ( $student->getGrade() != 99 ) ? ( strtolower( $passing ) == 'y' ) ? $student->getGrade() + 1 : $student->getGrade() : 0 ;

		if( $studentNextGrade > 12 ) {
			//Student is going into 13th grade! AuditEntry and return false;
			//AuditCode: submission error because student is going into 13th grade
			$this->recordAudit( 26 , 0 , $student->getStudentID() );
			return false;
		}

		/*
		$getSchools = new GetZonedSchoolsCommand();
		$getSchools->setContainer( $this->getContainer() );
		$zonedSchools = $getSchools->getSchools( $student , $this->getContainer()->get( 'request' ) );
		unset( $getSchools );

		if( !$zonedSchools ) {
			return $zonedSchools;
		}

		//Set the session variable for the zoned information
		$this->getContainer()->get('request')->getSession()->set( 'stw-formData-zoned' , base64_encode( serialize( $zonedSchools ) ) );
		*/

		$em = $this->getContainer()->get('doctrine')->getManager();

		$foundNextSchools = $em->getRepository( 'IIABStudentTransferBundle:ADM' )->createQueryBuilder( 'a' )
			->where( 'a.grade = :currentGrade' )
			->andWhere( 'a.enrollmentPeriod = :enrollment' )
			->setParameter(	'currentGrade' , sprintf( '%1$02d' , $studentNextGrade ) )
			->setParameter(	'enrollment' , abs( $enrollment ) )
			->orderBy('a.schoolName' , 'ASC' )
			->getQuery()
		;

		//building transfer school list to return
		foreach( $foundNextSchools->getArrayResult()  as $school ) {

			$returnArray[$school['id']] = $school['schoolName'];
		}

		asort( $returnArray );

		//DEBUG TODO REMOVE
		/*
		if (!$majorityFlag && !empty($returnArray))
		{
			//print out student
			echo '<pre>1: ' . print_r( $student , true ) .'</pre>';
		}

		if ($majorityFlag && empty($returnArray))
		{
			//print out student
			echo '<pre>2: ' . print_r( $student , true ) .'</pre>';
		}
		*/

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
		$user = $this->getContainer()->get( 'security.token_storage' )->getToken()->getUser();

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
