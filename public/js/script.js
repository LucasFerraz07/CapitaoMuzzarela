gsap.registerPlugin(ScrollTrigger,ScrollSmoother,ScrollToPlugin,SplitText);


//ESTOU COM PROBLEMAS AQUI, FAIXAS BRANCAS APARECENDO. -->

ScrollSmoother.create({
	smooth: 0.5, // how long (in seconds) it takes to "catch up" to the native scroll position
	effects: true, // looks for data-speed and data-lag attributes on elements
});



  // Animação Títulos Sequencial
  const tl = gsap.timeline(); // Cria uma timeline para sequenciar

  // Coletar todos os caracteres de .titleSplit
  const allTitleChars = [];
  const titleSplit = document.querySelectorAll(".titleSplit");
  titleSplit.forEach((element) => {
    const split = SplitText.create(element, {
      type: "lines, words, chars",
      mask: "lines",
    });
    allTitleChars.push(...split.chars); // Adiciona todos os chars a um array
  });

  // Anima .titleSplit primeiro
  tl.from(allTitleChars, {
    y: 30,
    opacity: 0,
    stagger: 0.07,
    duration: 0.5,
  });

  // Coletar todos os caracteres de .textSplit
  const allTextChars = [];
  const textSplit = document.querySelectorAll(".textSplit"); // Use .textSplit ou .textoSplit conforme sua classe real
  textSplit.forEach((element) => {
    const split = SplitText.create(element, {
      type: "lines, words, chars",
      mask: "lines",
    });
    allTextChars.push(...split.chars);
  });

  // Anima .textSplit depois
  tl.from(allTextChars, {
    y: 30,
    opacity: 0,
    stagger: 0.04,
    duration: 0.4,
  });

// Scroll To entre sections

// Detect if a link's href goes to the current page
function getSamePageAnchor (link) {
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

// Scroll to a given hash, preventing the event given if there is one
function scrollToHash(hash, e) {
  const elem = hash ? document.querySelector(hash) : false;
  if(elem) {
    if(e) e.preventDefault();
    gsap.to(window, {scrollTo: elem});
  }
}

// If a link's href is within the current page, scroll to it instead
document.querySelectorAll('nav a[href]').forEach(a => {
  a.addEventListener('click', e => {
    scrollToHash(getSamePageAnchor(a), e);
  });
});

// Scroll to the element in the URL's hash on load
scrollToHash(window.location.hash);
