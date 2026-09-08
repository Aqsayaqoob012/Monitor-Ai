import sounddevice as sd
import queue
import json
import time
from vosk import Model, KaldiRecognizer

# =====================================================
# SETTINGS
# =====================================================

SAMPLE_RATE = 16000
BLOCK_SIZE = 8000

# Suspicious words / phrases
SUSPICIOUS_PHRASES = [
    "answer",
    "answers",
    "tell me",
    "what is the answer",
    "what's the answer",
    "give me the answer",
    "help me",
    "show me",
    "look at this",
    "what did you get",
    "send me",
    "copy",
    "cheat",
    "cheating"
]

# =====================================================
# LOAD VOSK MODEL
# =====================================================

MODEL_PATH = "vosk-model-small-en-us-0.15"

model = None
recognizer = None
audio_queue = queue.Queue()


# =====================================================
# AUDIO CALLBACK
# =====================================================

def audio_callback(indata, frames, time_info, status):
    if status:
        print(status)

    audio_queue.put(bytes(indata))


# =====================================================
# START VOICE DETECTION
# =====================================================

def start_voice_detection():
    global model, recognizer

    try:
        model = Model(MODEL_PATH)

        recognizer = KaldiRecognizer(
            model,
            SAMPLE_RATE
        )

        stream = sd.RawInputStream(
            samplerate=SAMPLE_RATE,
            blocksize=BLOCK_SIZE,
            dtype="int16",
            channels=1,
            callback=audio_callback
        )

        stream.start()

        print("Voice Detection Started")

        return stream

    except Exception as e:
        print("Voice detection error:", e)
        return None


# =====================================================
# CHECK VOICE
# =====================================================

def check_voice():

    if recognizer is None:
        return {
            "speech_detected": False,
            "text": "",
            "suspicious": False
        }

    detected_text = ""

    try:

        while not audio_queue.empty():

            data = audio_queue.get()

            if recognizer.AcceptWaveform(data):

                result = json.loads(
                    recognizer.Result()
                )

                detected_text = result.get(
                    "text",
                    ""
                ).lower()

                if detected_text:
                    print(
                        "Detected Speech:",
                        detected_text
                    )

    except Exception as e:
        print("Voice processing error:", e)

    suspicious = False

    for phrase in SUSPICIOUS_PHRASES:

        if phrase in detected_text:
            suspicious = True
            break

    return {
        "speech_detected": bool(detected_text),
        "text": detected_text,
        "suspicious": suspicious
    }


# =====================================================
# STOP VOICE DETECTION
# =====================================================

def stop_voice_detection(stream):

    if stream is not None:

        stream.stop()
        stream.close()

    print("Voice Detection Stopped")