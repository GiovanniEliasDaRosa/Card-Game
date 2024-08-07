const popupselectcolor = document.querySelector("#popupselectcolor");
const popupselectcolor__buttons = [...document.querySelectorAll(".popupselectcolor__buttons")];
const popupselectcolorPlaySelected = document.querySelector("#popupselectcolorPlaySelected");
const hand = document.querySelector("#hand");

Disable(popupselectcolor);

/* <start Disconnecting User> */
let disconnecTimeout = "";
let officialyDisconected = false;
let animationPlayed = false;
let time = 2000;
if (isMobile) {
  time = 0.001; // 1ms
} else {
  window.onbeforeunload = () => {
    disconectUser();
  };
  time = 60 * 4; // 4 minutes
}
document.addEventListener("visibilitychange", visibilityChanged);
/* </end Disconnecting User> */

playCard.onclick = () => {
  if (hand.querySelector("[data-selected]") == null) return;
  // Disable(getMoreCard, false);
  // Disable(playCard, false);

  let selectedCard = hand.querySelector("[data-selected]");
  let value = selectedCard.dataset.value;
  let color = selectedCard.classList[1];
  console.log(`SEND -> value='${value}' color='${color}'`);

  let data = {
    type: "game",
    content: {
      type: "wanttoplaycard",
      user: userName,
      id: userID,
      value: value,
      color: color,
    },
  };
  ws.send(JSON.stringify(data));
};

getMoreCard.onclick = () => {
  let data = {
    type: "game",
    content: {
      type: "wanttogetcard",
      user: userName,
      id: userID,
    },
  };
  ws.send(JSON.stringify(data));

  if (isMobile) {
    getMoreCard.blur();
  }

  Disable(getMoreCard, false);
};

message.onkeyup = (e) => {
  if (e.key != "Enter") return;
  sendMessage();
};

sendmessage.onclick = () => {
  sendMessage();
};

window.onresize = () => {
  ScaleCards();
};

ScaleCards();

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
  data = {
    type: "game",
    content: {
      id: userID,
      user: userName,
      type: "selectedcolor",
      colorid: colorid,
    },
  };
  ws.send(JSON.stringify(data));
  selected.removeAttribute("data-selected");
  return;
};

/* Useful Functions */
function ScaleCards() {
  let prefered = window.innerWidth;
  if (window.innerHeight < window.innerWidth) {
    prefered = window.innerHeight;
  }
  let result = Clamp(prefered / 2 / 64, 0, 5);
  root.style = `--scale: ${result}`;
}

function disconectUser() {
  let data = {
    type: "server",
    content: {
      type: "disconnected",
      id: userID,
      user: userName,
    },
  };
  response = ws.send(JSON.stringify(data));

  setTimeout(() => {
    ws.close();
  }, 2000);
}

function visibilityChanged() {
  clearTimeout(disconnecTimeout);
  if (document.hidden) {
    disconnecTimeout = setTimeout(() => {
      disconectUser();
      officialyDisconected = true;
    }, time * 1000);
  } else {
    if (officialyDisconected && !animationPlayed) {
      animationPlayed = true;
      // Disable(document.body, false);
      let interactableElements = [...document.querySelectorAll("input, button")];

      interactableElements.forEach((element) => {
        Enable(element);

        setTimeout(() => {
          Disable(element, false);
        }, 100 + RandomNumber(1, 10) * 200);
      });

      ShowError("A página ficou inativa por muito tempo, então você precisa atualizá-la.");

      document.querySelector(".showerrorAbsolute").classList.add("deactivated");
    }
  }
}

const main = document.querySelector("main");
const menu = document.querySelector("#menu");
menu.setAttribute("data-open", "");
menu.addEventListener("click", () => {
  if (chatopen) {
    menu.removeAttribute("data-open");
    menu.setAttribute("data-prepare-close", "");
    main.setAttribute("data-close", "true");
    chat.setAttribute("data-close", "true");
    setTimeout(() => {
      menu.removeAttribute("data-prepare-close");
      menu.setAttribute("data-close", "");
      setTimeout(() => {
        main.setAttribute("data-closed", "true");
        menu.removeAttribute("data-close");
      }, 500);
    }, 1);
    localStorage.setItem("menuopen", true);
    chatopen = false;
  } else {
    menu.setAttribute("data-open", "");
    menu.removeAttribute("data-close");

    chat.removeAttribute("data-close");
    main.removeAttribute("data-closed");
    setTimeout(() => {
      main.removeAttribute("data-close");
    }, 500);
    localStorage.setItem("menuopen", false);
    chatopen = true;
    unreadnotifications = 0;
    Disable(menu__notifications);
  }
});

let menuopensaved = localStorage.getItem("menuopen");
if (menuopensaved != null) {
  if (menuopensaved == "true") {
    menu.removeAttribute("data-open");
    main.setAttribute("data-close", "true");
    chat.setAttribute("data-close", "true");
    menu.setAttribute("data-close", "");

    main.setAttribute("data-closed", "true");
    menu.removeAttribute("data-close");
    chatopen = false;
    unreadnotifications = 0;
    Disable(menu__notifications);
  }
}
