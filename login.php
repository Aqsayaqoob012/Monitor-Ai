<?php
/* 1. DATABASE CONNECTION */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "monitor_ai";
$conn = mysqli_connect($host, $user, $pass, $db);

session_start();

$msg = "";
$msg_type = "";

/* 2. REGISTRATION LOGIC */
if (isset($_POST['signup_action'])) {
    $name     = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $avatar   = $_POST['avatar_base64']; 

    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "This email is already registered.";
        $msg_type = "err";
    } else {
        $sql = "INSERT INTO users (fullname, email, password, avatar) VALUES ('$name', '$email', '$password', '$avatar')";
        if (mysqli_query($conn, $sql)) {
            $msg = "Account created! You can now Sign In.";
            $msg_type = "ok";
        } else {
            $msg = "Error: " . mysqli_error($conn);
            $msg_type = "err";
        }
    }
}

/* 3. LOGIN LOGIC */
if (isset($_POST['login_action'])) {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    $user   = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['fullname'];
        header("Location: dashboard.php");
        exit();
    } else {
        $msg = "Invalid email or password.";
        $msg_type = "err";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Monitor AI - Secure Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  
  <!-- Load AI Library -->
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

  <style>
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#03060f;--surface:#0d1425;
      --border:rgba(80,140,255,.2);--accent:#3b82f6;--accent2:#60a5fa;
      --text:#e8edf8;--muted:#7a8aaa;--err:#ef4444;
    }
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;position:relative;overflow-x:hidden}
    h1,h2{font-family:'Syne',sans-serif}
    #bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none}
    .grid-overlay{position:fixed;inset:0;z-index:0;pointer-events:none;
      background-image:linear-gradient(rgba(59,130,246,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(59,130,246,.04) 1px,transparent 1px);
      background-size:60px 60px;mask-image:radial-gradient(ellipse 80% 80% at 50% 50%,black 20%,transparent 100%)}
    .login-container{position:relative;z-index:10;height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
    .login-card{background:rgba(13,20,37,.92);border:1px solid var(--border);border-radius:16px;backdrop-filter:blur(20px);padding:1.5rem 1.8rem;width:100%;max-width:420px;box-shadow:0 32px 64px rgba(0,0,0,.4)}
    .logo-section{text-align:center;margin-bottom:1rem}
    .logo-img{width:56px;height:56px;border-radius:50%;object-fit:cover;margin-bottom:8px;border:2px solid rgba(80,140,255,.3);box-shadow:0 6px 20px rgba(59,130,246,.2)}
    .logo-heading{font-family:'Syne',sans-serif;font-size:1.35rem;font-weight:800;letter-spacing:-.3px}
    .logo-heading span{color:var(--accent2)}
    .logo-sub{font-size:.72rem;color:var(--muted);margin-top:2px}
    .tabs{display:flex;margin-bottom:1.2rem;border-bottom:1px solid rgba(80,140,255,.2)}
    .tab{flex:1;padding:.6rem 0;border:none;background:none;color:rgba(232,237,248,.4);font-family:'Syne',sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;transition:color .25s;border-bottom:2px solid transparent}
    .tab.active{color:#e8edf8;border-bottom-color:var(--accent)}
    .form{display:none}.form.active{display:block;animation:slideIn .25s ease}
    @keyframes slideIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .form-group{margin-bottom:.85rem}
    .form-group label{display:block;font-size:.76rem;font-weight:500;color:rgba(232,237,248,.7);margin-bottom:.25rem}
    .form-group input{width:100%;background:rgba(13,20,37,.8);border:1px solid rgba(80,140,255,.25);border-radius:8px;padding:.6rem .8rem;color:#e8edf8;font-size:.84rem;outline:none;transition:border-color .25s}
    .form-group input:focus{border-color:var(--accent)}
    .camera-box { position: relative; width: 100%; height: 180px; background: #000; border-radius: 12px; overflow: hidden; border: 1px solid var(--border); margin-bottom: 10px; }
    #camera-stream, #login-stream { width: 100%; height: 100%; object-fit: cover; }
    #photo-preview { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: none; z-index: 2; }
    .btn-primary{width:100%;background:linear-gradient(135deg,#3b82f6 0%,#60a5fa 100%);color:white;border:none;padding:.7rem;border-radius:8px;font-weight:500;cursor:pointer;transition:all .25s;margin-top:.2rem}
    .toast{position:fixed;top:20px;right:20px;z-index:999;padding:10px 16px;border-radius:8px;font-size:.78rem;font-weight:600;color:#fff;transform:translateX(120%);transition:transform .3s;}
    .toast.show{transform:translateX(0)}
    .toast-ok{background:rgba(16,185,129,.92)}
    .toast-err{background:rgba(239,68,68,.92)}
    #ai-status { font-size: 0.72rem; text-align: center; margin-top: 5px; color: var(--accent2); font-weight: 600; min-height: 14px;}
  </style>
</head>
<body>

  <canvas id="bg-canvas"></canvas>
  <div class="grid-overlay"></div>
  <img id="ref-image" style="display:none;" src="" crossorigin="anonymous">

  <div class="login-container">
    <div class="login-card">

      <div class="logo-section">
        <img class="logo-img" src="https://z-cdn-media.chatglm.cn/files/d14597eb-c852-4886-9f40-02e45359a007.jpg" alt="Monitor AI" />
        <h1 class="logo-heading">Monitor <span>AI</span></h1>
        <p class="logo-sub">Intelligent monitoring & assessment platform</p>
      </div>

      <div class="tabs">
        <button class="tab active" data-tab="login">Sign In</button>
        <button class="tab" data-tab="signup">Create Account</button>
      </div>

      <!-- Login Form -->
      <form id="loginForm" class="form active" method="POST">
        <div id="login-fields">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="l-email" required />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="l-pass" required />
            </div>
            <button type="button" class="btn-primary" onclick="prepareFaceVerify()">Next: Face Verify</button>
        </div>

        <div id="login-verify" style="display:none;">
            <div class="camera-box">
                <video id="login-stream" autoplay playsinline muted></video>
            </div>
            <div id="ai-status">Initializing AI Models...</div>
            <button type="submit" name="login_action" class="btn-primary" id="final-login-btn" style="display:none">Verified: Sign In</button>
        </div>
      </form>

      <!-- Signup Form -->
      <form id="signupForm" class="form" method="POST">
        <div class="form-group"><label>Full Name</label><input type="text" name="fullname" id="s-name" required /></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" id="s-email" required /></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" id="s-pass" required /></div>
        <div class="form-group"><label>Confirm Password</label><input type="password" id="s-confirm" required /></div>
        
        <div class="form-group">
          <label>Face Enrollment</label>
          <div class="camera-box">
            <video id="camera-stream" autoplay playsinline></video>
            <img id="photo-preview" alt="Captured">
          </div>
          <div style="display:flex; gap:10px;">
            <button type="button" class="btn-primary" id="start-cam" onclick="startCamera('camera-stream', 'start-cam', 'cap-btn')">Open Camera</button>
            <button type="button" class="btn-primary" id="cap-btn" style="display:none" onclick="capture('camera-stream','photo-preview','avatar_base64','cap-btn','ret-btn')">Capture</button>
            <button type="button" class="btn-primary" id="ret-btn" style="display:none" onclick="retake('camera-stream','photo-preview','avatar_base64','cap-btn','ret-btn')">Retake</button>
          </div>
          <input type="hidden" name="avatar_base64" id="avatar_base64">
        </div>
        <button type="submit" name="signup_action" class="btn-primary" onclick="return validateSignup()">Create Account</button>
      </form>

    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    /* ========== TAB SWITCHING ========== */
    document.querySelectorAll('.tab').forEach(t => t.addEventListener('click', function() {
        document.querySelectorAll('.tab, .form').forEach(el => el.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(this.dataset.tab + 'Form').classList.add('active');
    }));

    /* ========== CAMERA UTILS ========== */
    async function startCamera(vId, sB, cB) {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            document.getElementById(vId).srcObject = stream;
            if(sB) document.getElementById(sB).style.display = 'none';
            if(cB) document.getElementById(cB).style.display = 'block';
        } catch (err) { toast("Camera Access Error", "err"); }
    }

    function capture(vId, pId, hId, cB, rB) {
        const v = document.getElementById(vId);
        const canv = document.createElement('canvas');
        canv.width = 160; canv.height = 160;
        canv.getContext('2d').drawImage(v, 0, 0, 160, 160);
        const d = canv.toDataURL('image/jpeg', 0.7);
        document.getElementById(hId).value = d;
        document.getElementById(pId).src = d;
        v.style.display = 'none'; document.getElementById(pId).style.display = 'block';
        document.getElementById(cB).style.display = 'none'; document.getElementById(rB).style.display = 'block';
    }

    function retake(vId, pId, hId, cB, rB) {
        document.getElementById(vId).style.display = 'block'; document.getElementById(pId).style.display = 'none';
        document.getElementById(cB).style.display = 'block'; document.getElementById(rB).style.display = 'none';
        document.getElementById(hId).value = "";
    }

    /* ========== AI LOADING ========== */
    async function loadModels() {
        if (typeof faceapi === 'undefined') {
            setTimeout(loadModels, 500);
            return;
        }
        const MODEL_URL = './models';
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            document.getElementById('ai-status').textContent = "AI Ready. Enter Email to begin.";
            console.log("AI Models Loaded Successfully");
        } catch (e) {
            console.error("Failed to load models:", e);
            document.getElementById('ai-status').textContent = "Model Error. Ensure './models' folder is present.";
        }
    }
    window.onload = loadModels;

    /* ========== POINT 3: UPDATED VERIFICATION FUNCTIONS ========== */
async function prepareFaceVerify() {
    const email = document.getElementById('l-email').value;
    const pass = document.getElementById('l-pass').value;

    if (!email || !pass) {
        return toast("Enter email and password first", "err");
    }

    try {
        const status = document.getElementById('ai-status');
        status.textContent = "Connecting to Secure Vault...";
        
        // 1. Fetch user image from your PHP script
        const res = await fetch(`get_user_image.php?email=${email}`);
        const data = await res.json();

        if (data.status === 'success') {
            // 2. Load the reference image into the hidden <img> tag
            const refImg = document.getElementById('ref-image');
            refImg.src = data.image;

            // 3. IMPORTANT: Wait for the image to actually load before switching UI
            refImg.onload = async () => {
                document.getElementById('login-fields').style.display = 'none';
                document.getElementById('login-verify').style.display = 'block';
                
                // 4. Start the camera and matching logic
                await startMatchingProcess();
            };
        } else {
            toast("Account not found in database", "err");
            status.textContent = "Ready.";
        }
    } catch (err) {
        console.error("Fetch Error:", err);
        toast("Database Connection Error", "err");
    }
}

    async function startMatchingProcess() {
        const video = document.getElementById('login-stream');
        const status = document.getElementById('ai-status');

        try {
            // 1. Start the Camera Stream
            const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
            video.srcObject = stream;

            status.textContent = "Analyzing Face Data...";

            // 2. Wait for AI to process the Reference Image (the one from DB)
            const refImg = document.getElementById('ref-image');
            const refRes = await faceapi.detectSingleFace(refImg, new faceapi.TinyFaceDetectorOptions())
                                          .withFaceLandmarks()
                                          .withFaceDescriptor();

            if (!refRes) {
                status.textContent = "Error: Reference image is not clear.";
                return;
            }

            const faceMatcher = new faceapi.FaceMatcher(refRes);
            status.textContent = "Looking for your face...";

            // 3. Begin Real-time matching
            const matchInterval = setInterval(async () => {
                const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                                                .withFaceLandmarks()
                                                .withFaceDescriptor();

                if (detections) {
                    const match = faceMatcher.findBestMatch(detections.descriptor);
                    const score = Math.round((1 - match.distance) * 100);
                    
                    status.textContent = `Matching: ${score}%`;

                    // If match is better than the threshold (0.45 distance is roughly 55% similarity)
                    if (match.distance < 0.45) {
                        status.style.color = "#10b981";
                        status.textContent = "Identity Verified! Login Unlocked.";
                        document.getElementById('final-login-btn').style.display = 'block';
                        
                        // Stop checking once verified
                        clearInterval(matchInterval);
                    }
                } else {
                    status.textContent = "Position your face in the camera...";
                }
            }, 600);

        } catch (err) {
            console.error("Camera/AI Error:", err);
            status.textContent = "Camera access failed.";
        }
    }

    function validateSignup() {
        if(document.getElementById('s-pass').value !== document.getElementById('s-confirm').value) {
            toast("Passwords mismatch", "err"); return false;
        }
        if(!document.getElementById('avatar_base64').value) {
            toast("Please enroll face first", "err"); return false;
        }
        return true;
    }

    function toast(m,t){
      const el=document.getElementById('toast'); el.textContent=m;
      el.className=`toast toast-${t||'ok'} show`;
      setTimeout(()=>el.classList.remove('show'),3500);
    }

    /* ========== PARTICLES BACKGROUND ========== */
    var cv=document.getElementById('bg-canvas'),cx=cv.getContext('2d'),W,H,pts=[];
    function rsz(){W=cv.width=innerWidth;H=cv.height=innerHeight}
    function init(){pts=[];for(var i=0;i<80;i++)pts.push({x:Math.random()*W,y:Math.random()*H,r:Math.random()*1.4+.3,vx:(Math.random()-.5)*.35,vy:(Math.random()-.5)*.35,o:Math.random()*.5+.1})}
    function draw(){
      cx.clearRect(0,0,W,H);
      for(var i=0;i<pts.length;i++){for(var j=i+1;j<pts.length;j++){
        var dx=pts[i].x-pts[j].x,dy=pts[i].y-pts[j].y,d=Math.sqrt(dx*dx+dy*dy);
        if(d<130){cx.beginPath();cx.moveTo(pts[i].x,pts[i].y);cx.lineTo(pts[j].x,pts[j].y);cx.strokeStyle='rgba(59,130,246,'+((1-d/130)*.12)+')';cx.lineWidth=.6;cx.stroke()}
      }}
      for(var k=0;k<pts.length;k++){var p=pts[k];cx.beginPath();cx.arc(p.x,p.y,p.r,0,Math.PI*2);cx.fillStyle='rgba(96,165,250,'+p.o+')';cx.fill();p.x+=p.vx;p.y+=p.vy;if(p.x<0)p.x=W;if(p.x>W)p.x=0;if(p.y<0)p.y=H;if(p.y>H)p.y=0}
      requestAnimationFrame(draw);
    }
    addEventListener('resize',function(){rsz();init()});rsz();init();draw();

    <?php if($msg): ?> toast("<?= $msg ?>", "<?= $msg_type ?>"); <?php endif; ?>
  </script>
</body>
</html>