document.addEventListener('DOMContentLoaded', function () {
    const words = [
        'Welcome',
        'ようこそ',
        'Bienvenido',
        'Willkommen',
        'Bienvenue',
        '歡迎',
        'Добро пожаловать',
        'Selamat datang',
        'Bem-vindo',
        '환영합니다',
    ];

    let idx = 0;
    setInterval(() => {
        idx = (idx + 1) % words.length;
        const w = document.getElementById('welcomeWord');
        w.style.opacity = '0';
        setTimeout(() => {
            w.textContent = words[idx];
            w.style.opacity = '1';
        }, 250);
    }, 1800);
});