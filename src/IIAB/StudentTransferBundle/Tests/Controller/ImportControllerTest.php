<?php

namespace IIAB\StudentTransferBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImportControllerTest extends WebTestCase
{
    public function testImport()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/admin/import');
    }

    public function testImportinowdata()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/admin/import/inowdata');
    }

    public function testImportemployeefeederdata()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/admin/import/employee-feeder');
    }

    public function testImportadm()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/admin/import/admdata');
    }

}
