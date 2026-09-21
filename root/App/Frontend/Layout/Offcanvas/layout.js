document.addEventListener('DOMContentLoaded', function () {
  const hamburger = document.getElementById('hamburger');
  const sideMenu = document.getElementById('sideMenu');
  const closeBtn = document.getElementById('closeBtn');

  hamburger.addEventListener('click', () => {
    sideMenu.classList.add('open');
  });

  closeBtn.addEventListener('click', () => {
    sideMenu.classList.remove('open');
  });
});
