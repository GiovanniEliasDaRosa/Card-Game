<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Acessar Uno</title>
  <link rel="icon" href="img/favicon_16x16.png" sizes="16x16" />

  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/menu.css" />

  <script src="js/functions.js" defer></script>
  <script src="js/menu.js" defer></script>
</head>

<body>
  <main>
    <div id="headerTitle">
      <img src="img/favicon_16x16.png" alt="Card image" id="cardImage" />
      <h1>Acessar Uno</h1>
    </div>
    <label for="nome">Nome</label>
    <input type="text" name="username" id="username" placeholder="Digite o nome" required />
    <p id="warntext">
      <strong>Atenção</strong>: Uma vez criado um nome, ele
      <strong>não poderá ser alterado</strong>.<br />
      Escolha com cuidado, pois será <strong>permanente</strong>.
    </p>
    <input type="button" name="acessar" value="Acessar" class="button" id="acessButton" />
  </main>
</body>

</html>