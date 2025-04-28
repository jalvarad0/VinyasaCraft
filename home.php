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
  <title>Thumbnail Queue Manager</title>
  <style>
    /* Main content */
    /* Basic reset for margins and padding */
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  .page-container {
    display: grid;
    grid-template-columns: 250px 1fr;
    height: 100vh;
  }
  .side-column {
    background-color: #f4f4f4;
    padding: 20px;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
  }
  .main-content {
    overflow-y: auto;
    padding: 20px;
    max-height: 100vh;
    background-color: #fff;
  }

  /* Optional styling for the content */
  .content {
    height: 2000px; /* Example height to enable scrolling */
    background: #e9ecef;
    padding: 20px;
  }

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

    /* saved queue button container */
    .saved-queue {
      display: grid;
      grid-template-columns: 1fr; /* Adjust this to set how many buttons per row you want */
      grid-gap: 10px; /* Space between buttons */
      margin-bottom: 20px;
    }

    .saved-queue-btn {
      padding: 10px 20px; /* Match action buttons size */
      font-size: 1em; /* Match action buttons font size */
      cursor: pointer;
      text-align: left;
      width: 100%; /* Ensure buttons fill the container */
    }

    .selected {
      background-color: #28a745; /* Change color to indicate selection */
      border: 2px solid #006400; /* Optional: add a border or any additional styling */
    }

    /* Create new button */
    .create-new-btn {
      padding: 12px;
      font-size: 1em;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-align: center;
      width: 100%;
    }

    .create-new-btn:hover {
      background-color: #218838; /* Darker shade for hover effect */
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
    /* detail items */
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
      width: 120px;
      height: auto;
      margin-right: 20px;
      border-radius: 4px;
      object-fit: cover;
    }
    .detail-text {
      flex: 1;
    }
    .detail-text h3, .detail-text h4 {
      margin: 0 0 5px 0;
    }
    .detail-text p {
      margin: 5px 0;
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

  <div class="page-container">
    <div class="side-column">
     
      <!-- saved queue dropdown -->
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
        <button id="createQueueBtn" class="action-buttons">Create New</button>
      </div>

      <!-- login/logout -->
      <div id="loginBar">
        Logged in as <strong><?=htmlspecialchars($username)?></strong>
        <a href="logout.php">Log out</a>
      </div>

    </div>
    <div class="main-content">
      <!-- selection queue -->
      <div id="queue">
        <h2>Selection Queue</h2>
        <div 
        id="queueItems"
        data-qid=''
        ></div>
        <div class="action-buttons">
          <button id="editQueueBtn">Edit Queue</button>
          <button id="deleteQueueBtn">Delete Queue</button>
        </div>
      </div>

      <!-- movement details -->
      <div id="detail_container">
        <h2>Workout Details</h2>
        <div 
        id="detailItems"
        ></div>
      </div>

    </div>
  <div>
  


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
        const numSavedQueues = <?= $savedResult->num_rows ?>;
        
        // const values
        const MAX_SAVED = 10;
        
        var cache = [];

        function load_queues_thumb(q_obj) {
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
              return thumbnails.find(item => item.english_name === name);
            });
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
            const curr_q = cache.find(q => q.id = curr_qid);
            sessionStorage.setItem("queueData", JSON.stringify(curr_q));
            window.location.href = "index.php";
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
            });
        })
    });
  </script>
</body>
</html>