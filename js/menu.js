const acessButton = document.querySelector("#acessButton");
const username = document.querySelector("#username");
acessButton.onclick = (e) => {
  if (TestIsEmpty(username.value)) {
    e.preventDefault();
    return;
  }
  Enable(username);
  return;
};
