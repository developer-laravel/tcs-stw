<?php

namespace LeanFrog\SharedDataBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use LeanFrog\SharedDataBundle\Entity\ProgramSchool;
use LeanFrog\SharedDataBundle\Entity\ProgramSchoolData;

class ProgramSchoolAdmin extends AbstractAdmin {

    /**
     * @var string
     */
    protected $baseRouteName = 'admin_program_schools';

    /**
     * @var string
     */
    protected $baseRoutePattern = 'program_schools';

    /**
     * @var array
     */
    protected $datagridValues = array(
        '_page' => 1 ,            // display the first page (default = 1)
        '_sort_order' => 'ASC' , // reverse order (default = 'ASC')
        '_sort_by' => 'name'  // name of the ordered field
        // (default = the model's id field, if any)

        // the '_sort_by' key can be of the form 'mySubModel.mySubSubModel.myField'.
    );

    /**
     * @param FormMapper $form
     */
    protected function configureFormFields( FormMapper $form ) {

        $doctrine = $this->getConfigurationPool()->getContainer()->get('doctrine');
        $shared_manager = $doctrine->getEntityManager('shared');
        $magnet_manager = false;
        $transer_manager = false;

        if( class_exists( '\\IIAB\\MagnetBundle\\Entity\\MagnetSchool') ){
            $magnet_manager = $doctrine->getEntityManager();
        }

        if( class_exists( '\\IIAB\\StudentTransferBundle\\Entity\\ADM') ){
            $transer_manager = $doctrine->getEntityManager();
        }

        $schools = [];

        if( $magnet_manager ){
            $all_schools = $magnet_manager
                ->getRepository('IIABMagnetBundle:MagnetSchool')
                ->findAll();

            foreach( $all_schools as $school ){
                $schools[ 'm-'.$school->getId() ] = $school->getOpenEnrollment()->__toString() .' '. $school->__toString();
            }
        }

        $object = $this->getSubject();
        $data = [];

        $linked_schools = $shared_manager
            ->getRepository('lfSharedDataBundle:ProgramSchoolData')
            ->findBy([
                //'programSchool' => $object->getChildren(),
                'metaKey' => [ 'mpw_magnet_school', 'stw_adm_school' ],
            ]);

        $linked_schools_hash = [];
        foreach( $linked_schools as $school ){
            $key = substr( $school->getMetaKey(), 0, 1) .'-'. $school->getMetaValue();
            $linked_schools_hash[ $school->getProgramSchool()->getId() ][$key] = $key;
        }

        foreach( $object->getChildren() as $child ){
            $data[ $child->getId() ] = [
                'gradeLevel' => $child->getGradeLevel(),
                'school' => (isset( $linked_schools_hash[$child->getId()] ) )
                    ? $linked_schools_hash[$child->getId()]
                    : null,
            ];
        }

        $form
            ->add( 'name' )
            ->add( 'children' , 'collection' , array(
                'entry_type' => ProgramSchoolChildType::class ,
                'allow_delete' => true ,
                'allow_add' => true ,
                'delete_empty' => true ,
                'prototype_name' => 'School' ,
                'label' => 'Grades' ,
                'data' => $data,
                'mapped' => false,
                'required' => false,
                'entry_options' => array(
                    'linked_entities' => array_flip( $schools ),
                ) ,
            )
        );
    }

    /**
     * @param ListMapper $list
     */
    protected function configureListFields( ListMapper $list ) {

        $list
            ->addIdentifier( 'name' );
    }

    /**
     * @param string $context
     *
     * @return \Sonata\AdminBundle\Datagrid\ProxyQueryInterface
     */
    public function createQuery( $context = 'list' ) {

        $query = parent::createQuery( $context );

        $query->andWhere( $query->getRootAlias() . '.parent IS NULL' );

        return $query;
    }

    /**
     * Sets the ProgramSchool data.
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchool $object
     *
     * @return void
     */
    public function preUpdate( $object ) {

        $uniqid = $this->getRequest()->query->get( 'uniqid' );
        $formData = $this->getRequest()->request->get( $uniqid );

        $em = $this->getConfigurationPool()
        ->getContainer()->get('doctrine')->getManager('shared');

        $child_school = [];
        foreach( $object->getChildren() as $child ){
            $child_school[$child->getId()] = $child;
        }

        $linked_schools = $em
            ->getRepository('lfSharedDataBundle:ProgramSchoolData')
            ->findBy([
                'metaKey' => [ 'mpw_magnet_school', 'stw_adm_school' ],
            ]);

        $linked_schools_hash = [];
        foreach( $linked_schools as $school ){
            $key = substr( $school->getMetaKey(), 0, 1) . $school->getMetaValue();
            $linked_schools_hash[ $school->getProgramSchool()->getId() ][$key] = $key;
        }

        if( isset( $formData['children'] ) ){
            foreach( $formData['children'] as $id => $child_data ){
                if( isset( $child_school[ $id ] ) ){
                    $child = $child_school[ $id ];
                    unset( $child_school[ $id ] );
                } else {
                    $child = new ProgramSchool();
                    $child
                        ->setParent( $object );
                }

                $child
                    ->setName( $object->getName() )
                    ->setGradeLevel( $child_data['gradeLevel'] );
                $em->persist( $child );

                if( isset( $child_data['school'] ) ){

                    foreach( $child_data['school'] as $key ){

                        $school_split = explode( '-', $key );
                        switch( $school_split[0] ){
                            case 'm':
                                $meta_key = 'mpw_magnet_school';
                                break;
                            case 's':
                                $meta_key = 'stw_adm_school';
                                break;
                            default:
                                $meta_key = '';
                        }

                        if( $meta_key ){

                            if( isset( $child_school[ $id ] )
                                && isset( $linked_schools_hash[$id][$key] )
                            ){
                                $linked_school = $linked_schools_hash[$id][$key];
                            } else {
                                $linked_school = new ProgramSchoolData();
                                $linked_school->setProgramSchool( $child );
                            }

                            $linked_school
                                ->setMetaKey( $meta_key )
                                ->setMetaValue( $school_split[1] );
                            $em->persist( $linked_school );
                        }
                    }
                }
            }
        }

        foreach( $child_school as $delete_me ){
            $object->removeChild( $delete_me );
            $delete_me->setParent(null);
            $em->remove( $delete_me );
        }

        $em->persist( $object );
        $em->flush();
    }

    protected function configureRoutes( RouteCollection $collection ) {

        //Clear all routes except list.
        $collection->remove( 'batch' );
        $collection->remove( 'delete' );
        $collection->remove( 'export' );
    }
}