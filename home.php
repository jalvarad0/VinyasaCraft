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

  <!-- saved queue dropdown -->
  <div id="savedQueuesSection">
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

  <script>
    // get all the important values you need
    document.addEventListener('DOMContentLoaded', () => {
        const thumbnails       = <?= json_encode($itemsResult->fetch_all(MYSQLI_ASSOC)) ?>;
        const loadQueueBtns    = document.querySelectorAll('.saved-queue-btn');
        const queueContainer   = document.getElementById('queueItems');
        const editBtn          = document.getElementById('editQueueBtn');
        const deleteBtn        = document.getElementById('deleteQueueBtn');
        const createBtn        = document.getElementById('createQueueBtn');
        const savedQueueSelect = document.getElementById('savedQueueSelect');
        const numSavedQueues = <?= $savedResult->num_rows ?>;
        
        // const values
        const MAX_SAVED = 10;
        
        var cache = [];

        function load_queues_thumb(q_obj) {
            queueContainer.innerHTML = "";
            queueContainer.dataset.qid = q_obj.id;
            q_obj.queue.forEach(name => {
                const thumb = Array.from(thumbnails)
                                    .find(el => el.name === name);
                if (thumb) {
                    const qi = document.createElement('div');
                    qi.className = 'queue-item';
                    qi.dataset.name = thumb.name;

                    const img = document.createElement('img');
                    img.src = thumb.link;
                    img.alt = thumb.name;

                    const overlay = document.createElement('div');
                    overlay.className = 'queue-description-overlay';
                    overlay.textContent = thumb.description;

                    const cap = document.createElement('div');
                    cap.className = 'queue-name';
                    cap.textContent = thumb.name;

                    qi.append(img, overlay, cap);
                    queueContainer.appendChild(qi);
                }
            });
        }

        // edit button listener
        editBtn.addEventListener('click', () => {
            const curr_qid = queueContainer.dataset.qid;
            const curr_q = cache.find(q => q.id = curr_qid);
            sessionStorage.setItem("queueData", JSON.stringify(curr_q));
            window.location.href = "index.php";
        });

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

        function deleteQueue(queueId) {
            
        }

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

        // attach a event listener to every button, will load the queue as needed.
        loadQueueBtns.forEach(b =>{
            b.addEventListener('click', () => {
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