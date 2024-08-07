const chat = document.querySelector("#chat");
const chatMessage = document.querySelector("#chatMessage");
const message = document.querySelector("#message");
const sendmessage = document.querySelector("#sendmessage");
const playCard = document.querySelector("#playCard");
const userNameElement = document.querySelector("#userName");
const userIDElement = document.querySelector("#userID");
const otherplayers = document.querySelector("#otherplayers");
const getMoreCard = document.querySelector("#getMoreCard");
const table = document.querySelector("#table");
const currenttablecard = document.querySelector("#currenttablecard");
const loadingspinner = document.querySelector(".loadingspinner");
const gamedirection = document.querySelector("#gamedirection");

let getcardcountTimeout = "";
const getcardcount = document.querySelector("#getcardcount");
const root = document.querySelector(":root");
let thisUserTurn = false;
let turnhasgotnewcard = false;

let activePlayersTimeout = "";
const activePlayers = document.querySelector("#activePlayers");

const menu__notifications = document.querySelector("#menu__notifications");
let chatopen = true;
let unreadnotifications = 0;
Disable(menu__notifications);

let ws = null;
let activeUsers = 0;
let gamealreadyrunning = false;
let gameended = false;
let updatedUsersCard = false;

Disable(getMoreCard, false);
Disable(playCard, false);
Disable(getcardcount);

StartWebSocket();

function StartWebSocket() {
  if (userName == "") return;

  TryCreateStartButton();

  ws = new WebSocket(`ws://${serverADDR}:8080/`);
  ws.onopen = (e) => {
    chatMessage.removeAttribute("data-loading");
    loadingspinner.remove();
    let data = {
      type: "server",
      content: {
        type: "connected",
        id: userID,
        user: userName,
      },
    };
    ws.send(JSON.stringify(data));
    getInfo();
  };

  ws.onmessage = (response) => {
    let result = JSON.parse(response.data);

    let startGameButton = document.querySelector("#startGame");
    if (startGameButton != null) {
      if (result.started != null) {
        startGameButton.remove();
        // getInfo();
        gameended = false;
      }
    } else if (result.gameendend != null) {
      TryCreateStartButton();
    }

    switch (result.type) {
      case "message":
        updateChatWS(result);
        if (!chatopen) {
          unreadnotifications++;
          menu__notifications.innerText = unreadnotifications;
          Enable(menu__notifications);
        } else {
          unreadnotifications = 0;
          Disable(menu__notifications);
        }
        break;
      case "selectcolor":
        selectColorWS(result);
        break;
      case "users":
        updateUsersWS(result);
        break;
      case "game":
        updateGameWS(result);
        // selectcolor || users || messages
        break;
    }
  };

  ws.onerror = (e) => {
    chatMessage.removeAttribute("data-loading");
    ShowError("Não foi possível conectar ao server", true);
  };
}

const sendMessage = () => {
  if (message.value.trim().length == 0) {
    return;
  }

  let data = {
    type: "message",
    content: {
      id: userID,
      user: userName,
      message: message.value,
    },
  };
  ws.send(JSON.stringify(data));
  message.value = "";
};

/* Web Socket Functions */
function selectColorWS(result) {
  if (result.content == "select") {
    Disable(getMoreCard, false);
    Disable(playCard, false);
    Enable(popupselectcolor);
  } else {
    Disable(popupselectcolor);
  }
  return;
}

function updateUsersWS(result) {
  activeUsers = result.usersactive;
  UpdateActiveUsers();
  let theircards = JSON.parse(result.theircards);

  let turn = result.turn;
  turnhasgotnewcard = result.turnhasgotnewcard;

  let tablecard = result.tablecard;

  currenttablecard.innerHTML = "";
  let color = tablecard.color;
  if (result.selectedcolor != "") {
    color = result.selectedcolor;
  }
  CreateCard(tablecard.value, color, "table");

  if (!updatedUsersCard) {
    if (tablecard.value == "draw2" || tablecard.value == "wilddrawfour") {
      getInfo();
      updatedUsersCard = true;

      setTimeout(() => {
        updatedUsersCard = false;
      }, 1500);
    }
  }

  let getcardcountvalue = Number(result.getcardcount);
  if (result.getcardcount > 0) {
    getcardcount.innerText = result.getcardcount;
    Enable(getcardcount);
    getcardcount.removeAttribute("data-reset");
  } else {
    clearTimeout(getcardcountTimeout);
    Disable(getcardcount, false);
    getcardcount.setAttribute("data-reset", "");
    getcardcountTimeout = setTimeout(() => {
      Disable(getcardcount, true);
      getcardcount.removeAttribute("data-reset");
    }, 2000);
  }

  let direction = result.direction;
  if (direction == -1) {
    gamedirection.classList.add("leftarrow");
  } else {
    gamedirection.classList.remove("leftarrow");
  }
  // Show the direction of the playing
  // leftarrow
  // rightarrow
  // otherplayers.innerHTML = "";

  otherplayers.innerHTML = "";
  Disable(getMoreCard, false);
  Disable(playCard, false);

  for (let i = 0; i < theircards.length; i++) {
    const currentUser = theircards[i];
    let classlist = "";
    if (currentUser[1] == 0) {
      classlist += "won ";
    }
    if (currentUser[0] == userName) {
      classlist += "you ";
    }
    if (currentUser[0] == turn) {
      classlist += "turn icons uparrow";

      if (currentUser[0] == userName) {
        Enable(getMoreCard, false);
        Enable(playCard, false);
        if (gameended) {
          document.body.removeAttribute("data-myturn");
          thisUserTurn = false;
          Disable(playCard, false);
        } else {
          document.body.setAttribute("data-myturn", "");
          thisUserTurn = true;
          Enable(getMoreCard);
        }

        if (turnhasgotnewcard) {
          getMoreCard.innerText = "Passar vez";
        } else {
          getMoreCard.innerText = "Pescar";
        }
      } else {
        document.body.removeAttribute("data-myturn");
        getMoreCard.innerText = "Pescar";
        thisUserTurn = false;
      }
    }

    let user = currentUser[0];
    let cards = currentUser[1];

    let result = `<p class='otherplayers ${classlist}'>
      <span class='otherplayername' data-user="${user}">${user}</span>
      <span class='otherplayercardcount' data-cards="${cards}">${cards}</span>
    </p>`;
    otherplayers.innerHTML += result;
  }

  updateHandCards(getcardcountvalue);
}

function updateGameWS(result) {
  if (result.who != userName) return;

  let cards = result.content;
  hand.innerHTML = "";

  for (let i = 0; i < cards.length; i++) {
    const card = cards[i];
    CreateCard(card.value, card.color);
  }

  if (result.started != null) {
    gamealreadyrunning = true;
    let startGameButton = document.querySelector("#startGame");

    if (startGameButton != null) {
      startGameButton.remove();
    }

    getInfo();
    return;
  }

  if (gamealreadyrunning && result.gameendend) {
    // let whowon = result.whowon;

    TryCreateStartButton();

    gamealreadyrunning = false;
    gameended = true;
    return;
  }
}

function updateChatWS(result) {
  if (result.content != undefined) {
    chatMessage.insertAdjacentHTML("beforeend", `${result.content}`);
    let chatmessages = [...chatMessage.children];
    let lastchat = chatmessages[chatmessages.length - 1];
    if (lastchat != null) {
      lastchat.scrollIntoView({ behavior: "smooth" });
    }
  }
}

/* Useful Functions */
function UpdateActiveUsers() {
  activePlayers.innerText = activeUsers;
  clearTimeout(activePlayersTimeout);
  activePlayers.classList.add("active");

  activePlayersTimeout = setTimeout(() => {
    activePlayers.classList.remove("active");
  }, 1500);
}

function getInfo() {
  let data = {
    type: "game",
    content: {
      type: "getInfo",
      user: userName,
      id: userID,
    },
  };
  ws.send(JSON.stringify(data));
}

function CreateCard(type, color, where = null) {
  let newCard = document.createElement("button");
  newCard.setAttribute("data-value", type);

  if (type < 10) {
    newCard.className = `card ${color}`;
    newCard.textContent = type;
  } else {
    newCard.className = `card ${color} ${type}`;
    if (type == "wilddrawfour") {
      newCard.textContent = "+4";
    }
  }

  if (where == null) {
    newCard.onclick = () => {
      if (newCard.dataset.selected != null) {
        newCard.removeAttribute("data-selected");
      } else {
        const cards = [...document.querySelectorAll(".card")];
        cards.forEach((newCard) => {
          newCard.removeAttribute("data-selected");
        });
        newCard.setAttribute("data-selected", "");
      }
    };
    hand.appendChild(newCard);
  } else {
    newCard.classList.add("currenttablecard");
    currenttablecard.appendChild(newCard);
    Disable(currenttablecard.children[0], false);
  }
}

function TryCreateStartButton() {
  if (serverADDR == "127.0.0.1" || userName == "Giovanni") {
    let startGame = document.createElement("button");
    startGame.classList = "button";
    startGame.id = "startGame";
    startGame.textContent = "Começar jogo";

    table.appendChild(startGame);
    startGame.onclick = () => {
      let data = {
        type: "game",
        content: {
          type: "startgame",
          id: userID,
          user: userName,
        },
      };
      ws.send(JSON.stringify(data));
    };
  }
}

function ShowError(text, reset = false) {
  let tablecard = currenttablecard.children[0];
  if (tablecard.classList.contains("loading")) {
    tablecard.classList.add("stopspinner");
    tablecard.classList.add("error");
  }

  chatMessage.setAttribute("data-haserror", "true");
  if (reset) {
    chatMessage.innerHTML = `<div class='showerror'>
      <img src='img/error.png' />
      <p class='error'>${text}</p>
    </div>`;
  } else {
    document.body.innerHTML += `<div class='showerrorAbsolute'>
      <div class='showerror'>
        <img src='img/error.png' />
        <p class='error'>${text}</p>
      </div>
    </div>`;
  }
}
