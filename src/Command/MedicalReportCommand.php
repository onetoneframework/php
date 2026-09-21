<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\{Input, InputOption};
use Clover\Classes\Medical\Medical;
use Clover\Implement\CommandInterface;
use Clover\Classes\System\Output;

/**
 * Medical Report Command Class
 *
 * Command-line interface for generating medical health reports.
 * Processes patient data and generates comprehensive health analysis reports.
 */
class MedicalReportCommand implements CommandInterface
{
    /**
     * @var array Command options.
     */
    public $options = [];

    /**
     * MedicalReportCommand constructor.
     */
    public function __construct()
    {
        $this->options = [];
    }

    /**
     * Get the command name.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return "medical:report";
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return "Generate a medical health report based on patient data";
    }

    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->options[] = new InputOption('prompt', 'Prompt', 'How are you?');
    }

    /**
     * Execute the command.
     *
     * @param Input $input The input object containing options and arguments.
     * @return bool True on success, false on failure.
     */
    public function run(Input $input): bool
    {
        $medical = new Medical();
        $medical->setGender('male');
        $medical->setAge(31);
        $medical->setHemoglobinValue(15.3);
        $medical->setASTValue(33);
        $medical->setLdlValue(129);
        $medical->setGammaGTPValue(129);
        $medical->setTotalCholesterolValue(202);
        $medical->setEGprValue(72);
        $medical->setFBSValue(143);
        $medical->setSerumCreatinineValue(1.29);
        $medical->setDiastolicBloodPressureValue(85);
        $medical->setSystolicBloodPressureValue(145);

        Output::print($medical->getReport());

        return true;
    }
}