<?php
require 'config.php';
require_login();

$username = $_SESSION['username'];
$userId   = $_SESSION['user_id'];
$baseUrl  = "https://zackw1.sg-host.com/Project_4/images/";

// get thumbnail items and create target list
$itemStmt = $conn->prepare("
    SELECT english_name, sanskrit_name, link, targets, category, procedures
      FROM supported_postures
  ORDER BY english_name ASC
");
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

$items = [];
$targetSet = [];
$catSet = [];
while ($row = $itemResult->fetch_assoc()) {
    $items[] = $row;
    foreach (explode(',', $row['targets']) as $t) {
        $t = trim($t);
        if ($t && !in_array($t, $targetSet, true)) {
            $targetSet[] = $t;
        }
    }

    foreach (explode(',', $row['category']) as $t) {
      $t = trim($t);
      if ($t && !in_array($t, $catSet, true)) {
          $catSet[] = $t;
      }
  }
}
sort($targetSet, SORT_NATURAL | SORT_FLAG_CASE);
sort($catSet, SORT_NATURAL | SORT_FLAG_CASE);

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Yoga Flow Generator</title>
  <style>
    h1 {
      text-align: center;
      font-size: 36px;
      margin-top: 20px;
      color: white;
    }
    
    h2 {
      background: white;
      padding: 12px 20px;
      border-radius: 12px;
      text-align: center;
      font-size: 24px;
      color: #333;
      margin: 20px auto;
      max-width: 400px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    body {
      background: url('./images/yoga_mat.jpg') no-repeat center center fixed; 
      background-size: cover;
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
    }
    
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
  .available_poses_container {
    background: white;
    padding: 30px;
    margin: 30px auto 0 auto;
    border-radius: 15px;
    max-width: 1200px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    text-align: center;
  }

  .available_poses_container h2 {
    font-size: 28px;
    margin-bottom: 20px;
    color: #333;
  }

  #filterSection {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
  }

  #filterSection input {
    padding: 10px 16px;
    font-size: 16px;
    border-radius: 25px;
    border: 1px solid #ccc;
    cursor: pointer;
    transition: border-color 0.3s;
  }

  #filterSection label {
    font-size: 18px;
    color: #333;
    margin: 0;
  }

  #filterSection select {
    padding: 10px 16px;
    font-size: 16px;
    border-radius: 25px;
    border: 1px solid #ccc;
    cursor: pointer;
    transition: border-color 0.3s;
  }

  #filterSection select:hover {
    border-color: #6c63ff;
  }

/* selection queue */
#queue {
    background: white;
    padding: 20px;
    margin: 30px auto;
    max-width: 1200px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  #queue h2 {
    text-align: center;
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
  }

  .queue-item {
    background: #f9f9f9;
    padding: 10px;
    border-radius: 10px;
    margin: 5px;
    display: inline-block;
    text-align: center;
    position: relative;
    transition: transform 0.2s;
  }

  .queue-item:hover {
    transform: scale(1.05);
  }

  .queue-item img {
    width: 100px;
    border: none;
    border-radius: 8px;
    padding: 5px;
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
  #thumbnails {
    display: grid;
    grid-template-columns: repeat(5, 1fr); /* 5 items per row */
    gap: 15px;
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
  }

  .thumbnail-container {
    background: white;
    padding: 15px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: transform 0.2s;
  }

  .thumbnail-container:hover {
    transform: scale(1.05);
  }

  .thumbnail-container img {
    width: 100%;
    max-width: 120px;
    border: none;
    border-radius: 8px;
    padding: 5px;
    transition: opacity 0.3s;
  }

  .thumbnail-container:hover img {
    opacity: 0.8;
  }

  .name-caption {
    margin-top: 10px;
    font-weight: bold;
    font-size: 14px;
    color: #333;
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
  /* Button groups (Gemini Auto Generator, Move Left, Move Right, etc.) */
  
  .button_group {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    gap: 20px;
  }

  .button_group button {
    padding: 12px 24px;
    border: none;
    border-radius: 25px;
    background-color: #6c63ff;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
  }

  .button_group button:hover {
    background-color: #5751d9;
  }

  /* New top header container */
  .top-header {
    display: flex;
    align-items: center;
    justify-content: center; /* center everything first */
    position: relative;
    margin-top: 20px;
  }

  /* Yoga Flow Generator title */
  .top-header h1 {
    font-size: 36px;
    color: white;
    margin: 0;
  }

  /* login bar */
  #loginBar {
    position: absolute;
    right: 30px;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    padding: 10px 20px;
    border-radius: 25px;
    color: black;
    font-weight: bold;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  /* login link inside login bar */
  #loginBar a {
    background-color: #ff6666;
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.9em;
    transition: background 0.3s;
  }

  #loginBar a:hover {
    background-color: #ff3333;
  }

  /* Popup */
  .popup {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }
  .popup-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    max-width: 800px;
    width: 80%;
    text-align: center;
    overflow-y: auto;
    max-height: 80vh;
  }
  .popup-content ol {
    text-align: left;
  }
  .hidden {
    display: none;
  }
  .popup-content img {
    width: 60%;
    height: auto;
    margin-bottom: 15px;
  }
  .no-results {
    margin: 40px auto;
    padding: 20px 30px;
    max-width: 300px;
    background-color: white;
    border-radius: 12px;
    text-align: center;
    font-weight: bold;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    font-size: 1.2em;
    color: #333;
  }

  </style>
</head>
<body>
<!-- Header + Login container -->
<div class="top-header">
  <h1>Yoga Flow Generator</h1>
  <div id="loginBar">
    Logged in as <strong><?=htmlspecialchars($username)?></strong>
    <a href="logout.php">Log out</a>
  </div>
</div>

<!-- Button group wrapper for header buttons -->
<div class="button_group">
    <div class="header_buttons">
      <button id="home_button">Home</button>
      <button id="gemini_flow">Gemini Flow Auto-Generator</button>
    </div>
</div>

<!-- selection queue -->
<div id="queue">
    <h2>Selection Queue</h2>
    <div id="queueItems"></div>
    
    <!-- Button group wrapper for action buttons -->
    <div class="button_group">
        <div class="action-buttons">
            <button id="moveLeftBtn">Move Left</button>
            <button id="moveRightBtn">Move Right</button>
            <button id="clearQueueBtn">Clear Queue</button>
            <button id="saveQueueBtn">Save Queue</button>
        </div>
    </div>
</div>

<!-- Available Poses Section -->
<div class="available_poses_container">
    <h2>Pick From Available Poses</h2>

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

        <label for="catFilter"><strong>Filter by Category:</strong></label>
        <select id="catFilter">
          <option value="">All</option>
          <?php foreach($catSet as $t): ?>
            <option value="<?=htmlspecialchars($t)?>">
              <?=htmlspecialchars($t)?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="searchInput"><strong>Search by Name:</strong></label>
        <input type="text" id="searchInput" placeholder="Enter pose name...">
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
            data-category="<?=htmlspecialchars($row['category'])?>"
            data-procedures="<?=htmlspecialchars($row['procedures'])?>">
          <img src="<?=htmlspecialchars($link)?>" alt="<?=htmlspecialchars($row['english_name'])?>">
          <div class="description-overlay">
            <?=htmlspecialchars($row['sanskrit_name'])?>
          </div>
          <div class="name-caption"><?=htmlspecialchars($row['english_name'])?></div>
          <button class="info-button">i</button>
        </div>
      <?php endforeach; ?>

      <div id="noResultsBox" class="no-results" style="display:none;">
        No results found.
      </div>
    </div>

  <!-- Popup content -->
  <div id="infoPopup" class="popup hidden">
    <div class="popup-content">
      
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const thumbnails       = Array.from(document.querySelectorAll('.thumbnail-container'));
      const queueContainer   = document.getElementById('queueItems');
      const clearBtn         = document.getElementById('clearQueueBtn');
      const saveBtn          = document.getElementById('saveQueueBtn');
      const targetFilter     = document.getElementById('targetFilter');
      const catFilter        = document.getElementById('catFilter');
      const moveLeftBtn      = document.getElementById('moveLeftBtn');
      const moveRightBtn     = document.getElementById('moveRightBtn');
      const queueData        = JSON.parse(sessionStorage.getItem("queueData"));
      const infoButtons      = document.querySelectorAll('.info-button');
      const infoPopup        = document.getElementById('infoPopup');
      const popupContent     = document.querySelector('#infoPopup .popup-content');
      const searchInput      = document.getElementById('searchInput');

      let selectedItem = null;

      infoButtons.forEach(button => {
        button.addEventListener('click', (e) => {
          e.stopPropagation(); // stop click events from triggering adding
          const container = button.closest('.thumbnail-container');

          let steps = [];
          steps = JSON.parse(container.dataset.procedures);
          console.log("steps: ", steps);
          const stepsHtml = steps.map(step => `<li>${step}</li>`).join('');

          popupContent.innerHTML = `
            <img src="${container.dataset.link}" alt="${container.dataset.name}">
            <h2>${container.dataset.name}</h2>
            <p><strong>Description:</strong> ${container.dataset.description}</p>
            <button id="closePopup">Close</button>
            <ol>${stepsHtml}</ol>
          `;
          infoPopup.classList.remove('hidden');

          // Attach close handler again because the button is recreated
          document.getElementById('closePopup').addEventListener('click', () => {
            infoPopup.classList.add('hidden');
          });
        });
      });

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
        for (let i = 0; i < children.length - 1; i++) {
            console.log("i val: ", i);
            
            // Check if there is an abrupt transition
            if (warning_transitions[children[i].dataset.category].includes(children[i + 1].dataset.category)) {
                const warningElement = document.createElement('div');
                warningElement.classList.add('warning');
                warningElement.dataset.category = null;

                // add an onclick for resolving warnings
                warningElement.addEventListener('click', () => {
                  alert("Transition is abrupt! Add a kneeling posture between!");
                });

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
        console.log("cat: ", cat);

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
        if (!queueData || queueData.id == null) {
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
        let prev = selectedItem.previousElementSibling;
        if (prev) {
          if (prev.classList.contains('warning')) {
            prev = prev.previousElementSibling;
          }
          queueContainer.insertBefore(selectedItem, prev);
          queue_changed();
        }
      });

      // shift pose right
      moveRightBtn.addEventListener('click', () => {
        if (!selectedItem) return alert('Select a pose first.');
        let next = selectedItem.nextElementSibling;
        if (next){
          if (next.classList.contains('warning')) {
            next = next.nextElementSibling;
          }
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
        if (!queueData || !queueData.queueName) {
          qn = prompt('Name this queue:');
          if (!qn || !qn.trim()) return alert('A name is required.');
        } else {
          qn = queueData.queueName;
        }

        console.log(qn);
        console.log(names);
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

      targetFilter.addEventListener('change', applyFilters);
      catFilter.addEventListener('change', applyFilters);
      searchInput.addEventListener('input', applyFilters);

      function applyFilters() {
        const targetFilterValue = targetFilter.value.toLowerCase();
        const catFilterValue = catFilter.value.toLowerCase();
        const searchValue = searchInput.value.toLowerCase();
        
        let anyVisible = false; // Track if anything is still shown

        thumbnails.forEach(t => {
          const targets = t.dataset.targets
                            .split(',')
                            .map(s => s.trim().toLowerCase());
          const categories = t.dataset.category
                              .split(',')
                              .map(s => s.trim().toLowerCase());
          const name = t.dataset.name.toLowerCase();

          const targetMatch = !targetFilterValue || targets.includes(targetFilterValue);
          const catMatch = !catFilterValue || categories.includes(catFilterValue);
          const searchMatch = !searchValue || name.includes(searchValue);

          const visible = (targetMatch && catMatch && searchMatch);
          t.style.display = visible ? '' : 'none';

          if (visible) {
            anyVisible = true;
          }
        });

        const noResultsBox = document.getElementById('noResultsBox');
        if (!anyVisible) {
          noResultsBox.style.display = 'block';
        } else {
          noResultsBox.style.display = 'none';
        }
      }

      gemini_flow.addEventListener('click', () => {
        window.location.href = 'gemini_query.html'; 
      });
      const homeButton = document.getElementById('home_button');
        homeButton.addEventListener('click', () => {
          window.location.href = 'home.php'; 
      });
    });
  </script>
</body>
</html>
