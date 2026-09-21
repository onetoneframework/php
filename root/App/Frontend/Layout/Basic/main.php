<link rel="stylesheet" href="layout.css" />
<script src="layout.js"></script>

<div class="wrapper">
    <header>
        <div class="container">
            <div class="logo">{$logo}</div>

            <nav>
                <?php foreach ($menus as $menu): ?>
                    <li class="dropdown current_menu">
                        <a href="<?= $menu['link'] ?>" class="first_a">
                            <?= $menu['title'] ?>
                        </a>
                        <?php if (!empty($menu['children'])): ?>
                            <ul class="dropdown-menu">
                                <?php foreach ($menu['children'] as $child): ?>
                                    <li>
                                        <a href="<?= $child['link'] ?>"><?= $child['title'] ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <div id="contents">
        {$content}
    </div>

    <footer>
        <div class="container">
        </div>
    </footer>
</div>