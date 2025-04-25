<?php
require 'config.php';
require_login();
$username = $_SESSION['username'];
$userId   = $_SESSION['user_id'];

$baseUrl = "https://zackw1.sg-host.com/chunk/images/";

// get thumbnail items
$itemStmt = $conn->prepare("
    SELECT name, description, link
      FROM image
  ORDER BY name ASC
");
$itemStmt->execute();
$itemsResult = $itemStmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Thumbnail Queue Manager</title>
  <style>
    /*  login  */
    #loginBar {
      font-size: 0.9em;
      margin-bottom: 20px;
    }
    #loginBar a { margin-left: 10px; }

    /*  saved queue selection  */
    #savedQueuesSection {
      margin-bottom: 30px;
    }
    #savedQueuesSection select,
    #savedQueuesSection button {
      padding: 6px 12px;
      font-size: 1em;
      margin-right: 8px;
    }

    /*  thumbnails  */
    .thumbnail-container {
      display: inline-block;
      margin: 10px;
      position: relative;
      text-align: center;
      cursor: pointer;
    }
    .thumbnail-container img {
      width: 150px;
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
      background: rgba(0,0,0,0.75);
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

    /* selection queue */
    #queue {
      border: 1px solid #ccc;
      padding: 10px;
      margin-top: 40px;
    }
    #queue h2 {
      margin-top: 0;
    }
    .queue-item {
      display: inline-block;
      margin: 5px;
      text-align: center;
      position: relative;
      cursor: move;
    }
    .queue-item img {
      width: 100px;
      border: 1px solid #aaa;
      border-radius: 4px;
      padding: 3px;
    }
    .queue-item .queue-name {
      margin-top: 3px;
      font-size: 0.9em;
    }
    .queue-item .queue-description-overlay {
      visibility: hidden;
      width: 100px;
      background: rgba(0,0,0,0.75);
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
    .queue-item:hover .queue-description-overlay {
      visibility: visible;
      opacity: 1;
    }
    .over {
      outline: 2px dashed #666;
    }

    /* buttons */
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
  <!-- login/logout -->
  <div id="loginBar">
    Logged in as <strong><?=htmlspecialchars($username)?></strong>
    <a href="logout.php">Log out</a>
  </div>

  <!-- gallery of thumbnails -->
  <h2>Available Items</h2>
  <div id="thumbnails">
    <?php while ($row = $itemsResult->fetch_assoc()):
      $link = $row['link'];
      if (!filter_var($link, FILTER_VALIDATE_URL)) {
        $link = $baseUrl . $link;
      }
    ?>
      <div class="thumbnail-container"
           data-name="<?=htmlspecialchars($row['name'])?>"
           data-description="<?=htmlspecialchars($row['description'])?>"
           data-link="<?=htmlspecialchars($link)?>">
        <img src="<?=htmlspecialchars($link)?>" alt="<?=htmlspecialchars($row['name'])?>">
        <div class="description-overlay">
          <?=htmlspecialchars($row['description'])?>
        </div>
        <div class="name-caption">
          <?=htmlspecialchars($row['name'])?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

  <!-- selection queue -->
  <div id="queue">
    <h2>Selection Queue</h2>
    <div id="queueItems"></div>
    <div class="action-buttons">
      <button id="clearQueueBtn">Clear Queue</button>
      <button id="resetQueueBtn">Reset Queue</button>
      <button id="saveQueueBtn">Save Queue</button>
    </div>
  </div>

  <script>
    // drag & drop functionality
    let draggedItem = null;
    function attachDragEvents(item) {
      item.draggable = true;
      item.addEventListener('dragstart', e => {
        draggedItem = item;
        item.style.opacity = '0.4';
        e.dataTransfer.effectAllowed = 'move';
      });
      item.addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
      });
      item.addEventListener('dragenter', () => item.classList.add('over'));
      item.addEventListener('dragleave', () => item.classList.remove('over'));
      item.addEventListener('drop', () => {
        if (draggedItem && draggedItem !== item) {
          item.parentNode.insertBefore(draggedItem, item);
        }
        item.classList.remove('over');
      });
      item.addEventListener('dragend', () => {
        item.style.opacity = '1';
        document.querySelectorAll('.queue-item.over')
                .forEach(el => el.classList.remove('over'));
      });
    }

    // app logic
    document.addEventListener('DOMContentLoaded', () => {
      const thumbnails     = document.querySelectorAll('.thumbnail-container');
      const queueContainer = document.getElementById('queueItems');
      const clearBtn       = document.getElementById('clearQueueBtn');
      const saveBtn        = document.getElementById('saveQueueBtn');
      const resetBtn       = document.getElementById('resetQueueBtn');
      const queueData      = JSON.parse(sessionStorage.getItem("queueData"));

      function addQueueItem(name, link, desc) {
        const qi = document.createElement('div');
        qi.className = 'queue-item';
        qi.dataset.name = name;

        const img = document.createElement('img');
        img.src = link; img.alt = name;

        const overlay = document.createElement('div');
        overlay.className = 'queue-description-overlay';
        overlay.textContent = desc;

        const cap = document.createElement('div');
        cap.className = 'queue-name';
        cap.textContent = name;

        qi.append(img, overlay, cap);
        attachDragEvents(qi);
        queueContainer.appendChild(qi);
      }

      function display_queue(q_obj) {
        queueContainer.innerHTML = '';
        if (!q_obj.queue) return;
        q_obj.queue.forEach(name => {
          const thumb = Array.from(thumbnails)
                              .find(el => el.dataset.name === name);
          if (thumb) {
            addQueueItem(thumb.dataset.name,
                          thumb.dataset.link,
                          thumb.dataset.description);
          }
        });
      }
      display_queue(queueData)

      // build new queue via thumbnail selection
      thumbnails.forEach(t => {
        t.addEventListener('click', () => {
          addQueueItem(t.dataset.name, t.dataset.link, t.dataset.description);
        });
      });

      // clear queue
      clearBtn.addEventListener('click', () => {
        queueContainer.innerHTML = '';
      });

      // reset queue
      resetBtn.addEventListener('click', () => {
        display_queue(queueData)
      });

      // save queue
      saveBtn.addEventListener('click', () => {
        const names = Array.from(queueContainer.querySelectorAll('.queue-item'))
                           .map(el => el.dataset.name);
        if (!names.length) return alert('Queue is empty.');
        var qn;
        if (!queueData.queueName) {
          qn = prompt('Name this queue:');
          if (!qn || !qn.trim()) return alert('A name is required.');
        } else {
          qn = queueData.queueName;
        }
        
        fetch('save_queue.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ queueName: qn.trim(), queue: names })
        })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            alert('Saved!');
            queueContainer.innerHTML = '';
            window.location.href = "home.php";
          } else {
            alert('Error: ' + d.message);
          }
        })
        .catch(e => { console.error(e); alert('Save failed.'); });
      });
    });
  </script>
</body>
</html>