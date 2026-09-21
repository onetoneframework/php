<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Livewire Demo · Onetone</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #0f172a; }
        h1 { margin-bottom: .25rem; }
        p.lead { color: #475569; margin-top: 0; }
        code { background: #f1f5f9; padding: .1rem .3rem; border-radius: .25rem; }
        hr { border: none; border-top: 1px solid #e2e8f0; margin: 1.5rem 0; }
    </style>
</head>
<body>
    <h1>Onetone Livewire Demo</h1>
    <p class="lead">Two independent <code>Counter</code> components mounted on the same page.</p>

    <?= livewire('counter', ['start' => 0, 'step' => 1]) ?>
    <hr>
    <?= livewire('counter', ['start' => 10, 'step' => 5]) ?>

    <?= livewire_scripts() ?>
</body>
</html>
