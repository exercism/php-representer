<?php

declare(strict_types=1);

namespace App\Tests;

class FunctionReorderingTest extends RepresenterTestCase
{
    public function testFunctionReordering(): void
    {
        $this->markTestSkipped('Test is skipped because it is not implemented yet');

        //$codeA = <<<'CODE'
        //    <?php
        //    function helloWorldA() { return "Hello World A!"; }
        //    function helloWorldB() { return "Hello World B!"; }
        //    CODE;
        //
        //$codeB = <<<'CODE'
        //    <?php
        //
        //    function helloWorldB() { return "Hello World B!"; }
        //    function helloWorldA() { return "Hello World A!"; }
        //    CODE;
        //
        //$resultA = (new FileRepresenter($codeA))->represent();
        //$resultB = (new FileRepresenter($codeB))->represent();
        //$this->assertEquals($resultA->getRepresentationTxt(), $resultB->getRepresentationTxt());
        //$this->assertEquals($resultA->getRepresentationJson(), $resultB->getRepresentationJson());
        //$this->assertEquals('{"PLACEHODER_1": "a"}', $resultA->getMappingJson());
        //$this->assertEquals('{"PLACEHODER_1": "B"}', $resultB->getMappingJson());
    }
}
