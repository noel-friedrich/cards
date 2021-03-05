<!DOCTYPE html>

<table style="width:100%">
  <tr>
    <th><button type="button" onclick="reset()">Reset</button></th>
    <th><button type="button" onclick="mix()">Mischen</button></th>
    <th><button type="button" onclick="resize(1)">Size +</button></th>
    <th><button type="button" onclick="resize(-1)">Size -</button></th>
  </tr>
</table>

<canvas id="cardsCanvas" style="width:100%  height:100%"></canvas>

<script>

  let size = 0.3

  let oReq = new XMLHttpRequest()
  oReq.open("get", "delete-data.php", true)
  oReq.send()

  let canvas = document.getElementById("cardsCanvas")
  let context = canvas.getContext("2d")
  let max_layer = 0;
  canvas.width  = window.innerWidth - 100
  canvas.height = window.innerHeight - 100
  images = []
  items = ["clubs", "diamonds", "hearts", "spades"]
  cardnums = ["2","3","4","5","6","7","8","9","10","A","K","J","Q"]
  cards = []
  items.forEach(function(item){
    cardnums.forEach(function(card){
      let img = new Image()
      img.src = "https://www.die-quote.de/wp-content/uploads/2021/" + item + "/" + card
      images.push(img)
    })
  })
  desiredPos = []
  let a_difference = 0;
  let b_difference = 0;
  let ab_step = 5;
  let setId = false;
  let countc = 0;
  images.forEach(function(image) {
    let oReq = new XMLHttpRequest()
    let a = Math.round(1000 / 2 - (image.width * size) + a_difference - (ab_step * (images.length / 2)))
    let b = Math.round(1000 / 2 - (image.height * size) + b_difference - (ab_step * (images.length / 2)))
    a_difference += ab_step
    b_difference += ab_step
    oReq.open("get", "insert-data.php?x=" + a + "&y=" + b + "&l=" + countc , true)
    oReq.send()
    cards.push({x:a, y:b, img:image, layer:0, id:(countc+1)})
    desiredPos.push({x:0, y:0})
    countc++
  })
  sqlData = []
  mouseState = {up:true, down:false, dragging:false, draggingId:0, draggingElementId:0}
  relativePos = {x:0, y:0}
  let requestfinished = false

  let tickCount = 0

  const interval = setInterval(function() {
    if (!mouseState.dragging) {
      tickCount++
      context.clearRect(0, 0, canvas.width, canvas.height)
      context.fillStyle = "#DDDDDD"
      context.fillRect(0,0,canvas.width,canvas.height)
      if (tickCount % 10 == 0) {
        let oReq = new XMLHttpRequest()
        oReq.onload = function() {
          sqlData = this.responseText.split(",")
        }
        oReq.open("get", "get-data.php", true)
        oReq.send()
      }
      if ((sqlData.length - 1) == cards.length) {
        for (let i = 0; i < cards.length; i++) {
          desiredPos[i].x = sqlData[i].split("-")[0]
          desiredPos[i].y = sqlData[i].split("-")[1]
          cards[i].layer = sqlData[i].split("-")[2]
          cards[i].id = parseInt(sqlData[i].split("-")[3]) - 1
          let x_velocity = Math.ceil(Math.abs(desiredPos[i].x - cards[i].x) / 30)
          let y_velocity = Math.ceil(Math.abs(desiredPos[i].y - cards[i].y) / 30)
          if (desiredPos[i].x > cards[i].x) cards[i].x += x_velocity
          if (desiredPos[i].x < cards[i].x) cards[i].x -= x_velocity
          if (desiredPos[i].y > cards[i].y) cards[i].y += y_velocity
          if (desiredPos[i].y < cards[i].y) cards[i].y -= y_velocity
        }
        let s = getSortedCards()
        for (let i = 0; i < cards.length; i++) {
          context.drawImage(
            s[i].img,
            s[i].x * (canvas.width / 1000),
            s[i].y * (canvas.height / 1000),
            s[i].img.width * size,
            s[i].img.height * size
          )
        }
      }
    }
  }, 10)

  function get_max_layer() {
    let max_layer = 0
    for (let i = 0; i < cards.length; i++) {
      if (cards[i].layer > max_layer)
        max_layer = parseInt(cards[i].layer)
    }
    return max_layer
  }

  function getSortedCards() {
    let sortedCards = cards.slice()
    for (let t = 0; t < cards.length-1; t++) {
      for (let i = 0; i < cards.length-1; i++) {
        if (parseInt(sortedCards[i].layer) > parseInt(sortedCards[i+1].layer)) {
          let temp = sortedCards[i]
          sortedCards[i] = sortedCards[i+1]
          sortedCards[i+1] = temp
        }
      }
    }
    return sortedCards
  }

  function get_i(x,y) {
    for (let i = 0; i < cards.length; i++) {
      if (cards[i].x == x && cards[i].y == y) {
        return i
      }
    }
  }

  function check_card_collision(e) {
    let s = getSortedCards()
    let general_collison = false;
    let rect = canvas.getBoundingClientRect();
    for (let i = 0; i < cards.length; i++) {
      let ri = (cards.length - 1) - i
      mx = e.clientX - rect.left;
      my = e.clientY - rect.top;
      ex = s[ri].x * (canvas.width / 1000)
      ey = s[ri].y * (canvas.height / 1000)
      ew = s[ri].img.width * size
      eh = s[ri].img.height * size
      let collision = mx >= ex && mx <= ex+ew && my >= ey && my <= ey+eh
      if (collision) {
        general_collison = true
      }
      if (collision && mouseState.down && !mouseState.dragging) {
          relativePos.x = mx - ex
          relativePos.y = my - ey
          mouseState.dragging = true
          mouseState.draggingId = get_i(s[ri].x, s[ri].y)
          return general_collison
      }
    }
    return general_collison
  }

  canvas.addEventListener("mousemove", function(e) {
    let rect = canvas.getBoundingClientRect();
    let general_collison = check_card_collision(e)
    if (general_collison) {
      canvas.style.cursor = "move"
    } else {
      canvas.style.cursor = "default"
    }
    if (mouseState.dragging) {
      cards[mouseState.draggingId].x = Math.round((mx - relativePos.x) * (1000 / canvas.width))
      cards[mouseState.draggingId].y = Math.round((my - relativePos.y) * (1000 / canvas.height))
      context.clearRect(0, 0, canvas.width, canvas.height)
      context.fillStyle = "#DDDDDD"
      context.fillRect(0,0,canvas.width,canvas.height)
      let s = getSortedCards()
      for (let i = 0; i < cards.length; i++) {
        context.drawImage(
          s[i].img,
          s[i].x * (canvas.width / 1000),
          s[i].y * (canvas.height / 1000),
          s[i].img.width * size,
          s[i].img.height * size
        )
      }
      context.fillStyle = '#99FF99';
      context.beginPath();
      let i = mouseState.draggingId
      let b = cards.slice()
      let w = 5
      context.moveTo(b[i].x * (canvas.width / 1000)-w, b[i].y * (canvas.height / 1000)-w);
      context.lineTo(b[i].x * (canvas.width / 1000)+b[i].img.width * size+w,b[i].y * (canvas.height / 1000)-w);
      context.lineTo(b[i].x * (canvas.width / 1000)+b[i].img.width * size+w, b[i].y * (canvas.height / 1000)+b[i].img.height * size+w);
      context.lineTo(b[i].x * (canvas.width / 1000)-w, b[i].y * (canvas.height / 1000)+b[i].img.height * size+w);
      context.closePath();
      context.fill();
      context.drawImage(
        cards[mouseState.draggingId].img,
        cards[mouseState.draggingId].x * (canvas.width / 1000),
        cards[mouseState.draggingId].y * (canvas.height / 1000),
        cards[mouseState.draggingId].img.width * size,
        cards[mouseState.draggingId].img.height * size
      )
    }
  }, false)

  canvas.addEventListener("mousedown", function(e) {
    mouseState.down = true
    mouseState.up = false
  }, false)
  canvas.addEventListener("mouseup", function(e) {
    mouseState.up = true
    mouseState.down = false
    let rect = canvas.getBoundingClientRect();
    mx = e.clientX - rect.left;
    my = e.clientY - rect.top;
    let x = Math.round((mx - relativePos.x) * (1000 / canvas.width))
    let y = Math.round((my - relativePos.y) * (1000 / canvas.height))
    if (mouseState.dragging) {
      let oReq = new XMLHttpRequest()
      let new_layer = get_max_layer() + 1
      oReq.open("get", "set-data.php?x=" + x + "&y=" + y + "&i=" + (mouseState.draggingId + 1) + "&l=" + new_layer, true)
      oReq.send()
      mouseState.dragging = false
      console.log("MOVED #"+mouseState.draggingId,"TO",x,y)
      cards[mouseState.draggingId].layer = new_layer
      sqlData[mouseState.draggingId] = x + "-" + y + "-" + new_layer + "-" + (mouseState.draggingId+1)
    }
  }, false)

  function reset() {
    let a_difference = 0
    let b_difference = 0
    let ab_step = 5
    for (let i = 0; i < cards.length; i++) {
      let oReq = new XMLHttpRequest()
      let x = Math.round(1000 / 2 - (cards[i].img.width * size) + a_difference - (ab_step * (cards.length / 2)))
      let y = Math.round(1000 / 2 - (cards[i].img.height * size) + b_difference - (ab_step * (cards.length / 2)))
      oReq.open("get", "set-data.php?x=" + x + "&y=" + y + "&i=" + (i+1) + "&l="+i, true)
      oReq.send()
      a_difference += ab_step
      b_difference += ab_step
    }
  }

  function resize(i) {
    size += i / 20
  }

  function mix() {
    for (let i = 0; i < cards.length; i++) {
      let oReq = new XMLHttpRequest()
      let x = Math.round(Math.random() * 600 + 200 - cards[i].img.width * size)
      let y = Math.round(Math.random() * 600 + 200 - cards[i].img.height * size)
      oReq.open("get", "set-data.php?x=" + x + "&y=" + y + "&i=" + (i+1) + "&l=0", true)
      oReq.send()
    }
  }

</script>
