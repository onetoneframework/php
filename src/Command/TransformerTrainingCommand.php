<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\{Input, InputOption};
use Clover\Classes\Data\CSVHandler;
use Clover\Classes\Transformer\BilingualMorphTokenizer;
use Clover\Classes\Transformer\CompleteTransformerLanguageModel;
use Clover\Implement\CommandInterface;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\System\Output;

/**
 * Transformer Training Command Class
 *
 * Command-line interface for training transformer language models.
 * Processes training datasets and trains the model with specified parameters.
 */
class TransformerTrainingCommand implements CommandInterface
{
    /**
     * @var array Command options.
     */
    public $options = [];

    /**
     * TransformerTrainingCommand constructor.
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
        return "transformer:train";
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return "Train the transformer language model with prepared datasets";
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
        $trainingData = [];

        $file = FileHandler::read(__DIR__."/../../../../dataset/tsv/tatoeba/sentence_pairs_jp_kr.tsv");
        $csv = CSVHandler::decode($file, "\t");
        foreach ($csv->slice(0, 100) as $data) {
            $trainingData[] = ['output' => sprintf("JP: %s KO: %s", $data[1], $data[3])];
        }
        $tokenizer = new BilingualMorphTokenizer('C:\mecab\bin\mecab.exe', 'auto');

        foreach ($trainingData as $train) {
            $tokenizer->encode($train['output']);
        }

        $modelVocab = $tokenizer->getVocabSize();
        Output::printLine("Model Vocab Size: $modelVocab");

        $model = new CompleteTransformerLanguageModel(
            modelFile: __DIR__ . "/train.json",
            tokenizer: $tokenizer,
            max_seq_len: 32,
            num_heads: 4,
            d_ff: 128,
            num_layers: 2
        );

        $model->train($trainingData, 100, 1e-3, 8);

        return true;
    }
}