function initTocSpy() {
    const nav = document.querySelector('[data-toc-nav]');
    if (!nav) return;

    const links = [...nav.querySelectorAll('a[data-toc-link]')];
    if (!links.length) return;

    const sections = links
        .map(link => {
            const hash = link.getAttribute('href')?.split('#').pop();
            const target = hash ? document.getElementById(hash) : null;
            return { link, target };
        })
        .filter(({ target }) => target !== null);

    if (!sections.length) return;

    function setActive(index) {
        sections.forEach(({ link }, i) => {
            link.dataset.active = i === index ? 'true' : 'false';
        });
    }

    // Initialise: first entry active
    setActive(0);

    const observer = new IntersectionObserver(
        (entries) => {
            // Find the topmost intersecting section
            const intersecting = sections
                .map((s, i) => ({ ...s, index: i }))
                .filter(({ target }) =>
                    entries.find(e => e.target === target && e.isIntersecting)
                );

            if (intersecting.length > 0) {
                setActive(intersecting[0].index);
            }
        },
        // Trigger when element crosses the upper third of the viewport
        { rootMargin: '-10% 0px -65% 0px', threshold: 0 }
    );

    sections.forEach(({ target }) => observer.observe(target));
}

document.addEventListener('DOMContentLoaded', initTocSpy);
