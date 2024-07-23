const dumpper__div = document.querySelectorAll(".dumpper__div");
const dumppers = document.querySelectorAll(".dumpper");
// quote: /(\"|\')/gm,

// ([0-9]?!\")
// number: /((?<!\qt)\d+)/gm,
// number: /([0-9])/gm,
// (^(#\s?)([^#\s].*)$)/

// Select line that starts with O
// ^o.*

// quote: /((\"|\')(.*?)(\"|\'))/gm,

const simple = {
  number: /(\d+)/gm,
  pare: /(\{|\})/gm,
  curl: /(\(|\))/gm,
  square: /(\[|\])/gm,
  arrow: /(=(>|&gt;))/gm,
  somes: /(array|string|int|object|mysqli_result)/gm,
  other: /(mysqli|NULL|_sql_exception|private|protected|SensitiveParameterValue)/gm,
};

function FormatCode(text) {
  let result = text;
  result = result.replace(/\"/g, "&quot;").replace(/\'/g, "&apos;").replace(/\`/g, "&grave;");
  result = result.replace(/\ /g, "&nbsp;");

  Object.keys(simple).forEach(function (key) {
    result = result.replace(simple[key], `<span class='${key}'>$1</span>`);
  });

  result = result
    .replace(/(&quot;(.*?)&quot;)/gm, `<span class='quote qt1'>$1</span>`)
    .replace(/(&apos;(.*?)&apos;)/gm, `<span class='quote qt2'>$1</span>`)
    .replace(/(&grave;(.*?)&grave;)/gm, `<span class='quote qt3'>$1</span>`);

  result = result.replace(/(=&gt;)/gm, `⇒`);
  return result;
}

dumppers.forEach((dumpper) => {
  dumpper.innerHTML = FormatCode(dumpper.innerHTML);
});

// dumppers.forEach((dumpper) => {
//   let text = dumpper.innerHTML;
//   // text = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
//   text = text.replace(/\"/g, "&quot;").replace(/\'/g, "&apos;").replace(/\`/g, "&grave;");
//   text = text.replace(/\ /g, "&nbsp;");

//   // let delay = 0;

//   Object.keys(simple).forEach(function (key) {
//     text = text.replace(simple[key], `<span class='${key}'>$1</span>`);
//   });

//   text = text.replace(/(&quot;(.*?)&quot;)/gm, `<span class='quote qt1'>$1</span>`);
//   text = text.replace(/(&apos;(.*?)&apos;)/gm, `<span class='quote qt2'>$1</span>`);
//   text = text.replace(/(&grave;(.*?)&grave;)/gm, `<span class='quote qt3'>$1</span>`);

//   text = text.replace(/(=&gt;)/gm, `⇒`);

//   // console.log(text);

//   // text = text.replace(/\"/gm, `<span>`);
//   // text = text.replace(/(\((.*?)\))/gm, `<span class='number'>$1</span>`);

//   dumpper.innerHTML = text;
// });

function FormatPath(input) {
  const pattern = /^(.+?)\\([^\\]+) \| (\d+)$/;
  const match = input.match(pattern);

  if (match) {
    const [, directoryPath, filename, number] = match;
    const formattedOutput = `<span class='path'>${directoryPath}</span>\\${filename} | ${number}`;
    return formattedOutput;
  }

  console.warn("Invalid input format. Please provide a valid path.");
  return input;
}

var text = "";

dumpper__div.forEach((item) => {
  let animating = false;
  let dumpper__header = item.querySelector(".dumpper__header");
  let dumpper__content = item.querySelector(".dumpper");

  dumpper__header.innerHTML = FormatPath(dumpper__header.innerHTML);

  dumpper__header.innerHTML = dumpper__header.innerHTML
    .replace(/\\/g, "<span class='slash'>/</span>")
    .replace(/\d+/g, "<span class='number'>$&</span>");

  let savedHeight = dumpper__content.getBoundingClientRect().height;
  let savedDumpContent = dumpper__content.innerHTML;

  let text = dumpper__content.textContent;
  let splitText = text.split("\n");
  let first = splitText[0];
  let last = splitText[splitText.length - 2];

  let simplified = FormatCode(`${first}
  ...
${last}`);

  simplified = simplified.replace(/\.\.\./gm, `<span class='dots'>$&</span>`);

  dumpper__content.innerHTML = simplified;
  let savedSimplifiedHeight = dumpper__content.getBoundingClientRect().height;

  dumpper__content.innerHTML = savedDumpContent;

  dumpper__header.addEventListener("click", () => {
    if (animating) {
      return;
    }
    animating = true;

    let target = savedHeight;
    let targetedDump = savedDumpContent;

    if (item.dataset.closed != null) {
      item.removeAttribute("data-closed");
      targetedDump = savedDumpContent;
      // dumpper__content.style.display = "";
    } else {
      item.setAttribute("data-closed", "");
      targetedDump = simplified;
      target = savedSimplifiedHeight;
      // dumpper__content.style = "display: none !important";
    }

    dumpper__content.style.height = `${savedHeight}px`;
    // dumpper__content.innerHTML = simplified;
    dumpper__content.innerHTML = "";

    setTimeout(() => {
      dumpper__content.style.height = `${target}px`;
      setTimeout(() => {
        dumpper__content.innerHTML = targetedDump;
        animating = false;
      }, 500);
    }, 10);

    // dumpper__content.animate(
    //   {
    //     height: [`${oldHeight}px`, `${target}px`],
    //   },
    //   {
    //     fill: "both",
    //     duration: 500,
    //   }
    // );
  });
});
