<!-- License - Onetone Framework (AGPL-3.0) template -->
<div class="<?= $fold ? "wrapper fold" : "wrapper" ?>">
    <header>
        <div class="container">
            <div class="logo">
                <?php if (!empty($logo)): ?>
                    <a href="/" aria-label="Home">
                        <img src="<?= $logo; ?>" alt="Logo"/>
                    </a>
                <?php endif;?>
            </div>
             <nav id="nav-desktop" class="nav nav--desktop" aria-hidden="true">
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

            <button id="mobile-open" class="nav-toggle" aria-controls="nav-mobile" aria-expanded="false" aria-label="Open menu">
                <span class="nav-toggle__icon" aria-hidden="true"></span>
            </button>
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

<nav id="nav-mobile" class="nav nav--mobile" aria-hidden="true" aria-label="Mobile">
  <div class="nav-mobile__inner">
    <button id="mobile-close" class="nav-close" aria-label="Close menu">&times;</button>
    <ul>
      <?php foreach ($menus as $menu): ?>
        <li class="<?= !empty($menu['children']) ? 'has-children' : '' ?>">
          <div class="mobile-item">
            <a href="<?= $menu['link'] ?>"><?= $menu['title'] ?></a>
            <?php if (!empty($menu['children'])): ?>
              <button class="submenu-toggle" aria-expanded="false" aria-label="Toggle submenu"></button>
            <?php endif; ?>
          </div>
          <?php if (!empty($menu['children'])): ?>
            <ul class="sub" aria-hidden="true">
              <?php foreach ($menu['children'] as $c): ?>
                <li><a href="<?= $c['link'] ?>"><?= $c['title'] ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>

<div id="nav-overlay" class="nav-overlay" aria-hidden="true"></div>