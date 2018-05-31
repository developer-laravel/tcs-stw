<?php

namespace LeanFrog\SharedDataBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class lfSharedDataBundle extends Bundle
{

    public function boot(){
        if( !defined( 'MYPICK_CONFIG' ) ){
            define ( 'MYPICK_CONFIG' , false );
            //define ( 'MYPICK_CONFIG' , $this->container->getParameter('customer_configuration') );
        }
    }

}
