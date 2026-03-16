<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/x-icon" href="public/images/favicon.png">
  <!-- Flatpickr -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
  <!-- CSS original do projeto -->
  <link rel="stylesheet" href="public/css/style.css" />
  <!-- CSS do sistema de reservas -->
  <link rel="stylesheet" href="public/css/reserva.css" />
  <title>Capitão Muzzarela</title>
</head>

<body>
  <div id="smooth-wrapper">
    <div id="smooth-content">
      <header>
        <img
          src="public/images/capitaoLogo-Header.webp"
          alt="Logo do Capitão Muzzarela"
          class="logoHeader" />
        <nav>
          <ul>
            <li><a href="#hero">Home</a></li>
            <li><a href="#sessaoCardapio">Cardápio</a></li>
            <li><a href="#sessaoReserva">Galeria</a></li>
            <li><a href="#sessaoIFood">Delivery</a></li>
            <li>
              <!-- Botão que abre o modal (mantém id e classe originais) -->
              <button class="btn-reserva" id="btn-header">Reserva</button>
            </li>
          </ul>
        </nav>
      </header>

      <main>
        <section class="hero" id="hero">
          <div class="esquerda">
            <div class="textos">
              <h1 class="titleSplit">VENHA CONHECER A MELHOR PIZZARIA DA REGIÃO!</h1>
              <p class="textSplit">
                Aqui no <strong>CAPITÃO MUZZARELA</strong> cada fatia é uma
                viagem ao coração da tradição italiana. Somos a pizzaria
                número UM de Cruzeiro.
              </p>
            </div>

            <div class="botoes">
              <!-- Botão Hero — abre o mesmo modal -->
              <button class="reserva-hero">
                <img
                  src="public/images/calendario.svg"
                  alt="Ícone de calendário com um confere" />
                Faça sua reserva agora
              </button>

              <button class="btn-delivery">
                <img src="public/images/pizza.svg" alt="Ícone de pizza" /> Link
                para Delivery
              </button>
            </div>
          </div>

          <div class="direita">
            <img  data-speed="0.9"
              src="public/images/pizzaInteira.svg"
              alt="Imagem de uma pizza deliciosa"
              class="pizzaHero" />
            <img
              src="public/images/folhaHero.svg"
              alt="Folha decorativa"
              class="folhaHero" />
          </div>
        </section>

        <section class="sessaoCardapio" id="sessaoCardapio">
          <h2>Destaques do Capitão</h2>
          <ul class="menu">
            <button class="btn-menu" id="btn-pizzas">PIZZAS</button>
            <button class="btn-menu" id="btn-lanches">LANCHES</button>
            <button class="btn-menu" id="btn-bebidas">BEBIDAS</button>
            <button class="btn-menu" id="btn-sobremesas">SOBREMESAS</button>
          </ul>

          <div class="cards">
            <div class="card">
              <img src="public/images/pizzaCardapio.webp" alt="Pizza Calabresa" />
              <h3>Calabresa</h3>
              <p>Muzzarela, calabresa, cebola e orégano</p>
            </div>
            <div class="card">
              <img src="public/images/pizzaCardapio.webp" alt="Pizza Calabresa" />
              <h3>Calabresa</h3>
              <p>Muzzarela, calabresa, cebola e orégano</p>
            </div>
            <div class="card">
              <img src="public/images/pizzaCardapio.webp" alt="Pizza Calabresa" />
              <h3>Calabresa</h3>
              <p>Muzzarela, calabresa, cebola e orégano</p>
            </div>
            <div class="card">
              <img src="public/images/pizzaCardapio.webp" alt="Pizza Calabresa" />
              <h3>Calabresa</h3>
              <p>Muzzarela, calabresa, cebola e orégano</p>
            </div>
          </div>

          <div class="botoesCaradapio">
            <button class="btn-cardapio">
              <img src="public/images/cardapioButton.svg" alt="Ícone de cardápio" />
              Acesse nosso cardápio completo
            </button>
            <button class="btn-delivery">
              <img src="public/images/pizza.svg" alt="Ícone de Pizza" /> Link para Delivery
            </button>
          </div>
        </section>

        <section class="sessaoReserva" id="sessaoReserva">
          <h2>CONHEÇA NOSSO ESPAÇO</h2>
          <!-- Botão na seção reserva — também abre o modal -->
          <button class="btn-reserva">
            Faça sua reserva agora
            <img src="public/images/setaButton.svg" alt="Seta para direita" />
          </button>
        </section>

        <section class="sessaoGaleria" id="sessaoGaleria">
          <div class="galeriaEsquerda">
            <img src="public/images/ambiente1-Galeria.webp" alt="Mesa decorada" />
            <img src="public/images/ambiente2-Galeria.webp" alt="Ambiente interno" />
            <img src="public/images/ambiente3-Galeria.webp" alt="Ambiente externo" />
          </div>
          <div class="galeriaCentro">
            <img src="public/images/gastronomia1-Galeria.webp" alt="Petiscos variados" />
            <img src="public/images/gastronomia2-Galeria.webp" alt="Pizza portuguesa" />
          </div>
          <div class="galeriaDireita">
            <img src="public/images/bebidas1-Galeria.webp" alt="Caipirinha de Limão" />
            <img src="public/images/bebidas2-Galeria.webp" alt="Balde de cerveja" />
            <img src="public/images/bebidas3-Galeria.webp" alt="Drink de morango" />
          </div>
        </section>

        <section class="sessaoIFood" id="sessaoIFood">
          <img src="public/images/ondaGaleira.svg" alt="" />
          <div class="conteudoSessao">
            <div class="heroiIFood">
              <img
                src="public/images/heroiIFood.webp"
                alt="Herói do Capitão Muzzarela com bolsa iFood" />
            </div>
            <div class="conteudoIFood">
              <h2>
                <b>QUER COMER PIZZA, MAS NÃO QUER SAIR DE CASA?</b> NÓS
                ENVIAMOS PARA VOCÊ COM <b>ENTREGA EXPRESS DO IFOOD!</b>
              </h2>
              <ul class="checksIFood">
                <li>
                  <img src="public/images/confere.svg" alt="Ícone de confere" />
                  <b>PEDIU, CHEGOU!</b>
                </li>
                <li>
                  <img src="public/images/confere.svg" alt="Ícone de confere" />
                  <b>SEU PEDIDO CHEGA QUENTINHO E INTACTO</b>
                </li>
                <li>
                  <img src="public/images/confere.svg" alt="Ícone de confere" />
                  <b>TEMPO DE ESPERA MÍNIMO</b>
                </li>
              </ul>
              <button class="btn-delivery">
                <img src="public/images/pizza.svg" alt="Ícone de Pizza" /> Link para Delivery
              </button>
            </div>
          </div>
        </section>
      </main>

      <footer>
        <div class="conteudoFooter">
          <div class="logotipo">
            <a href="public/api/?action=admin-login">
            <img src="public/images/capitaoLogo-Footer.webp" alt="Logo do Capitão Muzzarela" />
            </a>
            <img src="public/images/divisorFooter.svg" alt="Divisor decorativo" />
          </div>
          <div class="info">
            <div class="midia">
              <h3>REDES SOCIAIS</h3>
              <ul class="redes">
                <li><img src="public/images/igFooter.svg" alt="Instagram" /></li>
                <li><img src="public/images/wppFooter.svg" alt="WhatsApp" /></li>
              </ul>
            </div>
            <div class="endereco">
              <h3>ENDEREÇO</h3>
              <p>Av. Jorge Tibiriçá, 1219 - Vila Canevari, Cruzeiro - SP.</p>
            </div>
          </div>
        </div>
      </footer>

    </div>
  </div>

  <!-- ║  MODAL DE RESERVA (incluído via PHP)      ║ -->
  <?php include __DIR__ . '/app/views/modal_reserva.php'; ?>

  <!-- Scripts GSAP -->
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.14.1/dist/gsap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.14.1/dist/ScrollTrigger.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.14.1/dist/ScrollSmoother.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.14.1/dist/ScrollToPlugin.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.14.1/dist/SplitText.min.js"></script>

  <!-- Script original do projeto -->
  <script src="public/js/script.js"></script>

  <!-- Script do sistema de reservas -->
  <script src="public/js/reserva.js"></script>
</body>

</html>
