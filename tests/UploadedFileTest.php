<?php

class UploadedFileTest extends \PHPUnit_Framework_TestCase
{
    public function testPipeline()
    {
        $data = [];
        
        for($i = 0; $i < 5; ++$i)
        {
            $data[] = new stdClass();
        }
        
        for($i = 0; $i < count($data); $i++)
        {
            if (isset($data[$i + 1]))
            {
                $data[$i]->next = &$data[$i + 1];
            }
        }
        
        print_r($data);
        
        return $this->assertTrue(true);
    }
}
