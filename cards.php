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

  var size = 0.3

  var oReq = new XMLHttpRequest()
  oReq.open("get", "delete-data.php", true)
  oReq.send()

  var canvas = document.getElementById("cardsCanvas")
  var context = canvas.getContext("2d")
  var max_layer = 0;
  canvas.width  = window.innerWidth
  canvas.height = window.innerHeight
  images = []
  items = ["clubs", "diamonds", "hearts", "spades"]
  cardnums = ["2","3","4","5","6","7","8","9","10","A","K","J","Q"]
  cards = []
  items.forEach(function(item){
    cardnums.forEach(function(card){
      var img = new Image()
      img.src = "https://www.die-quote.de/wp-content/uploads/2021/" + item + "/" + card
      images.push(img)
    })
  })
  desiredPos = []
  var a_difference = 0;
  var b_difference = 0;
  var ab_step = 5;
  images.forEach(function(image) {
    var oReq = new XMLHttpRequest()
    var a = Math.round(1000 / 2 - (image.width * size) + a_difference - (ab_step * (images.length / 2)))
    var b = Math.round(1000 / 2 - (image.height * size) + b_difference - (ab_step * (images.length / 2)))
    a_difference += ab_step
    b_difference += ab_step
    oReq.open("get", "insert-data.php?x=" + a + "&y=" + b + "&l=" + 0 , true)
    oReq.send()
    cards.push({x:a, y:b, img:image, layer:0})
    desiredPos.push({x:0, y:0})
  })
  sqlData = []
  mouseState = {up:true, down:false, dragging:false, draggingId:0}
  relativePos = {x:0, y:0}
  var requestfinished = false

  var tickCount = 0

  const interval = setInterval(function() {
    if (mouseState.up) {
      tickCount++
      context.clearRect(0, 0, canvas.width, canvas.height)
      context.fillStyle = "#DDDDDD"
      context.fillRect(0,0,canvas.width,canvas.height)
      if (tickCount % 10 == 0) {
        var oReq = new XMLHttpRequest()
        oReq.onload = function() {
          sqlData = this.responseText.split(",")
        }
        oReq.open("get", "get-data.php?id="+i, true)
        oReq.send()
      }
      if ((sqlData.length - 1) == cards.length) {
        for (var i = 0; i < cards.length; i++) {
          desiredPos[i].x = sqlData[i].split("-")[0]
          desiredPos[i].y = sqlData[i].split("-")[1]
          cards[i].layer = sqlData[i].split("-")[2]
          var x_velocity = Math.ceil(Math.abs(desiredPos[i].x - cards[i].x) / 30)
          var y_velocity = Math.ceil(Math.abs(desiredPos[i].y - cards[i].y) / 30)
          if (desiredPos[i].x > cards[i].x) cards[i].x += x_velocity
          if (desiredPos[i].x < cards[i].x) cards[i].x -= x_velocity
          if (desiredPos[i].y > cards[i].y) cards[i].y += y_velocity
          if (desiredPos[i].y < cards[i].y) cards[i].y -= y_velocity
        }
        var sorted = getSortedCards()
        for (var i = 0; i < sorted.length; i++) {
          context.drawImage(
            sorted[i].img,
            sorted[i].x * (canvas.width / 1000),
            sorted[i].y * (canvas.height / 1000),
            sorted[i].img.width * size,
            sorted[i].img.height * size
          )
        }
      }
    }
  }, 10)

  function getSortedCards() {
    var limit = cards.length
    sortedCards = cards.slice()
    while (limit > 0) {
      for (var i = 0; i < limit - 1; i++) {
        if (sortedCards[i].layer > sortedCards[i+1].layer) {
          var temp = sortedCards[i]
          sortedCards[i] = sortedCards[i+1]
          sortedCards[i+1] = temp
        }
      }
      limit--
    }
    max_layer = sortedCards[sortedCards.length-1].layer
    return sortedCards
  }

  canvas.addEventListener("mousemove", function(e) {
    cards.reverse()
    for (var i = 0; i < cards.length; i++) {
      mx = e.clientX
      my = e.clientY
      ex = Math.round(cards[i].x * (canvas.width / 1000))
      ey = Math.round(cards[i].y * (canvas.height / 1000))
      ew = cards[i].img.width * size
      eh = cards[i].img.height * size
      if (mx >= ex && mx <= ex+ew && my >= ey && my <= ey+eh && mouseState.down && !mouseState.dragging) {
          relativePos.x = mx - ex
          relativePos.y = my - ey
          mouseState.dragging = true
          mouseState.draggingId = (cards.length - 1) - i
          break
      }
    }
    cards.reverse()
    if (mouseState.dragging) {
      cards[mouseState.draggingId].x = Math.round((mx - relativePos.x) * (1000 / canvas.width))
      cards[mouseState.draggingId].y = Math.round((my - relativePos.y) * (1000 / canvas.height))
      context.clearRect(0, 0, canvas.width, canvas.height)
      context.fillStyle = "#DDDDDD"
      context.fillRect(0,0,canvas.width,canvas.height)
      var sorted = getSortedCards()
      for (var i = 0; i < sorted.length; i++) {
        context.drawImage(
          sorted[i].img,
          sorted[i].x * (canvas.width / 1000),
          sorted[i].y * (canvas.height / 1000),
          sorted[i].img.width * size,
          sorted[i].img.height * size
        )
      }
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
    var x = Math.round((e.clientX - relativePos.x) * (1000 / canvas.width))
    var y = Math.round((e.clientY - relativePos.y) * (1000 / canvas.height))
    if (mouseState.dragging) {
      var oReq = new XMLHttpRequest()
      var new_layer = parseInt(max_layer) + 1
      oReq.open("get", "set-data.php?x=" + x + "&y=" + y + "&i=" + (mouseState.draggingId + 1) + "&l=" + new_layer, true)
      oReq.send()
      mouseState.dragging = false
    }
    console.log("MOVED #"+mouseState.draggingId,"TO",x,y)
    cards[mouseState.draggingId].layer = max_layer + 1
    sqlData[mouseState.draggingId] = x + "-" + y
  }, false)

  function reset() {
    var a_difference = 0;
    var b_difference = 0;
    var ab_step = 5;
    for (var i = 0; i < cards.length; i++) {
      var oReq = new XMLHttpRequest()
      var x = Math.round(1000 / 2 - (cards[i].img.width * size) + a_difference - (ab_step * (cards.length / 2)))
      var y = Math.round(1000 / 2 - (cards[i].img.height * size) + b_difference - (ab_step * (cards.length / 2)))
      oReq.open("get", "set-data.php?x=" + x + "&y=" + y + "&i=" + (i+1) + "&l=0", true)
      oReq.send()
      a_difference += ab_step
      b_difference += ab_step
    }
  }

  function resize(i) {
    size += i / 20
  }

  function mix() {
    for (var i = 0; i < cards.length; i++) {
      var oReq = new XMLHttpRequest()
      var x = Math.round(Math.random() * 600 + 200 - cards[i].img.width * size)
      var y = Math.round(Math.random() * 600 + 200 - cards[i].img.height * size)
      oReq.open("get", "set-data.php?x=" + x + "&y=" + y + "&i=" + (i+1) + "&l=0", true)
      oReq.send()
    }
  }

</script>
