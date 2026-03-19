gsap.registerPlugin(ScrollTrigger, ScrollSmoother, ScrollToPlugin, SplitText);

// ── Desativa data-speed em telas pequenas ─────────────────
// O efeito parallax do ScrollSmoother causa sobreposição de
// elementos em mobile pois o layout não tem espaço para
// absorver o deslocamento gerado pelo data-speed.
const isMobile = window.innerWidth <= 840;

if (isMobile) {
    // Remove o atributo data-speed de todos os elementos que o possuem
    document.querySelectorAll('[data-speed]').forEach((el) => {
        el.removeAttribute('data-speed');
    });
}

// ── ScrollSmoother ────────────────────────────────────────
ScrollSmoother.create({
    smooth: 0.5,
    effects: !isMobile, // desativa os efeitos de parallax em mobile
});

// ── Animação Títulos Sequencial ───────────────────────────
const tl = gsap.timeline();

const allTitleChars = [];
const titleSplit = document.querySelectorAll('.titleSplit');
titleSplit.forEach((element) => {
    const split = SplitText.create(element, {
        type: 'lines, words, chars',
        mask: 'lines',
    });
    allTitleChars.push(...split.chars);
});

tl.from(allTitleChars, {
    y: 30,
    opacity: 0,
    stagger: 0.045,
    duration: 0.5,
});

const allTextChars = [];
const textSplit = document.querySelectorAll('.textSplit');
textSplit.forEach((element) => {
    const split = SplitText.create(element, {
        type: 'lines, words, chars',
        mask: 'lines',
    });
    allTextChars.push(...split.chars);
});

tl.from(allTextChars, {
    y: 30,
    opacity: 0,
    stagger: 0.03,
    duration: 0.4,
});

// ── Scroll To entre sections ──────────────────────────────
function getSamePageAnchor(link) {
    if (
        link.protocol !== window.location.protocol ||
        link.host !== window.location.host ||
        link.pathname !== window.location.pathname ||
        link.search !== window.location.search
    ) {
        return false;
    }
    return link.hash;
}

function scrollToHash(hash, e) {
    const elem = hash ? document.querySelector(hash) : false;
    if (elem) {
        if (e) e.preventDefault();
        gsap.to(window, { scrollTo: elem });
    }
}

document.querySelectorAll('nav a[href]').forEach((a) => {
    a.addEventListener('click', (e) => {
        scrollToHash(getSamePageAnchor(a), e);
    });
});

scrollToHash(window.location.hash);

// ── Animações na section Galeria ──────────────────────────
// Em mobile as animações de galeria também são desativadas
// pois o layout em coluna única não tem profundidade suficiente
// para o efeito de entrada fazer sentido visual.
if (!isMobile) {
    gsap.from('.galeriaEsquerda img', {
        y: 320,
        opacity: 0,
        stagger: 0.1,
        duration: 1.4,
        scrollTrigger: {
            trigger: '.sessaoGaleria',
            markers: false,
            start: '0% 50%',
            end: '100% 45%',
        },
    });

    gsap.from('.galeriaCentro img', {
        y: -100,
        opacity: 0,
        stagger: 0.1,
        duration: 1.4,
        scrollTrigger: {
            trigger: '.sessaoGaleria',
            markers: false,
            start: '0% 50%',
            end: '100% 45%',
        },
    });

    gsap.from('.galeriaDireita img', {
        y: 320,
        opacity: 0,
        stagger: 0.1,
        duration: 1.4,
        scrollTrigger: {
            trigger: '.sessaoGaleria',
            markers: false,
            start: '0% 50%',
            end: '100% 45%',
        },
    });
}
else{
    gsap.from('.sessaoGaleria img', {
        y: 80,
        opacity: 0,
        stagger: 0.7,
        filter: "blur(10px)", // Efeito de desfoque
        scrollTrigger: {
            trigger: '.sessaoGaleria',
            markers: false,
            start: '0% 80%',
            end: '100% 65%',
            scrub: true,
        },
    });
}
