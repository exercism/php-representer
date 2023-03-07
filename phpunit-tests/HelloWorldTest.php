<?php

declare(strict_types=1);

namespace App\Tests;

use App\FilesRepresenter;
use App\Mapping;

class HelloWorldTest extends RepresenterTestCase
{
    public function testHelloWorld(): void
    {
        $code = <<<'CODE'
            <?php
            
            function helloWorld()
            {
                return "Hello World!";
            }
            CODE;

        $mapping = new Mapping();
        $result = (new FilesRepresenter($mapping))->represent($code);
        $this->assertEquals(<<<'EOF'
        function fn0()
        {
            return 'Hello World!';
        }
        EOF
        , $result);
        $this->assertEquals('{"fn0":"helloworld"}', $mapping->toJson());
    }
}
