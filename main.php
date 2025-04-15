<?php
// db connection protocols
$servername = "localhost";
$username = "udvnhgd3sliun";
$password = "32w$)kA$(1x6";
$dbname   = "dbjvgekqfezksk";

// base URL in case links are relative
$baseUrl = "http://www.zackw1.sg-host.com/_final/img/";

// connect to db
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// query to fetch name, description, and link
$sql = "SELECT name, description, link FROM image ORDER BY name ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Interactive Thumbnails with Queue</title>
  <style>
    /* ---------- Thumbnail Section ---------- */
    .thumbnail-container {
      display: inline-block;
      margin: 10px;
      position: relative;
      text-align: center;
      cursor: pointer;
    }
    .thumbnail-container img {
      width: 150px;
      height: auto;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 5px;
      transition: opacity 0.3s;
    }
    .thumbnail-container:hover img {
      opacity: 0.7;
    }
    .thumbnail-container .description-overlay {
      visibility: hidden;
      width: 150px;
      background-color: rgba(0, 0, 0, 0.75);
      color: #fff;
      text-align: center;
      border-radius: 4px;
      padding: 5px;
      position: absolute;
      bottom: 100%;
      left: 50%;
      transform: translateX(-50%);
      opacity: 0;
      transition: opacity 0.3s;
      z-index: 1;
    }
    .thumbnail-container:hover .description-overlay {
      visibility: visible;
      opacity: 1;
    }
    .thumbnail-container .name-caption {
      margin-top: 5px;
      font-weight: bold;
    }
    
    /* ---------- Queue Section ---------- */
    #queue {
      border: 1px solid #ccc;
      padding: 10px;
      margin-top: 40px;
    }
    #queue h2 {
      margin-top: 0;
    }
    #queueItems .queue-item {
      display: inline-block;
      margin: 5px;
      text-align: center;
      position: relative;
      cursor: move;
    }
    #queueItems .queue-item img {
      width: 100px;
      height: auto;
      border: 1px solid #aaa;
      border-radius: 4px;
      padding: 3px;
    }
    #queueItems .queue-item .queue-name {
      margin-top: 3px;
      font-size: 0.9em;
    }
    /* hover overlay for queue item description */
    #queueItems .queue-item .queue-description-overlay {
      visibility: hidden;
      width: 100px;
      background-color: rgba(0, 0, 0, 0.75);
      color: #fff;
      text-align: center;
      border-radius: 4px;
      padding: 5px;
      position: absolute;
      top: 0;
      left: 50%;
      transform: translate(-50%, -100%);
      opacity: 0;
      transition: opacity 0.3s;
      z-index: 1;
    }
    #queueItems .queue-item:hover .queue-description-overlay {
      visibility: visible;
      opacity: 1;
    }
    /* visual feedback when dragging */
    .over {
      outline: 2px dashed #666;
    }
    /* button styling */
    .action-buttons {
      margin-top: 20px;
    }
    .action-buttons button {
      padding: 10px 20px;
      margin-right: 10px;
      font-size: 1em;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <h2>Database Items</h2>
  <div id="thumbnails">
    <?php
    if ($result->num_rows > 0) {
        // loop through each record - output thumbnail container
        while ($row = $result->fetch_assoc()) {
            // process image
            $imgLink = $row['link'];
            if (!filter_var($imgLink, FILTER_VALIDATE_URL)) {
                $imgLink = $baseUrl . $imgLink;
            }
            echo '<div class="thumbnail-container" data-name="' . htmlspecialchars($row['name']) . '" data-description="' . htmlspecialchars($row['description']) . '" data-link="' . htmlspecialchars($imgLink) . '">';
            echo '<img src="' . htmlspecialchars($imgLink) . '" alt="' . htmlspecialchars($row['name']) . '">';
            echo '<div class="description-overlay">' . htmlspecialchars($row['description']) . '</div>';
            echo '<div class="name-caption">' . htmlspecialchars($row['name']) . '</div>';
            echo '</div>';
        }
    } else {
        echo "<p>No items found.</p>";
    }
    $conn->close();
    ?>
  </div>

  <!-- Selection Queue Section -->
  <div id="queue">
    <h2>Selection Queue</h2>
    <div id="queueItems">
      <!-- appended clicked items-->
    </div>
    <div class="action-buttons">
      <button id="clearQueueBtn">Clear Queue</button>
      <button id="saveQueueBtn">Save Queue</button>
    </div>
  </div>

  <script>
    // -------------------- drag/drop fcn's --------------------
    var draggedItem = null;

    function attachDragEvents(item) {
      item.setAttribute('draggable', true);
      item.addEventListener('dragstart', handleDragStart, false);
      item.addEventListener('dragenter', handleDragEnter, false);
      item.addEventListener('dragover', handleDragOver, false);
      item.addEventListener('dragleave', handleDragLeave, false);
      item.addEventListener('drop', handleDrop, false);
      item.addEventListener('dragend', handleDragEnd, false);
    }

    function handleDragStart(e) {
      draggedItem = this;
      this.style.opacity = '0.4';
      e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragOver(e) {
      if (e.preventDefault) {
        e.preventDefault();
      }
      e.dataTransfer.dropEffect = 'move';
      return false;
    }

    function handleDragEnter(e) {
      this.classList.add('over');
    }

    function handleDragLeave(e) {
      this.classList.remove('over');
    }

    function handleDrop(e) {
      if (e.stopPropagation) {
        e.stopPropagation();
      }
      if (draggedItem !== this) {
        // insert dragged item before the drop target
        this.parentNode.insertBefore(draggedItem, this);
      }
      return false;
    }

    function handleDragEnd(e) {
      this.style.opacity = '1';
      // remove visual cue for all queue items
      var items = document.querySelectorAll('#queueItems .queue-item');
      items.forEach(function(item) {
        item.classList.remove('over');
      });
    }

    // -------------------- click handlers--------------------
    document.addEventListener('DOMContentLoaded', function () {
      var thumbnails = document.querySelectorAll('.thumbnail-container');
      var queueItemsContainer = document.getElementById('queueItems');
      var clearQueueBtn = document.getElementById('clearQueueBtn');
      var saveQueueBtn = document.getElementById('saveQueueBtn');

      // add queue item when image is clicked
      thumbnails.forEach(function(thumbnail) {
        thumbnail.addEventListener('click', function() {
          var name = this.getAttribute('data-name');
          var link = this.getAttribute('data-link');
          var description = this.getAttribute('data-description');
          
          // create new queue item elt - store data
          var queueItem = document.createElement('div');
          queueItem.className = 'queue-item';
          queueItem.setAttribute('data-name', name);
          
          // create the image elt for the queue item
          var img = document.createElement('img');
          img.src = link;
          img.alt = name;
          
          // overlay for the queue item
          var overlay = document.createElement('div');
          overlay.className = 'queue-description-overlay';
          overlay.textContent = description;
          
          // caption elt for item name
          var caption = document.createElement('div');
          caption.className = 'queue-name';
          caption.textContent = name;
          
          // append to the queue item
          queueItem.appendChild(img);
          queueItem.appendChild(overlay);
          queueItem.appendChild(caption);
          
          attachDragEvents(queueItem);
          
          queueItemsContainer.appendChild(queueItem);
        });
      });

      // clears entire queue
      clearQueueBtn.addEventListener('click', function() {
        queueItemsContainer.innerHTML = "";
      });

      // save queue button handler - sends data to save_queue.php
      saveQueueBtn.addEventListener('click', function() {
        // save queue items - 'names' only
        var items = [];
        var queueItems = document.querySelectorAll('#queueItems .queue-item');
        queueItems.forEach(function(item) {
          items.push(item.getAttribute('data-name'));
        });

        // no items in queue - alert user
        if (items.length === 0) {
          alert("The queue is empty. Please add items before saving.");
          return;
        }
        
        // name saved_queue
        var queueName = prompt("Enter a name for your selection queue:");
        if (queueName === null || queueName.trim() === "") {
          alert("Queue not saved. A queue name is required.");
          return;
        }
        
        // prep payload
        var payload = {
          queueName: queueName.trim(),
          queue: items
        };

        // send data via AJAX POST request using fetch
        fetch("save_queue.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(payload)
        })
        .then(function(response) {
          return response.json();
        })
        .then(function(data) {
          if (data.success) {
            alert("Queue saved successfully.");
            // clear queue after save
            queueItemsContainer.innerHTML = "";
          } else {
            alert("Error saving queue: " + data.message);
          }
        })
        .catch(function(error) {
          console.error("Error:", error);
          alert("An error occurred while saving the queue.");
        });
      });
    });
  </script>
</body>
</html>