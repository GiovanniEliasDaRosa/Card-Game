const acessButton = document.querySelector("#acessButton");
const warntext = document.querySelector("#warntext");
const username = document.querySelector("#username");
let loadedid = localStorage.getItem("userid");
let thisusername = null;

function createUniqueID() {
  let characthers = [
    "a",
    "b",
    "c",
    "d",
    "e",
    "f",
    "g",
    "h",
    "i",
    "j",
    "k",
    "l",
    "m",
    "n",
    "o",
    "p",
    "q",
    "r",
    "s",
    "t",
    "u",
    "v",
    "z",
    "x",
    "y",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    "0",
  ];

  let result = "";

  for (let i = 0; i < 10; i++) {
    result += characthers[RandomNumber(0, characthers.length)];
  }

  return result;
}

if (loadedid != null) {
  trySendUUID(loadedid, true);
}

function trySendUUID(thisuuid, testing = false) {
  console.log("trySendUUID", thisuuid);

  fetch("fetchable/uuid.php", {
    method: "POST",
    body: new URLSearchParams({ type: "availableuuid", uuid: thisuuid, testing: testing }),
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (testing) {
        thisusername = data.name;
        if (thisusername == undefined) {
          localStorage.removeItem("username");
          localStorage.removeItem("userid");
          return;
        }

        username.value = thisusername;
        console.log(`Returned
id:'${thisuuid}'
name:'${thisusername}'`);
        // Future disable

        Disable(username, false);
        Disable(warntext);

        localStorage.setItem("username", thisusername);
        return;
      }

      if (data.ok) {
        saveUUID(thisuuid);
      } else {
        if (data.type == "uuid invalid") {
          console.warn(`data.type The ID '${thisuuid}' got an error for ${data.type}`);
          setTimeout(() => {
            trySendUUID(createUniqueID());
          }, 1000);
        } else {
          console.warn(`else The ID '${thisuuid}' got an error for ${data.type}`);
          console.log("error", data);
        }
      }
    })
    .catch((error) => {
      console.error(error);
    });
}

function saveUUID(thisuuid) {
  console.log(`save the id ${thisuuid}`);
  localStorage.setItem("userid", thisuuid);
}

acessButton.onclick = () => {
  let newuuid = loadedid;
  if (loadedid == null) {
    console.log("no localID, creating one");
    newuuid = createUniqueID();
  }
  if (TestIsEmpty(username.value)) return;
  let name = username.value.trim();

  fetch("fetchable/uuid.php", {
    method: "POST",
    body: new URLSearchParams({ type: "saveifneeded", name: name, uuid: newuuid }),
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      console.log(data);
      if (data.ok && data.type == "user login") {
        loadedid = newuuid;
        saveUUID(newuuid);
        localStorage.setItem("username", name);

        Disable(username, false);
        Disable(warntext);
        Disable(acessButton, false);

        setTimeout(() => {
          console.log("redirect");
          window.location.href = `${window.location.origin}/Uno/uno.html`;
        }, 1000);
      }
    })
    .catch((error) => {
      console.error(error);
    });
};
