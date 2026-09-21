<?php

declare(strict_types=1);

?>
<main
	class="voice-studio"
	data-audio-analysis-page
	data-analysis-endpoint="<?= htmlspecialchars($analysisEndpoint, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
	data-comparison-endpoint="<?= htmlspecialchars($comparisonEndpoint, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
	data-maximum-upload-bytes="<?= htmlspecialchars((string) $maximumUploadBytes, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
	data-audio-worklet-url="<?= htmlspecialchars($voiceTransformerWorkletPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
>
	<header class="studio-app-bar">
		<div class="studio-app-bar__identity">
			<a class="studio-brand" href="/" aria-label="Onetone home">
				<span class="studio-brand__mark" aria-hidden="true">O</span>
				<span>Onetone</span>
			</a>
			<span class="studio-app-bar__divider" aria-hidden="true"></span>
			<div>
				<small>Acoustic intelligence</small>
				<strong>Voice Analysis Console</strong>
			</div>
		</div>
		<div class="studio-app-bar__status" aria-label="Analysis capabilities">
			<span>Request-only processing</span>
			<code>API v1</code>
		</div>
	</header>

	<section class="studio-hero" aria-labelledby="studio-title">
		<div class="studio-hero__copy">
			<p class="studio-eyebrow">Professional acoustic measurement</p>
			<h1 id="studio-title">Voice Acoustic <span>Workbench</span></h1>
			<p class="studio-hero__lead">
				Inspect signal level, pitch, periodicity, resonance, vocal-tract estimates, and spectral balance
				from one consistent analysis pipeline. Compare sessions without retaining uploaded recordings.
			</p>
			<div class="studio-hero__facts" aria-label="Analysis limits">
				<span><small>Measurement set</small><strong>50 acoustic metrics</strong></span>
				<span><small>Recording limit</small><strong>1 hour · 1 GiB</strong></span>
				<span><small>Output contract</small><strong>Typed · finite · traceable</strong></span>
			</div>
		</div>

		<aside class="studio-signal" aria-label="Analysis domains">
			<div class="studio-signal__header">
				<div>
					<span>Analysis pipeline</span>
					<strong>Five acoustic domains</strong>
				</div>
				<span class="studio-signal__state">Measurement set</span>
			</div>
			<div class="studio-signal__core" aria-hidden="true">
				<span style="--signal-height: 18%"></span>
				<span style="--signal-height: 36%"></span>
				<span style="--signal-height: 62%"></span>
				<span style="--signal-height: 90%"></span>
				<span style="--signal-height: 54%"></span>
				<span style="--signal-height: 78%"></span>
				<span style="--signal-height: 42%"></span>
				<span style="--signal-height: 96%"></span>
				<span style="--signal-height: 68%"></span>
				<span style="--signal-height: 32%"></span>
				<span style="--signal-height: 58%"></span>
				<span style="--signal-height: 24%"></span>
			</div>
			<div class="studio-signal__domains">
				<div><span>01</span><strong>Signal</strong><small>Level and dynamics</small></div>
				<div><span>02</span><strong>Pitch</strong><small>F0 and voicing</small></div>
				<div><span>03</span><strong>Quality</strong><small>HNR, jitter, shimmer</small></div>
				<div><span>04</span><strong>Resonance</strong><small>Formants and VTL</small></div>
				<div><span>05</span><strong>Spectrum</strong><small>Tilt and balance</small></div>
			</div>
			<p class="studio-signal__caption"><span aria-hidden="true">i</span> Acoustic measurement, not diagnosis</p>
		</aside>
	</section>

	<section class="studio-workspace" aria-labelledby="workspace-title">
		<div class="studio-workspace__header">
			<div>
				<p class="studio-kicker">Session control</p>
				<h2 id="workspace-title">Configure analysis</h2>
				<p class="studio-workspace__description">Select a workflow, confirm the recording specification, and start the analysis.</p>
			</div>
			<div class="studio-mode-switch" role="tablist" aria-label="Analysis mode">
				<button id="analysis-mode-tab" type="button" role="tab" class="is-active" data-mode-button="analysis" aria-selected="true" aria-controls="analysis-mode-panel" tabindex="0">
					<span>01</span> Single recording
				</button>
				<button id="comparison-mode-tab" type="button" role="tab" data-mode-button="comparison" aria-selected="false" aria-controls="comparison-mode-panel" tabindex="-1">
					<span>02</span> Compare sessions
				</button>
			</div>
		</div>

		<div class="studio-panel" id="analysis-mode-panel" role="tabpanel" aria-labelledby="analysis-mode-tab" data-mode-panel="analysis">
			<form id="analysis-form" enctype="multipart/form-data" novalidate>
				<div class="studio-session-layout">
					<aside class="studio-session-guide">
						<div class="studio-step-heading">
							<span>01</span>
							<div>
								<h3>Single-session analysis</h3>
								<p>Generate a complete acoustic profile for one recording.</p>
							</div>
						</div>
						<dl class="studio-spec-list">
							<div><dt>Container</dt><dd>WAV</dd></div>
							<div><dt>Sample rate</dt><dd>16–192 kHz</dd></div>
							<div><dt>Duration</dt><dd>0.25 s–1 h</dd></div>
							<div><dt>Maximum size</dt><dd>1 GiB</dd></div>
						</dl>
						<p class="studio-session-note"><span aria-hidden="true">◎</span> Record 15–30 cm from the microphone in a quiet room.</p>
					</aside>

					<div class="studio-session-stage">
						<div class="studio-upload-field" data-upload-field>
							<label class="studio-dropzone" for="analysis-audio" data-upload-zone>
								<input class="studio-visually-hidden" id="analysis-audio" name="audio" type="file" accept=".wav,audio/wav,audio/x-wav" required>
								<span class="studio-file-icon" aria-hidden="true">WAV</span>
								<span class="studio-dropzone__title">Drop a recording to begin</span>
								<span class="studio-dropzone__copy">PCM or floating-point WAV · up to 1 GiB</span>
								<span class="studio-dropzone__action">Browse recording</span>
							</label>
							<p class="studio-field-error" data-field-error role="alert" hidden></p>
							<div class="studio-file-summary" data-file-summary hidden>
								<div class="studio-file-summary__main">
									<span class="studio-file-summary__status" aria-hidden="true">✓</span>
									<div><strong data-file-name></strong><span data-file-size></span></div>
								</div>
								<div class="studio-audio-player-mount" data-audio-player data-player-label="Analysis recording">
									<audio preload="metadata" data-audio-preview hidden></audio>
								</div>
							</div>
						</div>
						<div class="studio-submit-row">
							<p><span aria-hidden="true">●</span> Files are processed for this request and are not retained.</p>
							<button class="studio-primary-button" type="submit" data-submit-button>
								<span class="studio-button-spinner" data-button-spinner hidden aria-hidden="true"></span>
								<span data-button-label>Run acoustic analysis</span>
								<span class="studio-button-arrow" aria-hidden="true">→</span>
							</button>
						</div>
					</div>
				</div>
			</form>
		</div>

		<div class="studio-panel" id="comparison-mode-panel" role="tabpanel" aria-labelledby="comparison-mode-tab" data-mode-panel="comparison" hidden>
			<form id="comparison-form" enctype="multipart/form-data" novalidate>
				<div class="studio-session-layout">
					<aside class="studio-session-guide">
						<div class="studio-step-heading">
							<span>A/B</span>
							<div>
								<h3>Session comparison</h3>
								<p>Calculate absolute and valid relative changes between two recordings.</p>
							</div>
						</div>
						<dl class="studio-spec-list">
							<div><dt>Baseline</dt><dd>Earlier reference</dd></div>
							<div><dt>Current</dt><dd>Session to evaluate</dd></div>
							<div><dt>Controls</dt><dd>Same room and gain</dd></div>
							<div><dt>Output</dt><dd>Absolute + relative</dd></div>
						</dl>
						<p class="studio-session-note"><span aria-hidden="true">◎</span> Keep the prompt, microphone, distance, angle, and input gain consistent.</p>
					</aside>

					<div class="studio-session-stage">
						<div class="studio-comparison-grid">
							<div class="studio-upload-field" data-upload-field>
								<p class="studio-upload-label"><span>A</span> Baseline session</p>
								<label class="studio-dropzone studio-dropzone--compact" for="baseline-audio" data-upload-zone>
									<input class="studio-visually-hidden" id="baseline-audio" name="baseline_audio" type="file" accept=".wav,audio/wav,audio/x-wav" required>
									<span class="studio-file-icon" aria-hidden="true">WAV</span>
									<span class="studio-dropzone__title">Choose baseline</span>
									<span class="studio-dropzone__copy">Earlier reference recording</span>
								</label>
								<p class="studio-field-error" data-field-error role="alert" hidden></p>
								<div class="studio-file-summary studio-file-summary--compact" data-file-summary hidden>
									<div class="studio-file-summary__main">
										<span class="studio-file-summary__status" aria-hidden="true">✓</span>
										<div><strong data-file-name></strong><span data-file-size></span></div>
									</div>
									<div class="studio-audio-player-mount" data-audio-player data-player-label="Baseline recording">
										<audio preload="metadata" data-audio-preview hidden></audio>
									</div>
								</div>
							</div>

							<div class="studio-upload-field" data-upload-field>
								<p class="studio-upload-label"><span>B</span> Current session</p>
								<label class="studio-dropzone studio-dropzone--compact" for="current-audio" data-upload-zone>
									<input class="studio-visually-hidden" id="current-audio" name="current_audio" type="file" accept=".wav,audio/wav,audio/x-wav" required>
									<span class="studio-file-icon" aria-hidden="true">WAV</span>
									<span class="studio-dropzone__title">Choose current</span>
									<span class="studio-dropzone__copy">Recording to evaluate</span>
								</label>
								<p class="studio-field-error" data-field-error role="alert" hidden></p>
								<div class="studio-file-summary studio-file-summary--compact" data-file-summary hidden>
									<div class="studio-file-summary__main">
										<span class="studio-file-summary__status" aria-hidden="true">✓</span>
										<div><strong data-file-name></strong><span data-file-size></span></div>
									</div>
									<div class="studio-audio-player-mount" data-audio-player data-player-label="Current recording">
										<audio preload="metadata" data-audio-preview hidden></audio>
									</div>
								</div>
							</div>
						</div>
						<div class="studio-submit-row">
							<p><span aria-hidden="true">●</span> Relative changes are shown only for mathematically valid measurements.</p>
							<button class="studio-primary-button" type="submit" data-submit-button>
								<span class="studio-button-spinner" data-button-spinner hidden aria-hidden="true"></span>
								<span data-button-label>Compare recordings</span>
								<span class="studio-button-arrow" aria-hidden="true">→</span>
							</button>
						</div>
					</div>
				</div>
			</form>
		</div>

		<div class="studio-status" data-request-status data-tone="idle" role="status" aria-live="polite">
			<span class="studio-status__indicator" aria-hidden="true"></span>
			<div><small>Session status</small><span data-status-message>Ready when you are.</span></div>
		</div>
	</section>

	<section class="studio-results" data-analysis-results hidden aria-labelledby="analysis-results-title" tabindex="-1">
		<div class="studio-results__heading">
			<div>
				<span class="studio-complete-badge"><i aria-hidden="true"></i> Analysis complete</span>
				<p class="studio-kicker">Session measurements</p>
				<h2 id="analysis-results-title">Acoustic analysis report</h2>
				<p>Review the recording context, high-signal indicators, detailed tracks, and complete measurement registry.</p>
			</div>
			<div class="studio-result-identity"><span>Analysis ID</span><code class="studio-result-id" data-analysis-identifier></code></div>
		</div>
		<nav class="studio-result-navigation" aria-label="Analysis report sections">
			<a href="#analysis-overview"><span>01</span> Overview</a>
			<a href="#analysis-profile"><span>02</span> Acoustic profile</a>
			<a href="#analysis-explorer"><span>03</span> Signal explorer</a>
			<a href="#analysis-registry"><span>04</span> Measurement registry</a>
		</nav>

		<section class="studio-result-block" id="analysis-overview" aria-labelledby="analysis-overview-title">
			<div class="studio-block-heading">
				<div><p class="studio-kicker">Recording context</p><h3 id="analysis-overview-title">Input specification</h3></div>
				<p>Source properties retained in the report contract.</p>
			</div>
			<div class="studio-metadata" data-analysis-metadata></div>
		</section>

		<section class="studio-priority-panel" id="analysis-profile" aria-labelledby="priority-measurements-title">
			<div class="studio-section-heading">
				<div>
					<p class="studio-kicker">Diagnostic overview</p>
					<h3 id="priority-measurements-title">Acoustic profile</h3>
				</div>
				<p>dBFS is relative to digital full scale. Intensity dB is an uncalibrated analysis value and is not sound-pressure level.</p>
			</div>
			<div class="studio-key-measurements" data-key-measurements></div>
		</section>

		<section class="studio-result-block" id="analysis-explorer" aria-labelledby="analysis-explorer-title">
			<div class="studio-section-heading studio-section-heading--charts">
				<div>
					<p class="studio-kicker">Interactive signal explorer</p>
					<h3 id="analysis-explorer-title">Time and frequency detail</h3>
				</div>
				<p>Use the keyboard-accessible controls or wheel and pinch gestures to zoom. Hover for exact values, drag to pan, and export any view.</p>
			</div>
			<div class="studio-chart-coverage" data-analysis-chart-coverage></div>
			<div class="studio-analysis-charts" data-analysis-charts></div>
		</section>

		<details class="studio-measurement-details" id="analysis-registry" open>
			<summary>
				<span><small>Reference data</small>Complete measurement registry</span>
				<small>All acoustic outputs grouped by measurement domain</small>
			</summary>
			<div class="studio-registry-toolbar">
				<label class="studio-registry-search">
					<span>Find a measurement</span>
					<input type="search" placeholder="Search by label, key, or unit" autocomplete="off" data-measurement-search>
				</label>
				<div class="studio-registry-filters" role="group" aria-label="Measurement category">
					<button type="button" class="is-active" data-measurement-filter="all" aria-pressed="true">All domains</button>
					<button type="button" data-measurement-filter="signal" aria-pressed="false">Signal</button>
					<button type="button" data-measurement-filter="pitch" aria-pressed="false">Pitch</button>
					<button type="button" data-measurement-filter="quality" aria-pressed="false">Voice quality</button>
					<button type="button" data-measurement-filter="resonance" aria-pressed="false">Resonance</button>
					<button type="button" data-measurement-filter="spectrum" aria-pressed="false">Spectrum</button>
				</div>
				<p class="studio-registry-status" data-measurement-registry-status aria-live="polite"></p>
			</div>
			<div class="studio-measurement-groups" data-measurement-groups></div>
		</details>
	</section>

	<section class="studio-results" data-comparison-results hidden aria-labelledby="comparison-results-title" tabindex="-1">
		<div class="studio-results__heading">
			<div>
				<span class="studio-complete-badge"><i aria-hidden="true"></i> Comparison complete</span>
				<p class="studio-kicker">Change tracking</p>
				<h2 id="comparison-results-title">Baseline to current report</h2>
				<p>Inspect both signal sets before interpreting measurement changes.</p>
			</div>
			<div class="studio-result-identity"><span>Comparison ID</span><code class="studio-result-id" data-comparison-identifier></code></div>
		</div>
		<nav class="studio-result-navigation" aria-label="Comparison report sections">
			<a href="#comparison-sessions"><span>01</span> Sessions</a>
			<a href="#comparison-explorer"><span>02</span> Signal explorer</a>
			<a href="#comparison-changes"><span>03</span> Change table</a>
		</nav>
		<section class="studio-result-block" id="comparison-sessions" aria-labelledby="comparison-sessions-title">
			<div class="studio-block-heading">
				<div><p class="studio-kicker">Recording pair</p><h3 id="comparison-sessions-title">Session context</h3></div>
				<p>Confirm that both sources represent a comparable recording protocol.</p>
			</div>
			<div class="studio-recording-pair">
				<div><span>Baseline</span><strong data-baseline-name></strong><small data-baseline-metadata></small></div>
				<span class="studio-recording-pair__arrow" aria-hidden="true">→</span>
				<div><span>Current</span><strong data-current-name></strong><small data-current-metadata></small></div>
			</div>
		</section>
		<section class="studio-result-block" id="comparison-explorer" aria-labelledby="comparison-explorer-title">
			<div class="studio-block-heading">
				<div><p class="studio-kicker">Interactive comparison</p><h3 id="comparison-explorer-title">Signal explorer</h3></div>
				<p>Use the same chart domain and zoom window when comparing visual patterns.</p>
			</div>
			<div class="studio-comparison-charts" data-comparison-charts></div>
		</section>
		<section class="studio-result-block" id="comparison-changes" aria-labelledby="comparison-changes-title">
			<div class="studio-block-heading">
				<div><p class="studio-kicker">Measurement deltas</p><h3 id="comparison-changes-title">Complete change table</h3></div>
				<p>Absolute changes are always unit-aware. Relative change is omitted when the comparison would be misleading.</p>
			</div>
			<div class="studio-table-shell" tabindex="0" aria-label="Complete acoustic measurement change table">
				<table class="studio-comparison-table" role="table">
					<caption class="studio-visually-hidden">Baseline and current acoustic measurement changes</caption>
					<thead role="rowgroup">
						<tr role="row">
							<th id="comparison-measurement" scope="col" role="columnheader">Measurement</th>
							<th id="comparison-baseline" scope="col" role="columnheader">Baseline</th>
							<th id="comparison-current" scope="col" role="columnheader">Current</th>
							<th id="comparison-difference" scope="col" role="columnheader">Difference</th>
							<th id="comparison-relative" scope="col" role="columnheader">Relative</th>
						</tr>
					</thead>
					<tbody role="rowgroup" data-comparison-body></tbody>
				</table>
			</div>
		</section>
	</section>

	<section class="studio-protocol" aria-labelledby="protocol-title">
		<div>
			<p class="studio-kicker">Measurement protocol</p>
			<h2 id="protocol-title">Control the variables before comparing the numbers</h2>
			<p>A professional comparison is only as reliable as the recording protocol. Setup variation can exceed performance variation.</p>
		</div>
		<ol>
			<li><span>01</span><strong>Repeat the prompt</strong><small>Use the same words, pace target, vocal task, and warm-up point.</small></li>
			<li><span>02</span><strong>Lock the signal chain</strong><small>Keep room, microphone, distance, angle, device, and input gain fixed.</small></li>
			<li><span>03</span><strong>Match the intention</strong><small>Compare like with like: projection, pitch target, register, and delivery style.</small></li>
		</ol>
	</section>

	<footer class="studio-disclaimer">
		<span aria-hidden="true">i</span>
		<p><strong>Measurement, not diagnosis.</strong> Results support acoustic inspection, coaching, and practice tracking. They do not identify disease, anatomy, gender, or treatment outcomes.</p>
	</footer>
</main>
