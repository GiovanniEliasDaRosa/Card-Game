const chatMessage = document.querySelector("#chatMessage");
let message = document.querySelector("#message");
let sendmessage = document.querySelector("#sendmessage");
let playCard = document.querySelector("#playCard");
let userNameElement = document.querySelector("#userName");
let otherplayers = document.querySelector("#otherplayers");
let getMoreCard = document.querySelector("#getMoreCard");
let table = document.querySelector("#table");
let currenttablecard = document.querySelector("#currenttablecard");
let loadingspinner = document.querySelector(".loadingspinner");

let testHUDuptimeout = "";
let testHUDup = document.querySelector("#testHUDup");
let testHUDdowntimeout = "";
let testHUDdown = document.querySelector("#testHUDdown");
let testHUDplayerstimeout = "";
let testHUDplayers = document.querySelector("#testHUDplayers");

let ws = null;
let activeUsers = 0;
let gamealreadyrunning = false;
// let secondconfirm = tru;

// Disable(getMoreCard, false);
// Disable(playCard, false);

let userName = localStorage.getItem("username");
if (userName == null) {
  window.location.href = `${window.location.origin}/Uno/index.html`;
}
userNameElement.textContent = userName;

fetch("fetchable/loadws.php", {
  method: "POST",
  body: new URLSearchParams(null),
  headers: {
    "Content-Type": "application/x-www-form-urlencoded",
  },
})
  .then((response) => response.json())
  .then((data) => {
    if (data.ok) StartWebSocket(data.serverADDR);
  })
  .catch((error) => {
    console.error(error);
  });

function StartWebSocket(serverADDR) {
  if (userName == null) return;

  if (serverADDR == "127.0.0.1") {
    let startGame = document.createElement("button");
    startGame.classList = "button";
    startGame.id = "startGame";
    startGame.textContent = "Começar jogo";

    table.appendChild(startGame);
    startGame.onclick = () => {
      let dados = {
        type: "game",
        content: {
          type: "startgame",
          content: {
            user: userName,
          },
        },
      };
      ws.send(JSON.stringify(dados));
      SendWS();
    };
  }

  ws = new WebSocket(`ws://${serverADDR}:8080/`);
  ws.onopen = (e) => {
    loadingspinner.remove();
    let dados = {
      type: "server",
      content: {
        type: "connected",
        content: userName,
      },
    };
    ws.send(JSON.stringify(dados));
    dados = {
      type: "game",
      content: {
        type: "getInfo",
        content: userName,
      },
    };
    ws.send(JSON.stringify(dados));
  };

  ws.onerror = (e) => {
    chatMessage.innerHTML = "ERRO";
  };

  ws.onmessage = (response) => {
    // console.log(response.data);
    let result = JSON.parse(response.data);
    // console.log(result.type, result);
    console.log(result.started);

    if (result.type == "users") {
      activeUsers = result.usersactive;
      UpdateActiveUsers();
      let theircards = JSON.parse(result.theircards);

      let turn = result.turn;
      let turnhasgotnewcard = result.turnhasgotnewcard;

      let tablecard = result.tablecard;

      currenttablecard.innerHTML = "";
      CreateCard(tablecard.value, tablecard.color, "table");

      otherplayers.innerHTML = "";

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
          classlist += "turn ";

          if (currentUser[0] == userName) {
            document.body.setAttribute("data-myturn", "");
            if (turnhasgotnewcard) {
              getMoreCard.innerText = "Passar vez";
            } else {
              getMoreCard.innerText = "Pescar";
            }
          } else {
            document.body.removeAttribute("data-myturn");
            getMoreCard.innerText = "Pescar";
          }
        }

        let result = `<p class='otherplayers ${classlist}'><span class='otherplayername'>${currentUser[0]}</span><span class='otherplayercardcount'>${currentUser[1]}</span></p>`;
        otherplayers.innerHTML += result;
      }
      console.log(result);
      // currenttablecard.innerHTML = result;
      // return;
    }

    if (result.type != "game") {
      if (result.started != null) {
        startGame.remove();
      }
      if (result.content != undefined) {
        chatMessage.insertAdjacentHTML("beforeend", `${result.content}`);
        let chatmessages = [...chatMessage.children];
        let lastchat = chatmessages[chatmessages.length - 1];
        lastchat.scrollIntoView({ behavior: "smooth" });
      }

      GetWS();
      return;
    }

    console.log("GAME ONLY");
    if (result.started != null) {
      console.warn("COMECO");
      gamealreadyrunning = true;
      let startGameButton = document.querySelector("#startGame");

      if (startGameButton != null) {
        startGameButton.remove();
      }

      dados = {
        type: "game",
        content: {
          type: "getInfo",
          content: userName,
        },
      };
      ws.send(JSON.stringify(dados));
      return;
    }

    console.log(result);
    if (gamealreadyrunning && result.gameendend) {
      let whowon = result.whowon;
      console.log(whowon);
      console.log("Game Endend");

      let resultedtext = "";
      for (let i = 0; i < whowon.length; i++) {
        if (i < whowon.length - 1) {
          resultedtext += whowon[i] + ", ";
        } else {
          resultedtext += whowon[i];
        }
      }

      setTimeout(() => {
        alert("Os ganhadores foram " + resultedtext);
      }, 500);

      if (serverADDR == "127.0.0.1") {
        let startGame = document.createElement("button");
        startGame.classList = "button";
        startGame.id = "startGame";
        startGame.textContent = "Começar jogo";

        table.appendChild(startGame);
        startGame.onclick = () => {
          let dados = {
            type: "game",
            content: {
              type: "startgame",
              content: {
                user: userName,
              },
            },
          };
          ws.send(JSON.stringify(dados));
          SendWS();
          startGame.remove();
        };
      }

      gamealreadyrunning = false;
      return;
    }

    if (result.who != userName) {
      console.log("other user", result.who);
      return;
    }

    let cards = result.content;
    hand.innerHTML = "";
    console.log(result.content);

    for (let i = 0; i < cards.length; i++) {
      const card = cards[i];
      CreateCard(card.value, card.color);
    }
  };
}

const sendMessage = () => {
  if (message.value.trim().length == 0) {
    return;
  }

  let dados = {
    type: "message",
    content: {
      user: userName,
      message: message.value,
    },
  };
  ws.send(JSON.stringify(dados));
  message.value = "";

  SendWS();
};

function SendWS() {
  clearTimeout(testHUDuptimeout);
  testHUDup.classList.add("active");

  testHUDuptimeout = setTimeout(() => {
    testHUDup.classList.remove("active");
  }, 1000);
}
function GetWS() {
  clearTimeout(testHUDdowntimeout);
  testHUDdown.classList.add("active");

  testHUDdowntimeout = setTimeout(() => {
    testHUDdown.classList.remove("active");
  }, 1500);
}

function UpdateActiveUsers() {
  testHUDplayers.innerText = activeUsers;
  clearTimeout(testHUDplayerstimeout);
  testHUDplayers.classList.add("active");

  testHUDplayerstimeout = setTimeout(() => {
    testHUDplayers.classList.remove("active");
  }, 1500);
}

window.onbeforeunload = () => {
  let dados = {
    type: "server",
    content: {
      type: "disconnected",
      content: userName,
    },
  };
  ws.send(JSON.stringify(dados));
};

// function DisconnectUser() {
//   let dados = {
//     type: "server",
//     content: {
//       type: "disconnected",
//       content: userName,
//     },
//   };
//   ws.send(JSON.stringify(dados));
// }

// let disconnecTimeout = "";

// document.addEventListener("visibilitychange", (e) => {
//   clearTimeout(disconnecTimeout);
//   if (document.hidden) {
//     disconnecTimeout = setTimeout(() => {
//       DisconnectUser();
//     }, 2000);
//   }
// });

playCard.onclick = () => {
  if (hand.querySelector("[data-selected]") == null) return;
  // Disable(getMoreCard, false);
  // Disable(playCard, false);

  let selectedCard = hand.querySelector("[data-selected]");
  let value = selectedCard.dataset.value;
  let color = selectedCard.classList[1];
  console.log(`SEND -> value='${value}' color='${color}'`);

  let dados = {
    type: "game",
    content: {
      type: "wanttoplaycard",
      content: {
        user: userName,
        value: value,
        color: color,
      },
    },
  };
  ws.send(JSON.stringify(dados));
  SendWS();
};

getMoreCard.onclick = () => {
  let dados = {
    type: "game",
    content: {
      type: "wanttogetcard",
      content: {
        user: userName,
      },
    },
  };
  ws.send(JSON.stringify(dados));
  SendWS();

  Disable(getMoreCard, false);
  setTimeout(() => {
    Enable(getMoreCard);
  }, 1000);
};

message.onkeyup = (e) => {
  if (e.key != "Enter") return;
  sendMessage();
};

sendmessage.onclick = () => {
  sendMessage();
};

const hand = document.querySelector("#hand");

function CreateCard(type, color, where = null) {
  let newCard = document.createElement("button");
  newCard.setAttribute("data-value", type);

  if (type < 10) {
    newCard.className = `card ${color}`;
    newCard.textContent = type;
  } else {
    newCard.className = `card ${color} ${type}`;
  }

  // type == "skip" ||
  // type == "reverse" ||
  if (type == "draw2" || type == "wild" || type == "wilddrawfour") {
    Disable(newCard, false);
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

      // if (document.querySelector(".card[data-selected") == null) {
      //   Disable(playCard, false);
      // } else {
      //   Enable(playCard);
      // }
    };
    hand.appendChild(newCard);
  } else {
    currenttablecard.appendChild(newCard);
  }

  // if (type == "skip" || type == "reverse" || type == "draw2") {
  //   console.log("skip || reverse || draw2");
  //   newCard.className = `card ${color} ${type}`;
  // } else if (type == "wild" || type == "wilddrawfour") {
  //   console.log("wild || wilddrawfour");
  //   newCard.className = `card ${color} ${type}`;
  // } else {
  //   newCard.className = `card ${color}`;
  //   newCard.textContent = type;
  // }

  // <button class="card red" data-value="0">0</button>
  // <button class="card yellow" data-value="1">1</button>
  // <button class="card green" data-value="2">2</button>
  // <button class="card blue" data-value="3">3</button>
  // <button class="card red" data-value="4">4</button>
  // <button class="card yellow" data-value="5">5</button>
  // <button class="card green" data-value="6">6</button>
  // <button class="card blue" data-value="7">7</button>
  // <button class="card red" data-value="8">8</button>
  // <button class="card yellow" data-value="9">9</button>
  // <button class="card green skip" data-value="skip"></button>
  // <button class="card blue reverse" data-value="reverse"></button>
  // <button class="card red draw2" data-value="draw2"></button>
  // <button class="card black wild" data-value="wild"></button>
  // <button class="card black wilddrawfour" data-value="wilddrawfour"></button>
  // CreateCard("0", "red");

  // const cards = [...document.querySelectorAll(".card")];

  // cards.forEach((item) => {
  //   item.onclick = () => {
  //     if (item.dataset.selected != null) {
  //       item.removeAttribute("data-selected");
  //     } else {
  //       cards.forEach((item) => {
  //         item.removeAttribute("data-selected");
  //       });
  //       item.setAttribute("data-selected", "");
  //     }
  //   };
  // });
}

window.onkeyup = (e) => {
  if (e.code != "KeyX" && e.code != "KeyY") return;

  let dados = {};
  if (e.code == "KeyX") {
    dados = {
      type: "users",
      content: {
        type: "getusers",
        content: "nothing",
      },
    };
  }
  if (e.code == "KeyY") {
    dados = {
      type: "gamenow",
      content: {
        type: "gamenow",
        content: "gamenow",
      },
    };
  }

  ws.send(JSON.stringify(dados));
  SendWS();
};

let root = document.querySelector(":root");

window.onresize = () => {
  ScaleCards();
};

ScaleCards();

function ScaleCards() {
  let prefered = window.innerWidth;
  if (window.innerHeight < window.innerWidth) {
    prefered = window.innerHeight;
  }
  let result = Clamp(prefered / 2 / 64, 0, 5);
  console.log(window.innerWidth, result);
  root.style = `--scale: ${result}`;
}

// let types = [
//   "0",
//   "1",
//   "2",
//   "3",
//   "4",
//   "5",
//   "6",
//   "7",
//   "8",
//   "9",
//   "skip",
//   "reverse",
//   "draw2",
//   "wild",
//   "wilddrawfour",
// ];

// let colors = ["red", "yellow", "green", "blue"];
// for (let i = 0; i < 7; i++) {
//   let type = types[RandomNumber(0, types.length)];
//   let color = 0;
//   if (type < 10 || !(type == "wild" || type == "wilddrawfour")) {
//     color = colors[RandomNumber(0, 4)];
//   } else {
//     color = "black";
//   }

//   // setTimeout(() => {
//   CreateCard(type, color);
//   // }, i * 10 + RandomNumber(0, i * 10));
// }

// CreateCard("0", "red");
// CreateCard("0", "yellow");
// CreateCard("0", "green");
// CreateCard("0", "blue");
// CreateCard("skip", "red");
// CreateCard("reverse", "yellow");
// CreateCard("draw2", "green");
// CreateCard("wild", "black");
// CreateCard("wilddrawfour", "black");
