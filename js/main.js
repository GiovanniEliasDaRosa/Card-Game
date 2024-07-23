const chatMessage = document.querySelector("#chatMessage");
let message = document.querySelector("#message");
let sendmessage = document.querySelector("#sendmessage");
let playCard = document.querySelector("#playCard");
let userNameElement = document.querySelector("#userName");
let loadingspinner = document.querySelector(".loadingspinner");

let testHUDuptimeout = "";
let testHUDup = document.querySelector("#testHUDup");
let testHUDdowntimeout = "";
let testHUDdown = document.querySelector("#testHUDdown");

let ws = null;

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
    let result = JSON.parse(response.data);
    chatMessage.insertAdjacentHTML("beforeend", `${result.content}`);

    chatMessage.scroll(0, chatMessage.clientHeight);

    clearTimeout(testHUDdowntimeout);
    testHUDdown.classList.add("active");

    testHUDdowntimeout = setTimeout(() => {
      testHUDdown.classList.remove("active");
    }, 1500);
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
  let dados = {
    type: "game",
    content: {
      type: "userwatstoplaycard",
      content: userName,
    },
  };
  ws.send(JSON.stringify(dados));
  SendWS();
};

message.onkeyup = (e) => {
  if (e.key != "Enter") return;
  sendMessage();
};

sendmessage.onclick = () => {
  sendMessage();
};
