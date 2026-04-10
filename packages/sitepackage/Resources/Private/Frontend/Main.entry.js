const toggle = document.getElementById('menu-toggle');
const menu = document.getElementById('mobile-menu');

if (toggle && menu) {
    const [bar1, bar2, bar3] = toggle.querySelectorAll('span');

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';

        if (isOpen) {
            menu.style.gridTemplateRows = '0fr';
            menu.style.opacity = '0';
            bar1.style.transform = '';
            bar2.style.opacity = '';
            bar3.style.transform = '';
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Menü öffnen');
        } else {
            menu.style.gridTemplateRows = '1fr';
            menu.style.opacity = '1';
            bar1.style.transform = 'translateY(8px) rotate(45deg)';
            bar2.style.opacity = '0';
            bar3.style.transform = 'translateY(-8px) rotate(-45deg)';
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Menü schließen');
        }
    });
}

// window.addEventListener('scroll', () => {
//     const header = document.querySelector('header');
//     if (!header) return;
//
//     if (window.scrollY > 0) {
//         header.classList.replace('pt-6', 'pt-4');
//         header.querySelector('img').classList.replace('h-12', 'h-10');
//         header.classList.add('shadow-md');
//     } else {
//         header.classList.replace('pt-4', 'pt-6');
//         header.querySelector('img').classList.replace('h-10', 'h-12');
//         header.classList.remove('shadow-md');
//     }
// });
