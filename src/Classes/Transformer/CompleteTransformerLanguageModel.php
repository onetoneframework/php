<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Transformer;

use Clover\Classes\Math\MatrixOps;
use InvalidArgumentException;
use Exception;
use function array_slice;
use function count;
use function sprintf;

/**
 * Complete Transformer Language Model
 * * - Character-level Tokenizer
 * - Full Transformer Architecture
 * - Faithful implementation of the paper
 */
class CompleteTransformerLanguageModel
{
    private BilingualMorphTokenizer|CharacterTokenizer $tokenizer;
    private readonly int $d_model;
    private readonly int $num_layers;
    private readonly int $num_heads;
    private readonly int $d_ff;
    private readonly int $max_seq_len;

    private array $embedding;
    private array $positional_encoding;
    /** @var TransformerEncoderLayer[] $encoder_layers */
    private array $encoder_layers = [];
    private array $output_projection;
    private LayerNormalization $output_layer_norm;

    private string $modelFile;

    // add optimizer states for embeddings and output projection (Adam)
    private array $adam_m = []; // first moment
    private array $adam_v = []; // second moment
    private array $adam_m_out = []; // first moment for output_projection
    private array $adam_v_out = []; // second moment for output_projection
    private int $adam_t = 0; // time step

    // training batch size default
    private int $batch_size = 8;

    public function __construct(string $modelFile, int $d_model = 128, int $num_layers = 3, int $num_heads = 8, int $d_ff = 512, int $max_seq_len = 200, BilingualMorphTokenizer|CharacterTokenizer|null $tokenizer = null)
    {
        $this->modelFile = $modelFile;
        $this->d_model = $d_model;
        $this->num_layers = $num_layers;
        $this->num_heads = $num_heads;
        $this->d_ff = $d_ff;
        $this->max_seq_len = $max_seq_len;

        if ($this->d_model % $this->num_heads !== 0) {
            throw new InvalidArgumentException("d_model must be divisible by num_heads");
        }

        // Initialize Tokenizer
        $this->tokenizer = $tokenizer ?? new CharacterTokenizer();
        $vocab_size = $this->tokenizer->getVocabSize();

        // Embedding Layer
        $this->embedding = MatrixOps::xavierInit($vocab_size, $d_model);

        // Positional Encoding
        $this->positional_encoding = MatrixOps::createPositionalEncoding($max_seq_len, $d_model);

        // Transformer Encoder Layers
        for ($i = 0; $i < $num_layers; $i++) {
            $this->encoder_layers[] = new TransformerEncoderLayer($d_model, $num_heads, $d_ff);
        }

        // Output Projection
        $this->output_projection = MatrixOps::xavierInit($d_model, $vocab_size);
        $this->output_layer_norm = new LayerNormalization($d_model);

        // Initialize Adam states for embedding and output_projection
        $vocab_size = $this->tokenizer->getVocabSize();
        // m and v for embedding (vocab_size x d_model)
        $this->adam_m = MatrixOps::zeros($vocab_size, $d_model);
        $this->adam_v = MatrixOps::zeros($vocab_size, $d_model);
        // m and v for output_projection (d_model x vocab_size)
        $this->adam_m_out = MatrixOps::zeros($d_model, $vocab_size);
        $this->adam_v_out = MatrixOps::zeros($d_model, $vocab_size);
        $this->adam_t = 0;

        // Prevent tokenizer from adding new OOV tokens during runtime, shifting matrix size
        if (method_exists($this->tokenizer, 'freezeVocabulary')) {
            $this->tokenizer->freezeVocabulary();
        }

        // Attempt to load existing model
        $this->loadModel();
    }

    /**
     * Estimate parameter count
     */
    private function estimateParameters(): string
    {
        $vocab_size = $this->tokenizer->getVocabSize();
        $params = 0;

        // Embedding
        $params += $vocab_size * $this->d_model;

        // Each encoder layer
        $layer_params = 0;
        // Multi-head attention: 4 * d_model^2 (W_Q, W_K, W_V, W_O)
        $layer_params += 4 * $this->d_model * $this->d_model;
        // Feed-forward: (d_model * d_ff + d_ff) + (d_ff * d_model + d_model)
        $layer_params += ($this->d_model * $this->d_ff + $this->d_ff) + ($this->d_ff * $this->d_model + $this->d_model);
        // Layer norms: 2 * (gamma + beta) * d_model
        $layer_params += 2 * (2 * $this->d_model);

        $params += $layer_params * $this->num_layers;

        // Output projection
        $params += $this->d_model * $vocab_size;

        return number_format($params);
    }

    /**
     * Embed tokens and add positional encoding
     */
    private function embedTokens(array $token_ids): array
    {
        $embedded = [];
        $scale = sqrt($this->d_model); // scale token embeddings for training stability

        foreach ($token_ids as $pos => $token_id) {
            $emb_row = [];
            for ($j = 0; $j < $this->d_model; $j++) {
                $token_emb = $this->embedding[$token_id][$j] ?? 0.0;
                $pos_enc = $this->positional_encoding[$pos][$j] ?? 0.0;
                $emb_row[$j] = $token_emb * $scale + $pos_enc;
            }
            $embedded[] = $emb_row;
        }

        return $embedded;
    }

    public function generateWithProbability(string $input_text, int $max_new_tokens = 50, float $temperature = 1.0): array
    {
        $start_id = $this->tokenizer->getStartId();
        $end_id = $this->tokenizer->getEndId();

        $input_ids = $this->tokenizer->encode($input_text);
        $token_ids = array_merge([$start_id], $input_ids);

        $generated_tokens = [];
        $token_probabilities = [];

        for ($step = 0; $step < $max_new_tokens; $step++) {
            $embedded = $this->embedTokens($token_ids);
            $mask = MatrixOps::createCausalMask(count($token_ids));

            $x = $embedded;
            foreach ($this->encoder_layers as $layer) {
                $x = $layer->forward($x, $mask);
            }

            $x = $this->output_layer_norm->forward($x);
            $logits = MatrixOps::matmul($x, $this->output_projection);

            $last_logits = end($logits);
            $scaled_logits = MatrixOps::divide($last_logits, max($temperature, 0.01));

            // Softmax
            $max = MatrixOps::max($scaled_logits);
            $exp_logits = MatrixOps::substractExp($scaled_logits, $max);
            $sum_exp = MatrixOps::sum($exp_logits);

            if ($sum_exp == 0) {
                break;
            }

            $probs = array_map(fn($e) => $e / $sum_exp, $exp_logits);

            // Argmax
            $next_id = 0;
            $max_prob = $probs[0];
            for ($i = 1; $i < count($probs); $i++) {
                if ($probs[$i] > $max_prob) {
                    $max_prob = $probs[$i];
                    $next_id = $i;
                }
            }

            if ($next_id == $end_id) {
                break;
            }

            $token_ids[] = $next_id;
            $generated_tokens[] = $next_id;
            $token_probabilities[] = $max_prob;
        }

        $output_text = "";
        $tokens_with_prob = "";

        if (count($generated_tokens) > 0) {
            $output_text = $this->tokenizer->decode($generated_tokens);

            $decoded_tokens = [];
            foreach ($generated_tokens as $i => $token_id) {
                $char = $this->tokenizer->decode([$token_id]);
                $prob = $token_probabilities[$i] ?? 0;
                $decoded_tokens[] = sprintf("%s[%.0f%%]", $char, $prob * 100);
            }

            $tokens_with_prob = implode(" ", $decoded_tokens);
        }

        return [
            'text' => $output_text,
            'tokens_with_prob' => $tokens_with_prob,
            'avg_confidence' => count($token_probabilities) > 0 ? array_sum($token_probabilities) / count($token_probabilities) : 0,
            'min_confidence' => count($token_probabilities) > 0 ? min($token_probabilities) : 0,
        ];
    }

    private function showValidationResults(array $samples, int $epoch): void
    {
        echo "\n=== Epoch $epoch Predictions ===\n";

        foreach ($samples as $sample) {
            $input = $sample['input'];
            $expected = $sample['expected'];

            // Generate with probability tracking
            $result = $this->generateWithProbability($input, 30, 0.1);

            echo sprintf("Input:    %s\n", $input);
            echo sprintf("Expected: %s\n", $expected);
            echo sprintf("Got:      %s [avg %.1f%%]\n", $result['text'], $result['avg_confidence'] * 100);
            echo sprintf("Tokens:   %s\n\n", $result['tokens_with_prob']);
        }
        echo "================================\n\n";
    }

    /**
     * Training loop [OPTIMIZED]
     *
     * - Assumes MatrixOps provides high-performance vectorized operations.
     * - Replaced manual PHP loops for Adam, Softmax, and Loss with MatrixOps driver calls.
     * - Removed inefficient nested loop for occurrence counting.
     */
    public function trainOptimize(array $training_data, int $epochs = 100, float $learning_rate = 0.0005, int $batch_size = 8, array $validation_samples = []): void
    {
        // Adam hyperparams
        $beta1 = 0.9;
        $beta2 = 0.999;
        $epsilon = 1e-8;
        $this->batch_size = max(1, $batch_size);

        $start_id = $this->tokenizer->getStartId();
        $end_id = $this->tokenizer->getEndId();
        $vocab_size = $this->tokenizer->getVocabSize();
        $pad_id = $this->tokenizer->getPadId();

        $num_samples = count($training_data);
        $indices = range(0, $num_samples - 1);

        for ($epoch = 1; $epoch <= $epochs; $epoch++) {
            $epoch_start = microtime(true);
            $total_loss = 0.0;
            $num_examples = 0;
            shuffle($indices);

            for ($batch_start = 0; $batch_start < $num_samples; $batch_start += $this->batch_size) {
                $batch_idx = array_slice($indices, $batch_start, $this->batch_size);
                $batch_inputs = [];
                $batch_targets = [];
                $max_len = 0;
                foreach ($batch_idx as $idx) {
                    $item = $training_data[$idx];

                    if (isset($item['input'])) {
                        $input_ids = array_merge([$start_id], $this->tokenizer->encode($item['input']));
                        $target_ids = array_merge([$start_id], $this->tokenizer->encode($item['output']), [$end_id]);
                    } else {
                        $full_sequence = array_merge([$start_id], $this->tokenizer->encode($item['output']), [$end_id]);
                        $input_ids = array_slice($full_sequence, 0, -1);
                        $target_ids = array_slice($full_sequence, 1);
                    }

                    $batch_inputs[] = $input_ids;
                    $batch_targets[] = $target_ids;
                    $max_len = max($max_len, count($input_ids), count($target_ids));
                }

                // Padding
                for ($b = 0; $b < count($batch_inputs); $b++) {
                    $bi = $batch_inputs[$b];
                    while (count($bi) < $max_len) {
                        $bi[] = $pad_id;
                    }
                    $batch_inputs[$b] = $bi;

                    $bt = $batch_targets[$b];
                    while (count($bt) < $max_len) {
                        $bt[] = $pad_id;
                    }

                    $batch_targets[$b] = $bt;
                }

                $batch_count = count($batch_inputs);
                if ($batch_count === 0)
                    continue;

                $batch_x = [];
                for ($b = 0; $b < $batch_count; $b++) {
                    $embedded = $this->embedTokens($batch_inputs[$b]); // seq_len x d_model
                    $mask = MatrixOps::createCausalMask(count($embedded));
                    $x = $embedded;
                    foreach ($this->encoder_layers as $layer) {
                        $x = $layer->forward($x, $mask);
                    }
                    $x = $this->output_layer_norm->forward($x);
                    $batch_x[] = $x; // (seq_len x d_model)
                }

                // Logits, Loss, and Backward Aggregation
                $dW_out_sum = MatrixOps::zeros($this->d_model, $vocab_size);
                $dX_sum = MatrixOps::zeros($max_len, $this->d_model);

                for ($b = 0; $b < $batch_count; $b++) {
                    $seq = $batch_x[$b]; // (seq_len x d_model)
                    $targets = $batch_targets[$b]; // (seq_len)
                    $T = count($seq);
                    if ($T === 0) {
                        continue;
                    }

                    $logits = MatrixOps::matmul($seq, $this->output_projection); // (seq x vocab_size)
                    $log_probs = MatrixOps::logSoftmax($logits); // (seq x vocab_size)
                    $sample_loss = MatrixOps::crossEntropyLoss($log_probs, $targets, $pad_id);
                    $total_loss += $sample_loss;
                    $num_examples++;

                    // d_logits (p - y)
                    $probs = MatrixOps::exp($log_probs); // p
                    $y_one_hot = MatrixOps::buildOneHot($targets, $vocab_size); // y
                    $d_logits = MatrixOps::subtract($probs, $y_one_hot);
                    $d_logits = MatrixOps::maskPadding($d_logits, $targets, $pad_id, 0.0);

                    // ackward Pass Aggregation

                    // dW_out (output_projection)
                    $dW_out_b = MatrixOps::matmul(MatrixOps::transpose($seq), $d_logits); // (d_model x vocab)
                    $dW_out_sum = MatrixOps::add($dW_out_sum, $dW_out_b);

                    $d_x_from_proj = MatrixOps::matmul($d_logits, MatrixOps::transpose($this->output_projection)); // (seq x d_model)
                    $dX_sum = MatrixOps::add($dX_sum, $d_x_from_proj);
                }

                if ($num_examples == 0) {
                    continue;
                }

                // Adam Update for output_projection
                $this->adam_t += 1;
                $lr_t = $learning_rate * sqrt(1 - pow($beta2, $this->adam_t)) / (1 - pow($beta1, $this->adam_t));

                $dW_out_avg = MatrixOps::scale($dW_out_sum, 1.0 / $batch_count);
                $dW_out_avg = MatrixOps::clipGradient($dW_out_avg, 5.0);

                list($this->output_projection, $this->adam_m_out, $this->adam_v_out) = MatrixOps::adamUpdate(
                    $this->output_projection,
                    $dW_out_avg,
                    $this->adam_m_out,
                    $this->adam_v_out,
                    $this->adam_t,
                    $lr_t,
                    $beta1,
                    $beta2,
                    $epsilon
                );

                // Backpropagate through Encoders
                $d_x_avg = MatrixOps::scale($dX_sum, 1.0 / $batch_count);

                $d_x_after_ln = $this->output_layer_norm->backward($d_x_avg, $learning_rate);

                $d_x_enc = $d_x_after_ln;
                for ($l = $this->num_layers - 1; $l >= 0; $l--) {
                    $d_x_enc = $this->encoder_layers[$l]->backward($d_x_enc, $learning_rate);
                }

                // Adam Update for Embeddings ---
                $grad_embeddings = []; // (token_id => grad_vector_sum)
                $grad_counts = [];     // (token_id => count)

                for ($b = 0; $b < $batch_count; $b++) {
                    $seq_ids = $batch_inputs[$b];
                    for ($t = 0; $t < count($seq_ids); $t++) {
                        $token_id = $seq_ids[$t];
                        if ($token_id == $pad_id || $token_id < 0 || $token_id >= $vocab_size) {
                            continue;
                        }

                        $grad_row = MatrixOps::getRow($d_x_enc, $t); // array[d_model]

                        if (!isset($grad_embeddings[$token_id])) {
                            $grad_embeddings[$token_id] = $grad_row;
                            $grad_counts[$token_id] = 1;
                        } else {
                            $grad_embeddings[$token_id] = MatrixOps::addVector($grad_embeddings[$token_id], $grad_row);
                            $grad_counts[$token_id]++;
                        }
                    }
                }

                foreach ($grad_embeddings as $token_id => $g_row_sum) {
                    $g_row_avg = MatrixOps::scaleVector($g_row_sum, 1.0 / $grad_counts[$token_id]);
                    $g_row_avg = MatrixOps::clipVector($g_row_avg, 5.0);

                    list(
                        $this->embedding[$token_id],
                        $this->adam_m[$token_id],
                        $this->adam_v[$token_id]
                    ) = MatrixOps::adamUpdateRow(
                                $this->embedding[$token_id],
                                $g_row_avg,
                                $this->adam_m[$token_id],
                                $this->adam_v[$token_id],
                                $this->adam_t,
                                $lr_t,
                                $beta1,
                                $beta2,
                                $epsilon
                            );
                }
            } // end batches

            // Epoch Logging & Checkpointing
            $avg_loss = $total_loss / max(1, $num_examples);
            $epoch_time = microtime(true) - $epoch_start;

            if ($epoch % 10 == 0 || $epoch == 1) {
                error_log(sprintf(
                    "Epoch %3d/%d | Loss: %.6f | Time: %.2fs\n",
                    $epoch,
                    $epochs,
                    $avg_loss,
                    $epoch_time
                ));
            }

            if (!empty($validation_samples) && ($epoch % 10 == 0 || $epoch == 1)) {
                $this->showValidationResults($validation_samples, $epoch);
            }

            if ($epoch % 50 == 0) {
                $this->saveModel();
            }
        }

        $this->saveModel();
    }

    /**
     * Training loop
     */
    public function train(array $training_data, int $epochs = 100, float $learning_rate = 0.0005, int $batch_size = 8, array $validation_samples = []): void
    {
        // Adam hyperparams
        $beta1 = 0.9;
        $beta2 = 0.999;
        $epsilon = 1e-8;
        $this->batch_size = max(1, $batch_size);

        $start_id = $this->tokenizer->getStartId();
        $end_id = $this->tokenizer->getEndId();

        $num_samples = count($training_data);
        $indices = range(0, $num_samples - 1);

        $totalBatches = round($num_samples / $this->batch_size);
        $tjs = new TransformerJobSummary($epochs, $totalBatches);

        for ($epoch = 1; $epoch <= $epochs; $epoch++) {
            $epoch_start = microtime(true);
            $total_loss = 0.0;
            $num_examples = 0;

            // shuffle data indices each epoch for SGD-like behavior
            shuffle($indices);

            // process minibatches
            for ($batch_start = 0; $batch_start < $num_samples; $batch_start += $this->batch_size) {
                $tjs->startBatches();
                $tjs->setCurrentJob("Prepare batch sequences");

                $batch_idx = array_slice($indices, $batch_start, $this->batch_size);
                $batch_inputs = [];
                $batch_targets = [];

                // prepare batch sequences (pad to max length in batch)
                $max_len = 0;
                foreach ($batch_idx as $idx) {
                    $item = $training_data[$idx];

                    if (isset($item['input'])) {
                        $input_ids = array_merge([$start_id], $this->tokenizer->encode($item['input']));
                        $target_ids = array_merge([$start_id], $this->tokenizer->encode($item['output']), [$end_id]);
                    } else {
                        $full_sequence = array_merge([$start_id], $this->tokenizer->encode($item['output']), [$end_id]);
                        $input_ids = array_slice($full_sequence, 0, -1);
                        $target_ids = array_slice($full_sequence, 1);
                    }

                    $batch_inputs[] = $input_ids;
                    $batch_targets[] = $target_ids;
                    $max_len = max($max_len, count($input_ids), count($target_ids));
                }

                $tjs->setEndJob();
                $tjs->setCurrentJob("Pad sequences");

                // pad sequences to max_len with PAD id (use tokenizer PAD index)
                $pad_id = $this->tokenizer->getVocabSize() - 1; // conservative fallback if PAD isn't found; tokenizer ensures PAD exists earlier
                $batch_input_count = count($batch_inputs);
                for ($b = 0; $b < $batch_input_count; $b++) {
                    $bi = $batch_inputs[$b];
                    $bi = array_pad($bi, $max_len, $pad_id);
                    $batch_inputs[$b] = $bi;

                    $bt = $batch_targets[$b];
                    $bt = array_pad($bt, $max_len, $pad_id);
                    $batch_targets[$b] = $bt;
                }

                $tjs->setEndJob();
                $tjs->setCurrentJob("Forward");

                // 0.578903s
                // Forward for full batch: compute embeddings sequence-wise and stack by time
                // We'll represent batch as list of sequences (seq_len x d_model) per sample and then average gradients across batch.
                $batch_x = []; // aggregated per-sample final encoder outputs (seq_len x d_model)
                for ($b = 0; $b < $batch_input_count; $b++) {
                    $embedded = $this->embedTokens($batch_inputs[$b]); // seq_len x d_model
                    $mask = MatrixOps::createCausalMask(count($embedded));

                    $x = $embedded;
                    foreach ($this->encoder_layers as $layer) {
                        $x = $layer->forward($x, $mask);
                    }

                    $x = $this->output_layer_norm->forward($x);
                    $batch_x[] = $x; // seq_len x d_model
                }

                $tjs->setEndJob();
                $tjs->setCurrentJob("Compute logits");

                $batch_x_count = count($batch_x);

                // Compute logits and stable log-softmax per time-step for all samples, accumulate loss and d_logits
                $vocab_size = $this->tokenizer->getVocabSize();
                // initialize d_logits accumulator per sample
                $batch_d_logits = [];
                for ($b = 0; $b < $batch_x_count; $b++) {
                    $seq = $batch_x[$b]; // seq_len x d_model
                    $logits = MatrixOps::matmul($seq, $this->output_projection); // seq x vocab_size

                    // compute stable log-softmax per row using log-sum-exp
                    $log_probs = [];
                    $probs = [];
                    $T = count($seq);
                    $d_logits = MatrixOps::zeros($T, $vocab_size);
                    $sample_loss = 0.0;

                    for ($t = 0; $t < $T; $t++) {
                        $row = $logits[$t];
                        // find max for stability
                        $maxVal = max($row);
                        $sumExp = 0.0;
                        $exps = [];
                        for ($j = 0; $j < $vocab_size; $j++) {
                            $e = exp($row[$j] - $maxVal);
                            $exps[$j] = $e;
                            $sumExp += $e;
                        }

                        $logsumexp = $maxVal + log(max($sumExp, 1e-12));
                        for ($j = 0; $j < $vocab_size; $j++) {
                            $log_p = $row[$j] - $logsumexp;
                            $p = exp($log_p);
                            $log_probs[$t][$j] = $log_p;
                            $probs[$t][$j] = $p;
                        }
                    }

                    // compute cross-entropy loss per time-step and build d_logits
                    $batch_targets_b = $batch_targets[$b];
                    $T_eff = count($batch_targets_b); // use padded length
                    for ($t = 0; $t < $T_eff; $t++) {
                        $target_id = $batch_targets_b[$t] ?? null;
                        if ($target_id === null) {
                            continue;
                        }

                        $lp = $log_probs[$t][$target_id] ?? log(1e-12);
                        $sample_loss -= $lp;
                        // gradient for logits: p - y (will average over batch later)
                        for ($j = 0; $j < $vocab_size; $j++) {
                            $y = ($j === $target_id) ? 1.0 : 0.0;
                            $d_logits[$t][$j] = ($probs[$t][$j] - $y);
                        }
                    }

                    // average sample loss over sequence length
                    $sample_loss /= max(1, $T_eff);
                    $total_loss += $sample_loss;
                    $num_examples++;

                    $batch_d_logits[$b] = $d_logits; // seq x vocab
                }

                $tjs->setEndJob();
                $tjs->setCurrentJob("Backward");

                // 0.324688s
                // Backward: aggregate gradients across batch, propagate through output_projection and encoder/backwards
                // Compute averaged gradient for output_projection: dW_out = sum_over_samples (seq_mat^T @ d_logits) / batch_size
                $dW_out_sum = null;
                $dX_from_proj_batch = []; // per-sample d_x_from_proj after projecting back
                for ($b = 0; $b < $batch_x_count; $b++) {
                    $seq = $batch_x[$b]; // seq x d_model
                    $d_logits = $batch_d_logits[$b]; // seq x vocab
                    $dW_out_b = MatrixOps::matmul(MatrixOps::transpose($seq), $d_logits); // d_model x vocab

                    if ($dW_out_sum === null) {
                        $dW_out_sum = $dW_out_b;
                    } else {
                        $dW_out_sum = MatrixOps::add($dW_out_sum, $dW_out_b);
                    }

                    // backprop to x: d_x_from_proj = d_logits @ W_out^T
                    $d_x_from_proj = MatrixOps::matmul($d_logits, MatrixOps::transpose($this->output_projection)); // seq x d_model
                    $dX_from_proj_batch[$b] = $d_x_from_proj;
                }

                // average dW_out
                $dW_out_avg = MatrixOps::scale($dW_out_sum, 1.0 / $batch_x_count);
                // clip gradient globally for dW_out
                $dW_out_avg = MatrixOps::clipGradient($dW_out_avg, 5.0);

                // Update output_projection via Adam
                $this->adam_t += 1;
                $lr_t = $learning_rate * sqrt(1 - pow($beta2, $this->adam_t)) / (1 - pow($beta1, $this->adam_t));

                // update per-element m,v and parameter W_out: shape d_model x vocab
                $dW_out_avg_count = count($dW_out_avg);
                for ($i = 0; $i < $dW_out_avg_count; $i++) {
                    $dW_out_avg_i = $dW_out_avg[$i];
                    $dW_out_avg_item_count = count($dW_out_avg_i);

                    for ($j = 0; $j < $dW_out_avg_item_count; $j++) {
                        $g = $dW_out_avg_i[$j];
                        $this->adam_m_out[$i][$j] = $beta1 * $this->adam_m_out[$i][$j] + (1 - $beta1) * $g;
                        $this->adam_v_out[$i][$j] = $beta2 * $this->adam_v_out[$i][$j] + (1 - $beta2) * ($g * $g);
                        $m_hat = $this->adam_m_out[$i][$j] / (1 - pow($beta1, $this->adam_t));
                        $v_hat = $this->adam_v_out[$i][$j] / (1 - pow($beta2, $this->adam_t));
                        $this->output_projection[$i][$j] -= $lr_t * $m_hat / (sqrt($v_hat) + $epsilon);
                    }
                }

                $tjs->setEndJob();
                $tjs->setCurrentJob("Backprop");

                // Now backprop through layernorm and encoder layers per sample using d_x_from_proj_batch averaged by batch
                // We'll average d_x across batch per time-step to feed upper layers in a stable manner
                // For each sample: d_x is seq x d_model. We'll average these per time-step across batch and feed encoder backward.
                $T_seq = $max_len;
                // compute avg d_x per time-step across batch
                $d_x_avg_per_time = [];
                for ($t = 0; $t < $T_seq; $t++) {
                    $count_valid = 0;
                    $d_row_sum = array_fill(0, $this->d_model, 0.0);
                    $dX_from_proj_batch_count = count($dX_from_proj_batch);

                    for ($b = 0; $b < $dX_from_proj_batch_count; $b++) {
                        $d_row = $dX_from_proj_batch[$b][$t] ?? null;
                        if ($d_row === null) {
                            continue;
                        }

                        $d_row_sum = MatrixOps::add([$d_row_sum], [$d_row])[0];
                        $count_valid++;
                    }

                    if ($count_valid === 0) {
                        $d_x_avg_per_time[$t] = $d_row_sum;
                    } else {
                        $d_x_avg_per_time[$t] = MatrixOps::scale([$d_row_sum], 1.0 / $count_valid)[0];
                    }
                }

                // Backprop aggregate through output_layer_norm and encoder layers
                $d_x_after_ln = $this->output_layer_norm->backward($d_x_avg_per_time, $learning_rate);

                // Propagate backward through encoder layers in reverse
                $d_x_enc = $d_x_after_ln;
                for ($l = $this->num_layers - 1; $l >= 0; $l--) {
                    $d_x_enc = $this->encoder_layers[$l]->backward($d_x_enc, $learning_rate);
                }

                $tjs->setEndJob();
                $tjs->setCurrentJob("Update embedding");

                // Update embeddings with Adam using averaged d_x_enc per time-step: treat embeddings per token id
                // Compute gradients for embeddings by summing gradients for positions where token appears in batch
                $grad_embeddings = []; // vocab_size x d_model sparse accumulation
                for ($b = 0; $b < $batch_input_count; $b++) {
                    $seq_ids = $batch_inputs[$b];
                    $seq_ids_count = count($seq_ids);

                    for ($t = 0; $t < $seq_ids_count; $t++) {
                        $token_id = $seq_ids[$t];
                        if ($token_id < 0 || $token_id >= $this->tokenizer->getVocabSize()) {
                            continue;
                        }

                        $grad_row = $d_x_enc[$t] ?? array_fill(0, $this->d_model, 0.0);
                        if (!isset($grad_embeddings[$token_id])) {
                            $grad_embeddings[$token_id] = $grad_row;
                        } else {
                            $grad_embeddings[$token_id] = MatrixOps::add([$grad_embeddings[$token_id]], [$grad_row])[0];
                        }
                    }
                }

                $tjs->setEndJob();
                $tjs->setCurrentJob("Adam update");

                // Apply Adam update to embedding rows that have grads
                foreach ($grad_embeddings as $token_id => $g_row) {
                    // average gradient across how many occurrences
                    // count occurrences
                    $occ = 0;
                    foreach ($batch_inputs as $seq_ids) {
                        foreach ($seq_ids as $tid) {
                            if ($tid === $token_id) {
                                $occ++;
                            }
                        }
                    }

                    $scale_occ = max(1, $occ);
                    for ($j = 0; $j < $this->d_model; $j++) {
                        $g = ($g_row[$j] ?? 0.0) / $scale_occ;
                        // clip scalar gradient
                        $g = max(min($g, 5.0), -5.0);
                        $this->adam_m[$token_id][$j] = $beta1 * $this->adam_m[$token_id][$j] + (1 - $beta1) * $g;
                        $this->adam_v[$token_id][$j] = $beta2 * $this->adam_v[$token_id][$j] + (1 - $beta2) * ($g * $g);
                        $m_hat = $this->adam_m[$token_id][$j] / (1 - pow($beta1, $this->adam_t));
                        $v_hat = $this->adam_v[$token_id][$j] / (1 - pow($beta2, $this->adam_t));
                        $this->embedding[$token_id][$j] -= $lr_t * $m_hat / (sqrt($v_hat) + $epsilon);
                    }
                }

                $tjs->setEndJob();
                $tjs->nextBatches();
                $tjs->nextTick();

                // end minibatch
            } // end batches

            $avg_loss = $total_loss / max(1, $num_examples);
            $epoch_time = microtime(true) - $epoch_start;

            $tjs->setAvgLoss($avg_loss);
            $tjs->setEpoch($epoch);
            $tjs->setEpochs($epochs);
            $tjs->setEpochTime($epoch_time);

            if (!empty($validation_samples)) {
                $this->showValidationResults($validation_samples, $epoch);
            }

            // atomic checkpoint save every 50 epochs
            if ($epoch % 50 == 0) {
                $this->saveModel();
            }

            $tjs->nextTick(true);
            $tjs->clearBatches();
        }

        // final save
        $this->saveModel();
    }

    /**
     * Text generation
     */
    public function generate(string $input_text, int $max_new_tokens = 50, float $temperature = 1.0): string
    {
        $start_id = $this->tokenizer->getStartId();
        $end_id = $this->tokenizer->getEndId();

        // Store input length
        $input_ids = $this->tokenizer->encode($input_text);
        $input_len = count($input_ids);

        $token_ids = array_merge([$start_id], $input_ids);

        // Generation loop
        $generated_tokens = [];
        for ($step = 0; $step < $max_new_tokens; $step++) {
            $embedded = $this->embedTokens($token_ids);
            $mask = MatrixOps::createCausalMask(count($token_ids));

            $x = $embedded;
            foreach ($this->encoder_layers as $layer) {
                $x = $layer->forward($x, $mask);
            }

            $x = $this->output_layer_norm->forward($x);
            $logits = MatrixOps::matmul($x, $this->output_projection);

            // Get logits for the last token
            $last_logits = end($logits);

            // Apply temperature
            $scaled_logits = array_map(fn($l) => $l / max($temperature, 0.01), $last_logits);

            // Softmax (numerically stable)
            $max = max($scaled_logits);
            $exp_logits = array_map(fn($l) => exp(min($l - $max, 20)), $scaled_logits);
            $sum_exp = array_sum($exp_logits);

            if ($sum_exp == 0) {
                error_log("Generation failed (sum_exp is zero).");
                break;
            }

            $probs = array_map(fn($e) => $e / $sum_exp, $exp_logits);

            // temperature > 1.0 random, lower than greedy
            if ($temperature >= 1.0) {
                // Random sampling from distribution
                $rand = mt_rand() / mt_getrandmax();
                $cumulative = 0.0;
                $next_id = 0;

                foreach ($probs as $idx => $prob) {
                    $cumulative += $prob;
                    if ($rand <= $cumulative) {
                        $next_id = $idx;
                        break;
                    }
                }
            } else {
                // Argmax (greedy)
                $next_id = 0;
                $max_prob = $probs[0];
                for ($i = 1; $i < count($probs); $i++) {
                    if ($probs[$i] > $max_prob) {
                        $max_prob = $probs[$i];
                        $next_id = $i;
                    }
                }
            }

            if ($step < 3) {
                $char = $this->tokenizer->decode([$next_id]);
            }

            if ($next_id == $end_id) {
                break;
            }

            $token_ids[] = $next_id;
            $generated_tokens[] = $next_id;
        }

        if (count($generated_tokens) > 0) {
            if (method_exists($this->tokenizer, 'decodeToText')) {
                $output_text = $this->tokenizer->decodeToText($generated_tokens);
            } else {
                $output_text = $this->tokenizer->decode($generated_tokens);
            }
        } else {
            $output_text = "";
        }

        return $output_text;
    }

    private function sampleFromDistribution(array $probs): int
    {
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0.0;

        foreach ($probs as $idx => $prob) {
            $cumulative += $prob;
            if ($rand <= $cumulative) {
                return $idx;
            }
        }

        return count($probs) - 1; // fallback
    }

    /**
     * Save model weights
     */
    private function saveModel(): void
    {
        $layer_weights = [];
        foreach ($this->encoder_layers as $layer) {
            $layer_weights[] = $layer->getWeights();
        }

        $data = [
            'embedding' => $this->embedding,
            'positional_encoding' => $this->positional_encoding,
            'encoder_layers' => $layer_weights,
            'output_projection' => $this->output_projection,
            'output_layer_norm' => $this->output_layer_norm->getWeights(),
            'config' => [
                'd_model' => $this->d_model,
                'num_layers' => $this->num_layers,
                'num_heads' => $this->num_heads,
                'd_ff' => $this->d_ff,
                'max_seq_len' => $this->max_seq_len
            ]
        ];

        file_put_contents($this->modelFile, json_encode($data));
    }

    /**
     * Load model weights
     */
    private function loadModel(): void
    {
        if (!file_exists($this->modelFile)) {
            return;
        }

        $data = json_decode(file_get_contents($this->modelFile), true);

        if (!$data) {
            throw new Exception("Model loading failed (invalid JSON).");
        }

        try {
            $this->embedding = $data['embedding'];
            $this->positional_encoding = $data['positional_encoding'];
            $this->output_projection = $data['output_projection'];
            $this->output_layer_norm->setWeights($data['output_layer_norm']);

            for ($i = 0; $i < $this->num_layers; $i++) {
                if (isset($data['encoder_layers'][$i])) {
                    $this->encoder_layers[$i]->setWeights($data['encoder_layers'][$i]);
                }
            }
        } catch (Exception $e) {
            throw new Exception("Model loading failed (structure mismatch): " . $e->getMessage());
        }
    }

}