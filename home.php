<?php
require 'config.php';
require_login();
$username = $_SESSION['username'];
$userId   = $_SESSION['user_id'];

$baseUrl = "https://zackw1.sg-host.com/chunk/images/";

// get thumbnail items and create target list
$itemStmt = $conn->prepare("
    SELECT *
      FROM supported_postures
  ORDER BY english_name ASC
");
$itemStmt->execute();
$itemsResult = $itemStmt->get_result();

// get user's saved queues
$queueStmt = $conn->prepare("
    SELECT
      _id    AS id,
      queue_name
    FROM saved_queues
   WHERE user_id = ?
ORDER BY created_at DESC
");
$queueStmt->bind_param("i", $userId);
$queueStmt->execute();
$savedResult = $queueStmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Home Page</title>
  <style>
body {
  background: url('./images/yoga_mat.jpg') no-repeat center center fixed;
  background-size: cover;
  margin: 0;
  padding: 0;
  font-family: Arial, sans-serif;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* Top Bar */
.top-bar {
  position: relative;
  text-align: center;
  padding: 30px 20px 0 20px;
}

.top-bar h1 {
  font-size: 40px;
  color: white;
}

#loginBar {
  position: absolute;
  top: 20px;
  right: 30px;
  background: white;
  padding: 10px 20px;
  border-radius: 25px;
  color: black;
  font-weight: bold;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 10px;
}

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

/* Layout */
.page-container {
  display: flex;
  align-items: flex-start;
  padding: 20px;
}

.side-wrapper {
  display: flex;
  justify-content: center;
  margin: 20px;
  width: 275px;
}

.side-column {
  background-color: rgba(34, 139, 34, 0.8);
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  color: white;
  position: fixed;
}

.side-column a {
  color: white;
  text-decoration: none;
}

.side-column a:hover {
  text-decoration: underline;
}

.main-content {
  flex: 1;
  background-color: #fff;
  padding: 0px 20px 20px 20px; /* top right bottom left */
  margin-left: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Headers */
h2 {
  background: white;
  padding: 12px 20px;
  border-radius: 12px;
  text-align: center;
  font-size: 24px;
  color: #333;
  margin: 0px auto;
  max-width: 400px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Saved Queues */
#savedQueuesSection {
  margin-bottom: 30px;
}

.saved-queue {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
  margin-bottom: 20px;
}

.saved-queue-btn {
  padding: 10px 20px;
  font-size: 1em;
  cursor: pointer;
  text-align: left;
  width: 100%;
}

.saved-queue-btn.selected {
  background-color: #28a745;
  border: 2px solid #006400;
}

.create-new-btn {
  padding: 16px;
  font-size: 1.5em;
  background-color: white;
  color: #228B22;
  border: 2px solid #228B22;
  border-radius: 25px;
  cursor: pointer;
  text-align: center;
  width: 100%;
  transition: background 0.3s, color 0.3s;
}

.create-new-btn:hover {
  background-color: #228B22;
  color: white;
}

/* Selection Queue */
#queue {
  padding: 10px;
  margin-top: 40px;
  border: none;
  background: none;
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

.queue-name {
  margin-top: 3px;
  font-size: 0.9em;
}

.queue-description-overlay {
  visibility: hidden;
  width: 100px;
  background: rgba(0, 0, 0, 0.75);
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

/* Workout Details */
.detail-item {
  border: 1px solid #ccc;
  border-radius: 6px;
  margin: 10px 0;
  padding: 10px;
  display: flex;
  align-items: center;
  background: #fafafa;
}

.detail-content {
  display: flex;
  width: 100%;
}

.detail-img {
  flex-shrink: 0;
  width: 150px;
  height: auto;
  margin-right: 20px;
  border-radius: 4px;
}

.detail-text {
  flex: 1;
}

.detail-text h3,
.detail-text h4 {
  margin: 0 0 5px 0;
}

.detail-text p {
  margin: 5px 0;
}

/* Action Buttons */
.action-buttons {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  gap: 20px;
}

.action-buttons button {
  padding: 10px 24px;
  font-size: 16px;
  border: none;
  border-radius: 25px;
  background-color: #6c63ff;
  color: white;
  cursor: pointer;
  transition: background 0.3s;
}

.action-buttons button:hover {
  background-color: #5751d9;
}

/* Divider */
.section-divider {
  border: none;
  height: 2px;
  background-color: #ccc;
  margin: 40px auto;
  width: 80%;
}
  </style>
</head>
<body>

<!-- Top bar -->
<div class="top-bar">
  <h1>Vinyasa Craft</h1>
  <div id="loginBar">
    Logged in as <strong><?=htmlspecialchars($username)?></strong>
    <a href="logout.php">Log out</a>
  </div>
</div>

<!-- Page container -->
<div class="page-container">
  <div class="side-wrapper">
    <div class="side-column">
      <div id="savedQueuesSection" class="saved-queue">
        <h2>My Saved Queues</h2>
        <div id="savedQueueSelect">
          <?php while ($q = $savedResult->fetch_assoc()): ?>
            <button 
              class="saved-queue-btn"
              data-id="<?=htmlspecialchars($q['id'])?>"
            >
              <?=htmlspecialchars($q['queue_name'])?>
            </button>
          <?php endwhile; ?>
        </div>
        <button id="createQueueBtn" class="create-new-btn">Create New</button>
      </div>
    </div>
  </div>

  <div class="main-content">
    <div id="queue">
      <h2>Selection Queue</h2>
      <div id="queueItems" data-qid=''></div>
      <div class="action-buttons">
        <button id="editQueueBtn">Edit Queue</button>
        <button id="deleteQueueBtn">Delete Queue</button>
        <button id="clearSelectionBtn">Clear Selection</button>
      </div>
    </div>

    <hr class="section-divider">

    <div id="detail_container">
      <h2>Workout Details</h2>
      <div id="detailItems"></div>
    </div>
  </div>
</div>
  <script>
    // get all the important values you need
    document.addEventListener('DOMContentLoaded', () => {
        const thumbnails       = <?= json_encode($itemsResult->fetch_all(MYSQLI_ASSOC)) ?>;
        const loadQueueBtns    = document.querySelectorAll('.saved-queue-btn');
        const queueContainer   = document.getElementById('queueItems');
        const detailContainer  = document.getElementById('detailItems');
        const editBtn          = document.getElementById('editQueueBtn');
        const deleteBtn        = document.getElementById('deleteQueueBtn');
        const createBtn        = document.getElementById('createQueueBtn');
        const savedQueueSelect = document.getElementById('savedQueueSelect');
        const clearBtn         = document.getElementById('clearSelectionBtn');
        const numSavedQueues = <?= $savedResult->num_rows ?>;
        
        // const values
        const MAX_SAVED = 10;
        
        var cache = [];

        function load_queues_thumb(q_obj) {
            console.log("q_obj: ", q_obj);
            console.log("Queue: ", q_obj.queue);
            queueContainer.innerHTML = "";
            queueContainer.dataset.qid = q_obj.id;
            q_obj.queue.forEach(name => {
                const thumb = Array.from(thumbnails)
                                    .find(el => el.english_name === name);
                if (thumb) {
                    // create element for the queue
                    const qi = document.createElement('div');
                    qi.className = 'queue-item';
                    qi.dataset.name = thumb.english_name;

                    const img = document.createElement('img');
                    img.src = thumb.link;
                    img.alt = thumb.english_name;

                    const overlay = document.createElement('div');
                    overlay.className = 'queue-description-overlay';
                    overlay.textContent = thumb.sanskrit_name;

                    const cap = document.createElement('div');
                    cap.className = 'queue-name';
                    cap.textContent = thumb.english_name;

                    qi.append(img, overlay, cap);
                    queueContainer.appendChild(qi);
                }
            });
            populate_details(q_obj);
        }

        // create all the detail cards.
        function populate_details(q_obj) {
          if (!q_obj.uniqueQueue) {
            // Extract unique names from the queue
            const uniqueNames = Array.from(new Set(q_obj.queue));
            // Now map the unique names back to the full items in the thumbnails array
            const uniqueQueue = uniqueNames.map(name => {
              const match = thumbnails.find(item => item.english_name == name);
              if (!match) console.warn("No match found for:", name);
              return match;
            }).filter(Boolean);
            q_obj.uniqueQueue = uniqueQueue;
          }
          
          detailContainer.innerHTML = '';
          q_obj.uniqueQueue.forEach(item => {
            const detailItem = document.createElement('div');
            detailItem.className = 'detail-item';
            detailItem.dataset.name = item.english_name;

            detailItem.innerHTML = `
              <div class="detail-content">
                <img src="${item.link}" alt="${item.english_name}" class="detail-img">
                <div class="detail-text">
                  <h3>${item.english_name}</h3>
                  <h4>${item.sanskrit_name}</h4>
                  <p><strong>Targeted Parts:</strong> ${item.targets || "N/A"}</p>
                  <ol id="procedure-list"></ol>
                </div>
              </div>
            `;

            const proceduresArray = JSON.parse(item.procedures);
            // Now safely add steps
            const procedureList = detailItem.querySelector('#procedure-list');
            proceduresArray.forEach(step => {
              const li = document.createElement('li');
              li.textContent = step;
              procedureList.appendChild(li);
            });

            detailContainer.appendChild(detailItem);
          }); 
        }

        // edit button listener
        editBtn.addEventListener('click', () => {
            const curr_qid = queueContainer.dataset.qid;
            const curr_q = cache.find(q => q.id === curr_qid);
            if (curr_q) {
              sessionStorage.setItem("queueData", JSON.stringify(curr_q));
              window.location.href = "index.php";
            } else {
              alert("Could not find the selected queue. Please try again.");
            }
        });

        // delete button listener
        deleteBtn.addEventListener('click', () => {
            const curr_qid = queueContainer.dataset.qid;
            if (!curr_qid) return alert("No queue selected"); 
            
            if (!confirm("Are you sure you want to delete this queue?")) return;

            fetch('delete_queue.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ queueId: curr_qid })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                alert("Queue deleted!");
                queueContainer.dataset.qid = null;
                window.location.reload();
                } else {
                alert("Error: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Something went wrong.");
            });
        });

        // create button listener
        createBtn.addEventListener('click', () => {
            // check for max saved
            if (numSavedQueues >= MAX_SAVED) {
                return alert('You have reached the maximum number of saved queues.');
            } else {
                // send empty queue
                sessionStorage.setItem("queueData", JSON.stringify({
                    id: null,
                    queueName: null,
                    queue: [], 
                }));
                window.location.href = "index.php";
            }
        });

        // for scrolling
        queueContainer.addEventListener('click',(event)=>{
          const child = event.target.closest('.queue-item');
          if (!child || !queueContainer.contains(child)) return;
          const child_thumb_name = child.dataset.name;
          
          const childArray = Array.from(detailContainer.children);
          const foundChild = childArray.find(c => c.dataset.name == child_thumb_name); 
          foundChild.scrollIntoView({ behavior: 'smooth' });
        });    

        // attach a event listener to every button, will load the queue as needed.
        loadQueueBtns.forEach(b =>{
            b.addEventListener('click', () => {
              const childWithClass = savedQueueSelect.querySelector('.selected');
              console.log("childWithClass:", childWithClass);
              if (childWithClass) childWithClass.classList.remove('selected');
            
              b.classList.add('selected');
              const qid = b.dataset.id;
              console.log("Query qid: ", qid);

              // check the cache obj to prevent rerender
              const cached_obj = cache.find(q => q.id == qid);
              if (cached_obj) {
                  console.log("Got from queue");
                  load_queues_thumb(cached_obj);
              } else {
                  console.log("Fetched");
                  fetch(`get_queue.php?id=${encodeURIComponent(qid)}`)
                  .then(r => r.json())
                  .then(d => {
                      if (!d.success) return alert('Error: ' + d.message);
                      q_obj = {
                          id: qid,
                          queueName: d.queueName,
                          queue: d.queue
                      }
                      cache.push(q_obj);
                      load_queues_thumb(q_obj);
                  })
                  .catch(e => { console.error(e); alert('Load failed.'); });
              }
              window.scrollTo({top: 0, behavior: 'smooth'});
            });
        
          });

          // clear btn script
          clearBtn.addEventListener('click', () => {
            const selectedBtn = document.querySelector('.saved-queue-btn.selected');
            if (selectedBtn) {
              selectedBtn.classList.remove('selected');
            }
            queueContainer.innerHTML = '';
            detailContainer.innerHTML = '';
            queueContainer.dataset.qid = '';
          });
      })  
  </script>
</body>
</html>