function updateHandCards(getcardcountvalue) {
  let currentTableCard = currenttablecard.children[0];
  let handCards = [...hand.children];
  let allotherplayers = [];

  [...otherplayers.children].forEach((player) => {
    let data = player.dataset;
    allotherplayers.push([data.user, data.cards]);
  });

  let getcardcountamount = getcardcountvalue;

  // CHANGE, for a better system
  // wild, black, wild ||| after selecting color || wild, green, wild
  // draw2, yellow, draw2
  // reverse, green, reverse
  let tableCardValue = currentTableCard.dataset.value;
  let tableCardColor = currentTableCard.classList[1];
  let activatedcards = handCards.length;

  for (let i = 0; i < handCards.length; i++) {
    const card = handCards[i];
    Disable(card, false);

    if (!thisUserTurn) continue;

    const cardValue = card.dataset.value;
    const cardColor = card.classList[1];

    if (cardValue == "wild" || cardValue == "wilddrawfour") {
      Enable(card);
    }

    if (tableCardValue == "wild") {
      if (tableCardColor == card.classList[1]) {
        Enable(card);
      }
    } else if (
      tableCardValue == "draw2" ||
      tableCardValue == "skip" ||
      tableCardValue == "reverse"
    ) {
      if (getcardcountamount > 0) {
        if (cardValue == tableCardValue) {
          Enable(card);
        }
      } else {
        if (tableCardValue == cardValue || tableCardColor == cardColor) {
          Enable(card);
        }
      }
    } else if (tableCardValue == "wilddraw4") {
      if (tableCardColor == cardColor) {
        Enable(card);
      }
    } else if (tableCardValue == "draw2" && cardValue == "draw2") {
      Enable(card);
    } else if (tableCardColor == cardColor || tableCardValue == cardValue) {
      Enable(card);
    } else {
      continue;
    }

    activatedcards--;
  }

  // the user will have to getcard
  if (activatedcards == handCards.length) {
    getMoreCard.focus();
  }

  // console.log(cardOnTable, handCards, allotherplayers, getcardcountamount);
  // otherplayers;
  // getcardcount;
  //
}
