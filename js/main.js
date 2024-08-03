const chatMessage = document.querySelector("#chatMessage");
const message = document.querySelector("#message");
const sendmessage = document.querySelector("#sendmessage");
const playCard = document.querySelector("#playCard");
const userNameElement = document.querySelector("#userName");
const otherplayers = document.querySelector("#otherplayers");
const getMoreCard = document.querySelector("#getMoreCard");
const table = document.querySelector("#table");
const currenttablecard = document.querySelector("#currenttablecard");
const loadingspinner = document.querySelector(".loadingspinner");
const root = document.querySelector(":root");

const popupselectcolor = document.querySelector("#popupselectcolor");
const popupselectcolor__buttons = [...document.querySelectorAll(".popupselectcolor__buttons")];
const popupselectcolorPlaySelected = document.querySelector("#popupselectcolorPlaySelected");

Disable(popupselectcolor);

let testHUDuptimeout = "";
const testHUDup = document.querySelector("#testHUDup");
let testHUDdowntimeout = "";
const testHUDdown = document.querySelector("#testHUDdown");
let testHUDplayerstimeout = "";
const testHUDplayers = document.querySelector("#testHUDplayers");

let ws = null;
let activeUsers = 0;
let gamealreadyrunning = false;
let gameended = false;
// Disable(getMoreCard, false);
// Disable(playCard, false);
let updatedUsersCard = false;

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
    console.log(data);
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
    chatMessage.innerHTML = "<p class='server'>ERRO</p>";
  };

  ws.onmessage = (response) => {
    let result = JSON.parse(response.data);

    if (result.type == "selectcolor") {
      if (result.content == "select") {
        Enable(popupselectcolor);
      } else {
        Disable(popupselectcolor);
      }
      return;
    }

    if (result.type == "users") {
      activeUsers = result.usersactive;
      UpdateActiveUsers();
      let theircards = JSON.parse(result.theircards);

      let turn = result.turn;
      let turnhasgotnewcard = result.turnhasgotnewcard;

      let tablecard = result.tablecard;

      currenttablecard.innerHTML = "";
      let color = tablecard.color;
      if (result.selectedcolor != "") {
        color = result.selectedcolor;
      }
      CreateCard(tablecard.value, color, "table");

      if (!updatedUsersCard) {
        if (tablecard.value == "draw2" || tablecard.value == "wilddrawfour") {
          ws.send(JSON.stringify(dados));
          dados = {
            type: "game",
            content: {
              type: "getInfo",
              content: userName,
            },
          };
          ws.send(JSON.stringify(dados));
          updatedUsersCard = true;

          setTimeout(() => {
            updatedUsersCard = false;
          }, 500);
        }
      }

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
            if (gameended) {
              document.body.removeAttribute("data-myturn");
            } else {
              document.body.setAttribute("data-myturn", "");
            }
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
    }

    if (result.type != "game") {
      if (result.started != null) {
        startGame.remove();
        gameended = false;
      }
      if (result.content != undefined) {
        chatMessage.insertAdjacentHTML("beforeend", `${result.content}`);
        let chatmessages = [...chatMessage.children];
        let lastchat = chatmessages[chatmessages.length - 1];
        if (lastchat != null) {
          lastchat.scrollIntoView({ behavior: "smooth" });
        }
      }

      GetWS();
      return;
    }

    if (result.started != null) {
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

    if (gamealreadyrunning && result.gameendend) {
      let whowon = result.whowon;

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
      gameended = true;
      return;
    }

    if (result.who != userName) return;

    let cards = result.content;
    hand.innerHTML = "";
    console.log(result.content);

    for (let i = 0; i < cards.length; i++) {
      const card = cards[i];
      CreateCard(card.value, card.color);
    }

    // console.log(result.userloaded);
    // console.log(result.userloaded == false);
    // if (result.userloaded) {
    //   updatedUsersCard = false;
    // }
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
    currenttablecard.appendChild(newCard);
  }
}

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

popupselectcolor__buttons.forEach((currentButton) => {
  currentButton.onclick = () => {
    if (currentButton.dataset.selected != null) {
      currentButton.removeAttribute("data-selected");
    } else {
      popupselectcolor__buttons.forEach((button) => {
        button.removeAttribute("data-selected");
      });
      currentButton.setAttribute("data-selected", "");
    }
  };
});

popupselectcolorPlaySelected.onclick = () => {
  let selected = document.querySelector(".popupselectcolor__buttons[data-selected]");
  if (selected == null) return;

  let colorid = Number(selected.dataset.id);
  if (colorid < 0 || colorid > 3) return;
  dados = {
    type: "game",
    content: {
      type: "selectedcolor",
      content: colorid,
    },
  };
  ws.send(JSON.stringify(dados));
  selected.removeAttribute("data-selected");
  return;
};

// Enable(popupselectcolor);
