from deepface import DeepFace

result = DeepFace.verify("imagev1.jpg", "imagev2.jpg")

print("\n===== Face Verification Result =====")
print(f"Verified       : {result['verified']}")
print(f"Confidence     : {result['confidence']:.2f}%")
print(f"Distance       : {result['distance']:.4f}")
print(f"Threshold      : {result['threshold']}")
print(f"Model Used     : {result['model']}")
print(f"Time Taken     : {result['time']} sec")