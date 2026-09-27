import time

from transformers import AutoTokenizer, AutoModelForSeq2SeqLM

MODEL_NAME = "thenHung/english-grammar-error-correction-t5-seq2seq"

print("Loading model...")

tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)
model = AutoModelForSeq2SeqLM.from_pretrained(MODEL_NAME)

tests = [
    "I didn't knew about this.",
    "I am working here since two years.",
    "We discussed about this yesterday.",
    "I will do it in Monday.",
    "He suggested to change it.",
    "She don't know what happened.",
    "I have seen him yesterday.",
    "There is too many problems.",
    "I look forward to meet you.",
    "Could you please send me more informations?",
]

for text in tests:
    inputs = tokenizer(text, return_tensors="pt")

    start = time.perf_counter()

    outputs = model.generate(
        **inputs,
        max_new_tokens=128,
    )

    elapsed = time.perf_counter() - start

    outputs = model.generate(
        **inputs,
        max_new_tokens=128,
    )

    result = tokenizer.decode(outputs[0], skip_special_tokens=True)

    print(f"Original:  {text}")
    print(f"Corrected: {result}")
    print(f"Time:      {elapsed:.3f}s")
    print()
