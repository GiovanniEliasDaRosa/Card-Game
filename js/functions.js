const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
if (isMobile) {
  document.body.setAttribute("data-mobile", "");
}

/* Enable & Disable elements */
function Enable(element) {
  element.removeAttribute("aria-disabled");
  element.removeAttribute("disabled");
  element.style.display = "";
}

function Disable(element, hide = true) {
  element.setAttribute("aria-disabled", "true");
  element.setAttribute("disabled", "");
  if (hide) {
    element.style.display = "none";
  }
}

/* Math Stuff */
function RandomNumber(min, max) {
  return Math.floor(Math.random() * (max - min) + min);
}

function Wrap(number, min, max) {
  let result = number;
  if (number > max) {
    result = min;
  } else if (number < min) {
    result = max;
  }
  return result;
}

function Inside(number, min, max) {
  if (number > max) {
    return false;
  } else if (number < min) {
    return false;
  }
  return true;
}

/* Basic functions */
function TestIsEmpty(text) {
  return text.trim().length == 0;
}

function Clamp(number, min, max) {
  return Math.max(min, Math.min(number, max));
}

/* Custom */
function CustomLog(text) {
  console.log(
    `%c${text}`,
    "padding: 1em; border: solid 0.1em hsla(0, 0%, 100%, 0.5); border-radius: 0.5em; background: hsla(0, 50%, 50%, 0.5)"
  );
}
