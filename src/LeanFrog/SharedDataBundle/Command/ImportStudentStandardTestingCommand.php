<?php

namespace LeanFrog\SharedDataBundle\Command;

use LeanFrog\SharedDataBundle\Entity\Student;
use LeanFrog\SharedDataBundle\Entity\StudentData;
use LeanFrog\SharedDataBundle\Command\Traits\ImportTraits;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStudentStandardTestingCommand extends ContainerAwareCommand {
    use ImportTraits;

    private $phpExcel;

    protected function configure() {

        $this
            ->setName( 'shared:student:testing:import' )
            ->setDescription( 'Import Standardized Testing data from File' )
            ->setHelp( <<<EOF
The <info>%command.name%</info> command imports student standardized testing results data from File.

<info>php %command.full_name%</info>

EOF
            );
    }

    protected function execute( InputInterface $input , OutputInterface $output ) {

        ini_set('memory_limit','2048M');

        $env = $this->getContainer()->get( 'kernel' )->getEnvironment();
        var_dump( 'Running Standardized Testing Import in environment: ' . $env );

        $this->entity_manager = $this->getContainer()->get( 'doctrine' )->getManager('shared');
        $this->entity_manager->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->phpExcel = $this->getContainer()->get( 'phpexcel' );

        $this->import_testing_from_file();

        $this->entity_manager->flush();
        var_dump( 'Completed Standardized Testing Import in envinronment: ' . $env );
    }

    public function find_files_in_directory(){

        $directory = MYPICK_CONFIG['student_standard_testing_file_directory'];
        $all_files = scandir( $directory );

        $excel_files = [];
        foreach( $all_files as $file ){
            if( strpos($file, '.xls') ){

                $keep_file = '';
                foreach( MYPICK_CONFIG['student_standard_testing_file_names'] as $file_type => $file_name ){
                    $keep_file = ( strpos( $file, $file_name ) === 0 ) ? $file_type : $keep_file;
                }
                if( $keep_file ){

                    if( isset( $excel_files[ $keep_file ] ) ){
                        $excel_files[ $keep_file ] =
                            ( filemtime( $directory.$file ) >
                              filemtime( $excel_files[ $keep_file ] )
                            )
                            ? $directory.$file : $excel_files[ $keep_file ];
                    } else {
                        $excel_files[ $keep_file ] = $directory.$file;
                    }
                }
            }
        }
        return $excel_files;
    }

    public function open_excel_file( $filename ){

        if (!file_exists($filename)) {
            $file = explode( '/', $filename );
            exit("Can't find file ". end($file) );
        }
        return $this->phpExcel->createPHPExcelObject($filename);
    }

    public function import_testing_from_file(){

        $test_scores = [];
        foreach( $this->find_files_in_directory() as $test_type => $file ){

            $test_scores[ $test_type ] = [];

            $file = $this->open_excel_file( $file );
            $sheet = $file->getActiveSheet();

            $max_column = $sheet->getHighestDataColumn();
            $id_column = '';
            $score_column = '';

            for( $column = 'A'; $column <= $max_column; $column++ ){

                if( strtolower( $sheet->getCell( $column.'1' ) ) == 'student id' ){
                    $id_column = $column;
                }

                if( strpos( strtolower( $sheet->getCell( $column.'1' ) ), 'sip' ) !== false ){
                    $score_column = $column;
                }

            }

            if( $id_column && $score_column ){

                $max_row = $sheet->getHighestDataRow();
                for( $row = 2; $row <= $max_row; $row++ ){

                    $test_scores[ $test_type ][ $sheet->getCell($id_column.$row )->getValue() ] =
                        $sheet->getCell($score_column.$row )->getValue();
                }
            }
        }

        $students = $this->entity_manager->getRepository('lfSharedDataBundle:Student')->findAll();

        $students_hash = [];
        foreach( $students as $student ){
            $students_hash[ $student->getStateID() ] = $student;
        }

        foreach( $test_scores as $test_type => $scores ){

            $delete_test_scores = $this->entity_manager->createQueryBuilder()
                ->delete('lfSharedDataBundle:StudentData', 'sd')
                ->where('sd.metaKey = :metaKey')
                ->setParameter('metaKey', $test_type .'_test')
                ->getQuery();
            $delete_test_scores->execute();

            foreach( $scores as $state_id => $score ){

                if( isset( $students_hash[ $state_id ] ) ){
                    $student = $students_hash[ $state_id ];
                    $student_data = new StudentData();
                    $student_data->setStudent( $student );
                    $student_data->setMetaKey( $test_type.'_test' );
                    $student_data->setMetaValue( $score );

                    $student->addAdditionalDatum( $student_data );
                    $this->entity_manager->persist( $student );
                    $this->entity_manager->persist( $student_data );

                    $this->maybe_flush();
                }
            }
        }
    }
}