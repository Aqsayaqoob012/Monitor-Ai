# MonitorAI

## AI-Based Real-Time Cheating Detection System

MonitorAI is an AI-based framework designed to assist in detecting suspicious activities during online examinations using multiple monitoring features.

## Current Modules

* **Face Detection** — detects the candidate's face.
* **Head Pose Detection** — monitors head movement and orientation.
* **Eye Movement Detection** — tracks eye/iris movement and direction.
* **Voice Detection** — detects speech and identifies predefined suspicious phrases.
* **Object Detection** — detects potentially unauthorized objects using YOLO.

## Project Files

| File                 | Purpose                                 |
| -------------------- | --------------------------------------- |
| `main.py`            | Main application and module integration |
| `app.py`             | Application/backend functionality       |
| `HeadPose.py`        | Head pose detection                     |
| `EyeMovement.py`     | Eye movement detection                  |
| `VoiceDetection.py`  | Voice/speech detection                  |
| `index.html`         | Main web interface                      |
| `index.php`          | PHP page/backend                        |
| `login.html`         | Login interface                         |
| `login.php`          | Login processing                        |
| `get_user_image.php` | Retrieves user image                    |
| `requirements.txt`   | Required Python packages                |

## Python Requirements

The following Python packages are required:

* OpenCV
* MediaPipe
* Ultralytics YOLO
* NumPy
* SoundDevice
* Vosk

Install them using:

```bash
pip install -r requirements.txt
```

## Voice Detection Model

Voice Detection uses the Vosk offline speech-recognition model.

The required model folder is:

```text
vosk-model-small-en-us-0.15
```

The model folder should be placed in the project root directory alongside `VoiceDetection.py`.

## Running the Project

After installing Python and the required packages:

```bash
python main.py
```

> The final execution command may be updated after all modules are integrated and tested.

## Development Status

The individual detection modules are being developed and tested separately before integration into the main MonitorAI application.

### Current Status

* Head Pose Detection — Completed
* Eye Movement Detection — Completed
* Voice Detection — Completed
* Module Integration — Pending
* Final System Testing — Pending
