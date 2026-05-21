import os
os.environ['PROTOCOL_BUFFERS_PYTHON_IMPLEMENTATION'] = 'python'
os.environ['TF_USE_LEGACY_KERAS'] = '1'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2' # Faltu warnings chupane ke liye

import cv2
import numpy as np
from flask import Flask, request, jsonify
from flask_cors import CORS
from deepface import DeepFace
import base64
import mediapipe as mp

app = Flask(__name__)
CORS(app) 

os.makedirs('saved_faces', exist_ok=True)
try:
    face_mesh = mp.solutions.face_mesh.FaceMesh(
        max_num_faces=1,
        refine_landmarks=True,
        min_detection_confidence=0.5,
        min_tracking_confidence=0.5
    )
except AttributeError:
    print("Error: Mediapipe solutions still not found. Try Step 1 again.")

# ==========================================
# HEAD POSE DETECTION (LIVENESS)
# ==========================================
def get_head_pose(image):
    img_h, img_w, img_c = image.shape
    results = face_mesh.process(cv2.cvtColor(image, cv2.COLOR_BGR2RGB))
    
    if not results.multi_face_landmarks:
        return "No Face"

    face_2d = []
    face_3d = []
    for face_landmarks in results.multi_face_landmarks:
        for idx, lm in enumerate(face_landmarks.landmark):
            if idx in [33, 263, 1, 61, 291, 199]:
                x, y = int(lm.x * img_w), int(lm.y * img_h)
                face_2d.append([x, y])
                face_3d.append([x, y, lm.z])

    face_2d = np.array(face_2d, dtype=np.float64)
    face_3d = np.array(face_3d, dtype=np.float64)
    focal_length = 1 * img_w
    cam_matrix = np.array([[focal_length, 0, img_h / 2], [0, focal_length, img_w / 2], [0, 0, 1]])
    dist_matrix = np.zeros((4, 1), dtype=np.float64)

    _, rot_vec, _ = cv2.solvePnP(face_3d, face_2d, cam_matrix, dist_matrix)
    rmat, _ = cv2.Rodrigues(rot_vec)
    angles, _, _, _, _, _ = cv2.RQDecomp3x3(rmat)

    x, y = angles[0] * 360, angles[1] * 360
    if y < -15: return "Left"
    if y > 15: return "Right"
    if x < -10: return "Down"
    if x > 10: return "Up"
    return "Forward"

# ==========================================
# ROUTE 1: REGISTRATION (ENROLLMENT)
# ==========================================
@app.route('/process_frame', methods=['POST'])
def process_frame():
    try:
        data = request.json
        target_dir = data['target']
        email = data.get('email', 'user')
        img_data = base64.b64decode(data['image'].split(',')[1])
        nparr = np.frombuffer(img_data, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        current_pose = get_head_pose(img)

        if current_pose == target_dir:
            filename = f"saved_faces/{email}_{target_dir}.jpg"
            cv2.imwrite(filename, img)
            try:
                embedding = DeepFace.represent(img_path=filename, model_name='Facenet', enforce_detection=False)[0]['embedding']
                return jsonify({"status": "match", "embedding": embedding})
            except Exception:
                return jsonify({"status": "error", "message": "Face not clear"})
        
        return jsonify({"status": "scanning", "current_pose": current_pose})
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)})

# ==========================================
# ROUTE 2: LOGIN (AUTHENTICATION)
# ==========================================
@app.route('/verify_login', methods=['POST'])
def verify_login():
    try:
        data = request.json
        target_dir = data['target'] # random angel will be from js for example left right down
        email = data.get('email')

        # it will check the image is place in folder or not
        saved_img_path = f"saved_faces/{email}_Forward.jpg"
        if not os.path.exists(saved_img_path):
            return jsonify({"status": "error", "message": "Face data not found! Please register first."})

        img_data = base64.b64decode(data['image'].split(',')[1])
        nparr = np.frombuffer(img_data, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        current_pose = get_head_pose(img)

        if current_pose == target_dir:
            try:
                result = DeepFace.verify(
                    img1_path=img, 
                    img2_path=saved_img_path, 
                    model_name='Facenet', 
                    enforce_detection=False
                )
                
                if result["verified"]:
                    return jsonify({"status": "success", "message": "Face Matched!"})
                else:
                    return jsonify({"status": "failed", "message": "Face does not match! Access Denied."})
            
            except Exception as e:
                return jsonify({"status": "error", "message": "Face not clear enough for verification."})
        
        return jsonify({"status": "scanning", "current_pose": current_pose})

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)})


if __name__ == '__main__':
    app.run(port=5000)