gsap.registerPlugin(ScrollTrigger,ScrollSmoother,ScrollToPlugin,SplitText);


//ESTOU COM PROBLEMAS AQUI, FAIXAS BRANCAS APARECENDO. -->

ScrollSmoother.create({
	smooth: 0, // how long (in seconds) it takes to "catch up" to the native scroll position
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
    stagger: 0.09,
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