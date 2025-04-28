<?php
require 'config.php';
require_login();

$username = $_SESSION['username'];
$userId   = $_SESSION['user_id'];
$baseUrl  = "https://zackw1.sg-host.com/Project_4/images/";

// get thumbnail items and create target list
$itemStmt = $conn->prepare("
    SELECT english_name, sanskrit_name, link, targets, category
      FROM supported_postures
  ORDER BY english_name ASC
");
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
$targetSet = [];
while ($row = $itemResult->fetch_assoc()) {
    $items[] = $row;
    foreach (explode(',', $row['targets']) as $t) {
        $t = trim($t);
        if ($t && !in_array($t, $targetSet, true)) {
            $targetSet[] = $t;
        }
    }
}
sort($targetSet, SORT_NATURAL | SORT_FLAG_CASE);

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Yoga Pose Selector</title>
  <style>
    /* login */
    #loginBar { font-size: 0.9em; margin-bottom: 20px; }
    #loginBar a { margin-left: 10px; }

    /* saved queues */
    #savedQueuesSection { margin-bottom: 30px; }
    #savedQueuesSection select,
    #savedQueuesSection button {
      padding: 6px 12px; font-size: 1em; margin-right: 8px;
    }

    /* target body part filter */
    #filterSection { margin-bottom: 20px; }
    #filterSection select {
      padding: 6px 12px; font-size: 1em;
    }

    /* selection queue */
    #queue { border: 1px solid #ccc; padding: 10px; margin-top: 20px; }
    #queue h2 { margin-top: 0; }
    .queue-item {
      display: inline-block; margin: 5px;
      text-align: center; position: relative;
      cursor: pointer;
    }
    .queue-item.selected {
      outline: 2px solid #00f;
    }
    .queue-item img {
      width: 100px; border: 1px solid #aaa;
      border-radius: 4px; padding: 3px;
    }
    .queue-name { margin-top: 3px; font-size: 0.9em; }
    .queue-description-overlay {
      visibility: hidden; width: 100px;
      background: rgba(0,0,0,0.75); color: #fff;
      text-align: center; border-radius: 4px; padding: 5px;
      position: absolute; top: 0; left: 50%;
      transform: translate(-50%, -100%); opacity: 0;
      transition: opacity 0.3s; z-index: 1;
    }
    .queue-item:hover .queue-description-overlay {
      visibility: visible; opacity: 1;
    }

    /* remove button */
    .remove-btn {
      position: absolute; top: 2px; right: 2px;
      background: transparent; border: none;
      font-size: 1.2em; line-height: 1;
      cursor: pointer; color: #900;
    }
    .remove-btn:hover { color: #c00; }

    /* action buttons */
    .action-buttons { margin-top: 10px; }
    .action-buttons button {
      padding: 8px 16px; margin-right: 8px;
      font-size: 1em; cursor: pointer;
    }

    /* thumbnails */
    .thumbnail-container {
      display: inline-block; margin: 10px;
      position: relative; text-align: center;
      cursor: pointer;
    }
    .warning {
      width: 50px;
      height: 150px;
      background-color: yellow;
      border: 2px solid #ff0000;
      display: inline-block; 
      margin: 5px; 
    }
    .thumbnail-container img {
      width: 150px; border: 1px solid #ddd;
      border-radius: 4px; padding: 5px;
      transition: opacity 0.3s;
    }
    .thumbnail-container:hover img { opacity: 0.7; }
    .description-overlay {
      visibility: hidden; width: 150px;
      background: rgba(0,0,0,0.75); color: #fff;
      text-align: center; border-radius: 4px; padding: 5px;
      position: absolute; bottom: 100%; left: 50%;
      transform: translateX(-50%); opacity: 0;
      transition: opacity 0.3s; z-index: 1;
    }
    .thumbnail-container:hover .description-overlay {
      visibility: visible; opacity: 1;
    }
    .name-caption { margin-top: 5px; font-weight: bold; }
    h1 {
        text-align: center;
      }
  </style>
</head>
<body>
  <h1> Flow Creator </h1>
  <!-- user login/logout -->
  <div id="loginBar">
    Logged in as <strong><?=htmlspecialchars($username)?></strong>
    <a href="logout.php">Log out</a>
  </div>

  <!-- body part filtering -->
  <div id="filterSection">
    <label for="targetFilter"><strong>Filter by body part:</strong></label>
    <select id="targetFilter">
      <option value="">All</option>
      <?php foreach($targetSet as $t): ?>
        <option value="<?=htmlspecialchars($t)?>">
          <?=htmlspecialchars($t)?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="header_buttons">
    <button id="gemini_flow">Gemini Flow Auto-Generator</button>
  </div>

  <!-- selection queue -->
  <div id="queue">
    <h2>Selection Queue</h2>
    <div id="queueItems"></div>
    <div class="action-buttons">
      <button id="moveLeftBtn">Move Left</button>
      <button id="moveRightBtn">Move Right</button>
      <button id="clearQueueBtn">Clear Queue</button>
      <button id="saveQueueBtn">Save Queue</button>
    </div>
  </div>

  <!-- gallery (thumbnails) -->
  <h2>Available Poses</h2>
  <div id="thumbnails">
    <?php foreach($items as $row):
      $link = $row['link'];
      if (!filter_var($link, FILTER_VALIDATE_URL)) {
        $link = $baseUrl . $link;
      }
    ?>
      <div class="thumbnail-container"
           data-name="<?=htmlspecialchars($row['english_name'])?>"
           data-description="<?=htmlspecialchars($row['sanskrit_name'])?>"
           data-link="<?=htmlspecialchars($link)?>"
           data-targets="<?=htmlspecialchars($row['targets'])?>"
           data-category="<?=htmlspecialchars($row['category'])?>">
        <img src="<?=htmlspecialchars($link)?>" alt="<?=htmlspecialchars($row['english_name'])?>">
        <div class="description-overlay">
          <?=htmlspecialchars($row['sanskrit_name'])?>
        </div>
        <div class="name-caption"><?=htmlspecialchars($row['english_name'])?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const thumbnails       = Array.from(document.querySelectorAll('.thumbnail-container'));
      const queueContainer   = document.getElementById('queueItems');
      const clearBtn         = document.getElementById('clearQueueBtn');
      const saveBtn          = document.getElementById('saveQueueBtn');
      const filterSelect     = document.getElementById('targetFilter');
      const moveLeftBtn      = document.getElementById('moveLeftBtn');
      const moveRightBtn     = document.getElementById('moveRightBtn');
      const queueData        = JSON.parse(sessionStorage.getItem("queueData"));

      let selectedItem = null;

      function queue_changed() {
        console.log("Queue Changed");
        // dict containing all abrupt transitions
        warning_transitions = {
            "Standing": ["Seated", "Prone", "Supine"],
            "Seated": ["Standing"],
            "Kneeling": [],
            "Prone": ["Standing"],
            "Supine": ["Standing"]
        };
        
        // clear queue_warnings between goes
        const queue_warnings = queueContainer.querySelectorAll('.warning');
        queue_warnings.forEach(child => {
            child.remove();
        });

        // get all thumbs and iterate through (create warnings as needed)
        const children = Array.from(queueContainer.children);

        // Use a manual index for iterating through children
        for (let i = 0; i < children.length - 1; i++) {
            console.log("i val: ", i);
            
            // Check if there is an abrupt transition
            if (warning_transitions[children[i].dataset.category].includes(children[i + 1].dataset.category)) {
                console.log("WARNING!!!!!!!!");

                // Create a warning element
                const warningElement = document.createElement('div');
                warningElement.classList.add('warning');
                warningElement.dataset.category = null;

                // Insert the warning element before the next child
                queueContainer.insertBefore(warningElement, children[i + 1]);
            }
        }
      }

      function addQueueItem(name, link, desc, cat) {
        const qi = document.createElement('div');
        qi.className = 'queue-item';
        qi.dataset.name = name;
        qi.dataset.category = cat;

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-btn';
        removeBtn.innerHTML = '&times;';
        removeBtn.title = 'Remove this pose';
        removeBtn.addEventListener('click', e => {
          e.stopPropagation();
          if (qi === selectedItem) selectedItem = null;
          qi.remove();
          queue_changed();
        });

        const img = document.createElement('img');
        img.src = link; img.alt = name;

        const overlay = document.createElement('div');
        overlay.className = 'queue-description-overlay';
        overlay.textContent = desc;

        const cap = document.createElement('div');
        cap.className = 'queue-name';
        cap.textContent = name;

        qi.append(removeBtn, img, overlay, cap);
        queueContainer.appendChild(qi);

        qi.addEventListener('click', () => {
          document.querySelectorAll('.queue-item.selected')
                  .forEach(el => el.classList.remove('selected'));
          qi.classList.add('selected');
          selectedItem = qi;
        });
      }

      // load saved queue
      function load_queue(queueData) {
        if (queueData.id == null) {
          return;
        }
        queueData.queue.forEach(name => {
          const thumb = thumbnails.find(el => el.dataset.name === name);
          if (thumb) {
            addQueueItem(
              thumb.dataset.name,
              thumb.dataset.link,
              thumb.dataset.description,
              thumb.dataset.category
            );
          }
        });
        queue_changed();
      };

      load_queue(queueData);

      // add pose on click
      thumbnails.forEach(t => {
        t.addEventListener('click', () => {
          addQueueItem(
            t.dataset.name,
            t.dataset.link,
            t.dataset.description,
            t.dataset.category
          );
          queue_changed();
        });
      });

      // clear queue
      clearBtn.addEventListener('click', () => {
        queueContainer.innerHTML = '';
        selectedItem = null;
        queue_changed();
      });

      // shift pose left
      moveLeftBtn.addEventListener('click', () => {
        if (!selectedItem) return alert('Select a pose first.');
        const prev = selectedItem.previousElementSibling;
        if (prev) {
          queueContainer.insertBefore(selectedItem, prev);
          queue_changed();
        }
        
      });

      // shift pose right
      moveRightBtn.addEventListener('click', () => {
        if (!selectedItem) return alert('Select a pose first.');
        const next = selectedItem.nextElementSibling;
        if (next){
          queueContainer.insertBefore(next, selectedItem);
          queue_changed();
        } 
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

      // filter selection gallery by body part
      filterSelect.addEventListener('change', () => {
        const filter = filterSelect.value.toLowerCase();
        thumbnails.forEach(t => {
          const targets = t.dataset.targets
                             .split(',')
                             .map(s => s.trim().toLowerCase());
          t.style.display = (!filter || targets.includes(filter)) ? '' : 'none';
        });
      });
    });
    // Gemini button down here
      gemini_flow.addEventListener('click', () => {
        window.location.href = 'gemini_query.html'; 
    });
  </script>
</body>
</html>
