<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\{Input, InputOption};
use Clover\Classes\Transformer\BilingualMorphTokenizer;
use Clover\Classes\Transformer\CompleteTransformerLanguageModel;
use Clover\Implement\CommandInterface;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\Data\CSVHandler;

/**
 * Transformer Predict Command Class
 *
 * Command-line interface for running predictions using transformer language models.
 * Loads a trained model and generates text predictions based on input prompts.
 */
class TransformerPredictCommand implements CommandInterface
{
    /**
     * @var array Command options.
     */
    public $options = [];

    /**
     * TransformerPredictCommand constructor.
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
        return "transformer:predict";
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return "Run a prediction using the transformer language model";
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
        ini_set('memory_limit', '256M');

        $tokenizer = new BilingualMorphTokenizer('mecab', 'auto');

        $trainingData = [];

        $file = FileHandler::read(__DIR__."/../../../../dataset/tsv/tatoeba/sentence_pairs_jp_kr.tsv");
        $csv = CSVHandler::decode($file, "\t");
        foreach ($csv->slice(0, 10) as $data) {
            $trainingData[] = ['output' => sprintf("JP: %s KO: %s", $data[1], $data[3])];
        }
        $tokenizer = new BilingualMorphTokenizer('C:\mecab\bin\mecab.exe', 'auto');

        foreach ($trainingData as $train) {
            $tokenizer->encode($train['output']);
        }

        $model = new CompleteTransformerLanguageModel(
            modelFile: __DIR__ . "/train.json",
            tokenizer: $tokenizer
        );

        foreach ($trainingData as $train) {
            $tokenizer->encode($train['output']);
        }

        echo "@" . $model->generate('JP: 今日は６月１８日で、ムーリエルの誕生日です！ KO:', 50, 0.8) . "\n";
        echo "@" . $model->generate('JP: 何してるの？ KO:', 50, 0.8) . "\n";
        echo "@" . $model->generate('JP: すぐに戻ります。 KO:', 50, 0.8) . "\n";
        echo "@" . $model->generate('JP: 何してるの？ KO:', 50, 0.8) . "\n";
        echo "@" . $model->generate('JP: 私は眠らなければなりません。 KO:', 50, 0.8) . "\n";
        echo "@" . $model->generate('JP: パスワードは「Muiriel」です。 KO:', 50, 0.8) . "\n";
        echo "@" . $model->generate('JP: 会議は何時からですか？ KO:', 50, 0.8) . "\n";
        echo "@" . $model->generate('JP: ここで写真を撮るときはフラッシュを使わないでください。 KO:', 50, 0.8) . "\n";

        return true;
    }
}