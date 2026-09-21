<!-- License - Onetone Framework (AGPL-3.0) template -->
<header>
  <div class="container">
    <div class="logo">
        <?php if (!empty($logo)): ?>
          <img src="<?= $logo; ?>"/>
        <?php endif;?>
    </div>
    <button class="hamburger" id="hamburger">&#9776;</button>
  </div>
</header>

<div id="sideMenu" class="side-menu">
  <a href="#" class="close-btn" id="closeBtn">&times;</a>
  <ul>
    <?php foreach ($menus as $menu): ?>
      <li>
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
</div>

<div id="contents">
    <?= $content; ?>
</div>
