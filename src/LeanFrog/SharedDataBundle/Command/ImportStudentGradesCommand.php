<?php

namespace LeanFrog\SharedDataBundle\Command;

use LeanFrog\SharedDataBundle\Entity\StudentGrade;
use LeanFrog\SharedDataBundle\Connection\InowAPIConnection;
use LeanFrog\SharedDataBundle\Command\Traits\ImportTraits;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStudentGradesCommand extends ContainerAwareCommand {
    use ImportTraits;

    private $year;

    protected function configure() {

        $this
            ->setName( 'shared:student:grade:import' )
            ->setDescription( 'Import Student Grades from API' )
            ->setHelp( <<<EOF
The <info>%command.name%</info> command imports student grades from the iNow database API.

<info>php %command.full_name%</info>

EOF
            );
    }

    protected function execute( InputInterface $input , OutputInterface $output ) {

        ini_set('memory_limit','5120M');

        $env = $this->getContainer()->get( 'kernel' )->getEnvironment();
        var_dump( 'Running Student Grade Import at: ' . date('H:i') );

        $this->entity_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->entity_manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->connection = new InowAPIConnection();
        $this->import_grades_from_inow();

        $this->import_scores_from_file();

        $this->entity_manager->flush();
        var_dump( 'Completed Student Grade Import at: ' . date('H:i') );
     }

    protected function import_grades_from_inow() {

        $delete_grades = $this->entity_manager->createQueryBuilder()
            ->delete('lfSharedDataBundle:StudentGrade', 'sg')
            ->getQuery();
        $delete_grades->execute();

        // Get all Academic Sessions
        $sessions_hash = [
            'current' => [],
            'late' => []
        ];
        $acad_sessions = $this->connection->get_response( 'acadsessions' );

        $grading_periods_hash = [];
        $graded_items_hash = [];
        $graded_items_terms = [];
        foreach($acad_sessions as $acad_session){

            $year_offset = intval( $acad_session->AcadYear ) - $this->getYear();

            if( $year_offset == 0 ){
                $sessions_hash['current'][ $acad_session->Id ] = $acad_session;
            } else if( $year_offset == -1 ){
                $sessions_hash['last'][ $acad_session->Id ] = $acad_session;
            }
        }

        uasort( $sessions_hash['current'], function ($a, $b)
        {
            if ($a->StartDate == $b->StartDate) {
                return 0;
            }
            return ($a->StartDate < $b->StartDate) ? 1 : -1;
        });

        uasort( $sessions_hash['last'], function ($a, $b)
        {
            if ($a->StartDate == $b->StartDate) {
                return 0;
            }
            return ($a->StartDate < $b->StartDate) ? 1 : -1;
        });

        $new = 0;
        $duplicates = [];

        $enrollment_statuses = [
            'Enrolled',
            'Registered'
        ];

        $alphaGrade_hash = [];
        $schools_hashed = [];
        foreach ($sessions_hash as $sessions) {

            foreach ($sessions as $session_id => $acad_session) {

                if( !in_array( $acad_session->SchoolId, $schools_hashed ) ){
                    $schools_hashed[] = $acad_session->SchoolId;

                    $alphaGrades = $this->connection->get_response('/schools/'. $acad_session->SchoolId . '/alphaGrades');

                    foreach( $alphaGrades as $alphaGrade ){
                        $alphaGrade_hash[ $alphaGrade->Id ] = $alphaGrade->Name;
                    }
                }
            }
        }
        unset( $schools_hashed );

        $find_students = [];
        foreach ($sessions_hash as $sessions) {

            foreach( $enrollment_statuses as $status ) {

                foreach ($sessions as $session_id => $acad_session) {

                    $grades = $this->connection->get_response($session_id . '/students/grades');

                    $grading_periods = $this->connection->get_response( $acad_session->Id .'/gradingPeriods' );

                    foreach( $grading_periods as $grading_period ){
                        if( is_object( $grading_period ) ){

                            switch( $grading_period->Name ){
                                case '1st Nine Weeks':
                                case '1st 9 weeks':
                                    $term = '1st 9 weeks';
                                    break;
                                case '2nd Nine Weeks':
                                case '2nd 9 weeks':
                                    $term = '2nd 9 weeks';
                                    break;
                                case '3rd Nine Weeks':
                                case '3rd 9 weeks':
                                    $term = '3rd 9 weeks';
                                    break;
                                case '4th Nine Weeks':
                                case '4th 9 weeks':
                                    $term = '4th 9 weeks';
                                    break;
                                default:
                                    $term = $grading_period->Name;
                                    break;
                            }

                            $grading_periods_hash[ $grading_period->Id ] = $term;
                        }
                    }

                    $graded_items = $this->connection->get_response( $acad_session->Id .'/gradedItems' );

                    foreach( $graded_items as $graded_item ){
                        if( is_object( $graded_item ) ){

                            $item_name = strtoupper( trim( $graded_item->Name ) );
                            $term = '';

                            switch( $item_name ){
                                case 'AVG 1':
                                case 'AVG 2':
                                case 'AVG 3':
                                case 'AVG 4':
                                    $term = $grading_periods_hash[ $graded_item->GradingPeriodId ];
                                    break;
                                case 'TRM 1':
                                    $term = 'semester 1';
                                    break;
                                case 'TRM 2':
                                    $term = 'semester 2';
                                    break;
                                case 'FINAL':
                                case 'TSCRPT':
                                case 'TSCRIPT':
                                case 'TRANS':
                                case 'EXM 1':
                                case 'EXM 2':
                                case 'EXM 3':
                                case 'EXM 4':
                                case 'GRD 1':
                                case 'GRD 2':
                                case 'GRD 3':
                                case 'GRD 4':
                                case 'BTEST1':
                                case 'BTEST2':
                                case 'BTEST3':
                                case 'BTEST4':
                                    $term = '';
                                    break;
                                default:
                                    var_dump(
                                        $graded_item->GradingPeriodId.' '.
                                        $graded_item->Id .' unknown term '.
                                        $graded_item->Name
                                    );
                                    $term = '';
                                    break;
                            }

                            $graded_items_terms[ $graded_item->Id ] = $term;
                            $graded_items_hash[ $graded_item->Id ] = $graded_item->GradingPeriodId;
                        }
                    }

                    $students = $this->connection->get_response($session_id . '/students?status=' . $status);
                    if (is_object($students)) {
                        $students = array();
                    };

                    foreach ($students as $index => $student) {

                        if ( is_object($student) && $student->StateIdNumber ) {
                            if( empty( $find_students[ $student->Id ] ) ){
                                $find_students[ $student->Id ] = $student ;
                            }
                        }
                    }
                }
            }
        }

        $students = $this->entity_manager->getRepository('lfSharedDataBundle:Student')->findAll();

        $students_hash = [];
        foreach( $students as $student ){
            $students_hash[ $student->getDborId() ] = $student;
        }

        $all_grades = [];
        $sections = [];
        $grades_records = 0;
        foreach( $sessions_hash as $sessions ){
            foreach( $sessions as $session_id => $acad_session ){

                $grades = $this->connection->get_response($session_id . '/students/grades');

                $year_offset = intval( $acad_session->AcadYear ) - $this->getYear();

                foreach( $grades as $grade ){

                    if( is_object( $grade ) ){

                        $grades_records ++;

                        if( empty( $sections[ $grade->SectionId ] ) ){

                            $section = $this->connection->get_response($session_id . '/sections/'. $grade->SectionId );

                            $course_type = false;
                            if( is_object( $section ) ){

                                switch( $section->CourseTypeId ){
                                    case 1:
                                        $course_type = '?business?' . $section->ShortName;
                                        break;
                                    case 2:
                                        $course_type = 'elective';
                                        break;
                                    case 3:
                                        if( strpos( strtolower( $section->ShortName ), 'read') !== false ){
                                            $course_type = 'reading';
                                        } else {
                                            $course_type = 'english';
                                        }
                                        break;
                                    case 4:
                                        $course_type = 'art';
                                        break;
                                    case 5:
                                        $course_type = 'foreign language';
                                        break;
                                    case 6:
                                        $course_type = 'health';
                                        break;
                                    case 7:
                                        $course_type = 'math';
                                        break;
                                    case 8:
                                        $course_type = '??' . $section->ShortName;
                                        break;
                                    case 9:
                                        $course_type = 'physical education';
                                        break;
                                    case 10:
                                        $course_type = 'science';
                                        break;
                                    case 11:
                                        $course_type = 'social';
                                        break;
                                    default:
                                        $course_type = '??' . $section->ShortName;
                                        break;
                                }

                                $sections[ $grade->SectionId ] = [
                                    'course_id' => $section->CourseTypeId,
                                    'course_type' => $course_type,
                                    'course_title' => $section->ShortName
                                ];
                            }

                        } else {
                            $course_type = $sections[ $grade->SectionId ]['course_type'];
                        }

                        if( empty( $graded_items_hash[ $grade->GradedItemId ] ) ){
                            $graded_item = $this->connection->get_response( $session_id . '/gradedItems/'. $grade->GradedItemId );

                            $graded_items_hash[ $grade->GradedItemId ] = $graded_item->GradingPeriodId;

                        }

                        if( $course_type
                            && in_array($course_type, ['reading', 'english', 'math', 'science', 'social'] )
                        ){

                            if( isset( $find_students[ $grade->StudentId ] ) ){

                                $commit_grade = true;

                                $state_id = $find_students[ $grade->StudentId ]->StateIdNumber;
                                if( empty( $state_id )
                                    || is_array( $state_id )
                                    || is_object( $state_id )
                                ){
                                    $commit_grade = false;
                                }

                                //$academic_term = $grading_periods_hash[ $graded_items_hash[ $grade->GradedItemId ] ];
                                $academic_term = $graded_items_terms[ $grade->GradedItemId ];
                                if( empty( $academic_term )
                                    || is_array( $academic_term )
                                    || is_object( $academic_term )
                                ){
                                    $commit_grade = false;
                                }

                                $course_id = $sections[ $grade->SectionId ]['course_id'];
                                if( empty( $course_id )
                                    || is_array( $course_id )
                                    || is_object( $course_id )
                                ){
                                    $commit_grade = false;
                                }

                                $course_title = $sections[ $grade->SectionId ]['course_title'];
                                if( empty( $course_title )
                                    || is_array( $course_title )
                                    || is_object( $course_title )
                                ){
                                    $commit_grade = false;
                                }

                                $numeric_grade = $grade->NumericGrade;
                                if( !is_numeric( $numeric_grade )
                                    || is_array( $numeric_grade )
                                    || is_object( $numeric_grade )
                                ){
                                    //$commit_grade = false;
                                }

                                $alpha_grade = ( isset( $alphaGrade_hash[ $grade->AlphaGradeId ] ) ) ? $alphaGrade_hash[ $grade->AlphaGradeId ] : '';

                                $commit_grade = ( empty( $students_hash[ $grade->StudentId ] ) ) ? false : $commit_grade;

                                if( $commit_grade ){
                                    $student_grade_object = new StudentGrade();

                                    $student_grade_object->setStudent( $students_hash[ $grade->StudentId ] );
                                    $student_grade_object->setAcademicYear( $this->getYear() + $year_offset );
                                    $student_grade_object->setAcademicTerm( $academic_term );
                                    $student_grade_object->setCourseTypeId( $course_id );
                                    $student_grade_object->setCourseType( $course_type );
                                    $student_grade_object->setCourseName( $course_title );
                                    $student_grade_object->SetSectionNumber( $grade->SectionId );
                                    $student_grade_object->setNumericGrade( $grade->NumericGrade );
                                    $student_grade_object->setAlphaGrade( $alpha_grade );

                                    $this->entity_manager->persist( $student_grade_object );

                                    $students_hash[ $grade->StudentId ]->addGrade( $student_grade_object );

                                    $this->entity_manager->persist( $students_hash[ $grade->StudentId ] );

                                    $this->maybe_flush();
                                }
                            }
                        }
                    }
                }
            }
        }
        var_dump( 'total grades '. $grades_records );
    }

    public function import_scores_from_file(){

        $scores_file = ( defined( 'MYPICK_CONFIG' ) && !empty( MYPICK_CONFIG['student_standards_scores_file'] ) ) ? MYPICK_CONFIG['student_standards_scores_file'] : false;

        if( $scores_file ){

            if( !file_exists( $scores_file ) ) {
                throw new \Exception( 'File could not be found. Please provide a file to import. Make sure to use the full path of the file.' );
            }

            try {
                $fp = fopen( $scores_file , 'r' );

                // Headrow
                $head = fgetcsv( $fp , 4096 , ',' , '"' , '\\' );

                $students = $this->entity_manager->getRepository('lfSharedDataBundle:Student')->findAll();

                $students_hash = [];
                foreach( $students as $student ){
                    $students_hash[ $student->getStateID() ] = $student;
                }

            } catch( \Exception $e ) {
                throw $e;
            }

            while( $column = fgetcsv( $fp , 4096 , ',' , '"' , '\\' ) ) {

                if( !empty( $column[2] )
                    && isset( $students_hash[ $column[2] ] )
                ){
                    $student_grade_object = new StudentGrade();

                        $student_grade_object->setStudent( $students_hash[ $column[2] ] );
                        $student_grade_object->setAcademicYear( $column[0] );
                        $student_grade_object->setAcademicTerm( $this->getScoreTerm( $column[6] ) );
                        $student_grade_object->setCourseTypeId( $column[3] );
                        $student_grade_object->setCourseType( $this->getScoreCourseType( $column[5] ) );
                        $student_grade_object->setCourseName( $column[5].': '. $column[8] );
                        $student_grade_object->SetSectionNumber( $column[7] );
                        $student_grade_object->setNumericGrade( $this->getScoreNumeric($column[9]) );
                        $student_grade_object->setAlphaGrade( $column[9] );

                        $this->entity_manager->persist( $student_grade_object );

                        $students_hash[ $column[2] ]->addGrade( $student_grade_object );

                        $this->entity_manager->persist( $students_hash[ $column[2] ] );

                        $this->maybe_flush();
                }
            }
        }
    }

    public function getScoreTerm( $term ){

        switch( $term ){
            case '1st 9wk':
                return '1st 9 weeks';
                break;
            case '2nd 9wk':
                return '2nd 9 weeks';
                break;
            case '3rd 9wk':
                return '3rd 9 weeks';
                break;
            case '4th 9wk':
                return '4th 9 weeks';
                break;
        }
    }

    public function getScoreCourseType( $course ){
        $course = explode( ',', $course )[0];
        $course = explode( ' ', $course)[0];
        return strtolower( $course );
    }

    public function getScoreNumeric( $score ){
        if( is_numeric($score) ){
            return $score;
        }

        switch($score){
            case 'N':
                return 1;
                break;
            case 'S':
                return 2;
                break;
            case 'P':
                return 3;
                break;
        }
    }
}