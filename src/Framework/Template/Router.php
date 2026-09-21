<!-- License - Onetone Framework (AGPL-3.0) template -->
<div id="route">
    <?php foreach ($map as $data): ?>
        <div>
            <div class="header">
                <div class="method">
                    <?= $data['method']; ?>
                </div>
                <div class="pattern">
                    <?= $data['pattern']; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .header {
        display: flex;
        padding: 5px;
        background-color: #f3fded;
        border: 1px solid #33cda2;
    }

    #route div:not(:first-child) .header {
        margin-top: 10px;
    }

    .pattern {
        display: flex;
        font-weight: bold;
        color: #2e2e2e;
    }

    .method {
        width: 120px;
        background-color: #1fa882;
        padding: 4px;
        text-align: center;
        color: #fff;
        font-weight: bold;
        margin-right: 10px;
    }
</style>