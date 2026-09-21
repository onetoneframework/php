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
        <ul>
          <?php foreach ($menus as $menu): ?>
            <li class="dropdown">
              <a href="<?= $menu['link']; ?>"><?= $menu['title']; ?></a>

              <?php if (!empty($menu['children'])): ?>
                <div class="mega-menu">
                  <div class="column">
                    <h3><?= $menu['title']; ?></h3>
                    <ul>
                      <?php foreach ($menu['children'] as $child): ?>
                        <li>
                          <a href="<?= $child['link']; ?>">
                            <?= $child['title']; ?>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
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