<!-- License - Onetone Framework (AGPL-3.0) template -->
<div class="wrapper">
    <header>
        <div class="container">
            <div class="logo">
                <?php if (!empty($logo)): ?>
                    <img src="<?= $logo; ?>"/>
                <?php endif;?>
            </div>

            <nav>
                <ul class="nav navbar-nav navbar-left">
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
                </ul>
            </nav>
        </div>
    </header>

    <div id="contents">
        <?= $content; ?>
    </div>

    <?php if(isset($footer)):?>
    <footer>
        <div class="container">
            <?= $footer; ?>
        </div>
    </footer>
    <?php endif;?>
</div>