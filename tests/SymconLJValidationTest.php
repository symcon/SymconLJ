<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class SymconLJValidationTest extends TestCaseSymconValidation
{
    public function testValidateSymconLJ(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateSymconLJModule(): void
    {
        $this->validateModule(__DIR__ . '/../LJQuick');
    }
}