import cv2
import mediapipe as mp

# =====================================================
# MEDIAPIPE FACE MESH
# =====================================================

mp_face_mesh = mp.solutions.face_mesh

face_mesh = mp_face_mesh.FaceMesh(
    max_num_faces=1,
    refine_landmarks=True,
    min_detection_confidence=0.5,
    min_tracking_confidence=0.5
)


# =====================================================
# EYE LANDMARKS
# =====================================================

# Right eye
RIGHT_EYE_LEFT_CORNER = 33
RIGHT_EYE_RIGHT_CORNER = 133
RIGHT_IRIS = [469, 470, 471, 472]

# Left eye
LEFT_EYE_LEFT_CORNER = 362
LEFT_EYE_RIGHT_CORNER = 263
LEFT_IRIS = [474, 475, 476, 477]


# =====================================================
# GET AVERAGE IRIS POSITION
# =====================================================

def get_iris_center(landmarks, iris_points):

    x = sum(
        landmarks[i].x for i in iris_points
    ) / len(iris_points)

    y = sum(
        landmarks[i].y for i in iris_points
    ) / len(iris_points)

    return x, y


# =====================================================
# EYE MOVEMENT DETECTION
# =====================================================

def get_eye_movement(frame):

    rgb = cv2.cvtColor(
        frame,
        cv2.COLOR_BGR2RGB
    )

    results = face_mesh.process(rgb)

    eye_direction = "UNKNOWN"

    if not results.multi_face_landmarks:
        return {
            "eye_direction": eye_direction,
            "eye_detected": False
        }

    landmarks = results.multi_face_landmarks[0].landmark

    # -------------------------------------------------
    # RIGHT EYE
    # -------------------------------------------------

    right_iris_x, right_iris_y = get_iris_center(
        landmarks,
        RIGHT_IRIS
    )

    right_left_x = landmarks[
        RIGHT_EYE_LEFT_CORNER
    ].x

    right_right_x = landmarks[
        RIGHT_EYE_RIGHT_CORNER
    ].x

    right_eye_width = abs(
        right_right_x - right_left_x
    )

    if right_eye_width == 0:
        return {
            "eye_direction": "UNKNOWN",
            "eye_detected": False
        }

    right_ratio = (
        right_iris_x - min(
            right_left_x,
            right_right_x
        )
    ) / right_eye_width


    # -------------------------------------------------
    # LEFT EYE
    # -------------------------------------------------

    left_iris_x, left_iris_y = get_iris_center(
        landmarks,
        LEFT_IRIS
    )

    left_left_x = landmarks[
        LEFT_EYE_LEFT_CORNER
    ].x

    left_right_x = landmarks[
        LEFT_EYE_RIGHT_CORNER
    ].x

    left_eye_width = abs(
        left_right_x - left_left_x
    )

    if left_eye_width == 0:
        return {
            "eye_direction": "UNKNOWN",
            "eye_detected": False
        }

    left_ratio = (
        left_iris_x - min(
            left_left_x,
            left_right_x
        )
    ) / left_eye_width


    # -------------------------------------------------
    # AVERAGE BOTH EYES
    # -------------------------------------------------

    average_ratio = (
        right_ratio + left_ratio
    ) / 2


    # -------------------------------------------------
    # DETERMINE EYE DIRECTION
    # -------------------------------------------------

    if average_ratio < 0.40:

        eye_direction = "LEFT"

    elif average_ratio > 0.60:

        eye_direction = "RIGHT"

    else:

        eye_direction = "CENTER"


    return {
        "eye_direction": eye_direction,
        "eye_detected": True
    }