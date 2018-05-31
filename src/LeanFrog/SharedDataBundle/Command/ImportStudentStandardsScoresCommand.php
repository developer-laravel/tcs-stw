<?php

namespace LeanFrog\SharedDataBundle\Command;

use LeanFrog\SharedDataBundle\Entity\Student;
use LeanFrog\SharedDataBundle\Entity\StudentGrade;
use LeanFrog\SharedDataBundle\Command\Traits\ImportTraits;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStudentStandardsScoresCommand extends ContainerAwareCommand {
    use ImportTraits;

    private $phpExcel;

    protected function configure() {

        $this
            ->setName( 'shared:student:standard:scores:import' )
            ->setDescription( 'Import Standards Scores data from File' )
            ->setHelp( <<<EOF
The <info>%command.name%</info> command imports student standards scores data from File.

<info>php %command.full_name%</info>

EOF
            );
    }

    protected function execute( InputInterface $input , OutputInterface $output ) {

        ini_set('memory_limit','2048M');

        $env = $this->getContainer()->get( 'kernel' )->getEnvironment();
        var_dump( 'Running Standards Scores Import at: ' . date('H:i' ) );

        $this->entity_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->entity_manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->phpExcel = $this->getContainer()->get( 'phpexcel' );

        $this->import_scores_from_file();

        $this->entity_manager->flush();
        var_dump( 'Completed Standards Scores Import at: ' . date('H:i') );
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